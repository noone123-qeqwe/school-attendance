<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<div class="sidebar-nav">
    <!-- Overview -->
    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" data-title="Dashboard">
        <i class="bi bi-grid-fill"></i> <span class="nav-link-text">Dashboard</span>
    </a>
    
    <!-- People Dropdown -->
    @php
        $isPeopleActive = request()->routeIs('admin.student*') || request()->routeIs('admin.teacher*');
    @endphp
    <div class="sidebar-dropdown-group {{ $isPeopleActive ? 'open' : '' }}">
        <button class="nav-link dropdown-toggle-btn" onclick="toggleSidebarDropdown(this)" data-title="People">
            <i class="bi bi-people-fill"></i> 
            <span class="nav-link-text">People</span> 
            <i class="bi bi-chevron-down ms-auto dropdown-chevron"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="{{ route('admin.students') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.student*') ? 'active' : '' }}">
                <span class="nav-link-text">Students</span>
            </a>
            <a href="{{ route('admin.teachers') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.teacher*') ? 'active' : '' }}">
                <span class="nav-link-text">Instructors</span>
            </a>
        </div>
    </div>
    
    <!-- Academics Dropdown -->
    @php
        $isAcademicsActive = request()->routeIs('admin.departments*') || request()->routeIs('admin.courses*') || request()->routeIs('admin.sections*') || request()->routeIs('admin.subject*') || request()->routeIs('admin.class-schedules*') || request()->routeIs('admin.academic-years*');
    @endphp
    <div class="sidebar-dropdown-group {{ $isAcademicsActive ? 'open' : '' }}">
        <button class="nav-link dropdown-toggle-btn" onclick="toggleSidebarDropdown(this)" data-title="Academics">
            <i class="bi bi-mortarboard-fill"></i> 
            <span class="nav-link-text">Academics</span> 
            <i class="bi bi-chevron-down ms-auto dropdown-chevron"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="{{ route('admin.academic-years.index') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.academic-years*') ? 'active' : '' }}">
                <span class="nav-link-text">Academic Terms</span>
            </a>
            <a href="{{ route('admin.departments.index') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.departments*') ? 'active' : '' }}">
                <span class="nav-link-text">Departments</span>
            </a>
            <a href="{{ route('admin.courses.index') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.courses*') ? 'active' : '' }}">
                <span class="nav-link-text">Courses</span>
            </a>
            <a href="{{ route('admin.sections.index') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.sections*') ? 'active' : '' }}">
                <span class="nav-link-text">Sections</span>
            </a>
            <a href="{{ route('admin.subjects') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.subject*') ? 'active' : '' }}">
                <span class="nav-link-text">Subjects</span>
            </a>
            <a href="{{ route('admin.class-schedules.index') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.class-schedules*') ? 'active' : '' }}">
                <span class="nav-link-text">Schedules</span>
            </a>
        </div>
    </div>
    
    <!-- Calendar (Dedicated) -->
    <a href="{{ route('admin.calendar') }}" class="nav-link {{ request()->routeIs('admin.calendar*') ? 'active' : '' }}" data-title="Calendar">
        <i class="bi bi-calendar-event-fill"></i> <span class="nav-link-text">Calendar</span>
    </a>
    
    <!-- Operations Dropdown -->
    @php
        $isOperationsActive = request()->routeIs('admin.reports*') || request()->routeIs('admin.early-warnings*') || request()->routeIs('admin.announcements*') || request()->routeIs('admin.excuses*') || request()->routeIs('admin.corrections*') || request()->routeIs('admin.attendance*');
    @endphp
    <div class="sidebar-dropdown-group {{ $isOperationsActive ? 'open' : '' }}">
        <button class="nav-link dropdown-toggle-btn" onclick="toggleSidebarDropdown(this)" data-title="Operations">
            <i class="bi bi-shield-check"></i> 
            <span class="nav-link-text">Operations</span> 
            <i class="bi bi-chevron-down ms-auto dropdown-chevron"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="{{ route('admin.attendance') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.attendance*') ? 'active' : '' }}">
                <span class="nav-link-text">Attendance Logs</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                <span class="nav-link-text">Reports & Analytics</span>
            </a>
            <a href="{{ route('admin.early-warnings') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.early-warnings*') ? 'active' : '' }}">
                <span class="nav-link-text">Early Warnings</span>
            </a>
            <a href="{{ route('admin.excuses') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.excuses*') ? 'active' : '' }}">
                <span class="nav-link-text">Excuse Reviews</span>
            </a>
            <a href="{{ route('admin.corrections') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.corrections*') ? 'active' : '' }}">
                <span class="nav-link-text">Correction Requests</span>
            </a>
            <a href="{{ route('admin.announcements.index') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.announcements*') ? 'active' : '' }}">
                <span class="nav-link-text">Announcements</span>
            </a>
        </div>
    </div>
    
    <!-- System Dropdown -->
    @php
        $isSystemActive = request()->routeIs('admin.system-update*') || request()->routeIs('admin.backups*') || request()->routeIs('admin.system-health*') || request()->routeIs('admin.activity.log');
    @endphp
    <div class="sidebar-dropdown-group {{ $isSystemActive ? 'open' : '' }}">
        <button class="nav-link dropdown-toggle-btn" onclick="toggleSidebarDropdown(this)" data-title="System">
            <i class="bi bi-gear-fill"></i> 
            <span class="nav-link-text">System</span> 
            <i class="bi bi-chevron-down ms-auto dropdown-chevron"></i>
        </button>
        <div class="sidebar-submenu">
            @if(Auth::user()->admin_sub_role === 'super_admin')
            <a href="{{ route('admin.system-update.index') }}" class="nav-link sub-nav-link {{ (request()->routeIs('admin.system-update*') || request()->routeIs('admin.backups*') || request()->routeIs('admin.system-health*')) ? 'active' : '' }}">
                <span class="nav-link-text">System Maintenance</span>
            </a>
            @endif
            <a href="{{ route('admin.activity.log') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.activity.log') ? 'active' : '' }}">
                <span class="nav-link-text">Audit Logs</span>
            </a>
        </div>
    </div>
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

