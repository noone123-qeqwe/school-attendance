<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EndToEndAttendanceQrWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $studentQr;
    private User $studentCode;
    private User $studentOtherClass;
    private Subject $subject;
    private Subject $otherSubject;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::create([
            'name' => '2026-2027',
            'semester' => 1,
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'is_current' => 1,
        ]);

        Setting::set('gps_lat', 14.5000);
        Setting::set('gps_lng', 121.0000);
        Setting::set('gps_radius', 50);

        // Teacher
        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Professor Smith',
        ]);

        // Subject assigned to Professor Smith
        $this->subject = Subject::create([
            'code' => 'CS101',
            'name' => 'Introduction to Computer Science',
            'units' => 3,
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => '1A',
            'instructor_id' => $this->teacher->id,
        ]);

        // Schedule for today
        Schedule::create([
            'subject_id' => $this->subject->id,
            'day' => today()->format('l'),
            'start_time' => now('Asia/Manila')->subMinutes(5)->format('H:i:s'),
            'end_time' => now('Asia/Manila')->addMinutes(55)->format('H:i:s'),
        ]);

        // Student 1 (Will scan QR)
        $this->studentQr = User::factory()->create([
            'role' => 'student',
            'name' => 'Student Alice',
            'student_number' => '2026-0001',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => '1A',
        ]);
        $this->studentQr->enrolledSubjects()->attach($this->subject->id);

        // Student 2 (Will enter 6-digit Code)
        $this->studentCode = User::factory()->create([
            'role' => 'student',
            'name' => 'Student Bob',
            'student_number' => '2026-0002',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => '1A',
        ]);
        $this->studentCode->enrolledSubjects()->attach($this->subject->id);

        // Student 3 (Enrolled in a different course / year)
        $this->studentOtherClass = User::factory()->create([
            'role' => 'student',
            'name' => 'Student Charlie',
            'student_number' => '2026-0003',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSIT',
            'section' => '3A',
        ]);

        // Another subject by another teacher
        $otherTeacher = User::factory()->create(['role' => 'teacher', 'name' => 'Professor Jones']);
        $this->otherSubject = Subject::create([
            'code' => 'IT301',
            'name' => 'Advanced Database Systems',
            'units' => 3,
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSIT',
            'section' => '3A',
            'instructor_id' => $otherTeacher->id,
        ]);
    }

    /**
     * Complete Workflow:
     * 1. Teacher generates QR code + 6-digit code for active class.
     * 2. Student 1 scans QR code -> Attendance recorded.
     * 3. Student 2 enters 6-digit code -> Attendance recorded against the SAME session.
     * 4. Teacher live monitor verifies both students are recorded.
     */
    public function test_complete_teacher_and_students_dual_attendance_workflow(): void
    {
        // ==========================================
        // 1. TEACHER WORKFLOW: Start Attendance Session
        // ==========================================
        $teacherResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'classroom_lat' => 14.5000,
            'classroom_lng' => 121.0000,
        ]);

        $teacherResponse->assertOk();
        $teacherData = $teacherResponse->json();

        $this->assertTrue($teacherData['success']);
        $this->assertNotEmpty($teacherData['session_id']);
        $this->assertNotEmpty($teacherData['token']);
        $this->assertNotEmpty($teacherData['session_code']);
        $this->assertNotEmpty($teacherData['scan_url']);
        $this->assertEquals(6, strlen($teacherData['session_code']));

        $sessionId = $teacherData['session_id'];
        $qrToken = $teacherData['token'];
        $sessionCode = $teacherData['session_code'];
        $formattedCode = $teacherData['formatted_code'];

        // Confirm session in database is correctly associated
        $session = AttendanceSession::find($sessionId);
        $this->assertNotNull($session);
        $this->assertEquals($this->subject->code, $session->subject_code);
        $this->assertEquals($this->teacher->id, $session->created_by);
        $this->assertTrue($session->active);
        $this->assertEquals($sessionCode, $session->session_code);
        $this->assertEquals($qrToken, $session->token);
        $this->assertEquals(14.5000, (float) $session->classroom_lat);
        $this->assertEquals(121.0000, (float) $session->classroom_lng);
        $this->assertTrue($session->session_ends_at->isFuture());

        // ==========================================
        // 2. STUDENT 1: Scan Attendance QR Code
        // ==========================================
        $student1ScanResponse = $this->actingAs($this->studentQr)->postJson('/qr/scan-process', [
            'token' => $qrToken,
            'latitude' => 14.5001,
            'longitude' => 121.0001,
            'accuracy' => 10,
            'method' => 'qr',
        ]);

        $student1ScanResponse->assertOk();
        $student1Data = $student1ScanResponse->json();

        $this->assertTrue($student1Data['success']);
        $this->assertFalse($student1Data['already_clocked_in']);
        $this->assertEquals('Present', $student1Data['status']);
        $this->assertEquals($this->subject->name, $student1Data['subject']);
        $this->assertEquals($this->subject->code, $student1Data['subject_code']);
        $this->assertEquals('Student Alice', $student1Data['student_name']);
        $this->assertEquals('Attendance Recorded Successfully', $student1Data['message']);

        // Check Attendance record in database for Student 1
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->studentQr->id,
            'subject_code' => $this->subject->code,
            'status' => 'Present',
            'method' => 'qr',
        ]);

        // Verify Student 1 can see record in attendance records endpoint
        $recordsResponse = $this->actingAs($this->studentQr)->get(route('attendance.records'));
        $recordsResponse->assertOk();
        $recordsResponse->assertSee($this->subject->name);
        $recordsResponse->assertSee('Present');

        // Verify Student 1 dashboard reflects 'present'
        $homeResponse = $this->actingAs($this->studentQr)->get(route('mobile.home'));
        $homeResponse->assertOk();
        $homeResponse->assertSee('Present');

        // ==========================================
        // 3. STUDENT 2: Enter 6-Digit Code (Same Session)
        // ==========================================
        // Test with formatted code containing space: e.g. "123 456"
        $codeWithSpace = substr($sessionCode, 0, 3) . ' ' . substr($sessionCode, 3, 3);

        $student2CodeResponse = $this->actingAs($this->studentCode)->postJson('/qr/scan-process', [
            'code' => $codeWithSpace,
            'latitude' => 14.5002,
            'longitude' => 121.0001,
            'accuracy' => 12,
            'method' => 'code',
        ]);

        $student2CodeResponse->assertOk();
        $student2Data = $student2CodeResponse->json();

        $this->assertTrue($student2Data['success']);
        $this->assertFalse($student2Data['already_clocked_in']);
        $this->assertEquals('Present', $student2Data['status']);
        $this->assertEquals($this->subject->name, $student2Data['subject']);
        $this->assertEquals($this->subject->code, $student2Data['subject_code']);
        $this->assertEquals('Student Bob', $student2Data['student_name']);
        $this->assertEquals('Attendance Recorded Successfully', $student2Data['message']);

        // Check Attendance record in database for Student 2
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->studentCode->id,
            'subject_code' => $this->subject->code,
            'status' => 'Present',
            'method' => 'code',
        ]);

        // ==========================================
        // 4. TEACHER LIVE MONITOR: Both Students Verified
        // ==========================================
        $monitorResponse = $this->actingAs($this->teacher)->getJson("/teacher/qr/clockins?session_id={$sessionId}");
        $monitorResponse->assertOk();
        $monitorData = $monitorResponse->json();

        $this->assertEquals(2, $monitorData['stats']['total_students']);
        $this->assertEquals(2, $monitorData['stats']['clocked_in']);
        $this->assertEquals(2, $monitorData['stats']['present']);
        $this->assertEquals(0, $monitorData['stats']['late']);
        $this->assertEquals(0, $monitorData['stats']['absent']);
        $this->assertEquals(100, $monitorData['stats']['progress']);

        $rosterNames = array_column($monitorData['clockins'], 'name');
        $this->assertContains('Student Alice', $rosterNames);
        $this->assertContains('Student Bob', $rosterNames);
    }

    /**
     * Test Token Refresh:
     * When the teacher refreshes the QR token, the new token works,
     * and the old token is temporarily valid during the grace period.
     */
    public function test_teacher_refresh_token_and_grace_period(): void
    {
        $teacherResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
        ]);
        $sessionId = $teacherResponse->json('session_id');
        $initialToken = $teacherResponse->json('token');
        $sessionCode = $teacherResponse->json('session_code');

        // Teacher refreshes token
        $refreshResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/refresh', [
            'session_id' => $sessionId,
        ]);
        $refreshResponse->assertOk();
        $newToken = $refreshResponse->json('token');

        $this->assertNotEquals($initialToken, $newToken);
        $this->assertEquals($sessionCode, $refreshResponse->json('session_code'));

        // Student 1 scans with the newly refreshed token
        $scanNewResponse = $this->actingAs($this->studentQr)->postJson('/qr/scan-process', [
            'token' => $newToken,
        ]);
        $scanNewResponse->assertOk();
        $this->assertTrue($scanNewResponse->json('success'));

        // Student 2 scans with the previous token during grace period
        $scanOldResponse = $this->actingAs($this->studentCode)->postJson('/qr/scan-process', [
            'token' => $initialToken,
        ]);
        $scanOldResponse->assertOk();
        $this->assertTrue($scanOldResponse->json('success'));
    }

    /**
     * Test Invalid Scenarios:
     * - Invalid QR code
     * - Invalid 6-digit code
     * - Incomplete code / incorrect number of digits
     * - Empty code
     * - Expired QR code
     * - Expired 6-digit code
     * - QR/code from another class (Schedule/Course mismatch)
     * - Student attempting to record attendance twice (Duplicate scan)
     * - Attendance session that has already ended
     * - Student outside classroom boundary
     * - Non-student role attempting scan
     */
    public function test_invalid_and_edge_scenarios(): void
    {
        // Start an active teacher session
        $teacherResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'classroom_lat' => 14.5000,
            'classroom_lng' => 121.0000,
        ]);
        $sessionId = $teacherResponse->json('session_id');
        $validToken = $teacherResponse->json('token');
        $validCode = $teacherResponse->json('session_code');

        // 1. Invalid QR code
        $invalidQrResponse = $this->actingAs($this->studentQr)->postJson('/qr/scan-process', [
            'token' => 'completely_fake_invalid_qr_token_12345',
        ]);
        $invalidQrResponse->assertStatus(422);
        $this->assertFalse($invalidQrResponse->json('success'));
        $this->assertEquals('invalid_or_expired', $invalidQrResponse->json('error_type'));
        $this->assertStringContainsString('Invalid or expired', $invalidQrResponse->json('message'));

        // 2. Invalid 6-digit code
        $invalidCodeResponse = $this->actingAs($this->studentCode)->postJson('/qr/scan-process', [
            'code' => '999999',
        ]);
        $invalidCodeResponse->assertStatus(422);
        $this->assertFalse($invalidCodeResponse->json('success'));
        $this->assertEquals('invalid_or_expired', $invalidCodeResponse->json('error_type'));

        // 3. Incomplete code / incorrect number of digits (e.g. 3 digits)
        $incompleteCodeResponse = $this->actingAs($this->studentCode)->postJson('/qr/scan-process', [
            'code' => '123',
        ]);
        $incompleteCodeResponse->assertStatus(422);
        $this->assertFalse($incompleteCodeResponse->json('success'));
        $this->assertEquals('invalid_or_expired', $incompleteCodeResponse->json('error_type'));

        // 4. Empty code / token
        $emptyResponse = $this->actingAs($this->studentCode)->postJson('/qr/scan-process', [
            'code' => '',
            'token' => '',
        ]);
        $emptyResponse->assertStatus(422);

        // 5. Student attempting to record attendance twice (duplicate clock-in)
        // First clock in:
        $clockIn1 = $this->actingAs($this->studentQr)->postJson('/qr/scan-process', [
            'token' => $validToken,
            'latitude' => 14.5000,
            'longitude' => 121.0000,
        ]);
        $clockIn1->assertOk();
        $this->assertTrue($clockIn1->json('success'));
        $this->assertFalse($clockIn1->json('already_clocked_in'));

        // Second clock in attempt via QR:
        $duplicateQr = $this->actingAs($this->studentQr)->postJson('/qr/scan-process', [
            'token' => $validToken,
            'latitude' => 14.5000,
            'longitude' => 121.0000,
        ]);
        $duplicateQr->assertOk();
        $this->assertTrue($duplicateQr->json('success'));
        $this->assertTrue($duplicateQr->json('already_clocked_in'));
        $this->assertStringContainsString('already clocked in', $duplicateQr->json('message'));

        // Third clock in attempt via 6-digit code:
        $duplicateCode = $this->actingAs($this->studentQr)->postJson('/qr/scan-process', [
            'code' => $validCode,
            'latitude' => 14.5000,
            'longitude' => 121.0000,
        ]);
        $duplicateCode->assertOk();
        $this->assertTrue($duplicateCode->json('already_clocked_in'));

        // 6. QR / code from another class (student not enrolled / course-year mismatch)
        $mismatchResponse = $this->actingAs($this->studentOtherClass)->postJson('/qr/scan-process', [
            'code' => $validCode,
            'latitude' => 14.5000,
            'longitude' => 121.0000,
        ]);
        $mismatchResponse->assertStatus(422);
        $this->assertFalse($mismatchResponse->json('success'));
        $this->assertEquals('schedule_mismatch', $mismatchResponse->json('error_type'));
        $this->assertStringContainsString('not intended for your class', $mismatchResponse->json('message'));

        // 7. Student outside classroom boundary
        $outsideResponse = $this->actingAs($this->studentCode)->postJson('/qr/scan-process', [
            'token' => $validToken,
            'latitude' => 14.5200, // ~2.2 km away
            'longitude' => 121.0200,
            'accuracy' => 10,
        ]);
        $outsideResponse->assertStatus(422);
        $this->assertFalse($outsideResponse->json('success'));
        $this->assertEquals('outside_classroom', $outsideResponse->json('error_type'));
        $this->assertGreaterThan(50, $outsideResponse->json('distance'));

        // 8. Expired / Ended session
        $session = AttendanceSession::find($sessionId);
        $session->update([
            'active' => false,
            'session_ends_at' => now()->subMinutes(10),
        ]);

        $endedSessionResponse = $this->actingAs($this->studentCode)->postJson('/qr/scan-process', [
            'token' => $validToken,
        ]);
        $endedSessionResponse->assertStatus(422);
        $this->assertFalse($endedSessionResponse->json('success'));
        $this->assertEquals('session_closed', $endedSessionResponse->json('error_type'));
        $this->assertStringContainsString('ended', $endedSessionResponse->json('message'));

        // 9. Non-student role attempting scan (Teacher or Admin)
        $teacherScanAttempt = $this->actingAs($this->teacher)->postJson('/qr/scan-process', [
            'token' => $validToken,
        ]);
        $teacherScanAttempt->assertStatus(403);
        $this->assertFalse($teacherScanAttempt->json('success'));
        $this->assertStringContainsString('Only enrolled students', $teacherScanAttempt->json('message'));
    }
}
