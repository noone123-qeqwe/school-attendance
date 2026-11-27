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
        $this->assertStringNotContainsString('Take Attendance', $sidebarHtml);
        $this->assertStringNotContainsString('Student Roster', $sidebarHtml);
        $this->assertStringNotContainsString('Student Record', $sidebarHtml);
        $this->assertStringNotContainsString('Attendance Records', $sidebarHtml);
        $this->assertStringNotContainsString(route('teacher.attendance'), $sidebarHtml);
        $this->assertStringNotContainsString(route('teacher.notifications'), $sidebarHtml);
        $this->assertStringContainsString(route('teacher.excuse.reviews'), $sidebarHtml);
        $this->assertStringContainsString('Excuses &amp; Corrections', $sidebarHtml);
        $this->assertStringNotContainsString(route('teacher.corrections'), $sidebarHtml);
        $this->assertStringNotContainsString(route('teacher.excuses'), $sidebarHtml);
        $this->assertStringNotContainsString('Correction Requests', $sidebarHtml);
        $this->assertStringNotContainsString('My Excuses', $sidebarHtml);
        $this->assertStringContainsString(route('teacher.classroom.index'), $sidebarHtml);

        // 5-slot Mobile Bottom Nav with center featured QR button
        $this->assertStringContainsString('mbn-item-featured', $html);
        $this->assertStringContainsString(route('teacher.classroom.index', ['task' => 'attendance']), $html);

        // Focused Command Canvas + Editorial Schedule Stage (removed secondary workspace widgets)
        $this->assertStringContainsString('portal-masthead', $html);
        $this->assertStringContainsString('portal-hero-grid', $html);
        $this->assertStringContainsString('portal-stage', $html);
        $this->assertStringNotContainsString('portal-utility-rail', $html);
        $this->assertStringNotContainsString('aria-label="Teacher shortcuts"', $html);
        $this->assertStringNotContainsString('portal-telemetry', $html);
        $this->assertStringNotContainsString('id="tdWeeklyTrend"', $html);
        $this->assertStringNotContainsString('id="realStats"', $html);
        $this->assertStringNotContainsString('portal-workspace', $html);
        $this->assertStringNotContainsString('id="schoolCalendar"', $html);
        $this->assertStringNotContainsString('id="recentAttendanceRows"', $html);
        $this->assertStringNotContainsString('id="recentLogsSearch"', $html);
        $this->assertStringNotContainsString('teacherAtRiskHeading', $html);
        $this->assertStringNotContainsString('teacherAnnounceHeading', $html);
        $this->assertStringContainsString('td-today-time', $html);
        $this->assertStringContainsString('td-today-status', $html);
        $this->assertStringContainsString('portal-stage-foot', $html);
        $this->assertStringContainsString('Prof. Clara Mendoza', $html);
        $this->assertStringContainsString('FAC-2026-900', $html);
        $this->assertStringContainsString('CS301', $html);

        // Preserved real-time Echo DOM hooks
        $this->assertStringContainsString('id="peerLiveNotice"', $html);
        $this->assertStringContainsString('id="peerLiveConnection"', $html);
        $this->assertStringContainsString('data-banner-dismiss="peerLiveConnection"', $html);
        $this->assertStringContainsString('data-banner-dismiss="peerLiveNotice"', $html);
        $this->assertStringContainsString('data-banner-message', $html);

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
            route('teacher.classroom.index', ['task' => 'records']),
            route('teacher.classroom.show', $subject->code),
            route('teacher.classroom.show', ['subjectCode' => $subject->code, 'tab' => 'records']),
            route('teacher.qr', $subject->code),
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

        $this->actingAs($teacher)->get(route('teacher.attendance'))
            ->assertRedirect(route('teacher.classroom.index', ['task' => 'records']));
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
