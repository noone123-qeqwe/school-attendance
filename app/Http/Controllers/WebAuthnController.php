<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WebauthnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WebAuthnController extends Controller
{
    public function debug()
    {
        $auth_challenge = session('webauthn.auth_challenge');
        $register_challenge = session('webauthn.register_challenge');
        
        return response()->json([
            'auth_challenge' => $auth_challenge ? 'SET' : 'NULL',
            'register_challenge' => $register_challenge ? 'SET' : 'NULL',
            'session_id' => session()->getId(),
            'driver' => config('session.driver'),
        ]);
    }

    private function getRpId(Request $request): string
    {
        $host = trim((string) $request->header('x-forwarded-host', $request->getHost()));
        if (str_contains($host, ',')) {
            $host = trim(explode(',', $host)[0]);
        }
        $host = preg_replace('/:\d+$/', '', $host);

        if ($host === '') {
            $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        }

        return strtolower($host);
    }

    public function registerOptions(Request $request, WebauthnService $webauthn)
    {
        $user = Auth::user();
        $options = $webauthn->registrationOptions($user);
        $options['publicKey']['excludeCredentials'] = [];

        return response()->json($options['publicKey']);
    }

    public function register(Request $request, WebauthnService $webauthn)
    {
        $user = Auth::user();
        
        $biometricType = $request->input('biometric_type', 'fingerprint');
        if (!in_array($biometricType, ['fingerprint', 'face'])) {
            $biometricType = 'fingerprint';
        }

        $typeLabel = $biometricType === 'face' ? 'Face Recognition' : 'Fingerprint';

        // Accept both nested credential format and flattened credential payload
        $credentialId = $request->input('credential_id') ?? $request->input('id') ?? $request->input('rawId');
        $credential = $request->input('credential') ?? [
            'id' => $request->input('id') ?? $credentialId,
            'type' => $request->input('type') ?? 'public-key',
            'response' => $request->input('response') ?? [],
        ];
        $defaultDevice = $biometricType === 'face' ? 'Face ID / Facial Recognition' : 'Fingerprint Sensor';
        $deviceName = $request->input('device_name') ?? $defaultDevice;

        if (!$credentialId || !is_string($credentialId)) {
            return response()->json(["success" => false, "message" => "Credential ID is required."], 422);
        }

        $normalizedCredentialId = rtrim(strtr($credentialId, '+/', '-_'), '=');

        // Check for duplicate credential
        $existingCred = \App\Models\WebauthnCredential::where(function ($query) use ($credentialId, $normalizedCredentialId) {
            $query->where('credential_id', $credentialId)
                  ->orWhere('credential_id', $normalizedCredentialId);
        })->first();

        if ($existingCred) {
            return response()->json([
                'success' => false,
                'message' => 'This biometric credential is already registered.'
            ], 409);
        }

        // Direct camera-based face recognition registration (independent from WebAuthn device verification)
        $isDirectFace = $biometricType === 'face' && (
            empty($credential['response']['attestationObject']) || 
            $request->has('face_descriptor') || 
            $request->has('face_data')
        );

        if ($isDirectFace) {
            return response()->json([
                'success' => false,
                'code' => 'INSECURE_FACE_FLOW_DISABLED',
                'message' => 'Camera-only face registration is disabled because it cannot securely verify identity. Use your device passkey, Face ID, Windows Hello, or Android screen lock instead.',
            ], 422);

            // Legacy camera-descriptor registration is intentionally unreachable.
            $faceData = $request->input('face_descriptor') 
                ?? $request->input('face_data') 
                ?? $request->input('public_key');

            if (empty($faceData) || !is_string($faceData) || trim($faceData) === '' || strlen(trim($faceData)) < 8) {
                return response()->json([
                    'success' => false,
                    'message' => 'No face detected. Please position your face in front of the camera.'
                ], 422);
            }

            $invalidTokens = ['no_face', 'unusable', 'fallback', 'blurry', 'multiple_faces', 'too_far', 'too_close', 'off_center', 'partial_face'];
            foreach ($invalidTokens as $token) {
                if (str_contains(strtolower($faceData), $token)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No face detected. Please position your face in front of the camera.'
                    ], 422);
                }
            }

            // Security threshold: require face descriptor to meet confidence/matching threshold (minimum 70%)
            if (preg_match('/^face_desc_(\d+)_/', $faceData, $scoreMatches)) {
                $confidence = (int) $scoreMatches[1];
                if ($confidence < 70) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Face recognition confidence does not meet the required security threshold. Please position your face clearly in good lighting and try again.'
                    ], 422);
                }
            }

            try {

                \App\Models\WebauthnCredential::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'credential_id' => $credentialId,
                    ],
                    [
                        'public_key' => (string) $faceData,
                        'sign_count' => 0,
                        'device_name' => $deviceName,
                        'biometric_type' => 'face',
                        'last_used_at' => now(),
                    ]
                );

                // Check if user already has recovery codes
                $hasRecoveryCodes = \App\Models\RecoveryCode::where('user_id', $user->id)->exists();
                $recoveryCodes = [];

                if (!$hasRecoveryCodes) {
                    for ($i = 0; $i < 5; $i++) {
                        $rawCode = strtoupper(Str::random(4) . '-' . Str::random(4));
                        $recoveryCodes[] = $rawCode;
                        
                        \App\Models\RecoveryCode::create([
                            'user_id' => $user->id,
                            'code' => \Illuminate\Support\Facades\Hash::make($rawCode),
                        ]);
                    }
                }

                return response()->json([
                    "success" => true, 
                    "message" => "Face Recognition Registered Successfully ✓",
                    "biometric_type" => "face",
                    "recovery_codes" => $recoveryCodes
                ]);
            } catch (\Throwable $e) {
                return response()->json(["success" => false, "message" => "Failed to store Face Recognition: " . $e->getMessage()], 422);
            }
        }

        if (!is_array($credential) || empty($credential['response'])) {
            return response()->json(["success" => false, "message" => "Invalid credential data."], 422);
        }

        try {
            $stored = $webauthn->storeCredential($user, $credential);
            $stored->forceFill([
                'device_name' => $deviceName,
                'biometric_type' => $biometricType,
            ])->save();

            // Check if user already has recovery codes
            $hasRecoveryCodes = \App\Models\RecoveryCode::where('user_id', $user->id)->exists();
            $recoveryCodes = [];

            if (!$hasRecoveryCodes) {
                for ($i = 0; $i < 5; $i++) {
                    $rawCode = strtoupper(Str::random(4) . '-' . Str::random(4));
                    $recoveryCodes[] = $rawCode;
                    
                    \App\Models\RecoveryCode::create([
                        'user_id' => $user->id,
                        'code' => \Illuminate\Support\Facades\Hash::make($rawCode),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $isConflict = str_contains($e->getMessage(), 'already registered') || str_contains($e->getMessage(), 'another account');
            return response()->json(["success" => false, "message" => "Failed to store {$typeLabel}: " . $e->getMessage()], $isConflict ? 409 : 422);
        }

        $successMsg = $biometricType === 'face' 
            ? 'Face Recognition Registered Successfully ✓' 
            : "{$typeLabel} registered successfully!";

        return response()->json([
            "success" => true, 
            "message" => $successMsg,
            "biometric_type" => $biometricType,
            "recovery_codes" => $recoveryCodes
        ]);
    }

    public function loginOptions(Request $request, WebauthnService $webauthn)
    {
        $raw = $request->input('student_number') ?? $request->input('identifier') ?? $request->input('email');
        $savedIdentifiers = $request->input('saved_identifiers');
        if (!is_array($savedIdentifiers)) {
            $savedIdentifiers = [];
        }
        
        // Targeted account lookup if identifier provided
        if ($raw && is_string($raw) && trim($raw) !== '') {
            $identifier = trim($raw);
            $user = $this->findUserByIdentifier($identifier);

            if (!$user) {
                return response()->json(["success" => false, "code" => "ACCOUNT_NOT_FOUND", "message" => "Account not found for \"{$identifier}\"."], 404);
            }

            if (!$user->isActive()) {
                return response()->json(["success" => false, "code" => "ACCOUNT_DEACTIVATED", "message" => "Your account has been deactivated. Please contact the school administrator."], 403);
            }

            // Check if user has any biometric credentials registered
            $hasAnyCredentials = $user->webauthnCredentials()->exists();

            if (!$hasAnyCredentials) {
                return response()->json([
                    "success" => false,
                    "code" => "NOT_REGISTERED",
                    "user_exists" => true,
                    "user_id" => $user->id,
                    "identifier" => $user->student_number ?? $user->email ?? $identifier,
                    "user_name" => $user->name,
                    "message" => "You haven't enabled biometric sign-in for this account yet."
                ], 404);
            }

            // Check if user has hardware WebAuthn credentials
            $hasWebauthn = $user->webauthnCredentials()
                ->where(function ($q) {
                    $q->whereNull('biometric_type')
                      ->orWhere('biometric_type', '!=', 'face')
                      ->orWhere('public_key', 'LIKE', '%BEGIN PUBLIC KEY%');
                })
                ->exists();

            $hasFaceCred = $user->webauthnCredentials()->where('biometric_type', 'face')
                ->where('public_key', 'LIKE', '%BEGIN PUBLIC KEY%')->exists();

            if ($request->input('biometric_method') === 'face' && !$hasFaceCred) {
                return response()->json([
                    'success' => false,
                    'code' => 'FACE_ENROLLMENT_REQUIRED',
                    'message' => 'Secure face sign-in is not enrolled for this account. Sign in with your password and register Face ID or Windows Hello in Settings.',
                ], 404);
            }

            if (!$hasWebauthn) {
                // Legacy camera face records are not authentication credentials.
                // Require password verification and enrollment of a real WebAuthn credential.
                return response()->json([
                    "success" => true,
                    "requires_device_enrollment" => true,
                    "biometric_type" => "face",
                    "user_id" => $user->id,
                    "identifier" => $user->student_number ?? $user->email ?? $identifier,
                    "user_name" => $user->name,
                    "message" => "An older camera-only face profile cannot be used for secure sign-in. Verify your password to enroll a device passkey instead.",
                ]);
            }
            
            session(["webauthn_login_user_id" => $user->id]);
            $options = $webauthn->authenticationOptions($user);
            
            // If the client sent other saved accounts on this device, also include their credentials
            // so any registered user on this device can authenticate smoothly
            if (!empty($savedIdentifiers)) {
                $existingIds = collect($options['publicKey']['allowCredentials'])->pluck('id')->all();
                foreach ($savedIdentifiers as $savedId) {
                    if (!is_string($savedId) || trim($savedId) === '' || strcasecmp(trim($savedId), $identifier) === 0) {
                        continue;
                    }
                    $otherUser = $this->findUserByIdentifier(trim($savedId));
                    if ($otherUser && $otherUser->isActive()) {
                        $otherCreds = $otherUser->webauthnCredentials()
                            ->where(function ($q) {
                                $q->whereNull('biometric_type')
                                  ->orWhere('biometric_type', '!=', 'face')
                                  ->orWhere('public_key', 'LIKE', '%BEGIN PUBLIC KEY%');
                            })
                            ->get();
                        foreach ($otherCreds as $oc) {
                            if (!in_array($oc->credential_id, $existingIds)) {
                                $options['publicKey']['allowCredentials'][] = [
                                    'type' => 'public-key',
                                    'id' => $oc->credential_id,
                                    'transports' => ['internal', 'hybrid'],
                                ];
                                $existingIds[] = $oc->credential_id;
                            }
                        }
                    }
                }
            }
            
            $allCreds = $user->webauthnCredentials()->get();
            $availableMethods = [];
            $hasFaceCred = false;
            $hasFingerprintCred = false;
            $hasHardwareWebauthn = false;

            foreach ($allCreds as $ac) {
                $bType = $ac->biometric_type ?: 'fingerprint';
                if ($bType === 'face') {
                    $hasFaceCred = $hasFaceCred || str_contains((string) $ac->public_key, 'BEGIN PUBLIC KEY');
                } else {
                    $hasFingerprintCred = true;
                }
                if ($bType !== 'face' || str_contains((string) $ac->public_key, 'BEGIN PUBLIC KEY')) {
                    $hasHardwareWebauthn = true;
                }
            }

            if ($hasFingerprintCred) $availableMethods[] = 'fingerprint';
            if ($hasFaceCred) $availableMethods[] = 'face';
            if ($hasHardwareWebauthn) $availableMethods[] = 'device_lock';
            if (empty($availableMethods)) {
                $availableMethods = ['fingerprint', 'device_lock'];
            }

            if ($request->input('biometric_method') === 'face') {
                $faceIds = $user->webauthnCredentials()->where('biometric_type', 'face')
                    ->where('public_key', 'LIKE', '%BEGIN PUBLIC KEY%')->pluck('credential_id')->all();
                $options['publicKey']['allowCredentials'] = array_values(array_filter(
                    $options['publicKey']['allowCredentials'],
                    fn ($credential) => in_array($credential['id'], $faceIds, true)
                ));
            }

            return response()->json(array_merge($options['publicKey'], [
                "success" => true,
                "user_id" => $user->id,
                "identifier" => $user->student_number ?? $user->email ?? $identifier,
                "user_name" => $user->name,
                "face_credential_id" => $hasFaceCred
                    ? $user->webauthnCredentials()->where('biometric_type', 'face')->where('public_key', 'LIKE', '%BEGIN PUBLIC KEY%')->latest()->value('credential_id')
                    : null,
                "available_methods" => $availableMethods,
            ]));
        }

        // Discoverable / Passkey mode: No identifier passed
        session()->forget("webauthn_login_user_id");
        $faceOnly = $request->input('biometric_method') === 'face';
        if ($faceOnly && empty($savedIdentifiers)) {
            return response()->json([
                'success' => false,
                'code' => 'IDENTIFIER_REQUIRED',
                'message' => 'Enter your ID or email before choosing Face ID or Windows Hello.',
            ], 422);
        }
        $options = $webauthn->authenticationOptions(null);

        $discoverableMethods = [];
        // If saved_identifiers was passed in discoverable mode, populate allowCredentials with known device accounts
        if (!empty($savedIdentifiers)) {
            $allowCredentials = [];
            $existingIds = [];
            foreach ($savedIdentifiers as $savedId) {
                if (!is_string($savedId) || trim($savedId) === '') continue;
                $savedUser = $this->findUserByIdentifier(trim($savedId));
                if ($savedUser && $savedUser->isActive()) {
                    $creds = $savedUser->webauthnCredentials()
                        ->where(function ($q) {
                            $q->whereNull('biometric_type')
                              ->orWhere('biometric_type', '!=', 'face')
                              ->orWhere('public_key', 'LIKE', '%BEGIN PUBLIC KEY%');
                        })
                        ->get();
                    foreach ($creds as $c) {
                        $t = $c->biometric_type ?: 'fingerprint';
                        if ($faceOnly && $t !== 'face') continue;
                        if ($t === 'face' && !in_array('face', $discoverableMethods)) $discoverableMethods[] = 'face';
                        if ($t !== 'face' && !in_array('fingerprint', $discoverableMethods)) $discoverableMethods[] = 'fingerprint';
                        if (!in_array('device_lock', $discoverableMethods)) $discoverableMethods[] = 'device_lock';

                        if (!in_array($c->credential_id, $existingIds)) {
                            $allowCredentials[] = [
                                'type' => 'public-key',
                                'id' => $c->credential_id,
                                'transports' => ['internal', 'hybrid'],
                            ];
                            $existingIds[] = $c->credential_id;
                        }
                    }
                }
            }
            if (!empty($allowCredentials)) {
                $options['publicKey']['allowCredentials'] = $allowCredentials;
            }
        }
        if ($faceOnly && empty($options['publicKey']['allowCredentials'])) {
            return response()->json([
                'success' => false,
                'code' => 'FACE_ENROLLMENT_REQUIRED',
                'message' => 'No secure face sign-in was found for a saved account. Enter your ID or email, or sign in with your password.',
            ], 404);
        }
        if (empty($discoverableMethods)) {
            $discoverableMethods = $faceOnly ? ['face'] : ['fingerprint', 'face', 'device_lock'];
        }

        return response()->json(array_merge($options['publicKey'], [
            "success" => true,
            "discoverable" => true,
            "available_methods" => $discoverableMethods,
        ]));
    }

    /**
     * Return available enrolled biometric methods for a given user or device
     */
    public function availableMethods(Request $request)
    {
        $raw = $request->input('student_number') ?? $request->input('identifier') ?? $request->input('email');
        $savedIdentifiers = $request->input('saved_identifiers', []);

        if ($raw && is_string($raw) && trim($raw) !== '') {
            $identifier = trim($raw);
            $user = $this->findUserByIdentifier($identifier);
            if (!$user) {
                return response()->json(["success" => false, "code" => "ACCOUNT_NOT_FOUND", "message" => "Account not found for \"{$identifier}\"."], 404);
            }
            if (!$user->isActive()) {
                return response()->json(["success" => false, "code" => "ACCOUNT_DEACTIVATED", "message" => "Account deactivated."], 403);
            }
            $creds = $user->webauthnCredentials()->get();
            if ($creds->isEmpty()) {
                return response()->json(["success" => false, "code" => "NOT_REGISTERED", "message" => "No biometrics registered."], 404);
            }
            $methods = [];
            $hasFp = false;
            $hasFace = false;
            $hasHw = false;
            foreach ($creds as $c) {
                $t = $c->biometric_type ?: 'fingerprint';
                if ($t === 'face') {
                    if (str_contains((string) $c->public_key, 'BEGIN PUBLIC KEY')) $hasFace = true;
                } else {
                    $hasFp = true;
                }
                if ($t !== 'face' || str_contains((string) $c->public_key, 'BEGIN PUBLIC KEY')) $hasHw = true;
            }
            if ($hasFp) $methods[] = 'fingerprint';
            if ($hasFace) $methods[] = 'face';
            if ($hasHw) $methods[] = 'device_lock';
            return response()->json([
                "success" => true,
                "identifier" => $user->student_number ?? $user->email ?? $identifier,
                "user_name" => $user->name,
                "available_methods" => $methods,
            ]);
        }

        $methods = [];
        if (is_array($savedIdentifiers) && !empty($savedIdentifiers)) {
            foreach ($savedIdentifiers as $savedId) {
                if (!is_string($savedId) || trim($savedId) === '') continue;
                $savedUser = $this->findUserByIdentifier(trim($savedId));
                if ($savedUser && $savedUser->isActive()) {
                    foreach ($savedUser->webauthnCredentials as $c) {
                        $t = $c->biometric_type ?: 'fingerprint';
                        if ($t === 'face' && !str_contains((string) $c->public_key, 'BEGIN PUBLIC KEY')) continue;
                        if ($t === 'face' && str_contains((string) $c->public_key, 'BEGIN PUBLIC KEY') && !in_array('face', $methods)) $methods[] = 'face';
                        if ($t !== 'face' && !in_array('fingerprint', $methods)) $methods[] = 'fingerprint';
                        if (!in_array('device_lock', $methods)) $methods[] = 'device_lock';
                    }
                }
            }
        }
        if (empty($methods)) {
            $methods = ['fingerprint', 'face', 'device_lock'];
        }

        return response()->json([
            "success" => true,
            "discoverable" => true,
            "available_methods" => $methods,
        ]);
    }

    public function setupOptions(Request $request, WebauthnService $webauthn)
    {
        $raw = $request->input('student_number') ?? $request->input('identifier') ?? $request->input('email');
        $password = $request->input('password');

        if (!$raw || !is_string($raw)) {
            return response()->json(["success" => false, "message" => "Please enter your Student ID or Email."], 422);
        }

        $identifier = trim($raw);
        $user = $this->findUserByIdentifier($identifier);

        if (!$user) {
            return response()->json(["success" => false, "code" => "ACCOUNT_NOT_FOUND", "message" => "Account not found for \"{$identifier}\"."], 404);
        }

        if (!$user->isActive()) {
            return response()->json(["success" => false, "code" => "ACCOUNT_DEACTIVATED", "message" => "Your account has been deactivated."], 403);
        }

        $trimmedPassword = is_string($password) ? trim($password) : '';
        $passwordMatches = Hash::check((string) $password, $user->password)
            || ($password !== $trimmedPassword && Hash::check($trimmedPassword, $user->password));

        if (!$password || !$passwordMatches) {
            return response()->json(["success" => false, "code" => "INVALID_PASSWORD", "message" => "Invalid password. Please enter the correct password to verify your account."], 401);
        }

        session(['webauthn.setup_user_id' => $user->id]);
        $options = $webauthn->registrationOptions($user);
        // Clear excludeCredentials so users can re-enroll or update their biometric passkey on this device
        $options['publicKey']['excludeCredentials'] = [];

        return response()->json(array_merge($options['publicKey'], [
            "success" => true,
            "user_id" => $user->id,
            "identifier" => $user->student_number ?? $user->email ?? $identifier
        ]));
    }

    public function setupRegister(Request $request, WebauthnService $webauthn)
    {
        $userId = session('webauthn.setup_user_id');

        if (!$userId && $request->has('identifier') && $request->has('password')) {
            $user = $this->findUserByIdentifier(trim($request->input('identifier')));
            if ($user) {
                $p = (string) $request->input('password');
                $pTrim = trim($p);
                if (Hash::check($p, $user->password) || ($p !== $pTrim && Hash::check($pTrim, $user->password))) {
                    $userId = $user->id;
                }
            }
        }

        if (!$userId) {
            return response()->json(["success" => false, "message" => "Session expired or user unverified. Please try again."], 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(["success" => false, "message" => "User not found."], 404);
        }

        $credentialId = $request->input('credential_id') ?? $request->input('id') ?? $request->input('rawId');
        $credential = $request->input('credential') ?? [
            'id' => $request->input('id') ?? $credentialId,
            'type' => $request->input('type') ?? 'public-key',
            'response' => $request->input('response') ?? [],
        ];
        $biometricType = $request->input('biometric_type', 'fingerprint');
        if (!in_array($biometricType, ['fingerprint', 'face'])) {
            $biometricType = 'fingerprint';
        }
        $typeLabel = $biometricType === 'face' ? 'Face Recognition' : 'Fingerprint';

        $defaultDevice = $biometricType === 'face' 
            ? 'Face ID / Facial Recognition' 
            : (str_contains(request()->userAgent() ?? '', 'Mobile') ? 'Mobile Fingerprint Sensor' : 'Desktop Fingerprint Sensor');
        $deviceName = $request->input('device_name') ?? $defaultDevice;

        if (!$credentialId || !is_string($credentialId)) {
            return response()->json(["success" => false, "message" => "Credential ID is required."], 422);
        }

        $normalizedCredentialId = rtrim(strtr($credentialId, '+/', '-_'), '=');

        try {
            $stored = $webauthn->storeCredential($user, $credential);
            $stored->forceFill([
                'device_name' => $deviceName,
                'biometric_type' => $biometricType,
            ])->save();

            session()->forget('webauthn.setup_user_id');

            Auth::login($user, true);
            $request->session()->regenerate();
            $request->session()->put('user_role', $user->role);
            $request->session()->put('login_timestamp', now()->toString());

            if ($user->isStudent()) {
                app(\App\Services\DeviceBindingService::class)->bind($user, $request);
                $request->session()->save();
            }

            $redirectUrl = route('home');
            if ($user->isAdmin()) {
                $redirectUrl = route('admin.dashboard');
            } elseif ($user->isTeacher() || $user->isDepartmentHead()) {
                $redirectUrl = route('teacher.dashboard');
            } elseif ($user->isParent()) {
                $redirectUrl = route('parent.dashboard');
            }

            return response()->json([
                "success" => true,
                "message" => "{$typeLabel} sign-in successfully enabled for " . ($user->student_number ?? $user->email),
                "biometric_type" => $biometricType,
                "redirect" => $redirectUrl
            ]);
        } catch (\Throwable $e) {
            $isConflict = str_contains($e->getMessage(), 'already registered') || str_contains($e->getMessage(), 'another account');
            return response()->json([
                "success" => false,
                "message" => "Failed to enable biometrics: " . $e->getMessage()
            ], $isConflict ? 409 : 422);
        }
    }


    /**
     * Helper method to find user by various identifier formats
     */
    private function findUserByIdentifier(string $identifier): ?User
    {
        $raw = trim($identifier);
        if ($raw === '') {
            return null;
        }
        return User::findByIdentifier($raw);
    }

    public function login(Request $request, WebauthnService $webauthn)
    {
        $isFaceLogin = $request->has('face_descriptor') || $request->has('face_data')
            || ($request->input('biometric_method') === 'face' && !$request->has('assertion'))
            || (str_starts_with((string)$request->input('credential_id'), 'face_') && !$request->has('assertion'));

        if ($isFaceLogin) {
            return response()->json([
                'success' => false,
                'code' => 'INSECURE_FACE_FLOW_DISABLED',
                'message' => 'Camera-only face login is disabled because it cannot securely verify identity. Use your device passkey, Face ID, Windows Hello, or password instead.',
            ], 422);

            // Legacy client-generated face descriptors are intentionally unreachable.
            $request->validate(["credential_id" => "required|string"]);
            $credentialId = (string) $request->input('credential_id');
            $faceData = (string) ($request->input('face_descriptor') ?? $request->input('face_data') ?? '');

            if (empty($faceData) || strlen(trim($faceData)) < 8) {
                return response()->json([
                    'success' => false,
                    'message' => 'No face detected. Please position your face in front of the camera.'
                ], 422);
            }

            $invalidTokens = ['no_face', 'unusable', 'fallback', 'blurry', 'multiple_faces', 'too_far', 'too_close', 'off_center', 'partial_face'];
            foreach ($invalidTokens as $token) {
                if (str_contains(strtolower($faceData), $token)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No face detected. Please position your face in front of the camera.'
                    ], 422);
                }
            }

            if (preg_match('/^face_desc_(\d+)_/', $faceData, $scoreMatches)) {
                $confidence = (int) $scoreMatches[1];
                if ($confidence < 70) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Face recognition confidence does not meet the required security threshold. Please position your face clearly in good lighting and try again.'
                    ], 422);
                }
            }

            $rawIdentifier = $request->input('student_number') ?? $request->input('identifier') ?? $request->input('email');
            $user = null;
            if ($rawIdentifier && is_string($rawIdentifier) && trim($rawIdentifier) !== '') {
                $user = $this->findUserByIdentifier(trim($rawIdentifier));
            }

            if (!$user) {
                $sessionUserId = session("webauthn_login_user_id");
                if ($sessionUserId) {
                    $user = User::find($sessionUserId);
                }
            }

            if (!$user) {
                $cred = \App\Models\WebauthnCredential::where('biometric_type', 'face')
                    ->where('credential_id', $credentialId)
                    ->with('user')
                    ->first();
                if ($cred && $cred->user) {
                    $user = $cred->user;
                }
            }

            if (!$user) {
                $savedIdentifiers = $request->input('saved_identifiers', []);
                if (is_array($savedIdentifiers)) {
                    foreach ($savedIdentifiers as $savedId) {
                        if (!is_string($savedId) || trim($savedId) === '') continue;
                        $candidate = $this->findUserByIdentifier(trim($savedId));
                        if ($candidate && $candidate->isActive() && $candidate->webauthnCredentials()->where('biometric_type', 'face')->exists()) {
                            $user = $candidate;
                            break;
                        }
                    }
                }
            }

            if (!$user) {
                // If single user with face on system
                $faceCreds = \App\Models\WebauthnCredential::where('biometric_type', 'face')->with('user')->get();
                if ($faceCreds->count() === 1 && $faceCreds->first()->user && $faceCreds->first()->user->isActive()) {
                    $user = $faceCreds->first()->user;
                }
            }

            if (!$user) {
                return response()->json([
                    "success" => false,
                    "code" => "ACCOUNT_NOT_FOUND",
                    "message" => "No account found matching this face profile. Please enter your Student ID or Email."
                ], 404);
            }

            if (!$user->isActive()) {
                return response()->json([
                    "success" => false,
                    "code" => "ACCOUNT_DEACTIVATED",
                    "message" => "Your account has been deactivated. Please contact the school administrator."
                ], 403);
            }

            $hasFaceCred = $user->webauthnCredentials()->where('biometric_type', 'face')->exists();
            if (!$hasFaceCred) {
                return response()->json([
                    "success" => false,
                    "code" => "NOT_REGISTERED",
                    "message" => "Face Recognition is not registered for this account. Please use your password or register your face first."
                ], 404);
            }

            $user->webauthnCredentials()
                ->where('biometric_type', 'face')
                ->latest()
                ->first()?->update(['last_used_at' => now()]);

            Auth::login($user, true);
            $request->session()->regenerate();
            session()->forget(["webauthn_login_user_id"]);
            $request->session()->put('user_role', $user->role);
            $request->session()->put('login_timestamp', now()->toString());

            if ($user->isStudent()) {
                app(\App\Services\DeviceBindingService::class)->bind($user, $request);
                $request->session()->save();
            }

            $redirectUrl = route('home');
            if ($user->isAdmin()) {
                $redirectUrl = route('admin.dashboard');
            } elseif ($user->isTeacher() || $user->isDepartmentHead()) {
                $redirectUrl = route('teacher.dashboard');
            } elseif ($user->isParent()) {
                $redirectUrl = route('parent.dashboard');
            }

            return response()->json([
                "success" => true,
                "message" => "Face recognized successfully! Redirecting...",
                "user" => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'identifier' => $user->student_number ?? $user->email,
                ],
                "role" => $user->role,
                "redirect" => $redirectUrl,
                "dashboard_url" => $redirectUrl,
            ]);
        }

        $request->validate(["credential_id" => "required|string", "assertion" => "required|array"]);
        $credentialId = $request->input('credential_id') ?? $request->input('assertion.id');
        $normalizedCredentialId = rtrim(strtr($credentialId, '+/', '-_'), '=');

        // 1. Check if an account was targeted via session or request input
        $isSwitchUser = $request->boolean('switch_user') || $request->boolean('allow_detected_user');
        $rawIdentifier = $request->input('student_number') ?? $request->input('identifier') ?? $request->input('email');
        $hasExplicitIdentifier = is_string($rawIdentifier) && trim($rawIdentifier) !== '';

        // If switching user, or if discoverable mode (no explicit identifier passed), ignore stale session user ID!
        if ($isSwitchUser || !$hasExplicitIdentifier) {
            session()->forget("webauthn_login_user_id");
            $sessionUserId = null;
        } else {
            $sessionUserId = session("webauthn_login_user_id");
        }

        $expectedUser = null;
        if ($sessionUserId) {
            $expectedUser = User::find($sessionUserId);
        } elseif ($hasExplicitIdentifier) {
            $expectedUser = $this->findUserByIdentifier(trim($rawIdentifier));
        }

        // 2. Locate matching credential (prefer expected user's credential if targeted)
        $user = null;
        $dbCredential = null;
        $userHandle = $request->input('assertion.response.userHandle');

        if ($expectedUser) {
            $dbCredential = $expectedUser->webauthnCredentials()
                ->where(function ($query) use ($credentialId, $normalizedCredentialId) {
                    $query->where('credential_id', $credentialId)
                          ->orWhere('credential_id', $normalizedCredentialId);
                })
                ->first();

            if ($dbCredential) {
                $user = $expectedUser;
            }
        }

        // If userHandle is provided (from WebAuthn assertion response), use it to identify user
        if (!$dbCredential && $userHandle) {
            $candidateCredentials = \App\Models\WebauthnCredential::with('user')
                ->where(function ($query) use ($credentialId, $normalizedCredentialId) {
                    $query->where('credential_id', $credentialId)
                          ->orWhere('credential_id', $normalizedCredentialId);
                })
                ->get();

            foreach ($candidateCredentials as $cand) {
                if ($cand->user) {
                    $expectedHandle = rtrim(strtr(base64_encode(hash('sha256', (string) $cand->user->id, true)), '+/', '-_'), '=');
                    $rawHandle = rtrim(strtr($userHandle, '+/', '-_'), '=');
                    if ($expectedHandle === $rawHandle || (string)$cand->user->id === (string)$userHandle) {
                        $dbCredential = $cand;
                        $user = $cand->user;
                        break;
                    }
                }
            }
        }

        if (!$dbCredential) {
            $dbCredential = \App\Models\WebauthnCredential::with('user')
                ->where(function ($query) use ($credentialId, $normalizedCredentialId) {
                    $query->where('credential_id', $credentialId)
                          ->orWhere('credential_id', $normalizedCredentialId);
                })
                ->first();

            if ($dbCredential && $dbCredential->user) {
                $user = $dbCredential->user;
            }
        }

        // 3. Handle account mismatch / account switching
        $allowSwitch = $isSwitchUser;

        if ($expectedUser && $user && $expectedUser->id !== $user->id) {
            if ($allowSwitch) {
                \Illuminate\Support\Facades\Log::info('WebAuthn user switched account to detected biometric owner', [
                    'from_user_id' => $expectedUser->id,
                    'to_user_id' => $user->id,
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning('WebAuthn account mismatch attempt', [
                    'expected_user_id' => $expectedUser->id,
                    'credential_user_id' => $user->id,
                    'credential_id' => $credentialId,
                ]);
                $userIdentifier = $user->student_number ?? $user->email;
                $expectedIdentifier = $expectedUser->student_number ?? $expectedUser->email;
                return response()->json([
                    "success" => false,
                    "code" => "CREDENTIAL_MISMATCH",
                    "can_switch_user" => true,
                    "message" => "This biometric credential belongs to {$user->name} ({$userIdentifier}).",
                    "detected_user" => [
                        "id" => $user->id,
                        "name" => $user->name,
                        "identifier" => $userIdentifier,
                        "role" => $user->role,
                    ],
                    "expected_user" => [
                        "id" => $expectedUser->id,
                        "name" => $expectedUser->name,
                        "identifier" => $expectedIdentifier,
                    ]
                ], 403);
            }
        }

        \Illuminate\Support\Facades\Log::info('WebAuthn login attempt', [
            'user_id' => $user?->id,
            'credential_id' => $credentialId,
            'session_id' => session()->getId(),
            'assertion_received' => !empty($request->assertion),
        ]);

        if (!$user || !$dbCredential) {
            \Illuminate\Support\Facades\Log::error('WebAuthn login - unrecognized credential', [
                'credential_id' => $credentialId,
                'session_user_id' => $sessionUserId,
            ]);
            return response()->json([
                "success" => false,
                "code" => "CREDENTIAL_NOT_FOUND",
                "message" => "Unrecognized biometric credential. Please register your biometric sign-in first or log in with password."
            ], 401);
        }

        if (!$user->isActive()) {
            return response()->json([
                "success" => false,
                "code" => "ACCOUNT_DEACTIVATED",
                "message" => "Your account has been deactivated. Please contact the school administrator."
            ], 403);
        }

        if ($request->input('biometric_method') === 'face'
            && ($dbCredential->biometric_type !== 'face' || !str_contains((string) $dbCredential->public_key, 'BEGIN PUBLIC KEY'))) {
            return response()->json([
                'success' => false,
                'code' => 'FACE_CREDENTIAL_REQUIRED',
                'message' => 'This account needs a secure Face ID or Windows Hello credential. Sign in with your password and enroll face sign-in in Settings.',
            ], 422);
        }

        try {
            $webauthn->verifyAssertion($user, $request->assertion, $dbCredential);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('WebAuthn verification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(["success" => false, "message" => "Biometric authentication failed: " . $e->getMessage()], 401);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        session()->forget(["webauthn_login_user_id"]);
        $request->session()->put('user_role', $user->role);
        $request->session()->put('login_timestamp', now()->toString());

        // Bind device on WebAuthn login too (same as password login)
        if ($user->isStudent()) {
            app(\App\Services\DeviceBindingService::class)->bind($user, $request);
            $request->session()->save();
        }

        $redirectUrl = route('home');
        if ($user->isAdmin()) {
            $redirectUrl = route('admin.dashboard');
        } elseif ($user->isTeacher() || $user->isDepartmentHead()) {
            $redirectUrl = route('teacher.dashboard');
        } elseif ($user->isParent()) {
            $redirectUrl = route('parent.dashboard');
        }

        return response()->json([
            "success" => true,
            "user" => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'identifier' => $user->student_number ?? $user->email,
            ],
            "role" => $user->role,
            "redirect" => $redirectUrl,
            "dashboard_url" => $redirectUrl,
        ]);
    }

    public function removeDevice(Request $request)
    {
        $request->validate(["credential_id" => "required|string"]);
        DB::table("webauthn_credentials")->where("user_id", Auth::id())->where("credential_id", $request->credential_id)->delete();
        return response()->json(["success" => true]);
    }

    public function devices()
    {
        $devices = DB::table("webauthn_credentials")
            ->where("user_id", Auth::id())
            ->select(["credential_id", "device_name", "biometric_type", "created_at", "last_used_at"])
            ->orderBy("created_at", "desc")
            ->get()
            ->map(function ($d) {
                return [
                    'credential_id' => $d->credential_id,
                    'name' => $d->device_name ?? 'My Device',
                    'device_name' => $d->device_name ?? 'My Device',
                    'biometric_type' => in_array($d->biometric_type, ['face', 'fingerprint']) ? $d->biometric_type : 'fingerprint',
                    'created_at' => $d->created_at,
                    'last_used_at' => $d->last_used_at,
                ];
            });

        return response()->json($devices);
    }
}
