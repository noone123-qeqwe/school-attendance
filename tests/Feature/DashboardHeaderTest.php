<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardHeaderTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2000001',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'employee_id' => 'T10001',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    private function getHeaderHtml(string $content): string
    {
        preg_match('/<header[^>]*class="[^"]*top-header[^"]*"[^>]*>(.*?)<\/header>/s', $content, $matches);
        return $matches[1] ?? '';
    }

    public function test_student_dashboard_header_does_not_contain_install_app(): void
    {
        $response = $this->actingAs($this->student)->get('/home');
        $response->assertStatus(200);

        $headerHtml = $this->getHeaderHtml($response->getContent());
        $this->assertNotEmpty($headerHtml);

        // Header must not contain Install App buttons or trigger classes
        $this->assertStringNotContainsString('Install App', $headerHtml);
        $this->assertStringNotContainsString('pwa-install-trigger', $headerHtml);
        $this->assertStringNotContainsString('pwa-install-text', $headerHtml);
        $this->assertStringNotContainsString('Install Smart Attendance App', $headerHtml);

        // Global response must not have the removed header button elements
        $response->assertDontSee('pwa-install-text', false);
        $response->assertDontSee('title="Install Smart Attendance App"', false);
    }

    public function test_student_dashboard_header_contains_modern_redesigned_search_bar(): void
    {
        $response = $this->actingAs($this->student)->get('/home');
        $response->assertStatus(200);

        $headerHtml = $this->getHeaderHtml($response->getContent());
        $this->assertNotEmpty($headerHtml);

        // Modern search bar container and ID
        $this->assertStringContainsString('id="headerSearchBar"', $headerHtml);
        $this->assertStringContainsString('class="header-search-bar"', $headerHtml);

        // Clear search icon
        $this->assertStringContainsString('header-search-icon', $headerHtml);

        // Clean placeholder
        $this->assertStringContainsString('Search anything...', $headerHtml);

        // Keyboard shortcut badge
        $this->assertStringContainsString('header-search-shortcut', $headerHtml);
        $this->assertStringContainsString('header-search-key', $headerHtml);
        $this->assertStringContainsString('id="headerSearchModKey"', $headerHtml);
        $this->assertStringContainsString('Ctrl', $headerHtml);
        $this->assertStringContainsString('K', $headerHtml);

        // Command palette trigger
        $this->assertStringContainsString('openCommandPalette()', $headerHtml);
    }

    public function test_teacher_dashboard_has_modern_search_bar_and_no_install_app(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');
        $response->assertStatus(200);

        $headerHtml = $this->getHeaderHtml($response->getContent());
        $this->assertNotEmpty($headerHtml);

        $this->assertStringNotContainsString('Install App', $headerHtml);
        $this->assertStringNotContainsString('pwa-install-trigger', $headerHtml);
        $this->assertStringContainsString('id="headerSearchBar"', $headerHtml);
        $this->assertStringContainsString('Search anything...', $headerHtml);
    }

    public function test_admin_dashboard_has_modern_search_bar_and_no_install_app(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');
        $response->assertStatus(200);

        $headerHtml = $this->getHeaderHtml($response->getContent());
        $this->assertNotEmpty($headerHtml);

        $this->assertStringNotContainsString('Install App', $headerHtml);
        $this->assertStringNotContainsString('pwa-install-trigger', $headerHtml);
        $this->assertStringContainsString('id="headerSearchBar"', $headerHtml);
        $this->assertStringContainsString('Search anything...', $headerHtml);
    }
}
