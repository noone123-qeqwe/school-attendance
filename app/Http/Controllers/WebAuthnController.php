<?php

namespace App\Http\Controllers;

use App\Models\RecoveryCode;
use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\AccountLockoutService;
use App\Services\BiometricVerificationAuditService;
use App\Services\DeviceBindingService;
use App\Services\FaceAttendanceChallenge;
use App\Services\FaceAttendanceConsent;
use App\Services\FaceVerificationAttemptLimiter;
use App\Services\FeatureFlagService;
use App\Services\LiveProfileFaceMatcher;
use App\Services\ProfileFacePhotoService;
use App\Services\WebauthnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebAuthnController extends Controller
{
    private const LOGIN_CHALLENGE_LIFETIME_SECONDS = WebauthnService::LOGIN_CHALLENGE_TTL_SECONDS;

    public function registerOptions(Request $request, WebauthnService $webauthn): JsonResponse
    {
        $user = Auth::user();
        $options = $webauthn->registrationOptions($user);

        return response()->json($options['publicKey'])->header('Cache-Control', 'private, no-store');
    }

    public function register(Request $request, WebauthnService $webauthn): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'biometric_type' => ['nullable', 'in:fingerprint,face,device_lock'],
            'device_name' => ['nullable', 'string', 'max:80'],
            'consent_accepted' => ['nullable', 'boolean'],
            'credential_id' => ['nullable', 'string', 'max:2048'],
            'id' => ['nullable', 'string', 'max:2048'],
            'rawId' => ['nullable', 'string', 'max:2048'],
            'credential' => ['nullable', 'array'],
            'transports' => ['nullable', 'array', 'max:6'],
            'transports.*' => ['string', 'max:32'],
        ]);

        if ($request->has('consent_accepted') && !$request->boolean('consent_accepted')) {
            return response()->json([
                'success' => false,
                'code' => 'CONSENT_REQUIRED',
                'message' => 'Explicit consent is required before registering a device credential.',
            ], 422);
        }

        if ($request->has('face_descriptor') || $request->has('face_data')) {
            return response()->json([
                'success' => false,
                'code' => 'SECURE_FACE_CREDENTIAL_REQUIRED',
                'message' => 'Camera-only face profiles cannot be used for passkey registration. Use the Face Registration page for camera enrollment.',
            ], 422);
        }

        $biometricType = $validated['biometric_type'] ?? 'device_lock';

        $credentialId = $request->input('credential_id') ?? $request->input('id') ?? $request->input('rawId');
        $credential = $request->input('credential') ?? [
            'id' => $request->input('id') ?? $credentialId,
            'rawId' => $request->input('rawId') ?? $credentialId,
            'type' => $request->input('type') ?? 'public-key',
            'authenticatorAttachment' => $request->input('authenticatorAttachment'),
            'response' => $request->input('response') ?? [],
        ];

        if (!is_string($credentialId) || trim($credentialId) === '') {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_CREDENTIAL',
                'message' => 'Credential ID is required.',
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
                'message' => 'Face sign-in must be registered through your device passkey prompt on this account.',
            ], 422);
        }

        if (!is_array($credential) || !is_array($credential['response'] ?? null) || empty($credential['response'])) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_CREDENTIAL',
                'message' => 'Invalid credential attestation payload.',
            ], 422);
        }

        $transports = $credential['transports'] ?? $request->input('transports');
        if (is_array($transports)) {
            $credential['transports'] = $transports;
        }

        $existingVariants = $webauthn->credentialIdVariants($credentialId);
        $existingCred = WebauthnCredential::active()->whereIn('credential_id', $existingVariants)->first();
        if ($existingCred) {
            return response()->json([
                'success' => false,
                'code' => 'CREDENTIAL_ALREADY_REGISTERED',
                'message' => 'This device credential is already registered.',
            ], 409);
        }

        try {
            $stored = $webauthn->storeCredential($user, $credential, [
                'device_name' => $validated['device_name'] ?? null,
                'biometric_type' => $biometricType,
                'user_agent' => $request->userAgent(),
                'consent_given' => true,
            ]);

            $hasRecoveryCodes = RecoveryCode::where('user_id', $user->id)->exists();
            $recoveryCodes = [];

            if (!$hasRecoveryCodes) {
                for ($i = 0; $i < 5; $i++) {
                    $rawCode = strtoupper(Str::random(4) . '-' . Str::random(4));
                    $recoveryCodes[] = $rawCode;

                    RecoveryCode::create([
                        'user_id' => $user->id,
                        'code' => Hash::make($rawCode),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            return $this->registrationFailureResponse($user, $biometricType, $e, 'WebAuthn credential registration failed');
        }

        Log::info('WebAuthn credential registered', [
            'user_id' => $user->id,
            'credential_pk' => $stored->id,
            'attachment' => $stored->authenticator_attachment,
            'transports' => $stored->transports,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device passkey registered successfully.',
            'biometric_type' => $stored->biometric_type,
            'device' => $this->serializeDevice($stored),
            'recovery_codes' => $recoveryCodes,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function loginOptions(Request $request, WebauthnService $webauthn): JsonResponse
    {
        $request->session()->forget('webauthn.login');
        $request->validate([
            'student_number' => ['nullable', 'string', 'max:255'],
            'identifier' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'saved_identifiers' => ['sometimes', 'array', 'max:10'],
            'saved_identifiers.*' => ['string', 'max:255'],
            'biometric_method' => ['nullable', 'in:fingerprint,face,device_lock'],
        ]);

        $identifier = $this->resolveRequestIdentifier($request);
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

        $request->session()->put('webauthn.login', [
            'challenge' => $options['challenge'],
            'user_id' => $user?->id,
            'allow_credential_ids' => array_values(array_map(
                static fn (array $cred): string => (string) ($cred['id'] ?? ''),
                $options['allowCredentials']
            )),
            'issued_at' => now()->timestamp,
            'expires_at' => now()->timestamp + self::LOGIN_CHALLENGE_LIFETIME_SECONDS,
        ]);

        return response()->json($options + [
            'success' => true,
            'discoverable' => $user === null,
            'has_account_credentials' => $user !== null,
            'available_methods' => $this->availableMethodNames(),
            'face_login_enabled' => $this->faceLoginReady(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function availableMethods(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'discoverable' => true,
            'available_methods' => $this->availableMethodNames(),
            'face_login_enabled' => $this->faceLoginReady(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function setupOptions(Request $request, WebauthnService $webauthn, AccountLockoutService $lockout): JsonResponse
    {
        session()->forget(['webauthn.setup_user_id', 'webauthn.register_challenge']);
        $request->validate([
            'student_number' => ['nullable', 'string', 'max:255'],
            'identifier' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
            'consent_accepted' => ['nullable', 'boolean'],
        ]);

        if ($request->has('consent_accepted') && !$request->boolean('consent_accepted')) {
            return response()->json([
                'success' => false,
                'code' => 'CONSENT_REQUIRED',
                'message' => 'Explicit consent is required before registering a device credential.',
            ], 422);
        }

        $identifier = $this->resolveRequestIdentifier($request);
        if ($identifier === '') {
            return response()->json([
                'success' => false,
                'code' => 'IDENTIFIER_REQUIRED',
                'message' => 'Enter your account details and password to continue.',
            ], 422);
        }

        $ip = $request->ip() ?: 'unknown';
        if ($lockout->isLocked($identifier, $ip)) {
            return $this->lockedAccountResponse($lockout->getRemainingSeconds($identifier, $ip));
        }

        $user = $this->findUserByIdentifier($identifier);
        if (!$user || !$user->isActive() || !Hash::check((string) $request->input('password'), (string) $user->password)) {
            $attempt = $lockout->recordFailedAttempt($identifier, $ip);
            if ($attempt['locked']) {
                return $this->lockedAccountResponse($attempt['lockout_seconds']);
            }

            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_VERIFICATION_FAILED',
                'message' => 'Account verification failed. Check your details or sign in with your password.',
            ], 401);
        }

        $lockout->clear($identifier, null);
        if ($user->ensurePasswordChangeRequired()) {
            return response()->json([
                'success' => false,
                'code' => 'password_reset_required',
                'message' => 'Use Forgot Password to set a new password before registering a passkey.',
            ], 403);
        }

        session(['webauthn.setup_user_id' => $user->id]);
        $options = $webauthn->registrationOptions($user);

        // Preserve excludeCredentials so the same authenticator cannot register duplicate active credentials.
        return response()->json(array_merge($options['publicKey'], [
            'success' => true,
            'user_id' => $user->id,
            'identifier' => $user->student_number ?? $user->email ?? $identifier,
        ]))->header('Cache-Control', 'private, no-store');
    }

    public function setupRegister(Request $request, WebauthnService $webauthn): JsonResponse
    {
        $validated = $request->validate([
            'credential_id' => ['nullable', 'string', 'max:2048'],
            'id' => ['nullable', 'string', 'max:2048'],
            'rawId' => ['nullable', 'string', 'max:2048'],
            'credential' => ['nullable', 'array'],
            'biometric_type' => ['nullable', 'in:fingerprint,face,device_lock'],
            'device_name' => ['nullable', 'string', 'max:80'],
            'consent_accepted' => ['nullable', 'boolean'],
            'qr_token' => ['nullable', 'string', 'max:512'],
            'transports' => ['nullable', 'array', 'max:6'],
            'transports.*' => ['string', 'max:32'],
        ]);

        if ($request->has('consent_accepted') && !$request->boolean('consent_accepted')) {
            return response()->json([
                'success' => false,
                'code' => 'CONSENT_REQUIRED',
                'message' => 'Explicit consent is required before registering a device credential.',
            ], 422);
        }

        $userId = session('webauthn.setup_user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'code' => 'PASSKEY_CHALLENGE_EXPIRED',
                'message' => 'Verification expired. Start device setup again.',
            ], 401);
        }

        $user = User::find($userId);
        if (!$user || !$user->isActive()) {
            session()->forget(['webauthn.setup_user_id', 'webauthn.register_challenge']);

            return response()->json([
                'success' => false,
                'code' => 'PASSKEY_CHALLENGE_EXPIRED',
                'message' => 'Verification expired. Sign in again and restart device setup.',
            ], 401);
        }

        $credentialId = $request->input('credential_id') ?? $request->input('id') ?? $request->input('rawId');
        $credential = $request->input('credential') ?? [
            'id' => $request->input('id') ?? $credentialId,
            'rawId' => $request->input('rawId') ?? $credentialId,
            'type' => $request->input('type') ?? 'public-key',
            'authenticatorAttachment' => $request->input('authenticatorAttachment'),
            'response' => $request->input('response') ?? [],
        ];
        $biometricType = $validated['biometric_type'] ?? 'device_lock';

        if (!is_string($credentialId) || trim($credentialId) === '') {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_CREDENTIAL',
                'message' => 'Credential ID is required.',
            ], 422);
        }

        $transports = $credential['transports'] ?? $request->input('transports');
        if (is_array($transports)) {
            $credential['transports'] = $transports;
        }

        try {
            $stored = $webauthn->storeCredential($user, $credential, [
                'device_name' => $validated['device_name'] ?? null,
                'biometric_type' => $biometricType,
                'user_agent' => $request->userAgent(),
                'consent_given' => true,
            ]);

            session()->forget('webauthn.setup_user_id');

            $redirectUrl = $this->completeAuthenticatedLogin(
                $request,
                $user,
                $request->input('qr_token'),
                true
            );

            return response()->json([
                'success' => true,
                'message' => 'Device passkey enabled for your account.',
                'biometric_type' => $stored->biometric_type,
                'device' => $this->serializeDevice($stored),
                'redirect' => $redirectUrl,
            ])->header('Cache-Control', 'private, no-store');
        } catch (\Throwable $e) {
            session()->forget(['webauthn.setup_user_id', 'webauthn.register_challenge']);

            return $this->registrationFailureResponse($user, $biometricType, $e, 'WebAuthn setup registration failed');
        }
    }

    public function login(Request $request, WebauthnService $webauthn, AccountLockoutService $lockout): JsonResponse
    {
        $isCameraFaceLogin = $request->has('face_descriptor')
            || $request->has('face_data')
            || ($request->input('biometric_method') === 'face' && !$request->has('assertion'))
            || (str_starts_with((string) $request->input('credential_id'), 'face_') && !$request->has('assertion'));

        if ($isCameraFaceLogin) {
            return response()->json([
                'success' => false,
                'code' => 'CAMERA_FACE_LOGIN_DISABLED',
                'message' => 'Camera-only face sign-in must use the dedicated Face Verification flow.',
            ], 422);
        }

        $request->validate([
            'credential_id' => ['required', 'string', 'max:2048'],
            'assertion' => ['required', 'array'],
            'assertion.id' => ['required', 'string', 'max:2048'],
            'assertion.response' => ['required', 'array'],
            'assertion.response.clientDataJSON' => ['required', 'string'],
            'assertion.response.authenticatorData' => ['required', 'string'],
            'assertion.response.signature' => ['required', 'string'],
            'assertion.response.userHandle' => ['nullable', 'string', 'max:128'],
            'qr_token' => ['nullable', 'string', 'max:512'],
        ]);

        $ip = $request->ip() ?: 'unknown';
        $rawIdentifier = $this->resolveRequestIdentifier($request);
        $lockoutKey = $rawIdentifier !== '' ? $rawIdentifier : 'webauthn:' . $ip;

        if ($lockout->isLocked($lockoutKey, $ip)) {
            $request->session()->forget('webauthn.login');

            return $this->lockedAccountResponse($lockout->getRemainingSeconds($lockoutKey, $ip));
        }

        // Single-use atomic pull prevents replay of the login ceremony.
        $ceremony = $request->session()->pull('webauthn.login');
        if (!is_array($ceremony) || ($ceremony['expires_at'] ?? 0) <= now()->timestamp || empty($ceremony['challenge'])) {
            return response()->json([
                'success' => false,
                'code' => 'PASSKEY_CHALLENGE_EXPIRED',
                'message' => 'This verification request expired. Start sign-in again.',
            ], 401);
        }

        $credentialId = (string) $request->input('credential_id');
        $normalizedCredentialId = $webauthn->normalizeCredentialId($credentialId);
        $credentialVariants = $webauthn->credentialIdVariants($credentialId);

        $hasExplicitIdentifier = $rawIdentifier !== '';
        $submittedUser = $hasExplicitIdentifier ? $this->findUserByIdentifier($rawIdentifier) : null;
        $expectedUserId = $ceremony['user_id'] ?? $submittedUser?->id;

        if ($hasExplicitIdentifier && (!$submittedUser || $submittedUser->id !== $expectedUserId)) {
            return $this->recordFailedWebauthnLogin($lockout, $lockoutKey, $ip, 'BIOMETRIC_UNAVAILABLE', 'Biometric sign-in could not be completed. Check your details or use your password.');
        }

        $allowedFromCeremony = is_array($ceremony['allow_credential_ids'] ?? null)
            ? array_values(array_filter($ceremony['allow_credential_ids'], 'is_string'))
            : [];

        if ($allowedFromCeremony !== [] && !in_array($normalizedCredentialId, $allowedFromCeremony, true)) {
            Log::warning('WebAuthn assertion credential not in issued allowCredentials list', [
                'expected_user_id' => $expectedUserId,
                'credential_fingerprint' => hash('sha256', $credentialId),
            ]);

            return $this->recordFailedWebauthnLogin($lockout, $lockoutKey, $ip, 'BIOMETRIC_UNAVAILABLE', 'Biometric sign-in could not be completed. Check your sign-in details or use your password.');
        }

        $user = null;
        $dbCredential = null;
        $userHandle = $request->input('assertion.response.userHandle');

        if ($expectedUserId !== null) {
            // Strict account-bound lookup: NEVER fall through to another user's credential.
            $dbCredential = WebauthnCredential::with('user')
                ->where('user_id', $expectedUserId)
                ->whereIn('credential_id', $credentialVariants)
                ->first();

            if ($dbCredential && $dbCredential->revoked_at !== null) {
                return $this->recordFailedWebauthnLogin($lockout, $lockoutKey, $ip, 'CREDENTIAL_REVOKED', 'This device credential was revoked. Register this device again or sign in with your password.');
            }

            if (!$dbCredential || !$dbCredential->isActive()) {
                return $this->recordFailedWebauthnLogin($lockout, $lockoutKey, $ip, 'BIOMETRIC_UNAVAILABLE', 'Biometric sign-in could not be completed. Check your sign-in details or use your password.');
            }

            $user = $dbCredential->user;
        } else {
            // Discoverable flow: resolve strictly among active credentials.
            $candidates = WebauthnCredential::with('user')
                ->active()
                ->whereIn('credential_id', $credentialVariants)
                ->get();

            if (is_string($userHandle) && trim($userHandle) !== '') {
                $normalizedHandle = $webauthn->normalizeCredentialId($userHandle);
                foreach ($candidates as $candidate) {
                    if ($candidate->user
                        && (hash_equals($webauthn->expectedUserHandle($candidate->user), $normalizedHandle)
                            || (string) $candidate->user->id === $userHandle)) {
                        $dbCredential = $candidate;
                        $user = $candidate->user;
                        break;
                    }
                }
            } elseif ($candidates->count() === 1) {
                $dbCredential = $candidates->first();
                $user = $dbCredential?->user;
            }
        }

        if (!$user || !$user->isActive() || !$dbCredential) {
            Log::warning('WebAuthn login failed - unrecognized or inactive account', [
                'credential_fingerprint' => hash('sha256', $credentialId),
            ]);

            return $this->recordFailedWebauthnLogin($lockout, $lockoutKey, $ip, 'BIOMETRIC_UNAVAILABLE', 'Biometric sign-in could not be completed. Try again or use your password.');
        }

        try {
            $webauthn->verifyAssertion($user, (array) $request->input('assertion'), (string) $ceremony['challenge'], $dbCredential);
        } catch (\Throwable $e) {
            Log::warning('WebAuthn assertion verification failed', [
                'user_id' => $user->id,
                'error_class' => get_class($e),
                'reason' => $e->getMessage(),
            ]);

            $code = str_contains(strtolower($e->getMessage()), 'revoked') ? 'CREDENTIAL_REVOKED' : 'VERIFICATION_FAILED';

            return $this->recordFailedWebauthnLogin($lockout, $lockoutKey, $ip, $code, 'Biometric authentication could not be completed. Try again or use your password.');
        }

        $lockout->clear($lockoutKey, null);

        $redirectUrl = $this->completeAuthenticatedLogin(
            $request,
            $user,
            $request->input('qr_token')
        );

        return response()->json($this->loginSuccessPayload($user, $redirectUrl))
            ->header('Cache-Control', 'private, no-store');
    }

    public function faceLoginOptions(Request $request, AccountLockoutService $lockout)
    {
        $request->validate([
            'identifier' => ['nullable', 'string', 'max:255'],
            'student_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
        ]);

        $identifier = $this->resolveRequestIdentifier($request);
        if ($identifier === '') {
            return response()->json([
                'success' => false,
                'code' => 'IDENTIFIER_REQUIRED',
                'message' => 'Enter your student number or email first to verify your face.',
            ], 422);
        }

        $ip = $request->ip();
        if ($lockout->isLocked($identifier, $ip)) {
            return $this->lockedAccountResponse($lockout->getRemainingSeconds($identifier, $ip));
        }

        if (!$this->faceLoginReady()) {
            return response()->json([
                'success' => false,
                'code' => 'FACE_VERIFICATION_UNAVAILABLE',
                'message' => 'Live face verification is unavailable on this server right now. Sign in with your device passkey or password.',
            ], 503);
        }

        $user = $this->findUserByIdentifier($identifier);
        if (!$user || !$this->canUseFaceLogin($user)) {
            return response()->json([
                'success' => false,
                'code' => 'FACE_LOGIN_UNAVAILABLE',
                'message' => 'No registered face profile was found for those sign-in details. Sign in with your password and register your face in Settings.',
            ], 404);
        }

        $limiter = app(FaceVerificationAttemptLimiter::class);
        if ($limiter->isLocked($user, 0)) {
            return $this->faceCooldownResponse($limiter, $user);
        }

        $challenge = app(FaceAttendanceChallenge::class)->issueForLogin($request, $user);

        return response()->json([
            'success' => true,
            'face_challenge' => $challenge,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function faceLogin(Request $request, AccountLockoutService $lockout)
    {
        $request->validate([
            'identifier' => ['nullable', 'string', 'max:255'],
            'student_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'face_challenge' => ['required', 'string', 'size:64'],
            'live_frames' => ['required', 'array', 'size:' . FaceAttendanceChallenge::FRAME_COUNT],
            'live_frames.*' => ['required', 'string', 'max:' . FaceAttendanceChallenge::MAX_FRAME_LENGTH],
            'qr_token' => ['nullable', 'string', 'max:512'],
        ]);

        $identifier = $this->resolveRequestIdentifier($request);
        if ($identifier === '') {
            return response()->json([
                'success' => false,
                'code' => 'IDENTIFIER_REQUIRED',
                'message' => 'Enter your student number or email first to verify your face.',
            ], 422);
        }

        $ip = $request->ip();
        if ($lockout->isLocked($identifier, $ip)) {
            return $this->lockedAccountResponse($lockout->getRemainingSeconds($identifier, $ip));
        }

        if (!$this->faceLoginReady()) {
            return response()->json([
                'success' => false,
                'code' => 'FACE_VERIFICATION_UNAVAILABLE',
                'message' => 'Live face verification is unavailable on this server right now. Sign in with your device passkey or password.',
            ], 503);
        }

        $user = $this->findUserByIdentifier($identifier);
        if (!$user || !$this->canUseFaceLogin($user)) {
            $attempt = $lockout->recordFailedAttempt($identifier, $ip);
            if ($attempt['locked']) {
                return $this->lockedAccountResponse($attempt['lockout_seconds']);
            }

            return response()->json([
                'success' => false,
                'code' => 'FACE_VERIFICATION_FAILED',
                'message' => 'Face sign-in is unavailable for those account details. Sign in with your password instead.',
            ], 401);
        }

        $limiter = app(FaceVerificationAttemptLimiter::class);
        if ($limiter->isLocked($user, 0)) {
            return $this->faceCooldownResponse($limiter, $user);
        }

        $challengeService = app(FaceAttendanceChallenge::class);
        $audit = app(BiometricVerificationAuditService::class);
        $lockedResponse = Cache::lock($challengeService->loginLockKey($user), 30)->get(function () use (
            $request,
            $user,
            $identifier,
            $ip,
            $lockout,
            $limiter,
            $challengeService,
            $audit
        ) {
            if ($limiter->isLocked($user, 0)) {
                return $this->faceCooldownResponse($limiter, $user);
            }

            $poses = $challengeService->consumeForLogin($request, $user);
            $frames = $request->input('live_frames', []);
            if (!$poses || count($frames) !== FaceAttendanceChallenge::FRAME_COUNT) {
                $audit->record($user, $request, 0, false, 'login_invalid_challenge_or_capture');
                $limiter->recordFailure($user, 0);
                $attempt = $lockout->recordFailedAttempt($identifier, $ip);
                if ($attempt['locked']) {
                    return $this->lockedAccountResponse($attempt['lockout_seconds']);
                }

                return response()->json([
                    'success' => false,
                    'code' => 'FACE_CHALLENGE_INVALID',
                    'message' => 'The camera check expired or was incomplete. Start face verification again.',
                ], 422);
            }

            $result = app(LiveProfileFaceMatcher::class)->verifyAttendance($user, $frames, $poses);
            if (($result['liveness_passed'] ?? false) !== true || ($result['match'] ?? false) !== true) {
                $code = $result['code'] ?? 'MATCHER_UNAVAILABLE';
                $audit->record($user, $request, 0, false, 'login_' . strtolower($code));
                if ($code !== 'MATCHER_UNAVAILABLE') {
                    $limiter->recordFailure($user, 0);
                    $attempt = $lockout->recordFailedAttempt($identifier, $ip);
                    if ($attempt['locked']) {
                        return $this->lockedAccountResponse($attempt['lockout_seconds']);
                    }
                }

                return response()->json([
                    'success' => false,
                    'code' => $code,
                    'message' => $result['message'] ?? 'Face verification did not match. Please try again in good lighting.',
                ], $code === 'MATCHER_UNAVAILABLE' ? 503 : 422);
            }

            $audit->record($user, $request, 0, true, '');
            $limiter->clear($user, 0);
            $lockout->clear($identifier, null);

            $redirectUrl = $this->completeAuthenticatedLogin(
                $request,
                $user,
                $request->input('qr_token')
            );

            return response()->json($this->loginSuccessPayload($user, $redirectUrl));
        });

        if ($lockedResponse === false) {
            return response()->json([
                'success' => false,
                'code' => 'FACE_VERIFICATION_BUSY',
                'message' => 'A camera verification check is already in progress. Wait a moment and try again.',
            ], 409);
        }

        return $lockedResponse;
    }

    /** Reject legacy native biometric requests that cannot prove cryptographic device verification. */
    public function nativeBiometricLogin(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'PASSKEY_REQUIRED',
            'message' => 'Please sign in with a WebAuthn passkey or your password. After signing in, register this device in Settings.',
        ], 403);
    }

    public function removeDevice(Request $request, WebauthnService $webauthn): JsonResponse
    {
        $validated = $request->validate([
            'credential_id' => ['required', 'string', 'max:2048'],
            'reason' => ['nullable', 'string', 'max:120'],
        ]);

        $variants = $webauthn->credentialIdVariants($validated['credential_id']);
        $credential = WebauthnCredential::where('user_id', Auth::id())
            ->whereNull('revoked_at')
            ->whereIn('credential_id', $variants)
            ->first();

        if (!$credential) {
            return response()->json([
                'success' => false,
                'code' => 'CREDENTIAL_NOT_FOUND',
                'message' => 'Registered device credential not found on your account.',
            ], 404);
        }

        $credential->revoke($validated['reason'] ?? 'user_revoked');

        Log::info('WebAuthn credential revoked by owner', [
            'user_id' => Auth::id(),
            'credential_pk' => $credential->id,
            'reason' => $credential->revoked_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device credential revoked. You can register this device again at any time.',
        ]);
    }

    public function renameDevice(Request $request, WebauthnService $webauthn): JsonResponse
    {
        $validated = $request->validate([
            'credential_id' => ['required', 'string', 'max:2048'],
            'device_name' => ['required', 'string', 'min:1', 'max:80'],
        ]);

        $variants = $webauthn->credentialIdVariants($validated['credential_id']);
        $credential = WebauthnCredential::active()
            ->where('user_id', Auth::id())
            ->whereIn('credential_id', $variants)
            ->first();

        if (!$credential) {
            return response()->json([
                'success' => false,
                'code' => 'CREDENTIAL_NOT_FOUND',
                'message' => 'Registered device credential not found on your account.',
            ], 404);
        }

        $credential->forceFill([
            'device_name' => $webauthn->sanitizeDeviceName($validated['device_name'], $request->userAgent()),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Device name updated.',
            'device' => $this->serializeDevice($credential),
        ]);
    }

    public function devices(): JsonResponse
    {
        $devices = WebauthnCredential::active()
            ->where('user_id', Auth::id())
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (WebauthnCredential $credential): array => $this->serializeDevice($credential))
            ->values();

        return response()->json($devices)->header('Cache-Control', 'private, no-store');
    }

    /**
     * Serialize only safe credential metadata — never expose public_key PEMs or internal secrets.
     *
     * @return array<string, mixed>
     */
    private function serializeDevice(WebauthnCredential $credential): array
    {
        $transports = is_array($credential->transports) ? array_values($credential->transports) : [];

        return [
            'credential_id' => $credential->normalizedCredentialId(),
            'name' => $credential->device_name ?: 'Device Passkey',
            'device_name' => $credential->device_name ?: 'Device Passkey',
            'biometric_type' => in_array($credential->biometric_type, ['face', 'fingerprint', 'device_lock'], true)
                ? $credential->biometric_type
                : 'device_lock',
            'authenticator_attachment' => $credential->authenticator_attachment ?? (in_array('internal', $transports, true) ? 'platform' : 'cross-platform'),
            'transports' => $transports,
            'backed_up' => (bool) $credential->backed_up,
            'user_agent_summary' => $credential->user_agent_summary,
            'consent_given_at' => $credential->consent_given_at?->toIso8601String(),
            'is_secure_face_credential' => $credential->biometric_type === 'face'
                ? $credential->isActive()
                : null,
            'created_at' => $credential->created_at?->toIso8601String(),
            'last_used_at' => $credential->last_used_at?->toIso8601String(),
        ];
    }

    private function registrationFailureResponse(User $user, string $biometricType, \Throwable $e, string $logContext): JsonResponse
    {
        $msgLower = strtolower($e->getMessage());
        $isConflict = str_contains($msgLower, 'already registered') || str_contains($msgLower, 'another account');
        $isExpired = str_contains($msgLower, 'expired');

        Log::warning($logContext, [
            'user_id' => $user->id,
            'biometric_type' => $biometricType,
            'error_class' => get_class($e),
            'reason' => $e->getMessage(),
        ]);

        if ($isConflict) {
            return response()->json([
                'success' => false,
                'code' => 'CREDENTIAL_ALREADY_REGISTERED',
                'message' => 'This device credential is already registered.',
            ], 409);
        }

        if ($isExpired) {
            return response()->json([
                'success' => false,
                'code' => 'PASSKEY_CHALLENGE_EXPIRED',
                'message' => 'The registration challenge expired. Please start registration again.',
            ], 401);
        }

        return response()->json([
            'success' => false,
            'code' => 'REGISTRATION_FAILED',
            'message' => 'Device registration could not be verified. Please try again.',
        ], 422);
    }

    private function recordFailedWebauthnLogin(
        AccountLockoutService $lockout,
        string $lockoutKey,
        string $ip,
        string $code,
        string $message
    ): JsonResponse {
        $attempt = $lockout->recordFailedAttempt($lockoutKey, $ip);
        if ($attempt['locked']) {
            return $this->lockedAccountResponse($attempt['lockout_seconds']);
        }

        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
        ], 401)->header('Cache-Control', 'private, no-store');
    }

    private function findUserByIdentifier(string $identifier): ?User
    {
        $raw = trim($identifier);
        if ($raw === '') {
            return null;
        }

        return User::findByIdentifier($raw);
    }

    private function biometricUnavailableResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'BIOMETRIC_UNAVAILABLE',
            'message' => 'No passkey was found for those sign-in details. Check your account details or use your password.',
            'available_methods' => $this->availableMethodNames(),
            'face_login_enabled' => $this->faceLoginReady(),
        ], 404)->header('Cache-Control', 'private, no-store');
    }

    private function lockedAccountResponse(int $retryAfterSeconds): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'ACCOUNT_LOCKED',
            'message' => 'Too many unsuccessful attempts. Please wait before trying again.',
        ], 429)->header('Retry-After', (string) max(1, $retryAfterSeconds));
    }

    private function faceCooldownResponse(FaceVerificationAttemptLimiter $limiter, User $user): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'FACE_ATTEMPTS_EXHAUSTED',
            'message' => 'Too many unsuccessful face checks. Wait before retrying or sign in with your password.',
        ], 429)->header('Retry-After', (string) max(1, $limiter->secondsUntilRetry($user, 0)));
    }

    private function resolveRequestIdentifier(Request $request): string
    {
        return trim((string) ($request->input('student_number') ?? $request->input('identifier') ?? $request->input('email') ?? ''));
    }

    private function availableMethodNames(): array
    {
        return $this->faceLoginReady() ? ['fingerprint', 'face'] : ['fingerprint'];
    }

    private function faceLoginReady(): bool
    {
        return app(FeatureFlagService::class)->enabled('feature.face_verification')
            && app(LiveProfileFaceMatcher::class)->attendanceReady();
    }

    private function canUseFaceLogin(User $user): bool
    {
        return $user->isActive()
            && app(ProfileFacePhotoService::class)->hasCameraEnrollment($user)
            && (!$user->isStudent() || app(FaceAttendanceConsent::class)->approved($user));
    }

    private function redirectTargetForUser(User $user, ?string $qrToken): string
    {
        if ($user->isAdmin()) {
            return route('admin.2fa.form');
        }
        if ($user->isTeacher()) {
            return route('teacher.dashboard');
        }
        if ($user->isParent()) {
            return route('parent.dashboard');
        }

        $cleanQrToken = is_string($qrToken) ? trim($qrToken) : '';
        if ($user->isStudent() && $cleanQrToken !== '') {
            return route('qr.scan', ['token' => $cleanQrToken]);
        }

        return route('home');
    }

    private function completeAuthenticatedLogin(Request $request, User $user, ?string $qrToken = null, bool $strictStudentBind = false): string
    {
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget('admin_2fa_verified');
        $request->session()->put('user_role', $user->role);
        $request->session()->put('login_timestamp', now()->toString());

        if ($user->isStudent()) {
            app(DeviceBindingService::class)->bind($user, $request, $strictStudentBind);
            $request->session()->save();
        }

        return $this->redirectTargetForUser($user, $qrToken);
    }

    private function loginSuccessPayload(User $user, string $redirectUrl): array
    {
        return [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'identifier' => $user->student_number ?? $user->email,
            ],
            'role' => $user->role,
            'redirect' => $redirectUrl,
            'dashboard_url' => $redirectUrl,
        ];
    }
}
