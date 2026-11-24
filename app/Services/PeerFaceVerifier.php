<?php

namespace App\Services;

use App\Models\PeerVouchRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Process\Process;

/** Verifies classmate live frames using either a configured HTTPS microservice or local OpenCV models. */
class PeerFaceVerifier
{
    public function isAvailable(): bool
    {
        return $this->usesExternalVerifier() || $this->usesLocalVerifier();
    }

    public function usesExternalVerifier(): bool
    {
        return str_starts_with((string) config('peer_snap.verifier_url'), 'https://')
            && (bool) config('peer_snap.verifier_token')
            && (bool) config('peer_snap.model_version')
            && (float) config('peer_snap.match_threshold') > 0
            && (float) config('peer_snap.match_threshold') <= 1
            && (float) config('peer_snap.pad_threshold') > 0
            && (float) config('peer_snap.pad_threshold') <= 1;
    }

    public function usesLocalVerifier(): bool
    {
        if ($this->usesExternalVerifier()) {
            return false;
        }

        $localThreshold = (float) config('peer_snap.local_match_threshold', config('face_match.threshold', 0.363));
        $padThreshold = (float) config('peer_snap.pad_threshold', 0.90);

        return $localThreshold > 0 && $localThreshold <= 1
            && $padThreshold > 0.5 && $padThreshold <= 1
            && is_file((string) config('face_match.detector_model'))
            && is_file((string) config('face_match.recognizer_model'))
            && is_file((string) config('face_match.attendance_pad_model'));
    }

    public function activeModelVersion(): string
    {
        if ($this->usesExternalVerifier()) {
            return (string) config('peer_snap.model_version');
        }

        return (string) config('peer_snap.local_model_version', 'opencv_yunet_sface_v1');
    }

    /** @param UploadedFile[] $frames */
    public function verify(PeerVouchRequest $attempt, string $nonce, string $subjectRef, array $frames): array
    {
        if ($this->usesExternalVerifier()) {
            return $this->verifyExternally($attempt, $nonce, $subjectRef, $frames);
        }

        if ($this->usesLocalVerifier()) {
            return $this->verifyLocally($attempt, $frames);
        }

        throw new RuntimeException('Peer face verifier is not configured.');
    }

    /** @param UploadedFile[] $frames */
    private function verifyExternally(PeerVouchRequest $attempt, string $nonce, string $subjectRef, array $frames): array
    {
        $url = (string) config('peer_snap.verifier_url');
        $token = (string) config('peer_snap.verifier_token');
        $model = (string) config('peer_snap.model_version');
        if (!str_starts_with($url, 'https://') || !$token || !$model) {
            throw new RuntimeException('Peer face verifier is not configured.');
        }

        $request = Http::withToken($token)->acceptJson()->timeout(12)
            ->withOptions(['allow_redirects' => false])
            ->withHeaders(['X-Peer-Verification-Id' => $attempt->verification_id]);
        foreach ($frames as $index => $frame) {
            $request = $request->attach('frames['.$index.']', $frame->get(), 'frame-'.$index.'.jpg', ['Content-Type' => 'image/jpeg']);
        }

        $response = $request->post($url, [
            'verification_id' => $attempt->verification_id,
            'nonce' => $nonce,
            'challenge' => $attempt->challenge,
            'subject_ref' => $subjectRef,
            'model_version' => $model,
        ]);

        if (!$response->successful() || !is_array($response->json())) {
            throw new RuntimeException('Peer face verifier failed.');
        }

        $result = $response->json();
        foreach (['verification_id' => $attempt->verification_id, 'nonce' => $nonce,
                  'challenge' => $attempt->challenge, 'subject_ref' => $subjectRef,
                  'model_version' => $model] as $key => $expected) {
            if (!isset($result[$key]) || !hash_equals($expected, (string) $result[$key])) {
                throw new RuntimeException('Peer face verifier binding failed.');
            }
        }

        if (!isset($result['frames']) || !is_array($result['frames']) || count($result['frames']) !== count($frames)) {
            throw new RuntimeException('Peer face verifier frame evidence missing.');
        }

        foreach ($result['frames'] as $frame) {
            if (!is_array($frame) || ($frame['face_count'] ?? null) !== 1) {
                return ['accepted' => false, 'reason' => 'face_count'];
            }
            if (($frame['quality_passed'] ?? null) !== true) {
                return ['accepted' => false, 'reason' => 'image_quality'];
            }
            if (!is_numeric($frame['pad_score'] ?? null) || (float) $frame['pad_score'] > 1
                || (float) $frame['pad_score'] < config('peer_snap.pad_threshold')) {
                return ['accepted' => false, 'reason' => 'liveness'];
            }
        }

        if (($result['challenge_passed'] ?? null) !== true) {
            return ['accepted' => false, 'reason' => 'liveness'];
        }
        if (!is_numeric($result['similarity'] ?? null) || (float) $result['similarity'] > 1
            || (float) $result['similarity'] < config('peer_snap.match_threshold')) {
            return ['accepted' => false, 'reason' => 'face_mismatch'];
        }

        return ['accepted' => true, 'reason' => null];
    }

