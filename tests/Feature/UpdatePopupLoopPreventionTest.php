<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UpdatePopupLoopPreventionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify /pwa/version reports is_up_to_date as true when installed == latest.
     */
    public function test_pwa_version_reports_up_to_date_when_versions_match(): void
    {
        $versionService = app(VersionService::class);
        $versionService->refresh();
        $latest = $versionService->getLatestVersion();

        Setting::set('installed_version', $latest);
        Setting::set('latest_version', $latest);
        Setting::flushCache();

        $response = $this->getJson('/pwa/version');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['is_up_to_date']);
        $this->assertEquals($latest, $data['installed_version']);
        $this->assertEquals($latest, $data['latest_version']);
    }

    /**
     * Verify client-side PWA script contains URL _v cleanup, loop prevention, and snooze persistence.
     */
    public function test_login_page_renders_update_loop_prevention_logic(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. URL parameter _v and _t extraction and clean history replacement
        $this->assertStringContainsString("_url.searchParams.get('_v')", $content);
        $this->assertStringContainsString("_url.searchParams.delete('_v')", $content);
        $this->assertStringContainsString("_url.searchParams.delete('_t')", $content);
        $this->assertStringContainsString('window.history.replaceState', $content);

        // 2. A release already present in the loaded document is not pending.
        $this->assertStringContainsString('latest === loaded', $content);

        // 3. Dismissal is keyed to a release rather than a short timer.
        $this->assertStringContainsString('dismissedTag === currentUpdateKey', $content);
        $this->assertStringNotContainsString('DISMISS_COOLDOWN_MS', $content);

        // 4. Background checks do not force an already dismissed prompt open.
        $this->assertStringContainsString('showUpdateReadyPrompt(latestVer, isManualCheck, updateChangelog, isManualCheck)', $content);

        // 5. Silent skipWaiting trigger for waiting service workers when up-to-date
        $this->assertStringContainsString("swRegistration.waiting.postMessage({ action: 'skipWaiting'", $content);
    }

    public function test_deployed_build_metadata_wins_over_stale_cache_and_config(): void
    {
        $versionService = app(VersionService::class);
        $file = json_decode(file_get_contents(base_path('version.json')), true);
        preg_match('/CACHE_VERSION\s*=\s*[\'\"](v\d+)[\'\"]/', file_get_contents(public_path('sw.js')), $matches);

        Cache::forever('pwa_sw_version', 'v1');
        config(['version.build' => 'outdated-build']);

        $this->assertSame($matches[1], $versionService->getSwVersion());
        $this->assertSame($file['build'], $versionService->getBuild());
    }
}
