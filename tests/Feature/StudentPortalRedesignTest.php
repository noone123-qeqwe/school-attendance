<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalRedesignTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

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
    }

    public function test_student_desktop_pages_load_the_shared_student_design_layer(): void
    {
        $studentPages = [
            '/home',
            '/profile',
            '/my-classes',
            '/attendance/records',
            '/student/attendance-calendar',
            '/student/calendar',
            '/excuses',
        ];

        foreach ($studentPages as $url) {
            $response = $this->actingAs($this->student)->get($url);

            $response->assertOk();
            $response->assertSee('student-portal-redesign.css', false);
            $response->assertSee('class="student-portal"', false);
        }
    }

    public function test_student_mobile_pages_load_the_shared_student_design_layer(): void
    {
        foreach (['/mobile/home', '/mobile/scan', '/mobile/history', '/mobile/profile'] as $url) {
            $response = $this->actingAs($this->student)->get($url);

            $response->assertOk();
            $response->assertSee('student-portal-redesign.css', false);
            $response->assertSee('student-mobile-portal', false);
        }
    }

    public function test_student_settings_keeps_its_existing_dedicated_design(): void
    {
        $response = $this->actingAs($this->student)->get('/settings');

        $response->assertOk();
        $response->assertDontSee('student-portal-redesign.css', false);
    }

    public function test_student_settings_uses_roomy_desktop_and_narrow_phone_layouts(): void
    {
        $response = $this->actingAs($this->student)->get('/settings');

        $response->assertOk();
        $response->assertSee('max-width: 1480px', false);
        $response->assertSee('grid-template-columns: 1fr; gap: 8px;', false);
        $response->assertSee('padding: 22px 14px;', false);
    }

    public function test_student_design_layer_includes_desktop_mobile_and_reduced_motion_rules(): void
    {
        $stylesheet = file_get_contents(public_path('css/student-portal-redesign.css'));

        $this->assertNotFalse($stylesheet);
        $this->assertStringContainsString('body.student-portal .student-hero-banner', $stylesheet);
        $this->assertStringContainsString('body.student-mobile-portal .mobile-header', $stylesheet);
        $this->assertStringContainsString('padding: clamp(28px, 3vw, 46px)', $stylesheet);
        $this->assertStringContainsString('body.student-mobile-portal .mobile-home .quick-action-btn', $stylesheet);
        $this->assertStringContainsString('@media (max-width: 480px)', $stylesheet);
        $this->assertStringContainsString('body.student-mobile-portal .mobile-history .stats-pills-grid', $stylesheet);
        $this->assertStringContainsString('@media (max-width: 767.98px)', $stylesheet);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $stylesheet);
    }
}
