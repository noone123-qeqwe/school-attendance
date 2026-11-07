<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\PolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_metadata_uses_safe_defaults_and_custom_updates_are_sanitized(): void
    {
        $service = app(PolicyService::class);
        $default = $service->getPrivacyPolicy();
        $this->assertSame(PolicyService::DEFAULT_PRIVACY_VERSION, $default['version']);
        $this->assertSame('September 1, 2024', $default['effective_date']);

        $service->updatePrivacyPolicy(
            '<p onclick="steal()">Updated notice</p><script>alert(1)</script><a href="javascript:alert(1)">unsafe</a>',
            '2.0',
            '2026-11-01'
        );
        $updated = $service->getPrivacyPolicy();

        $this->assertSame('2.0', $updated['version']);
        $this->assertSame('November 1, 2026', $updated['effective_date']);
        $this->assertStringContainsString('Updated notice', $updated['content']);
        $this->assertStringNotContainsString('onclick', $updated['content']);
        $this->assertStringNotContainsString('<script', $updated['content']);
        $this->assertStringNotContainsString('javascript:', $updated['content']);
        $this->assertDatabaseHas('settings', ['key' => 'privacy_policy_version', 'value' => '2.0']);
    }
}
