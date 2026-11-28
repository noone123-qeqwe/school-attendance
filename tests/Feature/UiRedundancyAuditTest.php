<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UiRedundancyAuditTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role) . ' Audit User',
            'student_number' => $role === 'student' ? '2026-AUDIT-1' : null,
            'employee_id' => $role !== 'student' ? strtoupper($role) . '-AUDIT-1' : null,
            'course' => $role === 'student' ? 'BSCS' : 'FACULTY',
            'year_level' => '1',
            'semester' => '1',
            'email' => $role . '.audit@example.test',
            'password' => Hash::make('StrongPass#12345'),
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    public function test_student_settings_removes_duplicate_mobile_back_button_and_keeps_desktop_and_header_navigation(): void
    {
        $student = $this->createUser('student');

        $response = $this->actingAs($student)->get(route('settings'));
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringNotContainsString('settings-mobile-back-chip', $html);
        $this->assertStringNotContainsString('mobile-sidebar-back-btn', $html);
        $this->assertStringContainsString('id="sfMobileSettingsBackBtn"', $html);
        $this->assertStringContainsString('class="sf-mobile-settings-hub"', $html);
        $this->assertStringContainsString('id="settingsBackToDashboardLink"', $html);
        $this->assertStringContainsString('id="studentSettingsPageTitle"', $html);

        $jsPath = public_path('js/student-settings.js');
        $js = file_get_contents($jsPath);
        $this->assertIsString($js);
        $this->assertStringContainsString('history.replaceState', $js);
        $this->assertStringNotContainsString('history.pushState', $js);
    }

    public function test_biometrics_fingerprint_setup_omits_duplicate_back_to_settings_link(): void
    {
        $student = $this->createUser('student');

        $response = $this->actingAs($student)->get(route('settings.biometrics.fingerprint'));
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('Back to Biometrics', $html);
        $this->assertStringContainsString('Set up Face Verification', $html);
        $this->assertStringNotContainsString('Back to Settings', $html);
    }

    public function test_role_bottom_navigation_stays_synchronized_without_repeating_primary_links(): void
    {
        $cases = [
            ['teacher', 'teacher.dashboard', 'teacher.profile', ['Home', 'Classes', 'QR', 'Calendar', 'Menu'], []],
            ['parent', 'parent.dashboard', 'parent.profile', ['Home', 'Calendar', 'Alerts', 'Menu'], []],
            ['admin', 'admin.dashboard', 'admin.profile', ['Home', 'Academic', 'Reports', 'Alerts', 'Menu'], ['admin_sub_role' => 'super_admin']],
        ];

        foreach ($cases as [$role, $dashboardRoute, $profileRoute, $expectedLabels, $overrides]) {
            $user = $this->createUser($role, $overrides);
            $this->actingAs($user)
                ->get(route('mobile.home'))
                ->assertRedirect(route($dashboardRoute));
            $this->get(route('mobile.profile'))
                ->assertRedirect(route($profileRoute));

            $response = $this->actingAs($user)
                ->withSession($role === 'admin' ? ['admin_2fa_verified' => true] : [])
                ->get(route($dashboardRoute))
                ->assertOk();
            $dom = new \DOMDocument();
            $dom->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($dom);
            $nav = $xpath->query('//nav[@id="mobileBottomNav"]')->item(0);
            $this->assertNotNull($nav, "{$role} dashboard should render shared mobile navigation.");

            $labels = [];
            foreach ($xpath->query('./a/span[last()] | ./button/span[last()]', $nav) as $label) {
                $labels[] = trim(preg_replace('/\s+/', ' ', $label->textContent));
            }
            $this->assertSame($expectedLabels, $labels, "{$role} mobile tabs should match its desktop workspace.");

            $primaryPaths = array_map(
                fn ($node) => parse_url($node->nodeValue, PHP_URL_PATH),
                iterator_to_array($xpath->query('//nav[@id="mobileBottomNav"]/a/@href')),
            );
            $morePaths = array_map(
                fn ($node) => parse_url($node->nodeValue, PHP_URL_PATH),
                iterator_to_array($xpath->query('//*[@id="moreSheetContent"]//a[@href]/@href')),
            );
            $this->assertSame([], array_values(array_intersect($primaryPaths, $morePaths)), "{$role} More sheet should not repeat primary tabs.");
            $this->flushSession();
        }
    }

    public function test_student_classes_schedule_removes_profile_summary_and_export_actions(): void
    {
        $student = $this->createUser('student');
        $html = $this->actingAs($student)->get(route('student.classes'))->assertOk()->getContent();

        foreach ([
            'Total Units',
            'Meeting Today',
            'Download PDF',
            'Print Schedule',
            'id="downloadPdfBtn"',
            'id="printCorBtn"',
            'cor-summary-chips-row',
        ] as $removedContent) {
            $this->assertStringNotContainsString($removedContent, $html);
        }

        $this->assertStringContainsString('id="sfClassesListPanel"', $html);
        $this->assertStringContainsString('id="sfClassesWeeklyPanel"', $html);
        $this->assertStringContainsString('id="sfMobileCoursesList"', $html);
        $this->assertStringContainsString('id="scheduleSearchInput"', $html);
    }

    public function test_parent_profile_and_subpages_remove_redundant_controls(): void
    {
        $parent = $this->createUser('parent');

        $profile = $this->actingAs($parent)->get(route('parent.profile'));
        $profile->assertOk();
        $profileHtml = $profile->getContent();
        $this->assertStringNotContainsString('stabs-floating-hint', $profileHtml);
        $this->assertStringNotContainsString('stabsFloatingHint', $profileHtml);
        $this->assertStringNotContainsString('Manage passkeys', $profileHtml);

        $linkChild = $this->actingAs($parent)->get(route('parent.link.form'));
        $linkChild->assertOk();
        $linkHtml = $linkChild->getContent();
        $this->assertStringContainsString('class="mobile-back-btn d-md-none"', $linkHtml);
        $this->assertStringContainsString('ui-back-link', $linkHtml);
        $this->assertStringContainsString('d-none d-md-inline-flex', $linkHtml);

        $generalExcuse = $this->actingAs($parent)->get(route('parent.excuses.create_general'));
        $generalExcuse->assertOk();
        $generalHtml = $generalExcuse->getContent();
        $this->assertStringContainsString('d-none d-md-inline-flex', $generalHtml);
    }

    public function test_teacher_and_admin_previews_and_forms_consolidate_back_and_download_actions(): void
    {
        $teacher = $this->createUser('teacher');
        $admin = $this->createUser('admin', ['admin_sub_role' => 'super_admin']);

        foreach ([
            [$teacher, route('teacher.attendance.preview')],
            [$teacher, route('teacher.students.preview')],
        ] as [$user, $url]) {
            $response = $this->actingAs($user)->get($url);
            $this->assertSame(200, $response->getStatusCode(), "GET {$url} redirected to " . ($response->headers->get('Location') ?? 'none'));
            $html = $response->getContent();

            $this->assertStringNotContainsString('Back to List', $html);
            $this->assertSame(1, substr_count($html, 'Download PDF'), "Expected single Download PDF CTA on {$url}");
            $this->assertStringContainsString('ui-back-link', $html);
            $this->assertStringContainsString('d-none d-md-inline-flex', $html);
        }

        $this->actingAs($teacher)->get(route('teacher.subjects.create'))
            ->assertOk()
            ->assertSee('class="mobile-back-btn d-md-none"', false)
            ->assertSee('ui-back-link', false);

        $this->actingAs($teacher)->get(route('teacher.excuses.create'))
            ->assertOk()
            ->assertSee('class="mobile-back-btn d-md-none"', false)
            ->assertSee('ui-back-link', false);

        $this->flushSession();

        foreach ([
            route('admin.attendance.preview'),
            route('admin.students.preview'),
        ] as $url) {
            $response = $this->actingAs($admin)
                ->withSession(['admin_2fa_verified' => true])
                ->get($url);
            $this->assertSame(200, $response->getStatusCode(), "GET {$url} redirected to " . ($response->headers->get('Location') ?? 'none'));
            $html = $response->getContent();

            $this->assertStringNotContainsString('Back to List', $html);
            $this->assertSame(1, substr_count($html, 'Download PDF'), "Expected single Download PDF CTA on {$url}");
            $this->assertStringContainsString('ui-back-link', $html);
            $this->assertStringContainsString('d-none d-md-inline-flex', $html);
        }

        foreach ([
            route('admin.student.create'),
            route('admin.teacher.create'),
            route('admin.admin.create'),
        ] as $url) {
            $response = $this->actingAs($admin)
                ->withSession(['admin_2fa_verified' => true])
                ->get($url);
            $this->assertSame(200, $response->getStatusCode(), "GET {$url} redirected to " . ($response->headers->get('Location') ?? 'none'));
            $html = $response->getContent();
            $this->assertStringContainsString('class="mobile-back-btn d-md-none"', $html);
            $this->assertStringContainsString('ui-back-link', $html);
        }

        $this->flushSession();

        $this->actingAs($this->createUser('student', ['email' => 'excuse.audit@example.test', 'student_number' => '2026-AUDIT-2']))
            ->get(route('excuses.create_general'))
            ->assertOk()
            ->assertSee('Back to Excuses', false)
            ->assertSee(route('excuses'), false);

        $this->flushSession();
        auth()->logout();
        $this->get('/non-existent-audit-route')->assertNotFound();
    }

    public function test_student_portal_eliminates_duplicate_photo_triggers_desktop_only_mobile_controls_and_inline_css_bloat(): void
    {
        $student = $this->createUser('student', [
            'email' => 'student.dedupe@example.test',
            'student_number' => '2026-DEDUPE-1',
        ]);

        // 1. Settings: single Change photo trigger, desktop-only Compact Sidebar, cached external CSS
        $settingsHtml = $this->actingAs($student)->get(route('settings'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($settingsHtml, 'for="ppmFileInput"'));
        $this->assertStringNotContainsString('id="ppmCameraBadge"', $settingsHtml);
        $this->assertStringNotContainsString('id="ppmHoverOverlay"', $settingsHtml);
        $this->assertMatchesRegularExpression('/sf-setting-row\s+sf-desktop-only[\s\S]*id="compactToggle"/', $settingsHtml);

        preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/i', $settingsHtml, $settingsStyles);
        $settingsInlineChars = array_sum(array_map('strlen', $settingsStyles[1] ?? []));
        $this->assertLessThan(10000, $settingsInlineChars, 'Settings inline <style> bloat should be extracted to cached stylesheets.');

        // 2. Standalone Profile: single Change photo trigger and no duplicate Full Name row
        $profileHtml = $this->actingAs($student)->get(route('profile'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($profileHtml, 'for="ppmFileInput"'));
        $this->assertStringNotContainsString('id="ppmCameraBadge"', $profileHtml);
        $this->assertStringNotContainsString('<div class="info-row-label">Full Name</div>', $profileHtml);

        // 3. Dashboard: extracted external stylesheets keep inline <style> minimal
        $homeHtml = $this->actingAs($student)->get(route('home'))->assertOk()->getContent();
        preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/i', $homeHtml, $homeStyles);
        $homeInlineChars = array_sum(array_map('strlen', $homeStyles[1] ?? []));
        $this->assertLessThan(10000, $homeInlineChars, 'Dashboard inline <style> bloat should be extracted to cached stylesheets.');

        // 4. Excuses: single primary submit trigger in header & modal trigger present
        $excusesHtml = $this->actingAs($student)->get(route('excuses'))->assertOk()->getContent();
        $this->assertStringContainsString('id="sfOpenExcuseModalBtn"', $excusesHtml);
        $this->assertStringContainsString('id="sfSubmitExcuseModal"', $excusesHtml);

        // 5. CSS brace integrity & deep-link / notification counter wiring
        $frontendCss = file_get_contents(public_path('css/student-frontend.css'));
        $this->assertNotFalse($frontendCss);
        $this->assertSame(substr_count($frontendCss, '{'), substr_count($frontendCss, '}'), 'student-frontend.css must keep balanced braces.');

        $cmdBlade = file_get_contents(resource_path('views/components/command-palette.blade.php'));
        $qrVerifyBlade = file_get_contents(resource_path('views/qr/verify.blade.php'));
        $this->assertNotFalse($cmdBlade);
        $this->assertNotFalse($qrVerifyBlade);
        $this->assertStringNotContainsString("route(\"home\") }}?open_code=1", $cmdBlade);
        $this->assertStringNotContainsString("route('home') }}?open_code=1", $qrVerifyBlade);

        $this->actingAs($student)->get(route('notifications'))
            ->assertOk()
            ->assertSee('id="tabCountActive"', false)
            ->assertSee('id="tabCountUnread"', false)
            ->assertSee('id="tabCountArchived"', false);
    }

    public function test_student_motion_craft_layer_is_isolated_to_students_and_free_of_transition_all(): void
    {
        $student = $this->createUser('student', [
            'email' => 'student.motion@example.test',
            'student_number' => '2026-MOT-1',
        ]);
        $teacher = $this->createUser('teacher', [
            'email' => 'teacher.motion@example.test',
            'employee_id' => 'T-MOT-1',
        ]);

        $studentHome = $this->actingAs($student)->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('css/student-frontend.css', $studentHome);
        $this->assertStringContainsString('js/student-frontend.js', $studentHome);

        $this->flushSession();

        $teacherDash = $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString('css/student-frontend.css', $teacherDash);
        $this->assertStringNotContainsString('js/student-frontend.js', $teacherDash);

        foreach ([
            'css/student-frontend.css',
            'css/profile-photo-manager.css',
            'css/pwa-ui.css',
            'css/biometric-flow.css',
        ] as $sheet) {
            $css = file_get_contents(public_path($sheet));
            $this->assertNotFalse($css, "Missing stylesheet {$sheet}");
            $this->assertDoesNotMatchRegularExpression('/transition\s*:\s*all\b/i', $css, "Avoid transition: all in {$sheet}");
            $this->assertSame(substr_count($css, '{'), substr_count($css, '}'), "Unbalanced braces in {$sheet}");
        }

        $cmdBlade = file_get_contents(resource_path('views/components/command-palette.blade.php'));
        $ppmBlade = file_get_contents(resource_path('views/components/profile-photo-manager.blade.php'));
        $this->assertNotFalse($cmdBlade);
        $this->assertNotFalse($ppmBlade);
        $this->assertStringContainsString("setAttribute('data-instant', 'true')", $cmdBlade);
        $this->assertStringContainsString('initModalKeyboardTrap', $ppmBlade);
    }

    public function test_student_portal_applies_progressive_disclosure_across_dense_views(): void
    {
        $student = $this->createUser('student', [
            'email' => 'student.disclosure@example.test',
            'student_number' => '2026-DISC-1',
        ]);

        // 1. Calendar splits Calendar & Records into responsive tabs + CSV export + SSR events
        $calHtml = $this->actingAs($student)->get(route('student.calendar'))->assertOk()->getContent();
        $this->assertStringContainsString('id="scalTabCalendar"', $calHtml);
        $this->assertStringContainsString('id="scalTabRecords"', $calHtml);
        $this->assertStringContainsString('id="scalWorkspaceCalendar"', $calHtml);
        $this->assertStringContainsString('id="scalWorkspaceRecords"', $calHtml);
        $this->assertStringContainsString(route('student.calendar.export-csv'), $calHtml);
        $this->assertStringContainsString('ssrEvents', file_get_contents(resource_path('views/student/calendar.blade.php')) ?: '');

        // 2. Records route redirects seamlessly into Calendar's Records workspace tab
        $this->actingAs($student)->get(route('attendance.records'))->assertRedirect();

        // 3. Excuse form prioritizes Date & Reason before subject checklist
        $excuseFormHtml = $this->actingAs($student)->get(route('excuses.create_general'))->assertOk()->getContent();
        $this->assertLessThan(
            strpos($excuseFormHtml, 'Choose Subjects'),
            strpos($excuseFormHtml, 'Absence Date'),
            'Date & Reason should precede the subject checklist on the general excuse form.'
        );

        // 4. Dashboard prioritizes Class schedule and Recent attendance with speculation rules and single font stylesheet
        $homeHtml = $this->actingAs($student)->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('Class schedule', $homeHtml);
        $this->assertStringContainsString('Recent attendance', $homeHtml);
        $this->assertStringContainsString('type="speculationrules"', $homeHtml);
        $this->assertSame(1, substr_count($homeHtml, 'https://fonts.googleapis.com/css2?'));

        // 5. Notifications exposes accessible search and filter controls
        $notifHtml = $this->actingAs($student)->get(route('notifications'))->assertOk()->getContent();
        $this->assertStringContainsString('id="notifSearchInput"', $notifHtml);
        $this->assertStringContainsString('aria-label="Search notifications or subjects"', $notifHtml);

        // 6. Classes uses desktop slide-over drawer + mobile modal + list/weekly toggle
        $classesHtml = $this->actingAs($student)->get(route('student.classes'))->assertOk()->getContent();
        $this->assertStringContainsString('id="sfCourseDrawer"', $classesHtml);
        $this->assertStringContainsString('id="sfMobileCourseModal"', $classesHtml);
        $this->assertStringContainsString('id="sfClassesViewGridBtn"', $classesHtml);

        // 7. Profile exposes passkey setup and device list
        $profileHtml = $this->actingAs($student)->get(route('profile'))->assertOk()->getContent();
        $this->assertStringContainsString('id="registerFpBtn"', $profileHtml);
        $this->assertStringContainsString('id="deviceList"', $profileHtml);

        // 8. Settings exposes mobile grouped hub drill-down & appearance theme picker
        $settingsHtml = $this->actingAs($student)->get(route('settings'))->assertOk()->getContent();
        $this->assertStringNotContainsString('<div class="info-lbl academic-spec-label">Full Name</div>', $settingsHtml);
        $this->assertStringContainsString('sf-mobile-settings-hub', $settingsHtml);
        $this->assertStringContainsString('sf-theme-picker', $settingsHtml);

        $bioCss = file_get_contents(public_path('css/biometric-flow.css'));
        $this->assertNotFalse($bioCss);
        $this->assertMatchesRegularExpression('/\.bio-consent-card\s+a\s*\{[^}]*min-height:\s*44px;/s', $bioCss);

        // 9. Error 403 & standalone scanner slim CSS guarantees
        $error403 = file_get_contents(resource_path('views/errors/403.blade.php')) ?: '';
        $this->assertStringContainsString("@section('page-title', 'Access Restricted')", $error403);
        $this->assertStringContainsString('<h1 ', $error403);
        $scanHtml = $this->actingAs($student)->get(route('mobile.scan'))->assertOk()->getContent();
        $this->assertStringContainsString('id="scannerPageTitle"', $scanHtml);
        $this->assertStringNotContainsString('dashboard-enterprise.css', $scanHtml);
    }
}
