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
        $isAcademicsActive = request()->routeIs('admin.departments*') || request()->routeIs('admin.courses*') || request()->routeIs('admin.sections*') || request()->routeIs('admin.subject*') || request()->routeIs('admin.class-schedules*');
    @endphp
    <div class="sidebar-dropdown-group {{ $isAcademicsActive ? 'open' : '' }}">
        <button class="nav-link dropdown-toggle-btn" onclick="toggleSidebarDropdown(this)" data-title="Academics">
            <i class="bi bi-mortarboard-fill"></i> 
            <span class="nav-link-text">Academics</span> 
            <i class="bi bi-chevron-down ms-auto dropdown-chevron"></i>
        </button>
        <div class="sidebar-submenu">
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
        $isOperationsActive = request()->routeIs('admin.early-warnings*') || request()->routeIs('admin.announcements*') || request()->routeIs('admin.excuses*') || request()->routeIs('admin.attendance*');
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
            <a href="{{ route('admin.early-warnings') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.early-warnings*') ? 'active' : '' }}">
                <span class="nav-link-text">Early Warnings</span>
            </a>
            <a href="{{ route('admin.excuses') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.excuses*') ? 'active' : '' }}">
                <span class="nav-link-text">Excuse Reviews</span>
            </a>
            <a href="{{ route('admin.announcements.index') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.announcements*') ? 'active' : '' }}">
                <span class="nav-link-text">Announcements</span>
            </a>
        </div>
    </div>
    
    <!-- System Dropdown -->
    @php
        $isSystemActive = request()->routeIs('admin.system-health.index') || request()->routeIs('admin.settings') || request()->routeIs('admin.activity.log');
    @endphp
    <div class="sidebar-dropdown-group {{ $isSystemActive ? 'open' : '' }}">
        <button class="nav-link dropdown-toggle-btn" onclick="toggleSidebarDropdown(this)" data-title="System">
            <i class="bi bi-gear-fill"></i> 
            <span class="nav-link-text">System</span> 
            <i class="bi bi-chevron-down ms-auto dropdown-chevron"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="{{ route('admin.system-health.index') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.system-health.index') ? 'active' : '' }}">
                <span class="nav-link-text">System Health</span>
            </a>
            <a href="{{ route('admin.settings') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <span class="nav-link-text">Settings</span>
            </a>
            <a href="{{ route('admin.activity.log') }}" class="nav-link sub-nav-link {{ request()->routeIs('admin.activity.log') ? 'active' : '' }}">
                <span class="nav-link-text">Audit Logs</span>
            </a>
        </div>
    </div>
</div>

<style>
.sidebar-submenu {
    padding-left: 28px;
    margin-top: 2px;
    margin-bottom: 8px;
    display: none;
    flex-direction: column;
    gap: 4px;
}
.sidebar-submenu .sub-nav-link {
    padding: 8px 14px;
    font-size: 0.85rem;
    margin-bottom: 0;
    border-left: 2px solid rgba(255, 255, 255, 0.05);
    border-radius: 0 var(--radius-md) var(--radius-md) 0;
}
.sidebar-submenu .sub-nav-link:hover {
    border-left-color: var(--gold-soft);
    background: rgba(255, 255, 255, 0.02);
}
.sidebar-submenu .sub-nav-link.active {
    border-left-color: var(--gold);
    background: rgba(212, 175, 55, 0.05) !important;
    color: var(--gold-soft) !important;
    font-weight: 600;
}
.dropdown-chevron {
    transition: transform 0.2s ease;
    font-size: 0.8rem;
}
.sidebar-dropdown-group.open .dropdown-chevron {
    transform: rotate(180deg);
}
.sidebar-dropdown-group.open .sidebar-submenu {
    display: flex !important;
}
.dropdown-toggle-btn {
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
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
