<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WebauthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PasskeySignInTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['fingerprint', 'face', false])]
    #[TestWith(['face', 'fingerprint', false])]
    #[TestWith(['device_lock', 'fingerprint', true])]
    public function test_registered_passkey_signs_in_independently_of_enrollment_label(
        string $method, string $storedLabel, bool $android
    ): void {
        $webauthn = app(WebauthnService::class);
        $webOrigin = 'https://attendance.example';
        config(['webauthn.rp_id' => 'attendance.example', 'webauthn.allowed_origins' => [$webOrigin]]);
        $origin = $android ? (config('webauthn.allowed_android_origins')[0] ?? null) : $webOrigin;
        $this->assertNotEmpty($origin);
        if ($android) {
            $links = json_decode(file_get_contents(public_path('.well-known/assetlinks.json')), true);
            $fingerprint = $links[0]['target']['sha256_cert_fingerprints'][0];
            $this->assertSame(
                'android:apk-key-hash:' . $webauthn->base64UrlEncode(hex2bin(str_replace(':', '', $fingerprint))),
                $origin
            );
        }

        $owner = User::create([
            'name' => 'Passkey Student',
            'student_number' => 'AUDIT-100',
            'course' => 'BSIT',
            'year_level' => '1',
            'email' => 'passkey-owner@example.test',
            'password' => Hash::make('SyntheticPasskeyPassword!'),
            'role' => 'student',
            'is_active' => true,
        ]);
        $opensslConfig = tempnam(sys_get_temp_dir(), 'attendance-passkey-');
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

        $id = $webauthn->base64UrlEncode(hex2bin('fbefff2a'));
        $owner->webauthnCredentials()->create([
            'credential_id' => base64_encode(hex2bin('fbefff2a')),
            'public_key' => openssl_pkey_get_details($key)['key'],
            'biometric_type' => $storedLabel,
        ]);
        $owner->webauthnCredentials()->create([
            'credential_id' => 'face_camera_profile',
            'public_key' => '[0.1,0.2]',
            'biometric_type' => 'face',
        ]);

        $options = $this->postJson(route('webauthn.login.options'), [
            'identifier' => $owner->email,
            'biometric_method' => $method,
        ])->assertOk()
            ->assertJsonPath('rpId', 'attendance.example')
            ->assertJsonPath('userVerification', 'required')
            ->assertJsonCount(1, 'allowCredentials')
            ->assertJsonPath('allowCredentials.0.id', $id)
            ->json();

        $clientData = json_encode([
            'type' => 'webauthn.get',
            'challenge' => $options['challenge'],
            'origin' => $origin,
        ], JSON_THROW_ON_ERROR);
        $authenticatorData = hash('sha256', $options['rpId'], true) . chr(0x05) . pack('N', 1);
        $this->assertTrue(openssl_sign(
            $authenticatorData . hash('sha256', $clientData, true),
            $signature,
            $key,
            OPENSSL_ALGO_SHA256
        ));

        $this->postJson(route('webauthn.login'), [
            'identifier' => $owner->email,
            'credential_id' => $id,
            'biometric_method' => $method,
            'assertion' => [
                'id' => $id,
                'response' => [
                    'clientDataJSON' => $webauthn->base64UrlEncode($clientData),
                    'authenticatorData' => $webauthn->base64UrlEncode($authenticatorData),
                    'signature' => $webauthn->base64UrlEncode($signature),
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.id', $owner->id);

        $this->assertAuthenticatedAs($owner);
    }
}
