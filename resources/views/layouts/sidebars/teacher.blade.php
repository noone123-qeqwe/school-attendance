<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="{{ config('app.name') }} Logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">{{ config('app.subtitle') }}</div>
    </div>
</div>

<div class="sidebar-divider"></div>

@php
    $teacher = Auth::user();
    $pendingExcusesCount = 0;
    $pendingCorrectionsCount = 0;
    if ($teacher) {
        try {
            $subjectCodes = \App\Models\Subject::where('instructor_id', $teacher->id)
                ->orWhere('instructor', $teacher->name)
                ->pluck('code');

            if ($subjectCodes->isNotEmpty()) {
                $pendingExcusesCount = \App\Models\ExcuseSubmission::where('status', 'pending')
                    ->whereHas('attendance', function($q) use ($subjectCodes) {
                        $q->whereIn('subject_code', $subjectCodes);
                    })->count();

                $pendingCorrectionsCount = \App\Models\AttendanceCorrection::where('status', 'pending')
                    ->whereHas('attendance', function ($q) use ($subjectCodes) {
                        $q->whereIn('subject_code', $subjectCodes);
                    })->count();
            }
        } catch (\Throwable $e) {
            $pendingExcusesCount = 0;
            $pendingCorrectionsCount = 0;
        }
    }
@endphp

<div class="sidebar-nav">
    <a href="{{ route('teacher.dashboard') }}" 
       class="nav-link {{ request()->routeIs('teacher.dashboard*') ? 'active' : '' }}"
       data-title="Dashboard">
        <i class="bi bi-grid-fill"></i>
        <span class="nav-link-text">Dashboard</span>
    </a>

    <a href="{{ route('teacher.classroom.index') }}" 
       class="nav-link {{ (request()->routeIs('teacher.classroom*') || request()->routeIs('teacher.subjects*')) ? 'active' : '' }}"
       data-title="My Classes">
        <i class="bi bi-journal-bookmark-fill"></i>
        <span class="nav-link-text">My Classes</span>
    </a>

    <a href="{{ route('teacher.calendar') }}" 
       class="nav-link {{ request()->routeIs('teacher.calendar*') ? 'active' : '' }}"
       data-title="School Calendar">
        <i class="bi bi-calendar2-week-fill"></i>
        <span class="nav-link-text">School Calendar</span>
    </a>

    <a href="{{ route('teacher.attendance') }}" 
       class="nav-link {{ request()->routeIs('teacher.attendance*') ? 'active' : '' }}"
       data-title="Attendance Records">
        <i class="bi bi-clipboard-check-fill"></i>
        <span class="nav-link-text">Attendance Records</span>
    </a>

    <a href="{{ route('teacher.students') }}" 
       class="nav-link {{ (request()->routeIs('teacher.students*') || request()->routeIs('teacher.student')) ? 'active' : '' }}"
       data-title="Student Roster">
        <i class="bi bi-people-fill"></i>
        <span class="nav-link-text">Student Roster</span>
    </a>

    <a href="{{ route('teacher.excuse.reviews') }}" 
       class="nav-link {{ request()->routeIs('teacher.excuse*') ? 'active' : '' }}"
       data-title="Excuse Submissions">
        <i class="bi bi-file-earmark-text-fill"></i>
        <span class="nav-link-text">Excuse Submissions</span>
        @if($pendingExcusesCount > 0)
            <span class="badge ms-auto" style="background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); font-size: 0.7rem; font-weight: 700; padding: 2px 7px; border-radius: 999px;">
                {{ $pendingExcusesCount }}
            </span>
        @endif
    </a>

    <a href="{{ route('teacher.corrections') }}" 
       class="nav-link {{ request()->routeIs('teacher.corrections*') ? 'active' : '' }}"
       data-title="Correction Requests">
        <i class="bi bi-pencil-square"></i>
        <span class="nav-link-text">Correction Requests</span>
        @if($pendingCorrectionsCount > 0)
            <span class="badge ms-auto" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.35); font-size: 0.7rem; font-weight: 700; padding: 2px 7px; border-radius: 999px;">
                {{ $pendingCorrectionsCount }}
            </span>
        @endif
    </a>

    <a href="{{ route('teacher.reports') }}" 
       class="nav-link {{ request()->routeIs('teacher.reports*') ? 'active' : '' }}"
       data-title="Attendance Reports">
        <i class="bi bi-graph-up-arrow"></i>
        <span class="nav-link-text">Attendance Reports</span>
    </a>
</div>
