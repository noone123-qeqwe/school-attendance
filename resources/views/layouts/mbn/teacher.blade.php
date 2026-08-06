<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav">
    <a href="{{ route('teacher.dashboard') }}" class="mbn-item {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span>Dashboard</span>
    </a>
    <a href="{{ route('teacher.classroom.index') }}" class="mbn-item {{ request()->routeIs('teacher.classroom*') ? 'active' : '' }}">
        <i class="bi bi-journal-album"></i>
        <span>Classes</span>
    </a>
    <a href="{{ route('teacher.absent') }}" class="mbn-item {{ request()->routeIs('teacher.absent*') ? 'active' : '' }}">
        <i class="bi bi-person-x-fill"></i>
        <span>Absent</span>
    </a>
    <a href="{{ route('teacher.excuse.reviews') }}" class="mbn-item {{ request()->routeIs('teacher.excuse*') ? 'active' : '' }}">
        <i class="bi bi-file-text-fill"></i>
        <span>Excuses</span>
    </a>
    <a href="{{ route('teacher.profile') }}" class="mbn-item {{ request()->routeIs('teacher.profile') ? 'active' : '' }}">
        <i class="bi bi-person-fill"></i>
        <span>Profile</span>
    </a>
</nav>
