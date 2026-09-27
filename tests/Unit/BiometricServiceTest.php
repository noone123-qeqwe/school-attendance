<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\BiometricService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiometricServiceTest extends TestCase
{
    use RefreshDatabase;

    private BiometricService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BiometricService();
    }

    public function test_identical_descriptors_match_with_perfect_similarity(): void
    {
        $desc = 'face_desc_92_125_122_140_118_v2_eyJ2ZWN0b3IiOlsxLDIsM119';
        $result = $this->service->compareDescriptors($desc, $desc);

        $this->assertTrue($result['match']);
        $this->assertEquals(100.0, $result['similarity']);
    }

    public function test_v2_invariant_vectors_calculate_accurate_cosine_similarity(): void
    {
        $vectorA = [0.42, 0.22, 0.23, 0.95, 0.92, 1.15, 0.88, 1.05, 0.35, 0.41, 0.28, 0.52];
        // Vector B is slightly perturbed (same person under warm lighting)
        $vectorB = [0.42, 0.22, 0.23, 0.94, 0.91, 1.14, 0.89, 1.05, 0.36, 0.40, 0.29, 0.51];

        $descA = $this->service->assembleDescriptor(92, $vectorA, ['eyeL' => 125, 'eyeR' => 122, 'nose' => 140, 'mouth' => 118]);
        $descB = $this->service->assembleDescriptor(90, $vectorB, ['eyeL' => 124, 'eyeR' => 121, 'nose' => 139, 'mouth' => 119]);

        $result = $this->service->compareDescriptors($descA, $descB, 75.0);

        $this->assertTrue($result['match']);
        $this->assertGreaterThanOrEqual(98.0, $result['similarity']);
        $this->assertSame('vector_cosine_v2', $result['method']);
    }

    public function test_v2_invariant_vectors_reject_different_persons(): void
    {
        $vectorPerson1 = [0.42, 0.22, 0.23, 0.95, 0.92, 1.15, 0.88, 1.05, 0.8, 0.1, 0.1, 0.1];
        // Vector Person 2 is orthogonal / completely different facial structure
        $vectorPerson2 = [0.10, 0.05, 0.05, 0.20, 0.20, 0.30, 0.10, 0.20, 0.1, 0.8, 0.8, 0.8];

        $desc1 = $this->service->assembleDescriptor(90, $vectorPerson1, ['eyeL' => 125, 'eyeR' => 122, 'nose' => 140, 'mouth' => 118]);
        $desc2 = $this->service->assembleDescriptor(88, $vectorPerson2, ['eyeL' => 80, 'eyeR' => 78, 'nose' => 95, 'mouth' => 70]);

        $result = $this->service->compareDescriptors($desc1, $desc2, 75.0);

        $this->assertFalse($result['match']);
        $this->assertLessThan(75.0, $result['similarity']);
    }

    public function test_adaptive_template_update_blends_vectors_using_ema(): void
    {
        $user = User::factory()->create();
        $initialVector = array_fill(0, 10, 1.0);
        $candidateVector = array_fill(0, 10, 2.0);

        $initialDesc = $this->service->assembleDescriptor(88, $initialVector, ['eyeL' => 120, 'eyeR' => 120, 'nose' => 135, 'mouth' => 110]);
        $candidateDesc = $this->service->assembleDescriptor(92, $candidateVector, ['eyeL' => 122, 'eyeR' => 121, 'nose' => 137, 'mouth' => 112]);

        $cred = WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => 'face_test_cred_1',
            'public_key' => $initialDesc,
            'biometric_type' => 'face',
            'device_name' => 'Device Face',
        ]);

        $updated = $this->service->adaptivelyUpdateTemplate($cred, $candidateDesc);
        $this->assertTrue($updated);

        $cred->refresh();
        $parsed = $this->service->parseDescriptor((string) $cred->public_key);

        $this->assertNotNull($parsed['vector']);
        // 0.82 * 1.0 + 0.18 * 2.0 = 1.18
        $this->assertEqualsWithDelta(1.18, $parsed['vector'][0], 0.01);
    }

    public function test_1_to_n_candidate_matching_identifies_correct_candidate(): void
    {
        $targetUser = User::factory()->create(['name' => 'Target Student']);
        $otherUser = User::factory()->create(['name' => 'Other Student']);

        $targetVector = [0.5, 0.4, 0.3, 0.6, 0.7, 0.8, 0.9, 0.2];
        $targetDesc = $this->service->assembleDescriptor(95, $targetVector, ['eyeL' => 120, 'eyeR' => 120, 'nose' => 135, 'mouth' => 110]);

        $otherVector = [0.1, 0.9, 0.1, 0.1, 0.2, 0.1, 0.1, 0.9];
        $otherDesc = $this->service->assembleDescriptor(90, $otherVector, ['eyeL' => 80, 'eyeR' => 80, 'nose' => 90, 'mouth' => 70]);

        $targetCred = WebauthnCredential::create([
            'user_id' => $targetUser->id,
            'credential_id' => 'face_target_cred',
            'public_key' => $targetDesc,
            'biometric_type' => 'face',
            'device_name' => 'Target Camera',
        ]);

        $otherCred = WebauthnCredential::create([
            'user_id' => $otherUser->id,
            'credential_id' => 'face_other_cred',
            'public_key' => $otherDesc,
            'biometric_type' => 'face',
            'device_name' => 'Other Camera',
        ]);

        $allCreds = collect([$targetCred, $otherCred]);

        // Incoming candidate is very close to target user
        $candidateVector = [0.51, 0.39, 0.31, 0.59, 0.71, 0.81, 0.89, 0.21];
        $candidateDesc = $this->service->assembleDescriptor(92, $candidateVector, ['eyeL' => 120, 'eyeR' => 120, 'nose' => 135, 'mouth' => 110]);

        $best = $this->service->findBestMatch($candidateDesc, $allCreds, 75.0);

        $this->assertNotNull($best);
        $this->assertSame($targetUser->id, $best['user']->id);
        $this->assertGreaterThanOrEqual(95.0, $best['similarity']);
    }

    public function test_invalid_descriptor_fails_gracefully(): void
    {
        $result = $this->service->compareDescriptors('not_a_descriptor', 'invalid');
        $this->assertFalse($result['match']);
        $this->assertSame(0.0, $result['similarity']);
    }

    public function test_48_dimensional_hybrid_vectors_match_with_high_accuracy(): void
    {
        // 16 primary/structural ratios + 16 gradient energies + 16 ULBP texture energies = 48 dims
        $baseVector = array_merge(
            [0.42, 0.22, 0.23, 0.95, 0.92, 1.15, 0.88, 1.05, 0.98, 1.02, 0.95, 1.10, 0.99, 1.01, 1.04, 0.96],
            array_fill(0, 16, 0.25),
            array_fill(0, 16, 0.35)
        );

        // Perturbed candidate (same individual under different ambient lighting and subtle expression)
        $perturbedVector = array_merge(
            [0.42, 0.22, 0.23, 0.94, 0.91, 1.14, 0.89, 1.05, 0.97, 1.02, 0.96, 1.09, 0.98, 1.01, 1.03, 0.97],
            array_fill(0, 16, 0.26),
            array_fill(0, 16, 0.34)
        );

        $descRef = $this->service->assembleDescriptor(95, $baseVector, ['eyeL' => 125, 'eyeR' => 123, 'nose' => 140, 'mouth' => 118]);
        $descCand = $this->service->assembleDescriptor(92, $perturbedVector, ['eyeL' => 124, 'eyeR' => 122, 'nose' => 139, 'mouth' => 119]);

        $result = $this->service->compareDescriptors($descCand, $descRef, 75.0);

        $this->assertTrue($result['match']);
        $this->assertGreaterThanOrEqual(95.0, $result['similarity']);
        $this->assertSame('vector_cosine_v2', $result['method']);
    }

    public function test_chi_square_and_salience_weighting_reject_subtle_impostor(): void
    {
        // Person 1 (legitimate enrollment)
        $person1Vector = array_merge(
            [0.42, 0.22, 0.23, 0.95, 0.92, 1.15, 0.88, 1.05, 0.98, 1.02, 0.95, 1.10, 0.99, 1.01, 1.04, 0.96],
            [0.1, 0.8, 0.1, 0.8, 0.2, 0.7, 0.1, 0.9, 0.2, 0.8, 0.1, 0.7, 0.2, 0.8, 0.1, 0.9],
            [0.9, 0.1, 0.8, 0.2, 0.9, 0.1, 0.8, 0.2, 0.9, 0.1, 0.8, 0.2, 0.9, 0.1, 0.8, 0.2]
        );

        // Person 2 (different person with completely different micro-texture and gradient distribution)
        $person2Vector = array_merge(
            [0.35, 0.18, 0.28, 0.70, 0.65, 0.85, 0.60, 0.80, 0.80, 0.85, 0.75, 0.90, 0.82, 0.84, 0.88, 0.79],
            [0.8, 0.1, 0.8, 0.1, 0.7, 0.2, 0.9, 0.1, 0.8, 0.2, 0.7, 0.1, 0.8, 0.2, 0.9, 0.1],
            [0.1, 0.9, 0.2, 0.8, 0.1, 0.9, 0.2, 0.8, 0.1, 0.9, 0.2, 0.8, 0.1, 0.9, 0.2, 0.8]
        );

        $desc1 = $this->service->assembleDescriptor(92, $person1Vector, ['eyeL' => 125, 'eyeR' => 123, 'nose' => 140, 'mouth' => 118]);
        $desc2 = $this->service->assembleDescriptor(90, $person2Vector, ['eyeL' => 85, 'eyeR' => 82, 'nose' => 95, 'mouth' => 75]);

        $result = $this->service->compareDescriptors($desc1, $desc2, 75.0);

        $this->assertFalse($result['match']);
        $this->assertLessThan(75.0, $result['similarity']);
    }

    public function test_cross_dimensional_compatibility_between_48_and_legacy_24(): void
    {
        $prefix = [0.42, 0.22, 0.23, 0.95, 0.92, 1.15, 0.88, 1.05];
        $legacyGradients = array_fill(0, 16, 0.25);
        $legacy24Vector = array_merge($prefix, $legacyGradients);

        // 48-dim vector with matching prefix and additional dimensions
        $modern48Vector = array_merge(
            $prefix,
            $legacyGradients,
            [0.98, 1.02, 0.95, 1.10, 0.99, 1.01, 1.04, 0.96],
            array_fill(0, 16, 0.35)
        );

        $desc24 = $this->service->assembleDescriptor(90, $legacy24Vector, ['eyeL' => 120, 'eyeR' => 120, 'nose' => 135, 'mouth' => 110]);
        $desc48 = $this->service->assembleDescriptor(92, $modern48Vector, ['eyeL' => 120, 'eyeR' => 120, 'nose' => 135, 'mouth' => 110]);

        $result = $this->service->compareDescriptors($desc48, $desc24, 75.0);

        $this->assertTrue($result['match']);
        $this->assertGreaterThanOrEqual(95.0, $result['similarity']);
    }

    public function test_lighting_invariant_vectors_survive_illumination_perturbations(): void
    {
        // Normal lighting vector
        $normalVector = array_merge(
            [0.42, 0.22, 0.23, 0.95, 0.92, 1.15, 0.88, 1.05, 0.98, 1.02, 0.95, 1.10, 0.99, 1.01, 1.04, 0.96],
            [0.25, 0.24, 0.26, 0.25, 0.24, 0.25, 0.25, 0.26, 0.25, 0.24, 0.25, 0.26, 0.24, 0.25, 0.26, 0.25],
            [0.35, 0.36, 0.34, 0.35, 0.36, 0.35, 0.34, 0.35, 0.36, 0.35, 0.34, 0.35, 0.36, 0.35, 0.34, 0.35]
        );

        // Perturbed vector simulating strong side-lighting and underexposure (shadow shifts on cheeks)
        $perturbedVector = array_merge(
            [0.42, 0.22, 0.23, 0.93, 0.90, 1.13, 0.87, 1.04, 0.96, 1.01, 0.94, 1.08, 0.97, 1.00, 1.02, 0.95],
            [0.26, 0.23, 0.27, 0.24, 0.25, 0.24, 0.26, 0.25, 0.26, 0.23, 0.26, 0.25, 0.25, 0.24, 0.27, 0.24],
            [0.35, 0.36, 0.34, 0.35, 0.36, 0.35, 0.34, 0.35, 0.36, 0.35, 0.34, 0.35, 0.36, 0.35, 0.34, 0.35]
        );

        $descNormal = $this->service->assembleDescriptor(95, $normalVector, ['eyeL' => 120, 'eyeR' => 120, 'nose' => 135, 'mouth' => 110]);
        $descPerturbed = $this->service->assembleDescriptor(90, $perturbedVector, ['eyeL' => 118, 'eyeR' => 122, 'nose' => 134, 'mouth' => 109]);

        $result = $this->service->compareDescriptors($descNormal, $descPerturbed, 75.0);

        $this->assertTrue($result['match']);
        $this->assertGreaterThanOrEqual(92.0, $result['similarity']);
    }
}
