<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Subject;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class PresenceVerificationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $student;
    protected Subject $subject;
    protected AttendanceSession $session;
    protected float $classroomLat = 14.599512;
    protected float $classroomLng = 120.984222;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Dr. Eleanor Vance',
        ]);

        $this->student = User::factory()->create([
            'role'           => 'student',
            'name'           => 'Marcus Rivera',
            'student_number' => 'STU-2026-999',
            'year_level'     => 2,
            'semester'       => 1,
            'course'         => 'BSIT',
            'section'        => '2B',
        ]);

        $this->subject = Subject::create([
            'code'          => 'IT201',
            'name'          => 'Network Security & Forensics',
            'units'         => 3,
            'year_level'    => 2,
            'semester'      => 1,
            'course'        => 'BSIT',
            'section'       => '2B',
            'instructor_id' => $this->teacher->id,
            'instructor'    => 'Dr. Eleanor Vance',
        ]);

        $this->student->enrolledSubjects()->attach($this->subject->id);

        $now = now('Asia/Manila');
        $this->session = AttendanceSession::create([
            'subject_code'         => $this->subject->code,
            'created_by'           => $this->teacher->id,
            'token'                => AttendanceSession::generateToken($this->subject->code),
            'session_code'         => '888222',
            'expires_at'           => $now->copy()->addMinutes(10),
            'session_ends_at'      => $now->copy()->addMinutes(45),
            'active'               => true,
            'classroom_lat'        => $this->classroomLat,
            'classroom_lng'        => $this->classroomLng,
            'radius_meters'        => 50,
            'grace_period_minutes' => 5,
        ]);
    }

    public function test_check_in_records_presence_metadata_and_returns_configs()
    {
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
            'accuracy'  => 8.5,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'              => true,
                'status'               => 'Present',
                'monitoring_active'    => true,
                'radius'               => 50,
                'grace_period_minutes' => 5,
            ]);

        $this->assertDatabaseHas('attendances', [
            'user_id'           => $this->student->id,
            'session_id'        => $this->session->id,
            'status'            => 'Present',
            'monitoring_status' => 'active',
            'last_accuracy'     => 8.5,
        ]);

        $att = Attendance::where('user_id', $this->student->id)->first();
        $this->assertNotNull($att->checked_in_at);
        $this->assertNotNull($att->last_location_check_at);
        $this->assertNull($att->outside_since);
        $this->assertEquals(0, $att->consecutive_outside_count);
    }

    public function test_inside_classroom_presence_ping_maintains_present_status()
    {
        // First clock in
        $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
            'accuracy'  => 10.0,
        ]);

        // Student sends periodic presence ping inside (within 15 meters)
        $insideLat = $this->classroomLat + 0.0001; // ~11 meters away
        $insideLng = $this->classroomLng + 0.0001;

        $response = $this->actingAs($this->student)->postJson('/student/presence-verify', [
            'session_id' => $this->session->id,
            'latitude'   => $insideLat,
            'longitude'  => $insideLng,
            'accuracy'   => 12.0,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'           => true,
                'status'            => 'Present',
                'monitoring_status' => 'active',
                'radius'            => 50,
            ]);

        $this->assertDatabaseHas('attendances', [
            'user_id'           => $this->student->id,
            'status'            => 'Present',
            'monitoring_status' => 'active',
            'outside_since'     => null,
        ]);
    }

    public function test_unreliable_gps_accuracy_does_not_falsely_penalize_student()
    {
        $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
        ]);

        // Ping with poor cell tower accuracy (e.g., 250 meters accuracy)
        $response = $this->actingAs($this->student)->postJson('/student/presence-verify', [
            'session_id' => $this->session->id,
            'latitude'   => $this->classroomLat + 0.005, // GPS spike
            'longitude'  => $this->classroomLng + 0.005,
            'accuracy'   => 250.0,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'           => true,
                'status'            => 'Present',
                'monitoring_status' => 'unreliable_gps',
            ]);

        // Verify student is still Present and not marked outside
        $att = Attendance::where('user_id', $this->student->id)->first();
        $this->assertEquals('Present', $att->status);
        $this->assertEquals(0, $att->consecutive_outside_count);
    }

    public function test_first_outside_reading_enters_warning_grace_period()
    {
        $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
        ]);

        // Student steps outside (300 meters away)
        $outsideLat = $this->classroomLat + 0.003;
        $outsideLng = $this->classroomLng + 0.003;

        $response = $this->actingAs($this->student)->postJson('/student/presence-verify', [
            'session_id' => $this->session->id,
            'latitude'   => $outsideLat,
            'longitude'  => $outsideLng,
            'accuracy'   => 10.0,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'           => true,
                'status'            => 'Present', // Still present!
                'monitoring_status' => 'warning',
            ]);

        $this->assertGreaterThan(0, $response->json('remaining_grace_seconds'));

        $att = Attendance::where('user_id', $this->student->id)->first();
        $this->assertEquals('Present', $att->status);
        $this->assertEquals('warning', $att->monitoring_status);
        $this->assertNotNull($att->outside_since);
        $this->assertEquals(1, $att->consecutive_outside_count);
    }

    public function test_returning_inside_before_grace_period_expires_restores_active_presence()
    {
        $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
        ]);

        // Step outside -> Warning
        $this->actingAs($this->student)->postJson('/student/presence-verify', [
            'session_id' => $this->session->id,
            'latitude'   => $this->classroomLat + 0.003,
            'longitude'  => $this->classroomLng + 0.003,
            'accuracy'   => 10.0,
        ]);

        // Student returns back inside classroom
        $response = $this->actingAs($this->student)->postJson('/student/presence-verify', [
            'session_id' => $this->session->id,
            'latitude'   => $this->classroomLat,
            'longitude'  => $this->classroomLng,
            'accuracy'   => 8.0,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'           => true,
                'status'            => 'Present',
                'monitoring_status' => 'active',
            ]);

        $att = Attendance::where('user_id', $this->student->id)->first();
        $this->assertEquals('Present', $att->status);
        $this->assertEquals('active', $att->monitoring_status);
        $this->assertNull($att->outside_since);
        $this->assertEquals(0, $att->consecutive_outside_count);
    }

    public function test_exceeding_grace_period_outside_marks_student_as_escaped()
    {
        $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
        ]);

        $att = Attendance::where('user_id', $this->student->id)->first();
        // Simulate that student was detected outside 6 minutes ago (exceeding 5-minute grace period)
        $att->update([
            'outside_since'             => now()->subMinutes(6),
            'consecutive_outside_count' => 2,
            'monitoring_status'         => 'warning',
        ]);

        // Another check confirms student is still outside
        $outsideLat = $this->classroomLat + 0.005;
        $outsideLng = $this->classroomLng + 0.005;

        $response = $this->actingAs($this->student)->postJson('/student/presence-verify', [
            'session_id' => $this->session->id,
            'latitude'   => $outsideLat,
            'longitude'  => $outsideLng,
            'accuracy'   => 10.0,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'           => true,
                'status'            => 'Escaped',
                'monitoring_status' => 'escaped',
            ]);

        $att->refresh();
        $this->assertEquals('Escaped', $att->status);
        $this->assertEquals('escaped', $att->monitoring_status);
        $this->assertNotNull($att->escaped_at);

        // Even if student later returns inside, status remains Escaped unless teacher overrides
        $returnResponse = $this->actingAs($this->student)->postJson('/student/presence-verify', [
            'session_id' => $this->session->id,
            'latitude'   => $this->classroomLat,
            'longitude'  => $this->classroomLng,
            'accuracy'   => 5.0,
        ]);

        $returnResponse->assertStatus(200)
            ->assertJson([
                'status'            => 'Escaped',
                'monitoring_status' => 'escaped',
            ]);
    }

    public function test_teacher_dashboard_shows_escaped_and_outside_students_and_allows_override()
    {
        // Create an escaped attendance record
        Attendance::create([
            'user_id'                   => $this->student->id,
            'subject_code'              => $this->subject->code,
            'subject_id'                => $this->subject->id,
            'session_id'                => $this->session->id,
            'date'                      => today()->toDateString(),
            'time_in'                   => '08:05:00',
            'status'                    => 'Escaped',
            'monitoring_status'         => 'escaped',
            'last_distance_meters'      => 450.0,
            'last_location_check_at'    => now(),
            'escaped_at'                => now(),
            'outside_since'             => now()->subMinutes(10),
            'consecutive_outside_count' => 3,
        ]);

        // Teacher fetches live clock-ins
        $response = $this->actingAs($this->teacher)->getJson('/teacher/qr/clockins?session_id=' . $this->session->id);

        $response->assertStatus(200)
            ->assertJson([
                'stats' => [
                    'escaped' => 1,
                ]
            ]);

        $clockins = $response->json('clockins');
        $this->assertNotEmpty($clockins);
        $this->assertEquals('Escaped', $clockins[0]['status']);
        $this->assertEquals('450m', $clockins[0]['distance']);

        // Teacher overrides status back to Present (e.g. excused clinic visit)
        $overrideResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/override', [
            'session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'status'     => 'Present',
        ]);

        $overrideResponse->assertStatus(200)->assertJson(['success' => true]);

        $att = Attendance::where('user_id', $this->student->id)->first();
        $this->assertEquals('Present', $att->status);
        $this->assertEquals('active', $att->monitoring_status);
        $this->assertNull($att->escaped_at);
        $this->assertNull($att->outside_since);
    }

    public function test_session_end_finalizes_monitoring()
    {
        $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
        ]);

        // Teacher stops session
        $this->session->update(['active' => false]);

        // Student presence ping detects session conclusion
        $response = $this->actingAs($this->student)->postJson('/student/presence-verify', [
            'session_id' => $this->session->id,
            'latitude'   => $this->classroomLat,
            'longitude'  => $this->classroomLng,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'session_active'    => false,
                'monitoring_status' => 'completed',
                'status'            => 'Present',
            ]);

        $att = Attendance::where('user_id', $this->student->id)->first();
        $this->assertEquals('Present', $att->status);
        $this->assertEquals('completed', $att->monitoring_status);
    }

    public function test_heartbeat_staleness_sweep_transitions_silent_abandonment_to_escaped()
    {
        // Student clocks in
        $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
        ]);

        $att = Attendance::where('user_id', $this->student->id)->first();
        $this->assertEquals('Present', $att->status);

        // Student closes tab / kills browser for 10 minutes (exceeding 5-minute grace period)
        $att->update([
            'last_location_check_at' => now()->subMinutes(10),
            'checked_in_at'          => now()->subMinutes(10),
        ]);

        // Teacher screen polls clockins
        $response = $this->actingAs($this->teacher)->getJson('/teacher/qr/clockins?session_id=' . $this->session->id);
        $response->assertStatus(200);

        // Verify that the staleness audit automatically detected silence and marked student as Escaped
        $att->refresh();
        $this->assertEquals('Escaped', $att->status);
        $this->assertEquals('escaped', $att->monitoring_status);
        $this->assertNotNull($att->escaped_at);

        $studentClockin = collect($response->json('clockins'))->firstWhere('id', $this->student->id);
        $this->assertEquals('Escaped', $studentClockin['status']);
    }

    public function test_location_permission_denial_reports_and_triggers_grace_period()
    {
        // Student clocks in
        $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
        ]);

        // Student revokes location permissions in browser -> Guardian posts error_code
        $response = $this->actingAs($this->student)->postJson('/student/presence-verify', [
            'session_id'    => $this->session->id,
            'error_code'    => 'permission_denied',
            'error_message' => 'User denied Geolocation',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'           => true,
                'status'            => 'Present',
                'monitoring_status' => 'warning',
            ]);

        $this->assertGreaterThan(0, $response->json('remaining_grace_seconds'));

        $att = Attendance::where('user_id', $this->student->id)->first();
        $this->assertEquals('Present', $att->status);
        $this->assertEquals('warning', $att->monitoring_status);
        $this->assertNotNull($att->outside_since);
    }

    public function test_geofence_enforced_when_coordinates_provided_even_if_teacher_coords_null()
    {
        // Teacher creates session without GPS coordinates (e.g. from a desktop PC)
        $this->session->update([
            'classroom_lat' => null,
            'classroom_lng' => null,
        ]);

        // Student attempts to scan from far away (14.9000, 121.5000 is ~60km away)
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => 14.9000,
            'longitude' => 121.5000,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success'    => false,
                'error_type' => 'outside_classroom',
            ]);
    }

    public function test_teleportation_leap_into_classroom_blocked()
    {
        // Student clocks in outside classroom (far away)
        Attendance::create([
            'user_id'                => $this->student->id,
            'session_id'             => $this->session->id,
            'subject_id'             => $this->subject->id,
            'subject_code'           => $this->subject->code,
            'date'                   => today()->toDateString(),
            'status'                 => 'Present',
            'last_latitude'          => $this->classroomLat + 0.05, // ~5.5 km away
            'last_longitude'         => $this->classroomLng + 0.05,
            'last_location_check_at' => now()->subSeconds(10), // 10 seconds ago
            'monitoring_status'      => 'warning',
        ]);

        // 10 seconds later, student claims to be directly inside classroom (speed ~ 550 m/s)
        $response = $this->actingAs($this->student)->postJson('/student/presence-verify', [
            'session_id' => $this->session->id,
            'latitude'   => $this->classroomLat,
            'longitude'  => $this->classroomLng,
            'accuracy'   => 15.0,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'           => true,
                'status'            => 'Present',
                'monitoring_status' => 'warning',
            ]);

        $this->assertStringContainsString('Abnormal location change detected', $response->json('message'));
    }

    public function test_proxy_attendance_blocked_when_same_device_used_by_multiple_students()
    {
        $peerStudent = User::factory()->create([
            'role'       => 'student',
            'year_level' => 2,
            'semester'   => 1,
            'course'     => 'BSIT',
            'section'    => '2B',
        ]);
        $peerStudent->enrolledSubjects()->attach($this->subject->id);

        $rawKey = 'shared_device_secret_xyz';
        $deviceHash = hash_hmac('sha256', $rawKey, config('app.key'));

        // Bind both student accounts to the same device hash
        \App\Models\DeviceBinding::create([
            'user_id'     => $this->student->id,
            'device_hash' => $deviceHash,
        ]);
        \App\Models\DeviceBinding::create([
            'user_id'     => $peerStudent->id,
            'device_hash' => $deviceHash,
        ]);

        // First student successfully clocks in from this device
        $clockIn1 = $this->actingAs($this->student)
            ->withSession(['device_bound_session' => true])
            ->postJson('/qr/scan-process', [
                'token'              => $this->session->token,
                'latitude'           => $this->classroomLat,
                'longitude'          => $this->classroomLng,
                'device_fingerprint' => $rawKey,
            ]);
        $clockIn1->assertStatus(200)->assertJson(['success' => true]);

        // Second student attempts to clock in into the same session using the same device
        $clockIn2 = $this->actingAs($peerStudent)
            ->withSession(['device_bound_session' => true])
            ->postJson('/qr/scan-process', [
                'token'              => $this->session->token,
                'latitude'           => $this->classroomLat,
                'longitude'          => $this->classroomLng,
                'device_fingerprint' => $rawKey,
            ]);

        $clockIn2->assertStatus(422)
            ->assertJson([
                'success'    => false,
                'error_type' => 'proxy_device_detected',
            ]);
    }

    public function test_suspicious_zero_accuracy_rejected()
    {
        $response = $this->actingAs($this->student)->postJson('/qr/scan-process', [
            'token'     => $this->session->token,
            'latitude'  => $this->classroomLat,
            'longitude' => $this->classroomLng,
            'accuracy'  => 0.0, // Mock/injected GPS sensor reading
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success'    => false,
                'error_type' => 'unreliable_gps',
            ]);
    }
}
