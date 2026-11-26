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
        $this->assertStringContainsString('class="mobile-back-btn d-md-none"', $html);
        $this->assertStringContainsString('id="settingsBackToDashboardLink"', $html);
        $this->assertStringContainsString('id="studentSettingsPageTitle"', $html);

        $jsPath = public_path('js/student-settings.js');
        $js = file_get_contents($jsPath);
        $this->assertIsString($js);
        $this->assertStringContainsString('history.replaceState', $js);
        $this->assertStringNotContainsString('history.pushState', $js);

        $cssPath = public_path('css/student-settings.css');
        $css = file_get_contents($cssPath);
        $this->assertIsString($css);
        $this->assertStringContainsString(':not([data-student-view^="change-"]) .student-settings-detail-heading h2', $css);
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

    public function test_mobile_home_menu_deduplicates_navigation_links(): void
    {
        $student = $this->createUser('student');

        $response = $this->actingAs($student)->get(route('mobile.home'));
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('seenPaths.has(normalizeMenuPath', $html);
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
            ->assertSee('ui-back-link', false)
            ->assertSee('d-none d-md-inline-flex', false);

        $this->flushSession();
        auth()->logout();
        $this->get('/non-existent-audit-route')->assertNotFound();
    }
}
