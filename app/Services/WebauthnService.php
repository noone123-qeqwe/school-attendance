<?php

namespace App\Services;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WebauthnService
{
    public const REGISTER_CHALLENGE_TTL_SECONDS = 180;
    public const LOGIN_CHALLENGE_TTL_SECONDS = 120;

    private const MAX_CBOR_DEPTH = 16;
    private const MAX_CBOR_ITEMS = 128;
    private const MAX_CBOR_BYTES = 65536;

    public function registrationOptions(User $user): array
    {
        $challenge = random_bytes(32);
        $encodedChallenge = $this->base64UrlEncode($challenge);

        session(['webauthn.register_challenge' => [
            'value' => $encodedChallenge,
            'user_id' => $user->id,
            'issued_at' => now()->timestamp,
            'expires_at' => now()->timestamp + self::REGISTER_CHALLENGE_TTL_SECONDS,
        ]]);

        $userIdentifier = $user->email ?: ($user->student_number ?: ($user->employee_id ?: 'user_' . $user->id));
        $displayName = $user->name ?: $userIdentifier;

        $excludeCredentials = $user->webauthnCredentials()
            ->active()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (WebauthnCredential $credential): array => array_filter([
                'type' => 'public-key',
                'id' => $this->normalizeCredentialId($credential->credential_id),
                'transports' => $this->sanitizeTransports($credential->transports),
            ], fn (mixed $value): bool => $value !== null))
            ->values()
            ->all();

