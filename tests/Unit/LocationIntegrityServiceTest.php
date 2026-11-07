<?php

namespace Tests\Unit;

use App\Services\LocationIntegrityService;
use PHPUnit\Framework\TestCase;

class LocationIntegrityServiceTest extends TestCase
{
    private LocationIntegrityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LocationIntegrityService();
    }

    // --- isUncertainBoundary ---

    public function test_inside_radius_is_not_uncertain(): void
    {
        $this->assertFalse($this->service->isUncertainBoundary(40.0, 50, 10.0));
    }

    public function test_exactly_on_radius_is_not_uncertain(): void
    {
        $this->assertFalse($this->service->isUncertainBoundary(50.0, 50, 10.0));
    }

    public function test_just_outside_radius_is_uncertain(): void
    {
        $this->assertTrue($this->service->isUncertainBoundary(53.0, 50, 10.0));
    }

    public function test_far_outside_radius_is_not_uncertain(): void
    {
        $this->assertFalse($this->service->isUncertainBoundary(100.0, 50, 10.0));
    }

    public function test_boundary_respects_accuracy_cap(): void
    {
        // Accuracy cap is min(10, accuracy/2). With accuracy=100, cap=10.
        // Distance must be <= 50+10=60 to be uncertain.
        $this->assertTrue($this->service->isUncertainBoundary(55.0, 50, 100.0));
        $this->assertFalse($this->service->isUncertainBoundary(61.0, 50, 100.0));
    }

    public function test_boundary_with_small_accuracy(): void
    {
        // With accuracy=4, cap=min(10, 2)=2. Distance must be <= 50+2=52 to be uncertain.
        $this->assertTrue($this->service->isUncertainBoundary(51.0, 50, 4.0));
        $this->assertFalse($this->service->isUncertainBoundary(53.0, 50, 4.0));
    }

    // --- isStaleClientFix ---

    public function test_null_timestamp_is_not_stale(): void
    {
        $this->assertFalse($this->service->isStaleClientFix(null));
    }

    public function test_current_timestamp_is_not_stale(): void
    {
        $nowMs = (int) round(microtime(true) * 1000);
        $this->assertFalse($this->service->isStaleClientFix($nowMs));
    }

    public function test_old_timestamp_is_stale(): void
    {
        $oldMs = (int) round(microtime(true) * 1000) - 20000; // 20 seconds ago
        $this->assertTrue($this->service->isStaleClientFix($oldMs));
    }

    public function test_future_timestamp_is_stale(): void
    {
        $futureMs = (int) round(microtime(true) * 1000) + 10000; // 10 seconds in future
        $this->assertTrue($this->service->isStaleClientFix($futureMs));
    }

    public function test_slightly_future_timestamp_is_not_stale(): void
    {
        // Within 5 seconds in the future is allowed
        $slightlyFutureMs = (int) round(microtime(true) * 1000) + 3000;
        $this->assertFalse($this->service->isStaleClientFix($slightlyFutureMs));
    }
}
