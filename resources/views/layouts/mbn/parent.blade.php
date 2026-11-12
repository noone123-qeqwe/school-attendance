<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav" aria-label="Parent navigation">
    <a href="{{ route('parent.dashboard') }}" class="mbn-item {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}" @if(request()->routeIs('parent.dashboard')) aria-current="page" @endif data-parent-child-context>
        <i class="bi bi-house-door-fill" aria-hidden="true"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('parent.calendar') }}" class="mbn-item {{ request()->routeIs('parent.calendar*', 'parent.attendance.calendar', 'parent.child', 'parent.child.warnings') ? 'active' : '' }}" @if(request()->routeIs('parent.calendar*', 'parent.attendance.calendar', 'parent.child', 'parent.child.warnings')) aria-current="page" @endif data-parent-child-context>
        <i class="bi bi-calendar-check-fill" aria-hidden="true"></i>
        <span>Calendar</span>
    </a>
    <a href="{{ route('parent.notifications') }}" id="parentMobileAlertsLink" class="mbn-item {{ request()->routeIs('parent.notifications') ? 'active' : '' }}" @if(request()->routeIs('parent.notifications')) aria-current="page" @endif>
        <span class="mbn-badge" data-parent-unread-count @if($parentUnreadCount === 0) hidden @endif aria-label="{{ $parentUnreadCount }} unread alerts">{{ $parentUnreadCount > 9 ? '9+' : $parentUnreadCount }}</span>
        <i class="bi bi-bell-fill" aria-hidden="true"></i>
        <span>Alerts</span>
    </a>
    <button type="button" class="mbn-item mbn-item-more {{ request()->routeIs('parent.schedule', 'parent.excuse*', 'parent.child.excuse', 'parent.link*', 'parent.profile') ? 'active' : '' }}" onclick="openMoreSheet()" aria-label="Open all guardian pages" aria-haspopup="dialog" aria-controls="moreSheet" aria-expanded="false" style="background:transparent;border:none;cursor:pointer;">
        <i class="bi bi-list" aria-hidden="true"></i>
        <span>Menu</span>
    </button>
</nav>
