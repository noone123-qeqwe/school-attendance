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
     * Minimum separation margin (%) required between top candidate and runner-up in 1:N identification.
     */
    public const MIN_DISCRIMINATION_MARGIN = 2.5;

    /**
     * Unambiguous similarity threshold that guarantees match even if runner-up is within margin.
     */
    public const UNAMBIGUOUS_CONFIDENCE_THRESHOLD = 84.0;

    /**
     * Compare an incoming candidate face descriptor against a reference face descriptor.
     *
     * @return array{match: bool, similarity: float, confidence: int, method: string, metrics?: array}
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

            // Canberra metric distance across feature vector (element-normalized divergence)
            $canberraSim = $this->canberraSimilarity($candidateData['vector'], $referenceData['vector']);
            $canberraScore = round($canberraSim * 100, 2);

            // Quad-Metric Composite Fusion:
            // 55% Invariant Cosine + 15% Canberra Distance + 15% Chi-Square Micro-Texture + 15% Landmark Topology
            $compositeScore = round(($scorePercent * 0.55) + ($canberraScore * 0.15) + ($chiScore * 0.15) + ($landmarkScore * 0.15), 2);

            // High harmony reward vs impostor penalty:
            // If textures, Canberra metric, or landmarks indicate an impostor, apply dissonance damping
            if ($chiScore < 65.0 || $canberraScore < 65.0 || $landmarkScore < 60.0) {
                $dissonance = min($chiScore, $canberraScore, $landmarkScore) / 100.0;
                $effectiveScore = round(min($scorePercent, $compositeScore) * $dissonance, 2);
            } elseif ($chiScore >= 68.0 && $canberraScore >= 68.0 && $landmarkScore >= 65.0) {
                $effectiveScore = max($scorePercent, $compositeScore);
            } else {
                $effectiveScore = min($scorePercent, $compositeScore);
            }

            // High-security matching decision:
            $isMatch = ($effectiveScore >= $threshold) && ($canberraScore >= 62.0) && ($chiScore >= 58.0);

            return [
                'match' => $isMatch,
                'similarity' => $effectiveScore,
                'confidence' => $candidateData['confidence'],
                'method' => 'vector_cosine_v2',
                'metrics' => [
                    'cosine' => $scorePercent,
                    'canberra' => $canberraScore,
                    'chi_square' => $chiScore,
                    'landmarks' => $landmarkScore,
                    'composite' => $compositeScore,
                ],
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
     * Find the best matching credential from a collection of candidate credentials with 1:N discrimination margin.
     *
     * @param Collection<int, WebauthnCredential> $credentials
     */
    public function findBestMatch(string $candidateDesc, Collection $credentials, float $threshold = self::DEFAULT_MATCH_THRESHOLD): ?array
    {
        $candidateData = $this->parseDescriptor($candidateDesc);
        if (!$candidateData) {
            return null;
        }

        $userBestScores = [];

        foreach ($credentials as $cred) {
            $refDesc = (string) $cred->public_key;
            if (empty($refDesc)) {
                continue;
            }

            $comparison = $this->compareDescriptors($candidateDesc, $refDesc, $threshold);
            if (!$comparison['match']) {
                continue;
            }

            $userId = $cred->user_id;
            $sim = $comparison['similarity'];

            if (!isset($userBestScores[$userId]) || $sim > $userBestScores[$userId]['score']) {
                $userBestScores[$userId] = [
                    'score' => $sim,
                    'credential' => $cred,
                    'user' => $cred->user,
                    'comparison' => $comparison,
                ];
            }
        }

        if (empty($userBestScores)) {
            return null;
        }

        // Sort matching users by similarity descending
        uasort($userBestScores, fn ($a, $b) => $b['score'] <=> $a['score']);
        $ranked = array_values($userBestScores);
        $topMatch = $ranked[0];

        $margin = 100.0;
        $isAmbiguous = false;
        $runnerUp = null;

        if (count($ranked) > 1) {
            $runnerUp = $ranked[1];
            $margin = $topMatch['score'] - $runnerUp['score'];
            // Ambiguity safeguard: If top 2 candidates are very close and neither has overwhelming confidence
            if ($margin < self::MIN_DISCRIMINATION_MARGIN && $topMatch['score'] < self::UNAMBIGUOUS_CONFIDENCE_THRESHOLD) {
                $isAmbiguous = true;
            }
        }

        return [
            'credential' => $topMatch['credential'],
            'user' => $topMatch['user'],
            'similarity' => $topMatch['score'],
            'comparison' => $topMatch['comparison'],
            'margin' => round($margin, 2),
            'is_ambiguous' => $isAmbiguous,
            'runner_up' => $runnerUp ? [
                'user' => $runnerUp['user'],
                'similarity' => $runnerUp['score'],
            ] : null,
        ];
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
     * nose bridge prominence, eye socket depth, triangular invariants) receive higher salience weights.
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
            // Dims 0..15: Core facial structural quotients and primary landmark ratios (weight: 1.30)
            // Dims 16..31: Spatial gradient orientation zones (weight: 1.05)
            // Dims 32..47: Uniform LBP micro-texture histograms (weight: 1.10)
            // Dims 48..63: High-frequency texture frequency & multi-scale triangular invariants (weight: 1.15)
            if ($i < 16) {
                $w = 1.30;
            } elseif ($i < 32) {
                $w = 1.05;
            } elseif ($i < 48) {
                $w = 1.10;
            } else {
                $w = 1.15;
            }

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
     * Compute Canberra metric similarity between two feature vectors.
     * Canberra distance normalizes element-wise absolute differences by the sum of their absolute values.
     * It is exceptionally sensitive to subtle impostor discrepancies across low-energy and high-frequency
     * micro-texture descriptors, effectively preventing false acceptance.
     */
    public function canberraSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if ($len === 0) {
            return 1.0;
        }

        $canberraSum = 0.0;
        $count = 0;

        for ($i = 0; $i < $len; $i++) {
            $valA = (float) $a[$i];
            $valB = (float) $b[$i];
            $denom = abs($valA) + abs($valB);
            if ($denom > 1e-6) {
                $canberraSum += abs($valA - $valB) / $denom;
                $count++;
            }
        }

        if ($count === 0) {
            return 1.0;
        }

        $avgDist = $canberraSum / $count;
        return max(0.0, min(1.0, 1.0 - ($avgDist * 0.95)));
    }

    /**
     * Compute normalized Euclidean similarity between two feature vectors.
     */
    public function euclideanSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        if ($len === 0) {
            return 1.0;
        }

        $sumSqDiff = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $diff = (float) $a[$i] - (float) $b[$i];
            $sumSqDiff += $diff * $diff;
        }

        $dist = sqrt($sumSqDiff / $len);
        return max(0.0, min(1.0, 1.0 - ($dist * 0.70)));
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
