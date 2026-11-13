<?php

namespace App\Services;

use App\Models\ProfileFacePhoto;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProfileFacePhotoService
{
    private const CAMERA_ENROLLMENT_SOURCE = 'camera-face-enrollment';
    private const MAX_SOURCE_BYTES = 5 * 1024 * 1024;
    private const MAX_SIDE = 1280;
    public const MAX_CAMERA_CAPTURE_BYTES = 3 * 1024 * 1024;

    public function save(User $user, string $sourceBytes, string $sourcePath): void
    {
        $jpeg = $this->normalizeForMatching($sourceBytes);
        if ($jpeg === null) {
            throw new \RuntimeException('Profile photo must be a readable JPEG, PNG, WebP, or GIF image.');
        }

        $this->saveNormalized($user, $jpeg, $sourcePath);
    }

    /** Persist an already-normalized JPEG so enrollment validates and stores identical bytes. */
    public function saveNormalized(User $user, string $jpeg, string $sourcePath): void
    {
        // Changing a public avatar must not replace an explicit camera enrollment.
        if ($sourcePath !== self::CAMERA_ENROLLMENT_SOURCE && ProfileFacePhoto::where('user_id', $user->id)
            ->where('source_path', self::CAMERA_ENROLLMENT_SOURCE)->exists()) {
            return;
        }
        $metadata = @getimagesizefromstring($jpeg);
        if (strlen($jpeg) < 128 || strlen($jpeg) > self::MAX_SOURCE_BYTES ||
            !is_array($metadata) || ($metadata['mime'] ?? null) !== 'image/jpeg' ||
            !isset($metadata[0], $metadata[1]) || min($metadata[0], $metadata[1]) < 100 ||
            max($metadata[0], $metadata[1]) > self::MAX_SIDE) {
            throw new \RuntimeException('Normalized profile photo is invalid.');
        }

        ProfileFacePhoto::updateOrCreate(
            ['user_id' => $user->id],
            [
                'image_ciphertext' => Crypt::encryptString(base64_encode($jpeg)),
                'mime_type' => 'image/jpeg',
                'source_path' => $sourcePath,
            ]
        );
    }

    /** Persist a camera enrollment without placing the face image in public avatar storage. */
    public function saveCameraEnrollment(User $user, string $jpeg): void
    {
        $this->saveNormalized($user, $jpeg, self::CAMERA_ENROLLMENT_SOURCE);
    }

    /** Store the complete, validated capture set atomically in the existing encrypted row. */
    public function saveCameraViews(User $user, array $views): void
    {
        if (array_keys($views) !== ['front', 'left', 'right']) {
            throw new \RuntimeException('All three face positions are required.');
        }
        $totalBytes = 0;
        foreach ($views as $jpeg) {
            if (!is_string($jpeg)) {
                throw new \RuntimeException('Invalid face capture.');
            }
            $totalBytes += strlen($jpeg);
            $metadata = @getimagesizefromstring($jpeg);
            if (strlen($jpeg) < 128 || !is_array($metadata) || $metadata['mime'] !== 'image/jpeg' ||
                min($metadata[0], $metadata[1]) < 100 || max($metadata[0], $metadata[1]) > self::MAX_SIDE ||
                $totalBytes > self::MAX_CAMERA_CAPTURE_BYTES) {
                throw new \RuntimeException('Invalid face capture.');
            }
        }
        ProfileFacePhoto::updateOrCreate(['user_id' => $user->id], [
            'image_ciphertext' => Crypt::encryptString(json_encode([
                'format' => 'camera-views-v1',
                'views' => array_map('base64_encode', $views),
            ], JSON_THROW_ON_ERROR)),
            'mime_type' => 'image/jpeg',
            'source_path' => self::CAMERA_ENROLLMENT_SOURCE,
        ]);
    }

    /** @return list<string> Decrypted references, available only to the server matcher. */
    public function referenceImages(User $user): array
    {
        $saved = ProfileFacePhoto::find($user->id);
        if ($saved && $saved->source_path === self::CAMERA_ENROLLMENT_SOURCE) {
            return array_values($this->decryptViews($saved));
        }
        $front = $this->bytes($user);
        return $front === null ? [] : [$front];
    }

    /** Attendance identity must come from camera enrollment, independent of the editable avatar. */
    public function hasCameraEnrollment(User $user): bool
    {
        return ProfileFacePhoto::whereKey($user->id)->where('source_path', self::CAMERA_ENROLLMENT_SOURCE)->exists();
    }

    /** @return list<string> Decrypted attendance references for the verifier. */
    public function cameraReferences(User $user): array
    {
        $photo = ProfileFacePhoto::whereKey($user->id)->where('source_path', self::CAMERA_ENROLLMENT_SOURCE)->first();

        return $photo ? array_values($this->decryptViews($photo)) : [];
    }

    /** Normalize enrollment input once before face-quality validation and encrypted storage. */
    public function normalizeForMatching(string $sourceBytes): ?string
    {
        return $this->normalize($sourceBytes);
    }

    public function delete(User $user): void
    {
        ProfileFacePhoto::where('user_id', $user->id)->delete();
    }

    public function bytes(User $user): ?string
    {
        $saved = ProfileFacePhoto::find($user->id);
        if ($saved && hash_equals(self::CAMERA_ENROLLMENT_SOURCE, (string) $saved->source_path)) {
            return $this->decrypt($saved);
        }

        $path = (string) $user->profile_image;
        if ($path === '') {
            return null;
        }

        if ($saved && hash_equals((string) $saved->source_path, $path)) {
            $bytes = $this->decrypt($saved);
            if ($bytes !== null) {
                return $bytes;
            }
        }

        $source = $this->readExistingPhoto($path);
        if ($source === null) {
            return null;
        }

        try {
            $this->save($user, $source, $path);
        } catch (\RuntimeException) {
            return null;
        }

        $saved = ProfileFacePhoto::find($user->id);
        return $saved ? $this->decrypt($saved) : null;
    }

    private function decrypt(ProfileFacePhoto $photo): ?string
    {
        return $this->decryptViews($photo)['front'] ?? null;
    }

    private function decryptViews(ProfileFacePhoto $photo): array
    {
        try {
            $plaintext = Crypt::decryptString($photo->image_ciphertext);
            $payload = json_decode($plaintext, true);
            if (is_array($payload) && ($payload['format'] ?? '') === 'camera-views-v1') {
                $views = $payload['views'] ?? [];
                if (!is_array($views) || array_keys($views) !== ['front', 'left', 'right']) return [];
                $decoded = [];
                foreach ($views as $pose => $encoded) {
                    $bytes = is_string($encoded) ? base64_decode($encoded, true) : false;
                    if (!is_string($bytes) || $bytes === '') return [];
                    $decoded[$pose] = $bytes;
                }
                return $decoded;
            }
            $bytes = base64_decode($plaintext, true);
            return is_string($bytes) && $bytes !== '' ? ['front' => $bytes] : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function readExistingPhoto(string $path): ?string
    {
        if (str_starts_with($path, 'https://')) {
            $host = strtolower((string) parse_url($path, PHP_URL_HOST));
            if ($host !== 'res.cloudinary.com') {
                return null;
            }
            try {
                $response = Http::timeout(6)->withOptions(['allow_redirects' => false])->get($path);
                $body = $response->successful() ? $response->body() : '';
                return strlen($body) <= self::MAX_SOURCE_BYTES ? $body : null;
            } catch (\Throwable) {
                return null;
            }
        }

        if (str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, '\\')) {
            return null;
        }

        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        $bytes = Storage::disk('public')->get($path);
        return strlen($bytes) <= self::MAX_SOURCE_BYTES ? $bytes : null;
    }

    private function normalize(string $bytes): ?string
    {
        if (strlen($bytes) < 128 || strlen($bytes) > self::MAX_SOURCE_BYTES || !function_exists('imagecreatefromstring')) {
            return null;
        }

        // Check dimensions from image metadata before decoding pixel data. This avoids
        // allocating a huge bitmap for a small, highly-compressed upload.
        $metadata = @getimagesizefromstring($bytes);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!is_array($metadata) || !in_array($metadata['mime'] ?? '', $allowedMimes, true) ||
            !isset($metadata[0], $metadata[1]) || min($metadata[0], $metadata[1]) < 100 ||
            max($metadata[0], $metadata[1]) > 5000) {
            return null;
        }

        $image = @imagecreatefromstring($bytes);
        if (!$image) {
            return null;
        }

        // Phone JPEGs often store camera rotation in EXIF rather than pixels.
        $orientation = $this->jpegOrientation($bytes);
        $degrees = match ($orientation) { 3 => 180, 6 => 270, 8 => 90, default => 0 };
        if ($degrees !== 0) {
            $rotated = imagerotate($image, $degrees, 0);
            if ($rotated !== false) {
                imagedestroy($image);
                $image = $rotated;
            }
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width < 100 || $height < 100 || $width > 5000 || $height > 5000) {
            imagedestroy($image);
            return null;
        }

        $scale = min(1.0, self::MAX_SIDE / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $normalized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($normalized, 0, 0, imagecolorallocate($normalized, 255, 255, 255));
        imagecopyresampled($normalized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagedestroy($image);

        ob_start();
        imagejpeg($normalized, null, 85);
        $jpeg = ob_get_clean();
        imagedestroy($normalized);

        return is_string($jpeg) && $jpeg !== '' ? $jpeg : null;
    }

    private function jpegOrientation(string $bytes): int
    {
        if (!function_exists('exif_read_data') || !str_starts_with($bytes, "\xFF\xD8")) {
            return 1;
        }

        $path = tempnam(sys_get_temp_dir(), 'profile_exif_');
        if ($path === false) {
            return 1;
        }

        try {
            if (file_put_contents($path, $bytes) !== strlen($bytes)) {
                return 1;
            }
            $exif = @exif_read_data($path, 'IFD0', true, false);
            return is_array($exif) ? (int) ($exif['IFD0']['Orientation'] ?? $exif['Orientation'] ?? 1) : 1;
        } finally {
            @unlink($path);
        }
    }
}
