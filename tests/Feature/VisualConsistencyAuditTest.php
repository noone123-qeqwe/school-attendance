<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VisualConsistencyAuditTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role) . ' Visual Audit User',
            'student_number' => $role === 'student' ? '2026-VIS-1' : null,
            'employee_id' => $role !== 'student' ? strtoupper($role) . '-VIS-1' : null,
            'course' => $role === 'student' ? 'BSCS' : 'FACULTY',
            'year_level' => '1',
            'semester' => '1',
            'email' => $role . '.visual@example.test',
            'password' => Hash::make('StrongPass#12345'),
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    public function test_header_avatar_triggers_render_without_rectangular_box_wrappers(): void
    {
        $student = $this->createUser('student');

        $homeHtml = $this->actingAs($student)->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('header-account-trigger', $homeHtml);
        $this->assertStringContainsString('header-profile-img', $homeHtml);
        $this->assertStringContainsString('sd-avatar-ring', $homeHtml);

        $settingsHtml = $this->actingAs($student)->get(route('settings'))->assertOk()->getContent();
        $this->assertStringContainsString('header-account-trigger', $settingsHtml);
        $this->assertStringContainsString('sidebar-logo', $settingsHtml);

        $mobileHomeHtml = $this->actingAs($student)->get(route('mobile.home'))->assertOk()->getContent();
        $this->assertStringContainsString('header-avatar-pill', $mobileHomeHtml);
        $this->assertStringContainsString('padding: 0;', $mobileHomeHtml);
        $this->assertStringNotContainsString('padding: 2px;', $mobileHomeHtml);
    }

    public function test_login_page_renders_glass_logo_without_decorative_ring(): void
    {
        $loginHtml = $this->get(route('login'))->assertOk()->getContent();
        $this->assertStringContainsString('glass-logo', $loginHtml);
    }

    public function test_shared_stylesheets_strip_redundant_avatar_logo_and_icon_containers(): void
    {
        $masterCss = file_get_contents(public_path('css/student-portal-master.css'));
        $navCss = file_get_contents(public_path('css/student-portal-navigation.css'));
        $mobileCss = file_get_contents(public_path('css/mobile-enterprise.css'));
        $modernCss = file_get_contents(public_path('css/system-ui-modern.css'));
        $premiumCss = file_get_contents(public_path('css/premium.css'));
        $adminThemeCss = file_get_contents(public_path('css/admin-theme.css'));
        $desktopUxCss = file_get_contents(public_path('css/student-desktop-ux.css'));
        $dashboardCss = file_get_contents(public_path('css/student-dashboard.css'));
        $loginCss = file_get_contents(public_path('css/auth-login.css'));
        $settingsCss = file_get_contents(public_path('css/settings-page.css'));
        $desktopSettingsCss = file_get_contents(public_path('css/student-desktop-settings.css'));

        foreach ([
            $masterCss,
            $navCss,
            $mobileCss,
            $modernCss,
            $premiumCss,
            $adminThemeCss,
            $desktopUxCss,
            $dashboardCss,
            $loginCss,
            $settingsCss,
            $desktopSettingsCss,
        ] as $css) {
            $this->assertIsString($css);
        }

        // Student header avatar trigger must not sit in a boxed rectangular outline.
        $this->assertStringContainsString('body.student-portal .top-header :is(.burger-btn, .notif-btn, #headerSearchBar, .mobile-back-btn)', $masterCss);
        $this->assertMatchesRegularExpression('/body\.student-portal\s+\.top-header\s+\.header-account-trigger\s*\{[^}]*border:\s*none\s*!important;[^}]*border-radius:\s*999px\s*!important;/s', $masterCss);
        $this->assertStringContainsString('body.student-portal .top-header .header-account-trigger:focus-visible', $masterCss);
        $this->assertMatchesRegularExpression('/body\.student-portal\s+\.top-header\s+\.header-profile-img\s*\{[^}]*border-radius:\s*50%\s*!important;/s', $masterCss);
        $this->assertMatchesRegularExpression('/body\.student-mobile-portal\s+\.header-avatar-pill\s*\{[^}]*border:\s*none\s*!important;[^}]*border-radius:\s*50%\s*!important;/s', $masterCss);

        // Desktop student nav preserves circular avatar without outer bordered box.
        $this->assertStringContainsString('body.student-portal .top-header :is(.burger-btn, .notif-btn)', $navCss);
        $this->assertMatchesRegularExpression('/body\.student-portal\s+\.top-header\s+\.header-profile-img\s*\{[^}]*border-radius:\s*50%;/s', $navCss);
        $this->assertDoesNotMatchRegularExpression('/body\.student-portal\s+\.top-header\s+\.header-profile-img\s*\{[^}]*border-radius:\s*7px;/s', $navCss);

        // Mobile header trigger preserves 40x40 invisible circular hit target around circular avatar.
        $this->assertStringContainsString('.header-right .header-account-trigger', $mobileCss);
        $this->assertStringContainsString('.header-right .header-account-trigger:not(:focus-visible)', $mobileCss);

        // Mobile app header avoids 12px border-radius clipping on avatar trigger, and removes boxed nav/setting icon chrome.
        $this->assertStringContainsString('.header-btn-right:not(.header-avatar-pill)', $modernCss);
        $this->assertStringContainsString('.mobile-app-layout .header-btn-right.header-avatar-pill', $modernCss);
        $this->assertStringContainsString('body.student-portal .sidebar .nav-link > i', $modernCss);

        // School crests stay borderless without artificial rings or tilted hover transforms.
        $this->assertMatchesRegularExpression('/\.sidebar-logo\s*\{[^}]*border:\s*none;/s', $premiumCss);
        $this->assertDoesNotMatchRegularExpression('/\.sidebar-logo:hover\s*\{[^}]*rotate/s', $premiumCss);
        $this->assertMatchesRegularExpression('/\.sidebar-logo\s*\{[^}]*background:\s*transparent\s*!important;/s', $adminThemeCss);
        $this->assertMatchesRegularExpression('/body\.student-portal\s+\.sidebar\s+\.sidebar-logo\s*\{[^}]*border:\s*none;/s', $desktopUxCss);
        $this->assertMatchesRegularExpression('/\.glass-logo\s*\{[^}]*border:\s*none;[^}]*box-shadow:\s*none;/s', $loginCss);

        // Dashboard hero avatar & settings icon containers drop nested frame/bevel boxes.
        $this->assertMatchesRegularExpression('/\.sd-avatar-ring\s*\{[^}]*padding:\s*0;[^}]*border-radius:\s*50%;/s', $dashboardCss);
        $this->assertMatchesRegularExpression('/\.sd-avatar-ring\s+img\s*\{[^}]*border-radius:\s*50%;/s', $dashboardCss);
        $this->assertMatchesRegularExpression('/\.sc-icon\s*\{[^}]*border:\s*none;/s', $settingsCss);
        $this->assertMatchesRegularExpression('/\.sec-card-icon\s*\{[^}]*border:\s*none\s*!important;/s', $settingsCss);
        $this->assertMatchesRegularExpression('/\.student-biometric-choices\s*>\s*a\s*>\s*i:first-child\s*\{[^}]*border:\s*none;/s', $desktopSettingsCss);
    }
}
