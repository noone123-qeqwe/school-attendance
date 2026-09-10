<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WebauthnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $exists = DB::table("webauthn_credentials")
            ->where("user_id", $user->id)
            ->where(function ($query) use ($credentialId, $normalizedCredentialId) {
                $query->where('credential_id', $credentialId)
                      ->orWhere('credential_id', $normalizedCredentialId);
            })
            ->exists();

        if ($exists) {
            return response()->json([
                "success" => false, 
                "message" => "This {$typeLabel} credential is already registered on your account."
            ], 409);
        }

        // Direct camera-based face recognition registration (independent from WebAuthn device verification)
        $isDirectFace = $biometricType === 'face' && (
            empty($credential['response']['attestationObject']) || 
            $request->has('face_descriptor') || 
            $request->has('face_data')
        );

        if ($isDirectFace) {
            try {
                $faceData = $request->input('face_descriptor') 
                    ?? $request->input('face_data') 
                    ?? $request->input('public_key') 
                    ?? hash('sha256', $credentialId . $user->id . config('app.key'));

                \App\Models\WebauthnCredential::create([
                    'user_id' => $user->id,
                    'credential_id' => $credentialId,
                    'public_key' => (string) $faceData,
                    'sign_count' => 0,
                    'device_name' => $deviceName,
                    'biometric_type' => 'face',
                    'last_used_at' => now(),
                ]);

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
            return response()->json(["success" => false, "message" => "Failed to store {$typeLabel}: " . $e->getMessage()], 422);
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
        if (!$raw || !is_string($raw)) {
            return response()->json(["success" => false, "code" => "IDENTIFIER_REQUIRED", "message" => "Please enter your Student ID or Email."], 422);
        }

        $identifier = trim($raw);
        $user = $this->findUserByIdentifier($identifier);

        if (!$user) {
            return response()->json(["success" => false, "code" => "ACCOUNT_NOT_FOUND", "message" => "Account not found for \"{$identifier}\"."], 404);
        }

        if (!$user->isActive()) {
            return response()->json(["success" => false, "code" => "ACCOUNT_DEACTIVATED", "message" => "Your account has been deactivated. Please contact the school administrator."], 403);
        }
        
        $credentials = $user->webauthnCredentials()->exists() || DB::table("webauthn_credentials")->where("user_id", $user->id)->exists();
        if (!$credentials) {
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
        
        session(["webauthn_login_user_id" => $user->id]);
        $options = $webauthn->authenticationOptions($user);
        
        return response()->json(array_merge($options['publicKey'], ["success" => true]));
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

        if (!$password || !\Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            return response()->json(["success" => false, "code" => "INVALID_PASSWORD", "message" => "Invalid password. Please enter the correct password to verify your account."], 401);
        }

        session(['webauthn.setup_user_id' => $user->id]);
        $options = $webauthn->registrationOptions($user);

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
            if ($user && \Illuminate\Support\Facades\Hash::check($request->input('password'), $user->password)) {
                $userId = $user->id;
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

        try {
            $stored = $webauthn->storeCredential($user, $credential);
            $stored->forceFill([
                'device_name' => $deviceName,
                'biometric_type' => $biometricType,
            ])->save();

            session()->forget('webauthn.setup_user_id');

            Auth::login($user, true);
            $request->session()->regenerate();

            if ($user->isStudent()) {
                app(\App\Services\DeviceBindingService::class)->bind($user, $request);
            }

            $redirectUrl = route('home');
            if ($user->isAdmin()) {
                $redirectUrl = route('admin.dashboard');
            } elseif ($user->isTeacher()) {
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
            return response()->json(["success" => false, "message" => "Failed to enable biometrics: " . $e->getMessage()], 422);
        }
    }


    /**
     * Helper method to find user by various identifier formats
     */
    private function findUserByIdentifier(string $identifier): ?User
    {
        $user = User::where("student_number", $identifier)
            ->orWhere("email", $identifier)
            ->orWhere("employee_id", $identifier)
            ->orWhereRaw("LOWER(email) = ?", [strtolower($identifier)])
            ->orWhereRaw("LOWER(student_number) = ?", [strtolower($identifier)])
            ->orWhereRaw("LOWER(employee_id) = ?", [strtolower($identifier)])
            ->first();

        // Also check if hyphens/spaces were omitted or added
        if (!$user) {
            $clean = preg_replace('/[^a-zA-Z0-9]/', '', $identifier);
            if ($clean !== '') {
                $user = User::whereRaw("REPLACE(REPLACE(student_number, '-', ''), ' ', '') = ?", [$clean])
                    ->orWhereRaw("REPLACE(REPLACE(employee_id, '-', ''), ' ', '') = ?", [$clean])
                    ->first();
            }
        }

        return $user;
    }

    public function login(Request $request, WebauthnService $webauthn)
    {
        $request->validate(["credential_id" => "required|string", "assertion" => "required|array"]);
        $userId = session("webauthn_login_user_id");
        
        // Resilient fallback: If session was lost or expired, look up user from request identifier
        if (!$userId) {
            $raw = $request->input('student_number') ?? $request->input('identifier') ?? $request->input('email');
            if ($raw && is_string($raw)) {
                $fallbackUser = $this->findUserByIdentifier(trim($raw));
                if ($fallbackUser) {
                    $userId = $fallbackUser->id;
                }
            }
        }
        
        \Illuminate\Support\Facades\Log::info('WebAuthn login attempt', [
            'user_id' => $userId,
            'session_id' => session()->getId(),
            'assertion_received' => !empty($request->assertion),
        ]);
        
        if (!$userId) {
            \Illuminate\Support\Facades\Log::error('WebAuthn login - no user_id in session or request', [
                'session_keys' => array_keys(session()->all()),
            ]);
            return response()->json(["success" => false, "message" => "Session expired. Please try again."], 401);
        }
        
        $user = User::find($userId);
        if (!$user) return response()->json(["success" => false, "message" => "User not found."], 401);

        if (!$user->isActive()) {
            return response()->json(["success" => false, "message" => "Your account has been deactivated. Please contact the school administrator."], 403);
        }
        
        try {
            $webauthn->verifyAssertion($user, $request->assertion);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('WebAuthn verification failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(["success" => false, "message" => "WebAuthn verification failed: " . $e->getMessage()], 401);
        }
        
        Auth::login($user, true);
        $request->session()->regenerate();
        session()->forget(["webauthn_login_user_id"]);

        // Bind device on WebAuthn login too (same as password login)
        if ($user->isStudent()) {
            app(\App\Services\DeviceBindingService::class)->bind($user, $request);
        }
        
        $redirectUrl = route('home');
        if ($user->isAdmin()) {
            $redirectUrl = route('admin.dashboard');
        } elseif ($user->isTeacher()) {
            $redirectUrl = route('teacher.dashboard');
        } elseif ($user->isParent()) {
            $redirectUrl = route('parent.dashboard');
        }

        return response()->json(["success" => true, "redirect" => $redirectUrl]);
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

