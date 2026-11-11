<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\VersionService;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuietUpdatePolicyTest extends TestCase
{
    use RefreshDatabase;

    private array $originalFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['version.json', 'package.json', 'public/manifest.json', 'public/sw.js', 'apps/android_app/app/build.gradle', '../android_app/app/build.gradle'] as $path) {
            if (is_file(base_path($path))) $this->originalFiles[$path] = file_get_contents(base_path($path));
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalFiles as $path => $content) file_put_contents(base_path($path), $content);
        parent::tearDown();
    }

    public function test_missing_invalid_or_stale_policy_defaults_to_quiet(): void
    {
        foreach ([null, ['version' => '0.0.1', 'mode' => 'critical'], ['mode' => 'critical'], ['version' => $this->version(), 'mode' => 'unknown']] as $policy) {
            $this->writePolicy($policy);
            $this->getJson('/pwa/version')->assertOk()->assertJsonPath('update.mode', 'silent');
        }
    }

    public function test_both_public_version_endpoints_report_the_same_release_policy(): void
    {
        $this->writePolicy(['version' => $this->version(), 'mode' => 'announcement']);
        foreach (['/pwa/version', '/api/version'] as $url) {
            $this->getJson($url)->assertOk()
                ->assertJsonPath('update.mode', 'announcement')
                ->assertJsonPath('update.version', $this->version());
        }
    }

    public function test_release_publishing_is_quiet_by_default_and_sends_no_push(): void
    {
        $this->mock(WebPushService::class)->shouldNotReceive('broadcastAnnouncement');
        $this->actingAs($this->superAdmin())->postJson(route('admin.system-update.app-bump'))
            ->assertOk()->assertJsonPath('update.mode', 'silent');
        $this->getJson('/pwa/version')->assertOk()->assertJsonPath('update.mode', 'silent');
    }

    public function test_announcements_and_critical_updates_require_an_explicit_publishing_choice(): void
    {
        $this->mock(WebPushService::class)->shouldReceive('broadcastAnnouncement')->twice()->andReturn(0);
        $this->actingAs($this->superAdmin());
        foreach (['announcement', 'critical'] as $mode) {
            $response = $this->postJson(route('admin.system-update.app-bump'), ['update_mode' => $mode]);
            $response->assertOk()->assertJsonPath('update.mode', $mode);
            $this->getJson('/pwa/version')->assertOk()->assertJsonPath('update.mode', $mode);
            $this->assertSame($this->version(), $response->json('update.version'));
        }
    }

    public function test_a_later_quiet_release_preserves_the_critical_minimum_safe_version(): void
    {
        $service = app(VersionService::class);
        $critical = $service->createRelease('patch', null, true, 'critical');
        $quiet = $service->createRelease();
        $this->assertSame('silent', $quiet['update']['mode']);
        $this->assertSame($critical['version'], $quiet['update']['minimum_supported_version']);
    }

    public function test_cache_refresh_stays_quiet_and_does_not_create_user_notifications(): void
    {
        $this->mock(WebPushService::class)->shouldNotReceive('broadcastAnnouncement');
        $this->actingAs($this->superAdmin())->postJson(route('admin.system-update.pwa-bump'))
            ->assertOk()->assertJsonPath('pushed_count', 0);
        $this->assertDatabaseMissing('notifications', ['type' => 'system_update']);
    }

    public function test_invalid_update_mode_is_rejected_before_release_metadata_changes(): void
    {
        $before = file_get_contents(base_path('version.json'));
        $this->actingAs($this->superAdmin())->postJson(route('admin.system-update.app-bump'), ['update_mode' => 'surprise'])
            ->assertUnprocessable()->assertJsonValidationErrors('update_mode');
        $this->assertSame($before, file_get_contents(base_path('version.json')));
    }

    public function test_students_cannot_publish_critical_updates(): void
    {
        $before = file_get_contents(base_path('version.json'));
        $this->actingAs(User::factory()->create(['role' => 'student']))
            ->postJson(route('admin.system-update.app-bump'), ['update_mode' => 'critical'])
            ->assertRedirect(route('home'))->assertSessionHas('error');
        $this->assertSame($before, file_get_contents(base_path('version.json')));
    }

    public function test_admin_release_controls_render_with_quiet_selected(): void
    {
        $content = $this->actingAs($this->superAdmin())->get(route('admin.system-update.index'))->assertOk()->getContent();
        $this->assertStringContainsString('id="releaseUpdateMode"', $content);
        $this->assertStringContainsString('value="silent" selected', $content);
        $this->assertStringContainsString('value="announcement"', $content);
        $this->assertStringContainsString('value="critical"', $content);
    }

    private function version(): string
    {
        return json_decode(file_get_contents(base_path('version.json')), true)['version'];
    }

    private function writePolicy(?array $policy): void
    {
        $data = json_decode(file_get_contents(base_path('version.json')), true);
        $data['update'] = $policy;
        file_put_contents(base_path('version.json'), json_encode($data));
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin', 'is_active' => true]);
    }
}
