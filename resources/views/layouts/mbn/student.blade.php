<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav">
    <a href="{{ route('home') }}" class="mbn-item {{ request()->routeIs('home') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('student.classes') }}" class="mbn-item {{ request()->routeIs('student.classes') ? 'active' : '' }}">
        <i class="bi bi-folder-fill"></i>
        <span>Classes</span>
    </a>
    <a href="{{ route('student.schedule') }}" class="mbn-item {{ request()->routeIs('student.schedule') ? 'active' : '' }}">
        <i class="bi bi-calendar-range-fill"></i>
        <span>Schedule</span>
    </a>
    <a href="{{ route('excuses') }}" class="mbn-item {{ request()->routeIs('excuses*') ? 'active' : '' }}">
        <i class="bi bi-file-text-fill"></i>
        <span>Excuses</span>
    </a>
    <a href="{{ route('student.calendar') }}" class="mbn-item {{ request()->routeIs('student.calendar') ? 'active' : '' }}">
        <i class="bi bi-calendar-event-fill"></i>
        <span>Calendar</span>
    </a>
    <a href="{{ route('settings') }}" class="mbn-item {{ request()->routeIs('settings') ? 'active' : '' }}">
        <i class="bi bi-person-fill"></i>
        <span>Profile</span>
    </a>
</nav>
