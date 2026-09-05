<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_manifest_file_is_valid_json_and_contains_required_pwa_fields(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $jsonContent = file_get_contents($manifestPath);
        $manifest = json_decode($jsonContent, true);

        $this->assertNotNull($manifest, 'manifest.json must be valid JSON');
        $this->assertEquals('Smart Attendance', $manifest['name']);
        $this->assertEquals('Smart Attendance', $manifest['short_name']);
        $this->assertEquals('standalone', $manifest['display']);
        $this->assertEquals('/', $manifest['start_url']);
        $this->assertEquals('#110A0A', $manifest['background_color']);
        $this->assertEquals('#110A0A', $manifest['theme_color']);
        $this->assertEquals('2.1.0', $manifest['version']);
        $this->assertArrayHasKey('launch_handler', $manifest);
        
        $this->assertNotEmpty($manifest['icons']);
        $this->assertGreaterThanOrEqual(10, count($manifest['icons']));

        // Verify maskable icon definition
        $hasMaskable = collect($manifest['icons'])->contains(function ($icon) {
            return ($icon['purpose'] ?? '') === 'maskable';
        });
        $this->assertTrue($hasMaskable, 'Manifest must include at least one maskable icon');
    }

    public function test_pwa_icon_assets_exist(): void
    {
        $icons = [
            'icon-16x16.png',
            'icon-32x32.png',
            'icon-48x48.png',
            'icon-72x72.png',
            'icon-96x96.png',
            'icon-128x128.png',
            'icon-144x144.png',
            'icon-152x152.png',
            'icon-180x180.png',
            'icon-192x192.png',
            'icon-384x384.png',
            'icon-512x512.png',
            'icon-maskable-512x512.png',
        ];

        foreach ($icons as $icon) {
            $this->assertFileExists(public_path("images/icons/{$icon}"), "Expected icon {$icon} to exist");
        }
    }

    public function test_pwa_screenshot_assets_exist(): void
    {
        $screenshots = [
            'dashboard.png',
            'desktop.png',
        ];

        foreach ($screenshots as $ss) {
            $path = public_path("images/screenshots/{$ss}");
            $this->assertFileExists($path, "Expected screenshot {$ss} to exist");
            $this->assertGreaterThan(0, filesize($path), "Screenshot {$ss} must not be empty");
        }
    }

    public function test_service_worker_file_exists_and_contains_modern_handlers(): void
    {
        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);
        $content = file_get_contents($swPath);
        $this->assertStringContainsString('addEventListener', $content);
        $this->assertStringContainsString('CACHE_NAME', $content);
        $this->assertStringContainsString('OFFLINE_URL', $content);
        $this->assertStringContainsString('push', $content);
        $this->assertStringContainsString('notificationclick', $content);
    }

    public function test_offline_page_renders_successfully(): void
    {
        $response = $this->get('/offline');
        $response->assertStatus(200);
        $response->assertSee("You're Offline", false);
        $response->assertSee('Retry Connection');
        $response->assertSee('cachedProfileBox', false);
    }

    public function test_login_page_includes_pwa_elements(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('/manifest.json');
        $response->assertSee('sw.js');
        $response->assertSee('apple-mobile-web-app-capable');
        $response->assertSee('pwaIosModal', false);
        $response->assertSee('pwaSystemUpdatePopup', false);
        $response->assertSee('pwaApplyUpdateBtn', false);
        $response->assertSee('pwa-install-trigger', false);
        $response->assertSee('Install Attendance App', false);
    }

    public function test_sw_file_is_served_with_no_cache_headers(): void
    {
        $response = $this->get('/sw.js');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
        $this->assertStringContainsString('no-cache', (string)$response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
        $this->assertStringContainsString('must-revalidate', (string)$response->headers->get('Cache-Control'));
        $response->assertHeader('Service-Worker-Allowed', '/');
    }

    public function test_pwa_version_endpoint_returns_json(): void
    {
        $response = $this->get('/pwa/version');
        $response->assertStatus(200);
        $response->assertJsonStructure(['version', 'timestamp']);
    }
}
