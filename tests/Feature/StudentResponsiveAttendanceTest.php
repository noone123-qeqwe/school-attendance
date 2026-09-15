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

    public function test_settings_and_biometrics_are_removed_from_sidebar_and_mobile_more_sheet(): void
    {
        $this->actingAs($this->student);
        $sidebar = view('layouts.sidebars.student')->render();
        $this->assertStringNotContainsString('Settings', $sidebar);

        $response = $this->actingAs($this->student)->get('/home');
        $response->assertOk();
        $html = $response->getContent();

        // Ensure moreSheetContent does not contain Settings or Biometrics link
        $this->assertDoesNotMatchRegularExpression('/id="moreSheetContent"[\s\S]*?<span class="more-sheet-item-label">Settings<\/span>/', $html);
        $this->assertDoesNotMatchRegularExpression('/id="moreSheetContent"[\s\S]*?<span class="more-sheet-item-label">Biometrics<\/span>/', $html);
    }

    public function test_home_dashboard_has_clean_student_hero_banner_without_redundant_action_buttons(): void
    {
        $response = $this->actingAs($this->student)->get('/home');

        $response->assertStatus(200);

        // Verify redundant CTAs and biometrics are removed from student dashboard hero
        $response->assertDontSee('Set up Biometrics');
        $response->assertDontSee('Scan / Enter Code');

        // Verify clean student identity elements and widgets are present
        $response->assertSee($this->student->name);
        $response->assertSee($this->student->course);
        $response->assertSee('studentClock');
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

    public function test_settings_page_has_floating_scrollable_tabs_hint(): void
    {
        $response = $this->actingAs($this->student)->get('/settings');
        $response->assertStatus(200);

        // Verify floating scrollable hint element and label
        $response->assertSee('id="stabsFloatingHint"', false);
        $response->assertSee('Scrollable');
        $response->assertSee('stabs-floating-hint', false);
    }

    public function test_student_scanner_modal_has_camera_viewfinder_laser_reticles_and_fallback_states(): void
    {
        $response = $this->actingAs($this->student)->get('/home');

        $response->assertStatus(200);

        // Viewfinder & camera components
        $response->assertSee('id="scannerVideoContainer"', false);
        $response->assertSee('id="reader"', false);
        $response->assertSee('id="scannerLaser"', false);
        $response->assertSee('id="scannerGuideBadge"', false);
        $response->assertSee('reticle-corner top-left', false);
        $response->assertSee('reticle-corner top-right', false);
        $response->assertSee('reticle-corner bottom-left', false);
        $response->assertSee('reticle-corner bottom-right', false);

        // Overlay & Feedback
        $response->assertSee('id="scannerProcessingOverlay"', false);
        $response->assertSee('id="scannerFallbackNotice"', false);
        $response->assertSee('id="retryCameraBtn"', false);
        $response->assertSee('id="resultAutoCloseNotice"', false);
        $response->assertSee('id="autoCloseCountdown"', false);
        $response->assertSee('id="outsideRangePopupModal"', false);

        // Functional JS safeguards
        $response->assertSee('safeStopScanner()', false);
        $response->assertSee('safeClearScanner()', false);
        $response->assertSee('isScanInFlight', false);
        $response->assertSee('extractQrToken(', false);
    }

    public function test_scanner_modal_has_paste_copied_code_features_and_clipboard_support(): void
    {
        $response = $this->actingAs($this->student)->get('/home');
        $response->assertStatus(200);

        // Paste buttons & actions
        $response->assertSee('id="pasteClipboardBtn"', false);
        $response->assertSee('data-action="paste-copied-code"', false);
        $response->assertSee('id="cameraPasteBtn"', false);
        $response->assertSee('Paste Copied Code');

        // JS paste handling
        $response->assertSee('pasteCopiedCodeFromClipboard()', false);
        $response->assertSee('handleCopiedCodeInput(', false);

        // Mobile scan page paste button
        $mobileResponse = $this->actingAs($this->student)->get('/mobile/scan');
        $mobileResponse->assertStatus(200);
        $mobileResponse->assertSee('id="openPasteBtn"', false);
        $mobileResponse->assertSee('Paste Copied Code');
    }

    public function test_calendar_day_tiles_and_modals_have_mobile_responsiveness_and_touch_support(): void
    {
        // 1. Student Dashboard (/home)
        $homeResponse = $this->actingAs($this->student)->get('/home');
        $homeResponse->assertStatus(200);

        // Day summary inspector modal & mobile sheet handle
        $homeResponse->assertSee('id="daySummaryModal"', false);
        $homeResponse->assertSee('class="scal-sheet-handle', false);
        $homeResponse->assertSee('closeDaySummaryModal()', false);

        // Calendar tiles touch action and accessibility
        $homeResponse->assertSee('touch-action: manipulation', false);
        $homeResponse->assertSee('role="button"', false);
        $homeResponse->assertSee('tabindex="0"', false);
        $homeResponse->assertSee('calTile_', false);

        // Stacking context fix: relocation to document.body and high z-index above bottom nav
        $homeResponse->assertSee('document.body.appendChild(dModal)', false);
        $homeResponse->assertSee('10060 !important', false);

        // 2. Student School Calendar (/student/calendar)
        $calResponse = $this->actingAs($this->student)->get(route('student.calendar'));
        $calResponse->assertStatus(200);
        $calResponse->assertSee('id="daySummaryModal"', false);
        $calResponse->assertSee('touch-action: manipulation', false);
        $calResponse->assertSee('scalDayClick(', false);
        $calResponse->assertSee('closeDaySummaryModal()', false);

        // 3. Student Attendance Calendar (/student/attendance-calendar)
        $attCalResponse = $this->actingAs($this->student)->get(route('student.attendance.calendar'));
        $attCalResponse->assertStatus(200);
        $attCalResponse->assertSee('id="daySummaryModal"', false);
        $attCalResponse->assertSee('touch-action: manipulation', false);
        $attCalResponse->assertSee('closeDaySummaryModal()', false);
        $attCalResponse->assertSee('document.body.appendChild(dModal)', false);
    }
}
