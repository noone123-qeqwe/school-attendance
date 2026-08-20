<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
        <div class="sidebar-portal">Student Portal</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<div class="sidebar-nav">
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

    <a href="{{ route('student.calendar') }}" class="nav-link {{ request()->routeIs('student.calendar') ? 'active' : '' }}">
        <i class="bi bi-calendar-event-fill"></i>
        <span class="nav-link-text">School Calendar</span>
    </a>
    <a href="{{ route('excuses') }}" class="nav-link {{ request()->routeIs('excuses*') ? 'active' : '' }}">
        <i class="bi bi-file-text-fill"></i>
        <span class="nav-link-text">Excuse Submissions</span>
    </a>
</div>
