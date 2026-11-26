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

    public function test_student_mobile_navigation_links_directly_to_excuses_and_marks_excuse_pages_active(): void
    {
        $this->actingAs($this->createStudent());

        foreach (['home', 'mobile.home', 'excuses', 'excuses.create_general'] as $route) {
            $response = $this->get(route($route))->assertOk();
            $dom = new \DOMDocument();
            $dom->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($dom);
            $nav = $xpath->query('//nav[@id="mobileBottomNav"]')->item(0);
            $this->assertNotNull($nav);

            $link = $xpath->query('./a[span[normalize-space()="Excuses"]]', $nav)->item(0);
            $this->assertInstanceOf(\DOMElement::class, $link);
            $this->assertSame(route('excuses'), $link->getAttribute('href'));
            $this->assertSame(str_starts_with($route, 'excuses') ? 'page' : '', $link->getAttribute('aria-current'));
            $this->assertSame(0, $xpath->query('.//*[normalize-space()="More"]', $nav)->length);
            $this->assertSame(0, $xpath->query('//*[@id="moreSheet"]')->length);
        }
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

        // Top header keeps notifications and Settings in the account dropdown, without My Profile
        $headerNode = $xpath->query('//header[@id="topHeader"]')->item(0);
        $this->assertNotNull($headerNode);
        $headerHtml = $dom->saveHTML($headerNode);
        $this->assertStringContainsString('topNavNotifBtn', $headerHtml);
        $this->assertStringNotContainsString('My Profile', $headerHtml);
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

        $response->assertSee('sd-masthead', false)
            ->assertSee('sd-identity-cluster', false)
            ->assertSee('sd-hero-grid', false)
            ->assertSee('id="sdHeroSpotlight"', false)
            ->assertSee('All classes completed today')
            ->assertSee('View timetable')
            ->assertSee('Attendance Standing')
            ->assertSee('7-Day Pulse')
            ->assertSee("Today's Schedule", false)
            ->assertSee('Subject Standing')
            ->assertSee('Recent Check-Ins')
            ->assertSee('Campus &amp; Class Announcements', false)
            ->assertSee('Lab Maintenance Notice')
            ->assertSee('Action Required: Attendance Warning')
            ->assertSee('id="daySummaryModal"', false)
            ->assertSee('CS302', false)
            ->assertSee('No sessions recorded yet')
            ->assertDontSee('Enter 6-Digit PIN')
            ->assertDontSee('sd-quick-dock', false)
            ->assertDontSee('id="subjectBreakdownModal"', false)
            ->assertDontSee('id="attendanceRecordsModal"', false);
    }

    public function test_student_dashboard_shows_missing_section_notice_when_section_blank(): void
    {
        $student = $this->createStudent(['section' => null]);

        $this->actingAs($student)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Class section not assigned')
            ->assertSee('sd-notice-banner')
            ->assertSee('id="closeMissingSectionNoticeBtn"', false)
            ->assertSee('aria-label="Close notice"', false)
            ->assertSee('Free schedule today');

        $this->actingAs($student)
            ->get(route('mobile.home'))
            ->assertOk()
            ->assertSee('Class section not assigned')
            ->assertSee('id="closeMissingSectionNoticeBtn"', false)
            ->assertSee('aria-label="Close notice"', false);
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
                ->assertSee('id="sdSpotlightProgressFill"', false)
                ->assertSee('Check in now', false)
                ->assertSee('View timetable', false)
                ->assertSee('Read more', false)
                ->assertSee('data-sched-filter="all"', false)
                ->assertSee('data-sched-filter="remaining"', false)
                ->assertSee('data-sched-filter="done"', false)
                ->assertSee('id="sdScheduleLiveRegion"', false)
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
            ->assertSee('sd-masthead', false)
            ->assertSee('sd-identity-cluster', false)
            ->assertSee('sd-hero-grid', false)
            ->assertSee("Today's Status")
            ->assertSee('Free schedule today')
            ->assertSee('Attendance Standing')
            ->assertSee('7-Day Pulse')
            ->assertSee('Recent Check-Ins')
            ->assertSee('1 pending excuse')
            ->assertSee('Subject Standing')
            ->assertSee('CS401', false)
            ->assertSee('id="daySummaryModal"', false);
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
            ->assertSee('aria-live="polite"', false)
            ->assertSee('document.hidden', false);

        $tokensCss = file_get_contents(public_path('css/design-tokens.css'));
        $this->assertNotFalse($tokensCss);
        $this->assertStringContainsString('scroll-padding-top:', $tokensCss);
        $this->assertStringContainsString('touch-action: manipulation;', $tokensCss);
        $this->assertStringContainsString('--ds-dur-exit:', $tokensCss);
        $this->assertStringContainsString('.ds-error-summary', $tokensCss);
        $this->assertStringContainsString('.ds-skeleton-stack', $tokensCss);

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
            ->assertSee('aria-controls="moreSheet"', false)
            ->assertSee('id="moreSheet"', false)
            ->assertDontSee('student-portal-master.css', false);
    }

    public function test_reinvented_face_and_fingerprint_registration_and_verification_studios_render(): void
    {
        $student = $this->createStudent([
            'email' => 'student.biometric.studio@gmail.com',
            'student_number' => '2310999',
        ]);

        // Face registration studio
        $this->actingAs($student)
            ->get(route('settings.biometrics.face'))
            ->assertOk()
            ->assertSee('Encrypted Biometric Studio', false)
            ->assertSee('bio-face-studio-grid', false)
            ->assertSee('bio-viewfinder-corner', false)
            ->assertSee('bio-consent-card', false)
            ->assertSee('id="cameraFaceStartBtn"', false)
            ->assertSee('id="cameraFaceConsentCheckbox"', false);

        // Fingerprint registration studio
        $this->actingAs($student)
            ->get(route('settings.biometrics.fingerprint'))
            ->assertOk()
            ->assertSee('Encrypted Biometric Studio', false)
            ->assertSee('bio-sensor-stage', false)
            ->assertSee('bio-sensor-pad', false)
            ->assertSee('id="startBioBtn"', false)
            ->assertSee('id="deviceList"', false);

        // Biometric studio CSS + Login verification modal harmonization
        $bioCss = file_get_contents(public_path('css/biometric-flow.css'));
        $this->assertNotFalse($bioCss);
        $this->assertStringContainsString('--bio-canvas: #141010;', $bioCss);
        $this->assertStringContainsString('.bio-sensor-pad', $bioCss);
        $this->assertStringContainsString('#biometricModal .bio-modal-card', $bioCss);
        $this->assertStringNotContainsString('#0f0407', $bioCss);
        $this->assertStringNotContainsString('#251b16', $bioCss);
    }

    public function test_reinvented_student_v3_pages_selectors_and_accessibility_contracts(): void
    {
        $student = $this->createStudent([
            'email' => 'student.v3.audit@gmail.com',
            'student_number' => '2310812',
        ]);

        // Calendar page no longer shows stale golden border copy
        $this->actingAs($student)
            ->get(route('student.calendar'))
            ->assertOk()
            ->assertSee('highlighted border', false)
            ->assertDontSee('The <strong>golden border</strong> marks today', false);

        // Mobile scan page drops redundant 101KB student-desktop-ux.css stylesheet
        $this->actingAs($student)
            ->get(route('mobile.scan'))
            ->assertOk()
            ->assertDontSee('student-desktop-ux.css', false)
            ->assertSee('sd-scan-desktop-bar', false);

        // Schedule route renders unified Classes & Schedule view cleanly with Login-Matched Smoked Charcoal & Warm Gold palette
        $this->actingAs($student)
            ->get(route('student.schedule'))
            ->assertOk()
            ->assertSee('corWeeklyGridView', false)
            ->assertSee('sd-view-toggle', false);

        // Standalone schedule view exposes ARIA tablist and pressed states
        $scheduleView = file_get_contents(resource_path('views/student/schedule.blade.php'));
        $this->assertNotFalse($scheduleView);
        $this->assertStringContainsString('role="tablist"', $scheduleView);
        $this->assertStringContainsString('aria-pressed=', $scheduleView);

        // Notifications search input has explicit accessible label
        $this->actingAs($student)
            ->get(route('notifications'))
            ->assertOk()
            ->assertSee('aria-label="Search notifications or subjects"', false);

        // Student pending approval, password change, QR result, and offline fallback use unified Login-Matched Smoked Charcoal & Warm Gold palette
        $this->actingAs($student)
            ->get(route('password.change.form'))
            ->assertOk()
            ->assertSee('#1E1515', false)
            ->assertDontSee('#14080C', false)
            ->assertDontSee('#6366f1', false);

        $this->flushSession();
        $pendingStudent = $this->createStudent([
            'email' => 'student.pending.v3@gmail.com',
            'student_number' => '2310813',
            'student_registration_pending' => true,
        ]);

        $this->actingAs($pendingStudent)
            ->get(route('student.pending-approval'))
            ->assertOk()
            ->assertSee('#1E1515', false)
            ->assertDontSee('#14080C', false)
            ->assertDontSee('rgba(25,15,13,.94)', false);

        $qrResultHtml = view('qr.result', ['status' => 'success', 'message' => 'Recorded'])->render();
        $this->assertStringContainsString('#1E1515', $qrResultHtml);
        $this->assertStringNotContainsString('#14080C', $qrResultHtml);
        $this->assertStringNotContainsString('#0f172a', $qrResultHtml);

        auth()->logout();
        $this->flushSession();
        $this->get('/offline')
            ->assertOk()
            ->assertSee('--bg:#141010;', false)
            ->assertDontSee('--bg:#0B0406;', false)
            ->assertDontSee('--bg:#110A0A;', false);

        // Verify fixed selector parity in student-portal-master.css & clean student-dashboard.css
        $masterCss = file_get_contents(public_path('css/student-portal-master.css'));
        $this->assertNotFalse($masterCss);
        $this->assertStringContainsString('.notif-item-card', $masterCss);
        $this->assertStringContainsString('.notif-pill', $masterCss);
        $this->assertStringContainsString('#cmdPaletteOverlay .cmd-palette', $masterCss);
        $this->assertStringContainsString('.cor-table-wrap', $masterCss);
        $this->assertStringContainsString('.sd-scan-desktop-bar', $masterCss);
        $this->assertStringNotContainsString('background-attachment: fixed', $masterCss);
        $this->assertStringNotContainsString('#0b0406', strtolower($masterCss));
        $this->assertStringNotContainsString('#e898a8', strtolower($masterCss));

        $dashCss = file_get_contents(public_path('css/student-dashboard.css'));
        $this->assertNotFalse($dashCss);
        $this->assertStringContainsString('--sdc-canvas: #141010;', $dashCss);
        $this->assertStringContainsString('--sdc-surface: #1E1515;', $dashCss);

        // Admin, Teacher & Shared design tokens use Login-Matched Smoked Charcoal Glass instead of heavy solid maroon walls
        $tokensCss = file_get_contents(public_path('css/design-tokens.css'));
        $premiumCss = file_get_contents(public_path('css/premium.css'));
        $adminCss = file_get_contents(public_path('css/admin-theme.css'));
        $teacherCss = file_get_contents(public_path('css/teacher-portal-redesign.css'));
        $this->assertNotFalse($tokensCss);
        $this->assertNotFalse($premiumCss);
        $this->assertNotFalse($adminCss);
        $this->assertNotFalse($teacherCss);
        $this->assertMatchesRegularExpression('/--ds-bg:\s*#141010;/', $tokensCss);
        $this->assertStringContainsString('--ds-surface-glass: rgba(28, 18, 18, 0.88);', $tokensCss);
        $this->assertStringContainsString('rgba(24, 17, 17, 0.94)', $premiumCss);
        $this->assertStringNotContainsString('rgba(45, 10, 10, 0.98)', $premiumCss);
        $this->assertStringNotContainsString('rgba(74, 12, 12, 0.95)', $adminCss);
        $this->assertStringNotContainsString('rgba(74, 12, 12, 0.95)', $teacherCss);
    }

    public function test_web_push_notifications_are_removed_from_settings_routes_and_schema(): void
    {
        $student = $this->createStudent([
            'email' => 'student.nowebpush@gmail.com',
            'student_number' => '2310820',
        ]);

        $settingsResponse = $this->actingAs($student)->get(route('settings'));
        $settingsResponse->assertOk()
            ->assertDontSee('Web Push Notifications', false)
            ->assertDontSee('Send Test Push Alert', false)
            ->assertDontSee('Receive background push notifications even when the browser tab is closed.', false)
            ->assertDontSee('push-toggle-input', false)
            ->assertDontSee('push-status-badge', false)
            ->assertDontSee('push-test-btn', false)
            ->assertDontSee('toggleWebPush', false)
            ->assertDontSee('web-push.js', false)
            ->assertSee('In-App Notifications', false)
            ->assertSee('Email Notifications', false)
            ->assertSee('App updates', false);

        $this->actingAs($student)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('web-push.js', false);

        $this->actingAs($student)
            ->post(route('settings.preferences.update'), [
                'prefs' => ['in_app' => '1', 'email' => '1'],
            ])
            ->assertRedirect(route('settings'));

        $this->assertSame(['in_app' => true, 'email' => true], $student->fresh()->notification_preferences);

        $this->get('/push/public-key')->assertNotFound();
        $this->actingAs($student)->postJson('/push/subscribe', [])->assertNotFound();
        $this->actingAs($student)->postJson('/push/unsubscribe', [])->assertNotFound();
        $this->actingAs($student)->postJson('/push/test', [])->assertNotFound();

        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('push_subscriptions'));
        $this->assertFileDoesNotExist(public_path('js/web-push.js'));

        $swContents = file_get_contents(public_path('sw.js'));
        $this->assertNotFalse($swContents);
        $this->assertStringNotContainsString("addEventListener('push'", $swContents);
        $this->assertStringNotContainsString("addEventListener('notificationclick'", $swContents);

        $teacher = $this->createStudent([
            'name' => 'Prof. Excuse Check',
            'role' => 'teacher',
            'student_number' => null,
            'employee_id' => 'TCH-NOWEB',
            'email' => 'teacher.nowebpush@gmail.com',
        ]);

        $subject = Subject::create([
            'code' => 'CS499',
            'name' => 'Capstone Verification',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
            'instructor_id' => $teacher->id,
        ]);

        $attendanceApproved = Attendance::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'subject_code' => $subject->code,
            'date' => now()->subDay()->toDateString(),
            'status' => 'Absent',
        ]);
        $excuseApprove = \App\Models\ExcuseSubmission::create([
            'user_id' => $student->id,
            'attendance_id' => $attendanceApproved->id,
            'reason' => 'Medical',
            'description' => 'Doctor note',
            'status' => 'pending',
        ]);

        $attendanceRejected = Attendance::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'subject_code' => $subject->code,
            'date' => now()->toDateString(),
            'status' => 'Absent',
        ]);
        $excuseReject = \App\Models\ExcuseSubmission::create([
            'user_id' => $student->id,
            'attendance_id' => $attendanceRejected->id,
            'reason' => 'Personal',
            'description' => 'Late submission',
            'status' => 'pending',
        ]);

        $this->flushSession();
        $this->actingAs($teacher)
            ->postJson(route('teacher.excuse.approve', $excuseApprove), ['admin_notes' => 'Approved note'])
            ->assertOk()
            ->assertJsonPath('status', 'approved');

        $this->actingAs($teacher)
            ->postJson(route('teacher.excuse.reject', $excuseReject), ['admin_notes' => 'Missing attachment'])
            ->assertOk()
            ->assertJsonPath('status', 'rejected');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'type' => 'excuse_approved',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'type' => 'warning_2',
        ]);
    }

    public function test_student_guardian_privacy_note_wraps_text_in_single_flex_item(): void
    {
        $student = $this->createStudent([
            'email' => 'student.privacywrap@gmail.com',
            'student_number' => '2310821',
        ]);

        $response = $this->actingAs($student)->get(route('settings'))->assertOk();

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $note = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " student-privacy-note ")]')->item(0);
        $this->assertNotNull($note);

        // Strong must live inside the text wrapper span, never as a direct flex sibling of text nodes.
        $this->assertSame(0, $xpath->query('./strong', $note)->length);
        $textWrap = $xpath->query('./span[contains(concat(" ", normalize-space(@class), " "), " student-privacy-note-text ")]', $note)->item(0);
        $this->assertNotNull($textWrap);
        $this->assertSame(1, $xpath->query('./strong[normalize-space()="Privacy safeguard:"]', $textWrap)->length);

        $settingsCss = file_get_contents(public_path('css/student-settings.css'));
        $this->assertNotFalse($settingsCss);
        $this->assertStringContainsString('.student-privacy-note-text', $settingsCss);
        $this->assertStringContainsString('overflow-wrap: break-word;', $settingsCss);
    }

    public function test_student_academic_spec_cards_and_pref_tiles_restore_full_borders_padding_and_grid_gap(): void
    {
        $student = $this->createStudent([
            'email' => 'student.specgrid@gmail.com',
            'student_number' => '2310822',
        ]);

        $this->actingAs($student)
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('aria-label="Copy Student ID"', false);

        $masterCss = file_get_contents(public_path('css/student-portal-master.css'));
        $this->assertNotFalse($masterCss);
        $this->assertMatchesRegularExpression(
            '/\.sp\.student-settings\[data-student-settings\]\s+\.academic-spec-grid\s*\{[^}]*gap:\s*12px\s*!important;/s',
            $masterCss
        );
        $this->assertMatchesRegularExpression(
            '/\.sp\.student-settings\[data-student-settings\]\s+\.academic-spec-card\s*,\s*body\.student-portal\s+\.sp\.student-settings\[data-student-settings\]\s+\.pref-tile\s*,\s*body\.student-portal\s+\.sp\.student-settings\[data-student-settings\]\s+\.sec-status-tile\s*\{[^}]*border:\s*1px\s+solid\s+var\(--sdc-border-subtle\)\s*!important;/s',
            $masterCss
        );
        $this->assertMatchesRegularExpression(
            '/\.sp\.student-settings\[data-student-settings\]\s+\.academic-spec-card\s*\{[^}]*padding:\s*14px\s+16px\s*!important;/s',
            $masterCss
        );
        $this->assertMatchesRegularExpression(
            '/\.sp\.student-settings\[data-student-settings\]\s+\.pref-tile\s*\{[^}]*padding:\s*16px\s+18px\s*!important;/s',
            $masterCss
        );

        $polishCss = file_get_contents(public_path('css/settings-polish.css'));
        $this->assertNotFalse($polishCss);
        $this->assertMatchesRegularExpression(
            '/\.pref-tile\s+\.form-check\.form-switch[^{]*\{[^}]*padding-left:\s*0\s*!important;/s',
            $polishCss
        );
        $this->assertMatchesRegularExpression(
            '/\.pref-tile\s+\.form-check\.form-switch\s+\.form-check-input[^{]*\{[^}]*margin:\s*0\s*!important;\s*float:\s*none\s*!important;/s',
            $polishCss
        );

        $studentSettingsCss = file_get_contents(public_path('css/student-settings.css'));
        $this->assertNotFalse($studentSettingsCss);
        $this->assertMatchesRegularExpression(
            '/\.pref-tile\s+\.form-check\.form-switch\s+\.form-check-input\s*\{[^}]*margin:\s*0\s*!important;\s*float:\s*none\s*!important;/s',
            $studentSettingsCss
        );
    }

    public function test_student_settings_profile_photo_card_avoids_profile_summary_collision_and_provides_change_photo_action(): void
    {
        $student = $this->createStudent([
            'email' => 'student.photocard@gmail.com',
            'student_number' => '2310823',
        ]);

        $response = $this->actingAs($student)
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('class="student-photo-change-btn"', false)
            ->assertSee('for="ppmFileInput"', false)
            ->assertSee('Change photo', false);

        $masterCss = file_get_contents(public_path('css/student-portal-master.css'));
        $this->assertNotFalse($masterCss);
        $this->assertStringContainsString('.student-profile-summary:not(.sc)', $masterCss);
        $this->assertMatchesRegularExpression(
            '/\.sp\.student-settings\[data-student-settings\]\s+\.sc\.student-profile-summary\s*\{[^}]*display:\s*block\s*!important;[^}]*padding:\s*0\s*!important;/s',
            $masterCss
        );
        $this->assertMatchesRegularExpression(
            '/\.sp\.student-settings\[data-student-settings\]\s+\.ppm-badge-btn\s*\{[^}]*background:\s*var\(--sdc-cta-grad\)\s*!important;[^}]*color:\s*var\(--sdc-cta-text\)\s*!important;/s',
            $masterCss
        );

        // Standalone student profile page still keeps its summary layout intact
        $this->actingAs($student)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('<section class="student-profile-summary"', false);
    }

    public function test_student_sidebar_omits_redundant_section_labels(): void
    {
        $student = $this->createStudent([
            'email' => 'student.nolabels@gmail.com',
            'student_number' => '2310824',
        ]);

        $response = $this->actingAs($student)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('student-sidebar-nav', false)
            ->assertSee('Classes &amp; Schedule', false)
            ->assertSee('Attendance &amp; Calendar', false)
            ->assertDontSee('My workspace', false)
            ->assertDontSee('Attendance &amp; school', false)
            ->assertDontSee('student-nav-label nav-section-label', false);

        $sidebarTemplate = file_get_contents(resource_path('views/layouts/sidebars/student.blade.php'));
        $this->assertNotFalse($sidebarTemplate);
        $this->assertStringNotContainsString('nav-section-label', $sidebarTemplate);
    }
}
