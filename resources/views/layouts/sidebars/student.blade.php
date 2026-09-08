<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<div class="sidebar-nav" style="display: flex; flex-direction: column; gap: 4px;">
    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span class="nav-link-text">Dashboard</span>
    </a>
    <a href="{{ route('student.schedule') }}" class="nav-link {{ request()->routeIs('student.schedule') ? 'active' : '' }}">
        <i class="bi bi-calendar2-week-fill"></i>
        <span class="nav-link-text">My Schedule</span>
    </a>
    <a href="{{ route('student.attendance.calendar') }}" class="nav-link {{ request()->routeIs('student.attendance.calendar') ? 'active' : '' }}">
        <i class="bi bi-calendar-check-fill"></i>
        <span class="nav-link-text">Attendance Calendar</span>
    </a>
    <a href="{{ route('student.classes') }}" class="nav-link {{ request()->routeIs('student.classes') ? 'active' : '' }}">
        <i class="bi bi-book-fill"></i>
        <span class="nav-link-text">My Classes</span>
    </a>
    <a href="{{ route('attendance.records') }}" class="nav-link {{ request()->routeIs('attendance.records') ? 'active' : '' }}">
        <i class="bi bi-clipboard-data-fill"></i>
        <span class="nav-link-text">Attendance Records</span>
    </a>
    <a href="{{ route('student.calendar') }}" class="nav-link {{ request()->routeIs('student.calendar') ? 'active' : '' }}">
        <i class="bi bi-calendar-event-fill"></i>
        <span class="nav-link-text">School Calendar</span>
    </a>
    <a href="{{ route('excuses') }}" class="nav-link {{ request()->routeIs('excuses*') ? 'active' : '' }}">
        <i class="bi bi-file-text-fill"></i>
        <span class="nav-link-text">Excuse Submissions</span>
    </a>
    <a href="{{ route('settings') }}" class="nav-link {{ request()->routeIs('settings*') ? 'active' : '' }}">
        <i class="bi bi-gear-fill"></i>
        <span class="nav-link-text">Settings</span>
    </a>
</div>

