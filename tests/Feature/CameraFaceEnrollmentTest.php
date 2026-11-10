<?php

namespace Tests\Feature;

use App\Models\ProfileFacePhoto;
use App\Models\Setting;
use App\Models\User;
use App\Services\ProfileFaceEnrollmentService;
use App\Services\ProfileFacePhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CameraFaceEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private function setQualityResult(array $result): void
    {
        $this->app->instance(ProfileFaceEnrollmentService::class, new class($result) extends ProfileFaceEnrollmentService {
            public function __construct(private array $result) {}

            public function validate(string $normalizedJpeg): array
            {
                return $this->result;
            }
        });
    }

    public function test_student_camera_enrollment_is_quality_checked_and_saved_encrypted_without_public_avatar(): void
    {
        $this->setQualityResult(['valid' => true, 'code' => 'PROFILE_READY', 'message' => '']);
        $student = User::factory()->create(['role' => 'student', 'profile_image' => null]);

        $response = $this->actingAs($student)->postJson(route('profile.face.enroll'), [
            'face_image' => UploadedFile::fake()->image('camera-capture.jpg', 480, 640)->size(500),
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNull($student->fresh()->profile_image);
        $photo = ProfileFacePhoto::findOrFail($student->id);
        $this->assertSame('camera-face-enrollment', $photo->source_path);
        $this->assertNotSame('', $photo->image_ciphertext);
        $this->assertNotNull(app(ProfileFacePhotoService::class)->bytes($student->fresh()));
        $this->actingAs($student)->get(route('settings'))
            ->assertOk()
            ->assertSee('A face profile is enrolled for attendance matching.', false);
    }

    public function test_student_settings_renders_camera_preview_and_registration_controls(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('settings'))
            ->assertOk()
            ->assertSee('id="cameraFaceVideo"', false)
            ->assertSee('id="cameraFaceCaptureBtn" hidden', false)
            ->assertSee('id="cameraFaceSaveBtn" hidden', false);

        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        // Face enrollment remains available independently of the device prompt UI.
        $this->assertSame(1, $xpath->query('//*[@id="cameraFaceEnrollment"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="studentPasskeySettings"]//*[@id="cameraFaceEnrollment"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="studentPasskeySettings"]//*[@id="methodCardFp"]')->length);
    }

    public function test_student_settings_explains_when_camera_enrollment_is_disabled(): void
    {
        Setting::set('feature.face_verification', '0', false);
        Cache::forget('application_feature_flags');
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('settings'))
            ->assertOk()
            ->assertSee('Camera-based face registration is currently unavailable.', false)
            ->assertDontSee('id="cameraFaceStartBtn"', false)
            ->assertDontSee('id="cameraFaceCaptureBtn"', false);
    }

    public function test_camera_enrollment_rejects_non_students_and_does_not_call_face_matcher(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacher)->postJson(route('profile.face.enroll'), [
            'face_image' => UploadedFile::fake()->image('camera-capture.jpg', 480, 640),
        ])->assertForbidden();

        $this->assertDatabaseCount('profile_face_photos', 0);
    }

    public function test_camera_enrollment_does_not_save_a_face_that_fails_server_quality_validation(): void
    {
        $this->setQualityResult(['valid' => false, 'code' => 'PROFILE_FACE_NOT_FOUND', 'message' => 'No clear face was found.']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->postJson(route('profile.face.enroll'), [
            'face_image' => UploadedFile::fake()->image('camera-capture.jpg', 480, 640),
        ])->assertUnprocessable()->assertJsonPath('code', 'PROFILE_FACE_NOT_FOUND');

        $this->assertDatabaseMissing('profile_face_photos', ['user_id' => $student->id]);
    }

    public function test_camera_enrollment_respects_face_verification_feature_flag(): void
    {
        Setting::set('feature.face_verification', '0', false);
        Cache::forget('application_feature_flags');
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->postJson(route('profile.face.enroll'), [
            'face_image' => UploadedFile::fake()->image('camera-capture.jpg', 480, 640),
        ])->assertStatus(503)->assertJsonPath('code', 'FACE_VERIFICATION_DISABLED');

        $this->assertDatabaseMissing('profile_face_photos', ['user_id' => $student->id]);
    }

    public function test_settings_has_a_camera_enrollment_flow_separate_from_device_passkeys(): void
    {
        $view = file_get_contents(resource_path('views/settings.blade.php'));

        $this->assertStringContainsString('Register face for attendance', $view);
        $this->assertStringContainsString('navigator.mediaDevices.getUserMedia', $view);
        $this->assertStringContainsString("route(\"profile.face.enroll\")", $view);
        $this->assertStringContainsString('This enrolls a device passkey', $view);

        $flowStart = strpos($view, '// ── Camera-based face enrollment for attendance ──');
        $flowEnd = strpos($view, '// ── Device Passkey Registration ──', $flowStart ?: 0);
        $cameraFlow = $flowStart !== false && $flowEnd !== false
            ? substr($view, $flowStart, $flowEnd - $flowStart)
            : '';
        $this->assertNotSame('', $cameraFlow);
        $this->assertStringNotContainsString('fingerprint', strtolower($cameraFlow));
        $this->assertStringNotContainsString('navigator.credentials.create', $cameraFlow);
    }
}
