<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\WebauthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class BiometricRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_fingerprint_biometric_and_stores_type()
    {
        $user = User::factory()->create([
            'student_number' => 'STU2001',
            'password' => Hash::make('password123'),
        ]);

        $mockWebauthn = Mockery::mock(WebauthnService::class);
        $mockWebauthn->shouldReceive('storeCredential')
            ->once()
            ->andReturnUsing(function ($u, $c) use ($user) {
                return WebauthnCredential::create([
                    'user_id' => $user->id,
                    'credential_id' => 'cred_fp_123',
                    'public_key' => 'pubkey_sample_fp',
                    'device_name' => 'Fingerprint Sensor',
                    'biometric_type' => 'fingerprint',
                ]);
            });

        $this->app->instance(WebauthnService::class, $mockWebauthn);

        $response = $this->actingAs($user)->postJson(route('webauthn.register'), [
            'credential_id' => 'cred_fp_123',
            'biometric_type' => 'fingerprint',
            'credential' => [
                'id' => 'cred_fp_123',
                'type' => 'public-key',
                'response' => [
                    'clientDataJSON' => base64_encode('{}'),
                    'attestationObject' => base64_encode('{}'),
                ],
            ],
            'device_name' => 'Laptop Fingerprint Sensor',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'biometric_type' => 'fingerprint',
            ]);

        $this->assertDatabaseHas('webauthn_credentials', [
            'user_id' => $user->id,
            'credential_id' => 'cred_fp_123',
            'biometric_type' => 'fingerprint',
        ]);
    }

    public function test_user_can_register_face_biometric_and_stores_type()
    {
        $user = User::factory()->create([
            'student_number' => 'STU2002',
            'password' => Hash::make('password123'),
        ]);

        $mockWebauthn = Mockery::mock(WebauthnService::class);
        $mockWebauthn->shouldReceive('storeCredential')
            ->once()
            ->andReturnUsing(function ($u, $c) use ($user) {
                return WebauthnCredential::create([
                    'user_id' => $user->id,
                    'credential_id' => 'cred_face_456',
                    'public_key' => 'pubkey_sample_face',
                    'device_name' => 'Face ID / Facial Recognition',
                    'biometric_type' => 'face',
                ]);
            });

        $this->app->instance(WebauthnService::class, $mockWebauthn);

        $response = $this->actingAs($user)->postJson(route('webauthn.register'), [
            'credential_id' => 'cred_face_456',
            'biometric_type' => 'face',
            'credential' => [
                'id' => 'cred_face_456',
                'type' => 'public-key',
                'response' => [
                    'clientDataJSON' => base64_encode('{}'),
                    'attestationObject' => base64_encode('{}'),
                ],
            ],
            'device_name' => 'Windows Hello Face Camera',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'biometric_type' => 'face',
            ]);

        $this->assertDatabaseHas('webauthn_credentials', [
            'user_id' => $user->id,
            'credential_id' => 'cred_face_456',
            'biometric_type' => 'face',
        ]);
    }

    public function test_prevents_duplicate_biometric_registration()
    {
        $user = User::factory()->create();

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'cred_existing_999',
            'public_key' => 'sample_key',
            'device_name' => 'Existing Sensor',
            'biometric_type' => 'fingerprint',
        ]);

        $response = $this->actingAs($user)->postJson(route('webauthn.register'), [
            'credential_id' => 'cred_existing_999',
            'biometric_type' => 'fingerprint',
            'credential' => [
                'id' => 'cred_existing_999',
                'type' => 'public-key',
                'response' => [
                    'clientDataJSON' => 'dummy',
                ],
            ],
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_devices_list_returns_biometric_type()
    {
        $user = User::factory()->create();

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'cred_list_fp',
            'public_key' => 'sample_key_1',
            'device_name' => 'Fingerprint Reader',
            'biometric_type' => 'fingerprint',
        ]);

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'cred_list_face',
            'public_key' => 'sample_key_2',
            'device_name' => 'Face ID Cam',
            'biometric_type' => 'face',
        ]);

        $response = $this->actingAs($user)->getJson(route('webauthn.devices'));

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment([
                'credential_id' => 'cred_list_fp',
                'biometric_type' => 'fingerprint',
            ])
            ->assertJsonFragment([
                'credential_id' => 'cred_list_face',
                'biometric_type' => 'face',
            ]);
    }

    public function test_remove_device_deletes_credential()
    {
        $user = User::factory()->create();

        $cred = WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'cred_to_delete',
            'public_key' => 'sample_key',
            'device_name' => 'Test Device',
            'biometric_type' => 'fingerprint',
        ]);

        $response = $this->actingAs($user)->deleteJson(route('webauthn.remove'), [
            'credential_id' => 'cred_to_delete',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('webauthn_credentials', [
            'credential_id' => 'cred_to_delete',
        ]);
    }
}