        return [
            'publicKey' => [
                'challenge' => $encodedChallenge,
                'rp' => [
                    'name' => config('app.name', 'Smart Attendance'),
                    'id' => $this->rpId(),
                ],
                'user' => [
                    // 32-byte deterministic handle derived from user ID (avoids short-handle authenticator issues and leaks no PII).
                    'id' => $this->expectedUserHandle($user),
                    'name' => $userIdentifier,
                    'displayName' => $displayName,
                ],
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7],   // ES256 (ECDSA w/ SHA-256)
                    ['type' => 'public-key', 'alg' => -257], // RS256 (RSASSA-PKCS1-v1_5 w/ SHA-256)
                ],
                'authenticatorSelection' => [
                    'authenticatorAttachment' => 'platform',
                    'userVerification' => 'required',
                    'residentKey' => 'preferred',
                    'requireResidentKey' => false,
                ],
                'hints' => ['client-device'],
                'timeout' => 60000,
                'attestation' => 'none',
                'excludeCredentials' => $excludeCredentials,
            ],
        ];
    }

    /**
     * Validate a WebAuthn registration attestation and persist only the public key and safe metadata.
     *
     * @param  array<string, mixed>  $credential
     * @param  array{device_name?: ?string, biometric_type?: ?string, user_agent?: ?string, consent_given?: bool}  $metadata
     */
    public function storeCredential(User $user, array $credential, array $metadata = []): WebauthnCredential
    {
        $challenge = session()->pull('webauthn.register_challenge');
        if (!is_array($challenge)
            || ($challenge['user_id'] ?? null) !== $user->id
            || ($challenge['expires_at'] ?? 0) <= now()->timestamp
            || empty($challenge['value'])) {
            throw new RuntimeException('Registration challenge expired. Please start registration again.');
        }

        $clientData = $this->decodeJson($credential['response']['clientDataJSON'] ?? null);
        $this->assertClientData($clientData, 'webauthn.create', (string) $challenge['value']);

        $attestationRaw = $this->base64UrlDecode($credential['response']['attestationObject'] ?? '');
        if ($attestationRaw === '' || strlen($attestationRaw) > self::MAX_CBOR_BYTES) {
            throw new RuntimeException('Invalid or oversized attestation response.');
        }

        $attestation = $this->decodeCbor($attestationRaw);
        $authData = is_array($attestation) ? ($attestation['authData'] ?? null) : null;

        if (!is_string($authData) || strlen($authData) < 55) {
            throw new RuntimeException('Invalid authenticator data.');
        }

        $parsed = $this->parseAuthenticatorData($authData, true);
        $this->assertRpIdHash($parsed);

        if (!$parsed['up'] || !$parsed['uv']) {
            throw new RuntimeException('Device verification was not completed. Complete your device biometric or screen lock prompt and try again.');
        }

        $credentialId = $this->base64UrlEncode($parsed['credential_id']);
        if ($credentialId === '' || strlen($parsed['credential_id']) > 1023) {
            throw new RuntimeException('Invalid credential identifier returned by authenticator.');
        }

        $submittedCredentialId = $credential['rawId'] ?? $credential['id'] ?? null;
        if (!is_string($submittedCredentialId)
            || $this->normalizeCredentialId($submittedCredentialId) !== $this->normalizeCredentialId($credentialId)) {
            throw new RuntimeException('The credential ID does not match the verified authenticator response. Please try registering this device again.');
        }

        $publicKey = $this->coseKeyToPem($parsed['credential_public_key']);
        $variants = $this->credentialIdVariants($credentialId);

        // Enforce global ownership and prevent duplicate registration across all active credentials.
        $existingActive = WebauthnCredential::active()
            ->whereIn('credential_id', $variants)
            ->first();

        if ($existingActive) {
            if ($existingActive->user_id !== $user->id) {
                Log::warning('WebAuthn cross-account credential registration blocked', [
                    'requesting_user_id' => $user->id,
                    'owner_user_id' => $existingActive->user_id,
                    'credential_fingerprint' => hash('sha256', $credentialId),
                ]);
                throw new RuntimeException('This credential is already registered to another account.');
            }

            throw new RuntimeException('This credential is already registered on your account.');
        }

        $transports = $this->sanitizeTransports(
            $credential['transports'] ?? ($credential['response']['transports'] ?? null)
        ) ?? ['internal'];

        $attachment = $this->sanitizeAttachment(
            $credential['authenticatorAttachment'] ?? null,
            $transports
        );

        $deviceName = $this->sanitizeDeviceName(
            $metadata['device_name'] ?? null,
            $metadata['user_agent'] ?? null
        );

        $biometricType = in_array($metadata['biometric_type'] ?? null, ['fingerprint', 'face', 'device_lock'], true)
            ? (string) $metadata['biometric_type']
            : 'device_lock';

        $userAgentSummary = $this->summarizeUserAgent($metadata['user_agent'] ?? null);

        // If this user previously revoked this exact credential ID, reactivate with the newly attested key material.
        $revokedOwn = WebauthnCredential::where('user_id', $user->id)
            ->whereNotNull('revoked_at')
            ->whereIn('credential_id', $variants)
            ->first();

        $attributes = [
            'credential_id' => $credentialId,
            'public_key' => $publicKey,
            'sign_count' => $parsed['sign_count'],
            'device_name' => $deviceName,
            'biometric_type' => $biometricType,
            'transports' => $transports,
            'aaguid' => $parsed['aaguid'],
            'authenticator_attachment' => $attachment,
            'backed_up' => $parsed['bs'],
            'user_agent_summary' => $userAgentSummary,
            'consent_given_at' => ($metadata['consent_given'] ?? true) ? now() : null,
            'last_used_at' => now(),
            'revoked_at' => null,
            'revoked_reason' => null,
        ];

        if ($revokedOwn) {
            $revokedOwn->forceFill($attributes)->save();

            return $revokedOwn;
        }

        return WebauthnCredential::create($attributes + ['user_id' => $user->id]);
    }

    public function authenticationOptions(?User $user = null): array
    {
        $challenge = random_bytes(32);
        $allowCredentials = [];

        if ($user) {
            $allowCredentials = $user->webauthnCredentials()
                ->active()
                ->orderByDesc('last_used_at')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (WebauthnCredential $credential): array => array_filter([
                    'type' => 'public-key',
                    'id' => $this->normalizeCredentialId($credential->credential_id),
                    'transports' => $this->sanitizeTransports($credential->transports),
                ], fn (mixed $value): bool => $value !== null))
                ->values()
                ->toArray();
        }

        return [
            'publicKey' => [
                'challenge' => $this->base64UrlEncode($challenge),
                'rpId' => $this->rpId(),
                'allowCredentials' => $allowCredentials,
                'hints' => ['client-device'],
                'userVerification' => 'required',
                'timeout' => 60000,
            ],
        ];
    }

    public function verifyAssertion(User $user, array $assertion, string $challenge, ?WebauthnCredential $credential = null): WebauthnCredential
    {
        $credentialId = $assertion['id'] ?? null;
        if (!is_string($credentialId) || trim($credentialId) === '') {
            throw new RuntimeException('Missing credential identifier in assertion.');
        }

        if (isset($assertion['rawId']) && is_string($assertion['rawId']) && $assertion['rawId'] !== '') {
            if ($this->normalizeCredentialId($assertion['rawId']) !== $this->normalizeCredentialId($credentialId)) {
                throw new RuntimeException('Credential identifier mismatch in assertion payload.');
            }
        }

        $normalizedCredentialId = $this->normalizeCredentialId($credentialId);
        $variants = $this->credentialIdVariants($credentialId);

        if (!$credential) {
            $credential = $user->webauthnCredentials()
                ->whereIn('credential_id', $variants)
                ->first();
        }

        if (!$credential) {
            $otherCredential = WebauthnCredential::active()
                ->whereIn('credential_id', $variants)
                ->first();

            if ($otherCredential && $otherCredential->user_id !== $user->id) {
                Log::warning('WebAuthn assertion attempted with another user credential', [
                    'expected_user_id' => $user->id,
                    'credential_user_id' => $otherCredential->user_id,
                ]);
                throw new RuntimeException('This biometric credential belongs to a different account.');
            }

            throw new RuntimeException('Unknown biometric credential.');
        }

        if ($credential->user_id !== $user->id) {
            throw new RuntimeException('This biometric credential belongs to a different account.');
        }

        if ($credential->revoked_at !== null) {
            Log::warning('WebAuthn assertion attempted with revoked credential', [
                'user_id' => $user->id,
                'credential_id' => $credential->id,
            ]);
            throw new RuntimeException('This biometric credential has been revoked. Please register a new device credential.');
        }

        if (!$credential->isActive()) {
            throw new RuntimeException('Stored passkey public key is invalid. Please register a new passkey.');
        }

        if ($normalizedCredentialId !== $this->normalizeCredentialId($credential->credential_id)) {
            throw new RuntimeException('The authenticator returned a different credential.');
        }

        $userHandle = $assertion['response']['userHandle'] ?? null;
        if (is_string($userHandle) && trim($userHandle) !== '') {
            $expectedHandle = $this->expectedUserHandle($user);
            $normalizedHandle = $this->normalizeCredentialId($userHandle);
            if (!hash_equals($expectedHandle, $normalizedHandle) && (string) $user->id !== $userHandle) {
                Log::warning('WebAuthn assertion userHandle mismatch', [
                    'expected_user_id' => $user->id,
                ]);
                throw new RuntimeException('The authenticator user handle does not match this account.');
            }
        }

        $clientDataEncoded = $assertion['response']['clientDataJSON'] ?? null;
        $clientDataJson = $this->base64UrlDecode(is_string($clientDataEncoded) ? $clientDataEncoded : '');
        $clientData = $this->decodeJson(is_string($clientDataEncoded) ? $clientDataEncoded : null);
        $this->assertClientData($clientData, 'webauthn.get', $challenge);

        $authenticatorDataEncoded = $assertion['response']['authenticatorData'] ?? null;
        $authenticatorData = $this->base64UrlDecode(is_string($authenticatorDataEncoded) ? $authenticatorDataEncoded : '');
        $parsed = $this->parseAuthenticatorData($authenticatorData, false);
        $this->assertRpIdHash($parsed);

        if (!$parsed['up']) {
            Log::warning('WebAuthn UP flag not set', ['flags' => $parsed['flags'], 'user_id' => $user->id]);
            throw new RuntimeException('User presence was not confirmed by the authenticator.');
        }

        if (!$parsed['uv']) {
            Log::warning('WebAuthn UV flag not set', ['flags' => $parsed['flags'], 'user_id' => $user->id]);
            throw new RuntimeException('Device verification was not completed. Complete your device biometric or screen lock prompt and try again.');
        }

        $signatureEncoded = $assertion['response']['signature'] ?? null;
        $signature = $this->base64UrlDecode(is_string($signatureEncoded) ? $signatureEncoded : '');
        if ($signature === '') {
            throw new RuntimeException('Missing cryptographic signature in assertion.');
        }

        $signedData = $authenticatorData . hash('sha256', $clientDataJson, true);
        $publicKey = openssl_pkey_get_public($credential->public_key);
        if ($publicKey === false) {
            throw new RuntimeException('Stored passkey public key is invalid. Please register a new passkey.');
        }

        $verification = openssl_verify($signedData, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verification !== 1) {
            throw new RuntimeException('Biometric signature verification failed.');
        }

        // W3C WebAuthn §7.2 step 21: if either stored or incoming signCount is non-zero,
        // incoming MUST strictly exceed stored to detect cloned or replayed authenticators.
        $storedSignCount = (int) $credential->sign_count;
        $incomingSignCount = (int) $parsed['sign_count'];
        if (($storedSignCount > 0 || $incomingSignCount > 0) && $incomingSignCount <= $storedSignCount) {
            Log::warning('WebAuthn possible replay/clone detected via sign_count', [
                'user_id' => $user->id,
                'credential_pk' => $credential->id,
                'stored_sign_count' => $storedSignCount,
                'incoming_sign_count' => $incomingSignCount,
            ]);
            throw new RuntimeException('Possible replayed or cloned biometric response.');
        }

        $credential->forceFill([
            'sign_count' => max($storedSignCount, $incomingSignCount),
            'backed_up' => $credential->backed_up || $parsed['bs'],
            'last_used_at' => now(),
        ])->save();

        return $credential;
    }

    public function expectedUserHandle(User $user): string
    {
        return $this->base64UrlEncode(hash('sha256', (string) $user->id, true));
    }

    /**
     * Return all base64/base64url encoding variants of a credential ID for safe lookup.
     *
     * @return array<int, string>
     */
    public function credentialIdVariants(string $credentialId): array
    {
        $normalized = $this->normalizeCredentialId($credentialId);
        $paddedUrl = $normalized . str_repeat('=', (4 - strlen($normalized) % 4) % 4);
        $stdUnpadded = strtr($normalized, '-_', '+/');
        $stdPadded = strtr($paddedUrl, '-_', '+/');

        return array_values(array_unique(array_filter([
            $credentialId,
            $normalized,
            $paddedUrl,
            $stdUnpadded,
            $stdPadded,
        ], fn (string $value): bool => $value !== '')));
    }

    public function normalizeCredentialId(string $credentialId): string
    {
        return rtrim(strtr(trim($credentialId), '+/', '-_'), '=');
    }

    public function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    public function rpId(): string
    {
        $cleanHost = strtolower(trim((string) config('webauthn.rp_id', '')));
        if ($cleanHost === '') {
            $cleanHost = strtolower((string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost'));
        }

        $cleanHost = trim($cleanHost, '.');
        if ($cleanHost === '127.0.0.1' || $cleanHost === '::1' || $cleanHost === '') {
            return 'localhost';
        }

        return $cleanHost;
    }

    public function sanitizeDeviceName(?string $deviceName, ?string $userAgent = null): string
    {
        $clean = trim(preg_replace('/[\x00-\x1F\x7F]+/u', '', (string) $deviceName) ?? '');
        if ($clean !== '') {
            return mb_substr($clean, 0, 80);
        }

        return $this->defaultDeviceNameFromUserAgent($userAgent);
    }

    public function defaultDeviceNameFromUserAgent(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);
        if (str_contains($ua, 'android')) {
            return 'Android Device';
        }
        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ipod')) {
            return 'iPhone / iPad';
        }
        if (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os x')) {
            return 'Mac';
        }
        if (str_contains($ua, 'windows')) {
            return 'Windows PC';
        }
        if (str_contains($ua, 'cros')) {
            return 'Chromebook';
        }
        if (str_contains($ua, 'linux')) {
            return 'Linux Device';
        }
        if (str_contains($ua, 'mobile')) {
            return 'Mobile Device';
        }

        return 'Device Passkey';
    }

    public function summarizeUserAgent(?string $userAgent): ?string
    {
        $ua = trim((string) $userAgent);
        if ($ua === '') {
            return null;
        }

        $lower = strtolower($ua);
        $browser = match (true) {
            str_contains($lower, 'edg/') || str_contains($lower, 'edga/') || str_contains($lower, 'edgios/') => 'Edge',
            str_contains($lower, 'opr/') || str_contains($lower, 'opera') => 'Opera',
            str_contains($lower, 'firefox') || str_contains($lower, 'fxios') => 'Firefox',
            str_contains($lower, 'chrome') || str_contains($lower, 'crios') => 'Chrome',
            str_contains($lower, 'safari') => 'Safari',
            default => 'Browser',
        };

        $os = $this->defaultDeviceNameFromUserAgent($ua);

        return mb_substr("{$browser} on {$os}", 0, 160);
    }

    /**
     * Normalize WebAuthn authenticator transports from the browser response.
     *
     * @param  array<int, mixed>|null  $transports
     * @return array<int, string>|null
     */
    private function sanitizeTransports(?array $transports): ?array
    {
        if (!$transports) {
            return null;
        }

        $allowed = ['internal', 'hybrid', 'usb', 'nfc', 'ble', 'smart-card'];
        $filtered = array_values(array_unique(array_filter(
            array_map(fn ($value) => is_string($value) ? strtolower(trim($value)) : '', $transports),
            fn (string $value) => in_array($value, $allowed, true)
        )));

        if ($filtered === []) {
            return null;
        }

        if (in_array('internal', $filtered, true)) {
            $filtered = array_values(array_unique(array_merge(['internal'], $filtered)));
        }

        return $filtered;
    }

    /**
     * @param  array<int, string>  $transports
     */
    private function sanitizeAttachment(?string $attachment, array $transports): string
    {
        $clean = strtolower(trim((string) $attachment));
        if (in_array($clean, ['platform', 'cross-platform'], true)) {
            return $clean;
        }

        return in_array('internal', $transports, true) ? 'platform' : 'cross-platform';
    }

    private function base64UrlDecode(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }

    private function decodeJson(?string $encoded): array
    {
        $json = $this->base64UrlDecode($encoded);
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new RuntimeException('Invalid WebAuthn client data.');
        }

        return $data;
    }

    private function assertClientData(array $clientData, string $type, ?string $challenge): void
    {
        if (($clientData['type'] ?? null) !== $type) {
            throw new RuntimeException('Invalid WebAuthn challenge - type mismatch.');
        }

        if ($challenge === null || $challenge === '') {
            throw new RuntimeException('WebAuthn session expired. Challenge not found. Please try again.');
        }

        $expectedChallenge = $this->base64UrlDecode($challenge);
        $actualChallenge = $this->base64UrlDecode(is_string($clientData['challenge'] ?? null) ? $clientData['challenge'] : '');

        if ($expectedChallenge === '' || $actualChallenge === '') {
            throw new RuntimeException('Invalid challenge encoding.');
        }

        if (!hash_equals($expectedChallenge, $actualChallenge)) {
            throw new RuntimeException('Invalid WebAuthn challenge - challenge values do not match.');
        }

        $origin = $clientData['origin'] ?? null;
        $normalizedOrigin = is_string($origin) ? $this->normalizeWebOrigin($origin) : null;
        $allowedOrigins = array_map(
            fn (mixed $allowed): ?string => is_string($allowed) ? $this->normalizeWebOrigin($allowed) : null,
            (array) config('webauthn.allowed_origins', [])
        );
        $allowedAndroidOrigins = array_values(array_filter(
            (array) config('webauthn.allowed_android_origins', []),
            'is_string'
        ));

        $isAllowedWebOrigin = $normalizedOrigin !== null
            && in_array($normalizedOrigin, array_filter($allowedOrigins), true);
        $originHost = $normalizedOrigin !== null ? strtolower((string) parse_url($normalizedOrigin, PHP_URL_HOST)) : '';
        $rpId = $this->rpId();
        $localAliases = ['localhost', '127.0.0.1', '[::1]', '::1'];
        $isLocalOriginForLocalRp = in_array($originHost, $localAliases, true) && in_array($rpId, $localAliases, true);
        $originBelongsToRp = $originHost === $rpId
            || str_ends_with($originHost, '.' . $rpId)
            || $isLocalOriginForLocalRp;
        $isAllowedWebOrigin = $isAllowedWebOrigin && $originBelongsToRp;

        $isAllowedAndroidOrigin = is_string($origin)
            && in_array($origin, $allowedAndroidOrigins, true);
        $isCrossOrigin = array_key_exists('crossOrigin', $clientData)
            && $clientData['crossOrigin'] !== false;

        if ($isCrossOrigin || (!$isAllowedWebOrigin && !$isAllowedAndroidOrigin)) {
            Log::warning('WebAuthn origin mismatch', [
                'origin' => is_string($origin) ? substr($origin, 0, 200) : null,
                'rp_id' => $rpId,
                'cross_origin' => $isCrossOrigin,
            ]);
            throw new RuntimeException('The sign-in origin is not trusted.');
        }
    }

    private function normalizeWebOrigin(string $origin): ?string
    {
        $parts = parse_url(trim($origin));
        if (!is_array($parts)
            || !isset($parts['scheme'], $parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || !in_array(($parts['path'] ?? ''), ['', '/'], true)) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower(rtrim((string) $parts['host'], '.'));
        if ($host === '' || !in_array($scheme, ['https', 'http'], true)) {
            return null;
        }

        // HTTP is permitted only for local development origins.
        if ($scheme === 'http' && !in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)) {
            return null;
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        if ($port !== null && (($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80))) {
            $port = null;
        }

        return $scheme . '://' . $host . ($port === null ? '' : ':' . $port);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseAuthenticatorData(string $authData, bool $requireAttestedCredential): array
    {
        $totalLength = strlen($authData);
        if ($totalLength < 37) {
            throw new RuntimeException('Invalid authenticator data.');
        }

        $flags = ord($authData[32]);
        $signCount = unpack('N', substr($authData, 33, 4))[1];
        $result = [
            'rp_id_hash' => substr($authData, 0, 32),
            'flags' => $flags,
            'up' => ($flags & 0x01) === 0x01,
            'uv' => ($flags & 0x04) === 0x04,
            'be' => ($flags & 0x08) === 0x08,
            'bs' => ($flags & 0x10) === 0x10,
            'at' => ($flags & 0x40) === 0x40,
            'ed' => ($flags & 0x80) === 0x80,
            'sign_count' => $signCount,
        ];

        if (!$requireAttestedCredential) {
            return $result;
        }

        if (!$result['at']) {
            throw new RuntimeException('Missing attested credential data.');
        }

        if ($totalLength < 55) {
            throw new RuntimeException('Truncated attested credential data.');
        }

        $aaguidBytes = substr($authData, 37, 16);
        $result['aaguid'] = $this->formatAaguid($aaguidBytes);

        $offset = 53;
        $credentialLength = unpack('n', substr($authData, $offset, 2))[1];
        $offset += 2;

        if ($credentialLength <= 0 || ($offset + $credentialLength) >= $totalLength) {
            throw new RuntimeException('Invalid attested credential ID length.');
        }

        $result['credential_id'] = substr($authData, $offset, $credentialLength);
        $offset += $credentialLength;
        $result['credential_public_key'] = substr($authData, $offset);

        return $result;
    }

    private function formatAaguid(string $bytes): string
    {
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    private function assertRpIdHash(array $authenticatorData): void
    {
        $rpIdHash = $authenticatorData['rp_id_hash'] ?? null;
        $expectedRpIdHash = hash('sha256', $this->rpId(), true);

        if (!is_string($rpIdHash) || !hash_equals($expectedRpIdHash, $rpIdHash)) {
            throw new RuntimeException('Authenticator response is bound to a different website. Please register this device again.');
        }
    }

    private function coseKeyToPem(string $coseKey): string
    {
        $key = $this->decodeCbor($coseKey);
        if (!is_array($key)) {
            throw new RuntimeException('Invalid COSE public key structure.');
        }

        $kty = $key[1] ?? null;
        $alg = $key[3] ?? null;

        // EC2 P-256 (ES256)
        if ($kty === 2 && $alg === -7) {
            $crv = $key[-1] ?? null;
            $x = $key[-2] ?? null;
            $y = $key[-3] ?? null;

            if ($crv !== 1 || !is_string($x) || !is_string($y) || strlen($x) !== 32 || strlen($y) !== 32) {
                throw new RuntimeException('Invalid EC P-256 credential key.');
            }

            $pem = $this->ecPublicKeyToPem($x, $y);
            if (openssl_pkey_get_public($pem) === false) {
                throw new RuntimeException('Unable to parse EC public key from authenticator.');
            }

            return $pem;
        }

        // RSA (RS256)
        if ($kty === 3 && $alg === -257) {
            $n = $key[-1] ?? null;
            $e = $key[-2] ?? null;

            if (!is_string($n) || !is_string($e) || strlen($n) < 128 || strlen($e) < 1) {
                throw new RuntimeException('Invalid RSA credential key.');
            }

            $pem = $this->rsaPublicKeyToPem($n, $e);
            if (openssl_pkey_get_public($pem) === false) {
                throw new RuntimeException('Unable to parse RSA public key from authenticator.');
            }

            return $pem;
        }

        throw new RuntimeException('Unsupported WebAuthn key type.');
    }

    private function ecPublicKeyToPem(string $x, string $y): string
    {
        $point = "\x04" . $x . $y;
        $algorithm = $this->derSequence(
            $this->derOid('1.2.840.10045.2.1') . $this->derOid('1.2.840.10045.3.1.7')
        );
        $spki = $this->derSequence($algorithm . $this->derBitString($point));

        return $this->pem($spki, 'PUBLIC KEY');
    }

    private function rsaPublicKeyToPem(string $n, string $e): string
    {
        $rsaKey = $this->derSequence($this->derInteger($n) . $this->derInteger($e));
        $algorithm = $this->derSequence($this->derOid('1.2.840.113549.1.1.1') . "\x05\x00");
        $spki = $this->derSequence($algorithm . $this->derBitString($rsaKey));

        return $this->pem($spki, 'PUBLIC KEY');
    }

    private function decodeCbor(string $bytes): mixed
    {
        if ($bytes === '' || strlen($bytes) > self::MAX_CBOR_BYTES) {
            throw new RuntimeException('Invalid CBOR payload size.');
        }

        $offset = 0;

        return $this->readCbor($bytes, $offset, 0);
    }

    private function readCbor(string $bytes, int &$offset, int $depth): mixed
    {
        if ($depth > self::MAX_CBOR_DEPTH) {
            throw new RuntimeException('CBOR nesting depth exceeded.');
        }

        $totalLength = strlen($bytes);
        if ($offset >= $totalLength) {
            throw new RuntimeException('Unexpected end of CBOR data.');
        }

        $initial = ord($bytes[$offset++]);
        $major = $initial >> 5;
        $additional = $initial & 0x1f;

        if ($major === 7) {
            return match ($additional) {
                20 => false,
                21 => true,
                22, 23 => null,
                default => throw new RuntimeException('Unsupported CBOR simple value.'),
            };
        }

        $length = $this->readCborLength($bytes, $offset, $additional);

        return match ($major) {
            0 => $length,
            1 => -1 - $length,
            2, 3 => $this->readBytes($bytes, $offset, $length),
            4 => $this->readArray($bytes, $offset, $length, $depth + 1),
            5 => $this->readMap($bytes, $offset, $length, $depth + 1),
            6 => $this->readCbor($bytes, $offset, $depth + 1),
            default => throw new RuntimeException('Unsupported CBOR value.'),
        };
    }

    private function readCborLength(string $bytes, int &$offset, int $additional): int
    {
        if ($additional < 24) {
            return $additional;
        }

        return match ($additional) {
            24 => ord($this->readBytes($bytes, $offset, 1)),
            25 => unpack('n', $this->readBytes($bytes, $offset, 2))[1],
            26 => unpack('N', $this->readBytes($bytes, $offset, 4))[1],
            default => throw new RuntimeException('Unsupported CBOR length.'),
        };
    }

    private function readBytes(string $bytes, int &$offset, int $length): string
    {
        if ($length < 0 || $length > self::MAX_CBOR_BYTES || ($offset + $length) > strlen($bytes)) {
            throw new RuntimeException('Truncated or oversized CBOR byte string.');
        }

        $chunk = substr($bytes, $offset, $length);
        $offset += $length;

        return $chunk;
    }

    private function readArray(string $bytes, int &$offset, int $length, int $depth): array
    {
        if ($length < 0 || $length > self::MAX_CBOR_ITEMS) {
            throw new RuntimeException('CBOR array exceeds safe item limit.');
        }

        $items = [];
        for ($i = 0; $i < $length; $i++) {
            $items[] = $this->readCbor($bytes, $offset, $depth);
        }

        return $items;
    }

    private function readMap(string $bytes, int &$offset, int $length, int $depth): array
    {
        if ($length < 0 || $length > self::MAX_CBOR_ITEMS) {
            throw new RuntimeException('CBOR map exceeds safe item limit.');
        }

        $map = [];
        for ($i = 0; $i < $length; $i++) {
            $key = $this->readCbor($bytes, $offset, $depth);
            if (!is_int($key) && !is_string($key)) {
                throw new RuntimeException('Unsupported CBOR map key type.');
            }
            $map[$key] = $this->readCbor($bytes, $offset, $depth);
        }

        return $map;
    }

    private function derSequence(string $value): string
    {
        return "\x30" . $this->derLength(strlen($value)) . $value;
    }

    private function derBitString(string $value): string
    {
        return "\x03" . $this->derLength(strlen($value) + 1) . "\x00" . $value;
    }

    private function derInteger(string $value): string
    {
        $value = ltrim($value, "\x00");

        if ($value === '' || (ord($value[0]) & 0x80)) {
            $value = "\x00" . $value;
        }

        return "\x02" . $this->derLength(strlen($value)) . $value;
    }

    private function derOid(string $oid): string
    {
        $parts = array_map('intval', explode('.', $oid));
        $body = chr(($parts[0] * 40) + $parts[1]);

        foreach (array_slice($parts, 2) as $part) {
            $encoded = chr($part & 0x7f);
            while ($part >>= 7) {
                $encoded = chr(($part & 0x7f) | 0x80) . $encoded;
            }
            $body .= $encoded;
        }

        return "\x06" . $this->derLength(strlen($body)) . $body;
    }

    private function derLength(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xff) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private function pem(string $der, string $label): string
    {
        return "-----BEGIN {$label}-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END {$label}-----\n";
    }
}
