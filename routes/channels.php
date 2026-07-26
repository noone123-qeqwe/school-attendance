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

// Public channel — admin dashboard (no auth needed, admin checks on frontend)
// admin-dashboard is a public Channel so no auth callback needed

// Private channel — only the teacher themselves can listen on their dashboard
Broadcast::channel('teacher-dashboard.{teacherId}', function ($user, $teacherId) {
    return (int) $user->id === (int) $teacherId && ($user->isTeacher() || $user->isAdmin());
});
