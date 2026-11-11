<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class AdminNavigation
{
    /** @return array<int, array{label: string, icon: string, items: array}> */
    public static function groups(User $user): array
    {
        $groups = [
            ['label' => 'Home', 'icon' => 'bi-house-door', 'description' => 'Overview, calendar and notices', 'items' => [
                ['Overview', 'admin.dashboard', 'bi-grid-fill', ['admin.dashboard'], 'Daily attendance and common tasks'],
                ['School calendar', 'admin.calendar', 'bi-calendar-event', ['admin.calendar*']],
                ['Announcements', 'admin.announcements.index', 'bi-megaphone', ['admin.announcements*']],
                ['Notifications', 'admin.notifications', 'bi-bell', ['admin.notifications*']],
            ]],
            ['label' => 'People', 'icon' => 'bi-people', 'description' => 'Students, teachers and families', 'items' => [
                ['Students', 'admin.students', 'bi-person', ['admin.students*', 'admin.student', 'admin.student.*']],
                ['Teachers', 'admin.teachers', 'bi-person-badge', ['admin.teachers*', 'admin.teacher.*'], 'Instructors and faculty accounts'],
                ['Parents & guardians', 'admin.parents.index', 'bi-people', ['admin.parents*']],
            ]],
            ['label' => 'Classes', 'icon' => 'bi-mortarboard', 'description' => 'Schedules, subjects and enrollment', 'items' => [
                ['Class schedules', 'admin.class-schedules.index', 'bi-clock', ['admin.class-schedules*'], 'Timetable, rooms and publishing'],
                ['Subjects & enrollment', 'admin.subjects', 'bi-journal-bookmark', ['admin.subjects*', 'admin.enrollments*'], 'Subjects, instructors and class rosters'],
                ['Class sections', 'admin.sections.index', 'bi-diagram-3', ['admin.sections*']],
                ['Academic terms', 'admin.academic-years.index', 'bi-calendar3', ['admin.academic-years*']],
                ['Departments', 'admin.departments.index', 'bi-building', ['admin.departments*']],
                ['Courses & programs', 'admin.courses.index', 'bi-book', ['admin.courses*']],
            ]],
            ['label' => 'Attendance', 'icon' => 'bi-clipboard-check', 'description' => 'Records, reviews and reports', 'items' => [
                ['Attendance records', 'admin.attendance', 'bi-clipboard-data', ['admin.attendance*']],
                ['Reports', 'admin.reports', 'bi-bar-chart', ['admin.reports*'], 'Analytics, summaries and downloads'],
                ['Student QR codes', 'admin.qr', 'bi-qr-code', ['admin.qr*'], 'QR center and printable student codes'],
                ['Excuse reviews', 'admin.excuses', 'bi-file-earmark-text', ['admin.excuses*']],
                ['Correction requests', 'admin.corrections', 'bi-pencil-square', ['admin.corrections*']],
                ['Early warnings', 'admin.early-warnings', 'bi-exclamation-triangle', ['admin.early-warnings*']],
                ['Classroom location', 'admin.gps', 'bi-geo-alt', ['admin.gps*']],
            ]],
            ['label' => 'School & system', 'icon' => 'bi-gear', 'description' => 'Preferences, access and maintenance', 'items' => [
                ['My profile', 'admin.profile', 'bi-person-circle', ['admin.profile']],
            ]],
        ];

        if ($user->admin_sub_role !== 'data_entry') {
            $groups[4]['items'][] = ['Audit logs', 'admin.activity.log', 'bi-list-check', ['admin.activity.log*']];
        }
        if ($user->isSuperAdmin()) {
            $groups[1]['items'][] = ['Administrators', 'admin.admins', 'bi-person-gear', ['admin.admins*', 'admin.admin.*']];
            $groups[4]['items'] = array_merge($groups[4]['items'], [
                ['System settings', 'admin.settings', 'bi-sliders', ['admin.settings*']],
                ['Permissions', 'admin.roles.index', 'bi-shield-lock', ['admin.roles*']],
                ['Privacy & terms', 'admin.policies.edit', 'bi-file-earmark-lock', ['admin.policies*']],
                ['System maintenance', 'admin.system-update.index', 'bi-tools', ['admin.system-update*', 'admin.system-health*', 'admin.backups*']],
            ]);
        }

        return $groups;
    }

    public static function matches(array $patterns, ?string $routeName): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $routeName ?? '')) return true;
        }
        return false;
    }

    /** @return array{title: string, group: string} */
    public static function context(User $user, ?string $routeName): array
    {
        foreach (self::groups($user) as $group) {
            foreach ($group['items'] as [$label, $route, $icon, $patterns]) {
                if (self::matches($patterns, $routeName)) return ['title' => $label, 'group' => $group['label']];
            }
        }
        return ['title' => 'Administration', 'group' => 'Workspace'];
    }

    public static function currentGroup(User $user, ?string $routeName): ?array
    {
        foreach (self::groups($user) as $group) {
            foreach ($group['items'] as $item) {
                if (self::matches($item[3], $routeName)) return $group;
            }
        }
        return null;
    }

    /** Reuse the same destinations and permissions in the global page search. */
    public static function searchItems(User $user): array
    {
        $items = [];
        foreach (self::groups($user) as $group) {
            foreach ($group['items'] as $item) {
                $items[] = [
                    'id' => $item[1], 'label' => $item[0],
                    'hint' => $item[4] ?? $group['description'], 'icon' => $item[2],
                    'category' => 'navigation', 'badge' => $group['label'],
                    'color' => '#cfa46f', 'bg' => 'rgba(207,164,111,0.12)', 'url' => route($item[1]),
                ];
            }
        }
        return $items;
    }
}
