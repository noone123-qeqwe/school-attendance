<?php

namespace App\Services;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Support\Facades\Log;

use RuntimeException;

class WebauthnService
{
    public function registrationOptions(User $user): array
    {
        $challenge = random_bytes(32);
        session(['webauthn.register_challenge' => $this->base64UrlEncode($challenge)]);

        return [
            'publicKey' => [
                'challenge' => $this->base64UrlEncode($challenge),
                'rp' => ['name' => config('app.name'), 'id' => $this->rpId()],
                'user' => [
                    // Hash user ID to 32 bytes (some Android authenticators throw NotReadableError for short IDs)
                    'id' => $this->base64UrlEncode(hash('sha256', (string) $user->id, true)),
                    'name' => $user->email,
                    'displayName' => $user->name,
                ],
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7],
                    ['type' => 'public-key', 'alg' => -257],
                ],
                'authenticatorSelection' => [
                    'authenticatorAttachment' => 'platform',
                    'userVerification' => 'preferred',
                ],
                'timeout' => 60000,
                'attestation' => 'none',
                'excludeCredentials' => $user->webauthnCredentials
                    ->map(fn ($credential) => [
                        'type' => 'public-key',
                        'id' => $credential->credential_id,
                    ])
                    ->values(),
            ],
        ];
    }

    public function storeCredential(User $user, array $credential): WebauthnCredential
    {
        $clientData = $this->decodeJson($credential['response']['clientDataJSON'] ?? null);
        $this->assertClientData($clientData, 'webauthn.create', session('webauthn.register_challenge'));

        $attestation = $this->decodeCbor($this->base64UrlDecode($credential['response']['attestationObject'] ?? ''));
        $authData = $attestation['authData'] ?? null;

        if (!is_string($authData) || strlen($authData) < 55) {
            throw new RuntimeException('Invalid authenticator data.');
        }

        $parsed = $this->parseAuthenticatorData($authData, true);
        $credentialId = $this->base64UrlEncode($parsed['credential_id']);
        $publicKey = $this->coseKeyToPem($parsed['credential_public_key']);

        session()->forget('webauthn.register_challenge');

        return WebauthnCredential::updateOrCreate(
            ['credential_id' => $credentialId],
            [
                'user_id' => $user->id,
                'public_key' => $publicKey,
                'sign_count' => $parsed['sign_count'],
                'device_name' => 'Phone fingerprint',
            ]
        );
    }

    public function authenticationOptions(User $user): array
    {
        $challenge = random_bytes(32);
        session(['webauthn.auth_challenge' => $this->base64UrlEncode($challenge)]);

        return [
            'publicKey' => [
                'challenge' => $this->base64UrlEncode($challenge),
                'rpId' => $this->rpId(),
                'allowCredentials' => $user->webauthnCredentials
                    ->map(fn ($credential) => [
                        'type' => 'public-key',
                        'id' => $credential->credential_id,
                    ])
                    ->values(),
                'userVerification' => 'preferred',
                'timeout' => 60000,
            ],
        ];
    }

    public function verifyAssertion(User $user, array $assertion): WebauthnCredential
    {
        $credentialId = $assertion['id'] ?? null;
        $normalizedCredentialId = $this->normalizeCredentialId($credentialId);
        
        Log::debug('WebAuthn verifyAssertion starting', [
            'user_id' => $user->id,
            'credential_id' => $credentialId,
            'normalized_credential_id' => $normalizedCredentialId,
            'session_challenge' => session('webauthn.auth_challenge') ? 'present' : 'missing',
        ]);
        
        $credential = $user->webauthnCredentials()
            ->where(function ($query) use ($credentialId, $normalizedCredentialId) {
                $query->where('credential_id', $credentialId);
                if ($normalizedCredentialId !== $credentialId) {
                    $query->orWhere('credential_id', $normalizedCredentialId);
                }
            })
            ->first();

        if (!$credential) {
            Log::error('WebAuthn credential not found', [
                'user_id' => $user->id,
            ]);
            throw new RuntimeException('Unknown biometric credential.');
        }

        Log::debug('WebAuthn credential found', [
            'user_id' => $user->id,
            'found_credential_id' => $credential->credential_id,
            'provided_credential_id' => $credentialId,
        ]);

        $clientDataJson = $this->base64UrlDecode($assertion['response']['clientDataJSON'] ?? '');
        $clientData = $this->decodeJson($assertion['response']['clientDataJSON'] ?? null);
        $this->assertClientData($clientData, 'webauthn.get', session('webauthn.auth_challenge'));

        $authenticatorData = $this->base64UrlDecode($assertion['response']['authenticatorData'] ?? '');
        $parsed = $this->parseAuthenticatorData($authenticatorData, false);

        if (($parsed['flags'] & 0x01) !== 0x01 || ($parsed['flags'] & 0x04) !== 0x04) {
            throw new RuntimeException('Fingerprint verification was not completed.');
        }

        $signedData = $authenticatorData . hash('sha256', $clientDataJson, true);
        $signature = $this->base64UrlDecode($assertion['response']['signature'] ?? '');

        $publicKey = openssl_pkey_get_public($credential->public_key);
        if ($publicKey === false) {
            throw new RuntimeException('Stored biometric public key is invalid. Please re-register your fingerprint.');
        }

        $verification = openssl_verify($signedData, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($publicKey);

        if ($verification !== 1) {
            throw new RuntimeException('Biometric signature verification failed.');
        }

        if ($parsed['sign_count'] > 0 && $credential->sign_count > 0 && $parsed['sign_count'] <= $credential->sign_count) {
            throw new RuntimeException('Possible replayed biometric response.');
        }

        $credential->forceFill([
            'sign_count' => max($credential->sign_count, $parsed['sign_count']),
            'last_used_at' => now(),
        ])->save();

        session()->forget('webauthn.auth_challenge');

        return $credential;
    }

    public function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function normalizeCredentialId(string $credentialId): string
    {
        $raw = $this->base64UrlDecode($credentialId);

        if ($raw === '') {
            return $credentialId;
        }

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function base64UrlDecode(?string $value): string
    {
        $value = (string) $value;
        $padding = strlen($value) % 4;

        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
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
            Log::debug('WebAuthn clientData type mismatch', [
                'expected' => $type,
                'actual' => $clientData['type'] ?? null,
                'challenge' => $clientData['challenge'] ?? null,
                'session_challenge' => $challenge,
            ]);
            throw new RuntimeException('Invalid WebAuthn challenge - type mismatch.');
        }

        if ($challenge === null || $challenge === '') {
            Log::error('WebAuthn challenge missing from session', [
                'type' => $type,
                'session_id' => session()->getId(),
                'all_session_keys' => array_keys(session()->all()),
            ]);
            throw new RuntimeException('WebAuthn session expired. Challenge not found. Please try again.');
        }

        $expectedChallenge = $this->base64UrlDecode($challenge);
        $actualChallenge = $this->base64UrlDecode($clientData['challenge'] ?? '');

        if ($expectedChallenge === '' || $actualChallenge === '') {
            Log::error('WebAuthn challenge encoding issue', [
                'expected_challenge' => $challenge,
                'actual_challenge' => $clientData['challenge'] ?? null,
                'expected_decoded_length' => strlen($expectedChallenge),
                'actual_decoded_length' => strlen($actualChallenge),
            ]);
            throw new RuntimeException('Invalid challenge encoding - unable to decode base64.');
        }

        if (!hash_equals($expectedChallenge, $actualChallenge)) {
            Log::debug('WebAuthn challenge mismatch', [
                'expected_raw' => bin2hex($expectedChallenge),
                'actual_raw' => bin2hex($actualChallenge),
                'expected_encoded' => $challenge,
                'actual_encoded' => $clientData['challenge'] ?? null,
                'expected_length' => strlen($expectedChallenge),
                'actual_length' => strlen($actualChallenge),
            ]);
            throw new RuntimeException('Invalid WebAuthn challenge - challenge values do not match.');
        }

        $originHost = parse_url($clientData['origin'] ?? '', PHP_URL_HOST);
        $rpId = $this->rpId();

        if (!$originHost || $originHost !== $rpId) {
            Log::warning('WebAuthn origin mismatch', [
                'origin_host' => $originHost,
                'rp_id' => $rpId,
                'full_origin' => $clientData['origin'] ?? null,
                'client_data' => $clientData,
            ]);
            throw new RuntimeException('Invalid WebAuthn origin - host mismatch.');
        }
    }

    private function normalizeBase64UrlString(string $value): string
    {
        $value = strtr($value, '+/', '-_');
        $value = rtrim($value, '=');
        return $value;
    }

    private function rpId(): string
    {
        $host = '';

        if (function_exists('request') && request()) {
            // Try x-forwarded-host first (set by proxies like ngrok)
            $host = trim((string) request()->header('x-forwarded-host'));
            
            // Fallback to x-forwarded-server
            if (!$host) {
                $host = trim((string) request()->header('x-forwarded-server'));
            }
            
            // Fallback to request host
            if (!$host) {
                $host = trim((string) request()->getHost());
            }
        }

        // Remove port numbers
        $host = preg_replace('/:\d+$/', '', $host);

        // Fallback to config if still empty
        if (!$host || $host === 'localhost') {
            $configHost = parse_url(config('app.url'), PHP_URL_HOST);
            if ($configHost && $configHost !== 'localhost' && $configHost !== '') {
                $host = $configHost;
            }
        }

        return strtolower($host ?: 'localhost');
    }

    private function parseAuthenticatorData(string $authData, bool $requireAttestedCredential): array
    {
        if (strlen($authData) < 37) {
            throw new RuntimeException('Invalid authenticator data.');
        }

        $flags = ord($authData[32]);
        $signCount = unpack('N', substr($authData, 33, 4))[1];
        $result = ['flags' => $flags, 'sign_count' => $signCount];

        if (!$requireAttestedCredential) {
            return $result;
        }

        if (($flags & 0x40) !== 0x40) {
            throw new RuntimeException('Missing attested credential data.');
        }

        $offset = 37 + 16;
        $credentialLength = unpack('n', substr($authData, $offset, 2))[1];
        $offset += 2;

        $result['credential_id'] = substr($authData, $offset, $credentialLength);
        $offset += $credentialLength;
        $result['credential_public_key'] = substr($authData, $offset);

        return $result;
    }

    private function coseKeyToPem(string $coseKey): string
    {
        $key = $this->decodeCbor($coseKey);
        $kty = $key[1] ?? null;
        $alg = $key[3] ?? null;

        if ($kty === 2 && $alg === -7) {
            $x = $key[-2] ?? null;
            $y = $key[-3] ?? null;

            if (!is_string($x) || !is_string($y)) {
                throw new RuntimeException('Invalid EC credential key.');
            }

            $pem = $this->ecPublicKeyToPem($x, $y);
            if (openssl_pkey_get_public($pem) === false) {
                throw new RuntimeException('Unable to parse EC public key from authenticator.');
            }

            return $pem;
        }

        if ($kty === 3 && $alg === -257) {
            $n = $key[-1] ?? null;
            $e = $key[-2] ?? null;

            if (!is_string($n) || !is_string($e)) {
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
        $offset = 0;
        return $this->readCbor($bytes, $offset);
    }

    private function readCbor(string $bytes, int &$offset): mixed
    {
        $initial = ord($bytes[$offset++]);
        $major = $initial >> 5;
        $additional = $initial & 0x1f;
        $length = $this->readCborLength($bytes, $offset, $additional);

        return match ($major) {
            0 => $length,
            1 => -1 - $length,
            2 => $this->readBytes($bytes, $offset, $length),
            3 => $this->readBytes($bytes, $offset, $length),
            4 => $this->readArray($bytes, $offset, $length),
            5 => $this->readMap($bytes, $offset, $length),
            7 => $additional === 20 ? false : ($additional === 21 ? true : null),
            default => throw new RuntimeException('Unsupported CBOR value.'),
        };
    }

    private function readCborLength(string $bytes, int &$offset, int $additional): int
    {
        if ($additional < 24) {
            return $additional;
        }

        return match ($additional) {
            24 => ord($bytes[$offset++]),
            25 => unpack('n', $this->readBytes($bytes, $offset, 2))[1],
            26 => unpack('N', $this->readBytes($bytes, $offset, 4))[1],
            default => throw new RuntimeException('Unsupported CBOR length.'),
        };
    }

    private function readBytes(string $bytes, int &$offset, int $length): string
    {
        $chunk = substr($bytes, $offset, $length);
        $offset += $length;

        return $chunk;
    }

    private function readArray(string $bytes, int &$offset, int $length): array
    {
        $items = [];

        for ($i = 0; $i < $length; $i++) {
            $items[] = $this->readCbor($bytes, $offset);
        }

        return $items;
    }

    private function readMap(string $bytes, int &$offset, int $length): array
    {
        $map = [];

        for ($i = 0; $i < $length; $i++) {
            $key = $this->readCbor($bytes, $offset);
            $map[$key] = $this->readCbor($bytes, $offset);
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
