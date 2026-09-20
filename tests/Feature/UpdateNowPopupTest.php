<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateNowPopupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify Update Now popup markup, styling, and action elements.
     */
    public function test_update_now_popup_markup_and_styles_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        // Core popup container and elements
        $response->assertSee('id="pwaSystemUpdatePopup"', false);
        $response->assertSee('class="pwa-update-banner"', false);
        $response->assertSee('id="pwaUpdateBackdrop"', false);
        $response->assertSee('id="pwaApplyUpdateBtn"', false);
        $response->assertSee('Update Now', false);
        $response->assertSee('id="pwaLaterUpdateBtn"', false);
        $response->assertSee('id="pwaDismissUpdatePopupBtn"', false);

        // Verify CSS .show rule exists for resilient flex rendering
        $response->assertSee('.pwa-update-banner.show', false);
        $response->assertSee('display: flex !important;', false);
    }

    /**
     * Verify /pwa/version indicates update is available when latest > installed.
     */
    public function test_pwa_version_check_detects_update_and_returns_payload(): void
    {
        // Set installed version behind latest
        Setting::set('installed_version', '1.0.0');
        Setting::flushCache();

        $response = $this->getJson('/pwa/version');
        $response->assertStatus(200);

        $versionService = app(VersionService::class);
        $versionService->refresh();
        $latest = $versionService->getLatestVersion();

        $data = $response->json();
        $this->assertFalse($data['is_up_to_date']);
        $this->assertEquals($latest, $data['latest_version']);
        $this->assertEquals('1.0.0', $data['installed_version']);
    }

    /**
     * Verify /pwa/update endpoint successfully applies update and transitions state to up-to-date.
     */
    public function test_pwa_update_endpoint_advances_version_and_marks_up_to_date(): void
    {
        $versionService = app(VersionService::class);
        $latest = $versionService->getLatestVersion();

        Setting::set('installed_version', '1.0.0');
        Setting::flushCache();
        $versionService->refresh();

        $response = $this->postJson('/pwa/update', [
            'version' => $latest,
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals($latest, $data['installed_version']);
        $this->assertTrue($data['is_up_to_date']);
    }

    /**
     * Verify client JavaScript contains all robust update display, timing, and direct click handling logic.
     */
    public function test_javascript_includes_launch_and_direct_click_handling(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. App launch and immediate instant check
        $this->assertStringContainsString('checkInstantUpdateAvailable()', $content);
        $this->assertStringContainsString('localStorage.removeItem(\'pwa_update_dismissed_at\')', $content);

        // 2. Direct click listener bound to #pwaApplyUpdateBtn
        $this->assertStringContainsString('pwaApplyUpdateBtn', $content);
        $this->assertStringContainsString('__hasUpdateListener', $content);

        // 3. Resilient showUpdateReadyPrompt logic with DOMContentLoaded fallback
        $this->assertStringContainsString('showUpdateReadyPrompt', $content);
        $this->assertStringContainsString('DOMContentLoaded', $content);

        // 4. Safe update apply with URL parameter preservation and abort timeout
        $this->assertStringContainsString('applySystemUpdate', $content);
        $this->assertStringContainsString('new URL(window.location.href)', $content);
        $this->assertStringContainsString('fetch(\'/pwa/update\'', $content);
    }
}
