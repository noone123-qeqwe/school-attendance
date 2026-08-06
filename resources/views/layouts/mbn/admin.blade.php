<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav">
    <a href="{{ route('admin.dashboard') }}" class="mbn-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('admin.students') }}" class="mbn-item {{ request()->routeIs('admin.student*') ? 'active' : '' }}">
        <i class="bi bi-people-fill"></i>
        <span>Students</span>
    </a>
    <a href="{{ route('admin.attendance') }}" class="mbn-item {{ request()->routeIs('admin.attendance') ? 'active' : '' }}">
        <i class="bi bi-clipboard-check-fill"></i>
        <span>Attendance</span>
    </a>
    <a href="{{ route('admin.qr') }}" class="mbn-item {{ request()->routeIs('admin.qr*') ? 'active' : '' }}">
        <i class="bi bi-qr-code"></i>
        <span>QR</span>
    </a>
    <a href="{{ route('admin.settings') }}" class="mbn-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
        <i class="bi bi-gear-fill"></i>
        <span>Settings</span>
    </a>
</nav>
