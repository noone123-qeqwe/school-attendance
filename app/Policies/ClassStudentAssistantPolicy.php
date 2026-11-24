<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class ClassStudentAssistantPolicy
{
    public function administer(User $user): bool
    {
        return $user->isActive()
            && $user->isAdmin()
            && in_array($user->admin_sub_role, [null, 'admin', 'super_admin'], true);
    }

    public function manageAssignments(User $user, Subject $subject): bool
    {
        return $this->administer($user)
            || ($user->isActive() && $user->isTeacher() && (int) $subject->instructor_id === (int) $user->id);
    }
}