/* Nav Links & Dropdown Toggle Buttons */
.sidebar .nav-link,
.dropdown-toggle-btn {
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
.dropdown-toggle-btn {
    justify-content: flex-start;
}
.dropdown-chevron {
    margin-left: auto;
    font-size: 0.75rem;
    transition: transform 0.25s ease;
    color: rgba(255, 255, 255, 0.4);
    flex-shrink: 0;
}
.sidebar-dropdown-group.open .dropdown-chevron {
    transform: rotate(180deg);
    color: var(--admin-gold, #D4AF37);
}

/* Submenu Container */
.sidebar-submenu {
    display: none;
    flex-direction: column;
    gap: 2px;
    margin: 2px 12px 8px 24px !important;
    padding: 4px 0 4px 12px !important;
    border-left: 2px solid rgba(212, 175, 55, 0.25);
    box-sizing: border-box !important;
}
.sidebar-dropdown-group.open .sidebar-submenu {
    display: flex !important;
    animation: submenuFadeIn 0.2s ease;
}
@keyframes submenuFadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Submenu Links */
.sidebar-submenu .sub-nav-link {
    margin: 1px 0 !important;
    padding: 8px 12px !important;
    width: 100% !important;
    box-sizing: border-box !important;
    font-size: 0.84rem !important;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.75) !important;
    border-radius: 8px !important;
    border: none !important;
    background: transparent !important;
    display: flex !important;
    align-items: center !important;
    transform: none !important;
    text-decoration: none;
}
.sidebar-submenu .sub-nav-link:hover {
    background: rgba(255, 255, 255, 0.06) !important;
    color: #fff !important;
    transform: translateX(2px) !important;
}
.sidebar-submenu .sub-nav-link.active {
    background: linear-gradient(90deg, rgba(212, 175, 55, 0.2) 0%, rgba(212, 175, 55, 0.04) 100%) !important;
    color: #fff !important;
    font-weight: 700 !important;
    border-left: 2px solid var(--admin-gold, #D4AF37) !important;
}
.sidebar-submenu .sub-nav-link .nav-link-text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar.collapsed .dropdown-chevron {
    display: none !important;
}
.sidebar.collapsed .sidebar-submenu {
    display: none !important;
}
</style>

<script>
function toggleSidebarDropdown(btn) {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && sidebar.classList.contains('collapsed')) {
        return;
    }
    
    const group = btn.closest('.sidebar-dropdown-group');
    if (group) {
        group.classList.toggle('open');
    }
}
</script>
