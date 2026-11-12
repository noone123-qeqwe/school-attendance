<?php

namespace App\Support;

class TeacherNavigation
{
    public static function groups(): array
    {
        return [
            ['label' => 'Daily teaching', 'items' => [
                ['home', 'Dashboard', 'teacher.dashboard', 'bi-house-door', ['teacher.dashboard'], 'Today’s classes and latest attendance', []],
                ['classes', 'My Classes', 'teacher.classroom.index', 'bi-journal-bookmark', ['teacher.classroom*', 'teacher.subjects*'], 'Students, schedules, and class materials', []],
                ['take-attendance', 'Take Attendance', 'teacher.classroom.index', 'bi-qr-code-scan', ['teacher.qr*'], 'Choose a class and open its attendance QR', ['task' => 'attendance']],
            ]],
            ['label' => 'Attendance & requests', 'items' => [
                ['attendance', 'Attendance Records', 'teacher.attendance', 'bi-clipboard-check', ['teacher.attendance*'], 'Find a student’s recorded attendance', []],
                ['absent', 'Absent Report', 'teacher.absent', 'bi-person-x', ['teacher.absent*'], 'Review missed classes and follow up', []],
                ['excuses', 'Excuse Submissions', 'teacher.excuse.reviews', 'bi-file-earmark-text', ['teacher.excuse.*'], 'Review student excuse letters', []],
                ['corrections', 'Correction Requests', 'teacher.corrections', 'bi-pencil-square', ['teacher.corrections*'], 'Review requests to fix attendance', []],
            ]],
            ['label' => 'Students & school', 'items' => [
                ['students', 'Student Roster', 'teacher.students', 'bi-people', ['teacher.students*', 'teacher.student'], 'Find students and view their progress', []],
                ['reports', 'Attendance Reports', 'teacher.reports', 'bi-graph-up-arrow', ['teacher.reports*'], 'Summaries and downloadable reports', []],
                ['calendar', 'School Calendar', 'teacher.calendar', 'bi-calendar2-week', ['teacher.calendar*'], 'School events, holidays, and meetings', []],
            ]],
            ['label' => 'My account', 'items' => [
                ['notifications', 'Notifications', 'teacher.notifications', 'bi-bell', ['teacher.notifications*'], 'Read school messages and updates', []],
                ['profile', 'My Profile', 'teacher.profile', 'bi-person-circle', ['teacher.profile*'], 'Personal details, security, and preferences', []],
                ['my-excuses', 'My Excuse Requests', 'teacher.excuses', 'bi-file-earmark-person', ['teacher.excuses*'], 'Submit or track your own excuse requests', []],
            ]],
        ];
    }

    public static function isActive(string $key, array $patterns): bool
    {
        $attendancePicker = request()->routeIs('teacher.classroom.index') && request('task') === 'attendance';
        if ($key === 'take-attendance' && $attendancePicker) return true;
        if ($key === 'classes' && $attendancePicker) return false;

        return request()->routeIs(...$patterns);
    }
}
