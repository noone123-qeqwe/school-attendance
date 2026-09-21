<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<div class="sidebar-nav">
    {{-- Dashboard --}}
    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" data-title="Dashboard">
        <i class="bi bi-grid-fill"></i> <span class="nav-link-text">Dashboard</span>
    </a>

    {{-- People --}}
    <a href="{{ route('admin.students') }}" class="nav-link {{ request()->routeIs('admin.student*') ? 'active' : '' }}" data-title="Students">
        <i class="bi bi-person-fill"></i> <span class="nav-link-text">Students</span>
    </a>
    <a href="{{ route('admin.teachers') }}" class="nav-link {{ request()->routeIs('admin.teacher*') ? 'active' : '' }}" data-title="Instructors">
        <i class="bi bi-person-badge-fill"></i> <span class="nav-link-text">Instructors</span>
    </a>

    {{-- Academics --}}
    <a href="{{ route('admin.academic-years.index') }}" class="nav-link {{ request()->routeIs('admin.academic-years*') ? 'active' : '' }}" data-title="Academic Terms">
        <i class="bi bi-calendar3"></i> <span class="nav-link-text">Academic Terms</span>
    </a>
    <a href="{{ route('admin.departments.index') }}" class="nav-link {{ request()->routeIs('admin.departments*') ? 'active' : '' }}" data-title="Departments">
        <i class="bi bi-building"></i> <span class="nav-link-text">Departments</span>
    </a>
    <a href="{{ route('admin.courses.index') }}" class="nav-link {{ request()->routeIs('admin.courses*') ? 'active' : '' }}" data-title="Courses">
        <i class="bi bi-book-fill"></i> <span class="nav-link-text">Courses</span>
    </a>
    <a href="{{ route('admin.sections.index') }}" class="nav-link {{ request()->routeIs('admin.sections*') ? 'active' : '' }}" data-title="Sections">
        <i class="bi bi-diagram-3-fill"></i> <span class="nav-link-text">Sections</span>
    </a>
    <a href="{{ route('admin.subjects') }}" class="nav-link {{ request()->routeIs('admin.subject*') ? 'active' : '' }}" data-title="Subjects">
        <i class="bi bi-journals"></i> <span class="nav-link-text">Subjects</span>
    </a>
    <a href="{{ route('admin.class-schedules.index') }}" class="nav-link {{ request()->routeIs('admin.class-schedules*') ? 'active' : '' }}" data-title="Schedules">
        <i class="bi bi-clock-fill"></i> <span class="nav-link-text">Schedules</span>
    </a>

    {{-- Calendar --}}
    <a href="{{ route('admin.calendar') }}" class="nav-link {{ request()->routeIs('admin.calendar*') ? 'active' : '' }}" data-title="Calendar">
        <i class="bi bi-calendar-event-fill"></i> <span class="nav-link-text">Calendar</span>
    </a>

    {{-- Operations --}}
    <a href="{{ route('admin.attendance') }}" class="nav-link {{ request()->routeIs('admin.attendance*') ? 'active' : '' }}" data-title="Attendance Logs">
        <i class="bi bi-clipboard2-check-fill"></i> <span class="nav-link-text">Attendance Logs</span>
    </a>
    <a href="{{ route('admin.reports') }}" class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" data-title="Reports">
        <i class="bi bi-bar-chart-fill"></i> <span class="nav-link-text">Reports &amp; Analytics</span>
    </a>
    <a href="{{ route('admin.early-warnings') }}" class="nav-link {{ request()->routeIs('admin.early-warnings*') ? 'active' : '' }}" data-title="Early Warnings">
        <i class="bi bi-exclamation-triangle-fill"></i> <span class="nav-link-text">Early Warnings</span>
    </a>
    <a href="{{ route('admin.excuses') }}" class="nav-link {{ request()->routeIs('admin.excuses*') ? 'active' : '' }}" data-title="Excuse Reviews">
        <i class="bi bi-file-earmark-check-fill"></i> <span class="nav-link-text">Excuse Reviews</span>
    </a>
    <a href="{{ route('admin.corrections') }}" class="nav-link {{ request()->routeIs('admin.corrections*') ? 'active' : '' }}" data-title="Correction Requests">
        <i class="bi bi-pencil-square"></i> <span class="nav-link-text">Correction Requests</span>
    </a>
    <a href="{{ route('admin.announcements.index') }}" class="nav-link {{ request()->routeIs('admin.announcements*') ? 'active' : '' }}" data-title="Announcements">
        <i class="bi bi-megaphone-fill"></i> <span class="nav-link-text">Announcements</span>
    </a>

    {{-- System --}}
    @if(Auth::user()->isSuperAdmin())
    <a href="{{ route('admin.system-update.index') }}" class="nav-link {{ (request()->routeIs('admin.system-update*') || request()->routeIs('admin.backups*') || request()->routeIs('admin.system-health*')) ? 'active' : '' }}" data-title="System Maintenance">
        <i class="bi bi-tools"></i> <span class="nav-link-text">System Maintenance</span>
    </a>
    @endif
    <a href="{{ route('admin.policies.edit') }}" class="nav-link {{ request()->routeIs('admin.policies*') ? 'active' : '' }}" data-title="Privacy & Terms">
        <i class="bi bi-shield-lock-fill"></i> <span class="nav-link-text">Privacy &amp; Terms</span>
    </a>
    <a href="{{ route('admin.activity.log') }}" class="nav-link {{ request()->routeIs('admin.activity.log') ? 'active' : '' }}" data-title="Audit Logs">
        <i class="bi bi-list-check"></i> <span class="nav-link-text">Audit Logs</span>
    </a>
    <a href="{{ route('admin.profile') }}" class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}" data-title="Profile">
        <i class="bi bi-person-circle"></i> <span class="nav-link-text">Biometric &amp; Profile</span>
    </a>
</div>

<style>
.sidebar {
    height: 100vh !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    background: rgba(74, 12, 12, 0.95) !important;
}
.sidebar-head {
    flex-shrink: 0 !important;
    position: relative !important;
    z-index: 20 !important;
    background: rgba(74, 12, 12, 0.98) !important;
    padding: 24px 16px 16px !important;
}
.sidebar-divider {
    height: 1px !important;
    background: rgba(255, 255, 255, 0.08) !important;
    margin: 0 16px 6px !important;
    flex-shrink: 0 !important;
    position: relative !important;
    z-index: 20 !important;
}
.sidebar-nav {
    flex: 1 1 auto !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    overscroll-behavior: contain !important;
    scrollbar-width: thin;
    scrollbar-color: rgba(212, 175, 55, 0.25) transparent;
    padding: 6px 0 80px 0 !important;
    display: flex;
    flex-direction: column;
    gap: 2px;
    position: relative !important;
    z-index: 5 !important;
}
.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}
.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}
.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(212, 175, 55, 0.2);
    border-radius: 99px;
}
.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(212, 175, 55, 0.4);
}

/* Nav Links */
.sidebar .nav-link {
    box-sizing: border-box !important;
    width: calc(100% - 24px) !important;
    margin: 2px 12px !important;
    padding: 11px 14px !important;
    display: flex !important;
    align-items: center !important;
    border-radius: 12px !important;
    position: relative;
    border: none;
    background: transparent;
    cursor: pointer;
    text-decoration: none;
    color: var(--text-secondary, #D1C5B4);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
