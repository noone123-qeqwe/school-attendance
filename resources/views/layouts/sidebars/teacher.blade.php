<div class="sidebar-head">
    <img src="{{ asset('images/logo.png') }}" class="sidebar-logo" alt="School logo">
    <div class="sidebar-text">
        <div class="sidebar-brand">{{ config('app.name') }}</div>
        <div class="sidebar-subtitle">Teacher workspace</div>
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


<nav class="sidebar-nav" aria-label="Teacher navigation">
    @include('partials.teacher-navigation')
</nav>
