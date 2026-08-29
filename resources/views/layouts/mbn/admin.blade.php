<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav">
    <a href="{{ route('admin.dashboard') }}" class="mbn-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('admin.students') }}" class="mbn-item {{ request()->routeIs('admin.student*') ? 'active' : '' }}">
        <i class="bi bi-people-fill"></i>
        <span>Students</span>
    </a>
    <a href="{{ route('admin.qr') }}" class="mbn-item mbn-item-featured {{ request()->routeIs('admin.qr*') ? 'active' : '' }}" aria-label="QR Center">
        <div class="mbn-featured-btn">
            <i class="bi bi-qr-code"></i>
        </div>
        <span>QR</span>
    </a>
    <a href="{{ route('admin.attendance') }}" class="mbn-item {{ request()->routeIs('admin.attendance') ? 'active' : '' }}">
        <i class="bi bi-clipboard-check-fill"></i>
        <span>Attendance</span>
    </a>
    <button type="button" class="mbn-item mbn-item-more" onclick="openMoreSheet()" aria-label="Open More Menu" style="background:transparent;border:none;outline:none;cursor:pointer;">
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <span>More</span>
    </button>
</nav>
