<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_vapid_public_key(): void
    {
        $response = $this->getJson(route('push.public_key'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'publicKey',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotEmpty($response->json('publicKey'));
    }

    public function test_can_subscribe_device_to_web_push(): void
    {
        $user = User::factory()->create();

        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-device-endpoint-' . uniqid();
        $payload = [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM=',
                'auth' => 'tBHItJI5svbpez7KI4CCXg==',
            ],
            'device_name' => 'Chrome Test Browser',
        ];

        $response = $this->actingAs($user)->postJson(route('push.subscribe'), $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => $endpoint,
            'device_name' => 'Chrome Test Browser',
        ]);
    }

    public function test_can_unsubscribe_device(): void
    {
        $user = User::factory()->create();
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-device-to-remove-' . uniqid();
        
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => $endpoint,
            'public_key' => 'test-key',
            'auth_token' => 'test-auth',
            'device_name' => 'Test Device',
        ]);

        $response = $this->actingAs($user)->postJson(route('push.unsubscribe'), [
            'endpoint' => $endpoint,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $endpoint,
        ]);
    }

    public function test_web_push_service_initialization(): void
    {
        $service = app(WebPushService::class);
        $this->assertInstanceOf(WebPushService::class, $service);
    }
}
