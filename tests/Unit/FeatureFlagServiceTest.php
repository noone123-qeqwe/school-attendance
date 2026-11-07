<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Models\User;
use App\Services\FeatureFlagService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_flags_use_the_documented_defaults(): void
    {
        $flags = app(FeatureFlagService::class)->all();

        $this->assertSame(FeatureFlagService::DEFAULTS, $flags);
    }

    public function test_admin_changes_are_saved_and_cache_is_refreshed(): void
    {
        $service = app(FeatureFlagService::class);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertFalse($service->enabled('feature.sms_notifications'));
        $service->set('feature.sms_notifications', true, $admin);

        $this->assertTrue($service->enabled('feature.sms_notifications'));
        $this->assertDatabaseHas('settings', ['key' => 'feature.sms_notifications', 'value' => '1']);

        Setting::where('key', 'feature.sms_notifications')->firstOrFail()->update(['value' => '0']);

        $this->assertFalse($service->enabled('feature.sms_notifications'));
    }

    public function test_non_admin_cannot_change_flags(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->expectException(AuthorizationException::class);
        app(FeatureFlagService::class)->set('feature.sms_notifications', true, $student);
    }
}
