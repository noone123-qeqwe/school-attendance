<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Setting;
use App\Services\LiveProfileFaceMatcher;
use App\Services\WebauthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery;

class LiveFaceAttendanceVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function createSyntheticFaceImage(int $seed = 1): string
    {
        $im = imagecreatetruecolor(160, 160);
        $skin = imagecolorallocate($im, 230, 180 + ($seed * 11) % 40, 150);
        $featureColor = imagecolorallocate($im, 50, 35, 25);
        imagefilledrectangle($im, 0, 0, 160, 160, $skin);

        if ($seed === 1) {
            // Standard frontal face geometry
            imagefilledellipse($im, 55, 60, 18, 14, $featureColor);  // Left eye
            imagefilledellipse($im, 105, 60, 18, 14, $featureColor); // Right eye
            imagefilledellipse($im, 80, 88, 14, 22, $featureColor);  // Nose
            imagefilledellipse($im, 80, 122, 40, 14, $featureColor); // Mouth
        } else {
            // Distinctly altered impostor geometry (different coordinates and dimensions)
            imagefilledellipse($im, 40, 75, 24, 18, $featureColor);  // Wide/low Left eye
            imagefilledellipse($im, 120, 75, 24, 18, $featureColor); // Wide/low Right eye
            imagefilledellipse($im, 80, 105, 20, 24, $featureColor); // Low Nose
            imagefilledellipse($im, 80, 138, 55, 12, $featureColor); // Wide low Mouth
        }

        ob_start();
        imagejpeg($im, null, 90);
        $raw = ob_get_clean();
        imagedestroy($im);

        return $raw;
    }

    private function toDataUrl(string $jpegBytes): string
    {
        return 'data:image/jpeg;base64,' . base64_encode($jpegBytes);
    }

    private function createTestEnvironment(): array
    {
        Storage::fake('public');

        $teacher = User::factory()->create(['role' => 'teacher']);

        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Jane Student',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
            'profile_image' => null,
        ]);

        $subject = Subject::factory()->create([
            'instructor_id' => $teacher->id,
            'code' => 'CS101',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        \App\Models\Schedule::create([
            'subject_id' => $subject->id,
            'day' => today()->format('l'),
            'start_time' => now()->subMinutes(10)->format('H:i:s'),
            'end_time' => now()->addMinutes(50)->format('H:i:s'),
        ]);

        $student->enrolledSubjects()->attach($subject->id);

        $response = $this->actingAs($teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $subject->code,
            'teacher_accuracy' => 10,
            'classroom_lat' => 14.5000,
            'classroom_lng' => 121.0000,
            'radius_meters' => 50,
        ]);

        $token = $response->json('token');
        $sessionId = $response->json('session_id');

        return [$teacher, $student, $subject, $token, $sessionId];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        config([
            'face_match.attendance_enabled' => true,
            'face_match.attendance_pad_model' => __FILE__,
            'face_match.detector_model' => __FILE__,
            'face_match.recognizer_model' => __FILE__,
        ]);
    }

    private function approve(User $student): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin']);
        app(\App\Services\ProfileFacePhotoService::class)->saveCameraEnrollment($student, $this->createSyntheticFaceImage());
        app(\App\Services\FaceAttendanceConsent::class)->update($student, $admin, 'guardian');
    }

    private function startFaceCheck(User $student, string $token): array
    {
        $page = $this->actingAs($student)->get(route('qr.scan', $token))->assertOk();
        $this->withCredentials()->withUnencryptedCookie(config('session.cookie'), $page->getCookie(config('session.cookie'), false)->getValue());

        $challenge = $this->postJson('/qr/verify-options', ['token' => $token, 'biometric_method' => 'face'])
            ->assertOk()->json('face_challenge');
        $this->assertSame('front', $challenge['poses'][0]);
        $this->assertEqualsCanonicalizing(['front', 'left', 'right', 'up', 'down'], $challenge['poses']);
        $this->travelTo(now()->addSeconds(4));

        return $challenge;
    }

    private function facePayload(string $token, array $challenge): array
    {
        return [
            'token' => $token, 'latitude' => 14.5001, 'longitude' => 121.0001, 'accuracy' => 10,
            'location_timestamp_ms' => (int) round(microtime(true) * 1000),
            'biometric_method' => 'face', 'face_challenge' => $challenge['id'],
            'live_frames' => array_fill(0, 14, $this->toDataUrl($this->createSyntheticFaceImage())),
        ];
    }

    public function test_only_approved_students_with_camera_references_can_open_face_check(): void
    {
        [, $student, , $token] = $this->createTestEnvironment();
        Storage::disk('public')->put('profile_images/student.jpg', $this->createSyntheticFaceImage());
        $student->update(['profile_image' => 'profile_images/student.jpg']);
        $this->actingAs($student)->postJson('/qr/verify-options', ['token' => $token, 'biometric_method' => 'face'])
            ->assertForbidden();
        $this->approve($student);
        $this->get(route('qr.scan', $token))->assertOk()->assertViewHas('hasFaceMethod', true)
            ->assertSee('Start camera')->assertSee('faceCaptureProgress')->assertDontSee('btnCaptureFace', false);
    }

    public function test_disabled_rollout_preserves_device_setup(): void
    {
        [, $student, , $token] = $this->createTestEnvironment();
        $this->approve($student);
        config(['face_match.attendance_enabled' => false]);
        $this->actingAs($student)->get(route('qr.scan', $token))
            ->assertOk()->assertViewHas('status', 'setup')->assertSee('Or Register Fingerprint');
        $this->postJson('/qr/verify-options', ['token' => $token, 'biometric_method' => 'face'])->assertForbidden();
    }

    public function test_no_attendance_without_both_server_verdicts(): void
    {
        [, $student, , $token] = $this->createTestEnvironment();
        $this->approve($student);
        $matcher = Mockery::mock(LiveProfileFaceMatcher::class)->makePartial();
        $matcher->shouldReceive('verifyAttendance')->times(3)->andReturn(
            ['match' => true, 'code' => 'LIVENESS_FAILED', 'liveness_passed' => false, 'message' => 'Spoof suspected.'],
            ['match' => false, 'code' => 'BIOMETRIC_MISMATCH', 'liveness_passed' => true, 'message' => 'Identity did not match.'],
            ['match' => false, 'code' => 'LIVENESS_UNCERTAIN', 'liveness_passed' => false, 'message' => 'Try again.'],
        );
        $this->app->instance(LiveProfileFaceMatcher::class, $matcher);

        foreach (['LIVENESS_FAILED', 'BIOMETRIC_MISMATCH', 'LIVENESS_UNCERTAIN'] as $code) {
            $payload = $this->facePayload($token, $this->startFaceCheck($student, $token));
            $this->postJson('/qr/verify-complete', $payload + ['liveness_passed' => true, 'match_score' => 1])
                ->assertUnprocessable()->assertJsonPath('code', $code);
            $this->assertDatabaseCount('attendances', 0);
        }
    }

    public function test_attendance_requires_single_use_challenge_and_records_only_once(): void
    {
        [, $student, $subject, $token] = $this->createTestEnvironment();
        $this->approve($student);
        $challenge = $this->startFaceCheck($student, $token);
        $payload = $this->facePayload($token, $challenge);
        $matcher = Mockery::mock(LiveProfileFaceMatcher::class)->makePartial();
        $this->app->instance(LiveProfileFaceMatcher::class, $matcher);

        $this->postJson('/qr/verify-complete', array_replace($payload, ['live_frames' => [$payload['live_frames'][0]]]))
            ->assertUnprocessable()->assertJsonPath('code', 'FACE_CHALLENGE_INVALID');
        $this->postJson('/qr/verify-complete', $payload)->assertUnprocessable()->assertJsonPath('code', 'FACE_CHALLENGE_INVALID');
        $nextChallenge = $this->startFaceCheck($student, $token);
        $payload = $this->facePayload($token, $nextChallenge);
        $matcher->shouldReceive('verifyAttendance')->once()->with(
            Mockery::on(fn ($user) => $user->is($student)), $payload['live_frames'], $nextChallenge['poses']
        )->andReturn(['match' => true, 'code' => 'MATCH', 'liveness_passed' => true]);
        $this->postJson('/qr/verify-complete', $payload)->assertOk()->assertJsonPath('face_verified', true);
        $this->postJson('/qr/verify-complete', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', ['user_id' => $student->id, 'subject_code' => $subject->code, 'method' => 'qr_face']);
    }

    public function test_exact_repeated_frames_are_rejected_before_inference(): void
    {
        [, $student, , $token] = $this->createTestEnvironment();
        $this->approve($student);
        $payload = $this->facePayload($token, $this->startFaceCheck($student, $token));
        $this->postJson('/qr/confirm', $payload)->assertUnprocessable()->assertJsonPath('code', 'LIVENESS_FAILED');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_withdrawal_during_inference_prevents_the_attendance_write(): void
    {
        [, $student, , $token] = $this->createTestEnvironment();
        $this->approve($student);
        $matcher = Mockery::mock(LiveProfileFaceMatcher::class)->makePartial();
        $matcher->shouldReceive('verifyAttendance')->once()->andReturnUsing(function () use ($student) {
            app(\App\Services\FaceAttendanceConsent::class)->update($student, $student, null);
            return ['match' => true, 'code' => 'MATCH', 'liveness_passed' => true];
        });
        $this->app->instance(LiveProfileFaceMatcher::class, $matcher);
        $payload = $this->facePayload($token, $this->startFaceCheck($student, $token));

        $this->postJson('/qr/verify-complete', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_a_teacher_mark_recorded_during_matching_is_preserved(): void
    {
        [, $student, $subject, $token, $sessionId] = $this->createTestEnvironment();
        $this->approve($student);
        $matcher = Mockery::mock(LiveProfileFaceMatcher::class)->makePartial();
        $matcher->shouldReceive('verifyAttendance')->once()->andReturnUsing(function () use ($student, $subject, $sessionId) {
            Attendance::create([
                'user_id' => $student->id, 'subject_id' => $subject->id, 'subject_code' => $subject->code,
                'date' => today()->toDateString(), 'session_id' => $sessionId,
                'status' => 'Late', 'method' => 'teacher_manual', 'time_in' => '09:00:00',
            ]);
            return ['match' => true, 'code' => 'MATCH', 'liveness_passed' => true];
        });
        $this->app->instance(LiveProfileFaceMatcher::class, $matcher);
        $payload = $this->facePayload($token, $this->startFaceCheck($student, $token));

        $this->postJson('/qr/verify-complete', $payload)->assertOk()->assertJsonPath('already_clocked_in', true);
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id, 'status' => 'Late', 'method' => 'teacher_manual', 'time_in' => '09:00:00',
        ]);
    }

    public function test_face_check_locks_after_five_failures(): void
    {
        [, $student, , $token] = $this->createTestEnvironment();
        $this->approve($student);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $payload = $this->facePayload($token, $this->startFaceCheck($student, $token));
            $this->postJson('/qr/verify-complete', $payload)->assertUnprocessable()->assertJsonPath('code', 'LIVENESS_FAILED');
        }
        $this->postJson('/qr/verify-options', ['token' => $token, 'biometric_method' => 'face'])
            ->assertStatus(429)->assertHeader('Retry-After');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_direct_scan_cannot_record_attendance_for_an_approved_camera_student(): void
    {
        [, $student, $subject, $token] = $this->createTestEnvironment();
        $this->approve($student);
        $this->actingAs($student)->postJson('/qr/scan-process', [
            'token' => $token, 'latitude' => 14.5001, 'longitude' => 121.0001, 'accuracy' => 10,
            'location_timestamp_ms' => now()->getTimestampMs(),
        ])->assertOk()->assertJsonPath('verification_url', route('qr.scan', $token, false));
        $this->postJson('/attendance/store', ['subject_code' => $subject->code, 'latitude' => 14.5001, 'longitude' => 121.0001])
            ->assertUnprocessable();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_consent_requires_super_admin_and_documentation_and_student_can_withdraw(): void
    {
        [, $student, , $token] = $this->createTestEnvironment();
        $admin = User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin']);
        $consentUrl = route('admin.student.face-attendance-consent.store', $student);
        $this->actingAs($student)->postJson($consentUrl, ['source' => 'guardian', 'documented' => true])
            ->assertRedirect();
        $this->assertDatabaseMissing('face_attendance_consents', ['student_id' => $student->id]);
        $this->actingAs($admin)->postJson($consentUrl, ['source' => 'guardian'])->assertUnprocessable();
        $this->post($consentUrl, ['source' => 'guardian', 'documented' => '1'])->assertRedirect();
        $this->assertDatabaseHas('face_attendance_consents', ['student_id' => $student->id, 'source' => 'guardian']);
        $this->actingAs($student)->delete(route('student.face-attendance-consent.withdraw'))->assertRedirect(route('home'));
        $this->assertDatabaseMissing('face_attendance_consents', ['student_id' => $student->id]);
        $this->postJson('/qr/verify-options', ['token' => $token, 'biometric_method' => 'face'])->assertForbidden();
    }
    public function test_face_with_device_factor_requires_a_binding_created_before_the_attempt()
    {
        [$teacher, $student, $subject, $token] = $this->createTestEnvironment();
        Setting::updateOrCreate(['key' => 'face_verification_additional_factor'], ['value' => 'device']);

        $response = $this->actingAs($student)->postJson('/qr/verify-complete', [
            'token' => $token,
            'biometric_method' => 'face',
            'live_frames' => ['frame-one', 'frame-two'],
        ]);

        $response->assertForbidden()->assertJsonPath('error_type', 'device_not_registered');
        $this->assertDatabaseMissing('attendances', ['user_id' => $student->id, 'subject_code' => $subject->code]);
    }

    public function test_face_verification_rejects_student_outside_classroom_geofence()
    {
        [$teacher, $student, $subject, $token] = $this->createTestEnvironment();

        $this->approve($student);
        $payload = $this->facePayload($token, $this->startFaceCheck($student, $token));
        $matcher = Mockery::mock(LiveProfileFaceMatcher::class)->makePartial();
        $matcher->shouldNotReceive('verifyAttendance');
        $this->app->instance(LiveProfileFaceMatcher::class, $matcher);

        // Student is far outside classroom (e.g. 15.5 lat vs 14.5 lat ~ 111 km away)
        $response = $this->actingAs($student)->postJson('/qr/verify-complete', array_replace($payload, [
            'latitude' => 15.5000, 'longitude' => 121.0000,
        ]));

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('outside_classroom', $response->json('error_type'));

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $student->id,
            'subject_code' => $subject->code,
        ]);
    }

    public function test_student_can_fallback_to_fingerprint_when_both_methods_are_enrolled()
    {
        [$teacher, $student, $subject, $token] = $this->createTestEnvironment();

        // Enrolled profile photo
        $photoBytes = $this->createSyntheticFaceImage(1);
        Storage::disk('public')->put('profile_images/student_both.jpg', $photoBytes);
        $student->update(['profile_image' => 'profile_images/student_both.jpg']);

        $this->approve($student);
        // Also enrolled WebAuthn fingerprint credential
        $student->webauthnCredentials()->create([
            'credential_id' => 'fp_cred_123',
            'public_key' => '-----BEGIN PUBLIC KEY-----',
            'sign_count' => 0,
            'user_handle' => 'fp_handle',
        ]);

        // Student requests options
        $optResponse = $this->actingAs($student)->postJson('/qr/verify-options', ['token' => $token]);
        $optResponse->assertStatus(200);
        $this->assertContains('face', $optResponse->json('available_methods'));
        $this->assertContains('fingerprint', $optResponse->json('available_methods'));

        // Mock WebAuthn assertion verification
        $mockWebauthn = Mockery::mock(WebauthnService::class);
        $mockCredential = new \App\Models\WebauthnCredential();
        $mockWebauthn->shouldReceive('verifyAssertion')->once()->andReturn($mockCredential);
        $this->app->instance(WebauthnService::class, $mockWebauthn);

        $response = $this->actingAs($student)->postJson('/qr/verify-complete', [
            'token' => $token,
            'latitude' => 14.5001,
            'longitude' => 121.0001,
            'accuracy' => 10,
            'location_timestamp_ms' => now()->getTimestampMs(),
            'credential' => '{"id":"fp_cred_123","rawId":"fp_cred_123","response":{},"type":"public-key"}',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'status' => 'Present',
            'method' => 'qr',
        ]);
    }
}
