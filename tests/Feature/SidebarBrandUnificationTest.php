<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SidebarBrandUnificationTest extends TestCase
{
    use RefreshDatabase;

    private function createSyntheticUser(string $role, array $overrides = []): User
    {
        static $sequence = 0;
        $sequence++;

        return User::create(array_merge([
            'name' => ucfirst($role) . ' Sidebar Unification User ' . $sequence,
            'student_number' => $role === 'student' ? '2026-SB-' . $sequence : null,
            'employee_id' => in_array($role, ['admin', 'teacher'], true) ? strtoupper($role) . '-SB-' . $sequence : null,
            'course' => $role === 'student' ? 'BSCS' : 'FACULTY',
            'year_level' => '1',
            'semester' => '1',
            'email' => $role . '.sidebar.' . $sequence . '.' . uniqid() . '@example.test',
            'password' => Hash::make('StrongPass#12345'),
            'role' => $role,
            'admin_sub_role' => $role === 'admin' ? 'super_admin' : null,
            'is_active' => true,
        ], $overrides));
    }

    public function test_admin_guardian_and_teacher_sidebars_match_student_brand_header(): void
    {
        $appName = e((string) config('app.name'));

        $cases = [
            ['admin', route('admin.dashboard'), 'admin-sidebar-brand'],
            ['admin', route('admin.settings'), 'admin-sidebar-brand'],
            ['parent', route('parent.dashboard'), 'parent-sidebar-brand'],
            ['parent', route('parent.profile'), 'parent-sidebar-brand'],
            ['teacher', route('teacher.dashboard'), 'teacher-sidebar-brand'],
            ['teacher', route('teacher.profile'), 'settings-sidebar-brand'],
        ];

        foreach ($cases as [$role, $url, $expectedClass]) {
            $this->flushSession();
            $user = $this->createSyntheticUser($role);
            $request = $this->actingAs($user);
            if ($role === 'admin') {
                $request = $request->withSession(['admin_2fa_verified' => true]);
            }
            $html = $request->get($url)->assertOk()->getContent();

            $this->assertMatchesRegularExpression('/<aside\b[^>]*\bid="sidebar"/', $html, "Missing sidebar on {$url}");
            preg_match('/<aside\b[^>]*\bid="sidebar"[^>]*>(.*?)<\/aside>/s', $html, $matches);
            $sidebarHtml = $matches[1] ?? '';

            $this->assertNotEmpty($sidebarHtml, "Empty sidebar content on {$url}");
            $this->assertStringContainsString($expectedClass, $sidebarHtml, "Missing {$expectedClass} on {$url}");
            $this->assertStringContainsString('class="sidebar-logo"', $sidebarHtml, "Missing logo on {$url}");
            $this->assertStringContainsString('<div class="sidebar-brand">' . $appName . '</div>', $sidebarHtml, "Missing brand name on {$url}");
            $this->assertStringContainsString('<div class="sidebar-divider"></div>', $sidebarHtml, "Missing divider on {$url}");
            $this->assertStringNotContainsString('sidebar-subtitle', $sidebarHtml, "Unexpected sidebar-subtitle on {$url}");
            $this->assertStringNotContainsString('admin-workspace-role', $sidebarHtml, "Unexpected admin-workspace-role on {$url}");
            $this->assertStringContainsString('Plus+Jakarta+Sans', $html, "Missing Plus Jakarta Sans font on {$url}");
            $this->assertStringContainsString('portal-layout-refinements.css', $html, "Missing unified CSS on {$url}");
        }

        // Student portal uses the dedicated Oxblood/Porcelain shell sidebar brand & Inter + JetBrains Mono typography.
        foreach ([route('home'), route('settings')] as $studentUrl) {
            $this->flushSession();
            $student = $this->createSyntheticUser('student');
            $html = $this->actingAs($student)->get($studentUrl)->assertOk()->getContent();

            $this->assertMatchesRegularExpression('/<aside\b[^>]*\bid="sidebar"/', $html, "Missing sidebar on {$studentUrl}");
            preg_match('/<aside\b[^>]*\bid="sidebar"[^>]*>(.*?)<\/aside>/s', $html, $matches);
            $sidebarHtml = $matches[1] ?? '';

            $this->assertNotEmpty($sidebarHtml, "Empty sidebar content on {$studentUrl}");
            $this->assertStringContainsString('class="sf-brand"', $sidebarHtml);
            $this->assertStringContainsString('class="sf-brand-logo"', $sidebarHtml);
            $this->assertStringContainsString('<span class="sf-brand-title">' . $appName . '</span>', $sidebarHtml);
            $this->assertStringContainsString('family=Inter', $html);
            $this->assertStringContainsString('JetBrains+Mono', $html);
            $this->assertStringContainsString('css/student-frontend.css', $html);
        }
    }

    public function test_shared_stylesheet_applies_student_sidebar_header_geometry_and_collapsed_state(): void
    {
        $sharedCss = file_get_contents(public_path('css/portal-layout-refinements.css'));
        $adminRedesignCss = file_get_contents(public_path('css/admin-portal-redesign.css'));
        $adminSimpleCss = file_get_contents(public_path('css/admin-simple.css'));
        $parentRedesignCss = file_get_contents(public_path('css/parent-portal-redesign.css'));
        $teacherMasterCss = file_get_contents(public_path('css/teacher-portal-master.css'));

        $this->assertIsString($sharedCss);
        $this->assertStringContainsString('body:is(.admin-portal, .parent-portal, .teacher-portal) .sidebar .sidebar-head', $sharedCss);
        $this->assertStringContainsString('flex-direction: row !important;', $sharedCss);
        $this->assertStringContainsString('width: 34px !important;', $sharedCss);
        $this->assertStringContainsString('text-transform: none !important;', $sharedCss);
        $this->assertStringContainsString('body:is(.admin-portal, .parent-portal, .teacher-portal) .sidebar.collapsed .sidebar-text', $sharedCss);
        $this->assertStringContainsString('display: none !important;', $sharedCss);

        // Stale role-specific sidebar brand overrides are removed to keep Student parity clean.
        $this->assertStringNotContainsString('.sidebar-head', $adminRedesignCss);
        $this->assertStringNotContainsString('.sidebar-head', $adminSimpleCss);
        $this->assertStringNotContainsString('.sidebar-head', $parentRedesignCss);
        $this->assertStringNotContainsString('.sidebar-head', $teacherMasterCss);
        $this->assertStringNotContainsString('.teacher-sidebar-brand', $teacherMasterCss);
    }

    public function test_all_roles_standardize_sidebar_width_and_offsets_to_student_reference(): void
    {
        $tokensCss = file_get_contents(public_path('css/design-tokens.css'));
        $premiumCss = file_get_contents(public_path('css/premium.css'));
        $adminSaasCss = file_get_contents(public_path('css/admin-saas.css'));
        $sharedCss = file_get_contents(public_path('css/portal-layout-refinements.css'));
        $studentDesktopCss = file_get_contents(public_path('css/student-desktop.css'));
        $studentUxCss = file_get_contents(public_path('css/student-desktop-ux.css'));

        $this->assertIsString($tokensCss);
        $this->assertStringContainsString('--ds-sidebar-width: 248px;', $tokensCss);
        $this->assertStringContainsString('--ds-sidebar-mini:  72px;', $tokensCss);

        $this->assertIsString($premiumCss);
        $this->assertStringContainsString('--sidebar-width: var(--ds-sidebar-width, 248px);', $premiumCss);
        $this->assertStringContainsString('--sidebar-mini: var(--ds-sidebar-mini, 72px);', $premiumCss);
        $this->assertStringNotContainsString('--sidebar-width: 280px;', $premiumCss);
        $this->assertStringNotContainsString('--sidebar-mini: 80px;', $premiumCss);

        $this->assertIsString($adminSaasCss);
        $this->assertStringContainsString('width: var(--sidebar-width, 248px);', $adminSaasCss);
        $this->assertStringContainsString('margin-left: var(--sidebar-width, 248px);', $adminSaasCss);

        $this->assertIsString($sharedCss);
        $this->assertStringContainsString('@media screen and (min-width: 768.02px)', $sharedCss);
        $this->assertStringContainsString('body:is(.admin-portal, .parent-portal, .teacher-portal) .sidebar {', $sharedCss);
        $this->assertStringContainsString('width: var(--sidebar-width, 248px) !important;', $sharedCss);
        $this->assertStringContainsString('width: var(--sidebar-mini, 72px) !important;', $sharedCss);
        $this->assertStringContainsString('left: var(--sidebar-width, 248px) !important;', $sharedCss);
        $this->assertStringContainsString('left: var(--sidebar-mini, 72px) !important;', $sharedCss);
        $this->assertStringContainsString('padding-left: var(--sidebar-width, 248px) !important;', $sharedCss);
        $this->assertStringContainsString('padding-left: var(--sidebar-mini, 72px) !important;', $sharedCss);

        $this->assertIsString($studentDesktopCss);
        $this->assertStringContainsString('--sidebar-width: var(--ds-sidebar-width, 248px);', $studentDesktopCss);
        $this->assertStringContainsString('--sidebar-mini: var(--ds-sidebar-mini, 72px);', $studentDesktopCss);

        $this->assertIsString($studentUxCss);
        $this->assertStringContainsString('width: 248px !important;', $studentUxCss);
        $this->assertStringContainsString('width: 72px !important;', $studentUxCss);

        foreach ([
            ['admin', route('admin.dashboard')],
            ['parent', route('parent.dashboard')],
            ['teacher', route('teacher.dashboard')],
        ] as [$role, $url]) {
            $this->flushSession();
            $user = $this->createSyntheticUser($role);
            $request = $this->actingAs($user);
            if ($role === 'admin') {
                $request = $request->withSession(['admin_2fa_verified' => true]);
            }
            $html = $request->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('css/design-tokens.css', $html, "Missing design-tokens.css on {$url}");
            $this->assertStringContainsString('css/premium.css', $html, "Missing premium.css on {$url}");
            $this->assertStringContainsString('css/portal-layout-refinements.css', $html, "Missing portal-layout-refinements.css on {$url}");
        }

        $this->flushSession();
        $student = $this->createSyntheticUser('student');
        $studentHtml = $this->actingAs($student)->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('css/student-frontend.css', $studentHtml);
        $this->assertStringContainsString('js/student-frontend.js', $studentHtml);
        $frontendCss = file_get_contents(public_path('css/student-frontend.css'));
        $this->assertIsString($frontendCss);
        $this->assertStringContainsString('--sf-sidebar-w:', $frontendCss);
        $this->assertStringContainsString('--sf-sidebar-mini-w:', $frontendCss);
    }
}
