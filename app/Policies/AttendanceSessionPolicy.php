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
        if (in_array($ability, ['viewAssistantClass', 'viewAssistantQr', 'generateAssistantQr', 'submitAssistantAttendance'], true)) {
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
        return $this->viewAssistantClass($user, $session) && $this->withinAssistantSessionWindow($session);
    }

    private function withinAssistantSessionWindow(AttendanceSession $session): bool
    {
        if (!$session->isSessionActive()) {
            return false;
        }

        $now = now('Asia/Manila');
        if (!$session->created_at || !$session->created_at->copy()->timezone('Asia/Manila')->isSameDay($now)) {
            return false;
        }

        $subject = $session->subject;
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
            $startsAt = Carbon::parse($now->toDateString() . ' ' . $schedule->start_time, 'Asia/Manila');
            $endsAt = Carbon::parse($now->toDateString() . ' ' . $schedule->end_time, 'Asia/Manila');
            if ($endsAt->lte($startsAt)) {
                $endsAt->addDay();
            }

            return $now->betweenIncluded($startsAt->copy()->subMinutes($grace), $endsAt);
        });
    }

    public function viewAssistantClass(User $user, AttendanceSession $session): bool
    {
        return $this->activeAssistantAssignment($user, $session) !== null;
    }

    private function activeAssistantAssignment(User $user, AttendanceSession $session): ?ClassStudentAssistant
    {
        if (!$user->isStudent() || !$user->isActive()) {
            return null;
        }

        $subject = $session->subject;
        if (!$subject || !$subject->hasStudent($user)) {
            return null;
        }

        $now = now('Asia/Manila');
        return ClassStudentAssistant::where('subject_id', $subject->id)
            ->where('student_id', $user->id)
            ->whereNotNull('active_slot')
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where('expires_at', '>=', $now)
            ->first();
    }

    public function submitAssistantAttendance(User $user, AttendanceSession $session): bool
    {
        return (bool) $this->activeAssistantAssignment($user, $session)?->can_submit_attendance
            && $this->withinAssistantSessionWindow($session);
    }

    public function generateAssistantQr(User $user, AttendanceSession $session): bool
    {
        return $this->viewAssistantQr($user, $session);
    }
}
