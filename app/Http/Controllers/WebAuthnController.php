<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountLockoutService;
use App\Services\WebauthnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WebAuthnController extends Controller
{
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

        $typeLabel = $biometricType === 'face' ? 'Face sign-in' : 'Fingerprint';

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

        if ($biometricType === 'face' && ($request->has('face_descriptor') || $request->has('face_data'))) {
            return response()->json([
                'success' => false,
                'code' => 'SECURE_FACE_CREDENTIAL_REQUIRED',
                'message' => 'Camera-only face profiles cannot be used for sign-in. Register Face ID or Windows Hello through your device passkey prompt.',
            ], 422);
        }

        if ($biometricType === 'face'
            && (!is_array($credential)
                || !is_array($credential['response'] ?? null)
                || empty($credential['response']['attestationObject'])
                || empty($credential['response']['clientDataJSON']))) {
            return response()->json([
                'success' => false,
                'code' => 'SECURE_FACE_CREDENTIAL_REQUIRED',
                'message' => 'Face sign-in must be registered through the protected passkey prompt on this device.',
            ], 422);
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
            \Illuminate\Support\Facades\Log::warning('WebAuthn credential registration failed', [
                'user_id' => $user->id,
                'biometric_type' => $biometricType,
                'error_class' => get_class($e),
            ]);
            return response()->json(["success" => false, "message" => $isConflict ? 'This biometric credential is already registered.' : 'Biometric registration could not be completed. Please try again.'], $isConflict ? 409 : 422);
        }

        $successMsg = $biometricType === 'face'
            ? 'Face sign-in device passkey registered successfully.'
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
        $request->validate([
            'student_number' => ['nullable', 'string', 'max:255'],
            'identifier' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'saved_identifiers' => ['sometimes', 'array', 'max:10'],
            'saved_identifiers.*' => ['string', 'max:255'],
            'biometric_method' => ['nullable', 'in:fingerprint,face,device_lock'],
        ]);

        $identifier = trim((string) ($request->input('student_number') ?? $request->input('identifier') ?? $request->input('email')));
        $user = $identifier !== '' ? $this->findUserByIdentifier($identifier) : null;

        if ($identifier !== '' && (!$user || !$user->isActive())) {
            return $this->biometricUnavailableResponse();
        }

        // Credential labels describe enrollment UI, not the verification method
        // the authenticator can perform. An empty allow list enables discovery.
        $options = $webauthn->authenticationOptions($user)['publicKey'];
        if ($user && !$options['allowCredentials']) {
            return $this->biometricUnavailableResponse();
        }

        return response()->json($options + [
            'success' => true,
            'discoverable' => $user === null,
            'available_methods' => ['device_lock'],
        ]);
    }

    public function availableMethods()
    {
        return response()->json([
            'success' => true,
            'discoverable' => true,
            'available_methods' => ['device_lock'],
        ]);
    }


    public function setupOptions(Request $request, WebauthnService $webauthn, AccountLockoutService $lockout)
    {
        // Clear any earlier pending enrollment even when this request is malformed.
        session()->forget(['webauthn.setup_user_id', 'webauthn.register_challenge']);
        $request->validate([
            'student_number' => ['nullable', 'string', 'max:255'],
            'identifier' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
        ]);

        $raw = $request->input('student_number') ?? $request->input('identifier') ?? $request->input('email');
        if (!is_string($raw) || trim($raw) === '') {
            return response()->json(['success' => false, 'message' => 'Enter your account details and password to continue.'], 422);
        }

        $identifier = trim($raw);
        $ip = $request->ip();
        if ($lockout->isLocked($identifier, $ip)) {
            $remaining = max(1, $lockout->getRemainingSeconds($identifier, $ip));
            return response()->json([
                'success' => false,
                'message' => 'Too many unsuccessful attempts. Please wait before trying again.',
            ], 429)->header('Retry-After', (string) $remaining);
        }

        $user = $this->findUserByIdentifier($identifier);

        if (!$user || !$user->isActive() || !Hash::check((string) $request->input('password'), (string) $user->password)) {
            $attempt = $lockout->recordFailedAttempt($identifier, $ip);
            $response = response()->json([
                'success' => false,
                'code' => 'ACCOUNT_VERIFICATION_FAILED',
                'message' => $attempt['locked']
                    ? 'Too many unsuccessful attempts. Please wait before trying again.'
                    : 'Account verification failed. Check your details or sign in with your password.',
            ], $attempt['locked'] ? 429 : 401);
            if ($attempt['locked']) {
                $response->header('Retry-After', (string) $attempt['lockout_seconds']);
            }
            return $response;
        }

        $lockout->clear($identifier, null);
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
        $request->validate([
            'credential_id' => ['nullable', 'string', 'max:2048'],
            'id' => ['nullable', 'string', 'max:2048'],
            'rawId' => ['nullable', 'string', 'max:2048'],
            'credential' => ['nullable', 'array'],
            'biometric_type' => ['nullable', 'in:fingerprint,face'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);
        $userId = session('webauthn.setup_user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Verification expired. Start device setup again.'], 401);
        }

        $user = User::find($userId);
        if (!$user || !$user->isActive()) {
            session()->forget(['webauthn.setup_user_id', 'webauthn.register_challenge']);
            return response()->json(['success' => false, 'message' => 'Verification expired. Sign in again and restart device setup.'], 401);
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
        $typeLabel = $biometricType === 'face' ? 'Face sign-in' : 'Fingerprint';

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
                app(\App\Services\DeviceBindingService::class)->bind($user, $request, true);
                $request->session()->save();
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
                "message" => "{$typeLabel} sign-in successfully enabled for your account.",
                "biometric_type" => $biometricType,
                "redirect" => $redirectUrl
            ]);
        } catch (\Throwable $e) {
            $isConflict = str_contains($e->getMessage(), 'already registered') || str_contains($e->getMessage(), 'another account');
            \Illuminate\Support\Facades\Log::warning('WebAuthn setup registration failed', [
                'user_id' => $user->id,
                'biometric_type' => $biometricType,
                'error_class' => get_class($e),
            ]);
            session()->forget(['webauthn.setup_user_id', 'webauthn.register_challenge']);
            return response()->json([
                "success" => false,
                "message" => $isConflict ? 'This biometric credential is already registered.' : 'Biometric setup could not be completed. Please restart setup and try again.'
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

    private function biometricUnavailableResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'BIOMETRIC_UNAVAILABLE',
            'message' => 'Biometric sign-in could not be started. Check your details or use your password.',
        ], 404);
    }

    public function login(Request $request, WebauthnService $webauthn)
    {
        $isCameraFaceLogin = $request->has('face_descriptor')
            || $request->has('face_data')
            || ($request->input('biometric_method') === 'face' && !$request->has('assertion'))
            || (str_starts_with((string) $request->input('credential_id'), 'face_') && !$request->has('assertion'));

        if ($isCameraFaceLogin) {
            return response()->json([
                'success' => false,
                'code' => 'CAMERA_FACE_LOGIN_DISABLED',
                'message' => 'Camera-only face sign-in is not supported. Sign in with your password, then register a device passkey in Settings.',
            ], 422);
        }

        $request->validate(["credential_id" => "required|string", "assertion" => "required|array"]);
        $credentialId = $request->input('credential_id');
        $normalizedCredentialId = rtrim(strtr($credentialId, '+/', '-_'), '=');
        $paddedId = $normalizedCredentialId . str_repeat('=', (4 - strlen($normalizedCredentialId) % 4) % 4);
        $credentialIds = array_unique([
            $credentialId, $normalizedCredentialId, $paddedId,
            strtr($normalizedCredentialId, '-_', '+/'), strtr($paddedId, '-_', '+/'),
        ]);

        // 1. Check if an account was targeted via session or request input
        $isSwitchUser = $request->boolean('switch_user') || $request->boolean('allow_detected_user');
        $rawIdentifier = $request->input('student_number') ?? $request->input('identifier') ?? $request->input('email');
        $hasExplicitIdentifier = is_string($rawIdentifier) && trim($rawIdentifier) !== '';

        // Never trust a cached account selection from an earlier options request;
        // bind this assertion to the identifier on this exact request instead.
        session()->forget('webauthn_login_user_id');
        $sessionUserId = null;
        $expectedUser = $hasExplicitIdentifier && !$isSwitchUser
            ? $this->findUserByIdentifier(trim($rawIdentifier))
            : null;

        // An explicitly entered but unknown account must never fall through to
        // a different credential owner. Discoverable sign-in remains available
        // only when the user deliberately chooses account switching.
        if ($hasExplicitIdentifier && !$expectedUser && !$isSwitchUser) {
            return response()->json([
                'success' => false,
                'code' => 'BIOMETRIC_UNAVAILABLE',
                'message' => 'Biometric sign-in could not be completed. Check your details or use your password.',
            ], 401);
        }

        // 2. Locate matching credential (prefer expected user's credential if targeted)
        $user = null;
        $dbCredential = null;
        $userHandle = $request->input('assertion.response.userHandle');

        if ($expectedUser) {
            $dbCredential = $expectedUser->webauthnCredentials()
                ->whereIn('credential_id', $credentialIds)
                ->first();

            if ($dbCredential) {
                $user = $expectedUser;
            }
        }

        // If userHandle is provided (from WebAuthn assertion response), use it to identify user
        if (!$dbCredential && $userHandle) {
            $candidateCredentials = \App\Models\WebauthnCredential::with('user')
                ->whereIn('credential_id', $credentialIds)
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
                ->whereIn('credential_id', $credentialIds)
                ->first();

            if ($dbCredential && $dbCredential->user) {
                $user = $dbCredential->user;
            }
        }

        // 3. Handle account mismatch
        if ($expectedUser && $user && $expectedUser->id !== $user->id) {
            \Illuminate\Support\Facades\Log::warning('WebAuthn account mismatch attempt', [
                'expected_user_id' => $expectedUser->id,
                'credential_user_id' => $user->id,
                'credential_fingerprint' => hash('sha256', $credentialId),
            ]);
            return response()->json([
                'success' => false,
                'code' => 'BIOMETRIC_UNAVAILABLE',
                'message' => 'Biometric sign-in could not be completed. Check your sign-in details or use your password.',
            ], 401);
        }

        \Illuminate\Support\Facades\Log::info('WebAuthn login attempt', [
            'user_id' => $user?->id,
            'credential_fingerprint' => hash('sha256', $credentialId),
            'assertion_received' => !empty($request->assertion),
        ]);

        if (!$user) {
            \Illuminate\Support\Facades\Log::error('WebAuthn login - unrecognized credential', [
                'credential_fingerprint' => hash('sha256', $credentialId),
                'session_user_id' => $sessionUserId,
            ]);
            return response()->json([
                "success" => false,
                "code" => "BIOMETRIC_UNAVAILABLE",
                "message" => "Biometric sign-in could not be completed. Try again or use your password."
            ], 401);
        }

        if (!$user->isActive()) {
            return response()->json([
                "success" => false,
                "code" => "BIOMETRIC_UNAVAILABLE",
                "message" => "Biometric sign-in could not be completed. Try again or use your password."
            ], 401);
        }

        try {
            $webauthn->verifyAssertion($user, $request->assertion, $dbCredential);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('WebAuthn verification failed', [
                'user_id' => $user->id,
                'error_class' => get_class($e),
            ]);
            return response()->json(["success" => false, "message" => "Biometric authentication could not be completed. Try again or use your password."], 401);
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
        } elseif ($user->isTeacher()) {
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

    /** Reject legacy native biometric requests that cannot prove device verification. */
    public function nativeBiometricLogin(Request $request)
    {
        // A browser-supplied device ID does not prove that Android's biometric
        // prompt succeeded. Only a signed WebAuthn assertion can authenticate.
        return response()->json([
            'success' => false,
            'code' => 'PASSKEY_REQUIRED',
            'message' => 'Please sign in with a passkey or your password. After signing in, add a passkey for this site in Settings.',
        ], 403);
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
            ->select(["credential_id", "device_name", "biometric_type", "public_key", "created_at", "last_used_at"])
            ->orderBy("created_at", "desc")
            ->get()
            ->map(function ($d) {
                return [
                    'credential_id' => $d->credential_id,
                    'name' => $d->device_name ?? 'My Device',
                    'device_name' => $d->device_name ?? 'My Device',
                    'biometric_type' => in_array($d->biometric_type, ['face', 'fingerprint']) ? $d->biometric_type : 'fingerprint',
                    'is_secure_face_credential' => $d->biometric_type === 'face'
                        ? str_contains((string) $d->public_key, 'BEGIN PUBLIC KEY')
                        : null,
                    'created_at' => $d->created_at,
                    'last_used_at' => $d->last_used_at,
                ];
            });

        return response()->json($devices);
    }
}
