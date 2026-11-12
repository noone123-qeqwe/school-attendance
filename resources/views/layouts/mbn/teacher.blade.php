@php $attendancePicker = request()->routeIs('teacher.classroom.index') && request('task') === 'attendance'; @endphp
<nav class="mobile-bottom-nav d-md-none" id="mobileBottomNav" aria-label="Teacher navigation">
    <a href="{{ route('teacher.dashboard') }}" class="mbn-item {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}" @if(request()->routeIs('teacher.dashboard')) aria-current="page" @endif>
        <i class="bi bi-house-door-fill" aria-hidden="true"></i><span>Home</span>
    </a>
    <a href="{{ route('teacher.classroom.index') }}" class="mbn-item {{ request()->routeIs('teacher.classroom*', 'teacher.subjects*') && !$attendancePicker ? 'active' : '' }}" @if(request()->routeIs('teacher.classroom*', 'teacher.subjects*') && !$attendancePicker) aria-current="page" @endif>
        <i class="bi bi-journal-album" aria-hidden="true"></i><span>Classes</span>
    </a>
    <a href="{{ route('teacher.classroom.index', ['task' => 'attendance']) }}" class="mbn-item {{ $attendancePicker || request()->routeIs('teacher.qr*') ? 'active' : '' }}" aria-label="Take attendance" @if($attendancePicker || request()->routeIs('teacher.qr*')) aria-current="page" @endif>
        <i class="bi bi-qr-code-scan" aria-hidden="true"></i><span>Attendance</span>
    </a>
    <button type="button" class="mbn-item mbn-item-more {{ request()->routeIs('teacher.attendance*', 'teacher.absent*', 'teacher.students*', 'teacher.student', 'teacher.excuse*', 'teacher.corrections*', 'teacher.reports*', 'teacher.calendar*', 'teacher.notifications*', 'teacher.profile*') ? 'active' : '' }}" onclick="openMoreSheet()" aria-label="Open all teacher pages" aria-haspopup="dialog" aria-controls="moreSheet" aria-expanded="false" style="background:transparent;border:none;cursor:pointer;">
        <i class="bi bi-list" aria-hidden="true"></i><span>Menu</span>
    </button>
</nav>
