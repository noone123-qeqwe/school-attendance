<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\User;

class ParentNavigation
{
    /** @return array<int, array{string, string, string, array<int, string>, string}> */
    public static function items(): array
    {
        return [
            ['Overview', 'parent.dashboard', 'bi-grid', ['parent.dashboard', 'parent.child', 'parent.child.warnings'], 'gold'],
            ['Linked students', 'parent.link.form', 'bi-people', ['parent.link*'], 'green'],
            ['Attendance calendar', 'parent.attendance.calendar', 'bi-calendar-check', ['parent.attendance.calendar'], 'green'],
            ['Weekly schedule', 'parent.schedule', 'bi-clock', ['parent.schedule'], 'purple'],
            ['School calendar', 'parent.calendar', 'bi-calendar-event', ['parent.calendar*'], 'gold'],
            ['Excuse letters', 'parent.excuses', 'bi-file-earmark-text', ['parent.excuses*', 'parent.excuse*', 'parent.child.excuse'], 'red'],
            ['Notifications', 'parent.notifications', 'bi-bell', ['parent.notifications*'], 'amber'],
            ['Account settings', 'parent.profile', 'bi-gear', ['parent.profile'], 'blue'],
        ];
    }

    public static function unreadCount(User $parent): int
    {
        $childIds = $parent->children()->pluck('users.id');
        return Notification::where('user_id', $parent->id)->active()->where('is_read', false)->count()
            + Notification::whereIn('user_id', $childIds)->active()->where('is_read', false)
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('parent_notification_reads')
                    ->whereColumn('parent_notification_reads.notification_id', 'notifications.id')
                    ->where('parent_notification_reads.parent_id', $parent->id))->count();
    }
}
