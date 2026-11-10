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
            ['label' => 'Overview', 'icon' => 'bi-grid', 'items' => [
                ['Dashboard', 'admin.dashboard', 'bi-grid-fill', ['admin.dashboard']],
                ['School calendar', 'admin.calendar', 'bi-calendar-event', ['admin.calendar*']],
                ['Announcements', 'admin.announcements.index', 'bi-megaphone', ['admin.announcements*']],
                ['Notifications', 'admin.notifications', 'bi-bell', ['admin.notifications*']],
            ]],
            ['label' => 'People', 'icon' => 'bi-people', 'items' => [
                ['Students', 'admin.students', 'bi-person', ['admin.students*', 'admin.student', 'admin.student.*']],
                ['Instructors', 'admin.teachers', 'bi-person-badge', ['admin.teachers*', 'admin.teacher.*']],
                ['Parents & guardians', 'admin.parents.index', 'bi-people', ['admin.parents*']],
            ]],
            ['label' => 'Academics', 'icon' => 'bi-mortarboard', 'items' => [
                ['Academic terms', 'admin.academic-years.index', 'bi-calendar3', ['admin.academic-years*']],
                ['Departments', 'admin.departments.index', 'bi-building', ['admin.departments*']],
                ['Courses & programs', 'admin.courses.index', 'bi-book', ['admin.courses*']],
                ['Class sections', 'admin.sections.index', 'bi-diagram-3', ['admin.sections*']],
                ['Subjects & enrollments', 'admin.subjects', 'bi-journal-bookmark', ['admin.subjects*', 'admin.enrollments*']],
                ['Class schedules', 'admin.class-schedules.index', 'bi-clock', ['admin.class-schedules*']],
            ]],
            ['label' => 'Attendance', 'icon' => 'bi-clipboard-check', 'items' => [
                ['Attendance records', 'admin.attendance', 'bi-clipboard-data', ['admin.attendance*']],
                ['QR center', 'admin.qr', 'bi-qr-code', ['admin.qr*']],
                ['Excuse reviews', 'admin.excuses', 'bi-file-earmark-text', ['admin.excuses*']],
                ['Correction requests', 'admin.corrections', 'bi-pencil-square', ['admin.corrections*']],
                ['Early warnings', 'admin.early-warnings', 'bi-exclamation-triangle', ['admin.early-warnings*']],
                ['Reports & analytics', 'admin.reports', 'bi-bar-chart', ['admin.reports*']],
                ['Classroom location', 'admin.gps', 'bi-geo-alt', ['admin.gps*']],
            ]],
            ['label' => 'Administration', 'icon' => 'bi-gear', 'items' => [
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
}
