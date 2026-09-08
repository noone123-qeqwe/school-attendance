<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentResponsiveAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2000001',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);
    }

    public function test_student_sidebar_does_not_contain_scan_qr_or_biometrics_registration(): void
    {
        $this->actingAs($this->student);
        $sidebar = view('layouts.sidebars.student')->render();

        // Verify sidebar items
        $this->assertStringContainsString('Dashboard', $sidebar);
        $this->assertStringContainsString('My Schedule', $sidebar);
        $this->assertStringContainsString('Attendance Calendar', $sidebar);
        $this->assertStringContainsString('My Classes', $sidebar);
        $this->assertStringContainsString('Attendance Records', $sidebar);
        $this->assertStringContainsString('School Calendar', $sidebar);
        $this->assertStringContainsString('Excuse Submissions', $sidebar);

        // Verify removed sidebar links (Settings, Scan QR, Biometrics)
        $this->assertStringNotContainsString('Settings', $sidebar);
        $this->assertStringNotContainsString(route('settings'), $sidebar);
        $this->assertStringNotContainsString('Scan QR / Enter Code', $sidebar);
        $this->assertStringNotContainsString('Scan QR', $sidebar);
        $this->assertStringNotContainsString('Biometrics Registration', $sidebar);
        $this->assertStringNotContainsString('student/biometrics', $sidebar);
    }

    public function test_settings_is_removed_from_sidebar_and_mobile_more_sheet(): void
    {
        $this->actingAs($this->student);
        $sidebar = view('layouts.sidebars.student')->render();
        $this->assertStringNotContainsString('Settings', $sidebar);

        $response = $this->actingAs($this->student)->get('/home');
        $response->assertOk();
        $html = $response->getContent();

        // Ensure moreSheetContent does not contain Settings link
        $this->assertDoesNotMatchRegularExpression('/id="moreSheetContent"[\s\S]*?<span class="more-sheet-item-label">Settings<\/span>/', $html);
    }

    public function test_home_dashboard_has_responsive_attendance_action_buttons(): void
    {
        $response = $this->actingAs($this->student)->get('/home');

        $response->assertStatus(200);

        // Desktop CTA (code entry)
        $response->assertSee("openStudentScanner('code')", false);
        $response->assertSee('Enter Attendance Code');

        // Mobile CTA (scan/code entry)
        $response->assertSee("openStudentScanner('scan')", false);
        $response->assertSee('Scan / Enter Code');
    }

    public function test_attendance_records_page_has_responsive_attendance_action_buttons(): void
    {
        $response = $this->actingAs($this->student)->get('/attendance/records');

        $response->assertStatus(200);

        // Desktop CTA (code entry)
        $response->assertSee("openStudentScanner('code')", false);
        $response->assertSee('Enter Attendance Code');

        // Mobile CTA (scan/code entry)
        $response->assertSee("openStudentScanner('scan')", false);
        $response->assertSee('Scan / Enter Code');
    }

    public function test_scanner_modal_has_desktop_code_header_and_responsive_logic(): void
    {
        $response = $this->actingAs($this->student)->get('/home');

        $response->assertStatus(200);

        // Modal markup components
        $response->assertSee('id="studentScannerModal"', false);
        $response->assertSee('id="desktopScannerHeader"', false);
        $response->assertSee('id="mobileModeSwitcher"', false);
        $response->assertSee('id="scannerCodeView"', false);
        $response->assertSee('id="directSessionCodeInput"', false);

        // Responsive JS functionality
        $response->assertSee('isDesktopDevice()', false);
        $response->assertSee("mode = 'code'", false);
    }

    public function test_settings_page_has_biometrics_section_with_methods_and_device_management(): void
    {
        $response = $this->actingAs($this->student)->get('/settings');

        $response->assertStatus(200);

        // Tab header
        $response->assertSee('Biometrics');
        $response->assertSee('id="tab-fingerprint"', false);

        // Methods
        $response->assertSee('Fingerprint');
        $response->assertSee('Face Recognition');
        $response->assertSee('id="methodCardFp"', false);
        $response->assertSee('id="methodCardFace"', false);

        // Device Management
        $response->assertSee('Registered Hardware Credentials');
        $response->assertSee('id="deviceList"', false);
    }
}
