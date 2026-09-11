<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MobileAttendanceHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $student;
    protected Subject $subject;
    protected AttendanceSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Professor Smith',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Jane Student',
            'student_number' => 'STU-2026-001',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => '1A',
        ]);

        $this->subject = Subject::create([
            'code' => 'CS101',
            'name' => 'Computer Science Fundamentals',
            'units' => 3,
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => '1A',
            'instructor_id' => $this->teacher->id,
            'instructor' => 'Professor Smith',
        ]);

        $this->student->enrolledSubjects()->attach($this->subject->id);

        $now = now('Asia/Manila');
        $this->session = AttendanceSession::create([
            'subject_code' => $this->subject->code,
            'created_by' => $this->teacher->id,
            'token' => AttendanceSession::generateToken($this->subject->code),
            'session_code' => '654321',
            'expires_at' => $now->copy()->addMinutes(5),
            'session_ends_at' => $now->copy()->addMinutes(30),
            'active' => true,
        ]);
    }

    public function test_student_scan_saves_record_and_returns_full_contract()
    {
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token' => $this->session->token,
            'code' => $this->session->session_code,
            'method' => 'qr',
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertFalse($data['already_clocked_in']);
        $this->assertStringContainsStringIgnoringCase('attendance recorded successfully', $data['message']);

        // Check contract IDs
        $this->assertNotEmpty($data['attendanceId']);
        $this->assertNotEmpty($data['attendance_id']);
        $this->assertEquals((string) $this->student->id, $data['studentId']);
        $this->assertEquals((string) $this->subject->id, $data['subjectId']);
        $this->assertEquals((string) $this->session->id, $data['sessionId']);

        // Check DB
        $this->assertDatabaseHas('attendances', [
            'id' => $data['attendanceId'],
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'session_id' => $this->session->id,
            'subject_code' => 'CS101',
            'status' => 'Present',
        ]);
    }

    public function test_mobile_history_renders_newly_recorded_attendance()
    {
        // First record attendance
        $scan = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'code' => $this->session->session_code,
            'method' => 'code',
        ]);
        $scan->assertOk();
        $attId = $scan->json('attendanceId');

        // Then open mobile history
        $response = $this->actingAs($this->student)->get('/mobile/history');
        $response->assertOk();
        $response->assertViewIs('mobile.history');
        $response->assertSee('Computer Science Fundamentals');
        $response->assertSee('CS101');
        $response->assertSee('Present');
        $response->assertSee('Attendance History');

        $records = $response->viewData('records');
        $this->assertTrue($records->contains('id', $attId));
    }

    public function test_desktop_attendance_records_renders_newly_recorded_attendance()
    {
        // Record attendance
        $scan = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'code' => $this->session->session_code,
            'method' => 'code',
        ]);
        $scan->assertOk();
        $attId = $scan->json('attendanceId');

        // Check both /attendance/records and alias /attendance/history
        $response1 = $this->actingAs($this->student)->get('/attendance/records');
        $response1->assertOk();
        $response1->assertViewIs('attendance.records');
        $records1 = $response1->viewData('records');
        $this->assertTrue($records1->contains('id', $attId));

        $response2 = $this->actingAs($this->student)->get('/attendance/history');
        $response2->assertOk();
        $records2 = $response2->viewData('records');
        $this->assertTrue($records2->contains('id', $attId));
    }

    public function test_duplicate_submission_does_not_create_duplicate_record()
    {
        // First scan
        $first = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'code' => $this->session->session_code,
            'method' => 'code',
        ]);
        $first->assertOk();

        // Second scan (duplicate)
        $second = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'code' => $this->session->session_code,
            'method' => 'code',
        ]);
        $second->assertOk();
        $data = $second->json();

        $this->assertTrue($data['success']);
        $this->assertTrue($data['already_clocked_in']);
        $this->assertStringContainsString('Attendance already recorded for this session.', $data['message']);

        // Assert only 1 record exists in DB
        $this->assertEquals(1, Attendance::where('user_id', $this->student->id)->where('subject_id', $this->subject->id)->count());
    }
}
