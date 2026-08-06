<?php

namespace App\Policies;

use App\Models\AttendanceSession;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttendanceSessionPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->id === $attendanceSession->subject->instructor_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->id === $attendanceSession->subject->instructor_id;
    }

    /**
     * Determine whether the user can manage the model.
     */
    public function manage(User $user, AttendanceSession $attendanceSession): bool
    {
        return $user->id === $attendanceSession->subject->instructor_id;
    }
}
