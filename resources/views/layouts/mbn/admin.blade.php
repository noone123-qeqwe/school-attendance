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
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="mbn-scan-icon-svg">
                <path d="M3.5 8.5V5.5C3.5 4.4 4.4 3.5 5.5 3.5H8.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15.5 3.5H18.5C19.6 3.5 20.5 4.4 20.5 5.5V8.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3.5 15.5V18.5C3.5 19.6 4.4 20.5 5.5 20.5H8.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15.5 20.5H18.5C19.6 20.5 20.5 19.6 20.5 18.5V15.5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                <rect x="7" y="7" width="3.5" height="3.5" rx="1" fill="currentColor"/>
                <rect x="13.5" y="7" width="3.5" height="3.5" rx="1" fill="currentColor"/>
                <rect x="7" y="13.5" width="3.5" height="3.5" rx="1" fill="currentColor"/>
                <circle cx="15.25" cy="15.25" r="1.75" fill="currentColor"/>
            </svg>
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
