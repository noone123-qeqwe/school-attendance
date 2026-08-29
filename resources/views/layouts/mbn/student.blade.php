<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav">
    <a href="{{ route('home') }}" class="mbn-item {{ request()->routeIs('home') && !request()->has('open_scanner') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('student.classes') }}" class="mbn-item {{ request()->routeIs('student.classes') ? 'active' : '' }}">
        <i class="bi bi-folder-fill"></i>
        <span>Classes</span>
    </a>
    <a href="javascript:void(0)" onclick="if(typeof openStudentScanner === 'function'){ openStudentScanner(); } else { window.location.href='{{ route('home') }}?open_scanner=1'; }" class="mbn-item mbn-item-featured" aria-label="Scan Attendance QR">
        <div class="mbn-featured-btn">
            <i class="bi bi-qr-code-scan"></i>
        </div>
        <span>Scan</span>
    </a>
    <a href="{{ route('student.schedule') }}" class="mbn-item {{ request()->routeIs('student.schedule') ? 'active' : '' }}">
        <i class="bi bi-calendar-range-fill"></i>
        <span>Schedule</span>
    </a>
    <button type="button" class="mbn-item mbn-item-more" onclick="openMoreSheet()" aria-label="Open More Menu" style="background:transparent;border:none;outline:none;cursor:pointer;">
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <span>More</span>
    </button>
</nav>
