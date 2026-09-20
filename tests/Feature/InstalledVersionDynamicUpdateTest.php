<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class InstalledVersionDynamicUpdateTest extends TestCase
{
    use RefreshDatabase;

    private string $originalVersionJson = '';

    protected function setUp(): void
    {
        parent::setUp();

        if (File::exists(base_path('version.json'))) {
            $this->originalVersionJson = File::get(base_path('version.json'));
        }
    }

    protected function tearDown(): void
    {
        if (!empty($this->originalVersionJson) && File::exists(base_path('version.json'))) {
            File::put(base_path('version.json'), $this->originalVersionJson);
        }

        parent::tearDown();
    }

    public function test_login_page_renders_actual_installed_version_from_version_service(): void
    {
        /** @var VersionService $versionService */
        $versionService = app(VersionService::class);
        $installedVer = $versionService->getInstalledVersion();
        $installedTag = $versionService->getInstalledVersionTag();

        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // Check that the installed version tag appears on login badges
        $response->assertSee('id="loginAppVersionDesktop"', false);
        $response->assertSee('id="loginAppVersionMobile"', false);
        $response->assertSee($installedTag);
        $response->assertSee('name="app-installed-version" content="' . $installedVer . '"', false);
        $response->assertSee('name="app-installed-version-tag" content="' . $installedTag . '"', false);
    }

    public function test_pwa_version_api_returns_installed_version_with_no_cache_headers(): void
    {
        Setting::set('installed_version', '2.5.2');
        Setting::set('latest_version', '2.5.2');
        Setting::flushCache();

        $response = $this->getJson('/pwa/version');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'installed_version' => '2.5.2',
                'current_version' => '2.5.2',
                'latest_version' => '2.5.2',
                'version_tag' => 'v2.5.2',
                'installed_version_tag' => 'v2.5.2',
            ]);

        // Must prevent stale caching
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
    }

    public function test_installed_version_updates_dynamically_without_restart(): void
    {
        // 1. Initially at 2.5.0
        Setting::set('installed_version', '2.5.0');
        Setting::set('system_version', '2.5.0');
        Setting::set('latest_version', '2.5.0');
        Setting::flushCache();

        $res1 = $this->get(route('login'));
        $res1->assertStatus(200);
        $res1->assertSee('v2.5.0');

        // 2. An update is applied to 2.5.3 via /pwa/update
        $updateRes = $this->postJson('/pwa/update', [
            'version' => '2.5.3',
        ]);
        $updateRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'installed_version' => '2.5.3',
                'version_tag' => 'v2.5.3',
            ]);

        // 3. Login page immediately renders 2.5.3 without manual restart or stale cache
        $res2 = $this->get(route('login'));
        $res2->assertStatus(200);
        $res2->assertSee('v2.5.3');
        $res2->assertSee('name="app-installed-version" content="2.5.3"', false);
    }

    public function test_blade_directives_render_correct_installed_version(): void
    {
        Setting::set('installed_version', '2.6.1');
        Setting::flushCache();

        $ver = Blade::render('@appInstalledVersion');
        $this->assertEquals('2.6.1', trim($ver));

        $tag = Blade::render('@appInstalledVersionTag');
        $this->assertEquals('v2.6.1', trim($tag));
    }

    public function test_login_page_has_realtime_version_sync_script_listeners(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // Verify listeners for visibilitychange, focus, pageshow, and BroadcastChannel
        $content = $response->getContent();
        $this->assertStringContainsString('syncLoginVersionBadge', $content);
        $this->assertStringContainsString('/pwa/version?_t=', $content);
        $this->assertStringContainsString('visibilitychange', $content);
        $this->assertStringContainsString('pageshow', $content);
        $this->assertStringContainsString('BroadcastChannel', $content);
        $this->assertStringContainsString('controllerchange', $content);
    }
}
