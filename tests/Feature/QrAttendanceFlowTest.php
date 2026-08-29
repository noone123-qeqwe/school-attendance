<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use App\Models\AttendanceSession;
use App\Services\WebauthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\URL;

class QrAttendanceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_attendance_flow()
    {
        // 1. Setup Data
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS'
        ]);

        // Mock webauthn credential for the student
        $student->webauthnCredentials()->create([
            'credential_id' => 'fake_cred_id',
            'public_key' => 'fake_key',
            'sign_count' => 0,
            'user_handle' => 'fake_handle',
        ]);

        $subject = Subject::factory()->create([
            'instructor_id' => $teacher->id,
            'code' => 'TEST101',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS'
        ]);

        \App\Models\Schedule::create([
            'subject_id' => $subject->id,
            'day' => today()->format('l'),
            'start_time' => now()->subMinutes(10)->format('H:i:s'),
            'end_time' => now()->addMinutes(50)->format('H:i:s')
        ]);

        // Explicitly enroll the student to avoid mismatch
        $student->enrolledSubjects()->attach($subject->id);

        // 2. Teacher Starts Session
        $response = $this->actingAs($teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $subject->code,
            'classroom_lat' => 14.5,
            'classroom_lng' => 121.0
        ]);

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        
        $token = $responseData['token'];
        $session_id = $responseData['session_id'];

        // 3. Student Scans QR Code
        $scanUrl = URL::signedRoute('qr.scan', ['token' => $token]);
        
        $scanResponse = $this->actingAs($student)->get($scanUrl);
        $scanResponse->assertStatus(200);
        // It should render the view qr.verify
        $scanResponse->assertViewIs('qr.verify');

        // 4. Student Requests Verification Options
        $optionsResponse = $this->actingAs($student)->postJson('/qr/verify-options', [
            'token' => $token
        ]);
        $optionsResponse->assertStatus(200);
        $this->assertTrue($optionsResponse->json('success'));

        // 5. Mock WebauthnService and Submit Verification
        $mockWebauthn = Mockery::mock(WebauthnService::class);
        $mockCredential = new \App\Models\WebauthnCredential();
        $mockWebauthn->shouldReceive('verifyAssertion')->once()->andReturn($mockCredential);
        $this->app->instance(WebauthnService::class, $mockWebauthn);

        $confirmResponse = $this->actingAs($student)->postJson('/qr/verify-complete', [
            'token' => $token,
            'latitude' => 14.5001, // Within radius
            'longitude' => 121.0001,
            'accuracy' => 10,
            'credential' => '{"id":"fake","rawId":"fake","response":{},"type":"public-key"}'
        ]);

        $confirmResponse->assertStatus(200);
        $this->assertTrue($confirmResponse->json('success'));

        // 6. Verify Attendance is Recorded
        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'status' => 'Present'
        ]);

        // 7. Teacher Stops Session
        $stopResponse = $this->actingAs($teacher)->postJson('/teacher/qr/stop', [
            'session_id' => $session_id
        ]);
        $stopResponse->assertStatus(200);
        
        $this->assertDatabaseHas('attendance_sessions', [
            'id' => $session_id,
            'active' => false
        ]);
    }

    public function test_student_outside_classroom_fails_to_clock_in()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS'
        ]);

        $student->webauthnCredentials()->create([
            'credential_id' => 'fake_cred_id_2',
            'public_key' => 'fake_key_2',
            'sign_count' => 0,
            'user_handle' => 'fake_handle_2',
        ]);

        $subject = Subject::factory()->create([
            'instructor_id' => $teacher->id,
            'code' => 'TEST102',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS'
        ]);

        \App\Models\Schedule::create([
            'subject_id' => $subject->id,
            'day' => today()->format('l'),
            'start_time' => now()->subMinutes(5)->format('H:i:s'),
            'end_time' => now()->addMinutes(55)->format('H:i:s')
        ]);

        $student->enrolledSubjects()->attach($subject->id);

        // Teacher starts session at classroom coordinates (14.5000, 121.0000)
        $startResponse = $this->actingAs($teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $subject->code,
            'classroom_lat' => 14.5000,
            'classroom_lng' => 121.0000
        ]);

        $startResponse->assertStatus(200);
        $token = $startResponse->json('token');

        // Request verification options
        $this->actingAs($student)->postJson('/qr/verify-options', ['token' => $token]);

        $mockWebauthn = Mockery::mock(WebauthnService::class);
        $mockCredential = new \App\Models\WebauthnCredential();
        $mockWebauthn->shouldReceive('verifyAssertion')->once()->andReturn($mockCredential);
        $this->app->instance(WebauthnService::class, $mockWebauthn);

        // Student submits from 14.6000, 121.1000 (~15km away - far outside classroom)
        $confirmResponse = $this->actingAs($student)->postJson('/qr/verify-complete', [
            'token' => $token,
            'latitude' => 14.6000,
            'longitude' => 121.1000,
            'accuracy' => 10,
            'credential' => '{"id":"fake","rawId":"fake","response":{},"type":"public-key"}'
        ]);

        $confirmResponse->assertStatus(422);
        $this->assertFalse($confirmResponse->json('success'));
        $this->assertEquals('outside_classroom', $confirmResponse->json('error_type'));
        $this->assertStringContainsString('outside the classroom', $confirmResponse->json('message'));

        // Verify attendance was NOT recorded
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $student->id,
            'subject_code' => $subject->code
        ]);
    }

    public function test_student_in_app_qr_scanner_success_records_attendance()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSIT',
            'section' => '1A',
            'student_number' => '2026-001'
        ]);

        $subject = Subject::factory()->create([
            'instructor_id' => $teacher->id,
            'code' => 'IT101',
            'name' => 'Intro to Computing',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSIT',
            'section' => '1A'
        ]);

        \App\Models\Schedule::create([
            'subject_id' => $subject->id,
            'day' => today()->format('l'),
            'start_time' => now()->subMinutes(5)->format('H:i:s'),
            'end_time' => now()->addMinutes(55)->format('H:i:s')
        ]);

        $startResponse = $this->actingAs($teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $subject->code,
            'classroom_lat' => 14.5000,
            'classroom_lng' => 121.0000
        ]);

        $startResponse->assertStatus(200);
        $token = $startResponse->json('token');

        // Student scans directly via in-app scanner
        $scanResponse = $this->actingAs($student)->postJson('/qr/scan-process', [
            'token' => $token,
            'latitude' => 14.5001,
            'longitude' => 121.0001,
            'accuracy' => 15
        ]);

        $scanResponse->assertStatus(200);
        $this->assertTrue($scanResponse->json('success'));
        $this->assertFalse($scanResponse->json('already_clocked_in'));
        $this->assertEquals('Present', $scanResponse->json('status'));
        $this->assertEquals('Intro to Computing', $scanResponse->json('subject'));
        $this->assertEquals('IT101', $scanResponse->json('subject_code'));
        $this->assertEquals('Attendance Recorded Successfully', $scanResponse->json('message'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'subject_code' => 'IT101',
            'status' => 'Present',
            'method' => 'qr'
        ]);
    }

    public function test_student_in_app_qr_scanner_prevents_duplicate_attendance()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSIT',
            'section' => '1A'
        ]);

        $subject = Subject::factory()->create([
            'instructor_id' => $teacher->id,
            'code' => 'IT102',
            'name' => 'Computer Programming',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSIT',
            'section' => '1A'
        ]);

        $startResponse = $this->actingAs($teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $subject->code,
            'classroom_lat' => 14.5000,
            'classroom_lng' => 121.0000
        ]);

        $token = $startResponse->json('token');

        // First scan
        $this->actingAs($student)->postJson('/qr/scan-process', [
            'token' => $token,
            'latitude' => 14.5001,
            'longitude' => 121.0001
        ]);

        // Second scan attempts duplicate
        $secondResponse = $this->actingAs($student)->postJson('/qr/scan-process', [
            'token' => $token,
            'latitude' => 14.5001,
            'longitude' => 121.0001
        ]);

        $secondResponse->assertStatus(200);
        $this->assertTrue($secondResponse->json('success'));
        $this->assertTrue($secondResponse->json('already_clocked_in'));
        $this->assertStringContainsString('already clocked in', $secondResponse->json('message'));
    }

    public function test_student_in_app_qr_scanner_rejects_schedule_mismatch()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        // 2nd Year Student
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSIT'
        ]);

        // 1st Year Subject
        $subject = Subject::factory()->create([
            'instructor_id' => $teacher->id,
            'code' => 'IT103',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSIT'
        ]);

        $startResponse = $this->actingAs($teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $subject->code
        ]);

        $token = $startResponse->json('token');

        $scanResponse = $this->actingAs($student)->postJson('/qr/scan-process', [
            'token' => $token
        ]);

        $scanResponse->assertStatus(422);
        $this->assertFalse($scanResponse->json('success'));
        $this->assertEquals('schedule_mismatch', $scanResponse->json('error_type'));
        $this->assertStringContainsString('Year mismatch', $scanResponse->json('message'));
    }

    public function test_student_can_clock_in_using_session_code_pin()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS'
        ]);

        $subject = Subject::factory()->create([
            'instructor_id' => $teacher->id,
            'code' => 'CS101',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS'
        ]);

        \App\Models\Schedule::create([
            'subject_id' => $subject->id,
            'day' => today()->format('l'),
            'start_time' => now()->subMinutes(10)->format('H:i:s'),
            'end_time' => now()->addMinutes(50)->format('H:i:s')
        ]);

        $student->enrolledSubjects()->attach($subject->id);

        $startResponse = $this->actingAs($teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $subject->code,
            'classroom_lat' => 14.5000,
            'classroom_lng' => 121.0000
        ]);

        $startResponse->assertStatus(200);
        $sessionCode = $startResponse->json('session_code');
        $this->assertNotEmpty($sessionCode);
        $this->assertEquals(6, strlen($sessionCode));

        // Submit via 6-digit code with spacing
        $formattedInput = substr($sessionCode, 0, 3) . ' ' . substr($sessionCode, 3, 3);
        $scanResponse = $this->actingAs($student)->postJson('/qr/scan-process', [
            'code' => $formattedInput,
            'latitude' => 14.5001,
            'longitude' => 121.0001
        ]);

        $scanResponse->assertStatus(200);
        $this->assertTrue($scanResponse->json('success'));
        $this->assertEquals('Present', $scanResponse->json('status'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'status' => 'Present',
            'method' => 'code'
        ]);
    }

    public function test_qr_session_stays_active_for_5_minutes()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $subject = Subject::factory()->create([
            'instructor_id' => $teacher->id,
            'code' => 'CS301'
        ]);

        $startResponse = $this->actingAs($teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $subject->code
        ]);

        $startResponse->assertStatus(200);
        $this->assertEquals(300, $startResponse->json('ttl'));

        $sessionId = $startResponse->json('session_id');
        $session = AttendanceSession::find($sessionId);

        $this->assertNotNull($session);
        // Expiration is ~300 seconds (5 minutes) in the future
        $diffSeconds = now()->diffInSeconds($session->expires_at, false);
        $this->assertGreaterThanOrEqual(290, $diffSeconds);
        $this->assertLessThanOrEqual(305, $diffSeconds);
    }
}
