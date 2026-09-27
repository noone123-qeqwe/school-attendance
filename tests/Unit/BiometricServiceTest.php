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
}
