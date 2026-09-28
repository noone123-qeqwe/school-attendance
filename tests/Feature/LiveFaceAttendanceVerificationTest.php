<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Services\BiometricService;
use App\Services\WebauthnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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

    public function test_student_with_registered_profile_photo_can_access_qr_scan_without_webauthn()
    {
        [$teacher, $student, $subject, $token] = $this->createTestEnvironment();

        // Enroll profile photo
        $photoBytes = $this->createSyntheticFaceImage(1);
        Storage::disk('public')->put('profile_images/student_1.jpg', $photoBytes);
        $student->update(['profile_image' => 'profile_images/student_1.jpg']);

        // Student has NO WebAuthn credentials
        $this->assertFalse($student->webauthnCredentials()->exists());

        $scanUrl = URL::signedRoute('qr.scan', ['token' => $token]);
        $response = $this->actingAs($student)->get($scanUrl);

        $response->assertStatus(200);
        $response->assertViewIs('qr.verify');
        $response->assertViewHas('hasProfilePhoto', true);
        $response->assertViewHas('hasFaceMethod', true);
        $response->assertViewHas('defaultMethod', 'face');
        $response->assertViewHas('status', 'ready');
    }

    public function test_student_without_photo_or_webauthn_is_prompted_for_setup()
    {
        [$teacher, $student, $subject, $token] = $this->createTestEnvironment();

        // Student has no photo and no WebAuthn
        $student->update(['profile_image' => null]);
        $this->assertFalse($student->webauthnCredentials()->exists());

        $scanUrl = URL::signedRoute('qr.scan', ['token' => $token]);
        $response = $this->actingAs($student)->get($scanUrl);

        $response->assertStatus(200);
        $response->assertViewIs('qr.verify');
        $response->assertViewHas('status', 'setup');
    }

    public function test_student_can_fetch_verification_options_with_profile_photo()
    {
        [$teacher, $student, $subject, $token] = $this->createTestEnvironment();

        $photoBytes = $this->createSyntheticFaceImage(1);
        Storage::disk('public')->put('profile_images/student_opt.jpg', $photoBytes);
        $student->update(['profile_image' => 'profile_images/student_opt.jpg']);

        $response = $this->actingAs($student)->postJson('/qr/verify-options', [
            'token' => $token,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertContains('face', $response->json('available_methods'));
        $this->assertTrue($response->json('has_profile_photo'));
    }

    public function test_student_can_complete_attendance_using_live_face_frame_matching_profile_photo()
    {
        [$teacher, $student, $subject, $token, $sessionId] = $this->createTestEnvironment();

        $photoBytes = $this->createSyntheticFaceImage(1);
        Storage::disk('public')->put('profile_images/student_match.jpg', $photoBytes);
        $student->update(['profile_image' => 'profile_images/student_match.jpg']);

        // First pre-cache descriptor via BiometricService
        $biometricService = app(BiometricService::class);
        $biometricService->getOrCreateProfilePhotoDescriptor($student);

        // Matching live camera frame
        $matchingLiveFrame = $this->toDataUrl($photoBytes);

        $response = $this->actingAs($student)->postJson('/qr/verify-complete', [
            'token' => $token,
            'latitude' => 14.5001,
            'longitude' => 121.0001,
            'accuracy' => 10,
            'biometric_method' => 'face',
            'live_frame' => $matchingLiveFrame,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'status' => 'Present',
            'method' => 'qr_face',
        ]);
    }

    public function test_student_face_verification_fails_when_live_face_is_impostor()
    {
        [$teacher, $student, $subject, $token] = $this->createTestEnvironment();

        // Enrolled photo: Geometry 1
        $enrolledBytes = $this->createSyntheticFaceImage(1);
        Storage::disk('public')->put('profile_images/student_ref.jpg', $enrolledBytes);
        $student->update(['profile_image' => 'profile_images/student_ref.jpg']);

        // Pre-cache enrolled descriptor
        app(BiometricService::class)->getOrCreateProfilePhotoDescriptor($student);

        // Impostor live camera frame: Distinctly different geometry
        $impostorBytes = $this->createSyntheticFaceImage(99);
        $impostorLiveFrame = $this->toDataUrl($impostorBytes);

        $response = $this->actingAs($student)->postJson('/qr/verify-complete', [
            'token' => $token,
            'latitude' => 14.5001,
            'longitude' => 121.0001,
            'accuracy' => 10,
            'biometric_method' => 'face',
            'live_frame' => $impostorLiveFrame,
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('BIOMETRIC_MISMATCH', $response->json('code'));

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $student->id,
            'subject_code' => $subject->code,
        ]);
    }

    public function test_face_verification_rejects_student_outside_classroom_geofence()
    {
        [$teacher, $student, $subject, $token] = $this->createTestEnvironment();

        $photoBytes = $this->createSyntheticFaceImage(1);
        Storage::disk('public')->put('profile_images/student_geo.jpg', $photoBytes);
        $student->update(['profile_image' => 'profile_images/student_geo.jpg']);

        $matchingLiveFrame = $this->toDataUrl($photoBytes);

        // Student is far outside classroom (e.g. 15.5 lat vs 14.5 lat ~ 111 km away)
        $response = $this->actingAs($student)->postJson('/qr/verify-complete', [
            'token' => $token,
            'latitude' => 15.5000,
            'longitude' => 121.0000,
            'accuracy' => 10,
            'biometric_method' => 'face',
            'live_frame' => $matchingLiveFrame,
        ]);

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

        // Also enrolled WebAuthn fingerprint credential
        $student->webauthnCredentials()->create([
            'credential_id' => 'fp_cred_123',
            'public_key' => 'fake_public_key',
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
