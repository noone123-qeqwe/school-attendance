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
    private string $originalAndroidGradle = '';

    protected function setUp(): void
    {
        parent::setUp();

        if (File::exists(base_path('version.json'))) {
            $this->originalVersionJson = File::get(base_path('version.json'));
        }
        $gradlePath = base_path('apps/android_app/app/build.gradle');
        if (File::exists($gradlePath)) {
            $this->originalAndroidGradle = File::get($gradlePath);
        }
    }

    protected function tearDown(): void
    {
        if (!empty($this->originalVersionJson) && File::exists(base_path('version.json'))) {
            File::put(base_path('version.json'), $this->originalVersionJson);
        }
        $gradlePath = base_path('apps/android_app/app/build.gradle');
        if (!empty($this->originalAndroidGradle) && File::exists($gradlePath)) {
            File::put($gradlePath, $this->originalAndroidGradle);
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

    /**
     * Test multiple consecutive version updates (1.0.1 -> 1.0.2 -> 1.0.3)
     * Verifies that:
     * - The version updates correctly with every new build or release.
     * - It does not remain stuck on the first updated version (1.0.1).
     * - The displayed version always matches the version defined in the current app build.
     * - Both desktop & mobile badges, meta tags, and API endpoints reflect every change.
     */
    public function test_consecutive_version_updates_update_correctly_every_time(): void
    {
        /** @var VersionService $versionService */
        $versionService = app(VersionService::class);
        $versionFile = base_path('version.json');

        $versions = ['1.0.1', '1.0.2', '1.0.3'];

        foreach ($versions as $targetVersion) {
            // Simulate deploying a new build/release with updated build metadata
            File::put($versionFile, json_encode([
                'version'           => $targetVersion,
                'installed_version' => $targetVersion,
                'build'             => '20260921.' . str_replace('.', '', $targetVersion),
                'commit'            => 'bld' . str_replace('.', '', $targetVersion),
                'release_date'      => '2026-09-21',
                'channel'           => 'stable',
                'name'              => 'Smart Classroom Attendance System',
            ], JSON_PRETTY_PRINT));

            $versionService->refresh();

            // 1. VersionService getters reflect current build version
            $this->assertEquals($targetVersion, $versionService->getInstalledVersion());
            $this->assertEquals('v' . $targetVersion, $versionService->getInstalledVersionTag());
            $this->assertEquals($targetVersion, $versionService->getLatestVersion());
            $this->assertTrue($versionService->isUpToDate());

            // 2. Blade directives render current build version
            $renderedVer = Blade::render('@appInstalledVersion');
            $renderedTag = Blade::render('@appInstalledVersionTag');
            $this->assertEquals($targetVersion, trim($renderedVer));
            $this->assertEquals('v' . $targetVersion, trim($renderedTag));

            // 3. /pwa/version endpoint returns current build version
            $pwaResponse = $this->getJson('/pwa/version');
            $pwaResponse->assertStatus(200)
                ->assertJson([
                    'success'               => true,
                    'installed_version'     => $targetVersion,
                    'current_version'       => $targetVersion,
                    'installed_version_tag' => 'v' . $targetVersion,
                    'is_up_to_date'         => true,
                ]);

            // 4. Login page HTML renders current build version badges and meta tags
            $loginResponse = $this->get(route('login'));
            $loginResponse->assertStatus(200);
            $loginResponse->assertSee('v' . $targetVersion);
            $loginResponse->assertSee('name="app-installed-version" content="' . $targetVersion . '"', false);
            $loginResponse->assertSee('name="app-installed-version-tag" content="v' . $targetVersion . '"', false);
        }
    }

    /**
     * Test that cached or persisted database setting does NOT override the installed build version.
     */
    public function test_persisted_version_data_does_not_override_installed_build_version(): void
    {
        /** @var VersionService $versionService */
        $versionService = app(VersionService::class);
        $versionFile = base_path('version.json');

        // 1. Build metadata defines version 1.0.2
        File::put($versionFile, json_encode([
            'version'           => '1.0.2',
            'installed_version' => '1.0.2',
            'build'             => '20260921.102',
            'release_date'      => '2026-09-21',
        ], JSON_PRETTY_PRINT));

        // 2. Persisted DB setting has an outdated or higher version from a previous installation
        Setting::set('installed_version', '1.0.1', false);
        $versionService->refresh();

        // Installed version must match the build metadata (1.0.2), NOT the stale database record (1.0.1)
        $this->assertEquals('1.0.2', $versionService->getInstalledVersion());
        $this->assertEquals('v1.0.2', $versionService->getInstalledVersionTag());

        // Login page renders 1.0.2, not 1.0.1
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('v1.0.2');
        $response->assertSee('name="app-installed-version" content="1.0.2"', false);
    }
}
