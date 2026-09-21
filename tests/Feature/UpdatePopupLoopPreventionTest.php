<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // 2. Strict checkInstantUpdateAvailable logic ensuring no popup when already installed >= latest
        $this->assertStringContainsString('compareSemver(installedVer, latestVer) >= 0', $content);

        // 3. Extended snooze cooldown (15 minutes)
        $this->assertStringContainsString('15 * 60 * 1000', $content);

        // 4. Background check uses isManualCheck instead of hardcoded true for force flag
        $this->assertStringContainsString('showUpdateReadyPrompt(latestVer, isManualCheck, updateChangelog, isManualCheck)', $content);

        // 5. Silent skipWaiting trigger for waiting service workers when up-to-date
        $this->assertStringContainsString("swRegistration.waiting.postMessage({ action: 'skipWaiting'", $content);
    }
}
