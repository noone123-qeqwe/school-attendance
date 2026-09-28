<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\User;

class PeerVouchPolicy
{
    public function create(User $host, AttendanceSession $session): bool
    {
        if ($host->role !== 'student' || !$host->isActive() || !$session->isSessionActive()) {
            return false;
        }

        $subject = $session->subject;
        if (!$subject || !$subject->getAllStudents()->contains('id', $host->id)) {
            return false;
        }

        $presence = Attendance::where('session_id', $session->id)
            ->where('user_id', $host->id)
            ->whereIn('status', ['Present', 'Late'])
            ->whereIn('method', ['qr', 'webauthn'])
            ->where('monitoring_status', 'active')
            ->where('last_location_check_at', '>=', now()->subSeconds(config('peer_snap.presence_fresh_seconds')))
            ->latest('id')->first();

        return $presence !== null
            && $presence->last_distance_meters !== null
            && $presence->last_distance_meters >= 0
            && $presence->last_distance_meters <= $session->getAllowedRadius()
            && $session->classroom_lat !== null
            && $session->classroom_lng !== null;
    }
}
