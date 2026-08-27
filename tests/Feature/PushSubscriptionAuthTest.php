<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\PushSubscription;
use Tests\TestCase;

class PushSubscriptionAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_subscribe_or_unsubscribe()
    {
        $response = $this->postJson('/push/subscribe', [
            'endpoint' => 'https://example.com/push',
            'keys' => ['p256dh' => 'key', 'auth' => 'auth']
        ]);
        $response->assertStatus(401);

        $responseUnsub = $this->postJson('/push/unsubscribe', [
            'endpoint' => 'https://example.com/push',
        ]);
        $responseUnsub->assertStatus(401);
    }

    public function test_user_can_subscribe_but_cannot_overwrite_others()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $endpoint = 'https://example.com/push/user1';

        $this->actingAs($user1)->postJson('/push/subscribe', [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'key', 'auth' => 'auth']
        ])->assertStatus(200);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user1->id,
            'endpoint' => $endpoint
        ]);

        $this->actingAs($user2)->postJson('/push/subscribe', [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'key2', 'auth' => 'auth2']
        ])->assertStatus(403);
    }

    public function test_user_cannot_unsubscribe_others()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $endpoint = 'https://example.com/push/shared';
        
        $sub = PushSubscription::create([
            'user_id' => $user1->id,
            'endpoint' => $endpoint,
            'endpoint_hash' => hash('sha256', $endpoint),
            'public_key' => 'key',
            'auth_token' => 'auth'
        ]);

        $this->actingAs($user2)->postJson('/push/unsubscribe', [
            'endpoint' => $endpoint
        ])->assertStatus(200); // the response is 200 but nothing should be deleted

        $this->assertDatabaseHas('push_subscriptions', ['id' => $sub->id]);

        $this->actingAs($user1)->postJson('/push/unsubscribe', [
            'endpoint' => $endpoint
        ])->assertStatus(200);

        $this->assertDatabaseMissing('push_subscriptions', ['id' => $sub->id]);
    }
}
