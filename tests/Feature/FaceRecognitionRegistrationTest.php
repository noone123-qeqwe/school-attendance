<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaceRecognitionRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_camera_descriptor_is_rejected_as_face_registration()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('webauthn.register'), [
            'credential_id' => 'face_direct_123456',
            'biometric_type' => 'face',
            'device_name' => 'Mobile Device (Face Recognition)',
            'face_descriptor' => 'face_desc_99_client_claimed_confidence',
            'public_key' => 'pub_face_sample_key',
        ])->assertStatus(422)->assertJsonPath('code', 'SECURE_FACE_CREDENTIAL_REQUIRED');

        $this->assertDatabaseMissing('webauthn_credentials', ['credential_id' => 'face_direct_123456']);
    }

    public function test_face_registration_requires_a_device_attestation()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('webauthn.register'), [
            'credential_id' => 'face_without_attestation',
            'biometric_type' => 'face',
            'device_name' => 'Device Face',
        ])->assertStatus(422)->assertJsonPath('code', 'SECURE_FACE_CREDENTIAL_REQUIRED');
    }

    public function test_face_registration_requires_credential_id()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('webauthn.register'), [
            'biometric_type' => 'face',
            'device_name' => 'Desktop (Face Recognition)',
        ])->assertStatus(422)->assertJson([
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
                'is_secure_face_credential' => false,
            ]);
    }

    public function test_student_settings_separates_camera_registration_from_device_credentials()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('settings'))
            ->assertOk()
            ->assertSee('href="'.route('settings.biometrics.face').'"', false)
            ->assertSee('href="'.route('settings.biometrics.fingerprint').'"', false)
            ->assertDontSee('id="cameraFaceVideo"', false)
            ->assertDontSee('id="startBioBtn"', false);
        $response = $this->get(route('settings.biometrics.fingerprint'));
        $response->assertOk()->assertSee('Back to Biometrics')->assertSee('Registered devices');
        $response->assertSee("authenticatorAttachment: 'platform'", false);
        $response->assertSee('navigator.credentials.create', false);
        $response->assertSee('Fingerprint registration', false);
        $response->assertSee('Your device may offer a PIN', false);
        $response->assertDontSee('id="topHeader"', false);
        $response->assertDontSee('id="cameraFaceVideo"', false);
    }

    public function test_login_page_renders_device_passkey_face_sign_in()
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Confirm with Face ID or Windows Hello', false);
        $response->assertSee('navigator.credentials.get', false);
        $response->assertSee('a PIN may be offered.', false);
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
            'public_key' => '-----BEGIN PUBLIC KEY-----\nface\n-----END PUBLIC KEY-----',
            'device_name' => 'Device Face',
            'biometric_type' => 'face',
        ]);

        $response = $this->postJson(route('webauthn.login.options'), [
            'identifier' => 'STU9901',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'face_credential_id' => 'face_registered_9901',
            ]);
        $this->assertContains('face', $response->json('available_methods'));
    }

    public function test_camera_face_descriptor_cannot_authenticate_a_legacy_record()
    {
        $user = User::factory()->create([
            'student_number' => 'STU9902',
            'is_active' => true,
            'role' => 'student',
        ]);

        WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'face_registered_9902',
            'public_key' => 'face_desc_90_legacy_descriptor',
            'device_name' => 'Old Camera Profile',
            'biometric_type' => 'face',
        ]);

        $this->postJson(route('webauthn.login'), [
            'biometric_method' => 'face',
            'credential_id' => 'face_registered_9902',
            'face_descriptor' => 'face_desc_99_client_claimed_confidence',
            'identifier' => 'STU9902',
        ])->assertStatus(422)->assertJsonPath('code', 'CAMERA_FACE_LOGIN_DISABLED');

        $this->assertGuest();
    }

}
