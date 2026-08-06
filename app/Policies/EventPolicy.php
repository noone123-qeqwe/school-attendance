<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can create events.
     */
    public function create(User $user, string $type = 'meeting'): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher()) {
            // Instructors may only create meetings or class/exam events
            if (in_array($type, ['meeting', 'class', 'exam'])) {
                return true;
            }
        }

        return false; // school_event and holiday creation is admin-only
    }

    /**
     * Determine whether the user can update the event.
     */
    public function update(User $user, Event $event): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($event->type === 'holiday' || $event->type === 'school_event') {
            return false; // Only admin can update these
        }

        if ($user->isTeacher()) {
            if ($event->type === 'meeting' && $event->organizer_id === $user->id) {
                return true;
            }
            
            if (in_array($event->type, ['class', 'exam']) && $event->class_id) {
                return $user->subjects()->where('id', $event->class_id)->exists();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the event.
     */
    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event); // Same rules as update
    }
}
