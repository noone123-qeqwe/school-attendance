<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FifteenSecondQrRegenerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $student;
    protected User $student2;
    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Prof. Alan Turing'
        ]);

        $this->student = User::factory()->create([
            'role'           => 'student',
            'name'           => 'Grace Hopper',
            'student_number' => '2026-0001',
            'year_level'     => 3,
            'semester'       => 1,
            'course'         => 'BSCS',
            'section'        => '3A'
        ]);

        $this->student2 = User::factory()->create([
            'role'           => 'student',
            'name'           => 'Ada Lovelace',
            'student_number' => '2026-0002',
            'year_level'     => 3,
            'semester'       => 1,
            'course'         => 'BSCS',
            'section'        => '3A'
        ]);

        $this->subject = Subject::factory()->create([
            'code'          => 'CS305',
            'name'          => 'Algorithms & Security',
            'instructor_id' => $this->teacher->id,
            'instructor'    => $this->teacher->name,
            'year_level'    => 3,
            'semester'      => 1,
            'course'        => 'BSCS',
            'section'       => '3A'
        ]);
    }

    public function test_qr_and_session_code_generate_with_15_second_ttl()
    {
        $response = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'radius_meters' => 0,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'ttl'     => 15,
            ]);

        $data = $response->json();
        $this->assertNotEmpty($data['token']);
        $this->assertNotEmpty($data['session_code']);
        $this->assertEquals(6, strlen($data['session_code']));

        $session = AttendanceSession::find($data['session_id']);
        $this->assertNotNull($session);
        $this->assertEquals($data['token'], $session->token);
        $this->assertEquals($data['session_code'], $session->session_code);

        // Server expires_at should be 15 seconds from creation
        $diffSeconds = now()->diffInSeconds($session->expires_at, false);
        $this->assertGreaterThanOrEqual(13, $diffSeconds);
        $this->assertLessThanOrEqual(16, $diffSeconds);
    }

    public function test_refresh_generates_new_unique_qr_token_and_attendance_code_every_15_seconds()
    {
        $startResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'radius_meters' => 0,
        ]);
        $sessionId   = $startResponse->json('session_id');
        $firstToken  = $startResponse->json('token');
        $firstCode   = $startResponse->json('session_code');

        // Refresh session
        $refreshResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/refresh', [
            'session_id' => $sessionId,
        ]);

        $refreshResponse->assertOk()
            ->assertJson([
                'success' => true,
                'ttl'     => 15,
            ]);

        $secondToken = $refreshResponse->json('token');
        $secondCode  = $refreshResponse->json('session_code');

        // Both token AND attendance code must be unique from previous cycle
        $this->assertNotEquals($firstToken, $secondToken);
        $this->assertNotEquals($firstCode, $secondCode);
        $this->assertEquals(6, strlen($secondCode));

        // DB session must preserve previous token and previous code
        $session = AttendanceSession::find($sessionId);
        $this->assertEquals($secondToken, $session->token);
        $this->assertEquals($firstToken, $session->previous_token);
        $this->assertEquals($secondCode, $session->session_code);
        $this->assertEquals($firstCode, $session->previous_session_code);
    }

    public function test_student_can_check_in_with_current_qr_or_attendance_code()
    {
        $startResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'radius_meters' => 0,
        ]);
        $token = $startResponse->json('token');
        $code  = $startResponse->json('session_code');

        // Student 1 checks in with live QR token
        $scanResponse = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'  => $token,
            'method' => 'qr'
        ]);
        $scanResponse->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('attendances', [
            'user_id'      => $this->student->id,
            'subject_code' => $this->subject->code,
            'status'       => 'Present'
        ]);

        // Student 2 checks in with live 6-digit attendance code
        $codeResponse = $this->actingAs($this->student2)->postJson('/qr/scan-process', [
            'code'   => $code,
            'method' => 'code'
        ]);
        $codeResponse->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('attendances', [
            'user_id'      => $this->student2->id,
            'subject_code' => $this->subject->code,
            'status'       => 'Present'
        ]);
    }

    public function test_network_delay_grace_period_allows_immediate_previous_code()
    {
        $startResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'radius_meters' => 0,
        ]);
        $sessionId = $startResponse->json('session_id');
        $prevToken = $startResponse->json('token');
        $prevCode  = $startResponse->json('session_code');

        // Teacher refreshes (code regenerates)
        $this->actingAs($this->teacher)->postJson('/teacher/qr/refresh', [
            'session_id' => $sessionId,
        ]);

        // Student 1 submits the previous token immediately within grace period (e.g. in-flight request delay)
        $scanResponse = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'  => $prevToken,
            'method' => 'qr'
        ]);
        $scanResponse->assertOk()
            ->assertJson(['success' => true]);

        // Student 2 submits the previous 6-digit code immediately within grace period
        $codeResponse = $this->actingAs($this->student2)->postJson('/qr/scan-process', [
            'code'   => $prevCode,
            'method' => 'code'
        ]);
        $codeResponse->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_server_rejects_expired_codes_past_grace_period()
    {
        $startResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'radius_meters' => 0,
        ]);
        $sessionId = $startResponse->json('session_id');
        $code      = $startResponse->json('session_code');
        $token     = $startResponse->json('token');

        $session = AttendanceSession::find($sessionId);

        // Simulate that 30 seconds have passed without refresh (well past 15s window + 5s grace)
        $session->update([
            'expires_at' => now('Asia/Manila')->subSeconds(25)
        ]);

        // Student tries to submit the expired code
        $codeResponse = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'code'   => $code,
            'method' => 'code'
        ]);
        $codeResponse->assertStatus(422)
            ->assertJson([
                'success'    => false,
                'error_type' => 'invalid_or_expired',
            ]);
        $this->assertStringContainsString('expired', strtolower($codeResponse->json('message')));

        // Student tries to submit the expired token
        $tokenResponse = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'  => $token,
            'method' => 'qr'
        ]);
        $tokenResponse->assertStatus(422)
            ->assertJson([
                'success'    => false,
                'error_type' => 'invalid_or_expired',
            ]);
        $this->assertStringContainsString('expired', strtolower($tokenResponse->json('message')));

        // Ensure no attendance record was created
        $this->assertDatabaseMissing('attendances', [
            'user_id'      => $this->student->id,
            'subject_code' => $this->subject->code
        ]);
    }

    public function test_older_rotation_codes_from_previous_cycles_are_rejected()
    {
        $startResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'radius_meters' => 0,
        ]);
        $sessionId = $startResponse->json('session_id');
        $cycle1Code  = $startResponse->json('session_code');
        $cycle1Token = $startResponse->json('token');

        // Cycle 2
        $this->actingAs($this->teacher)->postJson('/teacher/qr/refresh', [
            'session_id' => $sessionId,
        ]);

        // Cycle 3
        $cycle3Response = $this->actingAs($this->teacher)->postJson('/teacher/qr/refresh', [
            'session_id' => $sessionId,
        ]);
        $cycle3Code = $cycle3Response->json('session_code');

        // Cycle 1 code is now 2 rotations old (definitely invalid)
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'code'   => $cycle1Code,
            'method' => 'code'
        ]);
        $response->assertStatus(422)
            ->assertJson([
                'success'    => false,
                'error_type' => 'invalid_or_expired',
            ]);

        // Cycle 3 (current) code is valid
        $validResponse = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'code'   => $cycle3Code,
            'method' => 'code'
        ]);
        $validResponse->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_qr_and_code_are_synchronized_to_same_active_session()
    {
        $startResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'radius_meters' => 0,
        ]);
        $sessionId = $startResponse->json('session_id');
        $token     = $startResponse->json('token');
        $code      = $startResponse->json('session_code');

        // Both student 1 (QR) and student 2 (code) record attendance
        $this->actingAs($this->student)->postJson('/qr/scan-process', ['token' => $token, 'method' => 'qr']);
        $this->actingAs($this->student2)->postJson('/qr/scan-process', ['code' => $code, 'method' => 'code']);

        $attendances = Attendance::where('subject_code', $this->subject->code)->get();
        $this->assertCount(2, $attendances);

        foreach ($attendances as $att) {
            $this->assertEquals($sessionId, $att->session_id);
            $this->assertEquals('Present', $att->status);
        }
    }

    public function test_direct_scan_url_validates_15_second_expiration()
    {
        $startResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'radius_meters' => 0,
        ]);
        $sessionId = $startResponse->json('session_id');
        $token     = $startResponse->json('token');

        // Valid scan when current
        $response = $this->actingAs($this->student)->get("/qr/scan/{$token}");
        $response->assertOk();

        // Expire token past 15s window + grace
        $session = AttendanceSession::find($sessionId);
        $session->update([
            'expires_at' => now('Asia/Manila')->subSeconds(25)
        ]);

        $expiredResponse = $this->actingAs($this->student)->get("/qr/scan/{$token}");
        $expiredResponse->assertViewIs('qr.result');
        $expiredResponse->assertViewHas('status', 'expired');
    }

    public function test_teacher_page_view_supplies_15_second_ttl_and_remaining_seconds()
    {
        $session = AttendanceSession::create([
            'subject_code'          => $this->subject->code,
            'created_by'            => $this->teacher->id,
            'token'                 => AttendanceSession::generateToken($this->subject->code),
            'session_code'          => AttendanceSession::generateSessionCode(),
            'previous_session_code' => null,
            'previous_token'        => null,
            'expires_at'            => now('Asia/Manila')->addSeconds(10),
            'session_ends_at'       => now('Asia/Manila')->addMinutes(20),
            'active'                => true,
            'classroom_lat'         => 14.538800,
            'classroom_lng'         => 121.022300,
            'radius_meters'         => 50,
            'grace_period_minutes'  => 5,
        ]);

        $viewResponse = $this->actingAs($this->teacher)->get("/teacher/qr/{$this->subject->code}");
        $viewResponse->assertOk();
        $payload = $viewResponse->viewData('activeSessionPayload');

        $this->assertNotNull($payload);
        $this->assertEquals(15, $payload['ttl']);
        $this->assertGreaterThanOrEqual(8, $payload['remaining_ttl']);
        $this->assertLessThanOrEqual(11, $payload['remaining_ttl']);
    }
}
