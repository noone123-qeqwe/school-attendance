<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class ProfileFaceEnrollmentService
{
    /** @return array{valid: bool, code: string, message: string} */
    public function validate(string $normalizedJpeg): array
    {
        $metadata = @getimagesizefromstring($normalizedJpeg);
        if (!is_array($metadata) || ($metadata['mime'] ?? null) !== 'image/jpeg' ||
            !isset($metadata[0], $metadata[1]) || min($metadata[0], $metadata[1]) < 100 ||
            max($metadata[0], $metadata[1]) > 1280) {
            return $this->failure('PROFILE_IMAGE_UNREADABLE');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'face_enroll_');
        if ($temporaryPath === false) {
            return $this->failure('MATCHER_UNAVAILABLE');
        }

        try {
            if (file_put_contents($temporaryPath, $normalizedJpeg) !== strlen($normalizedJpeg)) {
                return $this->failure('MATCHER_UNAVAILABLE');
            }

            $process = new Process([
                config('face_match.python'),
                base_path('scripts/match_profile_face.py'),
                '--validate-profile',
                $temporaryPath,
                config('face_match.detector_model'),
                config('face_match.recognizer_model'),
            ]);
            $process->setTimeout(12);
            $process->run(null, ['OMP_NUM_THREADS' => '1', 'OPENBLAS_NUM_THREADS' => '1']);

            $result = json_decode(trim($process->getOutput()), true);
            $code = is_array($result) ? (string) ($result['code'] ?? '') : '';
            if ($code === 'PROFILE_READY' && $process->isSuccessful() && ($result['valid'] ?? false) === true) {
                return ['valid' => true, 'code' => 'PROFILE_READY', 'message' => ''];
            }

            $allowedCodes = [
                'PROFILE_IMAGE_UNREADABLE', 'PROFILE_IMAGE_QUALITY', 'PROFILE_FACE_NOT_FOUND',
                'PROFILE_MULTIPLE_FACES', 'PROFILE_FACE_TOO_SMALL', 'PROFILE_IMAGE_BLURRY',
                'MATCHER_UNAVAILABLE',
            ];
            if (!$process->isSuccessful() || !in_array($code, $allowedCodes, true)) {
                $code = 'MATCHER_UNAVAILABLE';
            }

            return $this->failure($code);
        } catch (\Throwable) {
            return $this->failure('MATCHER_UNAVAILABLE');
        } finally {
            @unlink($temporaryPath);
        }
    }

    private function failure(string $code): array
    {
        $message = match ($code) {
            'PROFILE_IMAGE_UNREADABLE', 'PROFILE_IMAGE_QUALITY' =>
                'This photo could not be read. Choose a JPG, PNG, WEBP, or GIF image at least 100 pixels wide and high.',
            'PROFILE_FACE_NOT_FOUND' =>
                'No clear face was found. Choose a recent, front-facing photo with your face visible.',
            'PROFILE_MULTIPLE_FACES' =>
                'More than one face was found. Choose a photo that shows only you.',
            'PROFILE_FACE_TOO_SMALL' =>
                'Your face is too small in this photo. Move closer or crop so your face is clearly visible.',
            'PROFILE_IMAGE_BLURRY' =>
                'This photo is too blurry for face matching. Choose a sharper photo with good lighting.',
            default =>
                'Face-photo checks are temporarily unavailable. Please retry later.',
        };

        return ['valid' => false, 'code' => $code, 'message' => $message];
    }
}
