<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
    </div>
</div>

<div class="sidebar-divider"></div>

<div class="sidebar-nav">
    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span class="nav-link-text">Dashboard</span>
    </a>
    <a href="javascript:void(0)" onclick="if(typeof openStudentScanner === 'function'){openStudentScanner();}else{window.location.href='{{ route('home') }}?open_scanner=1';}" class="nav-link" style="color: #f3e7cd; background: rgba(207,164,111,0.1); border: 1px solid rgba(207,164,111,0.22); border-radius: 12px; margin: 4px 0 8px 0;">
        <i class="bi bi-qr-code-scan" style="color: #cfa46f;"></i>
        <span class="nav-link-text" style="font-weight: 700;">Scan QR / Enter Code</span>
    </a>
    <a href="{{ route('student.schedule') }}" class="nav-link {{ request()->routeIs('student.schedule') ? 'active' : '' }}">
        <i class="bi bi-calendar2-week-fill"></i>
        <span class="nav-link-text">My Schedule</span>
    </a>
    <a href="{{ route('student.attendance.calendar') }}" class="nav-link {{ request()->routeIs('student.attendance.calendar') ? 'active' : '' }}">
        <i class="bi bi-calendar-check-fill"></i>
        <span class="nav-link-text">Attendance Calendar</span>
    </a>
    <a href="{{ route('attendance.records') }}" class="nav-link {{ request()->routeIs('attendance.records') ? 'active' : '' }}">
        <i class="bi bi-clipboard-data-fill"></i>
        <span class="nav-link-text">Attendance Records</span>
    </a>

    <a href="{{ route('student.calendar') }}" class="nav-link {{ request()->routeIs('student.calendar') ? 'active' : '' }}">
        <i class="bi bi-calendar-event-fill"></i>
        <span class="nav-link-text">School Calendar</span>
    </a>
    <a href="{{ route('excuses') }}" class="nav-link {{ request()->routeIs('excuses*') ? 'active' : '' }}">
        <i class="bi bi-file-text-fill"></i>
        <span class="nav-link-text">Excuse Submissions</span>
    </a>
</div>
