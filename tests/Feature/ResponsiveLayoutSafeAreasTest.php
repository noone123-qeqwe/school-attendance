<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveLayoutSafeAreasTest extends TestCase
{
    use RefreshDatabase;

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
