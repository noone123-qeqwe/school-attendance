<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
        <div class="sidebar-portal">Teacher Panel</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<div class="sidebar-nav">
    <a href="{{ route('teacher.dashboard') }}" class="nav-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span class="nav-link-text">Dashboard</span>
    </a>

    <div class="sidebar-section-label">Account</div>
    <a href="{{ route('teacher.profile') }}" class="nav-link {{ request()->routeIs('teacher.profile') ? 'active' : '' }}">
        <i class="bi bi-person-fill"></i>
        <span class="nav-link-text">My Profile</span>
    </a>
    <a href="{{ route('teacher.excuses') }}" class="nav-link {{ request()->routeIs('teacher.excuses*') ? 'active' : '' }}">
        <i class="bi bi-calendar2-x-fill"></i>
        <span class="nav-link-text">My Excuses</span>
    </a>

    <div class="sidebar-section-label">Academics</div>
    <a href="{{ route('teacher.classroom.index') }}" class="nav-link {{ request()->routeIs('teacher.classroom*') ? 'active' : '' }}">
        <i class="bi bi-journal-album"></i>
        <span class="nav-link-text">My Classes</span>
    </a>

    <div class="sidebar-section-label">Attendance Management</div>
    <a href="{{ route('teacher.absent') }}" class="nav-link {{ request()->routeIs('teacher.absent*') ? 'active' : '' }}">
        <i class="bi bi-person-x-fill"></i>
        <span class="nav-link-text">Absent Report</span>
    </a>
    <a href="{{ route('teacher.excuse.reviews') }}" class="nav-link {{ request()->routeIs('teacher.excuse.*') ? 'active' : '' }}">
        <i class="bi bi-file-text-fill"></i>
        <span class="nav-link-text">Excuse Reviews</span>
    </a>

    <div class="sidebar-section-label">Communication & Insight</div>
    <a href="{{ route('teacher.calendar') }}" class="nav-link {{ request()->routeIs('teacher.calendar*') ? 'active' : '' }}">
        <i class="bi bi-calendar-event"></i>
        <span class="nav-link-text">My Calendar</span>
    </a>
    <a href="{{ route('teacher.notifications') }}" class="nav-link {{ request()->routeIs('teacher.notifications*') ? 'active' : '' }}">
        <i class="bi bi-bell-fill"></i>
        <span class="nav-link-text">Notifications</span>
    </a>
</div>
