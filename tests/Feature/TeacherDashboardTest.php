<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create([
            'name' => 'Prof. Test Teacher',
        ]);
    }

    // ─────────────────────────────────────────
    // ACCESS CONTROL
    // ─────────────────────────────────────────

    public function test_teacher_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
    }

    public function test_student_cannot_access_teacher_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get('/teacher/dashboard');

        // Should be forbidden or redirected
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    public function test_guest_cannot_access_teacher_dashboard(): void
    {
        $response = $this->get('/teacher/dashboard');

        $response->assertRedirect('/login');
    }

    // ─────────────────────────────────────────
    // DASHBOARD CONTENT
    // ─────────────────────────────────────────

    public function test_dashboard_shows_attendance_stats(): void
    {
        $subject = Subject::create([
            'code' => 'CS101',
            'name' => 'Intro to CS',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
            'instructor' => $this->teacher->name,
        ]);

        $student = User::factory()->create(['role' => 'student']);

        Attendance::create([
            'user_id' => $student->id,
            'subject_code' => 'CS101',
            'status' => 'Present',
            'date' => today(),
            'time_in' => now()->format('H:i:s'),
        ]);

        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Present Today');
    }

    public function test_dashboard_shows_real_time_clock(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('id="teacherClock"', false);
    }



    // ─────────────────────────────────────────
    // ATTENDANCE MANAGEMENT
    // ─────────────────────────────────────────

    public function test_teacher_can_view_attendance_page(): void
    {
        $response = $this->actingAs($this->teacher)->get('/teacher/attendance');

        $response->assertStatus(200);
    }
}
