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

    public function test_exactly_on_radius_is_uncertain_with_nonzero_sensor_error(): void
    {
        $this->assertTrue($this->service->isUncertainBoundary(50.0, 50, 10.0));
    }

    public function test_just_outside_radius_is_uncertain(): void
    {
        $this->assertTrue($this->service->isUncertainBoundary(53.0, 50, 10.0));
    }

    public function test_far_outside_radius_is_not_uncertain(): void
    {
        $this->assertFalse($this->service->isUncertainBoundary(100.0, 50, 10.0));
    }

    public function test_boundary_uncertainty_uses_reported_accuracy(): void
    {
        $this->assertTrue($this->service->isUncertainBoundary(55.0, 50, 10.0));
        $this->assertFalse($this->service->isUncertainBoundary(61.0, 50, 10.0));
    }

    public function test_boundary_with_small_accuracy(): void
    {
        // The plausible interval overlaps the 50m boundary.
        $this->assertTrue($this->service->isUncertainBoundary(51.0, 50, 4.0));
        $this->assertFalse($this->service->isUncertainBoundary(55.0, 50, 4.0));
    }

    // --- isStaleClientFix ---

    public function test_missing_timestamp_is_not_accepted_as_a_fresh_fix(): void
    {
        $this->assertTrue($this->service->isStaleClientFix(null));
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

    // --- normalizeCoordinates & distanceMeters ---

    public function test_normalize_coordinates_swaps_inverted_coordinates(): void
    {
        [$lat, $lng] = LocationIntegrityService::normalizeCoordinates(123.619, 12.371);
        $this->assertEqualsWithDelta(12.371, $lat, 0.0001);
        $this->assertEqualsWithDelta(123.619, $lng, 0.0001);
    }

    public function test_normalize_coordinates_does_not_guess_from_valid_regional_coordinates(): void
    {
        [$lat, $lng] = LocationIntegrityService::normalizeCoordinates(55.7558, 37.6173);
        $this->assertEqualsWithDelta(55.7558, $lat, 0.0001);
        $this->assertEqualsWithDelta(37.6173, $lng, 0.0001);
    }

    public function test_invalid_coordinates_are_rejected_instead_of_clamped(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LocationIntegrityService::normalizeCoordinates(91.0, 181.0);
    }

    public function test_distance_meters_handles_inverted_pair_consistently(): void
    {
        $distance = $this->service->distanceMeters(121.0000, 14.5003, 14.5000, 121.0000);
        $this->assertEqualsWithDelta(33.36, $distance, 1.0);
    }

    public function test_distance_meters_reports_realistic_seventeen_kilometer_separation(): void
    {
        $distance = $this->service->distanceMeters(14.5000, 121.0000, 14.6530, 121.0000);

        $this->assertGreaterThan(16_900, $distance);
        $this->assertLessThan(17_100, $distance);
    }
}
