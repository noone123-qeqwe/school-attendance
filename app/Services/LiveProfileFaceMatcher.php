<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class LiveProfileFaceMatcher
{
    public function compare(User $user, ?string $liveFrame): array
    {
        return $this->compareFrameSet($user, [$liveFrame], false);
    }

    /** Compare two or three controlled camera samples against the encrypted enrollment. */
    public function compareFrames(User $user, array $liveFrames): array
    {
        if (count($liveFrames) < 2 || count($liveFrames) > 3) {
            return $this->failure('INCOMPLETE_MULTI_FRAME_CAPTURE');
        }

        return $this->compareFrameSet($user, $liveFrames, true);
    }

    public function attendanceReady(): bool
    {
        $threshold = filter_var(config('face_match.attendance_pad_threshold'), FILTER_VALIDATE_FLOAT);
        return $threshold !== false && is_finite($threshold) && $threshold > 0.5 && $threshold <= 1
            && is_readable(config('face_match.attendance_pad_model'))
            && is_readable(config('face_match.detector_model'))
            && is_readable(config('face_match.recognizer_model'));
    }

    public function verifyAttendance(User $user, array $frames, array $poses): array
    {
        if (!$this->attendanceReady() || count($frames) !== FaceAttendanceChallenge::FRAME_COUNT) {
            return $this->failure('MATCHER_UNAVAILABLE');
        }

        return $this->compareFrameSet($user, $frames, true, $poses);
    }

    private function compareFrameSet(User $user, array $liveFrames, bool $requireConsistency, ?array $poses = null): array
    {
        $photos = app(ProfileFacePhotoService::class);
        $references = $poses === null ? $photos->referenceImages($user) : $photos->cameraReferences($user);
        if ($references === []) {
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
        if ($poses !== null && !$this->freshEvidence($user, $liveBytes)) {
            return $this->failure('LIVENESS_FAILED');
        }

        $threshold = filter_var(config('face_match.threshold'), FILTER_VALIDATE_FLOAT);
        if ($threshold === false || !is_finite((float) $threshold) || $threshold <= 0 || $threshold > 1) {
            return $this->failure('MATCHER_UNAVAILABLE');
        }

        $temporaryPaths = [];
        try {
            $referencePaths = [];
            foreach ($references as $reference) {
                $path = tempnam(sys_get_temp_dir(), 'face_ref_');
                if ($path === false) return $this->failure('MATCHER_UNAVAILABLE');
                $temporaryPaths[] = $path;
                $referencePaths[] = $path;
                if (file_put_contents($path, $reference) !== strlen($reference)) return $this->failure('MATCHER_UNAVAILABLE');
            }
            $referencePath = $referencePaths[0];
            $referenceOptions = [];
            if (count($referencePaths) > 1) {
                $referencePath = tempnam(sys_get_temp_dir(), 'face_refs_');
                if ($referencePath === false) return $this->failure('MATCHER_UNAVAILABLE');
                $temporaryPaths[] = $referencePath;
                $manifest = json_encode($referencePaths, JSON_THROW_ON_ERROR);
                if (file_put_contents($referencePath, $manifest) !== strlen($manifest)) return $this->failure('MATCHER_UNAVAILABLE');
                $referenceOptions = ['--reference-set'];
            }

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

            $attendanceOptions = $poses === null ? [] : [
                '--attendance', implode(',', $poses), config('face_match.attendance_pad_model'),
                (string) config('face_match.attendance_pad_threshold'),
            ];
            $process = new Process([
                config('face_match.python'),
                base_path('scripts/match_profile_face.py'),
                ...$attendanceOptions,
                ...$referenceOptions,
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
                'LIVE_IMAGE_LIGHTING', 'LIVE_FACE_POSITION', 'LIVE_POSE_MISMATCH',
                'LIVENESS_FAILED', 'LIVENESS_UNCERTAIN', 'IDENTITY_UNCERTAIN',
            ];
            if (!in_array($code, $allowedCodes, true)) {
                return $this->failure('MATCHER_UNAVAILABLE');
            }

            $similarity = (float) $similarity;
            $frameConsistent = ($result['frame_consistent'] ?? ($poses === null)) === true;
            $livenessPassed = $poses !== null && ($result['liveness_passed'] ?? null) === true
                && ($result['frame_count'] ?? null) === FaceAttendanceChallenge::FRAME_COUNT;
            $match = $result['match'] && $code === 'MATCH' && $similarity >= $threshold
                && (!$requireConsistency || $frameConsistent) && ($poses === null || $livenessPassed);
            if ($code === 'MATCH' && !$match) {
                $code = 'BIOMETRIC_MISMATCH';
            }

            return [
                'match' => $match,
                'code' => $code,
                'similarity' => $similarity,
                'method' => 'opencv_yunet_sface',
                'frame_count' => count($liveFrames),
                'liveness_passed' => $livenessPassed,
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

    private function freshEvidence(User $user, array $frames): bool
    {
        $hashes = array_map(fn (string $bytes) => hash('sha256', $bytes), $frames);
        if (count(array_unique($hashes)) !== count($hashes)) {
            return false;
        }

        // Exact repeats are rejected even if the submitted frame order changes.
        // Store only a short-lived, keyed digest, never a frame or face embedding.
        sort($hashes, SORT_STRING);
        $key = hash_hmac('sha256', $user->id.':'.implode('', $hashes), (string) config('app.key'));

        return Cache::add('qr_face_evidence:'.$key, true, 600);
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
        if (!is_array($size) || !in_array($size['mime'] ?? null, ['image/jpeg', 'image/png', 'image/webp'], true)
            || min($size[0], $size[1]) < 100 || max($size[0], $size[1]) > 2000) {
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
            'liveness_passed' => false,
            'message' => $this->message($code),
        ];
    }

    private function message(string $code): string
    {
        return match ($code) {
            'MATCH' => 'Face samples matched your registered face profile.',
            'NO_PROFILE_PHOTO', 'PROFILE_IMAGE_UNREADABLE', 'PROFILE_FACE_NOT_FOUND' =>
                'Your face profile is unavailable or has no clear face. Register your face again in Settings > Biometrics > Face registration.',
            'PROFILE_MULTIPLE_FACES', 'PROFILE_FACE_TOO_SMALL', 'PROFILE_IMAGE_BLURRY', 'PROFILE_IMAGE_QUALITY' =>
                'Your face profile needs clear camera captures. Register your face again in Settings > Biometrics > Face registration.',
            'LIVE_FACE_NOT_FOUND', 'LIVE_FACE_TOO_SMALL', 'LIVE_IMAGE_BLURRY', 'LIVE_IMAGE_QUALITY' =>
                'Move closer to the camera and keep your face clear and steady, then try again.',
            'LIVE_MULTIPLE_FACES' => 'Only one person can be in the camera frame. Please try again.',
            'LIVE_FRAME_INCONSISTENT' => 'Your face moved between the camera samples. Keep your face steady and retry.',
            'LIVE_IMAGE_LIGHTING' => 'Use even light from the front. Avoid bright lights behind you and try again.',
            'LIVE_FACE_POSITION' => 'Keep your face inside the oval and try again.',
            'LIVE_POSE_MISMATCH' => 'The requested head movements could not be verified. Follow the directions and try again.',
            'LIVENESS_FAILED' => 'Liveness could not be verified. Use the live camera, device verification, or ask your teacher for help.',
            'LIVENESS_UNCERTAIN', 'IDENTITY_UNCERTAIN' =>
                'The camera check was inconclusive. Try again in even light, use device verification, or ask your teacher for help.',
            'INCOMPLETE_MULTI_FRAME_CAPTURE' => 'The camera capture was incomplete. Please retry the face check.',
            'INVALID_LIVE_FRAME', 'LIVE_IMAGE_UNREADABLE' => 'The camera frame could not be read. Please retry the camera scan.',
            'BIOMETRIC_MISMATCH' => 'Your face samples did not match your registered face profile. Please try again in good lighting.',
            default => 'Face matching is temporarily unavailable. Please use your registered fingerprint or ask your teacher for help.',
        };
    }
}
