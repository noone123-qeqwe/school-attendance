<?php

namespace Tests\Feature;

use Tests\TestCase;

class FastUpdateDetectionTest extends TestCase
{
    /**
     * Test fallback update pill UI rendered in DOM.
     */
    public function test_update_pill_rendered_in_views(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        // Verify #pwaUpdatePill exists with correct IDs and classes
        $response->assertSee('id="pwaUpdatePill"', false);
        $response->assertSee('class="pwa-update-pill"', false);
        $response->assertSee('id="pwaPillVersionBadge"', false);
        $response->assertSee('pwa-pill-dot', false);
        $response->assertSee('pwa-pill-btn', false);
    }

    /**
     * Test JavaScript contains non-blocking, fast, and multi-channel update checking logic.
     */
    public function test_javascript_contains_fast_and_resilient_update_checking(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Fast, non-blocking check with AbortController and mutex
        $this->assertStringContainsString('isCheckingVersion', $content);
        $this->assertStringContainsString('checkVersionPromise', $content);
        $this->assertStringContainsString('new AbortController()', $content);

        // 2. Cooldown timer for snooze/dismissal (not permanent suppression)
        $this->assertStringContainsString('DISMISS_COOLDOWN_MS', $content);
        $this->assertStringContainsString('showUpdateFallbackPill', $content);
        $this->assertStringContainsString('hideUpdateFallbackPill', $content);

        // 3. Multi-tab synchronization
        $this->assertStringContainsString('BroadcastChannel', $content);
        $this->assertStringContainsString('pwa_update_channel', $content);
        $this->assertStringContainsString('pwa_tab_updated_at', $content);

        // 4. Instant SW registration on DOMContentLoaded (not waiting for load)
        $this->assertStringContainsString('DOMContentLoaded', $content);

        // 5. Multi-channel background triggers
        $this->assertStringContainsString('visibilitychange', $content);
        $this->assertStringContainsString('pageshow', $content);
        $this->assertStringContainsString('focus', $content);
        $this->assertStringContainsString('online', $content);
        $this->assertStringContainsString('popstate', $content);
    }

    /**
     * Test service worker does not silently skip waiting if an active controller already exists.
     */
    public function test_service_worker_lifecycle_preserves_active_session(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);
        $content = file_get_contents($swPath);

        // Verifies conditional skipWaiting to protect active sessions
        $this->assertStringContainsString('!self.registration || !self.registration.active', $content);
        $this->assertStringContainsString('SW_UPDATED', $content);
        $this->assertStringContainsString('SKIP_WAITING', $content);
        $this->assertStringContainsString('CLEAR_CACHE', $content);
    }
}
