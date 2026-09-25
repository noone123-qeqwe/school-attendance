<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\WebauthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class QrDistanceValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Subject $subject;
    private AttendanceSession $session;
    private float $classroomLat = 14.500000;
    private float $classroomLng = 121.000000;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('gps_radius', 50);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $this->student = User::factory()->create([
            'role'       => 'student',
            'year_level' => 1,
            'semester'   => 1,
            'course'     => 'BSCS',
            'section'    => '1A',
        ]);

        $this->student->webauthnCredentials()->create([
            'credential_id' => 'fake_cred_dist',
            'public_key'    => 'fake_key_dist',
            'sign_count'    => 0,
            'user_handle'   => 'fake_handle_dist',
        ]);

        $this->subject = Subject::factory()->create([
            'instructor_id' => $this->teacher->id,
            'code'          => 'DIST101',
            'year_level'    => 1,
            'semester'      => 1,
            'course'        => 'BSCS',
            'section'       => '1A',
        ]);

        Schedule::create([
            'subject_id' => $this->subject->id,
            'day'        => today()->format('l'),
            'start_time' => now()->subMinutes(10)->format('H:i:s'),
            'end_time'   => now()->addMinutes(50)->format('H:i:s'),
        ]);

        $this->student->enrolledSubjects()->attach($this->subject->id);

        $this->session = AttendanceSession::create([
            'subject_code'    => $this->subject->code,
            'token'           => AttendanceSession::generateToken($this->subject->code),
            'session_code'    => AttendanceSession::generateSessionCode(),
            'created_by'      => $this->teacher->id,
            'classroom_lat'   => $this->classroomLat,
            'classroom_lng'   => $this->classroomLng,
            'radius_meters'   => 50,
            'active'          => true,
            'expires_at'      => now()->addHours(2),
            'session_ends_at' => now()->addHours(2),
        ]);
    }

    public function test_impossible_recent_location_jump_requires_retry_in_direct_scan()
    {
        $this->recordPreviousLocation(14.650000, 121.000000, 10, 20);

        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token' => $this->session->token,
            'latitude' => $this->classroomLat,
            'longitude' => $this->classroomLng,
            'accuracy' => 10,
        ]);

        $response->assertStatus(422)->assertJsonPath('error_type', 'location_jump_review');
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->student->id,
            'session_id' => $this->session->id,
        ]);
    }

    public function test_impossible_recent_location_jump_requires_retry_after_webauthn()
    {
        $this->recordPreviousLocation(14.650000, 121.000000, 10, 20);
        $this->actingAs($this->student)->postJson('/qr/verify-options', ['token' => $this->session->token]);

        $mock = Mockery::mock(WebauthnService::class);
        $mock->shouldReceive('verifyAssertion')->once()->andReturn(new WebauthnCredential());
        $this->app->instance(WebauthnService::class, $mock);

        $response = $this->actingAs($this->student)->postJson('/qr/verify-complete', [
            'token' => $this->session->token,
            'latitude' => $this->classroomLat,
            'longitude' => $this->classroomLng,
            'accuracy' => 10,
            'credential' => ['id' => 'fake', 'response' => []],
        ]);

        $response->assertStatus(422)->assertJsonPath('error_type', 'location_jump_review');
    }

    public function test_ordinary_movement_is_not_flagged_as_gps_spoofing()
    {
        $this->recordPreviousLocation(14.500500, 121.000000, 15, 20);

        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token' => $this->session->token,
            'latitude' => $this->classroomLat,
            'longitude' => $this->classroomLng,
            'accuracy' => 15,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_vpn_or_proxy_headers_do_not_bypass_gps_geofence()
    {
        $response = $this->actingAs($this->student)
            ->withHeaders(['X-Forwarded-For' => '127.0.0.1', 'Via' => '1.1 proxy'])
            ->postJson('/qr/scan-process', [
                'token' => $this->session->token,
                'latitude' => 14.600000,
                'longitude' => $this->classroomLng,
                'accuracy' => 5,
            ]);

        $response->assertStatus(422)->assertJsonPath('error_type', 'outside_classroom');
    }

    public function test_started_fingerprint_verification_can_finish_after_qr_rotates()
    {
        $originalToken = $this->session->token;
        $this->actingAs($this->student)->postJson('/qr/verify-options', ['token' => $originalToken])
            ->assertOk()->assertJsonPath('success', true);

        $this->session->update([
            'token' => AttendanceSession::generateToken($this->subject->code),
            'previous_token' => AttendanceSession::generateToken($this->subject->code),
            'expires_at' => now()->addSeconds(15),
        ]);

        $mock = Mockery::mock(WebauthnService::class);
        $mock->shouldReceive('verifyAssertion')->once()->andReturn(new WebauthnCredential());
        $this->app->instance(WebauthnService::class, $mock);

        $this->actingAs($this->student)->postJson('/qr/verify-complete', [
            'token' => $originalToken,
            'latitude' => $this->classroomLat,
            'longitude' => $this->classroomLng,
            'accuracy' => 10,
            'credential' => ['id' => 'fake', 'response' => []],
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_rotated_qr_cannot_start_new_verification()
    {
        $originalToken = $this->session->token;
        $this->session->update([
            'token' => AttendanceSession::generateToken($this->subject->code),
            'previous_token' => AttendanceSession::generateToken($this->subject->code),
        ]);

        $this->actingAs($this->student)->postJson('/qr/verify-options', [
            'token' => $originalToken,
        ])->assertStatus(422);
    }

    public function test_started_qr_completion_window_expires()
    {
        $originalToken = $this->session->token;
        $this->actingAs($this->student)->postJson('/qr/verify-options', ['token' => $originalToken])
            ->assertOk();
        $this->session->update([
            'token' => AttendanceSession::generateToken($this->subject->code),
            'previous_token' => AttendanceSession::generateToken($this->subject->code),
        ]);

        $this->travel(91)->seconds();
        $this->actingAs($this->student)->postJson('/qr/verify-complete', [
            'token' => $originalToken,
            'latitude' => $this->classroomLat,
            'longitude' => $this->classroomLng,
            'accuracy' => 10,
            'credential' => ['id' => 'fake', 'response' => []],
        ])->assertStatus(422);
    }

    private function recordPreviousLocation(float $lat, float $lng, float $accuracy, int $secondsAgo): void
    {
        $previousSession = AttendanceSession::factory()->create([
            'subject_code' => 'OTHER101',
            'created_by' => $this->teacher->id,
            'classroom_lat' => $lat,
            'classroom_lng' => $lng,
        ]);

        Attendance::create([
            'user_id' => $this->student->id,
            'session_id' => $previousSession->id,
            'subject_code' => 'OTHER101',
            'date' => today()->toDateString(),
            'status' => 'Present',
            'last_latitude' => $lat,
            'last_longitude' => $lng,
            'last_accuracy' => $accuracy,
            'last_location_check_at' => now()->subSeconds($secondsAgo),
        ]);
    }

    public function test_student_right_at_qr_location_is_accepted()
    {
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
            'accuracy'  => 5,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertDatabaseHas('attendances', [
            'user_id'      => $this->student->id,
            'session_id'   => $this->session->id,
            'subject_code' => $this->subject->code,
        ]);
    }

    public function test_student_within_configured_radius_is_accepted()
    {
        // 14.500300, 121.000000 is ~33.3 meters away from classroom center
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.500300,
            'longitude' => 121.000000,
            'accuracy'  => 10,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    public function test_student_beyond_50_meters_is_rejected_even_with_accuracy_margin()
    {
        // Reported accuracy must never reduce the measured 64.5m distance.
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.500580,
            'longitude' => 121.000000,
            'accuracy'  => 25,
        ]);

        $response->assertStatus(422)->assertJsonPath('error_type', 'outside_classroom');
        $this->assertGreaterThan(50, $response->json('distance'));
    }

    public function test_student_genuinely_outside_radius_is_rejected_with_outside_classroom()
    {
        // 14.501800, 121.000000 is ~200 meters away
        // With accuracy 15m, effective distance is ~185m > 50m
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.501800,
            'longitude' => 121.000000,
            'accuracy'  => 15,
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('outside_classroom', $response->json('error_type'));
        $this->assertGreaterThan(50, $response->json('distance'));
        $this->assertEquals(50, $response->json('radius'));
        $this->assertStringContainsString('outside the classroom boundary', $response->json('message'));
        $this->assertStringContainsString('allowed within 50m', $response->json('message'));
    }

    public function test_weak_gps_signal_is_handled_gracefully_with_unreliable_gps()
    {
        // Accuracy > 150m is too inaccurate to verify presence inside a classroom.
        // It must NOT falsely claim "outside the classroom boundary".
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.500800,
            'longitude' => 121.000000,
            'accuracy'  => 180, // Very weak signal / cell tower fix
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('unreliable_gps', $response->json('error_type'));
        $this->assertStringNotContainsString('outside the classroom boundary', $response->json('message'));
        $this->assertStringContainsString('GPS signal accuracy is too low', $response->json('message'));
    }

    public function test_invalid_gps_accuracy_reading_is_rejected()
    {
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
            'accuracy'  => 0, // Mock or corrupted GPS
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('unreliable_gps', $response->json('error_type'));
        $this->assertStringContainsString('Invalid GPS accuracy reading detected', $response->json('message'));
    }

    public function test_session_custom_radius_is_properly_applied()
    {
        // Teacher configures a larger lecture hall radius of 100 meters
        $this->session->update(['radius_meters' => 100]);

        // Student is ~75m away (14.500670, 121.000000) with 10m accuracy
        // Raw distance: ~74.5m
        // If default 50m was mistakenly used, it would fail.
        // With session radius 100m, it must succeed!
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.500670,
            'longitude' => 121.000000,
            'accuracy'  => 10,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Student at 160m away is rejected against the 100m radius
        $outsideStudent = User::factory()->create([
            'role'       => 'student',
            'year_level' => 1,
            'semester'   => 1,
            'course'     => 'BSCS',
            'section'    => '1A',
        ]);
        $outsideStudent->enrolledSubjects()->attach($this->subject->id);

        $outsideResponse = $this->actingAs($outsideStudent)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.501500, // ~166m away
            'longitude' => 121.000000,
            'accuracy'  => 10,
        ]);

        $outsideResponse->assertStatus(422);
        $this->assertEquals('outside_classroom', $outsideResponse->json('error_type'));
        $this->assertEquals(100, $outsideResponse->json('radius'));
        $this->assertStringContainsString('allowed within 100m', $outsideResponse->json('message'));
    }

    public function test_verify_attendance_webauthn_endpoint_distance_validation()
    {
        // 1. Request options
        $this->actingAs($this->student)->postJson('/qr/verify-options', ['token' => $this->session->token]);

        $mockWebauthn = Mockery::mock(WebauthnService::class);
        $mockCredential = new WebauthnCredential();
        $mockWebauthn->shouldReceive('verifyAssertion')->andReturn($mockCredential);
        $this->app->instance(WebauthnService::class, $mockWebauthn);

        // Student at 33m with a reliable fix is inside the session radius.
        $passResponse = $this->actingAs($this->student)->postJson('/qr/verify-complete', [
            'token'      => $this->session->token,
            'latitude'   => 14.500300,
            'longitude'  => 121.000000,
            'accuracy'   => 25,
            'credential' => '{"id":"fake","rawId":"fake","response":{},"type":"public-key"}',
        ]);

        $passResponse->assertStatus(200);
        $this->assertTrue($passResponse->json('success'));

        // Another student testing outside rejection
        $student2 = User::factory()->create([
            'role'       => 'student',
            'year_level' => 1,
            'semester'   => 1,
            'course'     => 'BSCS',
            'section'    => '1A',
        ]);
        $student2->enrolledSubjects()->attach($this->subject->id);
        $student2->webauthnCredentials()->create([
            'credential_id' => 'fake_cred_dist_2',
            'public_key'    => 'fake_key_dist_2',
            'sign_count'    => 0,
            'user_handle'   => 'fake_handle_dist_2',
        ]);

        $this->actingAs($student2)->postJson('/qr/verify-options', ['token' => $this->session->token]);

        $outsideResponse = $this->actingAs($student2)->postJson('/qr/verify-complete', [
            'token'      => $this->session->token,
            'latitude'   => 14.500580, // ~64.5m: accuracy must not expand the 50m boundary
            'longitude'  => 121.000000,
            'accuracy'   => 25,
            'credential' => '{"id":"fake2","rawId":"fake2","response":{},"type":"public-key"}',
        ]);

        $outsideResponse->assertStatus(422);
        $this->assertFalse($outsideResponse->json('success'));
        $this->assertEquals('outside_classroom', $outsideResponse->json('error_type'));
        $this->assertStringContainsString('outside the classroom', $outsideResponse->json('message'));
        $this->assertEquals(50, $outsideResponse->json('radius'));

        // Weak GPS accuracy check on verify-complete
        $weakGpsResponse = $this->actingAs($student2)->postJson('/qr/verify-complete', [
            'token'      => $this->session->token,
            'latitude'   => 14.500200,
            'longitude'  => 121.000000,
            'accuracy'   => 200,
            'credential' => '{"id":"fake2","rawId":"fake2","response":{},"type":"public-key"}',
        ]);

        $weakGpsResponse->assertStatus(422);
        $this->assertEquals('unreliable_gps', $weakGpsResponse->json('error_type'));
    }

    public function test_system_default_does_not_override_session_radius()
    {
        // Changing the default cannot silently expand an already active 50m session.
        Setting::set('gps_radius', 85);
        $this->assertEquals(50, $this->session->getAllowedRadius());

        // Student is ~68m away, outside this specific session.
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.500612,
            'longitude' => 121.000000,
            'accuracy'  => 5,
        ]);

        $response->assertStatus(422)->assertJsonPath('error_type', 'outside_classroom');
        $this->assertEquals(50, $response->json('radius'));
    }

    public function test_indoor_gps_accuracy_does_not_expand_boundary()
    {
        // Student is 85m away. A 50m accuracy reading does not make that inside.
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.500765,
            'longitude' => 121.000000,
            'accuracy'  => 50,
        ]);

        $response->assertStatus(422)->assertJsonPath('error_type', 'outside_classroom');
    }

    public function test_presence_verification_uses_raw_distance()
    {
        // First clock in
        $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
            'accuracy'  => 10.0,
        ]);

        // Student is ~65m away; monitoring must not subtract reported accuracy.
        $response = $this->actingAs($this->student)->postJson('/student/presence-verify', [
            'session_id' => $this->session->id,
            'latitude'   => 14.500580,
            'longitude'  => 121.000000,
            'accuracy'   => 25,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('Present', $response->json('status'));
        $this->assertEquals('warning', $response->json('monitoring_status'));
    }

    public function test_geofence_can_be_disabled_with_zero_radius()
    {
        // The radius column is unsigned; zero is the explicit disabled state.
        $this->session->update(['radius_meters' => 0]);
        $this->assertEquals(0, $this->session->getAllowedRadius());

        // Student is 50km away
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.950000,
            'longitude' => 121.450000,
            'accuracy'  => 10,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    public function test_scan_rejected_with_clear_message_when_teacher_coordinates_null()
    {
        // Teacher creates session without GPS coordinates
        $this->session->update([
            'classroom_lat' => null,
            'classroom_lng' => null,
            'radius_meters' => 50,
        ]);

        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.509600,
            'longitude' => 121.009000,
            'accuracy'  => 12,
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('teacher_location_unavailable', $response->json('error_type'));
        $this->assertStringContainsString('teacher\'s laptop location is not available', $response->json('message'));
    }

    public function test_teacher_can_update_active_session_location_via_endpoint()
    {
        $response = $this->actingAs($this->teacher)->postJson(route('teacher.qr.update-location'), [
            'session_id'    => $this->session->id,
            'classroom_lat' => 14.509612,
            'classroom_lng' => 121.009045,
            'teacher_accuracy' => 10,
            'radius_meters' => 100,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals(14.509612, $response->json('classroom_lat'));
        $this->assertEquals(121.009045, $response->json('classroom_lng'));
        $this->assertEquals(100, $response->json('radius_meters'));

        $this->session->refresh();
        $this->assertEquals(14.509612, (float) $this->session->classroom_lat);
        $this->assertEquals(121.009045, (float) $this->session->classroom_lng);
        $this->assertEquals(100, $this->session->radius_meters);
    }

    public function test_geofenced_session_requires_a_fresh_accurate_teacher_location()
    {
        $missing = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
        ]);
        $missing->assertStatus(422);

        $poor = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'classroom_lat' => 12.371250,
            'classroom_lng' => 123.619437,
            'teacher_accuracy' => 250,
        ]);
        $poor->assertStatus(422);

        $good = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'classroom_lat' => 12.371250,
            'classroom_lng' => 123.619437,
            'teacher_accuracy' => 12,
        ]);
        $good->assertOk()->assertJsonPath('radius_meters', 50);
        $this->assertDatabaseHas('attendance_sessions', [
            'id' => $good->json('session_id'),
            'classroom_lat' => 12.371250,
            'classroom_lng' => 123.619437,
            'radius_meters' => 50,
        ]);
    }

    public function test_radius_change_cannot_move_session_center_or_set_zero_zero()
    {
        $response = $this->actingAs($this->teacher)->postJson(route('teacher.qr.update-location'), [
            'session_id' => $this->session->id,
            'radius_meters' => 100,
        ]);
        $response->assertOk();
        $this->session->refresh();
        $this->assertEquals($this->classroomLat, (float) $this->session->classroom_lat);
        $this->assertEquals($this->classroomLng, (float) $this->session->classroom_lng);

        $invalid = $this->actingAs($this->teacher)->postJson(route('teacher.qr.update-location'), [
            'session_id' => $this->session->id,
            'classroom_lat' => 0,
            'classroom_lng' => 0,
            'teacher_accuracy' => 5,
        ]);
        $invalid->assertStatus(422);
        $this->session->refresh();
        $this->assertEquals($this->classroomLat, (float) $this->session->classroom_lat);
        $this->assertEquals($this->classroomLng, (float) $this->session->classroom_lng);
    }

    public function test_geofenced_scan_requires_student_accuracy_and_coordinates()
    {
        $missingAccuracy = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token' => $this->session->token,
            'latitude' => $this->classroomLat,
            'longitude' => $this->classroomLng,
        ]);
        $missingAccuracy->assertStatus(422)->assertJsonPath('error_type', 'unreliable_gps');

        $missingLocation = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token' => $this->session->token,
        ]);
        $missingLocation->assertStatus(422)->assertJsonPath('error_type', 'location_required');
    }

    public function test_masbate_student_physically_beside_teacher_laptop_is_accepted()
    {
        // Teacher's laptop is at Masbate location (12.371250, 123.619437)
        $this->session->update([
            'classroom_lat' => 12.371250,
            'classroom_lng' => 123.619437,
            'radius_meters' => 50,
        ]);

        // Student scans physically beside the teacher's laptop (~5.5 meters away)
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 12.371300,
            'longitude' => 123.619437,
            'accuracy'  => 10,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    public function test_inverted_coordinates_are_rejected_at_validation()
    {
        $this->session->update([
            'classroom_lat' => 12.371250,
            'classroom_lng' => 123.619437,
            'radius_meters' => 50,
        ]);

        // Student submits swapped coordinates (latitude: 123.619440, longitude: 12.371260)
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 123.619440,
            'longitude' => 12.371260,
            'accuracy'  => 8,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('latitude');
    }

    public function test_poor_gps_accuracy_returns_unreliable_gps_message_instead_of_outside_classroom()
    {
        $this->session->update([
            'classroom_lat' => 12.371250,
            'classroom_lng' => 123.619437,
            'radius_meters' => 50,
        ]);

        // Student submits with poor GPS accuracy (e.g. 180m indoor Wi-Fi)
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 12.371250,
            'longitude' => 123.619437,
            'accuracy'  => 180,
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('unreliable_gps', $response->json('error_type'));
        $this->assertStringContainsString('GPS signal accuracy is too low', $response->json('message'));
        $this->assertStringContainsString('High Accuracy GPS', $response->json('message'));
    }
}
