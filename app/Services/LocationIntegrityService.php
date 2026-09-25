<?php

namespace App\Services;

use App\Models\Attendance;

class LocationIntegrityService
{
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

        $distance = $this->distanceMeters($lat, $lng, (float) $prior->last_latitude, (float) $prior->last_longitude);
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

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return 6371000 * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
    }
}
