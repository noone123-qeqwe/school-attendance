<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
        <div class="sidebar-portal">Instructor Portal</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<div class="sidebar-nav">
    <a href="{{ route('teacher.dashboard') }}" class="nav-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span class="nav-link-text">Dashboard</span>
    </a>
    <a href="{{ route('teacher.classroom.index') }}" class="nav-link {{ request()->routeIs('teacher.classroom*') || request()->routeIs('teacher.subjects*') ? 'active' : '' }}">
        <i class="bi bi-folder-fill"></i>
        <span class="nav-link-text">My Classes</span>
    </a>
    <a href="{{ route('teacher.calendar') }}" class="nav-link {{ request()->routeIs('teacher.calendar*') ? 'active' : '' }}">
        <i class="bi bi-calendar-event-fill"></i>
        <span class="nav-link-text">School Calendar</span>
    </a>
    <a href="{{ route('teacher.excuse.reviews') }}" class="nav-link {{ request()->routeIs('teacher.excuse*') ? 'active' : '' }}">
        <i class="bi bi-file-text-fill"></i>
        <span class="nav-link-text">Excuse Submissions</span>
    </a>
</div>
