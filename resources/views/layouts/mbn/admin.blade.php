@php($adminArea = \App\Support\AdminNavigation::context(auth()->user(), request()->route()?->getName())['group'])
<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav" aria-label="Admin navigation">
    <a href="{{ route('admin.dashboard') }}" class="mbn-item {{ $adminArea === 'Home' ? 'active' : '' }}">
        <i class="bi bi-house-door" aria-hidden="true"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('admin.students') }}" class="mbn-item {{ $adminArea === 'People' ? 'active' : '' }}">
        <i class="bi bi-people-fill"></i>
        <span>People</span>
    </a>
    <a href="{{ route('admin.class-schedules.index') }}" class="mbn-item {{ $adminArea === 'Classes' ? 'active' : '' }}">
        <i class="bi bi-calendar3" aria-hidden="true"></i>
        <span>Classes</span>
    </a>
    <a href="{{ route('admin.attendance') }}" class="mbn-item {{ $adminArea === 'Attendance' ? 'active' : '' }}">
        <i class="bi bi-clipboard-check-fill"></i>
        <span>Attendance</span>
    </a>
    <button type="button" class="mbn-item mbn-item-more {{ $adminArea === 'School & system' ? 'active' : '' }}" onclick="openMoreSheet()" aria-label="Open all admin pages" aria-haspopup="dialog" aria-controls="moreSheet" aria-expanded="false" style="background:transparent;border:none;cursor:pointer;">
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <span>Menu</span>
    </button>
</nav>
