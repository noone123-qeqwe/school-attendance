<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationDropdownUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_dropdown_renders_clean_classes_and_base_styles(): void
    {
        $student = User::create([
            'name' => 'Notif Audit Student',
            'student_number' => '2026-NOTIF-1',
            'course' => 'BSCS',
            'year_level' => '1',
            'semester' => '1',
            'email' => 'student.notif@example.test',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $html = $this->actingAs($student)->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('id="topNavNotifBtn"', $html);
        $this->assertStringContainsString('class="sf-notif-dropdown"', $html);
        $this->assertStringContainsString('id="sfNotifDropdown"', $html);
        $this->assertStringContainsString('id="topNavNotifList"', $html);
        $this->assertStringContainsString('class="sf-notif-dd-empty"', $html);
        $this->assertStringContainsString('class="sf-notif-dd-foot"', $html);
        $this->assertStringNotContainsString('style="width:340px;', $html);

        $studentFrontendCss = file_get_contents(public_path('css/student-frontend.css'));
        $this->assertIsString($studentFrontendCss);
        $this->assertStringContainsString('.sf-notif-dropdown', $studentFrontendCss);
        $this->assertStringContainsString('.sf-notif-dd-foot', $studentFrontendCss);

        // Non-student portals retain shared notification dropdown styles untouched.
        $this->flushSession();
        $teacher = User::create([
            'name' => 'Notif Audit Teacher',
            'employee_id' => 'TCH-NOTIF-1',
            'course' => 'FACULTY',
            'year_level' => '1',
            'semester' => '1',
            'email' => 'teacher.notif@example.test',
            'password' => Hash::make('StrongPass#12345'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $teacherHtml = $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('class="dropdown top-notif-dropdown"', $teacherHtml);
        $this->assertStringContainsString('top-notif-dropdown-menu', $teacherHtml);
        $this->assertStringContainsString('class="notif-view-all"', $teacherHtml);

        $modernCss = file_get_contents(public_path('css/system-ui-modern.css'));
        $mobileCss = file_get_contents(public_path('css/mobile-enterprise.css'));
        $this->assertIsString($modernCss);
        $this->assertIsString($mobileCss);

        $this->assertStringContainsString('.notif-view-all {', $modernCss);
        $this->assertStringContainsString('text-decoration: none !important;', $modernCss);
        $this->assertStringContainsString('display: flex;', $modernCss);
        $this->assertStringContainsString('.top-header .top-notif-dropdown {', $mobileCss);
        $this->assertStringContainsString('inset: calc(100% + 8px) 12px auto 12px !important;', $mobileCss);
    }
}
