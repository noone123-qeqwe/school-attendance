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

    public function test_face_registration_fails_when_no_face_detected_or_empty_descriptor()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('webauthn.register'), [
            'credential_id' => 'face_no_detect_123',
            'biometric_type' => 'face',
            'device_name' => 'Device Face',
            'face_descriptor' => '',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'No face detected. Please position your face in front of the camera.',
            ]);
    }

    public function test_face_registration_fails_when_descriptor_contains_fallback_or_unusable()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('webauthn.register'), [
            'credential_id' => 'face_fallback_456',
            'biometric_type' => 'face',
            'device_name' => 'Device Face',
            'face_descriptor' => 'face_desc_fallback_xyz',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'No face detected. Please position your face in front of the camera.',
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

    public function test_settings_page_renders_face_recognition_and_detection_prompts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings'));

        $response->assertOk();
        $response->assertSee('id="faceCameraVideo"', false);
        $response->assertSee('detectAndAnalyzeFaceFrame', false);
        $response->assertSee('No face detected. Please position your face in front of the camera.', false);
        $response->assertSee('Multiple faces detected. Please ensure only the intended person is visible.', false);
        $response->assertSee('Multiple faces detected. Please ensure only one person is visible.', false);
        $response->assertSee('Camera image is blurry. Please hold steady in front of the camera.', false);
        $response->assertSee('Move closer', false);
        $response->assertSee('Move farther away', false);
        $response->assertSee('Center your face', false);
        $response->assertSee('Improve lighting', false);
    }

    public function test_face_registration_fails_when_confidence_score_below_security_threshold()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('webauthn.register'), [
            'credential_id' => 'face_low_conf_123',
            'biometric_type' => 'face',
            'device_name' => 'Device Face',
            'face_descriptor' => 'face_desc_58_110_108_130_105_xyz',
            'public_key' => 'pub_face_key_test',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Face recognition confidence does not meet the required security threshold. Please position your face clearly in good lighting and try again.',
            ]);
    }

    public function test_face_registration_succeeds_when_confidence_score_meets_or_exceeds_threshold()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('webauthn.register'), [
            'credential_id' => 'face_high_conf_789',
            'biometric_type' => 'face',
            'device_name' => 'Secure Face Sensor',
            'face_descriptor' => 'face_desc_84_112_110_134_106_xyz',
            'public_key' => 'pub_face_key_high',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Face Recognition Registered Successfully ✓',
                'biometric_type' => 'face',
            ]);

        $this->assertDatabaseHas('webauthn_credentials', [
            'user_id' => $user->id,
            'credential_id' => 'face_high_conf_789',
            'biometric_type' => 'face',
        ]);
    }

    public function test_face_registration_rejects_various_invalid_status_tokens()
    {
        $user = User::factory()->create();

        $invalidDescriptors = [
            'face_desc_blurry_frame_token',
            'face_desc_multiple_faces_detected',
            'face_desc_too_far_away',
            'face_desc_too_close_warning',
            'face_desc_off_center_warning',
            'face_desc_partial_face_edge',
        ];

        foreach ($invalidDescriptors as $desc) {
            $response = $this->actingAs($user)->postJson(route('webauthn.register'), [
                'credential_id' => 'face_invalid_' . md5($desc),
                'biometric_type' => 'face',
                'device_name' => 'Device Face',
                'face_descriptor' => $desc,
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'No face detected. Please position your face in front of the camera.',
                ]);
        }
    }
}

