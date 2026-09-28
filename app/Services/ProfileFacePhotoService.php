<?php

namespace App\Services;

use App\Models\ProfileFacePhoto;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProfileFacePhotoService
{
    private const MAX_SOURCE_BYTES = 5 * 1024 * 1024;
    private const MAX_SIDE = 1280;

    public function save(User $user, string $sourceBytes, string $sourcePath): void
    {
        $jpeg = $this->normalize($sourceBytes);
        if ($jpeg === null) {
            throw new \RuntimeException('Profile photo must be a readable JPEG, PNG, WebP, or GIF image.');
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

    public function delete(User $user): void
    {
        ProfileFacePhoto::where('user_id', $user->id)->delete();
    }

    public function bytes(User $user): ?string
    {
        $path = (string) $user->profile_image;
        if ($path === '') {
            return null;
        }

        $saved = ProfileFacePhoto::find($user->id);
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
        try {
            $bytes = base64_decode(Crypt::decryptString($photo->image_ciphertext), true);
            return is_string($bytes) && $bytes !== '' ? $bytes : null;
        } catch (\Throwable) {
            return null;
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

        $image = @imagecreatefromstring($bytes);
        if (!$image) {
            return null;
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
}
