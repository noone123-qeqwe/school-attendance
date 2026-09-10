<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FaceRecognitionRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_face_recognition_directly_without_device_verification_attestation()
    {
        $user = User::factory()->create([
            'student_number' => 'STU8801',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->postJson(route('webauthn.register'), [
            'credential_id' => 'face_direct_123456',
            'biometric_type' => 'face',
            'device_name' => 'Mobile Device (Face Recognition)',
            'face_descriptor' => 'face_desc_sample_vector_hash_xyz',
            'public_key' => 'pub_face_sample_key',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Face Recognition Registered Successfully ✓',
                'biometric_type' => 'face',
            ]);

        $this->assertDatabaseHas('webauthn_credentials', [
            'user_id' => $user->id,
            'credential_id' => 'face_direct_123456',
            'biometric_type' => 'face',
            'device_name' => 'Mobile Device (Face Recognition)',
        ]);
    }

    public function test_face_registration_requires_credential_id()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('webauthn.register'), [
            'biometric_type' => 'face',
            'device_name' => 'Desktop (Face Recognition)',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Credential ID is required.',
            ]);
    }

    public function test_face_registration_prevents_duplicate()
    {
        $user = User::factory()->create();

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'face_existing_777',
            'public_key' => 'pub_sample',
            'device_name' => 'Existing Face',
            'biometric_type' => 'face',
        ]);

        $response = $this->actingAs($user)->postJson(route('webauthn.register'), [
            'credential_id' => 'face_existing_777',
            'biometric_type' => 'face',
            'device_name' => 'Existing Face',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_face_credential_appears_in_devices_list()
    {
        $user = User::factory()->create();

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'face_listed_888',
            'public_key' => 'pub_sample',
            'device_name' => 'Phone (Face Recognition)',
            'biometric_type' => 'face',
        ]);

        $response = $this->actingAs($user)->getJson(route('webauthn.devices'));

        $response->assertOk()
            ->assertJsonFragment([
                'credential_id' => 'face_listed_888',
                'biometric_type' => 'face',
                'device_name' => 'Phone (Face Recognition)',
            ]);
    }
}
