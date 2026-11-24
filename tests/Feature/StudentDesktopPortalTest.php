<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\ExcuseSubmission;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentDesktopPortalTest extends TestCase
{
    use RefreshDatabase;

    private function seedStudentEnvironment(): array
    {
        $teacher = User::create([
            'name' => 'Dr. Elena Santos',
            'student_number' => 'TCH-101',
            'course' => 'FACULTY',
            'year_level' => '1',
            'email' => 'elena.santos@school.test',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $student = User::create([
            'name' => 'Marco Reyes',
            'student_number' => '2026-0412',
            'course' => 'BSCS',
            'year_level' => '2',
            'semester' => '1',
            'section' => 'A',
            'email' => 'marco.reyes@school.test',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $subject = Subject::create([
            'code' => 'CS204',
            'name' => 'Algorithms & Complexity',
            'year_level' => 2,
            'semester' => 1,
            'section' => 'A',
            'units' => 3,
            'instructor_id' => $teacher->id,
        ]);

        $student->enrolledSubjects()->attach($subject->id);

        Schedule::create([
            'subject_id' => $subject->id,
            'day' => now()->format('l'),
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'room' => 'Room 302',
            'section' => 'A',
        ]);

        Attendance::create([
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => today()->toDateString(),
            'status' => 'Present',
            'time_in' => '09:02:00',
            'method' => 'qr',
            'excused' => false,
        ]);

        $absent = Attendance::create([
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => today()->subDays(2)->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);

        $unexcusedAbsent = Attendance::create([
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => today()->subDays(4)->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);

        ExcuseSubmission::create([
            'user_id' => $student->id,
            'attendance_id' => $absent->id,
            'reason' => 'Medical/Health Issues',
            'description' => 'Clinic consultation for fever recovery.',
            'status' => 'pending',
        ]);

        Notification::create([
            'user_id' => $student->id,
            'type' => 'custom',
            'message' => 'Midterm room assignments published.',
            'is_read' => false,
        ]);

        Announcement::create([
            'title' => 'Chronicle Campus Assembly',
            'content' => 'Attendance verification reminder for Monday assemblies.',
            'target_audience' => 'All',
            'author_id' => $teacher->id,
            'scheduled_for' => now()->subMinute(),
        ]);

        return compact('teacher', 'student', 'subject', 'absent', 'unexcusedAbsent');
    }

    public function test_all_student_desktop_routes_render_and_preserve_hooks(): void
    {
        ['student' => $student, 'unexcusedAbsent' => $unexcusedAbsent] = $this->seedStudentEnvironment();

        $routesAndMarkers = [
            ['route' => route('home'), 'markers' => ['sd-command-hero', 'studentClock', 'headerSearchBar', 'studentSidebarNotifBadge', 'Chronicle Campus Assembly']],
            ['route' => route('student.classes'), 'markers' => ['corViewWeeklyBtn', 'corWeeklyGridView', 'Algorithms &amp; Complexity']],
            ['route' => route('student.schedule'), 'markers' => ['corViewWeeklyBtn', 'scheduleSearchInput']],
            ['route' => route('student.calendar'), 'markers' => ['scalGrid', 'sdDesktopDayInspector', 'attendance-records', 'excuseModal', 'correctionModal']],
            ['route' => route('excuses'), 'markers' => ['excuseNeedsPanel', 'excuseHistoryPanel', 'cs-excuse-new-btn']],
            ['route' => route('excuses.create_general'), 'markers' => ['excuseForm', 'excuseReason', 'dropZone']],
            ['route' => route('excuses.create', $unexcusedAbsent), 'markers' => ['excuseForm', 'CS204']],
            ['route' => route('notifications'), 'markers' => ['notifListContainer', 'btnMarkAllRead', 'Midterm room assignments published.']],
            ['route' => route('profile'), 'markers' => ['studentAvatarDisplay', 'registerFpBtn', '2026-0412']],
            ['route' => route('settings'), 'markers' => ['studentSettingsContent', 'studentSettingsFilter', 'student-settings-menu']],
            ['route' => route('settings.biometrics.face'), 'markers' => ['biometric-flow-topbar', 'cameraFaceGuide']],
            ['route' => route('settings.biometrics.fingerprint'), 'markers' => ['biometric-flow-topbar', 'startBioBtn']],
            ['route' => route('mobile.scan'), 'markers' => ['sd-scan-desktop-bar', 'openScannerBtn', 'openCodeBtn']],
        ];

        foreach ($routesAndMarkers as $entry) {
            $response = $this->actingAs($student)->get($entry['route']);
            $this->assertSame(200, $response->status(), 'Failed HTTP 200 on route: ' . $entry['route']);
            foreach ($entry['markers'] as $marker) {
                $response->assertSee($marker, false);
            }
        }
    }

    public function test_teacher_dashboard_does_not_receive_student_portal_body_class(): void
    {
        ['teacher' => $teacher] = $this->seedStudentEnvironment();

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));
        $response->assertOk();
        $response->assertDontSee('student-desktop-ux.css', false);
    }
}
