<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherPortalRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function createSyntheticUser(string $role, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role) . ' Redesign User',
            'student_number' => $role === 'student' ? '2026-REDESIGN-01' : null,
            'employee_id' => $role === 'teacher' ? 'FAC-REDESIGN-01' : null,
            'course' => $role === 'student' ? 'BSCS' : 'FACULTY',
            'year_level' => '1',
            'semester' => '1',
            'email' => $role . '.redesign.' . uniqid() . '@example.test',
            'password' => Hash::make('StrongPass#12345'),
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    public function test_teacher_dashboard_renders_unified_command_canvas_navigation_and_live_hooks(): void
    {
        $teacher = $this->createSyntheticUser('teacher', [
            'name' => 'Prof. Clara Mendoza',
            'employee_id' => 'FAC-2026-900',
        ]);
        $student = $this->createSyntheticUser('student', [
            'name' => 'Synthetic Student One',
            'student_number' => '2026-9901',
        ]);

        $subject = Subject::create([
            'code' => 'CS301',
            'name' => 'Algorithms & Data Structures',
            'year_level' => 3,
            'semester' => 1,
            'instructor_id' => $teacher->id,
            'section' => 'A',
        ]);

        Schedule::create([
            'subject_id' => $subject->id,
            'day' => now()->format('l'),
            'start_time' => '09:00',
            'end_time' => '10:30',
            'room' => 'Room 304',
        ]);

        Attendance::create([
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => now()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));
        $response->assertOk();

        $html = $response->getContent();

        // Shared CSS foundation + Teacher unified master stylesheet + font parity with Student portal
        $this->assertStringContainsString('Plus+Jakarta+Sans', $html);
        $this->assertStringContainsString('portal-unified-foundation.css', $html);
        $this->assertStringContainsString('teacher-portal-master.css', $html);
        $this->assertStringNotContainsString('student-dashboard.css', $html);

        // Streamlined single-line sidebar without subtitle clutter or duplicate Notifications/Profile
        $this->assertStringContainsString('teacher-sidebar-nav', $html);
        $this->assertStringNotContainsString('Teacher workspace', $html);
        preg_match('/<nav class="sidebar-nav teacher-sidebar-nav".*?<\/nav>/s', $html, $sidebarMatches);
        $sidebarHtml = $sidebarMatches[0] ?? '';
        $this->assertNotEmpty($sidebarHtml);
        $this->assertStringNotContainsString('teacher-nav-description', $sidebarHtml);
        $this->assertStringNotContainsString('My Profile', $sidebarHtml);
        $this->assertStringNotContainsString(route('teacher.notifications'), $sidebarHtml);
        $this->assertStringContainsString(route('teacher.corrections'), $sidebarHtml);

        // 5-slot Mobile Bottom Nav with center featured QR button
        $this->assertStringContainsString('mbn-item-featured', $html);
        $this->assertStringContainsString(route('teacher.classroom.index', ['task' => 'attendance']), $html);

        // Academic Command Canvas sections (without redundant shortcut pill rail) + Weekly trend + At-risk warning
        $this->assertStringContainsString('portal-masthead', $html);
        $this->assertStringContainsString('portal-hero-grid', $html);
        $this->assertStringNotContainsString('portal-utility-rail', $html);
        $this->assertStringNotContainsString('aria-label="Teacher shortcuts"', $html);
        $this->assertStringContainsString('portal-workspace', $html);
        $this->assertStringContainsString('id="tdWeeklyTrend"', $html);
        $this->assertStringContainsString('Prof. Clara Mendoza', $html);
        $this->assertStringContainsString('FAC-2026-900', $html);
        $this->assertStringContainsString('CS301', $html);
        $this->assertStringContainsString('Synthetic Student One', $html);
        $this->assertStringContainsString(route('teacher.student', $student), $html);

        // Preserved real-time Echo DOM hooks
        $this->assertStringContainsString('id="realStats"', $html);
        $this->assertStringContainsString('data-accent="success"', $html);
        $this->assertStringContainsString('data-accent="danger"', $html);
        $this->assertStringContainsString('id="peerLiveNotice"', $html);
        $this->assertStringContainsString('id="peerLiveConnection"', $html);
        $this->assertStringContainsString('id="recentAttendanceRows"', $html);
        $this->assertStringContainsString('id="recentLogsSearch"', $html);
        $this->assertStringContainsString('id="schoolCalendar"', $html);

        // FullCalendar loaded exactly once (duplicate CDN tag removed)
        $this->assertSame(1, substr_count($html, 'fullcalendar@6.1.21/index.global.min.js'));

        // Command palette points Live QR directly to attendance picker & includes Corrections
        $this->assertStringContainsString("label: 'Correction Requests'", $html);

        // Zero mojibake
        $this->assertStringNotContainsString('â€”', $html);
        $this->assertStringNotContainsString('â€¢', $html);
        $this->assertStringNotContainsString('â‰¥', $html);
    }

    public function test_all_teacher_subpages_render_with_unified_design_system_and_clean_encoding(): void
    {
        $teacher = $this->createSyntheticUser('teacher');
        $student = $this->createSyntheticUser('student');

        $subject = Subject::create([
            'code' => 'CS402',
            'name' => 'Software Architecture',
            'year_level' => 1,
            'semester' => 1,
            'instructor_id' => $teacher->id,
            'section' => 'B',
        ]);
        $subject->enrolledStudents()->attach($student->id);

        Attendance::create([
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => now()->toDateString(),
            'status' => 'Present',
            'excused' => false,
        ]);

        $routes = [
            route('teacher.classroom.index'),
            route('teacher.classroom.index', ['task' => 'attendance']),
            route('teacher.classroom.show', $subject->code),
            route('teacher.qr', $subject->code),
            route('teacher.attendance'),
            route('teacher.absent'),
            route('teacher.excuse.reviews'),
            route('teacher.corrections'),
            route('teacher.students'),
            route('teacher.student', $student),
            route('teacher.subjects.create'),
            route('teacher.subjects.students', $subject->code),
            route('teacher.reports'),
            route('teacher.calendar'),
            route('teacher.notifications'),
            route('teacher.profile'),
            route('teacher.excuses'),
            route('teacher.excuses.create'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($teacher)->get($url);
            $this->assertSame(200, $response->getStatusCode(), "Expected HTTP 200 on {$url}");
            $html = $response->getContent();
            $this->assertStringContainsString('portal-unified-foundation.css', $html, "Missing shared CSS on {$url}");
            $this->assertStringContainsString('teacher-portal-master.css', $html, "Missing teacher master CSS on {$url}");
            $this->assertStringNotContainsString('â€”', $html, "Mojibake detected on {$url}");
            $this->assertStringNotContainsString('â€¢', $html, "Mojibake bullet detected on {$url}");
            $this->assertStringNotContainsString('â‰¥', $html, "Mojibake >= detected on {$url}");
        }
    }

    public function test_student_portal_remains_unaffected_by_teacher_redesign(): void
    {
        $student = $this->createSyntheticUser('student');

        $response = $this->actingAs($student)->get(route('home'));
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('portal-unified-foundation.css', $html);
        $this->assertStringContainsString('student-portal-master.css', $html);
        $this->assertStringContainsString('student-dashboard.css', $html);
        $this->assertStringNotContainsString('teacher-portal-master.css', $html);
    }
}
