<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Subject;
use App\Models\User;

class PeerVouchPolicy
{
    public function create(User $host, AttendanceSession $session): bool
    {
        if ($host->role !== 'student' || !$host->isActive() || !$session->isSessionActive()
            || $session->getAllowedRadius() <= 0) {
            return false;
        }

        $subject = $session->subject;
        if (!$subject || !$this->isClassMember($host, $subject)) {
            return false;
        }

        $presence = Attendance::where('session_id', $session->id)
            ->where('user_id', $host->id)
            ->whereIn('status', ['Present', 'Late'])
            ->whereIn('method', ['qr', 'webauthn'])
            ->where('monitoring_status', 'active')
            ->where('last_location_check_at', '>=', now()->subSeconds(config('peer_snap.presence_fresh_seconds')))
            ->where('last_location_check_at', '<=', now())
            ->latest('id')->first();

        return $presence !== null
            && $presence->last_distance_meters !== null
            && $presence->last_distance_meters >= 0
            && $presence->last_distance_meters <= $session->getAllowedRadius()
            && $session->classroom_lat !== null
            && $session->classroom_lng !== null;
    }

    /** Match Subject::getAllStudents without loading the whole roster. */
    public function isClassMember(User $student, Subject $subject): bool
    {
        if ($student->role !== 'student') return false;

        if ($subject->enrolledStudents()->whereKey($student->id)->exists()) return true;

        return User::whereKey($student->id)
            ->where('role', 'student')
            ->where('year_level', $subject->year_level)
            ->where('semester', $subject->semester)
            ->when(!empty($subject->course), fn ($query) => $query->where('course', $subject->course))
            ->when(!empty($subject->section), fn ($query) => $query->where('section', $subject->section))
            ->exists();
    }
}
