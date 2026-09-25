<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\WebauthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SecureFaceLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_face_sign_in_options_only_include_secure_face_credentials(): void
    {
        $user = User::factory()->create(['student_number' => 'FACE-100', 'is_active' => true]);
        $face = $this->credential($user, 'face-secure', 'face', '-----BEGIN PUBLIC KEY-----\nface\n-----END PUBLIC KEY-----');
        $this->credential($user, 'fingerprint-secure', 'fingerprint', '-----BEGIN PUBLIC KEY-----\nfingerprint\n-----END PUBLIC KEY-----');
        $this->credential($user, 'face-legacy', 'face', 'face_desc_90_fake');

        $response = $this->postJson(route('webauthn.login.options'), [
            'identifier' => 'FACE-100',
            'biometric_method' => 'face',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSame([$face->credential_id], array_column($response->json('allowCredentials'), 'id'));
        $this->assertContains('face', $response->json('available_methods'));
    }

    public function test_face_sign_in_without_identifier_uses_only_saved_face_credentials(): void
    {
        $user = User::factory()->create(['student_number' => 'FACE-104', 'is_active' => true]);
        $face = $this->credential($user, 'saved-face', 'face', '-----BEGIN PUBLIC KEY-----\nface\n-----END PUBLIC KEY-----');
        $this->credential($user, 'saved-fingerprint', 'fingerprint', '-----BEGIN PUBLIC KEY-----\nfingerprint\n-----END PUBLIC KEY-----');

        $this->postJson(route('webauthn.login.options'), [
            'biometric_method' => 'face',
        ])->assertStatus(422)->assertJson(['code' => 'IDENTIFIER_REQUIRED']);

        $response = $this->postJson(route('webauthn.login.options'), [
            'biometric_method' => 'face',
            'saved_identifiers' => ['FACE-104'],
        ]);

        $response->assertOk();
        $this->assertSame([$face->credential_id], array_column($response->json('allowCredentials'), 'id'));
    }

    public function test_secure_face_assertion_uses_normal_webauthn_verification(): void
    {
        $user = User::factory()->create(['student_number' => 'FACE-101', 'is_active' => true]);
        $face = $this->credential($user, 'face-assertion', 'face', '-----BEGIN PUBLIC KEY-----\nface\n-----END PUBLIC KEY-----');

        $mock = Mockery::mock(WebauthnService::class);
        $mock->shouldReceive('verifyAssertion')->once()->andReturn($face);
        $this->app->instance(WebauthnService::class, $mock);

        $response = $this->postJson(route('webauthn.login'), [
            'biometric_method' => 'face',
            'credential_id' => $face->credential_id,
            'identifier' => 'FACE-101',
            'assertion' => [
                'id' => $face->credential_id,
                'response' => [
                    'clientDataJSON' => 'signed-client-data',
                    'authenticatorData' => 'signed-authenticator-data',
                    'signature' => 'signed-signature',
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_face_method_cannot_use_a_fingerprint_credential(): void
    {
        $user = User::factory()->create(['student_number' => 'FACE-102', 'is_active' => true]);
        $fingerprint = $this->credential($user, 'fingerprint-only', 'fingerprint', '-----BEGIN PUBLIC KEY-----\nfingerprint\n-----END PUBLIC KEY-----');

        $response = $this->postJson(route('webauthn.login'), [
            'biometric_method' => 'face',
            'credential_id' => $fingerprint->credential_id,
            'identifier' => 'FACE-102',
            'assertion' => ['id' => $fingerprint->credential_id, 'response' => []],
        ]);

        $response->assertStatus(422)->assertJson(['code' => 'FACE_CREDENTIAL_REQUIRED']);
        $this->assertGuest();
    }

    public function test_legacy_camera_face_records_require_secure_enrollment(): void
    {
        $user = User::factory()->create(['student_number' => 'FACE-103', 'is_active' => true]);
        $this->credential($user, 'legacy-face', 'face', 'face_desc_90_fake');

        $this->postJson(route('webauthn.login.options'), [
            'identifier' => 'FACE-103',
            'biometric_method' => 'face',
        ])->assertStatus(404)->assertJson(['code' => 'FACE_ENROLLMENT_REQUIRED']);

        $methods = $this->postJson(route('webauthn.available.methods'), [
            'identifier' => 'FACE-103',
        ]);
        $methods->assertOk();
        $this->assertNotContains('face', $methods->json('available_methods'));

        $this->postJson(route('webauthn.login'), [
            'biometric_method' => 'face',
            'credential_id' => 'legacy-face',
            'face_descriptor' => 'face_desc_90_fake',
        ])->assertStatus(422)->assertJson(['code' => 'INSECURE_FACE_FLOW_DISABLED']);

        $this->assertGuest();
    }

    public function test_face_registration_and_login_views_use_device_verification(): void
    {
        $settings = file_get_contents(resource_path('views/settings.blade.php'));
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));

        $this->assertStringContainsString("biometric_type: registrationType", $settings);
        $this->assertStringContainsString("authenticatorAttachment: 'platform'", $settings);
        $this->assertStringContainsString("biometric_method: selectedMethod.id", $login);
        $this->assertStringContainsString("var onDeviceMethod = selectedMethod.id === 'fingerprint' || selectedMethod.id === 'face'", $login);
        $this->assertStringContainsString('Confirm with Face ID or Windows Hello', $login);
    }

    private function credential(User $user, string $id, string $type, string $publicKey): WebauthnCredential
    {
        return WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => $id,
            'public_key' => $publicKey,
            'sign_count' => 0,
            'device_name' => 'Test device',
            'biometric_type' => $type,
        ]);
    }
}
