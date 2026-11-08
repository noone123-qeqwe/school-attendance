<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav">
    <a href="{{ route('parent.dashboard') }}" class="mbn-item {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('parent.schedule') }}" class="mbn-item {{ request()->routeIs('parent.schedule') ? 'active' : '' }}">
        <i class="bi bi-clock-fill"></i>
        <span>Schedule</span>
    </a>
    <a href="{{ route('parent.calendar') }}" class="mbn-item {{ request()->routeIs('parent.calendar') ? 'active' : '' }}">
        <i class="bi bi-calendar-event"></i>
        <span>Calendar</span>
    </a>

    <a href="{{ route('parent.notifications') }}" id="parentMobileAlertsLink" class="mbn-item {{ request()->routeIs('parent.notifications') ? 'active' : '' }}">
        @php
            $pChildIds = Auth::user()->children()->pluck('users.id');
            $pNotificationRecipientIds = $pChildIds->push(Auth::id())->unique();
            $pUnreadNotifs = \App\Models\Notification::where('user_id', Auth::id())->active()->where('is_read', false)->count()
                + \App\Models\Notification::whereIn('user_id', $pChildIds)->active()->where('is_read', false)
                    ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('parent_notification_reads')
                        ->whereColumn('parent_notification_reads.notification_id', 'notifications.id')
                        ->where('parent_notification_reads.parent_id', Auth::id()))->count();
        @endphp
        @if($pUnreadNotifs > 0)<span class="mbn-badge">{{ $pUnreadNotifs > 9 ? '9+' : $pUnreadNotifs }}</span>@endif
        <i class="bi bi-bell-fill"></i>
        <span>Alerts</span>
    </a>
    <button type="button" class="mbn-item mbn-item-more" onclick="openMoreSheet()" aria-label="Open More Menu" style="background:transparent;border:none;outline:none;cursor:pointer;">
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <span>More</span>
    </button>
</nav>
