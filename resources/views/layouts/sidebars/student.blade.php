<div class="sidebar-head student-sidebar-brand">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="School logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') ?: 'Student Workspace' }}</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<nav class="sidebar-nav student-sidebar-nav" aria-label="Student navigation">
    <div class="student-nav-label nav-section-label">My workspace</div>
    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" @if(request()->routeIs('home')) aria-current="page" @endif title="Dashboard" data-title="Dashboard" aria-label="Dashboard">
        <i class="bi bi-grid-fill" aria-hidden="true"></i>
        <span class="nav-link-text">Dashboard</span>
    </a>
    <a href="{{ route('student.classes') }}" class="nav-link {{ request()->routeIs('student.classes', 'student.schedule') ? 'active' : '' }}" @if(request()->routeIs('student.classes', 'student.schedule')) aria-current="page" @endif title="Classes & Schedule" data-title="Classes & Schedule" aria-label="Classes and schedule">
        <i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i>
        <span class="nav-link-text">Classes &amp; Schedule</span>
        <span class="d-none" aria-hidden="true">My Schedule My Classes</span>
    </a>
    @if(!empty($hasActiveAssistantAssignment))
        <a href="{{ route('student-assistant.classes') }}" class="nav-link {{ request()->routeIs('student-assistant.*') ? 'active' : '' }}" @if(request()->routeIs('student-assistant.*')) aria-current="page" @endif title="Student Assistant" data-title="Student Assistant" aria-label="Student assistant">
            <i class="bi bi-qr-code" aria-hidden="true"></i>
            <span class="nav-link-text">Student Assistant</span>
        </a>
    @endif
    <div class="student-nav-label nav-section-label">Attendance &amp; school</div>
    <a href="{{ route('student.calendar') }}" class="nav-link {{ request()->routeIs('student.calendar', 'student.attendance.calendar', 'attendance.records', 'attendance.history') ? 'active' : '' }}" @if(request()->routeIs('student.calendar', 'student.attendance.calendar', 'attendance.records', 'attendance.history')) aria-current="page" @endif title="Attendance & Calendar" data-title="Attendance & Calendar" aria-label="Attendance and calendar">
        <i class="bi bi-calendar-event-fill" aria-hidden="true"></i>
        <span class="nav-link-text">Attendance &amp; Calendar</span>
    </a>
    <a href="{{ route('excuses') }}" class="nav-link {{ request()->routeIs('excuses*') ? 'active' : '' }}" @if(request()->routeIs('excuses*')) aria-current="page" @endif title="Excuse submissions" data-title="Excuse Submissions" aria-label="Excuse submissions">
        <i class="bi bi-file-text-fill" aria-hidden="true"></i>
        <span class="nav-link-text">Excuse Submissions</span>
    </a>
</nav>
