<?php

namespace App\Support;

class TeacherNavigation
{
    public static function groups(): array
    {
        return [
            ['label' => 'Daily teaching', 'items' => [
                ['home', 'Dashboard', 'teacher.dashboard', 'bi-grid-fill', ['teacher.dashboard'], 'Today’s classes and latest attendance', []],
                ['classes', 'My Classes', 'teacher.classroom.index', 'bi-journal-bookmark-fill', ['teacher.classroom*', 'teacher.subjects*', 'teacher.attendance*'], 'Classes, rosters, attendance records, and sessions', []],
                ['take-attendance', 'Take Attendance', 'teacher.classroom.index', 'bi-qr-code-scan', ['teacher.qr*'], 'Choose a class and open its attendance QR', ['task' => 'attendance']],
            ]],
            ['label' => 'Attendance & requests', 'items' => [
                ['absent', 'Absent Report', 'teacher.absent', 'bi-person-x-fill', ['teacher.absent*'], 'Review missed classes and follow up', []],
                ['requests', 'Excuses & Corrections', 'teacher.excuse.reviews', 'bi-file-earmark-text-fill', ['teacher.excuse.*', 'teacher.corrections*', 'teacher.excuses*'], 'Review student excuses, attendance corrections, and your own requests', []],
            ]],
            ['label' => 'Students & school', 'items' => [
                ['students', 'Student Record', 'teacher.students', 'bi-people-fill', ['teacher.students*', 'teacher.student'], 'Find students and view their progress', []],
                ['reports', 'Attendance Reports', 'teacher.reports', 'bi-graph-up-arrow', ['teacher.reports*'], 'Summaries and downloadable reports', []],
                ['calendar', 'School Calendar', 'teacher.calendar', 'bi-calendar-event-fill', ['teacher.calendar*'], 'School events, holidays, and meetings', []],
            ]],
            ['label' => 'My account', 'items' => [
                ['notifications', 'Notifications', 'teacher.notifications', 'bi-bell-fill', ['teacher.notifications*'], 'Read school messages and updates', []],
                ['profile', 'My Profile', 'teacher.profile', 'bi-person-circle', ['teacher.profile*'], 'Personal details, security, and preferences', []],
            ]],
        ];
    }

    /**
     * Streamlined desktop sidebar items (mirrors the clean single-line Student sidebar).
     * Notifications, Profile/Settings, and Take Attendance live in the header/dashboard CTAs and mobile bottom nav.
     */
    public static function sidebarSections(): array
    {
        return [
            [
                ['home', 'Dashboard', 'teacher.dashboard', 'bi-grid-fill', ['teacher.dashboard'], []],
                ['classes', 'My Classes', 'teacher.classroom.index', 'bi-journal-bookmark-fill', ['teacher.classroom.index', 'teacher.classroom.show', 'teacher.classroom.assistant-attendance*', 'teacher.subjects*', 'teacher.attendance*'], []],
                ['calendar', 'School Calendar', 'teacher.calendar', 'bi-calendar-event-fill', ['teacher.calendar*'], []],
            ],
            [
                ['requests', 'Excuses & Corrections', 'teacher.excuse.reviews', 'bi-file-earmark-text-fill', ['teacher.excuse.*', 'teacher.corrections*', 'teacher.excuses*'], []],
                ['absent', 'Absent Report', 'teacher.absent', 'bi-person-x-fill', ['teacher.absent*'], []],
                ['reports', 'Attendance Reports', 'teacher.reports', 'bi-graph-up-arrow', ['teacher.reports*'], []],
            ],
        ];
    }

    public static function isActive(string $key, array $patterns): bool
    {
        $attendancePicker = request()->routeIs('teacher.classroom.index') && request('task') === 'attendance';
        if ($key === 'take-attendance' && $attendancePicker) {
            return true;
        }
        if ($key === 'classes' && $attendancePicker) {
            return false;
        }
        if ($key === 'classes' && request()->routeIs('teacher.classroom*', 'teacher.subjects*', 'teacher.attendance*')) {
            return true;
        }

        return request()->routeIs(...$patterns);
    }

    public static function context(?string $routeName): array
    {
        if (!$routeName) {
            return ['title' => 'Teacher Portal', 'group' => now()->format('l, F j, Y')];
        }

        if ($routeName === 'teacher.dashboard') {
            return ['title' => 'Dashboard', 'group' => now()->format('l, F j, Y')];
        }

        if ($routeName === 'teacher.classroom.index') {
            $task = request('task');
            $title = match ($task) {
                'attendance' => 'Take Attendance',
                'records' => 'Class Attendance Records',
                default => 'My Classes',
            };
            return [
                'title' => $title,
                'group' => 'Daily teaching',
            ];
        }

        $map = [
            'teacher.classroom.show' => ['title' => 'Class Workspace', 'group' => 'Daily teaching'],
            'teacher.classroom.assistant-attendance.*' => ['title' => 'Assistant Submissions', 'group' => 'Daily teaching'],
            'teacher.subjects*' => ['title' => 'My Subjects', 'group' => 'Daily teaching'],
            'teacher.qr*' => ['title' => 'Live QR Attendance', 'group' => 'Daily teaching'],
            'teacher.attendance.preview' => ['title' => 'Attendance Preview', 'group' => 'Attendance & records'],
            'teacher.attendance*' => ['title' => 'Attendance Records', 'group' => 'Attendance & records'],
            'teacher.absent*' => ['title' => 'Absent Report', 'group' => 'Attendance & records'],
            'teacher.excuses.create' => ['title' => 'Submit New Excuse', 'group' => 'Excuses & Corrections'],
            'teacher.excuse.*' => ['title' => 'Excuses & Corrections', 'group' => 'Attendance & requests'],
            'teacher.corrections*' => ['title' => 'Excuses & Corrections', 'group' => 'Attendance & requests'],
            'teacher.students.preview' => ['title' => 'Students Preview', 'group' => 'Students & records'],
            'teacher.students*' => ['title' => 'Student Record', 'group' => 'Students & records'],
            'teacher.student' => ['title' => 'Student Details', 'group' => 'Students & records'],
            'teacher.reports*' => ['title' => 'Attendance Reports', 'group' => 'Reports & analytics'],
            'teacher.calendar*' => ['title' => 'School Calendar', 'group' => 'Academic calendar'],
            'teacher.notifications*' => ['title' => 'Notifications', 'group' => 'My account'],
            'teacher.profile*' => ['title' => 'My Profile', 'group' => 'My account'],
            'teacher.excuses*' => ['title' => 'Excuses & Corrections', 'group' => 'Attendance & requests'],
        ];

        foreach ($map as $pattern => $meta) {
            if (\Illuminate\Support\Str::is($pattern, $routeName)) {
                return $meta;
            }
        }

        return ['title' => 'Teacher Portal', 'group' => now()->format('l, F j, Y')];
    }
}
