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
use Illuminate\Support\Facades\Crypt;
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

            public function validateViews(array $views): array
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
        $this->actingAs($student)->get(route('settings.biometrics.face'))
            ->assertOk()
            ->assertSee('A face profile is enrolled for attendance matching.', false);
    }

    public function test_face_registration_has_its_own_camera_and_light_controls(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $response = $this->actingAs($student)->get(route('settings.biometrics.face'))
            ->assertOk()
            ->assertSee('face-registration-page', false)
            ->assertSee('id="cameraFaceStage"', false)
            ->assertSee('Back to Biometrics')
            ->assertSee('id="cameraFaceVideo"', false)
            ->assertSee('id="cameraFaceCaptureBtn" hidden', false)
            ->assertSee('id="cameraFaceSaveBtn" hidden', false)
            ->assertSee('Scan three face views')
            ->assertSee('Center your face')
            ->assertSee('uploaded only when you tap Register face', false)
            ->assertDontSee('id="topHeader"', false)
            ->assertDontSee('id="startBioBtn"', false);

        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(3, $xpath->query('//*[@id="cameraFaceEnrollment"]//*[@data-face-pose]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="cameraFaceLight"]//*[@data-light-toggle]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="cameraFaceLight"]//label[@for="cameraFaceLight-level"]')->length);
        $this->assertSame(1, $xpath->query('//meta[@name="csrf-token"]')->length);
    }

    public function test_fingerprint_screen_explains_each_stage_and_shows_clear_registration_states(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('settings.biometrics.fingerprint'))
            ->assertOk()
            ->assertSee('Fingerprint sign-in')
            ->assertSee('Device-protected sign-in')
            ->assertSee('Your fingerprint or face stays on your device.', false)
            ->assertSee('data-bio-step="1"', false)
            ->assertSee('data-bio-step="2"', false)
            ->assertSee('data-bio-step="3"', false)
            ->assertSee('id="bioScanningView"', false)
            ->assertSee('id="bioSuccessView"', false)
            ->assertSee('id="bioErrorView"', false)
            ->assertSee('Registered devices');
    }

    public function test_registration_pages_require_a_student_session(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        foreach (['face', 'fingerprint'] as $method) {
            $this->get(route('settings.biometrics.'.$method))->assertRedirect(route('login'));
        }
        foreach (['face', 'fingerprint'] as $method) {
            $this->actingAs($teacher)->getJson(route('settings.biometrics.'.$method))->assertForbidden();
        }
    }

    public function test_face_registration_explains_when_camera_enrollment_is_disabled(): void
    {
        Setting::set('feature.face_verification', '0', false);
        Cache::forget('application_feature_flags');
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('settings.biometrics.face'))
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
        $view = file_get_contents(resource_path('views/student/biometrics/face.blade.php'));

        $this->assertStringContainsString("route('profile.face.enroll')", $view);

        $cameraFlow = file_get_contents(public_path('js/camera-face-enrollment.js'));
        $this->assertNotSame('', $cameraFlow);
        $this->assertStringContainsString('navigator.mediaDevices.getUserMedia', $cameraFlow);
        $this->assertStringContainsString('window.FaceDetector', $cameraFlow);
        $this->assertStringContainsString('automatic face scan', strtolower($cameraFlow));
        $this->assertStringNotContainsString('fingerprint', strtolower($cameraFlow));
        $this->assertStringNotContainsString('navigator.credentials.create', $cameraFlow);
    }

    private function cameraViews(): array
    {
        $views = [];
        foreach (['front', 'left', 'right'] as $pose) {
            $views[$pose] = UploadedFile::fake()->image($pose.'.jpg', 480, 640);
        }
        return $views;
    }

    public function test_three_validated_views_are_encrypted_and_available_to_the_attendance_matcher(): void
    {
        $this->setQualityResult(['valid' => true, 'code' => 'PROFILE_READY', 'message' => '']);
        $student = User::factory()->create(['role' => 'student', 'profile_image' => null]);

        $this->actingAs($student)->postJson(route('profile.face.enroll'), [
            'face_images' => $this->cameraViews(),
        ])->assertOk()->assertJsonPath('view_count', 3);

        $photo = ProfileFacePhoto::findOrFail($student->id);
        $payload = json_decode(Crypt::decryptString($photo->image_ciphertext), true);
        $this->assertSame('camera-views-v1', $payload['format']);
        $this->assertSame(['front', 'left', 'right'], array_keys($payload['views']));
        $references = app(ProfileFacePhotoService::class)->referenceImages($student);
        $this->assertCount(3, $references);
        foreach ($references as $image) {
            $this->assertSame('image/jpeg', getimagesizefromstring($image)['mime']);
            $this->assertStringNotContainsString(base64_encode($image), $photo->image_ciphertext);
        }
        $this->assertSame($references[0], app(ProfileFacePhotoService::class)->bytes($student));
        $this->assertNull($student->fresh()->profile_image);
    }

    public function test_incomplete_or_extra_positions_cannot_be_registered(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $views = $this->cameraViews();
        unset($views['right']);
        $this->actingAs($student)->postJson(route('profile.face.enroll'), ['face_images' => $views])
            ->assertUnprocessable()->assertJsonValidationErrors('face_images.right');
        $views = $this->cameraViews();
        $views['extra'] = UploadedFile::fake()->image('extra.jpg', 480, 640);
        $this->postJson(route('profile.face.enroll'), ['face_images' => $views])
            ->assertUnprocessable()->assertJsonValidationErrors('face_images');
        $this->assertDatabaseMissing('profile_face_photos', ['user_id' => $student->id]);
    }

    public function test_pose_or_identity_failure_preserves_the_existing_enrollment(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $photos = app(ProfileFacePhotoService::class);
        $photos->saveCameraEnrollment($student, UploadedFile::fake()->image('previous.jpg', 480, 640)->get());
        $previous = ProfileFacePhoto::findOrFail($student->id)->image_ciphertext;
        foreach (['PROFILE_POSE_VARIATION', 'PROFILE_IDENTITY_MISMATCH', 'MATCHER_UNAVAILABLE'] as $code) {
            $this->setQualityResult(['valid' => false, 'code' => $code, 'message' => 'Retake the captures.']);
            $this->actingAs($student)->postJson(route('profile.face.enroll'), ['face_images' => $this->cameraViews()])
                ->assertStatus($code === 'MATCHER_UNAVAILABLE' ? 503 : 422)->assertJsonPath('code', $code);
            $this->assertSame($previous, ProfileFacePhoto::findOrFail($student->id)->image_ciphertext);
        }
    }

    public function test_changing_the_public_avatar_does_not_replace_camera_enrollment(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $photos = app(ProfileFacePhotoService::class);
        $views = array_map(fn ($file) => $file->get(), $this->cameraViews());
        $photos->saveCameraViews($student, $views);
        $ciphertext = ProfileFacePhoto::findOrFail($student->id)->image_ciphertext;
        $photos->save($student, UploadedFile::fake()->image('avatar.jpg', 200, 200)->get(), 'profile-photos/avatar.jpg');
        $this->assertSame($ciphertext, ProfileFacePhoto::findOrFail($student->id)->image_ciphertext);
        $this->assertCount(3, $photos->referenceImages($student));
    }
}
