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
        $response->assertSee('id="faceFlashToggleBtn"', false);
        $response->assertSee('id="faceScreenFlashOverlay"', false);
        $response->assertSee('toggleFaceFlash', false);
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

    public function test_login_page_renders_face_scanner_and_camera_hud()
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('id="bioModalFaceScannerWrap"', false);
        $response->assertSee('id="bioLoginFaceVideo"', false);
        $response->assertSee('id="bioLoginLaserBar"', false);
        $response->assertSee('id="bioLoginFlashToggleBtn"', false);
        $response->assertSee('id="bioLoginScreenFlashOverlay"', false);
        $response->assertSee('toggleBioLoginFlash', false);
        $response->assertSee('startFaceRecognitionLogin', false);
    }

    public function test_login_options_returns_face_method_and_credential_id_for_registered_face_user()
    {
        $user = User::factory()->create([
            'student_number' => 'STU9901',
            'is_active' => true,
        ]);

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'face_registered_9901',
            'public_key' => 'pub_sample_face',
            'device_name' => 'Device Face',
            'biometric_type' => 'face',
        ]);

        $response = $this->postJson(route('webauthn.login.options'), [
            'identifier' => 'STU9901',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'biometric_type' => 'face',
                'face_credential_id' => 'face_registered_9901',
                'available_methods' => ['face'],
            ]);
    }

    public function test_user_can_authenticate_via_camera_face_recognition()
    {
        $user = User::factory()->create([
            'student_number' => 'STU9902',
            'is_active' => true,
            'role' => 'student',
        ]);

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'face_registered_9902',
            'public_key' => 'pub_sample_face',
            'device_name' => 'Webcam Face',
            'biometric_type' => 'face',
        ]);

        $response = $this->postJson(route('webauthn.login'), [
            'biometric_method' => 'face',
            'credential_id' => 'face_registered_9902',
            'face_descriptor' => 'face_desc_88_115_112_130_105_' . time(),
            'identifier' => 'STU9902',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Face recognized successfully! Redirecting...',
            ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_face_recognition_login_rejects_sub_threshold_confidence()
    {
        $user = User::factory()->create([
            'student_number' => 'STU9903',
            'is_active' => true,
        ]);

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'face_registered_9903',
            'public_key' => 'pub_sample_face',
            'device_name' => 'Webcam Face',
            'biometric_type' => 'face',
        ]);

        $response = $this->postJson(route('webauthn.login'), [
            'biometric_method' => 'face',
            'credential_id' => 'face_registered_9903',
            'face_descriptor' => 'face_desc_55_115_112_130_105_' . time(),
            'identifier' => 'STU9903',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Face recognition confidence does not meet the required security threshold. Please position your face clearly in good lighting and try again.',
            ]);

        $this->assertGuest();
    }

    public function test_face_recognition_login_rejects_invalid_tokens()
    {
        $user = User::factory()->create([
            'student_number' => 'STU9904',
            'is_active' => true,
        ]);

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'face_registered_9904',
            'public_key' => 'pub_sample_face',
            'device_name' => 'Webcam Face',
            'biometric_type' => 'face',
        ]);

        $response = $this->postJson(route('webauthn.login'), [
            'biometric_method' => 'face',
            'credential_id' => 'face_registered_9904',
            'face_descriptor' => 'face_desc_blurry_frame_test',
            'identifier' => 'STU9904',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'No face detected. Please position your face in front of the camera.',
            ]);

        $this->assertGuest();
    }

    public function test_settings_page_renders_camera_flash_elements_and_handlers()
    {
        $user = User::factory()->create();

        $settingsResponse = $this->actingAs($user)->get(route('settings'));
        $settingsResponse->assertOk();
        $settingsResponse->assertSee('id="faceFlashToggleBtn"', false);
        $settingsResponse->assertSee('id="faceScreenFlashOverlay"', false);
        $settingsResponse->assertSee('toggleFaceFlash', false);
        $settingsResponse->assertSee('resetFaceFlash', false);
        $settingsResponse->assertSee('face-hud-flash-btn', false);
        $settingsResponse->assertSee('face-screen-flash-overlay', false);
    }

    public function test_login_page_renders_camera_flash_elements_and_handlers()
    {
        $loginResponse = $this->get(route('login'));
        $loginResponse->assertOk();
        $loginResponse->assertSee('id="bioLoginFlashToggleBtn"', false);
        $loginResponse->assertSee('id="bioLoginScreenFlashOverlay"', false);
        $loginResponse->assertSee('toggleBioLoginFlash', false);
        $loginResponse->assertSee('resetBioLoginFlash', false);
        $loginResponse->assertSee('triggerCaptureFlash', false);
        $loginResponse->assertSee('face-hud-flash-btn', false);
        $loginResponse->assertSee('face-screen-flash-overlay', false);
    }
}


