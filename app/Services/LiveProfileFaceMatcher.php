<?php

namespace App\Services;

use App\Models\User;
use Symfony\Component\Process\Process;

class LiveProfileFaceMatcher
{
    public function compare(User $user, ?string $liveFrame): array
    {
        return $this->compareFrameSet($user, [$liveFrame], false);
    }

    /** Compare two or three controlled camera samples against the registered photo. */
    public function compareFrames(User $user, array $liveFrames): array
    {
        if (count($liveFrames) < 2 || count($liveFrames) > 3) {
            return $this->failure('INCOMPLETE_MULTI_FRAME_CAPTURE');
        }

        return $this->compareFrameSet($user, $liveFrames, true);
    }

    private function compareFrameSet(User $user, array $liveFrames, bool $requireConsistency): array
    {
        $reference = app(ProfileFacePhotoService::class)->bytes($user);
        if ($reference === null) {
            return $this->failure('NO_PROFILE_PHOTO');
        }

        $liveBytes = [];
        foreach ($liveFrames as $frame) {
            $bytes = $this->decodeLiveFrame($frame);
            if ($bytes === null) {
                return $this->failure('INVALID_LIVE_FRAME');
            }
            $liveBytes[] = $bytes;
        }

        $threshold = filter_var(config('face_match.threshold'), FILTER_VALIDATE_FLOAT);
        if ($threshold === false || !is_finite((float) $threshold) || $threshold <= 0 || $threshold > 1) {
            return $this->failure('MATCHER_UNAVAILABLE');
        }

        $temporaryPaths = [];
        try {
            $referencePath = tempnam(sys_get_temp_dir(), 'face_ref_');
            if ($referencePath === false) {
                return $this->failure('MATCHER_UNAVAILABLE');
            }
            $temporaryPaths[] = $referencePath;

            $framePaths = [];
            foreach ($liveBytes as $bytes) {
                $framePath = tempnam(sys_get_temp_dir(), 'face_live_');
                if ($framePath === false) {
                    return $this->failure('MATCHER_UNAVAILABLE');
                }
                $temporaryPaths[] = $framePath;
                $framePaths[] = $framePath;

                if (file_put_contents($framePath, $bytes) !== strlen($bytes)) {
                    return $this->failure('MATCHER_UNAVAILABLE');
                }
            }

            if (file_put_contents($referencePath, $reference) !== strlen($reference)) {
                return $this->failure('MATCHER_UNAVAILABLE');
            }

            $process = new Process([
                config('face_match.python'),
                base_path('scripts/match_profile_face.py'),
                $referencePath,
                ...$framePaths,
                config('face_match.detector_model'),
                config('face_match.recognizer_model'),
                (string) $threshold,
            ]);
            $process->setTimeout(20);
            $process->run(null, ['OMP_NUM_THREADS' => '1', 'OPENBLAS_NUM_THREADS' => '1']);
            $result = json_decode(trim($process->getOutput()), true);
            if (!$process->isSuccessful() || !is_array($result) || !is_bool($result['match'] ?? null)) {
                return $this->failure('MATCHER_UNAVAILABLE');
            }

            $similarity = $result['similarity'] ?? null;
            if (!is_numeric($similarity) || !is_finite((float) $similarity) ||
                (float) $similarity < -1 || (float) $similarity > 1) {
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
                'LIVE_IMAGE_BLURRY', 'LIVE_FRAME_INCONSISTENT',
            ];
            if (!in_array($code, $allowedCodes, true)) {
                return $this->failure('MATCHER_UNAVAILABLE');
            }

            $similarity = (float) $similarity;
            $frameConsistent = ($result['frame_consistent'] ?? true) === true;
            $match = $result['match'] && $code === 'MATCH' && $similarity >= $threshold
                && (!$requireConsistency || $frameConsistent);
            if ($code === 'MATCH' && !$match) {
                $code = 'BIOMETRIC_MISMATCH';
            }

            return [
                'match' => $match,
                'code' => $code,
                'similarity' => $similarity,
                'method' => 'opencv_yunet_sface',
                'frame_count' => count($liveFrames),
                // Identical samples are recorded only as a diagnostic; they are not proof of spoofing.
                'frames_identical' => count(array_unique(array_map('hash', array_fill(0, count($liveFrames), 'sha256'), $liveFrames))) === 1,
                'message' => $this->message($code),
            ];
        } catch (\Throwable) {
            return $this->failure('MATCHER_UNAVAILABLE');
        } finally {
            foreach ($temporaryPaths as $path) {
                @unlink($path);
            }
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
            'MATCH' => 'Face samples matched the registered profile photo.',
            'NO_PROFILE_PHOTO', 'PROFILE_IMAGE_UNREADABLE', 'PROFILE_FACE_NOT_FOUND' =>
                'Your profile photo is unavailable or has no clear face. Upload a new, front-facing photo in Settings.',
            'PROFILE_MULTIPLE_FACES', 'PROFILE_FACE_TOO_SMALL', 'PROFILE_IMAGE_BLURRY', 'PROFILE_IMAGE_QUALITY' =>
                'Your profile photo must show one clear, front-facing face. Upload a new photo in Settings.',
            'LIVE_FACE_NOT_FOUND', 'LIVE_FACE_TOO_SMALL', 'LIVE_IMAGE_BLURRY', 'LIVE_IMAGE_QUALITY' =>
                'Move closer to the camera and keep your face clear and steady, then try again.',
            'LIVE_MULTIPLE_FACES' => 'Only one person can be in the camera frame. Please try again.',
            'LIVE_FRAME_INCONSISTENT' => 'Your face moved between the camera samples. Keep your face steady and retry.',
            'INCOMPLETE_MULTI_FRAME_CAPTURE' => 'The camera capture was incomplete. Please retry the face check.',
            'INVALID_LIVE_FRAME', 'LIVE_IMAGE_UNREADABLE' => 'The camera frame could not be read. Please retry the camera scan.',
            'BIOMETRIC_MISMATCH' => 'Your face samples did not match your registered profile photo. Please try again in good lighting.',
            default => 'Face matching is temporarily unavailable. Please use your registered fingerprint or ask your teacher for help.',
        };
    }
}
