<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Subject;
use App\Models\QrSession;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class MobileScanQrFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        AcademicYear::create([
            'name' => '2026-2027',
            'semester' => 1,
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'is_current' => 1,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2026-99999',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        $this->subject = Subject::create([
            'code' => 'CS301',
            'name' => 'Mobile Systems',
            'units' => 3,
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'instructor_id' => $this->teacher->id,
        ]);
    }

    public function test_mobile_home_renders_scan_button_and_scanner_modal(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('mobile.home'));

        $response->assertOk();

        // Check Scan button in bottom navigation
        $response->assertSee('id="mobileNavScanBtn"', false);
        $response->assertSee('data-action="open-scanner"', false);
        $response->assertSee('Scan');

        // Check Quick Action Scan button on home
        $response->assertSee('id="quickActionScanBtn"', false);

        // Check Scanner modal is included in the DOM for students
        $response->assertSee('id="studentScannerModal"', false);
        $response->assertSee('id="scannerActiveView"', false);
        $response->assertSee('id="reader"', false);

        // Check requested guidance instruction text
        $response->assertSee('Position the teacher’s QR code inside the frame.', false);

        // Check requested permission error message
        $response->assertSee('Camera access is required to scan the attendance QR code. Please allow camera access in your device settings.', false);

        // Check cancel / close button exists
        $response->assertSee('Cancel & Return to Dashboard', false);
    }

    public function test_dedicated_mobile_scan_page_renders_cleanly(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('mobile.scan'));

        $response->assertOk();
        $response->assertSee('Scan Attendance QR');
        $response->assertSee('id="openScannerBtn"', false);
        $response->assertSee('data-action="open-scanner"', false);
        $response->assertSee('id="studentScannerModal"', false);
    }

    public function test_all_rendered_scripts_in_mobile_layout_carry_csp_nonces(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('mobile.home'));

        $response->assertOk();
        $html = $response->getContent();

        // Ensure there are no naked <script> tags without a nonce attribute
        preg_match_all('/<script\b(?![^>]*\bnonce=)[^>]*>/i', $html, $matches);
        $nakedScripts = $matches[0] ?? [];

        $this->assertEmpty(
            $nakedScripts,
            'Found <script> tags without nonce in mobile view, which would be blocked by CSP: ' . implode(', ', $nakedScripts)
        );
    }

    public function test_student_attendance_scan_records_attendance_from_qr(): void
    {
        // Setup an active QR session for the subject
        $token = (string) Str::uuid();
        $session = \App\Models\AttendanceSession::create([
            'created_by' => $this->teacher->id,
            'subject_code' => $this->subject->code,
            'token' => $token,
            'session_code' => '849201',
            'expires_at' => now()->addMinutes(15),
            'session_ends_at' => now()->addMinutes(45),
            'active' => true,
            'classroom_lat' => 14.5995,
            'classroom_lng' => 120.9842,
        ]);

        $response = $this->actingAs($this->student)
            ->withSession(['user_role' => 'student'])
            ->postJson(route('qr.scan.process'), [
                'token' => $token,
                'latitude' => 14.5995,
                'longitude' => 120.9842,
            ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals($this->subject->code, $data['subject_code']);

        // Verify attendance record in database
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->student->id,
            'subject_code' => $this->subject->code,
            'date' => now()->toDateString(),
        ]);
    }

    public function test_invalid_qr_code_returns_clean_error_message(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson(route('qr.scan.process'), [
                'token' => 'non-existent-or-expired-token',
            ]);

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertNotEmpty($data['message']);
    }

    public function test_student_attendance_records_from_manual_6_digit_code(): void
    {
        $token = (string) Str::uuid();
        $session = \App\Models\AttendanceSession::create([
            'created_by' => $this->teacher->id,
            'subject_code' => $this->subject->code,
            'token' => $token,
            'session_code' => '849201',
            'expires_at' => now()->addMinutes(15),
            'session_ends_at' => now()->addMinutes(45),
            'active' => true,
            'classroom_lat' => 14.5995,
            'classroom_lng' => 120.9842,
        ]);

        // Student submits 6-digit code with code parameter
        $response = $this->actingAs($this->student)
            ->withSession(['user_role' => 'student'])
            ->postJson(route('qr.scan.process'), [
                'code' => '849 201',
                'token' => '849201',
                'latitude' => 14.5995,
                'longitude' => 120.9842,
            ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals($this->subject->code, $data['subject_code']);
    }

    public function test_manual_code_entry_ui_partial_has_required_elements_and_handlers(): void
    {
        $response = $this->actingAs($this->student)
            ->withSession(['user_role' => 'student'])
            ->get(route('mobile.home'));

        $response->assertOk();
        $response->assertSee('id="directSessionCodeInput"', false);
        $response->assertSee('id="codeSubmitBtn"', false);
        $response->assertSee('id="tabCodeMode"', false);
        $response->assertSee('submitDirectCode', false);
        $response->assertSee('retryCurrentScanMode', false);
        $response->assertSee('window.submitDirectCode = submitDirectCode;', false);
    }

    public function test_camera_scanner_has_robust_lifecycle_and_error_handling_states(): void
    {
        $response = $this->actingAs($this->student)
            ->withSession(['user_role' => 'student'])
            ->get(route('mobile.home'));

        $response->assertOk();

        // Check Retry Camera button text element
        $response->assertSee('id="retryCameraBtnText"', false);

        // Check required error handling messages
        $response->assertSee('Camera permission was denied. Please allow camera access in your device or browser settings.', false);
        $response->assertSee('Unable to access your camera. Please check that your camera is available and try again.', false);
        $response->assertSee('Unable to start the scanner. Please try again.', false);
        $response->assertSee('Retry Camera', false);

        // Ensure destructive probe stream stopping is NOT present
        $response->assertDontSee('probeStream.getTracks().forEach', false);
    }
}
