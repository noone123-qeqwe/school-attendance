<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveLayoutSafeAreasTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_pages_share_the_screen_edge_spacing_layer(): void
    {
        $pages = [
            'student' => ['/home', '/settings', '/profile', '/student/calendar'],
            'teacher' => ['/teacher/dashboard', '/teacher/profile', '/teacher/notifications'],
            'parent' => ['/parent/dashboard', '/parent/profile', '/parent/notifications'],
            'admin' => ['/admin/dashboard', '/admin/profile', '/admin/settings'],
        ];

        foreach ($pages as $role => $urls) {
            $user = User::factory()->create(['role' => $role, 'admin_sub_role' => $role === 'admin' ? 'super_admin' : null]);
            $this->actingAs($user);
            foreach ($urls as $url) {
                $response = $this->get($url);
                $this->assertSame(200, $response->status(), "{$role}: {$url} must render.");
                $document = new \DOMDocument();
                @$document->loadHTML($response->getContent());
                $xpath = new \DOMXPath($document);
                $this->assertSame(1, $xpath->query('//*[@id="mainContent"]/*[contains(concat(" ", normalize-space(@class), " "), " page-enter ")]')->length, $url);
                $this->assertSame(1, $xpath->query('//link[contains(@href,"portal-layout-refinements.css?v=")]')->length, $url);
            }
        }
    }

    public function test_settings_does_not_cancel_the_outer_page_gutter(): void
    {
        $source = file_get_contents(resource_path('views/settings.blade.php'));
        $this->assertStringNotContainsString('.page-enter:has(.student-settings) { padding: 0 !important; }', $source);
        $styles = file_get_contents(public_path('css/student-settings.css'));
        $this->assertStringNotContainsString('margin: 0 0 18px -6px', $styles);
    }

    public function test_login_interface_has_dynamic_viewport_and_safe_area_insets(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Modern viewport meta with viewport-fit=cover and interactive-widget=resizes-content
        $this->assertStringContainsString('viewport-fit=cover', $content);
        $this->assertStringContainsString('interactive-widget=resizes-content', $content);

        // 2. Safe area inset CSS variables
        $this->assertStringContainsString('--sat: env(safe-area-inset-top', $content);
        $this->assertStringContainsString('--sab: env(safe-area-inset-bottom', $content);
        $this->assertStringContainsString('--app-height', $content);

        // 3. Dynamic visualViewport synchronization engine
        $this->assertStringContainsString('window.visualViewport', $content);
        $this->assertStringContainsString('--app-height', $content);
        $this->assertStringContainsString('applyLock', $content);
        $this->assertStringContainsString('isTextEntryFocused', $content);
        $this->assertStringContainsString("document.addEventListener('visibilitychange'", $content);
        $this->assertStringNotContainsString('if (window.scrollY !== 0 || window.scrollX !== 0) window.scrollTo(0, 0);', $content);

        // 4. Overscroll behavior disabled to prevent pull-to-refresh
        $this->assertStringContainsString('overscroll-behavior: none;', $content);
    }

    public function test_register_interface_has_dynamic_viewport_and_safe_area_insets(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Modern viewport meta
        $this->assertStringContainsString('viewport-fit=cover', $content);
        $this->assertStringContainsString('interactive-widget=resizes-content', $content);

        // 2. Safe area inset CSS variables
        $this->assertStringContainsString('--sat: env(safe-area-inset-top', $content);
        $this->assertStringContainsString('--sab: env(safe-area-inset-bottom', $content);
        $this->assertStringContainsString('--app-height', $content);
        $this->assertStringContainsString('isTextEntryFocused', $content);

        // 3. Dynamic visualViewport synchronization
        $this->assertStringContainsString('window.visualViewport', $content);
        $this->assertStringContainsString('--app-height', $content);

        // 4. Overscroll behavior disabled to prevent pull-to-refresh
        $this->assertStringContainsString('overscroll-behavior: none;', $content);
    }

    public function test_dashboard_layout_has_dynamic_viewport_and_safe_area_synchronization(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'John Doe',
            'email' => 'john.responsive@example.com',
            'student_number' => '2026-99999',
            'course' => 'BSCS',
            'year_level' => 2,
            'semester' => 1,
        ]);

        $response = $this->actingAs($student)->get(route('home'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Modern viewport meta on layout
        $this->assertStringContainsString('viewport-fit=cover', $content);
        $this->assertStringContainsString('interactive-widget=resizes-content', $content);

        // 2. Mobile enterprise CSS included
        $this->assertStringContainsString('mobile-enterprise.css', $content);

        // 3. Dynamic visualViewport synchronization script
        $this->assertStringContainsString('applyLock', $content);
        $this->assertStringContainsString('--app-height', $content);
        $this->assertStringContainsString('isTextEntryFocused', $content);
        $this->assertStringContainsString("document.addEventListener('visibilitychange'", $content);
    }
}
