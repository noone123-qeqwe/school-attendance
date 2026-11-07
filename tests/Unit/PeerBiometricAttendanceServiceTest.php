<?php

namespace Tests\Unit;

use App\Policies\PeerVouchPolicy;
use App\Services\PeerBiometricAttendanceService;
use App\Services\PeerFaceVerifier;
use Mockery;
use Tests\TestCase;

class PeerBiometricAttendanceServiceTest extends TestCase
{
    public function test_peer_verification_fails_closed_without_a_configured_https_verifier(): void
    {
        config([
            'peer_snap.enabled' => true,
            'peer_snap.verifier_url' => 'http://verifier.example.test',
            'peer_snap.verifier_token' => 'configured-token',
            'peer_snap.model_version' => 'test-model',
            'peer_snap.match_threshold' => 0.85,
            'peer_snap.pad_threshold' => 0.90,
        ]);
        $service = new PeerBiometricAttendanceService(
            Mockery::mock(PeerVouchPolicy::class),
            Mockery::mock(PeerFaceVerifier::class),
        );

        $this->assertFalse($service->isAvailable());

        config(['peer_snap.verifier_url' => 'https://verifier.example.test']);
        $this->assertTrue($service->isAvailable());
        config(['peer_snap.pad_threshold' => 1.1]);
        $this->assertFalse($service->isAvailable());
    }
}