    /** @param UploadedFile[] $frames */
    private function verifyLocally(PeerVouchRequest $attempt, array $frames): array
    {
        $subject = User::find($attempt->subject_student_id);
        if (!$subject) {
            return ['accepted' => false, 'reason' => 'face_mismatch'];
        }

        $references = app(ProfileFacePhotoService::class)->cameraReferences($subject);
        if ($references === []) {
            throw new RuntimeException('Subject camera face references unavailable.');
        }

        $dir = storage_path('app/private/face-match-tmp/' . bin2hex(random_bytes(16)));
        if (!@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to prepare temporary verification directory.');
        }

        try {
            $refPaths = [];
            foreach ($references as $index => $bytes) {
                $path = $dir . '/ref-' . $index . '.jpg';
                file_put_contents($path, $bytes);
                @chmod($path, 0600);
                $refPaths[] = $path;
            }
            $manifestPath = $dir . '/references.json';
            file_put_contents($manifestPath, json_encode($refPaths));
            @chmod($manifestPath, 0600);

            $framePaths = [];
            foreach ($frames as $index => $frame) {
                $path = $dir . '/live-' . $index . '.jpg';
                file_put_contents($path, $frame->get());
                @chmod($path, 0600);
                $framePaths[] = $path;
            }

            $threshold = (string) config('peer_snap.local_match_threshold', config('face_match.threshold', 0.363));
            $padThreshold = (string) config('peer_snap.pad_threshold', 0.90);

            $process = new Process([
                (string) config('face_match.python'),
                base_path('scripts/match_profile_face.py'),
                '--peer-snap',
                (string) $attempt->challenge,
                (string) config('face_match.attendance_pad_model'),
                $padThreshold,
                '--reference-set',
                $manifestPath,
                ...$framePaths,
                (string) config('face_match.detector_model'),
                (string) config('face_match.recognizer_model'),
                $threshold,
            ]);
            $process->setTimeout(25);
            $process->run(null, ['OMP_NUM_THREADS' => '1', 'OPENBLAS_NUM_THREADS' => '1']);

            $result = json_decode(trim($process->getOutput()), true);
            if (!$process->isSuccessful() || !is_array($result) || !is_bool($result['match'] ?? null)) {
                throw new RuntimeException('Local peer face matcher failed.');
            }

            $code = (string) ($result['code'] ?? 'MATCHER_UNAVAILABLE');
            if ($code === 'MATCHER_UNAVAILABLE' || str_starts_with($code, 'PROFILE_')) {
                throw new RuntimeException('Local peer face matcher unavailable: ' . $code);
            }

            if ($result['match'] === true && $code === 'MATCH' && ($result['liveness_passed'] ?? false) === true) {
                return ['accepted' => true, 'reason' => null];
            }

            $reason = match ($code) {
                'LIVE_FACE_NOT_FOUND', 'LIVE_MULTIPLE_FACES' => 'face_count',
                'LIVE_IMAGE_UNREADABLE', 'LIVE_IMAGE_QUALITY', 'LIVE_FACE_TOO_SMALL',
                'LIVE_IMAGE_BLURRY', 'LIVE_IMAGE_LIGHTING', 'LIVE_FACE_POSITION',
                'INCOMPLETE_MULTI_FRAME_CAPTURE' => 'image_quality',
                'LIVENESS_FAILED', 'LIVENESS_UNCERTAIN', 'LIVE_POSE_MISMATCH', 'LIVE_FRAME_INCONSISTENT' => 'liveness',
                default => 'face_mismatch',
            };

            return ['accepted' => false, 'reason' => $reason];
        } finally {
            foreach (glob($dir . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($dir);
        }
    }
}
