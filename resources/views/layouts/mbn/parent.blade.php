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

    <a href="{{ route('parent.notifications') }}" class="mbn-item {{ request()->routeIs('parent.notifications') ? 'active' : '' }}">
        @php
            $pChildIds = Auth::user()->children()->pluck('users.id');
            $pUnreadNotifs = \App\Models\Notification::whereIn('user_id', $pChildIds)->where('is_read', false)->count();
        @endphp
        @if($pUnreadNotifs > 0)<span class="mbn-badge">{{ $pUnreadNotifs > 9 ? '9+' : $pUnreadNotifs }}</span>@endif
        <i class="bi bi-bell-fill"></i>
        <span>Alerts</span>
    </a>
    <a href="javascript:void(0)" class="mbn-item" onclick="openMoreSheet()">
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <span>More</span>
    </a>
</nav>
