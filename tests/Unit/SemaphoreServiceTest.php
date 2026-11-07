<?php

namespace Tests\Unit;

use App\Services\SemaphoreService;
use App\Services\FeatureFlagService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SemaphoreServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('application_feature_flags', array_merge(FeatureFlagService::DEFAULTS, [
            'feature.sms_notifications' => true,
        ]), now()->addMinutes(5));
    }

    public function test_disabled_sms_does_not_contact_provider(): void
    {
        Http::fake();

        $sent = (new SemaphoreService('fake-unit-key-123', 'School', false))->send('09171234567', 'Attendance alert');

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_missing_api_key_does_not_contact_provider(): void
    {
        Http::fake();

        $sent = (new SemaphoreService('', 'School', true))->send('09171234567', 'Attendance alert');

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_invalid_phone_number_does_not_contact_provider(): void
    {
        Http::fake();

        $sent = (new SemaphoreService('fake-unit-key-123', 'School', true))->send('12345', 'Attendance alert');

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_only_provider_confirmed_network_delivery_returns_true(): void
    {
        Http::fake([
            'api.semaphore.co/api/v4/messages' => Http::response([
                ['message_id' => 10, 'status' => 'Sent'],
            ], 200),
        ]);

        $sent = (new SemaphoreService('fake-unit-key-123', 'School', true))->send('+63 (917) 123-4567', 'Attendance alert');

        $this->assertTrue($sent);
        Http::assertSent(fn (Request $request): bool => $request['number'] === '639171234567');
    }

    public function test_pending_response_is_not_reported_as_delivery(): void
    {
        Http::fake([
            'api.semaphore.co/api/v4/messages' => Http::response([
                ['message_id' => 11, 'status' => 'Pending'],
            ], 200),
        ]);

        $sent = (new SemaphoreService('fake-unit-key-123', 'School', true))->send('09171234567', 'Attendance alert');

        $this->assertFalse($sent);
    }

    public function test_rate_limit_and_insufficient_credit_responses_fail_safely(): void
    {
        Http::fakeSequence()
            ->push(['message' => 'rate limited'], 429)
            ->push(['message' => 'Insufficient credits'], 402);

        $service = new SemaphoreService('fake-unit-key-123', 'School', true);
        $this->assertFalse($service->send('09171234567', 'Attendance alert'));
        $this->assertFalse($service->send('09171234567', 'Attendance alert'));
        Http::assertSentCount(2);
    }
}
