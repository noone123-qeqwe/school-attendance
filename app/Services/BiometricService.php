<?php

namespace App\Services;

use App\Models\WebauthnCredential;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BiometricService
{
    /**
     * Default similarity threshold (0 - 100) for confirming identity.
     */
    public const DEFAULT_MATCH_THRESHOLD = 75.0;

    /**
     * High confidence threshold for automatic template refinement.
     */
    public const TEMPLATE_UPDATE_THRESHOLD = 88.0;

    /**
     * Compare an incoming candidate face descriptor against a reference face descriptor.
     *
     * @return array{match: bool, similarity: float, confidence: int, method: string}
     */
    public function compareDescriptors(string $candidateDesc, string $referenceDesc, float $threshold = self::DEFAULT_MATCH_THRESHOLD): array
    {
        // O(1) Fast-path: Identical descriptor strings (e.g. repeated token or mock test)
        if ($candidateDesc !== '' && $candidateDesc === $referenceDesc) {
            $parsed = $this->parseDescriptor($candidateDesc);
            return [
                'match' => true,
                'similarity' => 100.0,
                'confidence' => $parsed['confidence'] ?? 95,
                'method' => 'exact_descriptor_match',
            ];
        }

        $candidateData = $this->parseDescriptor($candidateDesc);
        $referenceData = $this->parseDescriptor($referenceDesc);

        // If either descriptor could not be parsed, fallback to structural comparison
        if (!$candidateData || !$referenceData) {
            return [
                'match' => false,
                'similarity' => 0.0,
                'confidence' => 0,
                'method' => 'invalid_descriptor',
            ];
        }

        // Mode 1: High-precision v2 invariant vectors present in both
        if (!empty($candidateData['vector']) && !empty($referenceData['vector'])) {
            $rawCosSim = $this->cosineSimilarity($candidateData['vector'], $referenceData['vector']);
            $weightedCosSim = $this->weightedCosineSimilarity($candidateData['vector'], $referenceData['vector']);
            $cosSim = max($rawCosSim, $weightedCosSim);
            $scorePercent = round($cosSim * 100, 2);

            // Cross-validate with illumination-invariant landmark topology
            $landmarkSim = $this->compareLandmarks($candidateData, $referenceData);
            $landmarkScore = round($landmarkSim * 100, 2);

            // Chi-square texture matching across spatial gradient and LBP blocks (offset 8 to skip scalar aspect ratios)
            $chiSim = $this->chiSquareSimilarity($candidateData['vector'], $referenceData['vector'], 8);
            $chiScore = round($chiSim * 100, 2);

            // Multi-Metric Composite Fusion:
            // 65% Cosine Invariant Vector + 20% Chi-Square Micro-Texture + 15% Landmark Topology
            $compositeScore = round(($scorePercent * 0.65) + ($chiScore * 0.20) + ($landmarkScore * 0.15), 2);

            // If textures and landmarks strongly agree, reward composite;
            // If micro-texture or landmarks indicate an impostor, enforce the composite penalty
            $effectiveScore = ($chiScore >= 70.0 && $landmarkScore >= 65.0)
                ? max($scorePercent, $compositeScore)
                : min($scorePercent, $compositeScore);

            $isMatch = ($scorePercent >= $threshold && $chiScore >= 60.0) || ($compositeScore >= $threshold && $scorePercent >= ($threshold - 4.0));

            return [
                'match' => $isMatch,
                'similarity' => $effectiveScore,
                'confidence' => $candidateData['confidence'],
                'method' => 'vector_cosine_v2',
            ];
        }

        // Mode 2: Hybrid comparison between v2 and legacy or between two legacy descriptors
        $similarity = $this->compareLandmarks($candidateData, $referenceData);
        $scorePercent = round($similarity * 100, 2);

        return [
            'match' => $scorePercent >= max(65.0, $threshold - 5.0),
            'similarity' => $scorePercent,
            'confidence' => $candidateData['confidence'],
            'method' => 'landmark_topology',
        ];
    }

    /**
     * Find the best matching credential from a collection of candidate credentials.
     *
     * @param Collection<int, WebauthnCredential> $credentials
     */
    public function findBestMatch(string $candidateDesc, Collection $credentials, float $threshold = self::DEFAULT_MATCH_THRESHOLD): ?array
    {
        $bestMatch = null;
        $highestSimilarity = 0.0;

        foreach ($credentials as $cred) {
            $refDesc = (string) $cred->public_key;
            if (empty($refDesc)) {
                continue;
            }

            $comparison = $this->compareDescriptors($candidateDesc, $refDesc, $threshold);
            if ($comparison['match'] && $comparison['similarity'] > $highestSimilarity) {
                $highestSimilarity = $comparison['similarity'];
                $bestMatch = [
                    'credential' => $cred,
                    'user' => $cred->user,
                    'similarity' => $comparison['similarity'],
                    'comparison' => $comparison,
                ];
            }
        }

        return $bestMatch;
    }

    /**
     * Smoothly update the registered biometric template with high-confidence candidate features.
     * Implements Exponential Moving Average (EMA) to prevent template drift while adapting
     * to lighting and subtle environmental variances.
     */
    public function adaptivelyUpdateTemplate(WebauthnCredential $credential, string $candidateDesc): bool
    {
        $candidateData = $this->parseDescriptor($candidateDesc);
        $referenceData = $this->parseDescriptor((string) $credential->public_key);

        if (!$candidateData || !$referenceData) {
            return false;
        }

        if (empty($candidateData['vector']) || empty($referenceData['vector'])) {
            // Upgrade legacy credential to v2 format if candidate has v2 vector
            if (!empty($candidateData['vector'])) {
                $credential->update([
                    'public_key' => $candidateDesc,
                    'last_used_at' => now(),
                ]);
                return true;
            }
            return false;
        }

        // EMA weighting: 82% reference anchor, 18% new sample
        $vCand = $candidateData['vector'];
        $vRef = $referenceData['vector'];
        $count = max(count($vCand), count($vRef));
        $blended = [];

        for ($i = 0; $i < $count; $i++) {
            if (isset($vRef[$i]) && isset($vCand[$i])) {
                $blended[] = round(0.82 * $vRef[$i] + 0.18 * $vCand[$i], 4);
            } elseif (isset($vCand[$i])) {
                $blended[] = round($vCand[$i], 4);
            } else {
                $blended[] = round($vRef[$i], 4);
            }
        }

        $newScore = max($candidateData['confidence'], $referenceData['confidence']);
        $newDescriptor = $this->assembleDescriptor($newScore, $blended, $candidateData['landmarks']);

        $credential->update([
            'public_key' => $newDescriptor,
            'last_used_at' => now(),
        ]);

        return true;
    }

    /**
     * Parse structured or legacy descriptor strings into numeric data.
     */
    public function parseDescriptor(string $desc): ?array
    {
        $raw = trim($desc);
        if ($raw === '' || strlen($raw) < 8) {
            return null;
        }

        // Parse format: face_desc_{score}_{eyeL}_{eyeR}_{nose}_{mouth}[_v2_{vector}]
        if (preg_match('/^face_desc_(\d+)(?:_(\d+)_(\d+)_(\d+)_(\d+))?(?:_v2_([A-Za-z0-9+\/=\-_]+))?/', $raw, $matches)) {
            $confidence = (int) $matches[1];
            $landmarks = [
                'eyeL' => isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 120,
                'eyeR' => isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : 120,
                'nose' => isset($matches[4]) && $matches[4] !== '' ? (int) $matches[4] : 135,
                'mouth' => isset($matches[5]) && $matches[5] !== '' ? (int) $matches[5] : 110,
            ];

            $vector = null;
            if (!empty($matches[6])) {
                $b64 = strtr($matches[6], '-_', '+/');
                $mod4 = strlen($b64) % 4;
                if ($mod4 > 0) {
                    $b64 .= substr('====', $mod4);
                }
                $decoded = base64_decode($b64, true);
                if ($decoded) {
                    $json = json_decode($decoded, true);
                    if (is_array($json)) {
                        $rawVector = isset($json['v']) && is_array($json['v']) ? $json['v'] : $json;
                        $vector = array_map('floatval', $rawVector);
                    }
                }
            }

            return [
                'confidence' => $confidence,
                'landmarks' => $landmarks,
                'vector' => $vector,
                'raw' => $raw,
            ];
        }

        // Direct v2 format: face_v2_{score}_{base64}
        if (preg_match('/^face_v2_(\d+)_([A-Za-z0-9+\/=\-_]+)/', $raw, $matches)) {
            $confidence = (int) $matches[1];
            $b64 = strtr($matches[2], '-_', '+/');
            $mod4 = strlen($b64) % 4;
            if ($mod4 > 0) {
                $b64 .= substr('====', $mod4);
            }
            $decoded = base64_decode($b64, true);
            $vector = null;
            $landmarks = ['eyeL' => 120, 'eyeR' => 120, 'nose' => 135, 'mouth' => 110];

            if ($decoded) {
                $payload = json_decode($decoded, true);
                if (is_array($payload)) {
                    $vector = $payload['v'] ?? $payload;
                    if (isset($payload['l']) && is_array($payload['l'])) {
                        $landmarks = $payload['l'];
                    }
                }
            }

            return [
                'confidence' => $confidence,
                'landmarks' => $landmarks,
                'vector' => is_array($vector) ? array_map('floatval', $vector) : null,
                'raw' => $raw,
            ];
        }

        // Fallback for legacy test strings (e.g., face_desc_90_fake, face_desc_85_sample_vector_hash_xyz)
        if (preg_match('/^face_desc_(\d+)/', $raw, $m)) {
            return [
                'confidence' => (int) $m[1],
                'landmarks' => ['eyeL' => 120, 'eyeR' => 120, 'nose' => 135, 'mouth' => 110],
                'vector' => null,
                'raw' => $raw,
            ];
        }

        return null;
    }

    /**
     * Compute cosine similarity between two numeric feature vectors.
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if ($len === 0) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $len; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        $similarity = $dotProduct / (sqrt($normA) * sqrt($normB));
        return max(0.0, min(1.0, (float) $similarity));
    }

    /**
     * Compute salience-weighted cosine similarity.
     * Dimensions corresponding to rigid geometric landmarks (inter-ocular distance,
     * nose bridge prominence, eye socket depth) receive higher salience weights.
     */
    public function weightedCosineSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if ($len === 0) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $len; $i++) {
            // First 12 dimensions: structural ratios (higher salience weight: 1.25)
            // Next dimensions: gradient & LBP micro-texture (salience weight: 1.0)
            $w = ($i < 12) ? 1.25 : 1.0;
            $valA = $a[$i] * $w;
            $valB = $b[$i] * $w;

            $dotProduct += $valA * $valB;
            $normA += $valA * $valA;
            $normB += $valB * $valB;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        $similarity = $dotProduct / (sqrt($normA) * sqrt($normB));
        return max(0.0, min(1.0, (float) $similarity));
    }

    /**
     * Compute Chi-Square similarity between normalized feature sub-vectors.
     * Chi-Square is exceptionally effective for histogram and texture features (LBP, gradient energy),
     * providing strong rejection of impostor candidates with different micro-texture.
     */
    public function chiSquareSimilarity(array $a, array $b, int $offset = 0): float
    {
        $len = min(count($a), count($b));
        if ($len <= $offset) {
            return 1.0;
        }

        $chiDist = 0.0;
        $elementsCount = 0;

        for ($i = $offset; $i < $len; $i++) {
            $valA = (float) $a[$i];
            $valB = (float) $b[$i];
            $sum = abs($valA) + abs($valB);
            if ($sum > 1e-6) {
                $diff = $valA - $valB;
                $chiDist += ($diff * $diff) / $sum;
                $elementsCount++;
            }
        }

        if ($elementsCount === 0) {
            return 1.0;
        }

        $normalizedDist = $chiDist / max(1.0, (float) $elementsCount);
        // Map distance to similarity [0, 1]
        return max(0.0, min(1.0, 1.0 - ($normalizedDist * 0.85)));
    }

    /**
     * Topological comparison based on lighting-invariant facial landmark ratios.
     */
    protected function compareLandmarks(array $candidate, array $reference): float
    {
        $cL = $candidate['landmarks'];
        $rL = $reference['landmarks'];

        // Compute relative contrast ratios (invariant to uniform illumination changes)
        $cEyeSym = abs($cL['eyeL'] - $cL['eyeR']) / max(1, ($cL['eyeL'] + $cL['eyeR']) / 2);
        $rEyeSym = abs($rL['eyeL'] - $rL['eyeR']) / max(1, ($rL['eyeL'] + $rL['eyeR']) / 2);
        $diffEyeSym = 1.0 - min(1.0, abs($cEyeSym - $rEyeSym) * 2.0);

        $cNoseEye = $cL['nose'] / max(1, ($cL['eyeL'] + $cL['eyeR']) / 2);
        $rNoseEye = $rL['nose'] / max(1, ($rL['eyeL'] + $rL['eyeR']) / 2);
        $diffNoseEye = 1.0 - min(1.0, abs($cNoseEye - $rNoseEye));

        $cMouthEye = $cL['mouth'] / max(1, ($cL['eyeL'] + $cL['eyeR']) / 2);
        $rMouthEye = $rL['mouth'] / max(1, ($rL['eyeL'] + $rL['eyeR']) / 2);
        $diffMouthEye = 1.0 - min(1.0, abs($cMouthEye - $rMouthEye));

        // Confidence harmony
        $confRatio = min($candidate['confidence'], $reference['confidence']) / max(1, max($candidate['confidence'], $reference['confidence']));

        $composite = ($diffEyeSym * 0.35) + ($diffNoseEye * 0.35) + ($diffMouthEye * 0.20) + ($confRatio * 0.10);
        return max(0.0, min(1.0, $composite));
    }

    /**
     * Assemble an invariant descriptor string.
     */
    public function assembleDescriptor(int $confidence, array $vector, array $landmarks): string
    {
        $vectorPayload = rtrim(strtr(base64_encode(json_encode($vector)), '+/', '-_'), '=');
        $l = $landmarks;
        return sprintf(
            'face_desc_%d_%d_%d_%d_%d_v2_%s',
            $confidence,
            $l['eyeL'] ?? 120,
            $l['eyeR'] ?? 120,
            $l['nose'] ?? 135,
            $l['mouth'] ?? 110,
            $vectorPayload
        );
    }
}
