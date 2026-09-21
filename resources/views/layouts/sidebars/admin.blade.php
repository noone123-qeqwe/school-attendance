<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="Logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<div class="sidebar-nav">

    {{-- ── OVERVIEW ── --}}
    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" data-title="Dashboard">
        <i class="bi bi-grid-fill"></i>
        <span class="nav-link-text">Dashboard</span>
    </a>
    <a href="{{ route('admin.calendar') }}" class="nav-link {{ request()->routeIs('admin.calendar*') ? 'active' : '' }}" data-title="Calendar">
        <i class="bi bi-calendar-event-fill"></i>
        <span class="nav-link-text">Calendar</span>
    </a>
    <a href="{{ route('admin.announcements.index') }}" class="nav-link {{ request()->routeIs('admin.announcements*') ? 'active' : '' }}" data-title="Announcements">
        <i class="bi bi-megaphone-fill"></i>
        <span class="nav-link-text">Announcements</span>
    </a>

    {{-- ── PEOPLE ── --}}
    <div class="nav-section-label">People</div>
    <a href="{{ route('admin.students') }}" class="nav-link {{ request()->routeIs('admin.student*') ? 'active' : '' }}" data-title="Students">
        <i class="bi bi-person-fill"></i>
        <span class="nav-link-text">Students</span>
    </a>
    <a href="{{ route('admin.teachers') }}" class="nav-link {{ request()->routeIs('admin.teacher*') ? 'active' : '' }}" data-title="Instructors">
        <i class="bi bi-person-badge-fill"></i>
        <span class="nav-link-text">Instructors</span>
    </a>
    @if(Auth::user()->isSuperAdmin())
    <a href="{{ route('admin.admins') }}" class="nav-link {{ request()->routeIs('admin.admins*') ? 'active' : '' }}" data-title="Admins">
        <i class="bi bi-shield-fill-check"></i>
        <span class="nav-link-text">Administrators</span>
    </a>
    @endif

    {{-- ── ACADEMICS ── --}}
    <div class="nav-section-label">Academics</div>
    <a href="{{ route('admin.academic-years.index') }}" class="nav-link {{ request()->routeIs('admin.academic-years*') ? 'active' : '' }}" data-title="Academic Terms">
        <i class="bi bi-calendar3"></i>
        <span class="nav-link-text">Academic Terms</span>
    </a>
    <a href="{{ route('admin.courses.index') }}" class="nav-link {{ (request()->routeIs('admin.courses*') || request()->routeIs('admin.sections*')) ? 'active' : '' }}" data-title="Courses & Sections">
        <i class="bi bi-book-fill"></i>
        <span class="nav-link-text">Courses &amp; Sections</span>
    </a>
    <a href="{{ route('admin.subjects') }}" class="nav-link {{ (request()->routeIs('admin.subject*') || request()->routeIs('admin.class-schedules*')) ? 'active' : '' }}" data-title="Subjects & Schedules">
        <i class="bi bi-journal-bookmark-fill"></i>
        <span class="nav-link-text">Subjects &amp; Schedules</span>
    </a>

    {{-- ── SYSTEM ── --}}
    <div class="nav-section-label">System</div>
    @if(Auth::user()->isSuperAdmin())
    <a href="{{ route('admin.system-update.index') }}" class="nav-link {{ (request()->routeIs('admin.system-update*') || request()->routeIs('admin.backups*') || request()->routeIs('admin.system-health*')) ? 'active' : '' }}" data-title="System Maintenance">
        <i class="bi bi-tools"></i>
        <span class="nav-link-text">System Maintenance</span>
    </a>
    @endif
    <a href="{{ route('admin.activity.log') }}" class="nav-link {{ request()->routeIs('admin.activity.log') ? 'active' : '' }}" data-title="Audit Logs">
        <i class="bi bi-list-check"></i>
        <span class="nav-link-text">Audit Logs</span>
    </a>
    <a href="{{ route('admin.policies.edit') }}" class="nav-link {{ request()->routeIs('admin.policies*') ? 'active' : '' }}" data-title="Privacy & Terms">
        <i class="bi bi-shield-lock-fill"></i>
        <span class="nav-link-text">Privacy &amp; Terms</span>
    </a>
    <a href="{{ route('admin.profile') }}" class="nav-link {{ request()->routeIs('admin.profile') ? 'active' : '' }}" data-title="Profile">
        <i class="bi bi-person-circle"></i>
        <span class="nav-link-text">Biometric &amp; Profile</span>
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
    gap: 1px;
    position: relative !important;
    z-index: 5 !important;
}
.sidebar-nav::-webkit-scrollbar { width: 4px; }
.sidebar-nav::-webkit-scrollbar-track { background: transparent; }
.sidebar-nav::-webkit-scrollbar-thumb { background: rgba(212, 175, 55, 0.2); border-radius: 99px; }
.sidebar-nav::-webkit-scrollbar-thumb:hover { background: rgba(212, 175, 55, 0.4); }

/* Section labels */
.nav-section-label {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(212, 175, 55, 0.55);
    padding: 14px 26px 4px;
    margin-top: 4px;
    pointer-events: none;
    user-select: none;
}

/* Nav Links */
.sidebar .nav-link {
    box-sizing: border-box !important;
    width: calc(100% - 24px) !important;
    margin: 1px 12px !important;
    padding: 10px 14px !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    border-radius: 10px !important;
    position: relative;
    border: none;
    background: transparent;
    cursor: pointer;
    text-decoration: none;
    color: rgba(209, 197, 180, 0.85);
    font-size: 0.875rem;
    font-weight: 500;
    transition: background 0.18s ease, color 0.18s ease, transform 0.15s ease;
}
.sidebar .nav-link i {
    font-size: 1rem;
    flex-shrink: 0;
    opacity: 0.7;
    transition: opacity 0.18s ease;
}
.sidebar .nav-link:hover {
    background: rgba(255, 255, 255, 0.06) !important;
    color: #fff !important;
    transform: translateX(2px);
}
.sidebar .nav-link:hover i { opacity: 1; }
.sidebar .nav-link.active {
    background: linear-gradient(90deg, rgba(212,175,55,0.2) 0%, rgba(212,175,55,0.04) 100%) !important;
    color: #fff !important;
    font-weight: 700 !important;
    border-left: 2px solid var(--admin-gold, #D4AF37) !important;
    padding-left: 12px !important;
}
.sidebar .nav-link.active i { opacity: 1; color: var(--admin-gold, #D4AF37); }
</style>
