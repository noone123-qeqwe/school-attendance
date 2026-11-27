<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class AdminNavigation
{
    /** @return array<int, array{label: string, icon: string, description: string, items: array}> */
    public static function groups(User $user): array
    {
        $academicRoutes = array_merge(...array_map(
            fn (array $tab) => array_merge(...array_column($tab['items'], 3)),
            self::academicTabs()
        ));

        $groups = [
            ['label' => 'Workspace', 'icon' => 'bi-grid', 'description' => 'Daily operations and reports', 'items' => [
                ['Dashboard', 'admin.dashboard', 'bi-grid', ['admin.dashboard'], 'Attendance trends, priorities and live sessions'],
                ['Academic Management', 'admin.academic.overview', 'bi-mortarboard', $academicRoutes, 'Students, teachers, instructors, classes, sections, schedules, enrollment and attendance'],
                ['Reports & analytics', 'admin.reports', 'bi-bar-chart', ['admin.reports*'], 'Analytics, summaries and downloads'],
            ]],
            ['label' => 'School operations', 'icon' => 'bi-building', 'description' => 'Calendar, families and communications', 'items' => [
                ['School calendar', 'admin.calendar', 'bi-calendar-event', ['admin.calendar*']],
                ['Announcements', 'admin.announcements.index', 'bi-megaphone', ['admin.announcements*']],
                ['Notifications', 'admin.notifications', 'bi-bell', ['admin.notifications*']],
                ['Parents & guardians', 'admin.parents.index', 'bi-people', ['admin.parents*']],
            ]],
            ['label' => 'Settings & access', 'icon' => 'bi-gear', 'description' => 'Account, permissions and maintenance', 'items' => [
                ['Account settings', 'admin.profile', 'bi-person-circle', ['admin.profile*']],
            ]],
        ];

        if ($user->admin_sub_role !== 'data_entry') {
            $groups[2]['items'][] = ['Audit logs', 'admin.activity.log', 'bi-list-check', ['admin.activity.log*']];
        }
        if ($user->isSuperAdmin()) {
            $groups[2]['items'] = array_merge($groups[2]['items'], [
                ['System settings', 'admin.settings', 'bi-sliders', ['admin.settings*']],
                ['Administrators', 'admin.admins', 'bi-person-gear', ['admin.admins*', 'admin.admin.*']],
                ['Permissions', 'admin.roles.index', 'bi-shield-lock', ['admin.roles*']],
                ['Privacy & terms', 'admin.policies.edit', 'bi-file-earmark-lock', ['admin.policies*']],
                ['System maintenance', 'admin.system-update.index', 'bi-tools', ['admin.system-update*', 'admin.system-health*', 'admin.backups*']],
            ]);
        }

        return $groups;
    }

    /** @return list<array{label: string, items: array}> */
    public static function academicTabs(): array
    {
        return [
            ['label' => 'Overview', 'items' => [
                ['Academic overview', 'admin.academic.overview', 'bi-grid', ['admin.academic.overview']],
            ]],
            ['label' => 'Students', 'items' => [
                ['Students', 'admin.students', 'bi-person', ['admin.students*', 'admin.student', 'admin.student.*']],
            ]],
            ['label' => 'Teachers', 'items' => [
                ['Teachers', 'admin.teachers', 'bi-person-badge', ['admin.teachers*', 'admin.teacher.*'], 'Instructors and faculty accounts'],
            ]],
            ['label' => 'Classes & Sections', 'items' => [
                ['Class schedules', 'admin.class-schedules.index', 'bi-clock', ['admin.class-schedules*'], 'Timetable, rooms and publishing'],
                ['Subjects & enrollment', 'admin.subjects', 'bi-journal-bookmark', ['admin.subjects*', 'admin.enrollments*'], 'Subjects, instructors and class rosters'],
                ['Class sections', 'admin.sections.index', 'bi-diagram-3', ['admin.sections*']],
                ['Academic terms', 'admin.academic-years.index', 'bi-calendar3', ['admin.academic-years*']],
                ['Departments', 'admin.departments.index', 'bi-building', ['admin.departments*']],
                ['Courses & programs', 'admin.courses.index', 'bi-book', ['admin.courses*']],
            ]],
            ['label' => 'Attendance', 'items' => [
                ['Attendance records', 'admin.attendance', 'bi-clipboard-data', ['admin.attendance*']],
                ['Excuse reviews', 'admin.excuses', 'bi-file-earmark-text', ['admin.excuses*']],
                ['Correction requests', 'admin.corrections', 'bi-pencil-square', ['admin.corrections*']],
                ['Early warnings', 'admin.early-warnings', 'bi-exclamation-triangle', ['admin.early-warnings*']],
                ['Student QR codes', 'admin.qr', 'bi-qr-code', ['admin.qr*'], 'Printable student codes'],
                ['Classroom location', 'admin.gps', 'bi-geo-alt', ['admin.gps*']],
            ]],
        ];
    }

    /** @return array{label: string, items: array, current: array}|null */
    public static function academicContext(?string $routeName): ?array
    {
        foreach (self::academicTabs() as $tab) {
            foreach ($tab['items'] as $item) {
                if (self::matches($item[3], $routeName)) {
                    return $tab + ['current' => $item];
                }
            }
        }

        return null;
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
        if ($academic = self::academicContext($routeName)) {
            return ['title' => $academic['current'][0], 'group' => 'Academic Management'];
        }

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
        $groups = self::groups($user);
        $tabItems = array_merge(...array_column(self::academicTabs(), 'items'));
        array_shift($tabItems);
        $groups[] = ['label' => 'Academic Management', 'description' => 'Academic Management', 'items' => $tabItems];

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $route = $item[1];
                $category = match (true) {
                    in_array($route, ['admin.qr', 'admin.excuses', 'admin.corrections'], true) => 'action',
                    in_array($route, [
                        'admin.academic.overview', 'admin.students', 'admin.teachers', 'admin.parents.index',
                        'admin.class-schedules.index', 'admin.subjects', 'admin.sections.index',
                        'admin.academic-years.index', 'admin.departments.index', 'admin.courses.index',
                        'admin.attendance', 'admin.reports', 'admin.early-warnings',
                    ], true) => 'academic',
                    in_array($route, [
                        'admin.profile', 'admin.activity.log', 'admin.admins',
                        'admin.settings', 'admin.roles.index', 'admin.policies.edit',
                        'admin.system-update.index', 'admin.gps',
                    ], true) => 'system',
                    default => 'navigation',
                };

                $items[] = [
                    'id' => $route, 'label' => $item[0],
                    'hint' => $item[4] ?? $group['description'], 'icon' => $item[2],
                    'category' => $category, 'badge' => $group['label'],
                    'color' => '#cfa46f', 'bg' => 'rgba(207,164,111,0.12)', 'url' => route($route),
                ];
            }
        }
        return $items;
    }
}
