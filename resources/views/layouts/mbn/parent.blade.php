<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav" aria-label="Parent navigation">
    <a href="{{ route('parent.dashboard') }}" class="mbn-item {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}" data-parent-child-context>
        <i class="bi bi-house-door-fill"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('parent.attendance.calendar') }}" class="mbn-item {{ request()->routeIs('parent.attendance.calendar', 'parent.child', 'parent.child.warnings') ? 'active' : '' }}" data-parent-child-context>
        <i class="bi bi-calendar-check-fill"></i>
        <span>Attendance</span>
    </a>
    <a href="{{ route('parent.schedule') }}" class="mbn-item {{ request()->routeIs('parent.schedule') ? 'active' : '' }}" data-parent-child-context>
        <i class="bi bi-clock-fill"></i>
        <span>Schedule</span>
    </a>

    <a href="{{ route('parent.notifications') }}" id="parentMobileAlertsLink" class="mbn-item {{ request()->routeIs('parent.notifications') ? 'active' : '' }}">
        <span class="mbn-badge" data-parent-unread-count @if($parentUnreadCount === 0) hidden @endif aria-label="{{ $parentUnreadCount }} unread alerts">{{ $parentUnreadCount > 9 ? '9+' : $parentUnreadCount }}</span>
        <i class="bi bi-bell-fill"></i>
        <span>Alerts</span>
    </a>
    <button type="button" class="mbn-item mbn-item-more" onclick="openMoreSheet()" aria-label="Open parent navigation" aria-haspopup="dialog" aria-controls="moreSheet" aria-expanded="false" style="background:transparent;border:none;outline:none;cursor:pointer;">
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <span>More</span>
    </button>
</nav>
