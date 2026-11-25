<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WebauthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebAuthnBiometricFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createEcKeyPair()
    {
        $opensslConfig = tempnam(sys_get_temp_dir(), 'attendance-webauthn-');
        $this->assertNotFalse($opensslConfig);
        try {
            file_put_contents($opensslConfig, "[req]\n");
            $key = openssl_pkey_new([
                'config' => $opensslConfig,
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => 'prime256v1',
            ]);
        } finally {
            unlink($opensslConfig);
        }

        $this->assertNotFalse($key);

        return $key;
    }

    private function cborBytes(string $bytes): string
    {
        $len = strlen($bytes);
        if ($len < 24) {
            return chr(0x40 | $len) . $bytes;
        }
        if ($len < 256) {
            return "\x58" . chr($len) . $bytes;
        }

        return "\x59" . pack('n', $len) . $bytes;
    }

    private function buildAttestationObject($key, string $rpId, string $rawCredentialId, int $signCount = 1, int $flags = 0x45): string
    {
        $details = openssl_pkey_get_details($key);
        $x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);

        // COSE_Key map: {1: 2 (EC2), 3: -7 (ES256), -1: 1 (P-256), -2: x, -3: y}
        $coseKey = "\xa5"
            . "\x01\x02"
            . "\x03\x26"
            . "\x20\x01"
            . "\x21" . $this->cborBytes($x)
            . "\x22" . $this->cborBytes($y);

        $authData = hash('sha256', $rpId, true)
            . chr($flags)
            . pack('N', $signCount)
            . str_repeat("\x00", 16)
            . pack('n', strlen($rawCredentialId))
            . $rawCredentialId
            . $coseKey;

        // CBOR map: {"fmt": "none", "attStmt": {}, "authData": <bstr>}
        return "\xa3"
            . "\x63fmt\x64none"
            . "\x67attStmt\xa0"
            . "\x68authData" . $this->cborBytes($authData);
    }

    private function createStudent(string $studentNumber = '2026-8801', string $email = 'bio-student@example.test'): User
    {
        return User::create([
            'name' => 'Biometric Test Student',
            'student_number' => $studentNumber,
            'course' => 'BSIT',
            'year_level' => '2',
            'email' => $email,
            'password' => Hash::make('StrongStudentPass123!'),
            'role' => 'student',
            'is_active' => true,
        ]);
    }

    public function test_setup_registration_and_login_full_lifecycle_stores_transports_and_increments_sign_count(): void
    {
        $webauthn = app(WebauthnService::class);
        $origin = 'https://attendance.example';
        config([
            'webauthn.rp_id' => 'attendance.example',
            'webauthn.allowed_origins' => [$origin],
        ]);

        $student = $this->createStudent();
        $key = $this->createEcKeyPair();
        $rawCredId = random_bytes(32);
        $credIdB64Url = $webauthn->base64UrlEncode($rawCredId);

        $setupOptions = $this->postJson(route('webauthn.setup.options'), [
            'identifier' => $student->student_number,
            'password' => 'StrongStudentPass123!',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->json();

        $registerClientData = json_encode([
            'type' => 'webauthn.create',
            'challenge' => $setupOptions['challenge'],
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);

        $attestation = $this->buildAttestationObject($key, 'attendance.example', $rawCredId, 1);

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 14; Pixel 8) Chrome/125.0 Mobile'])
            ->postJson(route('webauthn.setup.register'), [
                'credential_id' => $credIdB64Url,
                'transports' => ['hybrid', 'internal'],
                'credential' => [
                    'id' => $credIdB64Url,
                    'type' => 'public-key',
                    'transports' => ['hybrid', 'internal'],
                    'response' => [
                        'attestationObject' => $webauthn->base64UrlEncode($attestation),
                        'clientDataJSON' => $webauthn->base64UrlEncode($registerClientData),
                    ],
                ],
            ])->assertOk()
            ->assertJsonPath('success', true);

        $stored = $student->webauthnCredentials()->firstOrFail();
        $this->assertSame($credIdB64Url, $stored->credential_id);
        $this->assertSame('device_lock', $stored->biometric_type);
        $this->assertSame(['internal', 'hybrid'], $stored->transports);
        $this->assertSame('Android Device', $stored->device_name);
        $this->assertSame(1, (int) $stored->sign_count);

        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        // Next, verify loginOptions includes normalized transports, client-device hints, and account flag
        $loginOpts = $this->postJson(route('webauthn.login.options'), [
            'identifier' => $student->email,
            'biometric_method' => 'device_lock',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('has_account_credentials', true)
            ->assertJsonPath('hints.0', 'client-device')
            ->assertJsonPath('userVerification', 'required')
            ->assertJsonPath('allowCredentials.0.id', $credIdB64Url)
            ->assertJsonPath('allowCredentials.0.transports', ['internal', 'hybrid'])
            ->json();

        $getClientData = json_encode([
            'type' => 'webauthn.get',
            'challenge' => $loginOpts['challenge'],
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);

        $getAuthData = hash('sha256', 'attendance.example', true) . chr(0x05) . pack('N', 2);
        $this->assertTrue(openssl_sign(
            $getAuthData . hash('sha256', $getClientData, true),
            $signature,
            $key,
            OPENSSL_ALGO_SHA256
        ));

        $this->postJson(route('webauthn.login'), [
            'identifier' => $student->email,
            'credential_id' => $credIdB64Url,
            'biometric_method' => 'device_lock',
            'assertion' => [
                'id' => $credIdB64Url,
                'response' => [
                    'clientDataJSON' => $webauthn->base64UrlEncode($getClientData),
                    'authenticatorData' => $webauthn->base64UrlEncode($getAuthData),
                    'signature' => $webauthn->base64UrlEncode($signature),
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('role', 'student');

        $this->assertAuthenticatedAs($student);
        $stored->refresh();
        $this->assertSame(2, (int) $stored->sign_count);
        $this->assertNotNull($stored->last_used_at);
    }

    public function test_legacy_credentials_leave_unknown_transports_to_the_browser_and_discoverable_options_include_hints(): void
    {
        $webauthn = app(WebauthnService::class);
        config(['webauthn.rp_id' => 'attendance.example']);

        $student = $this->createStudent('2026-8802', 'legacy@example.test');
        $key = $this->createEcKeyPair();
        $rawId = random_bytes(24);
        $b64UrlId = $webauthn->base64UrlEncode($rawId);

        $student->webauthnCredentials()->create([
            'credential_id' => base64_encode($rawId),
            'public_key' => openssl_pkey_get_details($key)['key'],
            'biometric_type' => 'fingerprint',
            'transports' => null,
        ]);

        $this->postJson(route('webauthn.login.options'), [
            'identifier' => $student->student_number,
        ])->assertOk()
            ->assertJsonPath('has_account_credentials', true)
            ->assertJsonPath('hints.0', 'client-device')
            ->assertJsonPath('allowCredentials.0.id', $b64UrlId)
            ->assertJsonMissingPath('allowCredentials.0.transports');

        $this->postJson(route('webauthn.login.options'), [])
            ->assertOk()
            ->assertJsonPath('discoverable', true)
            ->assertJsonPath('has_account_credentials', false)
            ->assertJsonPath('hints.0', 'client-device')
            ->assertJsonCount(0, 'allowCredentials');
    }

    public function test_passkey_setup_respects_required_password_rotation(): void
    {
        $student = $this->createStudent();
        $student->forceFill(['must_change_password' => true])->save();

        $this->postJson(route('webauthn.setup.options'), [
            'identifier' => $student->email,
            'password' => 'StrongStudentPass123!',
        ])->assertForbidden()
            ->assertJsonPath('code', 'password_reset_required')
            ->assertSessionMissing('webauthn.register_challenge');
        $this->assertGuest();
    }

    public function test_assertion_rejects_replayed_sign_count_expired_challenge_missing_uv_and_untrusted_origin(): void
    {
        $webauthn = app(WebauthnService::class);
        $origin = 'https://attendance.example';
        config([
            'webauthn.rp_id' => 'attendance.example',
            'webauthn.allowed_origins' => [$origin],
        ]);

        $student = $this->createStudent('2026-8803', 'security@example.test');
        $key = $this->createEcKeyPair();
        $rawId = random_bytes(16);
        $credId = $webauthn->base64UrlEncode($rawId);

        $student->webauthnCredentials()->create([
            'credential_id' => $credId,
            'public_key' => openssl_pkey_get_details($key)['key'],
            'sign_count' => 5,
            'biometric_type' => 'fingerprint',
            'transports' => ['internal'],
        ]);

        $signAssertion = function (string $challenge, string $reqOrigin, int $flags, int $signCount) use ($webauthn, $key, $credId) {
            $clientData = json_encode([
                'type' => 'webauthn.get',
                'challenge' => $challenge,
                'origin' => $reqOrigin,
            ], JSON_THROW_ON_ERROR);
            $authData = hash('sha256', 'attendance.example', true) . chr($flags) . pack('N', $signCount);
            openssl_sign($authData . hash('sha256', $clientData, true), $sig, $key, OPENSSL_ALGO_SHA256);

            return [
                'credential_id' => $credId,
                'assertion' => [
                    'id' => $credId,
                    'response' => [
                        'clientDataJSON' => $webauthn->base64UrlEncode($clientData),
                        'authenticatorData' => $webauthn->base64UrlEncode($authData),
                        'signature' => $webauthn->base64UrlEncode($sig),
                    ],
                ],
            ];
        };

        // 1. Missing session challenge
        $this->postJson(route('webauthn.login'), $signAssertion($webauthn->base64UrlEncode(random_bytes(32)), $origin, 0x05, 6))
            ->assertStatus(401)
            ->assertJsonPath('success', false);
        $this->assertGuest();

        // 2. Replayed sign_count (<= 5)
        $opts = $this->postJson(route('webauthn.login.options'), ['identifier' => $student->email])->assertOk()->json();
        $this->postJson(route('webauthn.login'), $signAssertion($opts['challenge'], $origin, 0x05, 5) + ['identifier' => $student->email])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
        $this->assertGuest();

        // 3. Missing UV flag (UP only = 0x01) -> rejected with platform-neutral error (never mentioning Face ID)
        $opts = $this->postJson(route('webauthn.login.options'), ['identifier' => $student->email])->assertOk()->json();
        $uvRes = $this->postJson(route('webauthn.login'), $signAssertion($opts['challenge'], $origin, 0x01, 6) + ['identifier' => $student->email])
            ->assertStatus(401);
        $this->assertStringNotContainsString('Face ID', (string) $uvRes->json('message'));
        $this->assertStringNotContainsString('Windows Hello', (string) $uvRes->json('message'));
        $this->assertGuest();

        // 4. Untrusted origin -> rejected
        $opts = $this->postJson(route('webauthn.login.options'), ['identifier' => $student->email])->assertOk()->json();
        $this->postJson(route('webauthn.login'), $signAssertion($opts['challenge'], 'https://evil.example', 0x05, 6) + ['identifier' => $student->email])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
        $this->assertGuest();

        // 5. Cross-user mismatch -> rejected
        $otherStudent = $this->createStudent('2026-8805', 'other-student@example.test');
        $opts = $this->postJson(route('webauthn.login.options'), ['identifier' => $student->email])->assertOk()->json();
        $this->postJson(route('webauthn.login'), $signAssertion($opts['challenge'], $origin, 0x05, 6) + ['identifier' => $otherStudent->email])
            ->assertStatus(401)
            ->assertJsonPath('code', 'BIOMETRIC_UNAVAILABLE');
        $this->assertGuest();

        // A rejected ceremony cannot be submitted again, even with correct account details.
        $this->postJson(route('webauthn.login'), $signAssertion($opts['challenge'], $origin, 0x05, 6))
            ->assertStatus(401)
            ->assertJsonPath('code', 'PASSKEY_CHALLENGE_EXPIRED');
        $this->assertGuest();

        $opts = $this->postJson(route('webauthn.login.options'), ['identifier' => $student->email])->assertOk()->json();
        $this->travel(121)->seconds();
        $this->postJson(route('webauthn.login'), $signAssertion($opts['challenge'], $origin, 0x05, 6))
            ->assertStatus(401)
            ->assertJsonPath('code', 'PASSKEY_CHALLENGE_EXPIRED')
            ->assertJsonPath('success', false);
        $this->assertGuest();
    }

    public function test_devices_endpoint_returns_transports_and_login_view_uses_platform_neutral_watchdog_flow(): void
    {
        $student = $this->createStudent('2026-8804', 'devices@example.test');
        $key = $this->createEcKeyPair();
        $student->webauthnCredentials()->create([
            'credential_id' => 'cred-transport-test',
            'public_key' => openssl_pkey_get_details($key)['key'],
            'device_name' => 'Android Device',
            'biometric_type' => 'fingerprint',
            'transports' => ['internal', 'hybrid'],
        ]);

        $this->actingAs($student)
            ->getJson(route('webauthn.devices'))
            ->assertOk()
            ->assertJsonPath('0.transports', ['internal', 'hybrid'])
            ->assertJsonPath('0.biometric_type', 'fingerprint');

        auth()->logout();

        $loginHtml = $this->get(route('login'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Face ID', $loginHtml);
        $this->assertStringNotContainsString('Windows Hello', $loginHtml);
        $this->assertStringContainsString('buildAllowCredentials', $loginHtml);
        $this->assertStringContainsString('runWithCredentialWatchdog', $loginHtml);
        $this->assertStringContainsString('fetchJsonWithTimeout', $loginHtml);
        $this->assertStringContainsString('abortActiveBiometricAttempt', $loginHtml);
        $this->assertStringContainsString('client-device', $loginHtml);
        $this->assertStringContainsString('getTransports', $loginHtml);
        $this->assertStringNotContainsString('Use passkey from another device', $loginHtml);
        $this->assertStringNotContainsString('Set up fingerprint on this device', $loginHtml);
        $this->assertStringContainsString('on this account', $loginHtml);
    }
}
