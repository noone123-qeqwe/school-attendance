<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subject;
use App\Models\Attendance;
use App\Models\ExcuseSubmission;
use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherSidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create([
            'name' => 'Prof. Reorganize Teacher',
        ]);
    }

    public function test_teacher_sidebar_renders_all_organized_categories_and_links(): void
    {
        $this->actingAs($this->teacher);

        $html = view('layouts.sidebars.teacher')->render();

        // Check group categories
        $this->assertStringContainsString('Main', $html);
        $this->assertStringContainsString('Teaching', $html);
        $this->assertStringContainsString('Attendance', $html);
        $this->assertStringContainsString('Requests', $html);
        $this->assertStringContainsString('School', $html);

        // Check all individual features
        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringContainsString('My Classes', $html);
        $this->assertStringContainsString('Student Roster', $html);
        $this->assertStringContainsString('Attendance Records', $html);
        $this->assertStringContainsString('Attendance Reports', $html);
        $this->assertStringContainsString('Excuse Submissions', $html);
        $this->assertStringContainsString('Correction Requests', $html);
        $this->assertStringContainsString('School Calendar', $html);

        // Verify routes are properly bound
        $this->assertStringContainsString(route('teacher.dashboard'), $html);
        $this->assertStringContainsString(route('teacher.classroom.index'), $html);
        $this->assertStringContainsString(route('teacher.students'), $html);
        $this->assertStringContainsString(route('teacher.attendance'), $html);
        $this->assertStringContainsString(route('teacher.reports'), $html);
        $this->assertStringContainsString(route('teacher.excuse.reviews'), $html);
        $this->assertStringContainsString(route('teacher.corrections'), $html);
        $this->assertStringContainsString(route('teacher.calendar'), $html);

        // Verify collapsible controls and accessibility attributes
        $this->assertStringContainsString('toggleTeacherGroup', $html);
        $this->assertStringContainsString('teacherGroupTeaching', $html);
        $this->assertStringContainsString('teacherGroupAttendance', $html);
        $this->assertStringContainsString('teacherGroupRequests', $html);
        $this->assertStringContainsString('aria-expanded="true"', $html);
        $this->assertStringContainsString('aria-controls="teacherMenuTeaching"', $html);
        $this->assertStringContainsString('aria-controls="teacherMenuAttendance"', $html);
        $this->assertStringContainsString('aria-controls="teacherMenuRequests"', $html);

        // Ensure Biometric Login was removed
        $this->assertStringNotContainsString('Biometric Login', $html);
    }

    public function test_teacher_sidebar_displays_real_pending_badges_when_requests_exist(): void
    {
        $subject = Subject::create([
            'code' => 'SIDEBAR101',
            'name' => 'Sidebar Testing',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
            'instructor_id' => $this->teacher->id,
            'instructor' => $this->teacher->name,
        ]);

        $student = User::factory()->create(['role' => 'student']);

        $attendance = Attendance::create([
            'user_id' => $student->id,
            'subject_code' => 'SIDEBAR101',
            'status' => 'Absent',
            'date' => today(),
            'time_in' => now()->format('H:i:s'),
        ]);

        // Create 1 pending excuse submission
        ExcuseSubmission::create([
            'attendance_id' => $attendance->id,
            'user_id' => $student->id,
            'reason' => 'Sick',
            'description' => 'Sick with fever and medical certificate attached',
            'status' => 'pending',
        ]);

        // Create 1 pending correction
        AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'student_id' => $student->id,
            'requested_status' => 'Present',
            'reason' => 'Was present but scanned late',
            'status' => 'pending',
        ]);

        $this->actingAs($this->teacher);
        $html = view('layouts.sidebars.teacher')->render();

        // Check that real pending badges appear
        $this->assertStringContainsString('badge-excuses', $html);
        $this->assertStringContainsString('badge-corrections', $html);
        $this->assertStringContainsString('title="1 pending excuse(s)"', $html);
        $this->assertStringContainsString('title="1 pending correction(s)"', $html);
        $this->assertStringContainsString('title="2 pending request(s)"', $html);
    }

    public function test_teacher_routes_render_cleanly_with_redesigned_sidebar(): void
    {
        $routes = [
            '/teacher/dashboard',
            '/teacher/classroom',
            '/teacher/students',
            '/teacher/attendance',
            '/teacher/reports',
            '/teacher/excuse-reviews',
            '/teacher/corrections',
            '/teacher/calendar',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->teacher)->get($route);
            $response->assertOk();
            $response->assertSee('teacher-sidebar-nav', false);
            $response->assertSee('Dashboard', false);
        }
    }
}
