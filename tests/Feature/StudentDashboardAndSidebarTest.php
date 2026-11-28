<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\ClassStudentAssistant;
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

        foreach (['home', 'excuses', 'excuses.create_general'] as $route) {
            $response = $this->get(route($route))->assertOk();
            $dom = new \DOMDocument();
            $dom->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($dom);
            $nav = $xpath->query('//nav[@id="mobileBottomNav"]')->item(0);
            $this->assertNotNull($nav);

            $link = $xpath->query('./a[span[normalize-space()="Excuses"]]', $nav)->item(0);
            $this->assertInstanceOf(\DOMElement::class, $link);
            $this->assertSame(route('excuses'), $link->getAttribute('href'));
            $expectedCurrent = $route === 'excuses' ? 'page' : ($route === 'excuses.create_general' ? 'location' : '');
            $this->assertSame($expectedCurrent, $link->getAttribute('aria-current'));
            $this->assertSame(0, $xpath->query('.//*[normalize-space()="More"]', $nav)->length);
            $this->assertSame(0, $xpath->query('//*[@id="moreSheet"]')->length);
        }

        $this->get(route('mobile.home'))->assertRedirect(route('home'));
    }

    public function test_assigned_student_finds_assistant_tools_in_classes_instead_of_the_mobile_dock(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createStudent([
            'role' => 'teacher',
            'email' => 'teacher.assistant.test@example.com',
            'student_number' => 'TCH-ASST',
        ]);
        $subject = Subject::create([
            'code' => 'CS101',
            'name' => 'Introduction to Computing',
            'year_level' => 3,
            'semester' => 1,
            'section' => 'A',
            'instructor_id' => $teacher->id,
        ]);
        ClassStudentAssistant::create([
            'subject_id' => $subject->id,
            'student_id' => $student->id,
            'assigned_by_teacher_id' => $teacher->id,
            'active_slot' => 1,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($student)->get(route('student.classes'))->assertOk();
        $dom = new \DOMDocument();
        $dom->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);
        $mobileLinks = $xpath->query('//nav[@id="mobileBottomNav"]/a/@href');

        $this->assertSame([
            route('home'), route('student.classes'), route('mobile.scan'), route('student.calendar'), route('excuses'),
        ], array_map(fn (\DOMAttr $link) => $link->value, iterator_to_array($mobileLinks)));
        $this->assertSame(route('student-assistant.classes'), $xpath->evaluate('string(//main//a[normalize-space()="Student Assistant"]/@href)'));
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
        $sidebarNavNode = $xpath->query('.//nav[contains(@class, "sf-sidebar-nav")]', $sidebarNode)->item(0);
        $this->assertNotNull($sidebarNavNode, 'Student sidebar primary nav must exist.');

        $sidebarNavText = preg_replace('/\s+/', ' ', $sidebarNavNode->textContent ?? '');
        $sidebarHtml = $dom->saveHTML($sidebarNode);

        // Kept workspace & attendance items
        $this->assertStringContainsString('Dashboard', $sidebarNavText);
        $this->assertStringContainsString('Classes & Schedule', $sidebarNavText);
        $this->assertStringContainsString('Attendance & Calendar', $sidebarNavText);
        $this->assertStringContainsString('Excuse Submissions', $sidebarNavText);

        // Removed items must not appear inside the primary sidebar navigation list
        $this->assertStringNotContainsString('My Profile', $sidebarNavText);
        $this->assertStringNotContainsString('My profile', $sidebarNavText);
        $this->assertStringNotContainsString('Settings', $sidebarNavText);
        $this->assertStringNotContainsString('Notifications', $sidebarNavText);
        $this->assertStringNotContainsString('Scan QR', $sidebarNavText);
        $this->assertStringNotContainsString('PIN', $sidebarNavText);
        $this->assertStringNotContainsString('Check in', $sidebarNavText);
        $this->assertStringNotContainsString('sd-scan-dock', $sidebarHtml);
        $this->assertStringNotContainsString('studentSidebarNotifBadge', $sidebarHtml);
        $this->assertStringNotContainsString('sidebar-subtitle', $sidebarHtml);
        $this->assertStringNotContainsString('QR, GPS, and Biometric-Based Attendance Monitoring', $sidebarHtml);

        // Pinned Linear sidebar account popover holds Settings & Log out without My Profile
        $this->assertStringContainsString('id="sfAccountPopover"', $sidebarHtml);
        $this->assertStringContainsString(route('settings'), $sidebarHtml);
        $this->assertStringNotContainsString('My Profile', $sidebarHtml);

        $frontendCss = file_get_contents(public_path('css/student-frontend.css'));
        $this->assertNotFalse($frontendCss);
        $this->assertStringContainsString('.sf-brand-logo', $frontendCss);

        // Top header keeps notifications, command palette search, and mobile account trigger without My Profile
        $headerNode = $xpath->query('//header[@id="topHeader"]')->item(0);
        $this->assertNotNull($headerNode);
        $headerHtml = $dom->saveHTML($headerNode);
        $this->assertStringContainsString('topNavNotifBtn', $headerHtml);
        $this->assertStringContainsString('headerSearchBar', $headerHtml);
        $this->assertStringContainsString('sfMobileAvatarBtn', $headerHtml);
        $this->assertStringNotContainsString('My Profile', $headerHtml);
    }

    public function test_student_dashboard_shows_current_and_upcoming_classes_with_actionable_attendance(): void
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
        Schedule::create([
            'subject_id' => $zeroRecordSubject->id,
            'day' => $todayDay,
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'room' => 'CS Lab 202',
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

        $response->assertSee('sf-identity-strip', false)
            ->assertSee('sf-hero-card', false)
            ->assertSee('id="sdHeroSpotlight"', false)
            ->assertSee('Operating Systems')
            ->assertSee('Compiler Design')
            ->assertSee('Class schedule', false)
            ->assertSee('Needs your attention')
            ->assertSee('Recent attendance')
            ->assertDontSee('Attendance Standing')
            ->assertDontSee('7-Day Pulse')
            ->assertDontSee('Subject Standing')
            ->assertSee('Lab Maintenance Notice')
            ->assertSee('Please maintain attendance above 85%.')
            ->assertSee('CS302', false)
            ->assertDontSee('Enter 6-Digit PIN')
            ->assertDontSee('sd-quick-dock', false)
            ->assertDontSee('id="daySummaryModal"', false)
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
            ->assertSee('aria-label="Dismiss class section notice"', false)
            ->assertSee('data-alert-key="missing-class-section"', false)
            ->assertSee('js/alert-banners.js', false)
            ->assertSee('Free schedule today');

        $this->actingAs($student)
            ->get(route('mobile.home'))
            ->assertRedirect(route('home'));
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

        // Back to app link sits at top and links to student dashboard
        $backLinks = $xpath->query('.//a[@id="settingsBackToDashboardLink"]', $sidebarNode);
        $this->assertSame(1, $backLinks->length);
        $this->assertSame(route('home'), $backLinks->item(0)->getAttribute('href'));
        $this->assertStringContainsString('Back to app', $backLinks->item(0)->textContent ?? '');

        // Exact Settings sidebar groups and items
        foreach ([
            'Account',
            'Profile',
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
        $this->assertSame('Profile', trim($xpath->evaluate('string(//h2[@id="studentSettingsPageTitle"])')));
        $this->assertStringContainsString('data-settings-page="recovery"', $sidebarHtml);

        // The settings identify fingerprint passkey and facial verification options.
        $settingsResponse->assertSee('id="studentBiometricChoices"', false);
        $biometricChoices = $xpath->query('//*[@id="studentBiometricChoices"]//h3');
        $choiceTitles = [];
        foreach ($biometricChoices as $choice) {
            $choiceTitles[] = trim($choice->textContent ?? '');
        }
        $this->assertSame(['Fingerprint / Device Passkey', 'Face Verification'], $choiceTitles);

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
        $this->assertStringNotContainsString('Back to app', $dashSidebarText);
    }

    public function test_student_phone_editor_is_opened_from_password_and_email_and_can_save(): void
    {
        $student = $this->createStudent();
        $this->actingAs($student);
        $response = $this->get(route('settings'))->assertOk();
        $dom = new \DOMDocument();
        $dom->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);

        $this->assertSame(1, $xpath->query('//*[@id="tab-profile"]//input[@name="phone"]')->length);
        $this->assertSame(route('settings.update'), $xpath->evaluate('string(//*[@id="tab-profile"]//form[@id="personalInfoForm"]/@action)'));

        $phone = '+63 900 000-0012';
        $this->post(route('settings.update'), ['phone' => $phone])->assertRedirect(route('settings'));
        $this->assertSame($phone, $student->fresh()->phone);
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

    public function test_student_dashboard_prioritizes_current_class_actions_and_announcements(): void
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
                ->assertSee('Operating Systems')
                ->assertSee('Room 202')
                ->assertSee('Computer Networks')
                ->assertSee('Important Laboratory Guideline')
                ->assertSee(route('mobile.scan', ['open_scanner' => 1]), false)
                ->assertSee(route('mobile.scan', ['open_code' => 1]), false)
                ->assertDontSee('data-sched-filter=', false)
                ->assertSee('css/student-frontend.css', false)
                ->assertSee('id="cmdPaletteOverlay"', false)
                ->assertSee('id="cmdPaletteInput"', false);

            $frontendCss = file_get_contents(public_path('css/student-frontend.css'));
            $this->assertNotFalse($frontendCss);
            $this->assertStringContainsString('.sf-cmd-input', $frontendCss);
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_student_dashboard_shows_recent_attendance_and_pending_excuse_requests(): void
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
            ->assertSee('student-frontend.css', false)
            ->assertSee('sf-dash-grid', false)
            ->assertSee('sf-identity-strip', false)
            ->assertSee('Class schedule', false)
            ->assertSee('Recent attendance')
            ->assertSee('Excuse requests')
            ->assertSee('Medical')
            ->assertSee(route('excuses'), false)
            ->assertSee('CS401', false)
            ->assertDontSee('7-Day Pulse')
            ->assertDontSee('id="daySummaryModal"', false);
    }

    public function test_mobile_home_redirects_to_actionable_student_dashboard(): void
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

        $this->actingAs($student)->get(route('mobile.home'))
            ->assertRedirect(route('home'));

        $response = $this->followingRedirects()->get(route('mobile.home'));

        $response->assertOk()
            ->assertSee('student-frontend.css', false)
            ->assertSee('Class schedule', false)
            ->assertSee('Recent attendance')
            ->assertDontSee('id="quickActionScanBtn"', false)
            ->assertSee('Mobile Systems')
            ->assertSee('Mobile Campus Advisory');
    }

    public function test_student_dashboard_accessibility_tokens_and_live_regions_are_available(): void
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
            route('student.classes'),
            route('student.schedule'),
            route('student.calendar'),
            route('excuses'),
            route('excuses.create_general'),
            route('notifications'),
            route('profile'),
            route('mobile.scan'),
            route('settings'),
            route('settings.biometrics.face'),
            route('settings.biometrics.fingerprint'),
        ];

        foreach ($studentRoutes as $url) {
            $this->actingAs($student)
                ->get($url)
                ->assertOk()
                ->assertSee('student-frontend.css', false);
        }

        $this->actingAs($student)
            ->followingRedirects()
            ->get(route('mobile.home'))
            ->assertOk()
            ->assertSee('student-frontend.css', false);

        $this->actingAs($student)
            ->get(route('mobile.profile'))
            ->assertRedirect(route('profile'));

        // mobile.history redirects cleanly to the unified calendar & records view
        $this->actingAs($student)
            ->get(route('mobile.history'))
            ->assertRedirect(route('student.calendar').'#attendance-records');

        // Verify rebuilt semantic components render on key subpages
        $this->actingAs($student)
            ->get(route('excuses'))
            ->assertOk()
            ->assertSee('id="sfOpenExcuseModalBtn"', false)
            ->assertSee('id="sfSubmitExcuseModal"', false);

        $this->actingAs($student)
            ->get(route('excuses.create_general'))
            ->assertOk()
            ->assertSee('id="excuseForm"', false)
            ->assertSee('id="subjectsGrid"', false);

        $this->actingAs($student)
            ->get(route('profile'))
            ->assertOk()
            ->assertDontSee('sd-profile-quick-link', false)
            ->assertSee('Academic Record', false)
            ->assertSee('Attendance Summary', false);

        // Verify non-student portals (Teacher) do NOT load the student frontend stylesheet
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
            ->assertDontSee('student-frontend.css', false);
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
            ->assertSee('Biometric Verification', false)
            ->assertSee('bio-face-studio-grid', false)
            ->assertSee('bio-viewfinder-corner', false)
            ->assertSee('bio-consent-card', false)
            ->assertSee('id="cameraFaceStartBtn"', false)
            ->assertSee('id="cameraFaceConsentCheckbox"', false);

        // Fingerprint registration studio
        $this->actingAs($student)
            ->get(route('settings.biometrics.fingerprint'))
            ->assertOk()
            ->assertSee('Biometric Verification', false)
            ->assertSee('bio-sensor-stage', false)
            ->assertSee('bio-sensor-pad', false)
            ->assertSee('id="startBioBtn"', false)
            ->assertSee('id="deviceList"', false);

        // Biometric studio CSS + Oxblood/Porcelain harmonization
        $bioCss = file_get_contents(public_path('css/biometric-flow.css'));
        $this->assertNotFalse($bioCss);
        $this->assertStringContainsString('--bio-canvas: var(--sf-bg, #0C0709);', $bioCss);
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

        // Calendar page exposes CSV export and has no stale golden border copy
        $this->actingAs($student)
            ->get(route('student.calendar'))
            ->assertOk()
            ->assertSee(route('student.calendar.export-csv'), false)
            ->assertDontSee('The <strong>golden border</strong> marks today', false);

        // The dedicated scanner shares the student layout in standalone scanner mode without bottom dock.
        $this->actingAs($student)
            ->get(route('mobile.scan'))
            ->assertOk()
            ->assertSee('id="scannerPageTitle"', false)
            ->assertSee('id="tabCodeMode"', false)
            ->assertDontSee('id="mobileBottomNav"', false)
            ->assertDontSee('class="sd-scan-desktop-bar', false);

        // Schedule route renders unified Classes & Schedule view cleanly
        $this->actingAs($student)
            ->get(route('student.schedule'))
            ->assertOk()
            ->assertSee('sfClassesWeeklyPanel', false)
            ->assertSee('sf-segmented', false);

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

        // Student password change, pending approval, and QR result render cleanly
        $this->actingAs($student)
            ->get(route('password.change.form'))
            ->assertOk()
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
            ->assertSee('student-frontend.css', false)
            ->assertSee('data-student-theme="dark"', false)
            ->assertDontSee('rgba(25,15,13,.94)', false);

        $qrResultHtml = view('qr.result', ['status' => 'success', 'message' => 'Recorded'])->render();
        $this->assertStringContainsString('sf-card', $qrResultHtml);
        $this->assertStringContainsString('sfConfettiBurst', $qrResultHtml);
        $this->assertStringNotContainsString('#0f172a', $qrResultHtml);

        auth()->logout();
        $this->flushSession();
        $this->get('/offline')->assertNotFound();

        // Verify Dark Oxblood + Light Porcelain dual-theme tokens in student-frontend.css
        $frontendCss = file_get_contents(public_path('css/student-frontend.css'));
        $this->assertNotFalse($frontendCss);
        $this->assertStringContainsString('--sf-bg: #0C0709;', $frontendCss);
        $this->assertStringContainsString('--sf-surface: #160D10;', $frontendCss);
        $this->assertStringContainsString('[data-student-theme="light"]', $frontendCss);
        $this->assertStringContainsString('--sf-bg: #F7F3F4;', $frontendCss);
        $this->assertStringContainsString('.sf-cmd-card', $frontendCss);
        $this->assertStringContainsString('.sf-drawer', $frontendCss);
        $this->assertStringNotContainsString('background-attachment: fixed', $frontendCss);

        // Admin, Teacher & Shared design tokens stay intact
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
        $response->assertSee('Privacy safeguard:', false)
            ->assertSee('Students cannot remove a guardian link', false);
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
            ->assertSee('aria-label="Copy Student ID"', false)
            ->assertSee('sf-academic-grid', false)
            ->assertSee('sf-setting-row', false)
            ->assertSee('sf-theme-picker', false);

        $frontendCss = file_get_contents(public_path('css/student-frontend.css'));
        $this->assertNotFalse($frontendCss);
        $this->assertStringContainsString('.sf-academic-grid', $frontendCss);
        $this->assertStringContainsString('.sf-setting-row', $frontendCss);
        $this->assertStringContainsString('.sf-switch', $frontendCss);
    }

    public function test_student_settings_profile_photo_card_avoids_profile_summary_collision_and_provides_change_photo_action(): void
    {
        $student = $this->createStudent([
            'email' => 'student.photocard@gmail.com',
            'student_number' => '2310823',
        ]);

        $this->actingAs($student)
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('class="sf-avatar-cam-btn"', false)
            ->assertSee('for="ppmFileInput"', false)
            ->assertSee('Change photo', false)
            ->assertDontSee('id="ppmCameraBadge"', false)
            ->assertDontSee('id="ppmHoverOverlay"', false);

        // Standalone student profile page still keeps its summary hero intact
        $this->actingAs($student)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('aria-label="Student profile summary"', false);
    }

    public function test_student_sidebar_omits_redundant_section_labels(): void
    {
        $student = $this->createStudent([
            'email' => 'student.nolabels@gmail.com',
            'student_number' => '2310824',
        ]);

        $this->actingAs($student)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('sf-sidebar-nav', false)
            ->assertSee('Classes &amp; Schedule', false)
            ->assertSee('Attendance &amp; Calendar', false)
            ->assertDontSee('My workspace', false)
            ->assertDontSee('Attendance &amp; school', false)
            ->assertDontSee('student-nav-label nav-section-label', false);

        $sidebarTemplate = file_get_contents(resource_path('views/layouts/sidebars/student.blade.php'));
        $this->assertNotFalse($sidebarTemplate);
        $this->assertStringNotContainsString('nav-section-label', $sidebarTemplate);
    }

    public function test_student_calendar_csv_export_downloads_sanitized_attendance_rows(): void
    {
        $student = $this->createStudent([
            'email' => 'student.csvexport@gmail.com',
            'student_number' => '2310890',
        ]);

        $subject = Subject::create([
            'code' => 'CS450',
            'name' => '=HYPERLINK("http://evil.test","Click")',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
        ]);

        Attendance::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'subject_code' => $subject->code,
            'date' => now()->toDateString(),
            'time_in' => '08:15:00',
            'status' => 'Present',
            'excused' => false,
        ]);

        $response = $this->actingAs($student)->get(route('student.calendar.export-csv'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Date,"Subject Code","Subject Name",Instructor,"Clock In","Clock Out",Method,Status,Remarks', $csv);
        $this->assertStringContainsString('CS450', $csv);
        // Formula-injection prefix must be neutralized with leading single quote.
        $this->assertStringContainsString("'=HYPERLINK(", $csv);

        // Non-student cannot access student CSV export.
        $this->flushSession();
        $teacher = $this->createStudent([
            'role' => 'teacher',
            'student_number' => null,
            'employee_id' => 'TCH-CSV-1',
            'email' => 'teacher.csv@gmail.com',
        ]);
        $this->actingAs($teacher)->get(route('student.calendar.export-csv'))->assertRedirect();
    }
}
