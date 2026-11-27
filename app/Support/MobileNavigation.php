<?php

namespace App\Support;

use App\Models\User;

/** Bottom tab priorities and compact labels for the shared portal navigation. */
final class MobileNavigation
{
    /** Destinations promoted to tabs so the More sheet can omit duplicates. */
    public static function primaryRoutes(User $user): array
    {
        return array_values(array_unique(array_filter(array_column(self::tabs($user), 'route'))));
    }

    public static function tabs(User $user): array
    {
        if ($user->isStudent()) {
            $tabs = [];
            foreach (StudentNavigation::sidebarItems() as $item) {
                if ($item['key'] === 'attendance') {
                    $tabs[] = self::tab('scan', 'Scan Attendance QR', 'mobile.scan', 'qr-code-scan', ['mobile.scan'], 'Scan');
                }
                [$shortLabel, $icon] = match ($item['key']) {
                    'home' => ['Home', 'house-door'],
                    'classes' => ['Classes', 'journal-bookmark'],
                    'attendance' => ['Calendar', 'calendar2-week'],
                    'excuses' => ['Excuses', 'file-earmark-text'],
                    default => [$item['label'], $item['icon']],
                };
                $tabs[] = self::tab($item['key'], $item['label'], $item['route'], $icon, $item['active'], $shortLabel);
            }

            return $tabs;
        }

        if ($user->isTeacher()) {
            $primary = ['home' => 'Home', 'classes' => 'Classes', 'take-attendance' => 'QR', 'calendar' => 'Calendar'];
            $items = array_column(array_merge(...array_column(TeacherNavigation::groups(), 'items')), null, 0);
            $tabs = [];
            foreach ($primary as $key => $shortLabel) {
                [, $label, $route, $icon, $active, , $query] = $items[$key];
                $tabs[] = self::tab($key, $label, $route, $icon, $active, $shortLabel, $query, $key === 'take-attendance');
            }
            $menuActive = [];
            foreach (array_merge(...TeacherNavigation::sidebarSections()) as [$key, , , , $active]) {
                if (!isset($primary[$key])) {
                    array_push($menuActive, ...$active);
                }
            }
            $tabs[] = self::more('Open all teacher pages', $menuActive);

            return $tabs;
        }

        if ($user->isParent()) {
            return self::workspaceTabs(ParentNavigation::items(), [
                'parent.dashboard' => 'Home',
                'parent.calendar' => 'Calendar',
                'parent.notifications' => 'Alerts',
            ], 'Open all guardian pages');
        }

        return self::workspaceTabs(array_merge(...array_column(AdminNavigation::groups($user), 'items')), [
            'admin.dashboard' => 'Home',
            'admin.academic.overview' => 'Academic',
            'admin.reports' => 'Reports',
            'admin.notifications' => 'Alerts',
        ], 'Open all admin pages');
    }

    private static function workspaceTabs(array $items, array $primary, string $menuLabel): array
    {
        $tabs = [];
        $menuActive = [];
        foreach ($items as [$label, $route, $icon, $active]) {
            if (isset($primary[$route])) {
                $tabs[] = self::tab($route, $label, $route, $icon, $active, $primary[$route]);
            } else {
                array_push($menuActive, ...$active);
            }
        }

        return [...$tabs, self::more($menuLabel, $menuActive)];
    }

    private static function tab(string $key, string $label, ?string $route, string $icon, array $active, string $shortLabel, array $query = [], bool $featured = false): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'shortLabel' => $shortLabel,
            'route' => $route,
            'icon' => str_starts_with($icon, 'bi-') ? $icon : 'bi-'.$icon,
            'active' => $active,
            'query' => $query,
            'featured' => $featured,
        ];
    }

    private static function more(string $label, array $active): array
    {
        return self::tab('more', $label, null, 'list', $active, 'Menu');
    }
}
