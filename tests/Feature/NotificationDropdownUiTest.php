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

        $this->assertStringContainsString('class="dropdown top-notif-dropdown"', $html);
        $this->assertStringContainsString('top-notif-dropdown-menu', $html);
        $this->assertStringContainsString('class="top-notif-list"', $html);
        $this->assertStringContainsString('class="top-notif-empty"', $html);
        $this->assertStringContainsString('class="notif-view-all"', $html);
        $this->assertStringNotContainsString('style="width:340px;', $html);

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
