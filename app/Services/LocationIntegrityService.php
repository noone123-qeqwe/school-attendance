<?php

namespace App\Services;

use App\Models\Attendance;

class LocationIntegrityService
{
    /**
     * Normalize only an unmistakably reversed pair where the supplied latitude
     * is impossible. Valid coordinate pairs are never guessed or re-ordered.
     *
     * @return array{0: float, 1: float}
     */
    public static function normalizeCoordinates(float $lat, float $lng): array
    {
        if (abs($lat) > 90.0 && abs($lat) <= 180.0 && abs($lng) <= 90.0) {
            [$lat, $lng] = [$lng, $lat];
        }

        if (!is_finite($lat) || !is_finite($lng) || abs($lat) > 90.0 || abs($lng) > 180.0) {
            throw new \InvalidArgumentException('GPS coordinates are outside valid latitude/longitude ranges.');
        }

        return [$lat, $lng];
    }

    /** A close outside fix is inconclusive, but it must never count as inside. */
    public function isUncertainBoundary(float $distance, int $radius, float $accuracy): bool
    {
        return is_finite($distance) && is_finite($accuracy) && $accuracy > 0
            && $distance + $accuracy > $radius
            && $distance - $accuracy <= $radius;
    }

    /** Browser timestamps help reject accidental stale fixes; they are not attestation. */
    public function isStaleClientFix(?int $timestampMs): bool
    {
        if ($timestampMs === null) {
            return true;
        }

        $ageMs = (int) round(microtime(true) * 1000) - $timestampMs;

        return $ageMs < -5000 || $ageMs > 15000;
    }

    /**
     * Flag only an extreme jump between two reasonably accurate, server-timed
     * attendance fixes. Browser GPS alone cannot prove that a location is genuine.
     */
    public function impossibleRecentJump(int $userId, int $sessionId, float $lat, float $lng, float $accuracy): ?array
    {
        if ($accuracy <= 0 || $accuracy > 50) {
            return null;
        }

        $prior = Attendance::where('user_id', $userId)
            ->where('session_id', '!=', $sessionId)
            ->whereIn('status', ['Present', 'Late'])
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->whereNotNull('last_accuracy')
            ->where('last_accuracy', '>', 0)
            ->where('last_accuracy', '<=', 50)
            ->where('last_location_check_at', '>=', now()->subMinutes(5))
            ->orderByDesc('last_location_check_at')
            ->first();

        if (!$prior || !$prior->last_location_check_at || $prior->last_location_check_at->isFuture()) {
            return null;
        }

        $seconds = abs($prior->last_location_check_at->diffInSeconds(now()));
        if ($seconds < 5 || $seconds > 300) {
            return null;
        }

        try {
            $distance = $this->distanceMeters($lat, $lng, (float) $prior->last_latitude, (float) $prior->last_longitude);
        } catch (\InvalidArgumentException) {
            return null;
        }
        $minimumDistance = max(0, $distance - $accuracy - (float) $prior->last_accuracy);
        $speedMetersPerSecond = $minimumDistance / $seconds;

        // Exclude ordinary GPS jitter and fast road travel. This is an anomaly,
        // not a definitive finding of spoofing or VPN use.
        if ($minimumDistance <= 1000 || $speedMetersPerSecond <= 60) {
            return null;
        }

        return [
            'previous_attendance_id' => $prior->id,
            'distance_meters' => round($minimumDistance),
            'speed_kmh' => round($speedMetersPerSecond * 3.6),
        ];
    }

    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        [$lat1, $lng1] = self::normalizeCoordinates($lat1, $lng1);
        [$lat2, $lng2] = self::normalizeCoordinates($lat2, $lng2);

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;
        $a = min(1.0, max(0.0, $a));

        return 6371000.0 * 2.0 * atan2(sqrt($a), sqrt(max(0.0, 1.0 - $a)));
    }
}
