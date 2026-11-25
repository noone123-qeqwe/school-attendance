<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Models\Warning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentDashboardAndSidebarTest extends TestCase
{
    use RefreshDatabase;

    private function createStudent(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Mark Santos',
            'student_number' => '2310843',
            'course' => 'BSCS',
            'year_level' => '3',
            'semester' => '1',
            'section' => 'A',
            'email' => 'mark.santos@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'student',
            'is_active' => true,
        ], $overrides));
    }

    public function test_student_sidebar_omits_profile_settings_scan_pin_and_notifications(): void
    {
        $student = $this->createStudent();

        $response = $this->actingAs($student)->get(route('home'));
        $response->assertOk();

        $html = $response->getContent();
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $sidebarNode = $xpath->query('//aside[@id="sidebar"]')->item(0);
        $this->assertNotNull($sidebarNode, 'Student sidebar element must exist.');

        $sidebarText = preg_replace('/\s+/', ' ', $sidebarNode->textContent ?? '');
        $sidebarHtml = $dom->saveHTML($sidebarNode);

        // Kept workspace & attendance items
        $this->assertStringContainsString('Dashboard', $sidebarText);
        $this->assertStringContainsString('Classes & Schedule', $sidebarText);
        $this->assertStringContainsString('Attendance & Calendar', $sidebarText);
        $this->assertStringContainsString('Excuse Submissions', $sidebarText);

        // Removed items must not appear inside the sidebar
        $this->assertStringNotContainsString('My Profile', $sidebarText);
        $this->assertStringNotContainsString('My profile', $sidebarText);
        $this->assertStringNotContainsString('Settings', $sidebarText);
        $this->assertStringNotContainsString('Notifications', $sidebarText);
        $this->assertStringNotContainsString('Scan QR', $sidebarText);
        $this->assertStringNotContainsString('PIN', $sidebarText);
        $this->assertStringNotContainsString('Check in', $sidebarText);
        $this->assertStringNotContainsString('sd-scan-dock', $sidebarHtml);
        $this->assertStringNotContainsString('student-sidebar-account', $sidebarHtml);
        $this->assertStringNotContainsString('student-nav-account', $sidebarHtml);
        $this->assertStringNotContainsString('studentSidebarNotifBadge', $sidebarHtml);
        $this->assertStringNotContainsString('sidebar-subtitle', $sidebarHtml);
        $this->assertStringNotContainsString('QR, GPS, and Biometric-Based Attendance Monitoring', $sidebarText);

        $navCss = file_get_contents(public_path('css/student-portal-navigation.css'));
        $this->assertNotFalse($navCss);
        $this->assertMatchesRegularExpression('/\.student-sidebar-brand\s+\.sidebar-logo\s*\{[^}]*border:\s*none;/s', $navCss);
        $this->assertStringNotContainsString('border: 1px solid rgba(223, 184, 121, 0.28);', $navCss);

        // Top header still keeps notifications and account dropdown links
        $headerNode = $xpath->query('//header[@id="topHeader"]')->item(0);
        $this->assertNotNull($headerNode);
        $headerHtml = $dom->saveHTML($headerNode);
        $this->assertStringContainsString('topNavNotifBtn', $headerHtml);
        $this->assertStringContainsString(route('profile'), $headerHtml);
        $this->assertStringContainsString(route('settings'), $headerHtml);
    }

    public function test_student_dashboard_renders_redesigned_sections_and_zero_record_subject_gracefully(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'student_number' => 'ADM-01',
            'course' => 'ADMIN',
            'year_level' => '1',
            'email' => 'admin.dash@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'is_active' => true,
        ]);

        $teacher = User::create([
            'name' => 'Prof. Reyes',
            'student_number' => 'TCH-01',
            'course' => 'FACULTY',
            'year_level' => '1',
            'email' => 'reyes.prof@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $student = $this->createStudent();

        $activeSubject = Subject::create([
            'code' => 'CS301',
            'name' => 'Operating Systems',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
            'instructor_id' => $teacher->id,
        ]);

        $zeroRecordSubject = Subject::create([
            'code' => 'CS302',
            'name' => 'Compiler Design',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
            'instructor_id' => $teacher->id,
        ]);

        $student->enrolledSubjects()->attach([$activeSubject->id, $zeroRecordSubject->id]);

        $todayDay = now()->format('l');
        Schedule::create([
            'subject_id' => $activeSubject->id,
            'day' => $todayDay,
            'start_time' => now()->subMinutes(10)->format('H:i:s'),
            'end_time' => now()->addMinutes(50)->format('H:i:s'),
            'room' => 'CS Lab 201',
        ]);

        Attendance::create([
            'user_id' => $student->id,
            'subject_id' => $activeSubject->id,
            'subject_code' => $activeSubject->code,
            'date' => now()->toDateString(),
            'status' => 'Present',
            'time_in' => now()->subMinutes(5)->format('H:i:s'),
        ]);

        Announcement::create([
            'title' => 'Lab Maintenance Notice',
            'content' => 'Room 201 computers updated for finals.',
            'target_audience' => 'Student',
            'author_id' => $admin->id,
            'scheduled_for' => now()->subHour(),
        ]);

        Warning::create([
            'user_id' => $student->id,
            'subject_code' => 'CS301',
            'type' => 'warning_1',
            'message' => 'Please maintain attendance above 85%.',
            'sent_by' => $teacher->id,
        ]);

        $response = $this->actingAs($student)->get(route('home'));
        $response->assertOk();

        $response->assertSee('Scan Attendance QR')
            ->assertSee('Enter 6-Digit PIN')
            ->assertSee('Weekly Schedule')
            ->assertSee('My Classes')
            ->assertSee('Attendance Log')
            ->assertSee('Submit Excuse')
            ->assertSee('Attendance Overview')
            ->assertSee("Today's Schedule", false)
            ->assertSee('Subject Attendance')
            ->assertSee('Campus &amp; Class Announcements', false)
            ->assertSee('Lab Maintenance Notice')
            ->assertSee('Action Required: Attendance Warning')
            ->assertSee('Operating Systems')
            ->assertSee('Compiler Design')
            ->assertSee('No records');
    }

    public function test_student_dashboard_shows_missing_section_notice_when_section_blank(): void
    {
        $student = $this->createStudent(['section' => null]);

        $this->actingAs($student)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Class section not assigned')
            ->assertSee('sd-notice-banner');
    }
}
