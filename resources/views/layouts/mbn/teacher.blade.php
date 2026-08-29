<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav">
    <a href="{{ route('teacher.dashboard') }}" class="mbn-item {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('teacher.classroom.index') }}" class="mbn-item {{ request()->routeIs('teacher.classroom*') ? 'active' : '' }}">
        <i class="bi bi-journal-album"></i>
        <span>Classes</span>
    </a>
    <a href="{{ route('teacher.subjects') }}" class="mbn-item mbn-item-featured {{ request()->routeIs('teacher.subjects*') || request()->routeIs('teacher.qr*') ? 'active' : '' }}" aria-label="Start QR Attendance Session">
        <div class="mbn-featured-btn">
            <i class="bi bi-qr-code-scan"></i>
        </div>
        <span>QR</span>
    </a>
    <a href="{{ route('teacher.absent') }}" class="mbn-item {{ request()->routeIs('teacher.absent*') ? 'active' : '' }}">
        <i class="bi bi-person-x-fill"></i>
        <span>Absent</span>
    </a>
    <button type="button" class="mbn-item mbn-item-more" onclick="openMoreSheet()" aria-label="Open More Menu" style="background:transparent;border:none;outline:none;cursor:pointer;">
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <span>More</span>
    </button>
</nav>
