<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Private channel — only the student themselves can listen
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Private channel — only the teacher themselves can listen
Broadcast::channel('teacher.{teacherId}', function ($user, $teacherId) {
    return (int) $user->id === (int) $teacherId && $user->isTeacher();
});

// Private channel — admin dashboard (only authenticated admins can listen)
Broadcast::channel('admin-dashboard', function ($user) {
    return $user && $user->isAdmin();
});

// Private channel — only the teacher themselves can listen on their dashboard
Broadcast::channel('teacher-dashboard.{teacherId}', function ($user, $teacherId) {
    return (int) $user->id === (int) $teacherId && ($user->isTeacher() || $user->isAdmin());
});
