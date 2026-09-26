<?php

namespace App\Policies;

use App\Models\AttendanceSession;
use App\Models\ClassStudentAssistant;
use App\Models\User;
use Carbon\Carbon;

class AttendanceSessionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Admin access to teacher management must not imply student-assistant
        // QR authority, which is intentionally student-only.
        if (in_array($ability, ['viewAssistantQr', 'generateAssistantQr'], true)) {
            return null;
        }

        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, AttendanceSession $session): bool
    {
        return (int) $user->id === (int) $session->subject?->instructor_id;
    }

    public function update(User $user, AttendanceSession $session): bool
    {
        return $this->view($user, $session);
    }

    public function manage(User $user, AttendanceSession $session): bool
    {
        return $this->view($user, $session);
    }

    public function viewAssistantQr(User $user, AttendanceSession $session): bool
    {
        if (!$user->isStudent() || !$user->isActive() || !$session->isSessionActive()) {
            return false;
        }

        $now = now('Asia/Manila');
        if (!$session->created_at || !$session->created_at->copy()->timezone('Asia/Manila')->isSameDay($now)) {
            return false;
        }

        $subject = $session->subject;
        if (!$subject || !$subject->getAllStudents()->contains('id', $user->id)) {
            return false;
        }

        $assignment = ClassStudentAssistant::where('subject_id', $subject->id)
            ->where('student_id', $user->id)
            ->whereNotNull('active_slot')
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where('expires_at', '>=', $now)
            ->first();
        if (!$assignment) {
            return false;
        }

        // The teacher may explicitly start an ad-hoc class. When a schedule
        // exists today, assistants must wait until its attendance window opens.
        $todaySchedules = $subject->schedules->filter(
            fn ($schedule) => strcasecmp(trim($schedule->day ?? ''), $now->format('l')) === 0
        );
        if ($todaySchedules->isEmpty()) {
            return true;
        }

        $grace = max(0, (int) config('student_assistants.attendance_start_grace_minutes', 10));
        return $todaySchedules->contains(function ($schedule) use ($now, $grace) {
            $windowOpens = Carbon::parse($now->toDateString() . ' ' . $schedule->start_time, 'Asia/Manila')
                ->subMinutes($grace);
            return $now->gte($windowOpens);
        });
    }

    public function generateAssistantQr(User $user, AttendanceSession $session): bool
    {
        return $this->viewAssistantQr($user, $session);
    }
}
