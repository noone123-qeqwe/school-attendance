<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\WebauthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiUserAuthAndBiometricTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_users_across_roles_can_login_with_password()
    {
        $studentA = User::factory()->create([
            'name' => 'Alice Student',
            'student_number' => 'STU-001',
            'email' => 'alice@school.edu',
            'password' => Hash::make('PasswordA123!'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $studentB = User::factory()->create([
            'name' => 'Bob Student',
            'student_number' => 'STU-002',
            'email' => 'bob@school.edu',
            'password' => Hash::make('PasswordB123!'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $teacher = User::factory()->create([
            'name' => 'Taylor Teacher',
            'employee_id' => 'EMP-101',
            'email' => 'teacher@school.edu',
            'password' => Hash::make('TeacherPass123!'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $parent = User::factory()->create([
            'name' => 'Patricia Parent',
            'email' => 'parent@family.edu',
            'password' => Hash::make('ParentPass123!'),
            'role' => 'parent',
            'is_active' => true,
        ]);

        // Student A logs in with student number
        $resA = $this->post(route('login.submit'), [
            'identifier' => 'STU-001',
            'password' => 'PasswordA123!',
        ]);
        $resA->assertRedirect(route('home'));
        $this->assertTrue(Auth::check());
        $this->assertEquals($studentA->id, Auth::id());
        $this->assertEquals('student', session('user_role'));
        Auth::logout();

        // Student B logs in with email
        $resB = $this->post(route('login.submit'), [
            'identifier' => 'bob@school.edu',
            'password' => 'PasswordB123!',
        ]);
        $resB->assertRedirect(route('home'));
        $this->assertTrue(Auth::check());
        $this->assertEquals($studentB->id, Auth::id());
        $this->assertEquals('student', session('user_role'));
        Auth::logout();

        // Teacher logs in with employee_id
        $resT = $this->post(route('login.submit'), [
            'identifier' => 'EMP-101',
            'password' => 'TeacherPass123!',
        ]);
        $resT->assertRedirect(route('teacher.dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals($teacher->id, Auth::id());
        $this->assertEquals('teacher', session('user_role'));
        Auth::logout();

        // Parent logs in with email
        $resP = $this->post(route('login.submit'), [
            'identifier' => 'parent@family.edu',
            'password' => 'ParentPass123!',
        ]);
        $resP->assertRedirect(route('parent.dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertEquals($parent->id, Auth::id());
        $this->assertEquals('parent', session('user_role'));
        Auth::logout();
    }

    public function test_invalid_credentials_and_deactivated_accounts_are_rejected()
    {
        $user = User::factory()->create([
            'student_number' => 'STU-ACTIVE',
            'password' => Hash::make('CorrectPassword123!'),
            'role' => 'student',
            'is_active' => true,
        ]);

        // Wrong password
        $resWrong = $this->post(route('login.submit'), [
            'identifier' => 'STU-ACTIVE',
            'password' => 'WrongPassword!',
        ]);
        $this->assertFalse(Auth::check());

        // Deactivated account
        $deactivated = User::factory()->create([
            'student_number' => 'STU-DEACT',
            'password' => Hash::make('CorrectPassword123!'),
            'role' => 'student',
            'is_active' => false,
        ]);

        $resDeact = $this->post(route('login.submit'), [
            'identifier' => 'STU-DEACT',
            'password' => 'CorrectPassword123!',
        ]);
        $this->assertFalse(Auth::check());
    }

    public function test_multi_user_biometric_isolation_and_conflict_prevention()
    {
        $userA = User::factory()->create([
            'name' => 'Alice',
            'student_number' => 'STU-A',
            'email' => 'alice@test.edu',
            'password' => Hash::make('passwordA123!'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $userB = User::factory()->create([
            'name' => 'Bob',
            'student_number' => 'STU-B',
            'email' => 'bob@test.edu',
            'password' => Hash::make('passwordB123!'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $credA_id = 'cred_unique_alice_123';
        $credB_id = 'cred_unique_bob_456';

        // Mock WebauthnService storeCredential to create and return WebauthnCredential model
        $this->mock(WebauthnService::class, function ($mock) {
            $mock->shouldReceive('storeCredential')
                ->andReturnUsing(function ($user, array $credential) {
                    return WebauthnCredential::create([
                        'user_id' => $user->id,
                        'credential_id' => $credential['id'] ?? $credential['credential_id'] ?? 'mock_cred_id',
                        'public_key' => 'mock_public_key',
                        'sign_count' => 0,
                        'device_name' => 'Mock Device',
                        'biometric_type' => 'fingerprint',
                    ]);
                });
        });

        // Alice registers Credential A
        $resA = $this->postJson(route('webauthn.setup.register'), [
            'identifier' => 'STU-A',
            'password' => 'passwordA123!',
            'credential_id' => $credA_id,
            'credential' => [
                'id' => $credA_id,
                'type' => 'public-key',
                'response' => [
                    'attestationObject' => 'dummy_attestation_alice',
                    'clientDataJSON' => 'dummy_client_alice',
                ],
            ],
            'device_name' => 'Alice Device',
        ]);
        $resA->assertStatus(200)->assertJson(['success' => true]);

        $aliceCred = WebauthnCredential::where('credential_id', $credA_id)->first();
        $this->assertNotNull($aliceCred);
        $this->assertEquals($userA->id, $aliceCred->user_id);

        // Bob registers Credential B
        $resB = $this->postJson(route('webauthn.setup.register'), [
            'identifier' => 'STU-B',
            'password' => 'passwordB123!',
            'credential_id' => $credB_id,
            'credential' => [
                'id' => $credB_id,
                'type' => 'public-key',
                'response' => [
                    'attestationObject' => 'dummy_attestation_bob',
                    'clientDataJSON' => 'dummy_client_bob',
                ],
            ],
            'device_name' => 'Bob Device',
        ]);
        $resB->assertStatus(200)->assertJson(['success' => true]);

        $bobCred = WebauthnCredential::where('credential_id', $credB_id)->first();
        $this->assertNotNull($bobCred);
        $this->assertEquals($userB->id, $bobCred->user_id);

        // Multi-user shared device support: Bob registering on the same device / platform authenticator
        // succeeds and creates an isolated credential for Bob without overwriting Alice's credential
        $resShared = $this->postJson(route('webauthn.setup.register'), [
            'identifier' => 'STU-B',
            'password' => 'passwordB123!',
            'credential_id' => $credA_id,
            'credential' => [
                'id' => $credA_id,
                'type' => 'public-key',
                'response' => [
                    'attestationObject' => 'dummy_shared',
                    'clientDataJSON' => 'dummy_shared_client',
                ],
            ],
            'device_name' => 'Bob Shared Device',
        ]);
        $resShared->assertStatus(200)->assertJson(['success' => true]);

        // Verify Alice still owns her credential record
        $checkA = WebauthnCredential::where('credential_id', $credA_id)->where('user_id', $userA->id)->first();
        $this->assertNotNull($checkA);
        $this->assertEquals($userA->id, $checkA->user_id);

        // Verify Bob also owns his credential record for the shared device
        $checkB = WebauthnCredential::where('credential_id', $credA_id)->where('user_id', $userB->id)->first();
        $this->assertNotNull($checkB);
        $this->assertEquals($userB->id, $checkB->user_id);
    }

    public function test_targeted_and_discoverable_webauthn_login_options()
    {
        $userA = User::factory()->create([
            'student_number' => 'STU-TARGET-A',
            'email' => 'target.a@test.edu',
            'is_active' => true,
        ]);

        WebauthnCredential::create([
            'user_id' => $userA->id,
            'credential_id' => 'cred_target_a',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
            'device_name' => 'Target A Device',
        ]);

        // Targeted lookup returns user A's credential
        $resTarget = $this->postJson(route('webauthn.login.options'), [
            'identifier' => 'STU-TARGET-A',
        ]);
        $resTarget->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        $allowed = $resTarget->json('allowCredentials');
        $this->assertCount(1, $allowed);
        $this->assertEquals('cred_target_a', $allowed[0]['id']);

        // Discoverable passkey lookup (no identifier) returns allowCredentials: []
        $resDiscoverable = $this->postJson(route('webauthn.login.options'), [
            'identifier' => '',
        ]);
        $resDiscoverable->assertStatus(200)
            ->assertJson([
                'success' => true,
                'allowCredentials' => [],
            ])
            ->assertJsonStructure(['challenge']);
    }

    public function test_biometric_login_prevents_cross_user_impersonation()
    {
        $userA = User::factory()->create([
            'name' => 'Alice User',
            'student_number' => 'STU-CROSS-A',
            'role' => 'student',
            'is_active' => true,
        ]);

        $userB = User::factory()->create([
            'name' => 'Bob User',
            'student_number' => 'STU-CROSS-B',
            'role' => 'student',
            'is_active' => true,
        ]);

        WebauthnCredential::create([
            'user_id' => $userA->id,
            'credential_id' => 'cred_cross_a',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
            'device_name' => 'Alice Phone',
        ]);

        WebauthnCredential::create([
            'user_id' => $userB->id,
            'credential_id' => 'cred_cross_b',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
            'device_name' => 'Bob Phone',
        ]);

        // Mock WebauthnService so assertion verification checks credential ownership
        $this->mock(WebauthnService::class, function ($mock) {
            $mock->shouldReceive('verifyAssertion')
                ->andReturnUsing(function ($user, $assertion, $credential) {
                    if ($credential && $credential->user_id === $user->id) {
                        return $credential;
                    }
                    throw new \RuntimeException('Biometric verification failed.');
                });
        });

        // Cross-account attack: User enters Alice's ID ('STU-CROSS-A'), but provides Bob's credential ('cred_cross_b')
        $resMismatch = $this->postJson(route('webauthn.login'), [
            'identifier' => 'STU-CROSS-A',
            'credential_id' => 'cred_cross_b',
            'assertion' => [
                'id' => 'cred_cross_b',
                'type' => 'public-key',
                'response' => ['signature' => 'sig_b'],
            ],
        ]);

        $resMismatch->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'CREDENTIAL_MISMATCH',
            ]);
        $this->assertFalse(Auth::check());

        // Legitimate Alice login succeeds
        $resAlice = $this->postJson(route('webauthn.login'), [
            'identifier' => 'STU-CROSS-A',
            'credential_id' => 'cred_cross_a',
            'assertion' => [
                'id' => 'cred_cross_a',
                'type' => 'public-key',
                'response' => ['signature' => 'sig_a'],
            ],
        ]);

        $resAlice->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        $this->assertTrue(Auth::check());
        $this->assertEquals($userA->id, Auth::id());
        Auth::logout();

        // Discoverable Passkey login (no identifier supplied): Resolves directly to Bob from verified credential
        $resBobPasskey = $this->postJson(route('webauthn.login'), [
            'credential_id' => 'cred_cross_b',
            'assertion' => [
                'id' => 'cred_cross_b',
                'type' => 'public-key',
                'response' => ['signature' => 'sig_b'],
            ],
        ]);

        $resBobPasskey->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'id' => $userB->id,
                    'identifier' => 'STU-CROSS-B',
                ],
            ]);
        $this->assertTrue(Auth::check());
        $this->assertEquals($userB->id, Auth::id());
        Auth::logout();
    }

    public function test_multi_account_saved_identifiers_included_in_login_options()
    {
        $userA = User::factory()->create([
            'student_number' => 'STU-SAVED-A',
            'is_active' => true,
        ]);
        $userB = User::factory()->create([
            'student_number' => 'STU-SAVED-B',
            'is_active' => true,
        ]);

        WebauthnCredential::create([
            'user_id' => $userA->id,
            'credential_id' => 'cred_saved_a',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
            'device_name' => 'Phone A',
        ]);
        WebauthnCredential::create([
            'user_id' => $userB->id,
            'credential_id' => 'cred_saved_b',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
            'device_name' => 'Phone B',
        ]);

        // When user A is targeted, but device has saved both accounts:
        $response = $this->postJson(route('webauthn.login.options'), [
            'identifier' => 'STU-SAVED-A',
            'saved_identifiers' => ['STU-SAVED-A', 'STU-SAVED-B'],
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $allowed = $response->json('allowCredentials');
        $this->assertCount(2, $allowed);
        $credIds = array_column($allowed, 'id');
        $this->assertContains('cred_saved_a', $credIds);
        $this->assertContains('cred_saved_b', $credIds);
    }

    public function test_discoverable_biometric_login_clears_stale_session_and_authenticates_any_user()
    {
        $userA = User::factory()->create([
            'student_number' => 'STU-STALE-A',
            'is_active' => true,
        ]);
        $userB = User::factory()->create([
            'student_number' => 'STU-STALE-B',
            'is_active' => true,
        ]);

        WebauthnCredential::create([
            'user_id' => $userA->id,
            'credential_id' => 'cred_stale_a',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
        ]);
        WebauthnCredential::create([
            'user_id' => $userB->id,
            'credential_id' => 'cred_stale_b',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
        ]);

        // User A was looked up earlier in this session
        session(['webauthn_login_user_id' => $userA->id]);

        $this->mock(WebauthnService::class, function ($mock) {
            $mock->shouldReceive('verifyAssertion')
                ->andReturnUsing(function ($user, $assertion, $credential) {
                    return $credential;
                });
        });

        // Bob clicks "Sign in with Biometrics" with empty identifier (discoverable mode)
        $response = $this->postJson(route('webauthn.login'), [
            'identifier' => '',
            'credential_id' => 'cred_stale_b',
            'assertion' => [
                'id' => 'cred_stale_b',
                'type' => 'public-key',
                'response' => ['signature' => 'sig_b'],
            ],
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertTrue(Auth::check());
        $this->assertEquals($userB->id, Auth::id());
        $this->assertNull(session('webauthn_login_user_id'));
    }

    public function test_1_tap_account_switching_when_other_user_biometric_scanned()
    {
        $userA = User::factory()->create([
            'name' => 'Alice Switch',
            'student_number' => 'STU-SW-A',
            'is_active' => true,
        ]);
        $userB = User::factory()->create([
            'name' => 'Bob Switch',
            'student_number' => 'STU-SW-B',
            'is_active' => true,
        ]);

        WebauthnCredential::create([
            'user_id' => $userA->id,
            'credential_id' => 'cred_sw_a',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
        ]);
        WebauthnCredential::create([
            'user_id' => $userB->id,
            'credential_id' => 'cred_sw_b',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
        ]);

        $this->mock(WebauthnService::class, function ($mock) {
            $mock->shouldReceive('verifyAssertion')
                ->andReturnUsing(function ($user, $assertion, $credential) {
                    return $credential;
                });
        });

        // 1. First attempt: Alice's identifier was entered, but Bob's biometric was presented
        $resMismatch = $this->postJson(route('webauthn.login'), [
            'identifier' => 'STU-SW-A',
            'credential_id' => 'cred_sw_b',
            'assertion' => [
                'id' => 'cred_sw_b',
                'type' => 'public-key',
                'response' => ['signature' => 'sig_b'],
            ],
        ]);

        $resMismatch->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'CREDENTIAL_MISMATCH',
                'can_switch_user' => true,
            ]);

        // 2. User confirms 1-tap switch to detected Bob
        $resSwitch = $this->postJson(route('webauthn.login'), [
            'identifier' => 'STU-SW-B',
            'credential_id' => 'cred_sw_b',
            'switch_user' => true,
            'assertion' => [
                'id' => 'cred_sw_b',
                'type' => 'public-key',
                'response' => ['signature' => 'sig_b'],
            ],
        ]);

        $resSwitch->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'id' => $userB->id,
                    'identifier' => 'STU-SW-B',
                ],
            ]);
        $this->assertTrue(Auth::check());
        $this->assertEquals($userB->id, Auth::id());
    }

    public function test_multi_user_shared_device_disambiguation_via_user_handle()
    {
        $userA = User::factory()->create([
            'name' => 'Alice Shared',
            'student_number' => 'STU-SH-A',
            'is_active' => true,
        ]);
        $userB = User::factory()->create([
            'name' => 'Bob Shared',
            'student_number' => 'STU-SH-B',
            'is_active' => true,
        ]);

        $sharedCredId = 'cred_shared_platform_authenticator';

        // Both users have a credential with the same credential_id on the shared device
        WebauthnCredential::create([
            'user_id' => $userA->id,
            'credential_id' => $sharedCredId,
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
        ]);
        WebauthnCredential::create([
            'user_id' => $userB->id,
            'credential_id' => $sharedCredId,
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
        ]);

        $this->mock(WebauthnService::class, function ($mock) {
            $mock->shouldReceive('verifyAssertion')
                ->andReturnUsing(function ($user, $assertion, $credential) {
                    return $credential;
                });
        });

        // Bob logs in in discoverable mode, sending his hashed userHandle
        $bobUserHandle = rtrim(strtr(base64_encode(hash('sha256', (string) $userB->id, true)), '+/', '-_'), '=');

        $res = $this->postJson(route('webauthn.login'), [
            'credential_id' => $sharedCredId,
            'assertion' => [
                'id' => $sharedCredId,
                'type' => 'public-key',
                'response' => [
                    'signature' => 'sig_b',
                    'userHandle' => $bobUserHandle,
                ],
            ],
        ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'id' => $userB->id,
                    'identifier' => 'STU-SH-B',
                ],
            ]);
        $this->assertTrue(Auth::check());
        $this->assertEquals($userB->id, Auth::id());
    }
}
