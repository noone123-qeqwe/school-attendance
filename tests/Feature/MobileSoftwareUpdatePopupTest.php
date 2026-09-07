<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileSoftwareUpdatePopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_software_update_metadata_contract_satisfies_latest_greater_than_installed(): void
    {
        config([
            'changelog.installed_version' => '2.1.0',
            'changelog.default_version' => '2.2.0',
        ]);

        $response = $this->get('/pwa/version');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('installed_version', $data);
        $this->assertArrayHasKey('latest_version', $data);

        $installed = $data['installed_version'];
        $latest = $data['latest_version'];

        $this->assertEquals('2.1.0', $installed);
        $this->assertEquals('2.2.0', $latest);

        // Assert semver Latest > Installed
        $this->assertGreaterThan(0, version_compare($latest, $installed));
    }

    public function test_mobile_responsive_css_and_update_popup_rendered_on_login(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        // Update popup element
        $response->assertSee('id="pwaSystemUpdatePopup"', false);
        $response->assertSee('id="pwaApplyUpdateBtn"', false);
        $response->assertSee('id="pwaLaterUpdateBtn"', false);
        $response->assertSee('id="pwaDismissUpdatePopupBtn"', false);

        // Mobile CSS rules for clearing the floating mobile bottom nav capsule
        $response->assertSee('z-index: 100005 !important;', false);
        $response->assertSee('bottom: calc(84px + env(safe-area-inset-bottom, 12px)) !important;', false);
        $response->assertSee('max-height: min(520px, calc(100dvh - 96px - env(safe-area-inset-bottom, 12px))) !important;', false);

        // Meta tags for update comparison
        $installed = (string)config('changelog.installed_version', '2.2.0');
        $latest = (string)config('changelog.default_version', '2.2.0');
        $response->assertSee('<meta name="app-installed-version" content="' . $installed . '">', false);
        $response->assertSee('<meta name="app-latest-version" content="' . $latest . '">', false);
    }

    public function test_mobile_responsive_css_and_update_popup_rendered_on_register(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('id="pwaSystemUpdatePopup"', false);
        $installed = (string)config('changelog.installed_version', '2.2.0');
        $latest = (string)config('changelog.default_version', '2.2.0');
        $response->assertSee('<meta name="app-installed-version" content="' . $installed . '">', false);
        $response->assertSee('<meta name="app-latest-version" content="' . $latest . '">', false);
    }

    public function test_settings_page_reflects_dynamic_application_version(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/settings');

        $response->assertStatus(200);
        $latest = (string)config('changelog.default_version', '2.2.0');
        $response->assertSee('v' . $latest, false);
    }

    public function test_pwa_update_script_contains_semver_comparison_logic(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Ensure semver comparison functions and installed/latest getters are defined
        $this->assertStringContainsString('function parseSemver(', $content);
        $this->assertStringContainsString('function compareSemver(', $content);
        $this->assertStringContainsString('function getInstalledVersion(', $content);
        $this->assertStringContainsString('function getLatestVersion(', $content);
        $this->assertStringContainsString('compareSemver(latestVer, installedVer) > 0', $content);

        // Ensure pageshow and visibilitychange listeners exist for mobile tab/app switching
        $this->assertStringContainsString("'pageshow'", $content);
        $this->assertStringContainsString("'visibilitychange'", $content);
    }
}
