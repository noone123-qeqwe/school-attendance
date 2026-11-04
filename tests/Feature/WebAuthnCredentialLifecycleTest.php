<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\WebauthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthnCredentialLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EC_X = 'aqqH5uYPa1tz6no7gDU9Q2NqBBy7qpzcV7OaMBlgnlk';
    private const TEST_EC_Y = 'Y9rSY15zBK0oRTvSx9IA8D3KeJkLqQk_vL-8NgQGt-I';

    public function test_face_passkey_registration_stores_a_verified_device_public_key(): void
    {
        $user = User::factory()->create();
        [$payload, $challenge] = $this->registrationPayload($user);

        $response = $this->actingAs($user)
            ->withSession(['webauthn.register_challenge' => $challenge])
            ->postJson(route('webauthn.register'), $payload);

        $response->assertOk()->assertJsonPath('success', true)->assertJsonPath('biometric_type', 'face');
        $credential = WebauthnCredential::where('user_id', $user->id)->firstOrFail();
        $this->assertSame($payload['credential_id'], $credential->credential_id);
        $this->assertSame('face', $credential->biometric_type);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $credential->public_key);
        $this->assertFalse(str_contains($credential->public_key, 'face_desc_'));
    }

    public function test_face_passkey_registration_rejects_attestation_for_another_rp_id(): void
    {
        $user = User::factory()->create();
        [$payload, $challenge] = $this->registrationPayload($user, 'attacker.example');

        $response = $this->actingAs($user)
            ->withSession(['webauthn.register_challenge' => $challenge])
            ->postJson(route('webauthn.register'), $payload);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'bound to a different website'));
        $this->assertDatabaseMissing('webauthn_credentials', ['credential_id' => $payload['credential_id']]);
    }

    /** @return array{0: array<string, mixed>, 1: string} */
    private function registrationPayload(User $user, ?string $rpId = null): array
    {
        $service = app(WebauthnService::class);
        $options = $service->registrationOptions($user)['publicKey'];
        $challenge = $options['challenge'];
        $credentialId = random_bytes(32);
        $encodedCredentialId = $this->base64UrlEncode($credentialId);
        $cosePublicKey = $this->cborMap([
            [$this->cborInteger(1), $this->cborInteger(2)],
            [$this->cborInteger(3), $this->cborInteger(-7)],
            [$this->cborInteger(-1), $this->cborInteger(1)],
            [$this->cborInteger(-2), $this->cborBytes($this->base64UrlDecode(self::TEST_EC_X))],
            [$this->cborInteger(-3), $this->cborBytes($this->base64UrlDecode(self::TEST_EC_Y))],
        ]);
        $authenticatorData = hash('sha256', $rpId ?? $service->rpId(), true)
            . chr(0x45)
            . pack('N', 0)
            . str_repeat("\0", 16)
            . pack('n', strlen($credentialId))
            . $credentialId
            . $cosePublicKey;
        $clientDataJson = json_encode([
            'type' => 'webauthn.create',
            'challenge' => $challenge,
            'origin' => 'http://localhost',
        ], JSON_THROW_ON_ERROR);
        $attestationObject = $this->cborMap([
            [$this->cborText('fmt'), $this->cborText('none')],
            [$this->cborText('attStmt'), $this->cborMap([])],
            [$this->cborText('authData'), $this->cborBytes($authenticatorData)],
        ]);

        return [[
            'credential_id' => $encodedCredentialId,
            'biometric_type' => 'face',
            'device_name' => 'Test Face ID',
            'credential' => [
                'id' => $encodedCredentialId,
                'rawId' => $encodedCredentialId,
                'type' => 'public-key',
                'response' => [
                    'attestationObject' => $this->base64UrlEncode($attestationObject),
                    'clientDataJSON' => $this->base64UrlEncode($clientDataJson),
                ],
            ],
        ], $challenge];
    }

    private function cborMap(array $entries): string
    {
        $encoded = $this->cborLength(5, count($entries));
        foreach ($entries as [$key, $value]) {
            $encoded .= $key . $value;
        }

        return $encoded;
    }

    private function cborInteger(int $value): string
    {
        return $this->cborLength($value >= 0 ? 0 : 1, $value >= 0 ? $value : -1 - $value);
    }

    private function cborBytes(string $value): string
    {
        return $this->cborLength(2, strlen($value)) . $value;
    }

    private function cborText(string $value): string
    {
        return $this->cborLength(3, strlen($value)) . $value;
    }

    private function cborLength(int $major, int $length): string
    {
        $prefix = $major << 5;
        if ($length < 24) {
            return chr($prefix | $length);
        }
        if ($length <= 0xff) {
            return chr($prefix | 24) . chr($length);
        }
        if ($length <= 0xffff) {
            return chr($prefix | 25) . pack('n', $length);
        }

        return chr($prefix | 26) . pack('N', $length);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value . str_repeat('=', (4 - strlen($value) % 4) % 4), '-_', '+/'), true);
        $this->assertNotFalse($decoded);

        return $decoded;
    }
}
