<?php

namespace App\Services;

use App\Models\PeerVouchRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** A strict adapter; the verifier holds enrolled templates and must never retain frames. */
class PeerFaceVerifier
{
    /** @param UploadedFile[] $frames */
    public function verify(PeerVouchRequest $attempt, string $nonce, string $subjectRef, array $frames): array
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
}
