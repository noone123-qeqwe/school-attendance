<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\WebauthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class WebAuthnSetupAndLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_login_options_returns_not_registered_code_when_user_has_no_biometrics()
    {
        $user = User::factory()->create([
            'student_number' => 'STU1001',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson(route('webauthn.login.options'), [
            'identifier' => 'STU1001',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'code' => 'NOT_REGISTERED',
                'user_exists' => true,
                'identifier' => 'STU1001',
            ]);
    }

    public function test_setup_options_fails_with_invalid_password()
    {
        $user = User::factory()->create([
            'student_number' => 'STU1002',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson(route('webauthn.setup.options'), [
            'identifier' => 'STU1002',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'code' => 'INVALID_PASSWORD',
            ]);
    }

    public function test_setup_options_succeeds_with_valid_password()
    {
        $user = User::factory()->create([
            'student_number' => 'STU1003',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson(route('webauthn.setup.options'), [
            'identifier' => 'STU1003',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user_id' => $user->id,
            ])
            ->assertJsonStructure([
                'challenge',
                'rp',
                'user',
                'pubKeyCredParams',
            ]);

        $this->assertEquals($user->id, session('webauthn.setup_user_id'));
    }

    public function test_setup_register_fails_without_valid_session_or_credentials()
    {
        $response = $this->postJson(route('webauthn.setup.register'), [
            'credential_id' => 'fake_cred_id',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_options_returns_challenge_when_user_has_registered_biometrics()
    {
        $user = User::factory()->create([
            'student_number' => 'STU1004',
            'password' => Hash::make('password123'),
        ]);

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'test_cred_id_1004',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
            'device_name' => 'Test Phone',
        ]);

        $response = $this->postJson(route('webauthn.login.options'), [
            'identifier' => 'STU1004',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'challenge',
                'allowCredentials',
            ]);
    }

    public function test_account_isolation_user_a_registered_does_not_enable_user_b()
    {
        $userA = User::factory()->create([
            'student_number' => 'STU_A',
            'password' => Hash::make('password123'),
        ]);

        $userB = User::factory()->create([
            'student_number' => 'STU_B',
            'password' => Hash::make('password123'),
        ]);

        WebauthnCredential::create([
            'user_id' => $userA->id,
            'credential_id' => 'test_cred_user_a',
            'public_key' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAx\n-----END PUBLIC KEY-----',
            'sign_count' => 0,
            'device_name' => 'User A Phone',
        ]);

        // User A options -> succeeds
        $resA = $this->postJson(route('webauthn.login.options'), [
            'identifier' => 'STU_A',
        ]);
        $resA->assertStatus(200)->assertJson(['success' => true]);

        // User B options -> returns NOT_REGISTERED
        $resB = $this->postJson(route('webauthn.login.options'), [
            'identifier' => 'STU_B',
        ]);
        $resB->assertStatus(404)->assertJson(['code' => 'NOT_REGISTERED']);
    }
}
