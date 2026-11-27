<?php

namespace App\Support;

/**
 * Shared destinations and labels for student navigation across desktop and PWA layouts.
 * The mobile tab bar shortens labels and adds the dedicated attendance scanner.
 */
final class StudentNavigation
{
    /** @return list<array{key: string, label: string, route: string, icon: string, active: list<string>}> */
    public static function sidebarItems(bool $hasAssistantAssignment = false): array
    {
        $items = [
            ['key' => 'home', 'label' => 'Dashboard', 'route' => 'home', 'icon' => 'grid-fill', 'active' => ['home']],
            ['key' => 'classes', 'label' => 'Classes & Schedule', 'route' => 'student.classes', 'icon' => 'journal-bookmark-fill', 'active' => ['student.classes', 'student.schedule', 'classes.index']],
        ];

        if ($hasAssistantAssignment) {
            $items[] = ['key' => 'assistant', 'label' => 'Student Assistant', 'route' => 'student-assistant.classes', 'icon' => 'qr-code', 'active' => ['student-assistant.*']];
        }

        $items[] = ['key' => 'attendance', 'label' => 'Attendance & Calendar', 'route' => 'student.calendar', 'icon' => 'calendar-event-fill', 'active' => ['student.calendar', 'student.attendance.calendar', 'attendance.records', 'attendance.history']];
        $items[] = ['key' => 'excuses', 'label' => 'Excuse Submissions', 'route' => 'excuses', 'icon' => 'file-text-fill', 'active' => ['excuses*']];

        return $items;
    }

}
