<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ParentNavigation
{
    /** @return array<int, array{string, string, string, array<int, string>, string}> */
    public static function items(): array
    {
        return [
            ['Home', 'parent.dashboard', 'bi-house-door', ['parent.dashboard'], 'gold', 'Choose a child and see the most important updates'],
            ['Attendance & school calendar', 'parent.calendar', 'bi-calendar-check', ['parent.calendar*', 'parent.attendance.calendar', 'parent.child', 'parent.child.warnings'], 'green', 'View attendance, school events, holidays, and important dates'],
            ['Class schedule', 'parent.schedule', 'bi-clock', ['parent.schedule'], 'purple', 'See class times, rooms, and instructors'],
            ['Excuse requests', 'parent.excuses', 'bi-file-earmark-text', ['parent.excuses*', 'parent.excuse*', 'parent.child.excuse'], 'red', 'Submit an excuse or check its status'],
            ['Alerts', 'parent.notifications', 'bi-bell', ['parent.notifications*'], 'amber', 'Read attendance alerts and school messages'],
            ['My children', 'parent.link.form', 'bi-people', ['parent.link*'], 'green', 'Link a student or manage connections'],
            ['Account Settings', 'parent.profile', 'bi-gear', ['parent.profile'], 'blue', 'Profile, sign-in and preferences'],
        ];
    }

    public static function groups(): array
    {
        $items = self::items();
        return [
            ['label' => 'Your child', 'items' => array_slice($items, 0, 3)],
            ['label' => 'School support', 'items' => array_slice($items, 3, 2)],
            ['label' => 'Family & account', 'items' => array_slice($items, 5)],
        ];
    }

    public static function context(?string $routeName): array
    {
        foreach (self::groups() as $group) {
            foreach ($group['items'] as $item) {
                foreach ($item[3] as $pattern) {
                    if (Str::is($pattern, $routeName ?? '')) return ['title' => $item[0], 'group' => $group['label']];
                }
            }
        }
        return ['title' => 'Parent portal', 'group' => 'Family'];
    }

    public static function selectionKey(User $parent): string
    {
        return 'parent_selected_child.'.$parent->id;
    }

    /** Choose only from the authenticated parent's already-authorized children. */
    public static function selectChild(Collection $children, Request $request): ?User
    {
        $key = self::selectionKey($request->user());
        $id = $request->query('child_id', $request->session()->get($key));
        $selected = is_scalar($id) ? $children->firstWhere('id', (string) $id) : null;
        $selected ??= $children->first();
        if ($selected) $request->session()->put($key, $selected->id);
        else $request->session()->forget($key);
        return $selected;
    }

    public static function url(string $destination, ?int $childId = null): string
    {
        $withChild = in_array($destination, ['parent.dashboard', 'parent.attendance.calendar', 'parent.schedule', 'parent.calendar'], true);
        return route($destination, $withChild && $childId ? ['child_id' => $childId] : []);
    }

    public static function searchItems(?int $childId = null): array
    {
        return array_map(fn ($item) => [
            'id' => $item[1], 'label' => $item[0], 'hint' => $item[5], 'icon' => $item[2],
            'category' => 'navigation', 'badge' => 'Parent', 'color' => '#cfa46f',
            'bg' => 'rgba(207,164,111,0.12)', 'url' => self::url($item[1], $childId),
        ], self::items());
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
