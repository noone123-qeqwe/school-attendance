<?php

namespace Tests\Feature;

use App\Events\PeerAttendanceVerified;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\DeviceBinding;
use App\Models\PeerFaceEnrollment;
use App\Models\PeerVouchRequest;
use App\Models\Subject;
use App\Models\User;
use App\Services\DeviceBindingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PeerSnapAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $host;
    private User $student;
    private User $teacher;
    private Subject $subject;
    private AttendanceSession $session;
    private array $verifierResponse = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Http::fake(['verifier.example/*' => function () {
            return Http::response($this->verifierResponse, 200);
        }]);
        $this->travelTo(now('Asia/Manila')->startOfDay()->addHours(10));
        config([
            'peer_snap.enabled' => true,
            'peer_snap.verifier_url' => 'https://verifier.example/verify',
            'peer_snap.verifier_token' => 'test-secret',
            'peer_snap.model_version' => 'validated-v1',
            'peer_snap.max_vouches' => 2,
            'peer_snap.max_failed_attempts' => 3,
            'peer_snap.lockout_minutes' => 30,
        ]);
        $this->teacher = User::factory()->teacher()->create();
        $details = ['year_level' => 1, 'semester' => 1, 'course' => 'BSCS', 'section' => 'A'];
        $this->host = User::factory()->create($details);
        $this->student = User::factory()->create($details);
        $this->subject = Subject::create($details + [
            'code' => 'PEER101', 'name' => 'Peer Test Class', 'instructor_id' => $this->teacher->id,
        ]);
        $this->session = AttendanceSession::create([
            'subject_code' => $this->subject->code, 'created_by' => $this->teacher->id,
            'token' => Str::random(64), 'expires_at' => now()->addMinute(),
            'session_ends_at' => now()->addMinutes(20), 'active' => true,
            'classroom_lat' => 10.0, 'classroom_lng' => 123.0, 'radius_meters' => 50,
        ]);
        Attendance::create([
            'user_id' => $this->host->id, 'subject_code' => $this->subject->code,
            'subject_id' => $this->subject->id, 'date' => now('Asia/Manila')->toDateString(),
            'status' => 'Present', 'method' => 'qr', 'session_id' => $this->session->id,
            'monitoring_status' => 'active', 'last_location_check_at' => now(),
            'last_distance_meters' => 10,
        ]);
        DeviceBinding::create([
            'user_id' => $this->host->id,
            'device_hash' => app(DeviceBindingService::class)->hashDeviceKey('test-host-device'),
            'device_name' => 'Test host phone',
        ]);
        PeerFaceEnrollment::create([
            'user_id' => $this->student->id, 'verifier_subject_ref' => 'enrolled-'.$this->student->id,
            'model_version' => 'validated-v1', 'consented_at' => now(),
        ]);
        $this->actingAs($this->host)->withHeaders(['Accept' => 'application/json', 'X-Device-Key' => 'test-host-device']);
    }

    private function start(?User $student = null): array
    {
        return $this->postJson(route('peer-snap.start'), [
            'session_id' => $this->session->id,
            'student_number' => ($student ?? $this->student)->student_number,
        ])->assertCreated()->json();
    }

    private function frames(): array
    {
        return array_map(fn ($i) => UploadedFile::fake()->image('frame-'.$i.'.jpg', 100, 100), range(0, 4));
    }

    private function confirm(array $ticket, array $extra = [])
    {
        return $this->post(route('peer-snap.confirm'), array_merge([
            'verification_id' => $ticket['verification_id'], 'nonce' => $ticket['nonce'],
            'frames' => $this->frames(),
        ], $extra));
    }

    private function fakeVerifier(array $ticket, array $changes = []): void
    {
        $this->verifierResponse = array_merge([
            'verification_id' => $ticket['verification_id'], 'nonce' => $ticket['nonce'],
            'challenge' => $ticket['challenge'], 'subject_ref' => 'enrolled-'.$this->student->id,
            'model_version' => 'validated-v1',
            'frames' => array_fill(0, 5, ['face_count' => 1, 'quality_passed' => true, 'pad_score' => 0.98]),
            'challenge_passed' => true, 'similarity' => 0.95,
        ], $changes);
    }

    public function test_host_must_be_authenticated_and_have_verified_attendance(): void
    {
        auth()->logout();
        $this->postJson(route('peer-snap.start'), ['session_id' => $this->session->id, 'student_number' => $this->student->student_number])->assertUnauthorized();
        $this->actingAs($this->host);
        Attendance::where('user_id', $this->host->id)->delete();
        $this->postJson(route('peer-snap.start'), ['session_id' => $this->session->id, 'student_number' => $this->student->student_number])->assertForbidden();
    }

    public function test_host_outside_geofence_or_with_stale_presence_is_denied(): void
    {
        Attendance::where('user_id', $this->host->id)->update(['last_distance_meters' => 80]);
        $this->postJson(route('peer-snap.start'), ['session_id' => $this->session->id, 'student_number' => $this->student->student_number])->assertForbidden();
        Attendance::where('user_id', $this->host->id)->update(['last_distance_meters' => 5, 'last_location_check_at' => now()->subMinutes(4)]);
        $this->postJson(route('peer-snap.start'), ['session_id' => $this->session->id, 'student_number' => $this->student->student_number])->assertForbidden();
    }

    public function test_peer_requests_from_a_different_device_are_denied(): void
    {
        $this->withHeader('X-Device-Key', 'other-phone');
        $this->getJson(route('peer-snap.sessions'))->assertForbidden();
        $this->postJson(route('peer-snap.start'), [
            'session_id' => $this->session->id, 'student_number' => $this->student->student_number,
        ])->assertForbidden();
        $this->assertDatabaseCount('peer_vouch_requests', 0);
    }

    public function test_voucher_quota_and_class_membership_are_enforced(): void
    {
        for ($i = 0; $i < 2; $i++) {
            PeerVouchRequest::create([
                'verification_id' => (string) Str::uuid(), 'session_id' => $this->session->id,
                'subject_student_id' => $this->student->id, 'voucher_student_id' => $this->host->id,
                'nonce_hash' => hash('sha256', Str::random(64)), 'challenge' => 'turn_left',
                'status' => 'verified', 'expires_at' => now()->addMinute(), 'consumed_at' => now(),
            ]);
        }
        $this->postJson(route('peer-snap.start'), ['session_id' => $this->session->id, 'student_number' => $this->student->student_number])->assertForbidden();
        PeerVouchRequest::query()->delete();
        $this->student->update(['section' => 'B']);
        $this->postJson(route('peer-snap.start'), ['session_id' => $this->session->id, 'student_number' => $this->student->student_number])->assertUnprocessable();
    }

    public function test_explicit_enrollment_allows_student_outside_implicit_section(): void
    {
        $this->student->update(['section' => 'B']);
        $this->subject->enrolledStudents()->attach($this->student->id);
        $this->start();
    }

    public function test_expired_and_replayed_requests_never_create_attendance(): void
    {
        $ticket = $this->start(); $this->fakeVerifier($ticket);
        PeerVouchRequest::where('verification_id', $ticket['verification_id'])->update(['expires_at' => now()->subSecond()]);
        $this->confirm($ticket)->assertStatus(410);
        $this->confirm($ticket)->assertConflict();
        $this->assertDatabaseMissing('attendances', ['user_id' => $this->student->id, 'method' => 'peer_biometric']);
    }

    public function test_duplicate_attendance_is_rejected_after_verification(): void
    {
        $ticket = $this->start(); $this->fakeVerifier($ticket);
        Attendance::create(['user_id' => $this->student->id, 'subject_id' => $this->subject->id,
            'subject_code' => $this->subject->code, 'date' => now('Asia/Manila')->toDateString(), 'status' => 'Present']);
        $this->confirm($ticket)->assertConflict();
    }

    public function test_escaped_or_excused_attendance_cannot_be_overwritten(): void
    {
        $record = Attendance::create([
            'user_id' => $this->student->id, 'subject_id' => $this->subject->id,
            'subject_code' => $this->subject->code, 'date' => now('Asia/Manila')->toDateString(),
            'status' => 'Escaped',
        ]);
        $this->postJson(route('peer-snap.start'), [
            'session_id' => $this->session->id, 'student_number' => $this->student->student_number,
        ])->assertUnprocessable();
        $record->update(['status' => 'Absent', 'excused' => true]);
        $this->postJson(route('peer-snap.start'), [
            'session_id' => $this->session->id, 'student_number' => $this->student->student_number,
        ])->assertUnprocessable();
    }

    public function test_status_changed_during_verification_is_not_overwritten(): void
    {
        $ticket = $this->start(); $this->fakeVerifier($ticket);
        Attendance::create([
            'user_id' => $this->student->id, 'subject_id' => $this->subject->id,
            'subject_code' => $this->subject->code, 'date' => now('Asia/Manila')->toDateString(),
            'status' => 'Escaped',
        ]);
        $this->confirm($ticket)->assertConflict();
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->student->id, 'status' => 'Escaped',
        ]);
    }

    public function test_three_failed_attempts_lock_host_and_subject_across_new_sessions(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $ticket = $this->start();
            $this->fakeVerifier($ticket, ['similarity' => 0.1]);
            $this->confirm($ticket)->assertUnprocessable();
        }
        $this->postJson(route('peer-snap.start'), ['session_id' => $this->session->id, 'student_number' => $this->student->student_number])->assertStatus(429);
        $secondHost = User::factory()->create(['year_level' => 1, 'semester' => 1, 'course' => 'BSCS', 'section' => 'A']);
        $this->host = $secondHost;
        Attendance::create(['user_id' => $secondHost->id, 'subject_code' => $this->subject->code,
            'subject_id' => $this->subject->id, 'date' => now('Asia/Manila')->toDateString(),
            'status' => 'Present', 'method' => 'qr', 'session_id' => $this->session->id,
            'monitoring_status' => 'active', 'last_location_check_at' => now(), 'last_distance_meters' => 5]);
        $this->actingAs($secondHost);
        $this->postJson(route('peer-snap.start'), ['session_id' => $this->session->id, 'student_number' => $this->student->student_number])->assertStatus(429);
    }

    public function test_browser_claims_and_identity_overrides_are_prohibited(): void
    {
        $ticket = $this->start(); $this->fakeVerifier($ticket);
        foreach (['match_score' => 1.0, 'liveness_passed' => true, 'student_id' => 999,
                  'voucher_student_id' => 999, 'session_id' => 999] as $key => $value) {
            $this->confirm($ticket, [$key => $value])->assertUnprocessable();
        }
        $this->assertDatabaseHas('peer_vouch_requests', ['verification_id' => $ticket['verification_id'], 'status' => 'pending']);
    }

    public function test_verifier_rejects_multiple_faces_and_failed_liveness(): void
    {
        $ticket = $this->start(); $this->fakeVerifier($ticket, ['frames' => array_fill(0, 5, ['face_count' => 2, 'quality_passed' => true, 'pad_score' => .99])]);
        $this->confirm($ticket)->assertUnprocessable();
        $this->assertDatabaseHas('peer_vouch_requests', ['verification_id' => $ticket['verification_id'], 'failure_reason' => 'face_count']);
        $ticket = $this->start(); $this->fakeVerifier($ticket, ['challenge_passed' => false]);
        $this->confirm($ticket)->assertUnprocessable();
        $this->assertDatabaseHas('peer_vouch_requests', ['verification_id' => $ticket['verification_id'], 'failure_reason' => 'liveness']);
    }

    public function test_success_creates_nonprovisional_attendance_and_broadcasts(): void
    {
        Event::fake([PeerAttendanceVerified::class]);
        $ticket = $this->start(); $this->fakeVerifier($ticket);
        $this->confirm($ticket)->assertOk()->assertJsonPath('verification_channel', 'peer_biometric');
        $this->assertDatabaseHas('attendances', ['user_id' => $this->student->id, 'subject_code' => $this->subject->code,
            'status' => 'Present', 'verification_channel' => 'peer_biometric', 'is_provisional' => 0]);
        $this->assertDatabaseHas('peer_vouch_requests', ['verification_id' => $ticket['verification_id'], 'status' => 'verified']);
        $this->assertTrue(PeerVouchRequest::where('verification_id', $ticket['verification_id'])->first()->hasValidDecisionMac());
        Event::assertDispatched(PeerAttendanceVerified::class, fn ($event) => $event->teacherId === $this->teacher->id
            && $event->instructorId === $this->teacher->id && $event->voucherName === $this->host->name);
        $this->confirm($ticket)->assertConflict();
        $this->travel(6)->minutes();
        $this->actingAs($this->teacher)->getJson(route('teacher.qr.clockins', ['session_id' => $this->session->id]))
            ->assertOk()->assertJsonFragment(['verification_channel' => 'peer_biometric', 'status' => 'Present']);
        $this->assertDatabaseHas('attendances', ['user_id' => $this->student->id, 'status' => 'Present']);
    }

    public function test_missing_verifier_fails_closed(): void
    {
        config(['peer_snap.verifier_url' => '']);
        $this->postJson(route('peer-snap.start'), ['session_id' => $this->session->id, 'student_number' => $this->student->student_number])->assertStatus(503);
        $this->assertDatabaseCount('peer_vouch_requests', 0);
    }

    public function test_disabled_feature_shows_no_sessions_and_cannot_start(): void
    {
        config(['peer_snap.enabled' => false]);
        $this->getJson(route('peer-snap.sessions'))->assertOk()
            ->assertJsonPath('available', false)->assertJsonPath('sessions', []);
        $this->postJson(route('peer-snap.start'), [
            'session_id' => $this->session->id, 'student_number' => $this->student->student_number,
        ])->assertStatus(503);
    }

    public function test_unknown_student_is_generic_and_counts_toward_host_lockout(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson(route('peer-snap.start'), [
                'session_id' => $this->session->id, 'student_number' => 'UNKNOWN-'.$i,
            ])->assertUnprocessable()->assertJsonPath('message', 'Student cannot be checked in through this method.');
        }
        $this->assertDatabaseCount('peer_vouch_requests', 3);
        $this->postJson(route('peer-snap.start'), [
            'session_id' => $this->session->id, 'student_number' => $this->student->student_number,
        ])->assertStatus(429);
    }

    public function test_wrong_nonce_consumes_request_and_does_not_call_verifier(): void
    {
        $ticket = $this->start();
        $ticket['nonce'] = str_repeat('a', 64);
        $this->confirm($ticket)->assertUnprocessable();
        $this->assertDatabaseHas('peer_vouch_requests', [
            'verification_id' => $ticket['verification_id'], 'failure_reason' => 'invalid_nonce',
        ]);
        Http::assertNothingSent();
    }

    public function test_verifier_response_must_bind_exact_student_and_challenge(): void
    {
        $ticket = $this->start(); $this->fakeVerifier($ticket, ['subject_ref' => 'another-student']);
        $this->confirm($ticket)->assertStatus(503);
        $this->assertDatabaseMissing('attendances', ['user_id' => $this->student->id, 'method' => 'peer_biometric']);
        $this->assertDatabaseHas('peer_vouch_requests', [
            'verification_id' => $ticket['verification_id'], 'failure_reason' => 'verifier_unavailable',
        ]);
    }

    public function test_enrollment_revocation_and_closed_session_fail_closed(): void
    {
        PeerFaceEnrollment::where('user_id', $this->student->id)->update(['revoked_at' => now()]);
        $this->postJson(route('peer-snap.start'), [
            'session_id' => $this->session->id, 'student_number' => $this->student->student_number,
        ])->assertUnprocessable();
        $this->session->update(['active' => false]);
        $this->postJson(route('peer-snap.start'), [
            'session_id' => $this->session->id, 'student_number' => $this->student->student_number,
        ])->assertForbidden();
    }

    public function test_camera_page_renders_steps_and_camera_controls(): void
    {
        $this->get(route('peer-snap.index'))->assertOk()
            ->assertSee('Check in for a Classmate')
            ->assertSee('peerVideo')
            ->assertSee('Begin live challenge');
    }

    public function test_instructor_dashboard_listens_for_peer_attendance(): void
    {
        $this->actingAs($this->teacher)->get(route('teacher.dashboard'))
            ->assertOk()->assertSee('attendance.peer.verified')->assertSee('peerLiveNotice');
    }

    public function test_admin_owned_session_also_notifies_subject_instructor(): void
    {
        Event::fake([PeerAttendanceVerified::class]);
        $admin = User::factory()->admin()->create();
        $this->session->update(['created_by' => $admin->id]);
        $ticket = $this->start(); $this->fakeVerifier($ticket);
        $this->confirm($ticket)->assertOk();
        Event::assertDispatched(PeerAttendanceVerified::class, function ($event) use ($admin) {
            return $event->teacherId === $admin->id
                && $event->instructorId === $this->teacher->id
                && count($event->broadcastOn()) === 2;
        });
    }
}
