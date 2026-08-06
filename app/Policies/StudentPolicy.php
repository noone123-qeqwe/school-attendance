<?php

namespace App\Policies;

use App\Models\User;

class StudentPolicy
{
    /**
     * Determine whether the user can view the student.
     */
    public function view(User $user, User $student): bool
    {
        return $user->role === 'admin' || $user->role === 'teacher';
    }

    /**
     * Determine whether the user can update the student.
     */
    public function update(User $user, User $student): bool
    {
        return $user->role === 'admin' || $user->id === $student->id;
    }

    /**
     * Determine whether the user can delete the student.
     */
    public function delete(User $user, User $student): bool
    {
        return $user->role === 'admin';
    }
}
