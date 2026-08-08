<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
        <div class="sidebar-portal">Admin HQ</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<div class="sidebar-nav">
    <!-- Overview -->
    <div class="sidebar-section-label">Overview</div>
    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i> <span class="nav-link-text">Dashboard</span>
    </a>
    
    <!-- People -->
    <div class="sidebar-section-label">People</div>
    <a href="{{ route('admin.students') }}" class="nav-link {{ request()->routeIs('admin.student*') ? 'active' : '' }}">
        <i class="bi bi-people-fill"></i> <span class="nav-link-text">Students</span>
    </a>
    <a href="{{ route('admin.teachers') }}" class="nav-link {{ request()->routeIs('admin.teacher*') ? 'active' : '' }}">
        <i class="bi bi-person-workspace"></i> <span class="nav-link-text">Instructors</span>
    </a>
    <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles*') ? 'active' : '' }}">
        <i class="bi bi-shield-lock-fill"></i> <span class="nav-link-text">User Mgmt</span>
    </a>
    
    <!-- Academics -->
    <div class="sidebar-section-label">Academics</div>
    <a href="{{ route('admin.departments.index') }}" class="nav-link {{ request()->routeIs('admin.departments*') ? 'active' : '' }}">
        <i class="bi bi-building"></i> <span class="nav-link-text">Departments</span>
    </a>
    <a href="{{ route('admin.courses.index') }}" class="nav-link {{ request()->routeIs('admin.courses*') ? 'active' : '' }}">
        <i class="bi bi-mortarboard-fill"></i> <span class="nav-link-text">Courses</span>
    </a>
    <a href="{{ route('admin.sections.index') }}" class="nav-link {{ request()->routeIs('admin.sections*') ? 'active' : '' }}">
        <i class="bi bi-diagram-3-fill"></i> <span class="nav-link-text">Sections</span>
    </a>
    <a href="{{ route('admin.subjects') }}" class="nav-link {{ request()->routeIs('admin.subject*') ? 'active' : '' }}">
        <i class="bi bi-book-fill"></i> <span class="nav-link-text">Subjects</span>
    </a>
    <a href="{{ route('admin.class-schedules.index') }}" class="nav-link {{ request()->routeIs('admin.class-schedules*') ? 'active' : '' }}">
        <i class="bi bi-calendar-range"></i> <span class="nav-link-text">Schedules</span>
    </a>
    
    <!-- Attendance & Ops -->
    <div class="sidebar-section-label">Operations</div>
    <a href="{{ route('admin.attendance') }}" class="nav-link {{ request()->routeIs('admin.attendance') ? 'active' : '' }}">
        <i class="bi bi-clipboard-check-fill"></i> <span class="nav-link-text">Attendance</span>
    </a>
    <a href="{{ route('admin.early-warnings') }}" class="nav-link {{ request()->routeIs('admin.early-warnings') ? 'active' : '' }}">
        <i class="bi bi-exclamation-triangle-fill text-warning"></i> <span class="nav-link-text">Early Warnings</span>
    </a>
    <a href="{{ route('admin.qr') }}" class="nav-link {{ request()->routeIs('admin.qr*') ? 'active' : '' }}">
        <i class="bi bi-qr-code"></i> <span class="nav-link-text">QR Management</span>
    </a>
    <a href="{{ route('admin.attendance.pdf') }}" class="nav-link">
        <i class="bi bi-file-earmark-bar-graph-fill"></i> <span class="nav-link-text">Reports</span>
    </a>
    
    <!-- Communication -->
    <div class="sidebar-section-label">Communication</div>
    <a href="{{ route('admin.calendar') }}" class="nav-link {{ request()->routeIs('admin.calendar*') ? 'active' : '' }}">
        <i class="bi bi-calendar-event-fill"></i> <span class="nav-link-text">Calendar</span>
    </a>
    <a href="{{ route('admin.announcements.index') }}" class="nav-link {{ request()->routeIs('admin.announcements*') ? 'active' : '' }}">
        <i class="bi bi-megaphone-fill"></i> <span class="nav-link-text">Announcements</span>
    </a>

    <a href="{{ route('admin.excuses') }}" class="nav-link {{ request()->routeIs('admin.excuses') ? 'active' : '' }}">
        <i class="bi bi-file-text-fill"></i> <span class="nav-link-text">Excuse Reviews</span>
    </a>
    
    <!-- System -->
    <div class="sidebar-section-label">System</div>
    <a href="{{ route('admin.system-health.index') }}" class="nav-link {{ request()->routeIs('admin.system-health.index') ? 'active' : '' }}">
        <i class="bi bi-heart-pulse-fill"></i> <span class="nav-link-text">System Health</span>
    </a>
    <a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
        <i class="bi bi-gear-fill"></i> <span class="nav-link-text">Settings</span>
    </a>
</div>
