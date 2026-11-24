<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="School logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<nav class="sidebar-nav" aria-label="Student navigation" style="display: flex; flex-direction: column; gap: 4px;">
    <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="student-desktop-scan student-desktop-only" title="Scan attendance">
        <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
        <span class="nav-link-text">Scan attendance</span>
    </a>
    <div class="student-nav-label nav-section-label">My workspace</div>
    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" @if(request()->routeIs('home')) aria-current="page" @endif title="Dashboard">
        <i class="bi bi-grid-fill"></i>
        <span class="nav-link-text">Dashboard</span>
    </a>
    <a href="{{ route('profile') }}" class="nav-link {{ request()->routeIs('profile') ? 'active' : '' }}" @if(request()->routeIs('profile')) aria-current="page" @endif title="My profile">
        <i class="bi bi-person-circle"></i>
        <span class="nav-link-text">My Profile</span>
    </a>
    <a href="{{ route('student.classes') }}" class="nav-link {{ request()->routeIs('student.classes', 'student.schedule') ? 'active' : '' }}" @if(request()->routeIs('student.classes', 'student.schedule')) aria-current="page" @endif title="Classes & Schedule">
        <i class="bi bi-journal-bookmark-fill"></i>
        <span class="nav-link-text">Classes & Schedule</span>
        <span class="d-none" aria-hidden="true">My Schedule My Classes</span>
    </a>
    @if(!empty($hasActiveAssistantAssignment))
        <a href="{{ route('student-assistant.classes') }}" class="nav-link {{ request()->routeIs('student-assistant.*') ? 'active' : '' }}" @if(request()->routeIs('student-assistant.*')) aria-current="page" @endif title="Student Assistant">
            <i class="bi bi-qr-code"></i>
            <span class="nav-link-text">Student Assistant</span>
        </a>
    @endif
    <div class="student-nav-label nav-section-label">Attendance &amp; school</div>
    <a href="{{ route('student.calendar') }}" class="nav-link {{ request()->routeIs('student.calendar', 'student.attendance.calendar', 'attendance.records', 'attendance.history') ? 'active' : '' }}" @if(request()->routeIs('student.calendar', 'student.attendance.calendar', 'attendance.records', 'attendance.history')) aria-current="page" @endif title="Attendance & Calendar">
        <i class="bi bi-calendar-event-fill"></i>
        <span class="nav-link-text">Attendance &amp; Calendar</span>
    </a>
    <a href="{{ route('excuses') }}" class="nav-link {{ request()->routeIs('excuses*') ? 'active' : '' }}" @if(request()->routeIs('excuses*')) aria-current="page" @endif title="Excuse submissions">
        <i class="bi bi-file-text-fill"></i>
        <span class="nav-link-text">Excuse Submissions</span>
    </a>
    <div class="student-desktop-only student-nav-account">
        <div class="student-nav-label nav-section-label">Account</div>
        <a href="{{ route('notifications') }}" class="nav-link {{ request()->routeIs('notifications*') ? 'active' : '' }}" @if(request()->routeIs('notifications*')) aria-current="page" @endif title="Notifications">
            <i class="bi bi-bell" aria-hidden="true"></i>
            <span class="nav-link-text">Notifications</span>
        </a>
        <a href="{{ route('settings') }}" class="nav-link {{ request()->routeIs('settings', 'settings.biometrics.*') ? 'active' : '' }}" @if(request()->routeIs('settings', 'settings.biometrics.*')) aria-current="page" @endif title="Settings">
            <i class="bi bi-gear" aria-hidden="true"></i>
            <span class="nav-link-text">Settings</span>
        </a>
    </div>
</nav>
<a href="{{ route('profile') }}" class="student-desktop-only student-sidebar-account" title="View your profile">
    <img src="{{ Auth::user()->profile_photo_url_with_version }}" alt="" class="user-avatar-img">
    <span class="nav-link-text">
        <strong>{{ Auth::user()->name }}</strong>
        <small>Student ID {{ Auth::user()->student_number }}</small>
    </span>
</a>
