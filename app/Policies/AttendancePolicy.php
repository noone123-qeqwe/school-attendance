<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attendance;

class AttendancePolicy
{
    /**
     * Determine whether the user can view the attendance record.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->role === 'admin' || $user->role === 'teacher') {
            return true;
        }
        
        return $user->id === $attendance->user_id;
    }

    /**
     * Determine whether the user can create an attendance record.
     */
    public function create(User $user): bool
    {
        return $user->role === 'teacher' || $user->role === 'student';
    }

    /**
     * Determine whether the user can update the attendance record.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $user->role === 'admin' || $user->role === 'teacher';
    }
}
