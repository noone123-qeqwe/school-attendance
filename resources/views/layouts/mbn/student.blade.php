<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav">
    <a href="{{ route('home') }}" class="mbn-item {{ request()->routeIs('home') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('student.classes') }}" class="mbn-item {{ request()->routeIs('student.classes') ? 'active' : '' }}">
        <i class="bi bi-folder-fill"></i>
        <span>Classes</span>
    </a>
    <a href="{{ route('student.schedule') }}" class="mbn-item {{ request()->routeIs('student.schedule') ? 'active' : '' }}">
        <i class="bi bi-calendar-range-fill"></i>
        <span>Schedule</span>
    </a>
    <a href="{{ route('excuses') }}" class="mbn-item {{ request()->routeIs('excuses*') ? 'active' : '' }}">
        <i class="bi bi-file-text-fill"></i>
        <span>Excuses</span>
    </a>
    <a href="javascript:void(0)" class="mbn-item" onclick="openMoreSheet()">
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <span>More</span>
    </a>
</nav>
