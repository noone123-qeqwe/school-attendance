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
            ->assertSee('sd-notice-banner')
            ->assertSee('Free schedule today');
    }

    public function test_student_settings_replaces_app_sidebar_with_single_settings_sidebar(): void
    {
        $student = $this->createStudent();

        $settingsResponse = $this->actingAs($student)->get(route('settings'));
        $settingsResponse->assertOk();

        $html = $settingsResponse->getContent();
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Only the primary sidebar container exists; no secondary sidebar inside main content
        $sidebars = $xpath->query('//aside');
        $this->assertSame(1, $sidebars->length);
        $sidebarNode = $xpath->query('//aside[@id="sidebar"]')->item(0);
        $this->assertNotNull($sidebarNode);
        $this->assertSame(0, $xpath->query('//aside[contains(@class, "student-settings-menu")]')->length);
        $this->assertSame(0, $xpath->query('//aside[contains(@class, "settings-sidebar")]')->length);

        $sidebarText = preg_replace('/\s+/', ' ', $sidebarNode->textContent ?? '');
        $sidebarHtml = $dom->saveHTML($sidebarNode);

        // Back to Dashboard sits at top and links to student dashboard
        $backLinks = $xpath->query('.//a[contains(@class, "settings-sidebar-back-btn")]', $sidebarNode);
        $this->assertSame(1, $backLinks->length);
        $this->assertSame(route('home'), $backLinks->item(0)->getAttribute('href'));
        $this->assertStringContainsString('Back to Dashboard', $backLinks->item(0)->textContent ?? '');

        // Exact Settings sidebar groups and items
        foreach ([
            'Account',
            'Profile & school details',
            'Guardians',
            'Security & attendance',
            'Password & email',
            'Biometrics',
            'Attendance device',
            'Recovery codes',
            'App',
            'Notifications & app',
        ] as $expectedText) {
            $this->assertStringContainsString($expectedText, $sidebarText);
        }

        // Default application sidebar links are absent while on Settings
        $this->assertStringNotContainsString('Classes & Schedule', $sidebarText);
        $this->assertStringNotContainsString('Excuse Submissions', $sidebarText);
        $this->assertStringContainsString('data-settings-page="profile"', $sidebarHtml);
        $this->assertStringContainsString('data-settings-page="recovery"', $sidebarHtml);

        // The settings identify attendance face enrollment and fingerprint registration.
        $settingsResponse->assertSee('id="studentBiometricChoices"', false);
        $biometricChoices = $xpath->query('//*[@id="studentBiometricChoices"]//strong');
        $choiceTitles = [];
        foreach ($biometricChoices as $choice) {
            $choiceTitles[] = trim($choice->textContent ?? '');
        }
        $this->assertSame(['Face registration', 'Fingerprint registration'], $choiceTitles);

        // Navigating back to Dashboard restores the default student application sidebar
        $dashboardResponse = $this->actingAs($student)->get(route('home'));
        $dashboardResponse->assertOk();
        $dashDom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dashDom->loadHTML($dashboardResponse->getContent());
        libxml_clear_errors();
        $dashXpath = new \DOMXPath($dashDom);
        $dashSidebar = $dashXpath->query('//aside[@id="sidebar"]')->item(0);
        $this->assertNotNull($dashSidebar);
        $dashSidebarText = preg_replace('/\s+/', ' ', $dashSidebar->textContent ?? '');
        $this->assertStringContainsString('Classes & Schedule', $dashSidebarText);
        $this->assertStringContainsString('Excuse Submissions', $dashSidebarText);
        $this->assertStringNotContainsString('Back to Dashboard', $dashSidebarText);
    }

    public function test_navigating_to_other_app_pages_keeps_default_sidebar_while_settings_middleware_stays_intact(): void
    {
        $student = $this->createStudent();

        foreach ([route('student.classes'), route('student.calendar'), route('excuses')] as $url) {
            $resp = $this->actingAs($student)->get($url);
            $resp->assertOk();

            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($resp->getContent());
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);

            $sidebars = $xpath->query('//aside');
            $this->assertSame(1, $sidebars->length);
            $sidebarNode = $xpath->query('//aside[@id="sidebar"]')->item(0);
            $this->assertNotNull($sidebarNode);

            $sidebarText = preg_replace('/\s+/', ' ', $sidebarNode->textContent ?? '');
            $this->assertStringContainsString('Dashboard', $sidebarText);
            $this->assertStringContainsString('Classes & Schedule', $sidebarText);
            $this->assertStringNotContainsString('Back to Dashboard', $sidebarText);
        }

        // Preserve student-only route access control on /settings
        $this->flushSession();
        $teacher = $this->createStudent([
            'role' => 'teacher',
            'student_number' => null,
            'employee_id' => 'TCH-99',
            'email' => 'teacher.settings@gmail.com',
        ]);
        $this->actingAs($teacher)->get(route('settings'))->assertRedirect();
    }

    public function test_student_dashboard_renders_interactive_filters_kpi_modals_and_announcement_toggles(): void
    {
        $testNow = \Carbon\Carbon::today('Asia/Manila')->setTime(10, 0);
        \Carbon\Carbon::setTestNow($testNow);

        try {
            $admin = $this->createStudent([
                'name' => 'Admin Interactive',
                'role' => 'admin',
                'admin_sub_role' => 'super_admin',
                'student_number' => null,
                'employee_id' => 'ADM-99',
                'email' => 'admin.interactive@gmail.com',
            ]);

            $teacher = $this->createStudent([
                'name' => 'Prof. Interactive',
                'role' => 'teacher',
                'student_number' => null,
                'employee_id' => 'TCH-98',
                'email' => 'teacher.interactive@gmail.com',
            ]);

            $student = $this->createStudent([
                'email' => 'student.interactive@gmail.com',
                'student_number' => '2310999',
            ]);
            $student->forceFill(['created_at' => $testNow->copy()->subDays(5)])->save();

            $todayName = $testNow->format('l');

            $classes = [
                ['code' => 'CS201', 'name' => 'Algorithms', 'start' => '08:00:00', 'end' => '09:00:00', 'present' => true],
                ['code' => 'CS202', 'name' => 'Operating Systems', 'start' => '09:30:00', 'end' => '11:00:00', 'present' => false],
                ['code' => 'CS203', 'name' => 'Computer Networks', 'start' => '13:00:00', 'end' => '14:30:00', 'present' => false],
            ];

            $subjectIds = [];
            foreach ($classes as $idx => $entry) {
                $subject = Subject::create([
                    'code' => $entry['code'],
                    'name' => $entry['name'],
                    'year_level' => 3,
                    'semester' => 1,
                    'course' => 'BSCS',
                    'section' => 'A',
                    'instructor_id' => $teacher->id,
                ]);
                $subjectIds[] = $subject->id;

                Schedule::create([
                    'subject_id' => $subject->id,
                    'day' => $todayName,
                    'start_time' => $entry['start'],
                    'end_time' => $entry['end'],
                    'room' => 'Room ' . (201 + $idx),
                ]);

                if ($entry['present']) {
                    Attendance::create([
                        'user_id' => $student->id,
                        'subject_id' => $subject->id,
                        'subject_code' => $subject->code,
                        'date' => $testNow->toDateString(),
                        'time_in' => '08:04:00',
                        'status' => 'Present',
                    ]);
                }
            }

            $student->enrolledSubjects()->attach($subjectIds);

            Announcement::create([
                'title' => 'Important Laboratory Guideline',
                'content' => str_repeat('Please bring your lab notebook and student ID card before scheduled sessions. ', 4),
                'target_audience' => 'Student',
                'author_id' => $admin->id,
                'scheduled_for' => $testNow->copy()->subHour(),
            ]);

            $response = $this->actingAs($student)->get(route('home'));

            $response->assertOk()
                ->assertSee('id="sdHeroSpotlight"', false)
                ->assertSee('id="sdSpotlightTimer"', false)
                ->assertSee("openAttendanceRecordsModal('present')", false)
                ->assertSee("openAttendanceRecordsModal('late')", false)
                ->assertSee("openAttendanceRecordsModal('absent')", false)
                ->assertSee('openSubjectBreakdownModal()', false)
                ->assertSee('All (3)', false)
                ->assertSee('Remaining (2)', false)
                ->assertSee('Done (1)', false)
                ->assertSee('Read more', false)
                ->assertSee('#cmdPaletteOverlay .cmd-palette-input:focus-visible', false)
                ->assertSee('id="cmdPaletteCategories" role="group"', false);
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_reinvented_student_dashboard_renders_standing_gauge_seven_day_pulse_recent_activity_and_pending_excuses(): void
    {
        $student = $this->createStudent([
            'email' => 'student.cockpit@gmail.com',
            'student_number' => '2310777',
        ]);

        $subject = Subject::create([
            'code' => 'CS401',
            'name' => 'Software Architecture',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
        ]);
        $student->enrolledSubjects()->attach([$subject->id]);

        $attendance = Attendance::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'subject_code' => $subject->code,
            'date' => now()->toDateString(),
            'time_in' => '08:05:00',
            'status' => 'Present',
        ]);

        \App\Models\ExcuseSubmission::create([
            'user_id' => $student->id,
            'attendance_id' => $attendance->id,
            'reason' => 'Medical',
            'description' => 'Clinic visit certificate',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($student)->get(route('home'));

        $response->assertOk()
            ->assertSee('student-dashboard.css', false)
            ->assertSee('class="sd-cockpit"', false)
            ->assertSee('Attendance Standing')
            ->assertSee('7-Day Attendance Pulse')
            ->assertSee('Recent Activity')
            ->assertSee('1 Pending Excuse')
            ->assertSee('Software Architecture');
    }

    public function test_reinvented_mobile_home_dashboard_renders_unified_cockpit_sections(): void
    {
        $admin = $this->createStudent([
            'name' => 'Admin Mobile',
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'student_number' => null,
            'employee_id' => 'ADM-55',
            'email' => 'admin.mobile@gmail.com',
        ]);

        $student = $this->createStudent([
            'email' => 'student.mobile@gmail.com',
            'student_number' => '2310888',
        ]);

        $subject = Subject::create([
            'code' => 'CS405',
            'name' => 'Mobile Systems',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
        ]);
        $student->enrolledSubjects()->attach([$subject->id]);

        Schedule::create([
            'subject_id' => $subject->id,
            'day' => now()->format('l'),
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'room' => 'Lab 304',
        ]);

        Attendance::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'subject_code' => $subject->code,
            'date' => now()->toDateString(),
            'time_in' => '09:00:00',
            'status' => 'Present',
        ]);

        Announcement::create([
            'title' => 'Mobile Campus Advisory',
            'content' => 'Reminder to carry student ID on campus.',
            'target_audience' => 'Student',
            'author_id' => $admin->id,
            'scheduled_for' => now()->subMinute(),
        ]);

        $response = $this->actingAs($student)->get(route('mobile.home'));

        $response->assertOk()
            ->assertSee('student-dashboard.css', false)
            ->assertSee('sd-mobile-cockpit', false)
            ->assertSee('Scan QR')
            ->assertSee('Enter PIN')
            ->assertSee('Attendance Standing')
            ->assertSee('7-Day Pulse')
            ->assertSee('Mobile Systems')
            ->assertSee('Mobile Campus Advisory');
    }

    public function test_ui_ux_pro_max_wcag_tokens_skeletons_live_regions_and_design_system_are_in_place(): void
    {
        $student = $this->createStudent([
            'email' => 'student.ux@gmail.com',
            'student_number' => '2310666',
        ]);

        $response = $this->actingAs($student)->get(route('home'));
        $response->assertOk()
            ->assertSee('id="recordsModalLiveStatus"', false)
            ->assertSee('ds-skeleton-stack', false)
            ->assertSee('document.hidden', false);

        $tokensCss = file_get_contents(public_path('css/design-tokens.css'));
        $this->assertNotFalse($tokensCss);
        $this->assertStringContainsString('scroll-padding-top:', $tokensCss);
        $this->assertStringContainsString('touch-action: manipulation;', $tokensCss);
        $this->assertStringContainsString('--ds-dur-exit:', $tokensCss);
        $this->assertStringContainsString('.ds-error-summary', $tokensCss);

        $modernCss = file_get_contents(public_path('css/system-ui-modern.css'));
        $this->assertNotFalse($modernCss);
        $this->assertStringContainsString('text-wrap: balance;', $modernCss);
        $this->assertStringContainsString('font-variant-numeric: tabular-nums;', $modernCss);

        $this->assertFileExists(base_path('design-system/smart-attendance/MASTER.md'));
        $this->assertFileExists(base_path('design-system/smart-attendance/pages/student-dashboard.md'));
    }

    public function test_reinvented_student_frontend_master_stylesheet_loads_across_all_desktop_and_mobile_pages(): void
    {
        $student = $this->createStudent([
            'email' => 'student.fullfrontend@gmail.com',
            'student_number' => '2310777',
        ]);

        $subject = Subject::create([
            'code' => 'CS410',
            'name' => 'Software Architecture',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
        ]);
        $student->enrolledSubjects()->attach([$subject->id]);

        $studentRoutes = [
            route('home'),
            route('mobile.home'),
            route('student.classes'),
            route('student.schedule'),
            route('student.calendar'),
            route('excuses'),
            route('excuses.create_general'),
            route('notifications'),
            route('profile'),
            route('mobile.profile'),
            route('mobile.scan'),
            route('settings'),
            route('settings.biometrics.face'),
            route('settings.biometrics.fingerprint'),
        ];

        foreach ($studentRoutes as $url) {
            $this->actingAs($student)
                ->get($url)
                ->assertOk()
                ->assertSee('student-portal-master.css', false);
        }

        // mobile.history redirects cleanly to the unified calendar & records view
        $this->actingAs($student)
            ->get(route('mobile.history'))
            ->assertRedirect(route('student.calendar').'#attendance-records');

        // Verify reinvented semantic classes render on key subpages
        $this->actingAs($student)
            ->get(route('excuses'))
            ->assertOk()
            ->assertSee('sd-excuse-hero', false)
            ->assertSee('sd-excuse-kpis', false);

        $this->actingAs($student)
            ->get(route('excuses.create_general'))
            ->assertOk()
            ->assertSee('sd-excuse-form-card', false)
            ->assertSee('sd-dropzone', false);

        $this->actingAs($student)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('sd-profile-quick-link', false);

        // Verify non-student portals (Teacher) do NOT load the student portal master stylesheet
        $this->flushSession();
        $teacher = User::create([
            'name' => 'Portal Scope Teacher',
            'employee_id' => 'TCH-SCOPE-01',
            'email' => 'teacher.scope@gmail.com',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertDontSee('student-portal-master.css', false);
    }
}
