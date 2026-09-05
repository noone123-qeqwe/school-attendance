<?php

namespace Tests\Feature;

use App\Services\ChangelogService;
use Tests\TestCase;

class SoftwareUpdateChangelogTest extends TestCase
{
    protected ChangelogService $changelogService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->changelogService = app(ChangelogService::class);
    }

    public function test_changelog_service_returns_structured_metadata_for_current_release(): void
    {
        $release = $this->changelogService->getRelease('1.4.3');

        $this->assertIsArray($release);
        $this->assertEquals('1.4.3', $release['version']);
        $this->assertEquals('v1.4.3', $release['version_tag']);
        $this->assertEquals('VERSION 1.4.3', $release['version_display']);
        $this->assertNotEmpty($release['title']);
        $this->assertNotEmpty($release['description']);

        // Must have 2 to 4 actual features
        $this->assertGreaterThanOrEqual(2, count($release['features']));
        $this->assertLessThanOrEqual(4, count($release['features']));

        // Improvements and bug fixes
        $this->assertNotEmpty($release['improvements']);
        $this->assertNotEmpty($release['bugFixes']);

        // Security fixes when present
        $this->assertNotEmpty($release['security']);
        $this->assertTrue($release['has_security_fixes']);
    }

    public function test_changelog_service_normalizes_various_version_formats(): void
    {
        $variations = ['v1.4.3', '1.4.3', '143', 'v143', 'v143_1725546549', '143_999999'];

        foreach ($variations as $var) {
            $release = $this->changelogService->getRelease($var);
            $this->assertEquals('1.4.3', $release['version'], "Failed resolving variation: {$var}");
            $this->assertEquals('VERSION 1.4.3', $release['version_display']);
        }
    }

    public function test_version_without_security_fixes_omits_security_entries(): void
    {
        $release = $this->changelogService->getRelease('1.4.2');

        $this->assertIsArray($release);
        $this->assertEquals('1.4.2', $release['version']);
        $this->assertEmpty($release['security']);
        $this->assertFalse($release['has_security_fixes']);
    }

    public function test_unlisted_version_generates_safe_dynamic_fallback(): void
    {
        $release = $this->changelogService->getRelease('1.9.9');

        $this->assertIsArray($release);
        $this->assertEquals('1.9.9', $release['version']);
        $this->assertEquals('VERSION 1.9.9', $release['version_display']);
        $this->assertNotEmpty($release['title']);
        $this->assertNotEmpty($release['description']);
        $this->assertNotEmpty($release['features']);
        $this->assertNotEmpty($release['improvements']);
        $this->assertNotEmpty($release['bugFixes']);
        $this->assertEmpty($release['security']);
        $this->assertFalse($release['has_security_fixes']);
    }

    public function test_pwa_version_endpoint_includes_dynamic_changelog(): void
    {
        $response = $this->get('/pwa/version');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'version',
            'sw_version',
            'timestamp',
            'changelog' => [
                'version',
                'version_tag',
                'version_display',
                'title',
                'description',
                'features',
                'improvements',
                'bugFixes',
                'security',
                'has_security_fixes',
            ],
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['changelog']['features']);
        $this->assertNotEmpty($data['changelog']['title']);
    }

    public function test_pwa_version_endpoint_supports_version_query_parameter(): void
    {
        $response = $this->get('/pwa/version?v=1.4.2');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('1.4.2', $data['changelog']['version']);
        $this->assertEmpty($data['changelog']['security']);
    }

    public function test_rendered_pwa_banner_contains_dynamic_changelog_and_no_hardcoded_chips(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        // Verify dynamic changelog container and elements
        $response->assertSee('pwaSystemUpdatePopup', false);
        $response->assertSee('pwaUpdateChangelogContainer', false);
        $response->assertSee('pwaUpdateTitle', false);
        $response->assertSee('pwaUpdateSubtitle', false);
        $response->assertSee('pwaUpdateVersionBadge', false);

        // Verify categories are present
        $response->assertSee('NEW FEATURES', false);
        $response->assertSee('IMPROVEMENTS', false);
        $response->assertSee('BUG FIXES', false);

        // Verify hardcoded generic strings are completely absent
        $response->assertDontSee('Faster Clock-In & Sync');
        $response->assertDontSee('Faster Clock-In &amp; Sync', false);
        $response->assertDontSee('Latest Security Fixes');
    }
}
