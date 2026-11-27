@extends('layouts.app')

@section('page-title', 'Overview')

@section('content')
    <header class="admin-section-masthead">
        <div>
            <h1>Academic overview</h1>
            <p>Review the school’s enrollment, scheduled classes, and attendance at a glance.</p>
        </div>
        @if($term)
            <a class="admin-term" href="{{ route('admin.academic-years.index') }}">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <span>{{ $term->name }} <small>{{ $term->semester_label }}</small></span>
            </a>
        @else
            <a class="saas-btn saas-btn-secondary" href="{{ route('admin.academic-years.index') }}">View academic terms</a>
        @endif
    </header>

    <dl class="admin-metrics" aria-label="Academic totals">
        <div><dt>Students</dt><dd>{{ number_format($directoryCounts['students']) }}</dd></div>
        <div><dt>Teachers</dt><dd>{{ number_format($directoryCounts['teachers']) }}</dd></div>
        <div><dt>Scheduled classes</dt><dd>{{ number_format($activeClassCount) }}</dd></div>
        <div><dt>Class sections</dt><dd>{{ number_format($sectionCount) }}</dd></div>
    </dl>

    <div class="admin-overview-grid">
        <section class="admin-panel" aria-labelledby="academicAttendanceTitle">
            <header class="admin-panel-heading">
                <div>
                    <h2 id="academicAttendanceTitle">Attendance summary</h2>
                    <p>Past 30 days</p>
                </div>
            </header>
            @if($attendanceSummary['recorded'])
                <dl class="admin-panel-stats">
                    <div><dt>Attendance rate</dt><dd>{{ $attendanceSummary['rate'] }}%</dd></div>
                    <div><dt>Recorded results</dt><dd>{{ number_format($attendanceSummary['recorded']) }}</dd></div>
                    <div><dt>Absences</dt><dd>{{ number_format($attendanceSummary['absent']) }}</dd></div>
                </dl>
                <p class="admin-panel-note">Attendance rate includes present and late records among present, late, and absent results.</p>
            @else
                <p class="admin-empty-state">There are no present, late, or absent records in this period.</p>
            @endif
            <a class="admin-panel-link" href="{{ route('admin.attendance') }}">Open attendance records <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </section>

        <section class="admin-panel" aria-labelledby="academicScheduleTitle">
            <header class="admin-panel-heading">
                <div>
                    <h2 id="academicScheduleTitle">Recent schedule changes</h2>
                    <p>Last five updated timetable entries</p>
                </div>
                <a class="admin-panel-link" href="{{ route('admin.class-schedules.index') }}">Schedules <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </header>
            @if($recentSchedules->isEmpty())
                <p class="admin-empty-state">Updated class schedules will appear here.</p>
            @else
                <ul class="admin-data-list">
                    @foreach($recentSchedules as $schedule)
                        <li>
                            <span>
                                <strong>{{ $schedule->subject->name }}</strong>
                                <small>{{ $schedule->subject->code }} · {{ $schedule->section->name }}</small>
                            </span>
                            @if($schedule->updated_at)
                                <time datetime="{{ $schedule->updated_at->toAtomString() }}">{{ $schedule->updated_at->format('M j') }}</time>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
