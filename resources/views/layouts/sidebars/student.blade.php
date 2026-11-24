<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="School logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') ?: 'Student Workspace' }}</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<nav class="sidebar-nav" aria-label="Student navigation">
    <div class="sd-scan-dock student-desktop-only">
        <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="student-desktop-scan" title="Scan attendance QR" data-title="Scan QR">
            <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
            <span class="nav-link-text">Scan QR</span>
        </a>
        <a href="{{ route('mobile.scan', ['open_code' => 1]) }}" class="sd-scan-pin-btn" title="Enter 6-digit attendance PIN" data-title="Enter PIN" aria-label="Enter 6-digit attendance PIN">
            <i class="bi bi-123" aria-hidden="true"></i>
            <span class="nav-link-text">PIN</span>
        </a>
    </div>
    <div class="student-nav-label nav-section-label">My workspace</div>
    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" @if(request()->routeIs('home')) aria-current="page" @endif title="Dashboard" data-title="Dashboard">
        <i class="bi bi-grid-fill" aria-hidden="true"></i>
        <span class="nav-link-text">Dashboard</span>
    </a>
    <a href="{{ route('profile') }}" class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}" @if(request()->routeIs('profile')) aria-current="page" @endif title="My profile" data-title="My Profile">
        <i class="bi bi-person-circle" aria-hidden="true"></i>
        <span class="nav-link-text">My Profile</span>
    </a>
    <a href="{{ route('student.classes') }}" class="nav-link {{ request()->routeIs('student.classes', 'student.schedule') ? 'active' : '' }}" @if(request()->routeIs('student.classes', 'student.schedule')) aria-current="page" @endif title="Classes & Schedule" data-title="Classes & Schedule">
        <i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i>
        <span class="nav-link-text">Classes &amp; Schedule</span>
        <span class="d-none" aria-hidden="true">My Schedule My Classes</span>
    </a>
    @if(!empty($hasActiveAssistantAssignment))
        <a href="{{ route('student-assistant.classes') }}" class="nav-link {{ request()->routeIs('student-assistant.*') ? 'active' : '' }}" @if(request()->routeIs('student-assistant.*')) aria-current="page" @endif title="Student Assistant" data-title="Student Assistant">
            <i class="bi bi-qr-code" aria-hidden="true"></i>
            <span class="nav-link-text">Student Assistant</span>
        </a>
    @endif
    <div class="student-nav-label nav-section-label">Attendance &amp; school</div>
    <a href="{{ route('student.calendar') }}" class="nav-link {{ request()->routeIs('student.calendar', 'student.attendance.calendar', 'attendance.records', 'attendance.history') ? 'active' : '' }}" @if(request()->routeIs('student.calendar', 'student.attendance.calendar', 'attendance.records', 'attendance.history')) aria-current="page" @endif title="Attendance & Calendar" data-title="Attendance & Calendar">
        <i class="bi bi-calendar-event-fill" aria-hidden="true"></i>
        <span class="nav-link-text">Attendance &amp; Calendar</span>
    </a>
    <a href="{{ route('excuses') }}" class="nav-link {{ request()->routeIs('excuses*') ? 'active' : '' }}" @if(request()->routeIs('excuses*')) aria-current="page" @endif title="Excuse submissions" data-title="Excuse Submissions">
        <i class="bi bi-file-text-fill" aria-hidden="true"></i>
        <span class="nav-link-text">Excuse Submissions</span>
    </a>
    <div class="student-desktop-only student-nav-account">
        <div class="student-nav-label nav-section-label">Account</div>
        <a href="{{ route('notifications') }}" class="nav-link {{ request()->routeIs('notifications*') ? 'active' : '' }}" @if(request()->routeIs('notifications*')) aria-current="page" @endif title="Notifications" data-title="Notifications">
            <i class="bi bi-bell" aria-hidden="true"></i>
            <span class="nav-link-text">Notifications</span>
            <span class="student-nav-badge d-none" id="studentSidebarNotifBadge" aria-label="Unread notifications"></span>
        </a>
        <a href="{{ route('settings') }}" class="nav-link {{ request()->routeIs('settings', 'settings.biometrics.*') ? 'active' : '' }}" @if(request()->routeIs('settings', 'settings.biometrics.*')) aria-current="page" @endif title="Settings" data-title="Settings">
            <i class="bi bi-gear" aria-hidden="true"></i>
            <span class="nav-link-text">Settings</span>
        </a>
    </div>
</nav>
<a href="{{ route('profile') }}" class="student-desktop-only student-sidebar-account" title="Academic profile & ID" data-title="My Profile">
    <span class="sd-cohort-badge" aria-hidden="true">
        <i class="bi bi-mortarboard-fill"></i>
    </span>
    <span class="nav-link-text">
        <strong>{{ Auth::user()->course ?: 'Student' }} &middot; Yr {{ Auth::user()->year_level ?: '1' }}{{ Auth::user()->section ? ' · Sec ' . Auth::user()->section : '' }}</strong>
        <small>ID {{ Auth::user()->student_number ?: 'Pending' }}</small>
    </span>
    <i class="bi bi-chevron-right student-sidebar-account-arrow" aria-hidden="true"></i>
</a>
@push('scripts')
<script @cspNonce>
(function() {
    function syncSidebarNotifBadge() {
        var sidebarBadge = document.getElementById('studentSidebarNotifBadge');
        if (!sidebarBadge) return;
        var headerBadge = document.getElementById('topNavNotifBadge');
        var countText = headerBadge && !headerBadge.classList.contains('d-none') ? (headerBadge.textContent || '').trim() : '';
        var countNum = parseInt(countText, 10);
        if (headerBadge && headerBadge.style.display !== 'none' && countText !== '' && (!isNaN(countNum) ? countNum > 0 : true)) {
            sidebarBadge.textContent = countText;
            sidebarBadge.classList.remove('d-none');
        } else {
            sidebarBadge.textContent = '';
            sidebarBadge.classList.add('d-none');
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        syncSidebarNotifBadge();
        var headerBtn = document.getElementById('topNavNotifBtn');
        if (headerBtn && typeof MutationObserver !== 'undefined') {
            new MutationObserver(syncSidebarNotifBadge).observe(headerBtn, {
                childList: true,
                subtree: true,
                characterData: true,
                attributes: true
            });
        }
    });
})();
</script>
@endpush
