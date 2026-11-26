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

        $this->getJson(route('webauthn.register.options'))
            ->assertOk()
            ->assertJsonPath('excludeCredentials.0.id', 'cred-transport-test');

        auth()->logout();

        $loginHtml = $this->get(route('login'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Face ID', $loginHtml);
        $this->assertStringNotContainsString('Windows Hello', $loginHtml);
        $this->assertStringContainsString('device-passkey-capabilities.js', $loginHtml);
        $this->assertStringContainsString('DevicePasskeys.verifyFingerprint', $loginHtml);
        $this->assertStringContainsString('DevicePasskeys.registerFingerprint', $loginHtml);
        $this->assertStringContainsString('runWithCredentialWatchdog', $loginHtml);
        $this->assertStringContainsString('fetchJsonWithTimeout', $loginHtml);
        $this->assertStringContainsString('abortActiveBiometricAttempt', $loginHtml);
        $this->assertStringNotContainsString('Use passkey from another device', $loginHtml);
        $this->assertStringNotContainsString('Set up fingerprint on this device', $loginHtml);
        $this->assertStringContainsString('on this account', $loginHtml);
    }

    public function test_available_methods_is_independent_and_login_options_enforces_database_first_allow_list(): void
    {
        $webauthn = app(WebauthnService::class);
        $origin = 'https://attendance.example';
        config([
            'webauthn.rp_id' => 'attendance.example',
            'webauthn.allowed_origins' => [$origin],
        ]);

        // 1. Checking available methods never creates a WebAuthn login challenge in session.
        $this->postJson(route('webauthn.available.methods'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['available_methods', 'face_login_enabled'])
            ->assertSessionMissing('webauthn.login');

        // 2. Account without passkeys returns 404 BIOMETRIC_UNAVAILABLE with face_login_enabled metadata.
        $unenrolled = $this->createStudent('2026-8806', 'no-passkey@example.test');
        $this->postJson(route('webauthn.login.options'), [
            'identifier' => $unenrolled->email,
            'biometric_method' => 'fingerprint',
        ])->assertNotFound()
            ->assertJsonPath('code', 'BIOMETRIC_UNAVAILABLE')
            ->assertJsonStructure(['face_login_enabled', 'available_methods'])
            ->assertSessionMissing('webauthn.login');

        // 3. Assertion signed by a credential NOT in the database allow list issued for the account is rejected.
        $studentA = $this->createStudent('2026-8807', 'student-a@example.test');
        $studentB = $this->createStudent('2026-8808', 'student-b@example.test');
        $keyA = $this->createEcKeyPair();
        $keyB = $this->createEcKeyPair();
        $credIdA = $webauthn->base64UrlEncode(random_bytes(16));
        $credIdB = $webauthn->base64UrlEncode(random_bytes(16));

        $studentA->webauthnCredentials()->create([
            'credential_id' => $credIdA,
            'public_key' => openssl_pkey_get_details($keyA)['key'],
            'biometric_type' => 'fingerprint',
            'transports' => ['internal'],
        ]);
        $studentB->webauthnCredentials()->create([
            'credential_id' => $credIdB,
            'public_key' => openssl_pkey_get_details($keyB)['key'],
            'biometric_type' => 'fingerprint',
            'transports' => ['internal'],
        ]);

        $opts = $this->postJson(route('webauthn.login.options'), [
            'identifier' => $studentA->email,
            'biometric_method' => 'fingerprint',
        ])->assertOk()
            ->assertJsonPath('allowCredentials.0.id', $credIdA)
            ->json();

        // Try submitting studentB's valid cryptographic signature against studentA's challenge.
        $clientData = json_encode([
            'type' => 'webauthn.get',
            'challenge' => $opts['challenge'],
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);
        $authData = hash('sha256', 'attendance.example', true) . chr(0x05) . pack('N', 1);
        openssl_sign($authData . hash('sha256', $clientData, true), $sigB, $keyB, OPENSSL_ALGO_SHA256);

        $this->postJson(route('webauthn.login'), [
            'identifier' => $studentA->email,
            'credential_id' => $credIdB,
            'assertion' => [
                'id' => $credIdB,
                'response' => [
                    'clientDataJSON' => $webauthn->base64UrlEncode($clientData),
                    'authenticatorData' => $webauthn->base64UrlEncode($authData),
                    'signature' => $webauthn->base64UrlEncode($sigB),
                ],
            ],
        ])->assertStatus(401)
            ->assertJsonPath('code', 'BIOMETRIC_UNAVAILABLE');
        $this->assertGuest();
    }

    public function test_authenticated_registration_requires_consent_blocks_duplicates_and_prevents_cross_account_hijack(): void
    {
        $webauthn = app(WebauthnService::class);
        $origin = 'https://attendance.example';
        config([
            'webauthn.rp_id' => 'attendance.example',
            'webauthn.allowed_origins' => [$origin],
        ]);

        $studentA = $this->createStudent('2026-8810', 'owner-a@example.test');
        $studentB = $this->createStudent('2026-8811', 'attacker-b@example.test');
        $key = $this->createEcKeyPair();
        $rawCredId = random_bytes(32);
        $credId = $webauthn->base64UrlEncode($rawCredId);

        // 1. Explicit consent_accepted = false is rejected
        $optsA = $this->actingAs($studentA)->getJson(route('webauthn.register.options'))
            ->assertOk()
            ->assertJsonPath('authenticatorSelection.authenticatorAttachment', 'platform')
            ->assertJsonPath('authenticatorSelection.userVerification', 'required')
            ->json();

        $clientDataA = json_encode([
            'type' => 'webauthn.create',
            'challenge' => $optsA['challenge'],
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);
        $attestation = $this->buildAttestationObject($key, 'attendance.example', $rawCredId, 1);

        $this->actingAs($studentA)->postJson(route('webauthn.register'), [
            'credential_id' => $credId,
            'consent_accepted' => false,
            'credential' => [
                'id' => $credId,
                'rawId' => $credId,
                'type' => 'public-key',
                'response' => [
                    'attestationObject' => $webauthn->base64UrlEncode($attestation),
                    'clientDataJSON' => $webauthn->base64UrlEncode($clientDataA),
                ],
            ],
        ])->assertStatus(422)
            ->assertJsonPath('code', 'CONSENT_REQUIRED');

        // 2. First-time registration with consent succeeds and records safe metadata (never raw biometric data)
        $optsA = $this->actingAs($studentA)->getJson(route('webauthn.register.options'))->assertOk()->json();
        $clientDataA = json_encode([
            'type' => 'webauthn.create',
            'challenge' => $optsA['challenge'],
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($studentA)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0'])
            ->postJson(route('webauthn.register'), [
                'credential_id' => $credId,
                'device_name' => 'Study Laptop',
                'biometric_type' => 'device_lock',
                'consent_accepted' => true,
                'credential' => [
                    'id' => $credId,
                    'rawId' => $credId,
                    'type' => 'public-key',
                    'authenticatorAttachment' => 'platform',
                    'transports' => ['internal'],
                    'response' => [
                        'attestationObject' => $webauthn->base64UrlEncode($attestation),
                        'clientDataJSON' => $webauthn->base64UrlEncode($clientDataA),
                    ],
                ],
            ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('device.device_name', 'Study Laptop')
            ->assertJsonPath('device.authenticator_attachment', 'platform')
            ->assertJsonMissingPath('device.public_key');

        $stored = $studentA->webauthnCredentials()->firstOrFail();
        $this->assertNotNull($stored->consent_given_at);
        $this->assertSame('00000000-0000-0000-0000-000000000000', $stored->aaguid);
        $this->assertSame('Chrome on Windows PC', $stored->user_agent_summary);

        // 3. Duplicate registration by the same user is blocked (409) AND setupOptions preserves excludeCredentials
        $setupOpts = $this->postJson(route('webauthn.setup.options'), [
            'identifier' => $studentA->email,
            'password' => 'StrongStudentPass123!',
            'consent_accepted' => true,
        ])->assertOk()->json();
        $this->assertSame($credId, $setupOpts['excludeCredentials'][0]['id'] ?? null);

        $optsDup = $this->actingAs($studentA)->getJson(route('webauthn.register.options'))->assertOk()->json();
        $clientDataDup = json_encode([
            'type' => 'webauthn.create',
            'challenge' => $optsDup['challenge'],
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($studentA)->postJson(route('webauthn.register'), [
            'credential_id' => $credId,
            'consent_accepted' => true,
            'credential' => [
                'id' => $credId,
                'rawId' => $credId,
                'type' => 'public-key',
                'response' => [
                    'attestationObject' => $webauthn->base64UrlEncode($attestation),
                    'clientDataJSON' => $webauthn->base64UrlEncode($clientDataDup),
                ],
            ],
        ])->assertStatus(409)
            ->assertJsonPath('code', 'CREDENTIAL_ALREADY_REGISTERED');

        // 4. Cross-account hijack attempt: studentB tries to register studentA's credential ID -> 409
        auth()->logout();
        $this->flushSession();
        $optsB = $this->actingAs($studentB)->getJson(route('webauthn.register.options'))->assertOk()->json();
        $clientDataB = json_encode([
            'type' => 'webauthn.create',
            'challenge' => $optsB['challenge'],
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($studentB)->postJson(route('webauthn.register'), [
            'credential_id' => $credId,
            'consent_accepted' => true,
            'credential' => [
                'id' => $credId,
                'rawId' => $credId,
                'type' => 'public-key',
                'response' => [
                    'attestationObject' => $webauthn->base64UrlEncode($attestation),
                    'clientDataJSON' => $webauthn->base64UrlEncode($clientDataB),
                ],
            ],
        ])->assertStatus(409)
            ->assertJsonPath('code', 'CREDENTIAL_ALREADY_REGISTERED');

        $this->assertSame(0, $studentB->webauthnCredentials()->count());
    }

    public function test_credential_revocation_blocks_login_prevents_unauthorized_deletion_and_allows_reenrollment(): void
    {
        $webauthn = app(WebauthnService::class);
        $origin = 'https://attendance.example';
        config([
            'webauthn.rp_id' => 'attendance.example',
            'webauthn.allowed_origins' => [$origin],
        ]);

        $owner = $this->createStudent('2026-8812', 'revoke-owner@example.test');
        $other = $this->createStudent('2026-8813', 'revoke-other@example.test');
        $key = $this->createEcKeyPair();
        $rawId = random_bytes(20);
        $credId = $webauthn->base64UrlEncode($rawId);

        $cred = $owner->webauthnCredentials()->create([
            'credential_id' => $credId,
            'public_key' => openssl_pkey_get_details($key)['key'],
            'sign_count' => 3,
            'device_name' => 'Original Phone',
            'biometric_type' => 'fingerprint',
            'transports' => ['internal'],
        ]);

        // Unauthorized user cannot revoke or rename owner's credential
        $this->actingAs($other)->deleteJson(route('webauthn.remove'), [
            'credential_id' => $credId,
        ])->assertNotFound()->assertJsonPath('code', 'CREDENTIAL_NOT_FOUND');

        $this->actingAs($other)->patchJson(route('webauthn.rename'), [
            'credential_id' => $credId,
            'device_name' => 'Hacked Device',
        ])->assertNotFound();

        // Owner can rename their own credential
        auth()->logout();
        $this->flushSession();
        $this->actingAs($owner)->patchJson(route('webauthn.rename'), [
            'credential_id' => $credId,
            'device_name' => 'Renamed Phone',
        ])->assertOk()->assertJsonPath('device.device_name', 'Renamed Phone');

        // Owner revokes their credential
        $this->actingAs($owner)->deleteJson(route('webauthn.remove'), [
            'credential_id' => $credId,
            'reason' => 'lost_device',
        ])->assertOk()->assertJsonPath('success', true);

        $cred->refresh();
        $this->assertNotNull($cred->revoked_at);
        $this->assertSame('lost_device', $cred->revoked_reason);

        // Revoked credential is no longer returned in /webauthn/devices or login options
        $this->actingAs($owner)->getJson(route('webauthn.devices'))
            ->assertOk()
            ->assertJsonCount(0);

        auth()->logout();
        $this->postJson(route('webauthn.login.options'), ['identifier' => $owner->email])
            ->assertNotFound()
            ->assertJsonPath('code', 'BIOMETRIC_UNAVAILABLE');

        // Even if someone forces a discoverable challenge and presents the revoked credential, login is rejected
        $discOpts = $this->postJson(route('webauthn.login.options'), [])->assertOk()->json();
        $clientData = json_encode([
            'type' => 'webauthn.get',
            'challenge' => $discOpts['challenge'],
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);
        $authData = hash('sha256', 'attendance.example', true) . chr(0x05) . pack('N', 4);
        openssl_sign($authData . hash('sha256', $clientData, true), $sig, $key, OPENSSL_ALGO_SHA256);

        $this->postJson(route('webauthn.login'), [
            'credential_id' => $credId,
            'assertion' => [
                'id' => $credId,
                'response' => [
                    'clientDataJSON' => $webauthn->base64UrlEncode($clientData),
                    'authenticatorData' => $webauthn->base64UrlEncode($authData),
                    'signature' => $webauthn->base64UrlEncode($sig),
                ],
            ],
        ])->assertStatus(401);
        $this->assertGuest();

        // Owner can cleanly re-register a new credential after revoking the old one
        $newKey = $this->createEcKeyPair();
        $newRawId = random_bytes(20);
        $newCredId = $webauthn->base64UrlEncode($newRawId);
        $regOpts = $this->actingAs($owner)->getJson(route('webauthn.register.options'))
            ->assertOk()
            ->assertJsonCount(0, 'excludeCredentials')
            ->json();

        $regClientData = json_encode([
            'type' => 'webauthn.create',
            'challenge' => $regOpts['challenge'],
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);
        $attestation = $this->buildAttestationObject($newKey, 'attendance.example', $newRawId, 1);

        $this->actingAs($owner)->postJson(route('webauthn.register'), [
            'credential_id' => $newCredId,
            'consent_accepted' => true,
            'credential' => [
                'id' => $newCredId,
                'rawId' => $newCredId,
                'type' => 'public-key',
                'response' => [
                    'attestationObject' => $webauthn->base64UrlEncode($attestation),
                    'clientDataJSON' => $webauthn->base64UrlEncode($regClientData),
                ],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $this->actingAs($owner)->getJson(route('webauthn.devices'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.credential_id', $newCredId);
    }

    public function test_sign_count_zero_downgrade_and_malformed_cbor_are_safely_rejected(): void
    {
        $webauthn = app(WebauthnService::class);
        $origin = 'https://attendance.example';
        config([
            'webauthn.rp_id' => 'attendance.example',
            'webauthn.allowed_origins' => [$origin],
        ]);

        $student = $this->createStudent('2026-8814', 'hardening@example.test');
        $key = $this->createEcKeyPair();
        $rawId = random_bytes(16);
        $credId = $webauthn->base64UrlEncode($rawId);

        $student->webauthnCredentials()->create([
            'credential_id' => $credId,
            'public_key' => openssl_pkey_get_details($key)['key'],
            'sign_count' => 10,
            'biometric_type' => 'device_lock',
            'transports' => ['internal'],
        ]);

        // 1. Sign-count downgrade to 0 when stored sign_count > 0 must be rejected (W3C §7.2 step 21)
        $opts = $this->postJson(route('webauthn.login.options'), ['identifier' => $student->email])->assertOk()->json();
        $clientData = json_encode([
            'type' => 'webauthn.get',
            'challenge' => $opts['challenge'],
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);
        $authDataZero = hash('sha256', 'attendance.example', true) . chr(0x05) . pack('N', 0);
        openssl_sign($authDataZero . hash('sha256', $clientData, true), $sigZero, $key, OPENSSL_ALGO_SHA256);

        $this->postJson(route('webauthn.login'), [
            'identifier' => $student->email,
            'credential_id' => $credId,
            'assertion' => [
                'id' => $credId,
                'response' => [
                    'clientDataJSON' => $webauthn->base64UrlEncode($clientData),
                    'authenticatorData' => $webauthn->base64UrlEncode($authDataZero),
                    'signature' => $webauthn->base64UrlEncode($sigZero),
                ],
            ],
        ])->assertStatus(401)->assertJsonPath('code', 'VERIFICATION_FAILED');
        $this->assertGuest();

        // 2. Malformed / DoS CBOR payload (huge map count 0xba 0xff 0xff 0xff 0xff) is rejected cleanly without crashing
        $regOpts = $this->actingAs($student)->getJson(route('webauthn.register.options'))->assertOk()->json();
        $regClientData = json_encode([
            'type' => 'webauthn.create',
            'challenge' => $regOpts['challenge'],
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);
        $dosCbor = "\xba\xff\xff\xff\xff";

        $this->actingAs($student)->postJson(route('webauthn.register'), [
            'credential_id' => 'dos-test-cred',
            'consent_accepted' => true,
            'credential' => [
                'id' => 'dos-test-cred',
                'rawId' => 'dos-test-cred',
                'type' => 'public-key',
                'response' => [
                    'attestationObject' => $webauthn->base64UrlEncode($dosCbor),
                    'clientDataJSON' => $webauthn->base64UrlEncode($regClientData),
                ],
            ],
        ])->assertStatus(422)->assertJsonPath('code', 'REGISTRATION_FAILED');
    }

    public function test_fingerprint_registration_view_enforces_explicit_consent_and_has_no_fake_scan_animation(): void
    {
        $student = $this->createStudent('2026-8815', 'ui-check@example.test');
        $html = $this->actingAs($student)->get(route('settings.biometrics.fingerprint'))->assertOk()->getContent();

        $this->assertStringContainsString('id="bioConsentCheckbox"', $html);
        $this->assertStringContainsString('id="bioDeviceNameInput"', $html);
        $this->assertStringContainsString('id="bioUnsupportedPanel"', $html);
        $this->assertStringNotContainsString('bio-sensor-pad is-scanning', $html);
        $this->assertStringNotContainsString('bio-radar-ring', $html);
    }
}
