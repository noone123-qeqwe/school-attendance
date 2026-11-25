<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FaceAttendanceChallenge;
use App\Services\FaceAttendanceConsent;
use App\Services\LiveProfileFaceMatcher;
use App\Services\ProfileFacePhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
use Tests\TestCase;

class FaceLoginVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function createEnrolledStudent(string $studentNumber = '2026-9001', bool $enrollCamera = true): User
    {
        $student = User::forceCreate([
            'name' => 'Face Login Student',
            'email' => strtolower($studentNumber) . '@example.test',
            'email_verified_at' => now(),
            'student_number' => $studentNumber,
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('StudentPass123!'),
        ]);

        if ($enrollCamera) {
            $jpeg = $this->createSampleJpeg();
            app(ProfileFacePhotoService::class)->saveCameraViews($student, [
                'front' => $jpeg,
                'left' => $jpeg,
                'right' => $jpeg,
            ]);
            app(FaceAttendanceConsent::class)->update($student, $student, 'adult_student');
        }

        return $student;
    }

    private function createSampleJpeg(): string
    {
        $img = imagecreatetruecolor(120, 120);
        $bg = imagecolorallocate($img, 200, 180, 160);
        imagefill($img, 0, 0, $bg);
        ob_start();
        imagejpeg($img, null, 85);
        imagedestroy($img);

        return (string) ob_get_clean();
    }

    private function sampleFrames(): array
    {
        $payload = 'data:image/jpeg;base64,' . base64_encode($this->createSampleJpeg());

        return array_fill(0, FaceAttendanceChallenge::FRAME_COUNT, $payload);
    }

    public function test_face_login_options_does_not_enumerate_unknown_or_unenrolled_accounts(): void
    {
        $this->mock(LiveProfileFaceMatcher::class, function (MockInterface $mock): void {
            $mock->shouldReceive('attendanceReady')->andReturn(true);
        });

        $unenrolled = $this->createEnrolledStudent('2026-9002', false);

        $missingResp = $this->postJson(route('webauthn.face.options'), [
            'identifier' => '2026-0000',
        ]);
        $unenrolledResp = $this->postJson(route('webauthn.face.options'), [
            'identifier' => $unenrolled->student_number,
        ]);

        $missingResp->assertNotFound()->assertJsonPath('code', 'FACE_LOGIN_UNAVAILABLE');
        $unenrolledResp->assertNotFound()->assertJsonPath('code', 'FACE_LOGIN_UNAVAILABLE');
        $this->assertSame($missingResp->json('message'), $unenrolledResp->json('message'));
    }

    public function test_face_login_options_returns_unavailable_when_matcher_is_not_ready(): void
    {
        $student = $this->createEnrolledStudent('2026-9003', true);

        $this->mock(LiveProfileFaceMatcher::class, function (MockInterface $mock): void {
            $mock->shouldReceive('attendanceReady')->andReturn(false);
        });

        $this->postJson(route('webauthn.face.options'), [
            'identifier' => $student->student_number,
        ])->assertStatus(503)->assertJsonPath('code', 'FACE_VERIFICATION_UNAVAILABLE');
    }

    public function test_face_login_verifies_live_frames_once_logs_in_and_redirects_to_qr_scan(): void
    {
        $student = $this->createEnrolledStudent('2026-9004', true);
        $frames = $this->sampleFrames();

        $this->mock(LiveProfileFaceMatcher::class, function (MockInterface $mock): void {
            $mock->shouldReceive('attendanceReady')->andReturn(true);
            $mock->shouldReceive('verifyAttendance')->once()->andReturn([
                'match' => true,
                'liveness_passed' => true,
                'similarity' => 0.84,
                'code' => 'MATCH',
                'method' => 'opencv_yunet_sface',
                'message' => 'Face samples matched your registered face profile.',
            ]);
        });

        $optionsResponse = $this->postJson(route('webauthn.face.options'), [
            'identifier' => $student->student_number,
        ])->assertOk();

        $challenge = $optionsResponse->json('face_challenge');
        $this->assertSame(64, strlen((string) ($challenge['id'] ?? '')));
        $this->assertSame('front', $challenge['poses'][0] ?? null);
        $this->assertCount(5, $challenge['poses'] ?? []);

        // Advance clock past the 3500ms server minimum liveness elapsed check.
        $this->travel(4)->seconds();

        $loginResponse = $this->postJson(route('webauthn.face.login'), [
            'identifier' => $student->student_number,
            'face_challenge' => $challenge['id'],
            'live_frames' => $frames,
            'qr_token' => 'qr-token-xyz',
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.id', $student->id)
            ->assertJsonPath('redirect', route('qr.scan', ['token' => 'qr-token-xyz']));

        $this->assertAuthenticatedAs($student);

        // Consumed single-use challenge cannot be replayed.
        $this->post('/logout');
        $this->postJson(route('webauthn.face.login'), [
            'identifier' => $student->student_number,
            'face_challenge' => $challenge['id'],
            'live_frames' => $frames,
        ])->assertStatus(422)->assertJsonPath('code', 'FACE_CHALLENGE_INVALID');
    }

    public function test_face_login_rejects_under_minimum_duration_and_enforces_lockout_on_mismatch(): void
    {
        $student = $this->createEnrolledStudent('2026-9005', true);
        $frames = $this->sampleFrames();

        $this->mock(LiveProfileFaceMatcher::class, function (MockInterface $mock): void {
            $mock->shouldReceive('attendanceReady')->andReturn(true);
            $mock->shouldReceive('verifyAttendance')->times(4)->andReturn([
                'match' => false,
                'liveness_passed' => false,
                'similarity' => 0.12,
                'code' => 'BIOMETRIC_MISMATCH',
                'method' => 'opencv_yunet_sface',
                'message' => 'Your face samples did not match your registered face profile.',
            ]);
        });

        // Too fast (< 3500ms) counts as attempt 1 and invalidates challenge.
        $fastChallenge = $this->postJson(route('webauthn.face.options'), [
            'identifier' => $student->student_number,
        ])->assertOk()->json('face_challenge.id');

        $this->postJson(route('webauthn.face.login'), [
            'identifier' => $student->student_number,
            'face_challenge' => $fastChallenge,
            'live_frames' => $frames,
        ])->assertStatus(422)->assertJsonPath('code', 'FACE_CHALLENGE_INVALID');

        // Four mismatches hit the 5-attempt lockout limit cleanly.
        for ($i = 0; $i < 4; $i++) {
            $challengeId = $this->postJson(route('webauthn.face.options'), [
                'identifier' => $student->student_number,
            ])->assertOk()->json('face_challenge.id');

            $this->travel(4)->seconds();

            $resp = $this->postJson(route('webauthn.face.login'), [
                'identifier' => $student->student_number,
                'face_challenge' => $challengeId,
                'live_frames' => $frames,
            ]);

            if ($i < 3) {
                $resp->assertStatus(422)->assertJsonPath('code', 'BIOMETRIC_MISMATCH');
            } else {
                $resp->assertStatus(429);
            }
        }

        $this->postJson(route('webauthn.face.options'), [
            'identifier' => $student->student_number,
        ])->assertStatus(429);
        $this->assertGuest();
    }
}
