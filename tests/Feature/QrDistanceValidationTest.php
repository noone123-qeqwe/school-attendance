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

    public function test_student_physically_close_with_gps_inaccuracy_is_accepted_due_to_accuracy_margin()
    {
        // 14.500580, 121.000000 is ~64.5 meters away (raw distance exceeds 50m radius)
        // However, phone reports GPS accuracy of 25m (typical indoor reading)
        // Effective distance is 64.5m - 25m = 39.5m, which is within the 50m radius.
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.500580,
            'longitude' => 121.000000,
            'accuracy'  => 25,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
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

        // Student at 65m away with 25m accuracy should pass (effective 40m <= 50m)
        $passResponse = $this->actingAs($this->student)->postJson('/qr/verify-complete', [
            'token'      => $this->session->token,
            'latitude'   => 14.500580,
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
            'latitude'   => 14.502000, // ~222m away
            'longitude'  => 121.000000,
            'accuracy'   => 10,
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

    public function test_system_configured_gps_radius_is_applied_when_larger_than_session_radius()
    {
        // System admin configures GPS radius to 85 meters
        Setting::set('gps_radius', 85);
        $this->assertEquals(85, $this->session->getAllowedRadius());

        // Student is ~68m away (14.500612, 121.000000) with 5m accuracy
        // Effective distance is ~63m (would fail if only 50m applied, but passes with configured 85m)
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.500612,
            'longitude' => 121.000000,
            'accuracy'  => 5,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    public function test_indoor_gps_reading_with_larger_inaccuracy_allowance_is_accepted()
    {
        // Student is indoors, 85m away (14.500765, 121.000000)
        // Phone reports indoor accuracy of 50m
        // Effective distance is 85m - 50m = 35m <= 50m radius -> Accepted
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.500765,
            'longitude' => 121.000000,
            'accuracy'  => 50,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    public function test_presence_verification_applies_accuracy_margin_and_respects_radius()
    {
        // First clock in
        $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
            'accuracy'  => 10.0,
        ]);

        // Student is ~65m away with 25m accuracy (effective 40m <= 50m)
        $response = $this->actingAs($this->student)->postJson('/student/presence-verify', [
            'session_id' => $this->session->id,
            'latitude'   => 14.500580,
            'longitude'  => 121.000000,
            'accuracy'   => 25,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('Present', $response->json('status'));
        $this->assertEquals('active', $response->json('monitoring_status'));
    }
}
