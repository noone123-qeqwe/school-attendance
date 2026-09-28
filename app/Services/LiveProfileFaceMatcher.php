<?php

namespace App\Services;

use App\Models\User;
use Symfony\Component\Process\Process;

class LiveProfileFaceMatcher
{
    public function compare(User $user, ?string $liveFrame): array
    {
        $reference = app(ProfileFacePhotoService::class)->bytes($user);
        if ($reference === null) {
            return $this->failure('NO_PROFILE_PHOTO');
        }

        $liveBytes = $this->decodeLiveFrame($liveFrame);
        if ($liveBytes === null) {
            return $this->failure('INVALID_LIVE_FRAME');
        }

        $referencePath = tempnam(sys_get_temp_dir(), 'face_ref_');
        $livePath = tempnam(sys_get_temp_dir(), 'face_live_');
        if ($referencePath === false || $livePath === false) {
            if ($referencePath !== false) @unlink($referencePath);
            if ($livePath !== false) @unlink($livePath);
            return $this->failure('MATCHER_UNAVAILABLE');
        }

        try {
            if (file_put_contents($referencePath, $reference) !== strlen($reference) ||
                file_put_contents($livePath, $liveBytes) !== strlen($liveBytes)) {
                return $this->failure('MATCHER_UNAVAILABLE');
            }

            $process = new Process([
                config('face_match.python'),
                base_path('scripts/match_profile_face.py'),
                $referencePath,
                $livePath,
                config('face_match.detector_model'),
                config('face_match.recognizer_model'),
            ]);
            $process->setTimeout(20);
            $process->run(null, ['OMP_NUM_THREADS' => '1', 'OPENBLAS_NUM_THREADS' => '1']);
            $result = json_decode(trim($process->getOutput()), true);
            if (!$process->isSuccessful() || !is_array($result) || !is_bool($result['match'] ?? null)) {
                return $this->failure('MATCHER_UNAVAILABLE');
            }

            $code = (string) ($result['code'] ?? 'MATCHER_UNAVAILABLE');
            $allowedCodes = [
                'MATCH', 'BIOMETRIC_MISMATCH', 'PROFILE_IMAGE_UNREADABLE',
                'PROFILE_IMAGE_QUALITY', 'PROFILE_FACE_NOT_FOUND',
                'PROFILE_MULTIPLE_FACES', 'PROFILE_FACE_TOO_SMALL',
                'PROFILE_IMAGE_BLURRY', 'LIVE_IMAGE_UNREADABLE',
                'LIVE_IMAGE_QUALITY', 'LIVE_FACE_NOT_FOUND',
                'LIVE_MULTIPLE_FACES', 'LIVE_FACE_TOO_SMALL',
                'LIVE_IMAGE_BLURRY',
            ];
            if (!in_array($code, $allowedCodes, true)) {
                return $this->failure('MATCHER_UNAVAILABLE');
            }

            return [
                'match' => $result['match'] && $code === 'MATCH',
                'code' => $code,
                'similarity' => is_numeric($result['similarity'] ?? null) ? (float) $result['similarity'] : 0.0,
                'method' => 'opencv_yunet_sface',
                'message' => $this->message($code),
            ];
        } catch (\Throwable) {
            return $this->failure('MATCHER_UNAVAILABLE');
        } finally {
            @unlink($referencePath);
            @unlink($livePath);
        }
    }

    private function decodeLiveFrame(?string $frame): ?string
    {
        if (!is_string($frame) || strlen($frame) > 4 * 1024 * 1024 ||
            !preg_match('#^data:image/(?:jpeg|png|webp);base64,([A-Za-z0-9+/=]+)$#D', $frame, $matches)) {
            return null;
        }

        $bytes = base64_decode($matches[1], true);
        if (!is_string($bytes) || strlen($bytes) < 1024 || strlen($bytes) > 2 * 1024 * 1024) {
            return null;
        }

        $size = @getimagesizefromstring($bytes);
        if (!is_array($size) || min($size[0], $size[1]) < 100 || max($size[0], $size[1]) > 2000) {
            return null;
        }

        return $bytes;
    }

    private function failure(string $code): array
    {
        return [
            'match' => false,
            'code' => $code,
            'similarity' => 0.0,
            'method' => 'opencv_yunet_sface',
            'message' => $this->message($code),
        ];
    }

    private function message(string $code): string
    {
        return match ($code) {
            'MATCH' => 'Live face matched the registered profile photo.',
            'NO_PROFILE_PHOTO', 'PROFILE_IMAGE_UNREADABLE', 'PROFILE_FACE_NOT_FOUND' =>
                'Your profile photo is unavailable or has no clear face. Upload a new, front-facing photo in Settings.',
            'PROFILE_MULTIPLE_FACES', 'PROFILE_FACE_TOO_SMALL', 'PROFILE_IMAGE_BLURRY', 'PROFILE_IMAGE_QUALITY' =>
                'Your profile photo must show one clear, front-facing face. Upload a new photo in Settings.',
            'LIVE_FACE_NOT_FOUND', 'LIVE_FACE_TOO_SMALL', 'LIVE_IMAGE_BLURRY', 'LIVE_IMAGE_QUALITY' =>
                'Move closer to the camera and keep your face clear and steady, then try again.',
            'LIVE_MULTIPLE_FACES' => 'Only one person can be in the camera frame. Please try again.',
            'INVALID_LIVE_FRAME', 'LIVE_IMAGE_UNREADABLE' => 'The camera frame could not be read. Please retry the camera scan.',
            'BIOMETRIC_MISMATCH' => 'Your live face did not match your registered profile photo. Please try again in good lighting.',
            default => 'Face matching is temporarily unavailable. Please use your registered fingerprint or ask your teacher for help.',
        };
    }
}
