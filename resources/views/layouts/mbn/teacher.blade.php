@php
    $attendancePicker = request()->routeIs('teacher.classroom.index') && request('task') === 'attendance';
    $qrActive = $attendancePicker || request()->routeIs('teacher.qr*');
    $classesActive = request()->routeIs('teacher.classroom*', 'teacher.subjects*', 'teacher.attendance*') && !$attendancePicker;
    $calendarActive = request()->routeIs('teacher.calendar*');
    $moreActive = request()->routeIs('teacher.absent*', 'teacher.students*', 'teacher.student', 'teacher.excuse*', 'teacher.corrections*', 'teacher.reports*', 'teacher.notifications*', 'teacher.profile*', 'teacher.excuses*');
@endphp
<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav" aria-label="Teacher navigation">
    <a href="{{ route('teacher.dashboard') }}" class="mbn-item {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}" @if(request()->routeIs('teacher.dashboard')) aria-current="page" @endif>
        <i class="bi bi-grid-fill" aria-hidden="true"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('teacher.classroom.index') }}" class="mbn-item {{ $classesActive ? 'active' : '' }}" @if($classesActive) aria-current="page" @endif>
        <i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i>
        <span>Classes</span>
    </a>
    <a href="{{ route('teacher.classroom.index', ['task' => 'attendance']) }}" class="mbn-item mbn-item-featured {{ $qrActive ? 'active' : '' }}" aria-label="Take Attendance QR" @if($qrActive) aria-current="page" @endif>
        <div class="mbn-featured-btn" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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
    <a href="{{ route('teacher.calendar') }}" class="mbn-item {{ $calendarActive ? 'active' : '' }}" @if($calendarActive) aria-current="page" @endif>
        <i class="bi bi-calendar-event-fill" aria-hidden="true"></i>
        <span>Calendar</span>
    </a>
    <button type="button" class="mbn-item mbn-item-more {{ $moreActive ? 'active' : '' }}" onclick="openMoreSheet()" aria-label="Open all teacher pages" aria-haspopup="dialog" aria-controls="moreSheet" aria-expanded="false">
        <i class="bi bi-grid-3x3-gap-fill" aria-hidden="true"></i>
        <span>Menu</span>
    </button>
</nav>
