<?php

namespace Tests\Unit;

use App\Services\ProfileFaceEnrollmentService;
use App\Services\ProfileFacePhotoService;
use Tests\TestCase;

class ProfileFaceEnrollmentServiceTest extends TestCase
{
    public function test_enrollment_check_rejects_non_image_bytes_before_starting_matcher(): void
    {
        $service = new ProfileFaceEnrollmentService();

        $result = $service->validate('not an image');

        $this->assertFalse($result['valid']);
        $this->assertSame('PROFILE_IMAGE_UNREADABLE', $result['code']);
        $this->assertNotSame('', $result['message']);
    }

    public function test_profile_photo_normalization_rejects_oversized_dimensions_from_metadata(): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD is required to generate a dimension-boundary image.');
        }

        $image = imagecreatetruecolor(5001, 100);
        ob_start();
        imagejpeg($image, null, 60);
        $bytes = ob_get_clean();
        imagedestroy($image);

        $normalized = (new ProfileFacePhotoService())->normalizeForMatching((string) $bytes);

        $this->assertNull($normalized);
    }
}
