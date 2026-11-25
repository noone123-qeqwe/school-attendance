@extends('layouts.app')
@section('page-title', 'Dashboard')

@section('content')

@php
    $calYear   = max(2000, min(2100, (int) request('cal_year', now()->year)));
    $calMonth  = max(1, min(12, (int) request('cal_month', now()->month)));
    $today     = now()->day;
    $isCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $totalRecords = (int) ($attendanceRecordCount ?? 0);
    $todaySchedCollection = collect($todaySchedule ?? []);
    $todayClassCount = $todaySchedCollection->count();
    $liveClassItem = $todaySchedCollection->firstWhere('status', 'ongoing');
    $nextClassItem = $todaySchedCollection->firstWhere('status', 'upcoming');
    $featuredClass = $liveClassItem ?? $nextClassItem;
    $featuredRoom = trim((string) ($featuredClass?->schedule?->room ?? ''));
    $featuredInstructor = trim((string) ($featuredClass?->subject?->instructorUser?->name ?? $featuredClass?->subject?->instructor ?? ''));
    $greetHour = now()->hour;
    $greeting = $greetHour < 12 ? 'Good Morning' : ($greetHour < 17 ? 'Good Afternoon' : 'Good Evening');
    $rateVal = $attendanceRate;
    $rateTone = $rateVal === null ? 'rate-empty' : ($rateVal >= 85 ? 'rate-good' : ($rateVal >= 75 ? 'rate-warning' : 'rate-danger'));
    $rateIcon = $rateVal === null ? 'bi-dash-circle' : ($rateVal >= 85 ? 'bi-shield-check' : ($rateVal >= 75 ? 'bi-shield-exclamation' : 'bi-exclamation-triangle-fill'));
    $subjectCount = isset($subjects) ? count($subjects) : 0;
    $computedTotalAll = ($totalPresent ?? 0) + ($totalLate ?? 0) + ($totalAbsent ?? 0);
@endphp

@if(blank(auth()->user()->section))
<div role="status" class="sd-notice-banner">
    <span class="sd-notice-icon" aria-hidden="true"><i class="bi bi-info-circle-fill"></i></span>
    <div class="sd-notice-copy">
        <strong>Class section not assigned</strong>
        <span>Your section timetable will appear after the school assigns your section. Contact the registrar or school administrator to update your profile.</span>
    </div>
</div>
@endif

@if(session('error'))
<div class="sd-error-banner" role="alert">
    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
    <span>{{ session('error') }}</span>
</div>
@endif

@if(isset($activeWarnings) && $activeWarnings->count() > 0)
<div class="ent-alert ent-fade-up mobile-warning-section" role="region" aria-label="Attendance Warnings">
    <div class="warning-header">
        <div class="ent-alert-icon warning-icon-box" aria-hidden="true">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div class="ent-alert-body warning-header-body">
            <div class="ent-alert-title warning-title">Action Required: Attendance Warning</div>
            <div class="ent-alert-text warning-subtitle">You have {{ $activeWarnings->count() }} active {{ \Illuminate\Support\Str::plural('warning', $activeWarnings->count()) }}. Please review your attendance immediately.</div>
        </div>
    </div>

    <div class="warning-cards-list">
        @foreach($activeWarnings as $warning)
        <div class="warning-card-item">
            <div class="warning-card-header">
                <span class="warning-card-code"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> {{ $warning->subject_code }}</span>
                <span class="warning-card-time"><i class="bi bi-clock" aria-hidden="true"></i> {{ $warning->created_at->diffForHumans() }}</span>
            </div>
            <div class="warning-card-message">{{ $warning->message }}</div>
        </div>
        @endforeach
    </div>

    <div class="warning-action-btns">
        <a href="{{ route('excuses') }}" class="ent-btn ent-btn-primary warning-btn-submit">
            <i class="bi bi-file-text-fill" aria-hidden="true"></i> Submit Excuse
        </a>
        <a href="{{ route('student.calendar') }}#attendance-records" class="ent-btn ent-btn-secondary warning-btn-records">
            <i class="bi bi-journal-text" aria-hidden="true"></i> View Records
        </a>
    </div>
</div>
@endif

<div class="student-desktop-dashboard">
    {{-- ── Hero Command Center ────────────────────────────────────── --}}
    <section class="student-hero-banner premium-hero-card" aria-label="Student overview">
        <div class="student-hero-inner">
            <div class="sd-hero-primary">
                <div class="student-hero-context">
                    <div class="student-greeting-badge">
                        @if($greetHour < 12)
                            <i class="bi bi-brightness-alt-high-fill" aria-hidden="true"></i>
                        @elseif($greetHour < 17)
                            <i class="bi bi-sun-fill" aria-hidden="true"></i>
                        @else
                            <i class="bi bi-moon-stars-fill" aria-hidden="true"></i>
                        @endif
                        <span>{{ $greeting }}</span>
                    </div>
                    <div class="student-status-badge" title="Enrollment Status: Active">
                        <span class="student-status-dot" aria-hidden="true"></span>
                        <span>Active Student</span>
                    </div>
                    <span class="sd-hero-date-pill d-md-none">{{ now()->format('D, M j') }}</span>
                </div>

                <div class="student-hero-profile">
                    <div class="student-avatar-box">
                        <img src="{{ Auth::user()->profile_photo_url_with_version }}" alt="{{ Auth::user()->name }}" class="student-avatar-img">
                    </div>
                    <div class="student-profile-info">
                        <h1 class="student-hero-name">{{ Auth::user()->name }}</h1>
                        <div class="student-meta-strip">
                            @if(!empty(Auth::user()->student_number))
                                <span class="student-meta-chip chip-gold" title="Student Identification Number">
                                    <i class="bi bi-person-badge" aria-hidden="true"></i>
                                    <span>ID {{ Auth::user()->student_number }}</span>
                                </span>
                            @endif
                            <span class="student-meta-chip" title="Enrolled Degree Program">
                                <i class="bi bi-mortarboard" aria-hidden="true"></i>
                                <span>{{ Auth::user()->course ?? 'BSCS' }}</span>
                            </span>
                            <span class="student-meta-chip" title="Academic Standing">
                                <i class="bi bi-layers" aria-hidden="true"></i>
                                <span>Year {{ Auth::user()->year_level ?? '1' }} &middot; Sem {{ Auth::user()->semester ?? '1' }}</span>
                            </span>
                            @if(!empty(Auth::user()->section))
                                <span class="student-meta-chip" title="Class Section">
                                    <i class="bi bi-people" aria-hidden="true"></i>
                                    <span>Sec {{ Auth::user()->section }}</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="student-hero-cta-group">
                    <a class="student-desktop-check-in" href="{{ route('mobile.scan', ['open_scanner' => 1]) }}">
                        <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                        <span>Scan Attendance QR</span>
                    </a>
                    <a class="student-desktop-check-in is-secondary" href="{{ route('mobile.scan', ['open_code' => 1]) }}">
                        <i class="bi bi-123" aria-hidden="true"></i>
                        <span>Enter 6-Digit PIN</span>
                    </a>
                </div>
            </div>

            {{-- Right Spotlight: Live or Next Class --}}
            <aside id="sdHeroSpotlight" class="sd-hero-spotlight {{ $liveClassItem ? 'is-live' : ($nextClassItem ? 'is-next' : 'is-idle') }}" aria-labelledby="dashboardClassHeading" @if($featuredClass) data-start-ms="{{ $featuredClass->start_time->getTimestampMs() }}" data-end-ms="{{ $featuredClass->end_time->getTimestampMs() }}" @endif>
                <div class="sd-spotlight-top">
                    <span class="sd-spotlight-label">
                        <span class="sd-spotlight-dot {{ $liveClassItem ? 'is-live' : ($nextClassItem ? 'is-next' : 'is-done') }}" aria-hidden="true"></span>
                        {{ $liveClassItem ? 'Class in Progress' : ($nextClassItem ? 'Up Next Today' : "Today's Status") }}
                    </span>
                    @if($featuredClass)
                        <span class="sd-spotlight-badges">
                            <span id="sdSpotlightTimer" class="sd-spotlight-timer" aria-live="off"></span>
                            <span class="sd-spotlight-code">{{ $featuredClass->subject->code }}</span>
                        </span>
                    @endif
                </div>

                <h2 id="dashboardClassHeading" class="sd-spotlight-text">
                    {{ $featuredClass?->subject?->name ?? ($todayClassCount ? 'All classes completed today' : 'No classes scheduled today') }}
                </h2>

                @if($featuredClass)
                    <div class="sd-spotlight-meta">
                        <span><i class="bi bi-clock" aria-hidden="true"></i> {{ $featuredClass->start_time->format('g:i A') }} &ndash; {{ $featuredClass->end_time->format('g:i A') }}</span>
                        @if($featuredRoom !== '' && strtoupper($featuredRoom) !== 'TBA')
                            <span><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $featuredRoom }}</span>
                        @endif
                        @if($featuredInstructor !== '')
                            <span><i class="bi bi-person" aria-hidden="true"></i> {{ $featuredInstructor }}</span>
                        @endif
                    </div>
                    @if($liveClassItem)
                        @php
                            $spotStartTs = $liveClassItem->start_time->timestamp;
                            $spotEndTs = $liveClassItem->end_time->timestamp;
                            $spotNowTs = now()->timestamp;
                            $spotProgressPct = $spotEndTs > $spotStartTs
                                ? min(100, max(4, round((($spotNowTs - $spotStartTs) / ($spotEndTs - $spotStartTs)) * 100)))
                                : 50;
                        @endphp
                        <div class="sd-spotlight-progress" aria-hidden="true">
                            <span id="sdSpotlightProgressFill" class="sd-spotlight-progress-fill" style="width: {{ $spotProgressPct }}%;"></span>
                        </div>
                    @endif
                @else
                    <p class="sd-spotlight-empty-note">
                        {{ $todayClassCount ? 'You have wrapped up all scheduled sessions for today.' : 'Use your free block to review records or submit any pending excuses.' }}
                    </p>
                @endif

                <div class="sd-spotlight-actions">
                    @if($liveClassItem && empty($alreadyClockedIn))
                        <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sd-spotlight-link is-check-in">
                            <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Check in now
                        </a>
                    @elseif($liveClassItem && !empty($alreadyClockedIn))
                        <span class="sd-spotlight-checked">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i> Checked in
                        </span>
                    @endif
                    <a href="{{ route('student.schedule', ['view' => 'weekly']) }}" class="sd-spotlight-link">
                        View timetable <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </aside>

            {{-- Bottom Telemetry Bar --}}
            @php
                $completedTodayCount = $todaySchedCollection->where('status', 'completed')->count();
                $remainingTodayCount = $todaySchedCollection->whereIn('status', ['ongoing', 'upcoming'])->count();
            @endphp
            <div class="student-hero-deck">
                <div class="hero-deck-card" title="Campus Local Time">
                    <div class="hero-deck-icon icon-time" aria-hidden="true">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <div class="hero-deck-body">
                        <div class="hero-deck-value" id="studentClock">{{ now()->format('h:i A') }}</div>
                        <div class="hero-deck-label">{{ now()->format('D, M j, Y') }}</div>
                    </div>
                </div>

                <div class="hero-deck-card" title="{{ $attendanceRate === null ? 'Attendance rate available after your first recorded class' : 'Overall Attendance Rate: '.$attendanceRate.'%' }}">
                    <div class="hero-deck-icon icon-{{ $rateTone }}" aria-hidden="true">
                        <i class="bi {{ $rateIcon }}"></i>
                    </div>
                    <div class="hero-deck-body">
                        <div class="hero-deck-value-row">
                            <div class="hero-deck-value val-{{ $rateTone }}">{{ $attendanceRate === null ? '—' : $attendanceRate.'%' }}</div>
                            @if($attendanceRate !== null)
                                <span class="sd-rate-chip is-{{ $rateTone }}">{{ $attendanceRate >= 85 ? 'Good' : ($attendanceRate >= 75 ? 'Fair' : 'At Risk') }}</span>
                            @endif
                        </div>
                        <div class="hero-deck-label">{{ $attendanceRate === null ? 'No records yet' : 'Overall attendance' }}</div>
                        @if($attendanceRate !== null)
                            <div class="sd-deck-progress" aria-hidden="true">
                                <span class="sd-deck-progress-fill is-{{ $rateTone }}" style="width: {{ min(100, max(0, $attendanceRate)) }}%;"></span>
                            </div>
                        @endif
                    </div>
                </div>

                @if(isset($streakCount) && $streakCount > 0)
                    <div class="hero-deck-card" title="Consecutive Attendance Streak">
                        <div class="hero-deck-icon icon-streak" aria-hidden="true">
                            <i class="bi bi-fire"></i>
                        </div>
                        <div class="hero-deck-body">
                            <div class="hero-deck-value">{{ $streakCount }} {{ $streakCount == 1 ? 'Day' : 'Days' }}</div>
                            <div class="hero-deck-label">
                                @if($todayClassCount > 0)
                                    Active streak &middot; {{ $completedTodayCount }}/{{ $todayClassCount }} today
                                @else
                                    Active streak
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="hero-deck-card" title="Today's Scheduled Classes">
                        <div class="hero-deck-icon icon-classes" aria-hidden="true">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <div class="hero-deck-body">
                            <div class="hero-deck-value">
                                @if($todayClassCount > 0)
                                    {{ $completedTodayCount }}/{{ $todayClassCount }} Done
                                @else
                                    0 Classes
                                @endif
                            </div>
                            <div class="hero-deck-label">{{ $todayClassCount > 0 ? "Today's progress" : "Today's schedule" }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ── Quick Actions Strip ────────────────────────────────────── --}}
    <nav class="student-quick-links" aria-label="Student quick shortcuts">
        <a href="{{ route('student.schedule', ['view' => 'weekly']) }}" class="quick-link-pill">
            <span class="sd-quick-icon" aria-hidden="true"><i class="bi bi-calendar-week"></i></span>
            <span class="sd-quick-text">
                <strong>Weekly Schedule</strong>
                <small>Full class timetable</small>
            </span>
            <i class="bi bi-chevron-right sd-quick-arrow" aria-hidden="true"></i>
        </a>
        <a href="{{ route('student.classes') }}" class="quick-link-pill">
            <span class="sd-quick-icon" aria-hidden="true"><i class="bi bi-journal-bookmark"></i></span>
            <span class="sd-quick-text">
                <strong>My Classes</strong>
                <small>Subjects, units &amp; rooms</small>
            </span>
            <i class="bi bi-chevron-right sd-quick-arrow" aria-hidden="true"></i>
        </a>
        <a href="{{ route('student.calendar') }}#attendance-records" class="quick-link-pill">
            <span class="sd-quick-icon" aria-hidden="true"><i class="bi bi-clipboard-check"></i></span>
            <span class="sd-quick-text">
                <strong>Attendance Log</strong>
                <small>History, calendar &amp; appeals</small>
            </span>
            <i class="bi bi-chevron-right sd-quick-arrow" aria-hidden="true"></i>
        </a>
        <a href="{{ route('excuses.create_general') }}" class="quick-link-pill">
            <span class="sd-quick-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
            <span class="sd-quick-text">
                <strong>Submit Excuse</strong>
                <small>Upload documentation</small>
            </span>
            <i class="bi bi-chevron-right sd-quick-arrow" aria-hidden="true"></i>
        </a>
    </nav>

    {{-- ── Attendance Overview KPIs ─────────────────────────────────── --}}
    @php
        $presentShare = $computedTotalAll > 0 ? round((($totalPresent ?? 0) / $computedTotalAll) * 100) : null;
        $lateShare = $computedTotalAll > 0 ? round((($totalLate ?? 0) / $computedTotalAll) * 100) : null;
        $absentShare = $computedTotalAll > 0 ? round((($totalAbsent ?? 0) / $computedTotalAll) * 100) : null;
    @endphp
    <section aria-labelledby="attendanceOverviewHeading">
        <div class="sd-overview-head">
            <h2 id="attendanceOverviewHeading">Attendance Overview</h2>
            <a href="{{ route('student.calendar') }}#attendance-records">Open calendar &amp; log <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="ent-grid ent-grid-4" id="realStats">
            <button type="button" class="sd-kpi-link sd-kpi-btn" onclick="openAttendanceRecordsModal('present')" title="Filter attendance log by Present">
                <div class="ent-kpi-card sd-kpi-card" data-accent="success">
                    <div class="ent-kpi-icon" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="ent-kpi-body">
                        <div class="ent-kpi-label">Present</div>
                        <div class="ent-kpi-value">{{ $totalPresent ?? 0 }}</div>
                        <div class="sd-kpi-sub">{{ $presentShare !== null ? $presentShare.'% of recorded sessions' : 'On-time check-ins' }}</div>
                    </div>
                    <i class="bi bi-arrow-up-right sd-kpi-chevron" aria-hidden="true"></i>
                </div>
            </button>
            <button type="button" class="sd-kpi-link sd-kpi-btn" onclick="openAttendanceRecordsModal('late')" title="Filter attendance log by Late">
                <div class="ent-kpi-card sd-kpi-card" data-accent="warning">
                    <div class="ent-kpi-icon" aria-hidden="true"><i class="bi bi-clock-fill"></i></div>
                    <div class="ent-kpi-body">
                        <div class="ent-kpi-label">Late</div>
                        <div class="ent-kpi-value">{{ $totalLate ?? 0 }}</div>
                        <div class="sd-kpi-sub">{{ $lateShare !== null ? $lateShare.'% of recorded sessions' : 'Late arrivals' }}</div>
                    </div>
                    <i class="bi bi-arrow-up-right sd-kpi-chevron" aria-hidden="true"></i>
                </div>
            </button>
            <button type="button" class="sd-kpi-link sd-kpi-btn" onclick="openAttendanceRecordsModal('absent')" title="Filter attendance log by Absent">
                <div class="ent-kpi-card sd-kpi-card" data-accent="danger">
                    <div class="ent-kpi-icon" aria-hidden="true"><i class="bi bi-x-circle-fill"></i></div>
                    <div class="ent-kpi-body">
                        <div class="ent-kpi-label">Absent</div>
                        <div class="ent-kpi-value">{{ $totalAbsent ?? 0 }}</div>
                        <div class="sd-kpi-sub">{{ $absentShare !== null ? $absentShare.'% missed sessions' : 'Missed sessions' }}</div>
                    </div>
                    <i class="bi bi-arrow-up-right sd-kpi-chevron" aria-hidden="true"></i>
                </div>
            </button>
            <a class="sd-kpi-link" href="{{ route('student.classes') }}" title="View enrolled subjects">
                <div class="ent-kpi-card sd-kpi-card" data-accent="gold">
                    <div class="ent-kpi-icon" aria-hidden="true"><i class="bi bi-book-fill"></i></div>
                    <div class="ent-kpi-body">
                        <div class="ent-kpi-label">Subjects</div>
                        <div class="ent-kpi-value">{{ $subjectCount }}</div>
                        <div class="sd-kpi-sub">{{ $computedTotalAll }} total {{ \Illuminate\Support\Str::plural('session', $computedTotalAll) }}</div>
                    </div>
                    <i class="bi bi-arrow-up-right sd-kpi-chevron" aria-hidden="true"></i>
                </div>
            </a>
        </div>
    </section>

    {{-- ── Main Two-Column Workspace ──────────────────────────────── --}}
    <div class="sd-dash-workspace">
        {{-- Left Column: Today's Schedule --}}
        <div class="sd-dash-main">
            <section class="sd-panel" id="todayScheduleSection" aria-labelledby="todayScheduleHeading">
                <header class="sd-panel-head">
                    <div class="sd-panel-title-wrap">
                        <span class="sd-panel-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                        <div>
                            <h2 id="todayScheduleHeading" class="sd-panel-title">Today's Schedule</h2>
                            <p class="sd-panel-sub">{{ now()->format('l, F j') }} &middot; {{ $todayClassCount }} {{ \Illuminate\Support\Str::plural('class', $todayClassCount) }}</p>
                        </div>
                    </div>
                    <a href="{{ route('student.schedule', ['view' => 'weekly']) }}" class="sd-header-pill">
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <span>Full Schedule</span>
                    </a>
                </header>

                <div class="sd-panel-body">
                    @if($todaySchedCollection->isNotEmpty())
                        @if($todayClassCount >= 3)
                            <div class="sd-sched-filters" role="group" aria-label="Filter today's classes">
                                <button type="button" class="sd-sched-filter-btn is-active" data-sched-filter="all" aria-pressed="true">
                                    All ({{ $todayClassCount }})
                                </button>
                                <button type="button" class="sd-sched-filter-btn" data-sched-filter="remaining" aria-pressed="false">
                                    Remaining ({{ $remainingTodayCount }})
                                </button>
                                <button type="button" class="sd-sched-filter-btn" data-sched-filter="done" aria-pressed="false">
                                    Done ({{ $completedTodayCount }})
                                </button>
                            </div>
                        @endif
                        <div class="schedule-items-grid sd-timeline-list">
                        @foreach($todaySchedCollection as $item)
                            @php
                                $statusColor = $item->status === 'completed' ? '#34d399' : ($item->status === 'ongoing' ? '#fbbf24' : ($item->status === 'missed' ? '#f87171' : ($item->status === 'past' ? '#9ca3af' : '#dfb879')));
                                $roomName = trim((string) ($item->schedule?->room ?? ''));
                                $instructorName = trim((string) ($item->subject->instructorUser?->name ?? $item->subject->instructor ?? ''));
                                $timeInText = $item->attendance?->time_in ? \Carbon\Carbon::parse($item->attendance->time_in)->format('g:i A') : null;
                                $excuseHref = $item->attendance?->id ? route('excuses.create', $item->attendance->id) : route('excuses.create_general');
                                $itemStartTs = $item->start_time->timestamp;
                                $itemEndTs = $item->end_time->timestamp;
                                $itemNowTs = now()->timestamp;
                                $itemLivePct = ($item->status === 'ongoing' && $itemEndTs > $itemStartTs)
                                    ? min(100, max(4, round((($itemNowTs - $itemStartTs) / ($itemEndTs - $itemStartTs)) * 100)))
                                    : 0;
                            @endphp
                            <div class="schedule-modern-card sd-timeline-item is-status-{{ $item->status }}" data-sched-status="{{ $item->status }}" style="--tl-color: {{ $statusColor }};">
                                <div class="sd-timeline-time d-none d-md-flex" aria-hidden="true">
                                    <strong>{{ $item->start_time->format('g:i A') }}</strong>
                                    <span>{{ $item->end_time->format('g:i A') }}</span>
                                </div>
                                <div class="sd-timeline-rail d-none d-md-flex" aria-hidden="true">
                                    <span class="sd-timeline-node"></span>
                                </div>
                                <div class="schedule-main-info">
                                    <div class="schedule-subject-title">{{ $item->subject->name }}</div>
                                    <div class="schedule-meta-row">
                                        <span class="schedule-time-badge d-md-none">
                                            <i class="bi bi-clock-fill" aria-hidden="true"></i>
                                            {{ $item->start_time->format('g:i A') }} &ndash; {{ $item->end_time->format('g:i A') }}
                                        </span>
                                        <span class="schedule-code-badge">{{ $item->subject->code }}</span>
                                        @if($instructorName !== '')
                                            <span class="schedule-instructor-badge">
                                                <i class="bi bi-person-fill" aria-hidden="true"></i> {{ $instructorName }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($item->status === 'ongoing')
                                        <div class="sd-timeline-progress" aria-hidden="true">
                                            <div class="sd-timeline-progress-fill" style="width: {{ $itemLivePct }}%;"></div>
                                        </div>
                                    @endif
                                    @if(($roomName !== '' && strtoupper($roomName) !== 'TBA') || $timeInText || $item->status === 'missed' || $item->status === 'ongoing')
                                        <div class="sd-sched-meta-pills">
                                            @if($roomName !== '' && strtoupper($roomName) !== 'TBA')
                                                <span class="sd-sched-chip"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> {{ $roomName }}</span>
                                            @endif
                                            @if($timeInText)
                                                <span class="sd-sched-chip is-checked-in"><i class="bi bi-check2-circle" aria-hidden="true"></i> Checked in {{ $timeInText }}</span>
                                            @elseif($item->status === 'ongoing')
                                                <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sd-sched-chip is-check-in-cta"><i class="bi bi-qr-code-scan" aria-hidden="true"></i> Check in now</a>
                                            @elseif($item->status === 'missed')
                                                <a href="{{ $excuseHref }}" class="sd-sched-chip is-excuse-cta"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i> Submit excuse</a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="schedule-status-col">
                                    @if($item->status === 'completed')
                                        @if(strtolower((string) ($item->attendance?->status ?? '')) === 'late')
                                            <x-badge type="late">Late</x-badge>
                                        @else
                                            <x-badge type="present">Present</x-badge>
                                        @endif
                                    @elseif($item->status === 'ongoing') <x-badge type="late" pulse>Live Now</x-badge>
                                    @elseif($item->status === 'missed') <x-badge type="absent">Missed</x-badge>
                                    @elseif($item->status === 'past') <x-badge type="neutral">Past</x-badge>
                                    @else <x-badge type="info">Upcoming</x-badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        </div>
                        <div id="sdScheduleFilterEmpty" class="sd-sched-filter-empty" hidden>
                            No classes match this filter right now.
                        </div>
                    @else
                        <div class="schedule-empty-state">
                            <span class="sd-empty-badge" aria-hidden="true"><i class="bi bi-calendar2-check"></i></span>
                            <p class="sd-empty-title">No classes scheduled today</p>
                            <p class="sd-empty-sub">Your timetable is clear for {{ now()->format('l') }}.</p>
                            <a href="{{ route('student.schedule', ['view' => 'weekly']) }}" class="sd-header-pill mt-2">
                                <i class="bi bi-calendar3" aria-hidden="true"></i> Open Weekly Timetable
                            </a>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        {{-- Right Column: Subject Attendance & Campus Announcements --}}
        <div class="sd-dash-aside">
            <section class="sd-panel sd-subject-breakdown-card" aria-labelledby="sdSubjectBreakdownHeading">
                <header class="sd-panel-head">
                    <div class="sd-panel-title-wrap">
                        <span class="sd-panel-icon" aria-hidden="true"><i class="bi bi-bar-chart-line-fill"></i></span>
                        <div>
                            <h2 id="sdSubjectBreakdownHeading" class="sd-panel-title">Subject Attendance</h2>
                            <p class="sd-panel-sub">Standing across {{ count($subjectStats ?? []) }} {{ \Illuminate\Support\Str::plural('subject', count($subjectStats ?? [])) }}</p>
                        </div>
                    </div>
                    @if(!empty($subjectStats) && count($subjectStats) > 0)
                        <button type="button" class="sd-header-pill" onclick="openSubjectBreakdownModal()">
                            <span>View all</span>
                        </button>
                    @else
                        <a href="{{ route('student.classes') }}" class="sd-header-pill">
                            <span>My Classes</span>
                        </a>
                    @endif
                </header>

                <div class="sd-panel-body">
                    @if(!empty($subjectStats) && count($subjectStats) > 0)
                        <div class="sd-subject-list">
                            @foreach(collect($subjectStats)->take(5) as $sub)
                                @php
                                    $hasSubjectRecords = $sub->total > 0;
                                    $rateClass = !$hasSubjectRecords ? '' : ($sub->rate >= 85 ? 'is-good' : ($sub->rate >= 75 ? 'is-warn' : 'is-risk'));
                                    $barColor = !$hasSubjectRecords ? 'rgba(255,255,255,0.14)' : ($sub->rate >= 85 ? '#34d399' : ($sub->rate >= 75 ? '#fbbf24' : '#f87171'));
                                    $barWidth = $hasSubjectRecords ? min(100, max(0, $sub->rate)) : 0;
                                @endphp
                                <div class="sd-subject-item">
                                    <button type="button" class="sd-subject-item-trigger" onclick="openSubjectBreakdownModal()" aria-label="View full breakdown including {{ $sub->code }} {{ $sub->name }}">
                                        <div class="sd-subject-item-top">
                                            <div class="sd-subject-id-group">
                                                <span class="sd-subject-code">{{ $sub->code }}</span>
                                                <span class="sd-subject-name" title="{{ $sub->name }}">{{ $sub->name }}</span>
                                            </div>
                                            <span class="sd-subject-rate {{ $rateClass }}">{{ $hasSubjectRecords ? $sub->rate.'%' : 'No records' }}</span>
                                        </div>
                                        <div class="sd-progress-track" aria-hidden="true">
                                            <div class="sd-progress-fill" style="width: {{ $barWidth }}%; background: {{ $barColor }};"></div>
                                        </div>
                                        <div class="sd-subject-counts">
                                            <span><strong class="text-count-present">{{ $sub->present }}</strong> present</span>
                                            <span><strong class="text-count-late">{{ $sub->late }}</strong> late</span>
                                            <span><strong class="text-count-absent">{{ $sub->absent }}</strong> absent</span>
                                            @if($hasSubjectRecords && $sub->rate < 75)
                                                <span class="sd-risk-pill">Below 75%</span>
                                            @endif
                                        </div>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="schedule-empty-state is-compact">
                            <span class="sd-empty-badge" aria-hidden="true"><i class="bi bi-journal-check"></i></span>
                            <p class="sd-empty-title">No session records yet</p>
                            <p class="sd-empty-sub">Subject metrics appear once sessions are recorded.</p>
                        </div>
                    @endif
                </div>
            </section>

            @if(isset($announcements) && $announcements->isNotEmpty())
            <section class="sd-panel" id="studentAnnouncementsSection" aria-labelledby="studentAnnouncementsHeading">
                <header class="sd-panel-head">
                    <div class="sd-panel-title-wrap">
                        <span class="sd-panel-icon" aria-hidden="true"><i class="bi bi-megaphone-fill"></i></span>
                        <div>
                            <h2 id="studentAnnouncementsHeading" class="sd-panel-title">Campus &amp; Class Announcements</h2>
                            <p class="sd-panel-sub">Latest notices from administration &amp; instructors</p>
                        </div>
                    </div>
                </header>
                <div class="sd-panel-body">
                    <div class="sd-announcements-list">
                        @foreach($announcements as $announcement)
                            @php
                                $annContent = trim((string) $announcement->content);
                                $annNeedsClamp = \Illuminate\Support\Str::length($annContent) > 170 || substr_count($annContent, "\n") >= 3;
                                $annUrgent = \Illuminate\Support\Str::contains(strtolower((string) $announcement->title . ' ' . ($announcement->target_audience ?? '')), ['urgent', 'important', 'alert']);
                                $annCopyId = 'sdAnnouncementCopy' . $loop->index;
                            @endphp
                            <article class="sd-announcement-item {{ $annUrgent ? 'is-urgent' : '' }}">
                                <div class="sd-announcement-head">
                                    <h3 class="sd-announcement-title">{{ $announcement->title }}</h3>
                                    <span class="schedule-code-badge">{{ $announcement->target_audience ?? 'All' }}</span>
                                </div>
                                <p id="{{ $annCopyId }}" class="sd-announcement-copy {{ $annNeedsClamp ? 'is-clamped' : '' }}">{{ $annContent }}</p>
                                @if($annNeedsClamp)
                                    <button type="button" class="sd-announcement-toggle" data-announcement-toggle="{{ $annCopyId }}" aria-expanded="false">
                                        Read more
                                    </button>
                                @endif
                                <div class="sd-announcement-meta">
                                    <span><i class="bi bi-person-fill" aria-hidden="true"></i> {{ $announcement->author->name ?? 'Administration' }}</span>
                                    <span><i class="bi bi-clock" aria-hidden="true"></i> {{ ($announcement->scheduled_for ?? $announcement->created_at)->diffForHumans() }}</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif
        </div>
    </div>
</div>

<style>
    /* ─── Modal & Inspector Styling (Subject Breakdown, Day Summary, Records) ── */
    .scal-sheet-handle,
    .day-modal-sheet-handle {
        width: 42px;
        height: 4.5px;
        background: rgba(255, 255, 255, 0.22);
        border-radius: 99px;
        margin: 12px auto 4px auto;
        display: none;
        flex-shrink: 0;
    }

    #daySummaryModal,
    #attendanceRecordsModal,
    #subjectBreakdownModal {
        z-index: 10060 !important;
    }
    .modal-backdrop {
        z-index: 10050 !important;
        backdrop-filter: blur(8px) !important;
        -webkit-backdrop-filter: blur(8px) !important;
        background: rgba(0, 0, 0, 0.72) !important;
    }
    #daySummaryModal .modal-dialog {
        transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    #daySummaryModal .modal-content {
        background: linear-gradient(180deg, #18120d 0%, #0d0806 100%) !important;
        border: 1px solid rgba(212, 175, 55, 0.2) !important;
        border-radius: 24px !important;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.85), 0 0 40px rgba(207, 164, 111, 0.08) !important;
        position: relative;
    }
    #daySummaryModal .modal-content::before { display: none !important; }

    .day-modal-action-bar { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px 8px; }
    .day-nav-stepper { display: inline-flex; align-items: center; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 3px; gap: 2px; }
    .day-nav-step-btn { width: 30px; height: 30px; border-radius: 8px; background: transparent; border: none; color: #cfa46f; display: inline-flex; align-items: center; justify-content: center; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease; }
    .day-nav-step-btn:hover { background: rgba(207,164,111,0.15); color: #ffd166; }
    .day-nav-today-btn { height: 30px; padding: 0 10px; border-radius: 8px; background: rgba(207,164,111,0.12); border: 1px solid rgba(207,164,111,0.25); color: #ffd166; font-size: 0.75rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
    .day-nav-today-btn:hover { background: rgba(207,164,111,0.25); border-color: #ffd166; color: #fff; }
    .day-modal-close-btn { width: 34px; height: 34px; border-radius: 50%; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); color: #cfa46f; display: inline-flex; align-items: center; justify-content: center; font-size: 0.82rem; cursor: pointer; transition: all 0.2s ease; }
    .day-modal-close-btn:hover { background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.35); color: #f87171; }
    .day-modal-header-content { padding: 4px 20px 14px; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .day-modal-date { font-size: 1.24rem; font-weight: 800; color: #f8fafc; margin: 0 0 8px; line-height: 1.25; }
    .day-summary-badges { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .day-summary-chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 0.74rem; font-weight: 700; }
    .day-summary-chip.chip-count { background: rgba(207,164,111,0.12); color: #f3ede4; border: 1px solid rgba(207,164,111,0.25); }
    .day-summary-chip.chip-present { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
    .day-summary-chip.chip-late { background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
    .day-summary-chip.chip-absent { background: rgba(239,68,68,0.12); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
    .day-summary-chip.chip-empty { background: rgba(255,255,255,0.04); color: #a8937e; border: 1px solid rgba(255,255,255,0.08); }
    .day-section-header { display: flex; align-items: center; justify-content: space-between; margin: 16px 0 12px; }
    .day-section-label { font-size: 0.74rem; font-weight: 800; color: #cfa46f; text-transform: uppercase; letter-spacing: 0.08em; }
    .day-section-icon { color: #cfa46f; font-size: 0.8rem; }
    .day-section-badge { font-size: 0.72rem; font-weight: 700; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); color: #b39b82; padding: 2px 8px; border-radius: 6px; }
    .att-detail-card { background: linear-gradient(145deg, rgba(32,24,20,0.9), rgba(18,13,11,0.95)); border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; padding: 16px 18px; position: relative; overflow: hidden; }
    .att-detail-card.status-border-present { border-left: 4px solid #10b981 !important; }
    .att-detail-card.status-border-late { border-left: 4px solid #f59e0b !important; }
    .att-detail-card.status-border-absent { border-left: 4px solid #ef4444 !important; }
    .att-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
    .att-card-subject-title { font-weight: 800; font-size: 1.05rem; color: #f8fafc; line-height: 1.32; margin-bottom: 6px; }
    .att-card-meta { font-size: 0.8rem; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .att-code-pill { background: rgba(207,164,111,0.15); color: #ffd166; border: 1px solid rgba(207,164,111,0.35); font-weight: 800; font-size: 0.74rem; padding: 2px 8px; border-radius: 6px; }
    .att-instructor-pill { color: #c5b3a1; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 4px; }
    .att-status-badge { padding: 5px 12px; border-radius: 10px; font-size: 0.74rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .att-status-badge.present { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.35); }
    .att-status-badge.late { background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.35); }
    .att-status-badge.absent { background: rgba(239,68,68,0.12); color: #f87171; border: 1px solid rgba(239,68,68,0.35); }
    .att-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.05); border-radius: 14px; padding: 12px 14px; }
    .att-grid-col-label { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #cfa46f; margin-bottom: 4px; display: flex; align-items: center; gap: 5px; }
    .att-grid-col-val { font-size: 0.88rem; font-weight: 700; color: #f1f5f9; }
    .day-card-excuse-btn { display: flex; align-items: center; justify-content: space-between; margin-top: 10px; padding: 8px 12px; border-radius: 10px; background: rgba(207,164,111,0.08); border: 1px dashed rgba(207,164,111,0.3); color: #ffd166; font-size: 0.76rem; font-weight: 700; text-decoration: none; }
    .day-empty-card { text-align: center; padding: 38px 20px; background: rgba(255,255,255,0.02); border-radius: 20px; border: 1px dashed rgba(255,255,255,0.08); }
    .day-empty-icon { width: 56px; height: 56px; border-radius: 18px; background: rgba(207,164,111,0.1); border: 1px solid rgba(207,164,111,0.22); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; color: #ffd166; font-size: 1.6rem; }

    @media (max-width: 768px) {
        #daySummaryModal .modal-dialog,
        #attendanceRecordsModal .modal-dialog,
        #subjectBreakdownModal .modal-dialog {
            margin: 0 !important;
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            max-height: 88vh !important;
            display: flex !important;
            align-items: flex-end !important;
            transform: translateY(0) !important;
        }
        #daySummaryModal .modal-content,
        #attendanceRecordsModal .modal-content,
        #subjectBreakdownModal .modal-content {
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-top-left-radius: 28px !important;
            border-top-right-radius: 28px !important;
            max-height: 88vh !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            padding-bottom: calc(24px + env(safe-area-inset-bottom, 16px)) !important;
            border-bottom: none !important;
            width: 100% !important;
        }
        .day-modal-sheet-handle,
        .scal-sheet-handle {
            display: block !important;
        }
    }

    .subject-minimal-card { border-color: rgba(255, 255, 255, 0.06); }
    .subject-minimal-card:hover { background: rgba(255, 255, 255, 0.035) !important; border-color: rgba(207, 164, 111, 0.3) !important; transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.45); }
</style>

{{-- ── Subject Attendance Breakdown Modal ─────────────────────────────── --}}
<div class="modal fade" id="subjectBreakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background:#14100e; border:1px solid rgba(223,184,121,0.2); border-radius:22px; box-shadow:0 30px 80px rgba(0,0,0,0.85);">
            <div class="scal-sheet-handle"></div>
            <div class="d-flex justify-content-between align-items-center px-4 pt-4 pb-3 border-bottom" style="border-color:rgba(255,255,255,0.07)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:40px; height:40px; border-radius:12px; background:rgba(223,184,121,0.14); border:1px solid rgba(223,184,121,0.28); display:flex; align-items:center; justify-content:center; color:#dfb879; font-size:1.15rem;">
                        <i class="bi bi-pie-chart-fill"></i>
                    </div>
                    <div>
                        <h3 style="font-weight:800; font-size:1.15rem; color:#f5efe6; margin:0;">Subject Breakdown</h3>
                        <div style="font-size:0.78rem; color:#b5a89a; margin-top:2px;">Attendance rate &amp; records per enrolled subject</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge" style="background:rgba(223,184,121,0.12); color:#dfb879; border:1px solid rgba(223,184,121,0.25); font-size:0.76rem; font-weight:700; padding:6px 12px; border-radius:10px;">
                        {{ count($subjectStats ?? []) }} {{ \Illuminate\Support\Str::plural('Subject', count($subjectStats ?? [])) }}
                    </span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="px-4 py-4" style="max-height:72vh; overflow-y:auto;">
                @if(empty($subjectStats) || count($subjectStats) === 0)
                    <div style="text-align:center; padding:42px 20px; background:rgba(255,255,255,0.02); border-radius:18px; border:1px dashed rgba(255,255,255,0.08);">
                        <div style="width:52px; height:52px; border-radius:14px; background:rgba(223,184,121,0.1); border:1px solid rgba(223,184,121,0.2); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; color:#dfb879; font-size:1.4rem;">
                            <i class="bi bi-journal-x"></i>
                        </div>
                        <p style="color:#b5a89a; font-size:0.9rem; font-weight:600; margin:0;">No enrolled subject records found</p>
                    </div>
                @else
                    <div class="row g-3">
                        @foreach($subjectStats as $sub)
                            @php
                                $hasRecords = $sub->total > 0;
                                $brkRateColor = '#94a3b8';
                                $brkRateBg    = 'rgba(148,163,184,0.12)';
                                $brkRateBorder= 'rgba(148,163,184,0.26)';
                                if ($hasRecords) {
                                    if ($sub->rate < 75) {
                                        $brkRateColor  = '#f87171';
                                        $brkRateBg     = 'rgba(239,68,68,0.12)';
                                        $brkRateBorder = 'rgba(239,68,68,0.3)';
                                    } elseif ($sub->rate < 85) {
                                        $brkRateColor  = '#fbbf24';
                                        $brkRateBg     = 'rgba(245,158,11,0.12)';
                                        $brkRateBorder = 'rgba(245,158,11,0.3)';
                                    } else {
                                        $brkRateColor  = '#34d399';
                                        $brkRateBg     = 'rgba(16,185,129,0.12)';
                                        $brkRateBorder = 'rgba(16,185,129,0.3)';
                                    }
                                }
                            @endphp
                            <div class="col-12 col-md-6">
                                <div class="subject-minimal-card" style="background:rgba(255,255,255,0.025); border:1px solid rgba(255,255,255,0.07); border-radius:16px; padding:16px 18px; height:100%; display:flex; flex-direction:column; justify-content:space-between; transition:all 0.22s ease;">
                                    <div>
                                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px;">
                                            <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                                <span style="background:rgba(223,184,121,0.14); color:#dfb879; border:1px solid rgba(223,184,121,0.26); font-size:0.74rem; font-weight:800; padding:3px 8px; border-radius:7px; flex-shrink:0;">{{ $sub->code }}</span>
                                                <span style="font-size:0.9rem; font-weight:700; color:#f5efe6; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $sub->name }}">{{ $sub->name }}</span>
                                            </div>
                                            <span class="badge" style="background:{{ $brkRateBg }}; color:{{ $brkRateColor }}; border:1px solid {{ $brkRateBorder }}; font-size:0.8rem; font-weight:800; padding:4px 9px; border-radius:8px; flex-shrink:0;">{{ $hasRecords ? $sub->rate.'%' : 'No records' }}</span>
                                        </div>
                                        <div style="height:5px; background:rgba(255,255,255,0.07); border-radius:99px; overflow:hidden; margin-bottom:13px;">
                                            <div style="width:{{ $hasRecords ? min(100, max(0, $sub->rate)) : 0 }}%; height:100%; background:{{ $brkRateColor }}; border-radius:99px;"></div>
                                        </div>
                                    </div>
                                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; padding-top:10px; border-top:1px solid rgba(255,255,255,0.05);">
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <span style="font-size:0.75rem; color:#b5a89a;"><strong style="color:#34d399;">{{ $sub->present }}</strong> Present</span>
                                            <span style="font-size:0.75rem; color:#b5a89a;"><strong style="color:#fbbf24;">{{ $sub->late }}</strong> Late</span>
                                            <span style="font-size:0.75rem; color:#b5a89a;"><strong style="color:#f87171;">{{ $sub->absent }}</strong> Absent</span>
                                            @if($sub->excused > 0)
                                                <span style="font-size:0.75rem; color:#b5a89a;"><strong style="color:#60a5fa;">{{ $sub->excused }}</strong> Excused</span>
                                            @endif
                                        </div>
                                        <span style="font-size:0.72rem; color:#948678; font-weight:600;">{{ $sub->total }} {{ \Illuminate\Support\Str::plural('Session', $sub->total) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center" style="border-color:rgba(255,255,255,0.07)!important; background:rgba(255,255,255,0.015);">
                <a href="{{ route('student.classes') }}" class="btn btn-sm" style="background:rgba(223,184,121,0.12); border:1px solid rgba(223,184,121,0.3); color:#dfb879; font-weight:700; font-size:0.82rem; border-radius:10px; padding:7px 16px;">
                    <i class="bi bi-book-fill me-1"></i> My Classes
                </a>
                <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.12); color:#f5efe6; font-weight:700; font-size:0.82rem; border-radius:10px; padding:7px 18px;">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Day Summary Inspector Modal ────────────────────────────────────── --}}
<div class="modal fade" id="daySummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="scal-sheet-handle day-modal-sheet-handle"></div>
            <div class="day-modal-action-bar">
                <div class="day-nav-stepper">
                    <button type="button" class="day-nav-step-btn" onclick="navigateDayModal(-1)" title="Previous Day" aria-label="Previous Day">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button" class="day-nav-today-btn" onclick="goToTodayModal()" title="Jump to Today">
                        Today
                    </button>
                    <button type="button" class="day-nav-step-btn" onclick="navigateDayModal(1)" title="Next Day" aria-label="Next Day">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <button type="button" class="day-modal-close-btn" data-bs-dismiss="modal" onclick="closeDaySummaryModal()" title="Close Inspector" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="day-modal-header-content">
                <h3 class="day-modal-date" id="daySummaryTitle">Date</h3>
                <div class="day-summary-badges" id="daySummarySubtitle">
                    <span class="day-summary-chip chip-empty" id="daySummaryStatusText">
                        <span id="daySummaryStatusDot" style="width:7px; height:7px; border-radius:50%; background:#ffd166; display:inline-block;"></span>
                        Attendance Details
                    </span>
                </div>
            </div>
            <div class="px-4 pb-4 pt-1">
                <div id="daySummaryContent" class="d-flex flex-column gap-3"></div>
                <div style="font-size:0.75rem; color:#8f826f; text-align:center; margin-top:20px; display:flex; align-items:center; justify-content:center; gap:6px;">
                    <i class="bi bi-shield-check text-gold" style="color:#cfa46f;"></i>
                    <span>Real-time records, events, and schedules for this date.</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Attendance Records Modal ───────────────────────────────────────── --}}
<div class="modal fade" id="attendanceRecordsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background:#14100e; border:1px solid rgba(223,184,121,0.2); border-radius:22px; box-shadow:0 30px 80px rgba(0,0,0,0.85);">
            <div class="scal-sheet-handle"></div>
            <div class="d-flex justify-content-between align-items-center px-4 pt-4 pb-3 border-bottom" style="border-color:rgba(255,255,255,0.06)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:40px; height:40px; border-radius:12px; background:rgba(223,184,121,0.14); border:1px solid rgba(223,184,121,0.28); display:flex; align-items:center; justify-content:center; color:#dfb879; font-size:1.15rem;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h3 style="font-weight:800; font-size:1.2rem; color:#f5efe6; margin:0;">Attendance Records</h3>
                        <div style="font-size:0.8rem; color:#b5a89a; margin-top:2px;">Complete attendance history per subject and date</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span id="recordsModalCount" class="badge" style="background:rgba(223,184,121,0.12); color:#dfb879; border:1px solid rgba(223,184,121,0.25); font-size:0.78rem; padding:6px 12px; border-radius:10px;">
                        {{ $totalRecords }} Records
                    </span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="px-4 py-3 border-bottom" style="background:rgba(255,255,255,0.015); border-color:rgba(255,255,255,0.05)!important;">
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <div style="position:relative;">
                            <i class="bi bi-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#8f826f; font-size:0.85rem;"></i>
                            <input type="text" id="recordsSearchInput" oninput="filterAttendanceRecords()" class="form-control form-control-sm" placeholder="Search subject, code, or instructor..." style="background:#0f0c0a; border:1px solid rgba(255,255,255,0.1); color:#f5efe6; border-radius:10px; padding-left:34px; font-size:0.82rem; height:38px;">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="recordsStatusFilter" onchange="filterAttendanceRecords()" class="form-select form-select-sm" style="background:#0f0c0a; border:1px solid rgba(255,255,255,0.1); color:#f5efe6; border-radius:10px; font-size:0.82rem; height:38px;">
                            <option value="all">All Statuses</option>
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                            <option value="excused">Excused</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="recordsMonthFilter" onchange="filterAttendanceRecords()" class="form-select form-select-sm" style="background:#0f0c0a; border:1px solid rgba(255,255,255,0.1); color:#f5efe6; border-radius:10px; font-size:0.82rem; height:38px;">
                            <option value="all">All Months</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="px-4 py-3" style="min-height:280px; max-height:60vh; overflow-y:auto;">
                <div id="recordsModalList" class="d-flex flex-column gap-2"></div>
            </div>

            <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center" style="border-color:rgba(255,255,255,0.06)!important; background:rgba(255,255,255,0.01);">
                <a href="{{ route('student.calendar') }}#attendance-records" class="btn btn-outline-warning btn-sm" style="border-radius:10px; font-weight:700; font-size:0.82rem; padding:7px 16px; border-color:rgba(223,184,121,0.4); color:#dfb879;">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Open Full History Page
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="border-radius:10px; font-weight:700; font-size:0.82rem; padding:7px 18px; background:rgba(255,255,255,0.08); border-color:rgba(255,255,255,0.1);">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
var attCalendarData = {};
var eventsCalendarData = {};
var dashboardCalendarDataLoaded = false;
var dashboardCalendarDataRequest = null;
var allAttendanceRecords = [];
let attendanceHistoryLoaded = false;
let attendanceHistoryRequest = null;
let dayModalInstance = null;
let recordsModalInstance = null;
let currentSelectedDateKey = '{{ $calYear }}-{{ str_pad($calMonth, 2, '0', STR_PAD_LEFT) }}-{{ str_pad(($isCurrentMonth && $today ? $today : 14), 2, '0', STR_PAD_LEFT) }}';

function loadDashboardCalendarData() {
    if (dashboardCalendarDataLoaded) {
        return Promise.resolve();
    }
    if (dashboardCalendarDataRequest) {
        return dashboardCalendarDataRequest;
    }
    var url = '{{ route('student.dashboard.calendar.data') }}'
        + '?year={{ $calYear }}&month={{ $calMonth }}';

    dashboardCalendarDataRequest = fetch(url, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
        .then(function(response) {
            if (!response.ok) throw new Error('Dashboard calendar request failed');
            return response.json();
        })
        .then(function(data) {
            attCalendarData = data && data.attendance && typeof data.attendance === 'object'
                ? data.attendance
                : {};
            eventsCalendarData = data && data.events && typeof data.events === 'object'
                ? data.events
                : {};
            dashboardCalendarDataLoaded = true;
        })
        .finally(function() {
            dashboardCalendarDataRequest = null;
        });

    return dashboardCalendarDataRequest;
}

function closeDaySummaryModal() {
    currentSelectedDateKey = null;
    const modalEl = document.getElementById('daySummaryModal');
    if (modalEl) {
        if (dayModalInstance) {
            try { dayModalInstance.hide(); } catch (e) {}
        }
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.removeAttribute('aria-modal');
    }
    document.body.classList.remove('modal-open');
    const fb = document.querySelector('.scal-fallback-backdrop');
    if (fb) fb.remove();
}

function selectCalendarDay(dateKey, day, openModal = true) {
    document.querySelectorAll('.scal-tile.selected').forEach(el => el.classList.remove('selected'));

    const tile = document.getElementById('calTile_' + dateKey);
    if (tile) {
        tile.classList.add('selected');
    }
    currentSelectedDateKey = dateKey;

    if (openModal) {
        showAttDetail(dateKey, day);
    }
}

function navigateDayModal(delta) {
    if (!currentSelectedDateKey) return;
    const dt = new Date(currentSelectedDateKey + 'T00:00:00');
    dt.setDate(dt.getDate() + delta);
    const y = dt.getFullYear();
    const m = String(dt.getMonth() + 1).padStart(2, '0');
    const d = String(dt.getDate()).padStart(2, '0');
    const newDateKey = `${y}-${m}-${d}`;
    selectCalendarDay(newDateKey, dt.getDate(), true);
}

function goToTodayModal() {
    const todayStr = '{{ now()->format("Y-m-d") }}';
    const dt = new Date(todayStr + 'T00:00:00');
    selectCalendarDay(todayStr, dt.getDate(), true);
}

function showAttDetail(dateKey, day) {
    currentSelectedDateKey = dateKey;
    const titleEl = document.getElementById('daySummaryTitle');
    const subtitleEl = document.getElementById('daySummarySubtitle');
    const contentEl = document.getElementById('daySummaryContent');

    if (!dashboardCalendarDataLoaded) {
        if (titleEl) titleEl.textContent = 'Loading calendar details';
        if (subtitleEl) subtitleEl.innerHTML = '<span class="day-summary-chip chip-empty"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Preparing attendance and event details</span>';
        if (contentEl) contentEl.innerHTML = '<div class="day-empty-card"><div class="day-empty-icon"><i class="bi bi-calendar2-week"></i></div><div style="font-weight:700;color:#f3ede4;">Loading this day…</div></div>';
        openDaySummaryInspector();
        loadDashboardCalendarData().then(function() {
            if (dashboardCalendarDataLoaded && currentSelectedDateKey === dateKey) {
                showAttDetail(dateKey, day);
            }
        }).catch(function() {
            if (currentSelectedDateKey !== dateKey) return;
            if (subtitleEl) subtitleEl.textContent = 'Calendar unavailable';
            if (contentEl) contentEl.innerHTML = '<div class="day-empty-card" role="alert"><p>Could not load calendar details. Close this window and open the day again to retry.</p><a href="{{ route('student.calendar') }}" style="color:#ffd166;">Open attendance and school calendar</a></div>';
        });
        return;
    }

    const dt = new Date(dateKey + 'T00:00:00');
    const formattedDate = dt.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    if (titleEl) titleEl.textContent = formattedDate;

    const records = attCalendarData[dateKey] || [];
    const dayEvents = (eventsCalendarData && eventsCalendarData[dateKey]) ? eventsCalendarData[dateKey] : [];

    if (records.length === 0 && dayEvents.length === 0) {
        if (subtitleEl) {
            subtitleEl.innerHTML = `
                <span class="day-summary-chip chip-empty">
                    <i class="bi bi-calendar-x text-gold me-1"></i> No records or events
                </span>`;
        }
        if (contentEl) {
            contentEl.innerHTML = `
                <div class="day-empty-card" style="padding: 36px 20px; text-align: center;">
                    <div class="day-empty-icon" style="margin-bottom: 14px;">
                        <i class="bi bi-calendar2-x" style="font-size: 2.4rem; color: #b39b82; opacity: 0.6;"></i>
                    </div>
                    <div style="font-weight:800; font-size:1.05rem; color:#f3ede4; margin-bottom:6px;">No records or events for this date.</div>
                    <div style="font-size:0.82rem; color:#8f826f; max-width:300px; margin:0 auto; line-height:1.5;">There are no attendance records, classes, exams, events, or holidays scheduled for this date.</div>
                </div>`;
        }
    } else {
        let badgesHtml = '';
        if (records.length > 0) {
            const presentCount = records.filter(r => r.status_lower === 'present').length;
            const lateCount = records.filter(r => r.status_lower === 'late').length;
            const absentCount = records.filter(r => r.status_lower === 'absent').length;

            badgesHtml += `<span class="day-summary-chip chip-count"><i class="bi bi-journal-bookmark-fill me-1" style="color:#cfa46f;"></i> ${records.length} ${records.length === 1 ? 'Subject' : 'Subjects'}</span>`;
            if (presentCount > 0) {
                badgesHtml += `<span class="day-summary-chip chip-present"><i class="bi bi-check-circle-fill me-1"></i> ${presentCount} Present</span>`;
            }
            if (lateCount > 0) {
                badgesHtml += `<span class="day-summary-chip chip-late"><i class="bi bi-clock-fill me-1"></i> ${lateCount} Late</span>`;
            }
            if (absentCount > 0) {
                badgesHtml += `<span class="day-summary-chip chip-absent"><i class="bi bi-x-circle-fill me-1"></i> ${absentCount} Absent</span>`;
            }
        }

        if (dayEvents.length > 0) {
            const holidays = dayEvents.filter(e => e.type === 'holiday');
            const exams = dayEvents.filter(e => e.type === 'exam');
            const events = dayEvents.filter(e => e.type !== 'holiday' && e.type !== 'exam');

            if (holidays.length > 0) {
                badgesHtml += `<span class="day-summary-chip" style="background:rgba(74,222,128,0.15); color:#4ade80; border:1px solid rgba(74,222,128,0.3);"><i class="bi bi-flag-fill me-1"></i> ${holidays.length === 1 ? 'Holiday' : holidays.length + ' Holidays'}</span>`;
            }
            if (exams.length > 0) {
                badgesHtml += `<span class="day-summary-chip" style="background:rgba(236,72,153,0.15); color:#ec4899; border:1px solid rgba(236,72,153,0.3);"><i class="bi bi-award-fill me-1"></i> ${exams.length === 1 ? 'Exam' : exams.length + ' Exams'}</span>`;
            }
            if (events.length > 0) {
                badgesHtml += `<span class="day-summary-chip" style="background:rgba(139,92,246,0.15); color:#c4b5fd; border:1px solid rgba(139,92,246,0.3);"><i class="bi bi-calendar-event-fill me-1"></i> ${events.length === 1 ? 'Event' : events.length + ' Events'}</span>`;
            }
        }
        if (subtitleEl) subtitleEl.innerHTML = badgesHtml;

        let fullHtml = '';

        if (records.length > 0) {
            fullHtml += `
                <div class="day-section-header mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="day-section-icon"><i class="bi bi-journal-check"></i></span>
                        <span class="day-section-label">ATTENDANCE</span>
                    </div>
                    <span class="day-section-badge">${records.length} ${records.length === 1 ? 'Subject' : 'Subjects'}</span>
                </div>
                <div class="d-flex flex-column gap-3 mb-4">
            `;

            records.forEach(r => {
                const st = (r.status_lower || 'absent');
                let iconClass = 'bi-x-circle-fill';
                let clockInColor = '#f87171';
                let borderStatusClass = 'status-border-absent';

                if (st === 'present') {
                    iconClass = 'bi-check-circle-fill';
                    clockInColor = '#34d399';
                    borderStatusClass = 'status-border-present';
                } else if (st === 'late') {
                    iconClass = 'bi-clock-fill';
                    clockInColor = '#fbbf24';
                    borderStatusClass = 'status-border-late';
                }

                const clockInText = r.clock_in || (st === 'absent' ? 'No clock-in' : '—');
                const isNoClockIn = clockInText.toLowerCase().includes('no clock-in');

                fullHtml += `
                    <div class="att-detail-card ${borderStatusClass}">
                        <div class="att-card-header">
                            <div style="flex:1; min-width:180px;">
                                <div class="att-card-subject-title">${escapeHtml(r.subject)}</div>
                                <div class="att-card-meta">
                                    <span class="att-code-pill">${escapeHtml(r.code)}</span>
                                    <span style="color:rgba(255,255,255,0.2);">•</span>
                                    <span class="att-instructor-pill"><i class="bi bi-person-fill text-gold-muted me-1"></i>${escapeHtml(r.instructor)}</span>
                                </div>
                            </div>
                            <span class="att-status-badge ${st}">
                                <i class="bi ${iconClass}"></i> ${escapeHtml(r.status)}
                            </span>
                        </div>

                        <div class="att-info-grid">
                            <div>
                                <div class="att-grid-col-label">
                                    <i class="bi bi-calendar3 text-gold"></i> Schedule
                                </div>
                                <div class="att-grid-col-val">${escapeHtml(r.schedule)}</div>
                            </div>
                            <div>
                                <div class="att-grid-col-label">
                                    <i class="bi bi-fingerprint text-gold"></i> Actual Clock-in
                                </div>
                                <div class="att-grid-col-val" style="color:${isNoClockIn ? '#f87171' : clockInColor};">
                                    ${isNoClockIn ? '<i class="bi bi-x-circle me-1"></i>' : (st === 'present' ? '<i class="bi bi-check2 me-1"></i>' : '<i class="bi bi-clock-history me-1"></i>')}
                                    ${escapeHtml(clockInText)}
                                </div>
                            </div>
                            ${r.time_out ? `
                            <div>
                                <div class="att-grid-col-label">
                                    <i class="bi bi-box-arrow-right text-gold"></i> Clock-out
                                </div>
                                <div class="att-grid-col-val" style="color:#b39b82;">${escapeHtml(r.time_out)}</div>
                            </div>` : ''}
                            ${r.remarks ? `
                            <div style="grid-column: 1 / -1;">
                                <div class="att-grid-col-label">
                                    <i class="bi bi-chat-left-text text-gold"></i> Remarks
                                </div>
                                <div style="font-size:0.82rem; font-weight:500; color:#ffd166;">${escapeHtml(r.remarks)}</div>
                            </div>` : ''}
                        </div>

                        ${st === 'absent' ? `
                        <a href="{{ route('excuses.create_general') }}" class="day-card-excuse-btn">
                            <span><i class="bi bi-file-earmark-medical me-1"></i> File Excuse Request for this Absence</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>` : ''}
                    </div>
                `;
            });

            fullHtml += `</div>`;
        }

        if (dayEvents.length > 0) {
            fullHtml += `
                <div class="day-section-header mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="day-section-icon" style="background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3);">
                            <i class="bi bi-calendar-event-fill"></i>
                        </span>
                        <span class="day-section-label">EVENTS / IMPORTANT DATES</span>
                    </div>
                    <span class="day-section-badge" style="background: rgba(139, 92, 246, 0.12); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.25);">
                        ${dayEvents.length} ${dayEvents.length === 1 ? 'Entry' : 'Entries'}
                    </span>
                </div>
                <div class="d-flex flex-column gap-3">
            `;

            dayEvents.forEach(evt => {
                const isExam = evt.type === 'exam';
                const isHoliday = evt.type === 'holiday';

                let badgeText = 'Event';
                let badgeStyle = 'background: rgba(139, 92, 246, 0.15); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.35);';
                let borderStyle = 'border-left: 4px solid #8b5cf6;';
                let iconClass = 'bi-calendar-event-fill';

                if (isExam) {
                    badgeText = 'Exam';
                    badgeStyle = 'background: rgba(236, 72, 153, 0.15); color: #f472b6; border: 1px solid rgba(236, 72, 153, 0.35);';
                    borderStyle = 'border-left: 4px solid #ec4899;';
                    iconClass = 'bi-award-fill';
                } else if (isHoliday) {
                    badgeText = 'Holiday';
                    badgeStyle = 'background: rgba(74, 222, 128, 0.15); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.35);';
                    borderStyle = 'border-left: 4px solid #4ade80;';
                    iconClass = 'bi-flag-fill';
                }

                let metaRows = '';
                if (evt.subject) {
                    metaRows += `
                        <div style="font-size: 0.95rem; font-weight: 700; color: #ffd166; margin-top: 6px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-book-half" style="font-size: 0.85rem; color: #cfa46f;"></i>
                            <span>${escapeHtml(evt.subject)}</span>
                        </div>
                    `;
                }
                if (evt.time) {
                    metaRows += `
                        <div style="font-size: 0.85rem; font-weight: 600; color: #f3ede4; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-clock-fill" style="font-size: 0.8rem; color: #cfa46f;"></i>
                            <span>${escapeHtml(evt.time)}</span>
                        </div>
                    `;
                }
                if (evt.location) {
                    metaRows += `
                        <div style="font-size: 0.85rem; font-weight: 600; color: #b39b82; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-geo-alt-fill" style="font-size: 0.8rem; color: #cfa46f;"></i>
                            <span>${escapeHtml(evt.location)}</span>
                        </div>
                    `;
                }
                if (evt.description && evt.description.trim() !== '') {
                    metaRows += `
                        <div style="font-size: 0.82rem; color: #8f826f; margin-top: 6px; line-height: 1.45;">
                            ${escapeHtml(evt.description)}
                        </div>
                    `;
                }

                fullHtml += `
                    <div style="background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.08); ${borderStyle} border-radius: 14px; padding: 16px 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 10px; border-radius: 99px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; ${badgeStyle} margin-bottom: 6px;">
                                    <i class="bi ${iconClass}"></i>
                                    <span>${badgeText}</span>
                                </div>
                                <div style="font-weight: 800; color: #f3ede4; font-size: 1.1rem; line-height: 1.3;">
                                    ${escapeHtml(evt.title)}
                                </div>
                                ${metaRows}
                            </div>
                        </div>
                    </div>
                `;
            });

            fullHtml += `</div>`;
        }

        if (contentEl) contentEl.innerHTML = fullHtml;
    }

    openDaySummaryInspector();
    if (window.triggerHaptic) window.triggerHaptic('light');
}

function openDaySummaryInspector() {
    const modalEl = document.getElementById('daySummaryModal');
    if (modalEl) {
        if (modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }
        try {
            if (!dayModalInstance && window.bootstrap && window.bootstrap.Modal) {
                dayModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            }
            if (dayModalInstance) {
                dayModalInstance.show();
            } else {
                throw new Error('Bootstrap Modal not available');
            }
        } catch (err) {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            modalEl.setAttribute('aria-modal', 'true');
            modalEl.removeAttribute('aria-hidden');
            document.body.classList.add('modal-open');
            let fb = document.querySelector('.scal-fallback-backdrop');
            if (!fb) {
                fb = document.createElement('div');
                fb.className = 'modal-backdrop fade show scal-fallback-backdrop';
                fb.style.zIndex = '10050';
                document.body.appendChild(fb);
                fb.onclick = closeDaySummaryModal;
            }
        }
    }
}

async function loadAttendanceRecordsForModal() {
    if (attendanceHistoryLoaded) {
        filterAttendanceRecords();
        return;
    }
    if (attendanceHistoryRequest) return attendanceHistoryRequest;

    const listEl = document.getElementById('recordsModalList');
    if (listEl) {
        listEl.innerHTML = `
            <div style="text-align:center;padding:40px 20px;color:#b39b82;">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Loading attendance records…
            </div>`;
    }

    attendanceHistoryRequest = fetch('{{ route('attendance.history.data') }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) throw new Error('Attendance history request failed');
            return response.json();
        })
        .then(data => {
            allAttendanceRecords = Array.isArray(data.records) ? data.records : [];
            attendanceHistoryLoaded = true;

            const monthFilter = document.getElementById('recordsMonthFilter');
            if (monthFilter) {
                while (monthFilter.options.length > 1) monthFilter.remove(1);
                const months = [...new Set(allAttendanceRecords.map(record => record.date_month).filter(Boolean))]
                    .sort((a, b) => b.localeCompare(a));
                months.forEach(month => {
                    const [year, monthNumber] = month.split('-').map(Number);
                    const option = document.createElement('option');
                    option.value = month;
                    option.textContent = new Date(year, monthNumber - 1, 1)
                        .toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
                    monthFilter.appendChild(option);
                });
            }

            filterAttendanceRecords();
        })
        .catch(() => {
            if (listEl) {
                listEl.innerHTML = `
                    <div style="text-align:center;padding:40px 20px;color:#b39b82;">
                        Could not load attendance records. Close this window and try again.
                    </div>`;
            }
        })
        .finally(() => {
            attendanceHistoryRequest = null;
        });

    return attendanceHistoryRequest;
}

function openAttendanceRecordsModal(initialStatus) {
    const statusFilterEl = document.getElementById('recordsStatusFilter');
    if (statusFilterEl && typeof initialStatus === 'string') {
        statusFilterEl.value = initialStatus;
    } else if (statusFilterEl && !initialStatus) {
        statusFilterEl.value = 'all';
    }
    const modalEl = document.getElementById('attendanceRecordsModal');
    if (modalEl) {
        if (modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }
        try {
            if (!recordsModalInstance && window.bootstrap && window.bootstrap.Modal) {
                recordsModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            }
            if (recordsModalInstance) {
                recordsModalInstance.show();
            } else {
                throw new Error('Bootstrap Modal not available');
            }
        } catch (err) {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            modalEl.setAttribute('aria-modal', 'true');
            modalEl.removeAttribute('aria-hidden');
            document.body.classList.add('modal-open');
        }
    }
    loadAttendanceRecordsForModal().then(() => {
        if (statusFilterEl && typeof initialStatus === 'string') {
            statusFilterEl.value = initialStatus;
            filterAttendanceRecords();
        }
    });
    if (window.triggerHaptic) window.triggerHaptic('light');
}

function openSubjectBreakdownModal() {
    const modalEl = document.getElementById('subjectBreakdownModal');
    if (!modalEl) return;
    if (modalEl.parentNode !== document.body) {
        document.body.appendChild(modalEl);
    }
    try {
        if (window.bootstrap && window.bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            throw new Error('Bootstrap not ready');
        }
    } catch (err) {
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.setAttribute('aria-modal', 'true');
        modalEl.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
    }
    if (window.triggerHaptic) window.triggerHaptic('light');
}

(function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('open_breakdown') === '1') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(openSubjectBreakdownModal, 400);
        });
    }
})();

function filterAttendanceRecords() {
    const searchVal = (document.getElementById('recordsSearchInput')?.value || '').toLowerCase().trim();
    const statusVal = (document.getElementById('recordsStatusFilter')?.value || 'all').toLowerCase();
    const monthVal = (document.getElementById('recordsMonthFilter')?.value || 'all');

    const filtered = allAttendanceRecords.filter(r => {
        if (searchVal) {
            const matchSubj = (r.subject || '').toLowerCase().includes(searchVal);
            const matchCode = (r.code || '').toLowerCase().includes(searchVal);
            const matchInst = (r.instructor || '').toLowerCase().includes(searchVal);
            if (!matchSubj && !matchCode && !matchInst) return false;
        }
        if (statusVal !== 'all') {
            if (statusVal === 'excused' && !r.excused) return false;
            if (statusVal !== 'excused' && r.status_lower !== statusVal) return false;
        }
        if (monthVal !== 'all') {
            if (r.date_month !== monthVal) return false;
        }
        return true;
    });

    renderAttendanceRecordsList(filtered);
}

function renderAttendanceRecordsList(items) {
    const listEl = document.getElementById('recordsModalList');
    const countEl = document.getElementById('recordsModalCount');
    if (!listEl) return;

    if (countEl) {
        countEl.textContent = `${items.length} ${items.length === 1 ? 'Record' : 'Records'}`;
    }

    if (items.length === 0) {
        listEl.innerHTML = `
            <div style="text-align:center; padding:40px 20px; color:#8f826f; background:rgba(255,255,255,0.02); border-radius:18px; border:1px dashed rgba(255,255,255,0.08);">
                <i class="bi bi-search" style="font-size:2rem; color:#cfa46f; display:block; margin-bottom:10px; opacity:0.6;"></i>
                <div style="font-weight:700; font-size:0.95rem; color:#f3ede4;">No records match your filters</div>
                <div style="font-size:0.8rem; margin-top:4px;">Try clearing your search query or changing filters.</div>
            </div>
        `;
        return;
    }

    let html = '';
    items.forEach(r => {
        const st = (r.status_lower || 'absent');
        let iconClass = 'bi-x-circle-fill';
        let clockInColor = '#f87171';

        if (st === 'present') {
            iconClass = 'bi-check-circle-fill';
            clockInColor = '#4ade80';
        } else if (st === 'late') {
            iconClass = 'bi-clock-fill';
            clockInColor = '#fbbf24';
        }

        const clockInText = r.clock_in || (st === 'absent' ? 'No clock-in' : '—');
        const isNoClockIn = clockInText.toLowerCase().includes('no clock-in');

        html += `
            <div class="record-modal-item" style="background:rgba(255,255,255,0.025); border:1px solid rgba(255,255,255,0.06); border-radius:16px; padding:16px 18px; margin-bottom:10px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:10px; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:0.8rem; font-weight:700; color:#ffd166; display:flex; align-items:center; gap:6px;">
                            <i class="bi bi-calendar-event"></i> ${escapeHtml(r.date_formatted)}
                            <span style="color:rgba(255,255,255,0.3); font-weight:400;">(${escapeHtml(r.day_name)})</span>
                        </div>
                        <h4 style="margin:4px 0 0; font-size:1.02rem; font-weight:800; color:#f3ede4; line-height:1.3;">
                            ${escapeHtml(r.subject)}
                        </h4>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span class="subject-card-badge ${st}" style="padding:5px 12px; border-radius:8px; font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; display:inline-flex; align-items:center; gap:5px;">
                            <i class="bi ${iconClass}"></i> ${escapeHtml(r.status)}
                        </span>
                        ${r.excused ? `<span style="padding:5px 10px; border-radius:8px; font-size:0.72rem; font-weight:700; background:rgba(207,164,111,0.15); color:#ffd166; border:1px solid rgba(207,164,111,0.3);">Excused</span>` : ''}
                    </div>
                </div>

                <div style="font-size:0.8rem; color:#cfa46f; margin-bottom:12px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span style="background:rgba(207,164,111,0.12); padding:2px 8px; border-radius:6px; font-weight:700; border:1px solid rgba(207,164,111,0.25);">${escapeHtml(r.code)}</span>
                    <span style="color:rgba(255,255,255,0.25);">•</span>
                    <span style="color:#b39b82;"><i class="bi bi-person me-1"></i>${escapeHtml(r.instructor)}</span>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px; background:rgba(0,0,0,0.25); border:1px solid rgba(255,255,255,0.03); border-radius:12px; padding:12px 14px;">
                    <div>
                        <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                            <i class="bi bi-clock me-1"></i> Scheduled
                        </div>
                        <div style="font-size:0.82rem; font-weight:600; color:#f3ede4;">${escapeHtml(r.schedule)}</div>
                    </div>
                    <div>
                        <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Actual Clock-in
                        </div>
                        <div style="font-size:0.82rem; font-weight:700; color:${isNoClockIn ? '#f87171' : clockInColor};">
                            ${escapeHtml(clockInText)}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                            <i class="bi bi-box-arrow-right me-1"></i> Clock-out
                        </div>
                        <div style="font-size:0.82rem; font-weight:600; color:#b39b82;">${escapeHtml(r.clock_out)}</div>
                    </div>
                    ${r.remarks && r.remarks !== '—' && r.remarks !== 'None' ? `
                    <div style="grid-column:1 / -1;">
                        <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                            <i class="bi bi-chat-left-text me-1"></i> Remarks
                        </div>
                        <div style="font-size:0.8rem; font-weight:500; color:#ffd166;">${escapeHtml(r.remarks)}</div>
                    </div>` : ''}
                </div>
            </div>
        `;
    });
    listEl.innerHTML = html;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', function() {
    const dModal = document.getElementById('daySummaryModal');
    if (dModal) {
        dModal.addEventListener('hide.bs.modal', function() {
            currentSelectedDateKey = null;
        });
        if (dModal.parentNode !== document.body) {
            document.body.appendChild(dModal);
        }
    }
    const rModal = document.getElementById('attendanceRecordsModal');
    if (rModal && rModal.parentNode !== document.body) {
        document.body.appendChild(rModal);
    }
    const sModal = document.getElementById('subjectBreakdownModal');
    if (sModal && sModal.parentNode !== document.body) {
        document.body.appendChild(sModal);
    }

    document.querySelectorAll('.scal-tile:not(.empty)').forEach(tile => {
        tile.addEventListener('click', function() {
            const dKey = this.getAttribute('data-date');
            const dNum = parseInt(this.getAttribute('data-day'), 10);
            if (dKey) {
                selectCalendarDay(dKey, dNum, true);
            }
        });
        tile.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const dKey = this.getAttribute('data-date');
                const dNum = parseInt(this.getAttribute('data-day'), 10);
                if (dKey) {
                    selectCalendarDay(dKey, dNum, true);
                }
            }
        });
    });
});
</script>

<script nonce="{{ csp_nonce() }}">
(function() {
    function formatRemainingMinutes(diffMs) {
        const totalMins = Math.max(1, Math.ceil(diffMs / 60000));
        if (totalMins < 60) {
            return totalMins + 'm';
        }
        const hours = Math.floor(totalMins / 60);
        const mins = totalMins % 60;
        return mins > 0 ? (hours + 'h ' + mins + 'm') : (hours + 'h');
    }

    function tick() {
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        const short = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
        const clockEl = document.getElementById('studentClock');
        if (clockEl) clockEl.textContent = short;

        const nowMs = now.getTime();
        const spotlight = document.getElementById('sdHeroSpotlight');
        if (spotlight) {
            const startMs = Number(spotlight.getAttribute('data-start-ms') || 0);
            const endMs = Number(spotlight.getAttribute('data-end-ms') || 0);
            const timerEl = document.getElementById('sdSpotlightTimer');
            const progressFill = document.getElementById('sdSpotlightProgressFill');

            if (startMs && endMs && timerEl) {
                if (nowMs >= startMs && nowMs <= endMs) {
                    const leftText = formatRemainingMinutes(endMs - nowMs);
                    timerEl.textContent = leftText + ' left';
                    if (progressFill) {
                        const pct = Math.min(100, Math.max(4, Math.round(((nowMs - startMs) / Math.max(1, endMs - startMs)) * 100)));
                        progressFill.style.width = pct + '%';
                    }
                } else if (nowMs < startMs) {
                    timerEl.textContent = 'Starts in ' + formatRemainingMinutes(startMs - nowMs);
                } else {
                    timerEl.textContent = 'Session ended';
                    if (progressFill) progressFill.style.width = '100%';
                }
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        tick();

        document.querySelectorAll('[data-sched-filter]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const mode = btn.getAttribute('data-sched-filter');
                document.querySelectorAll('[data-sched-filter]').forEach(function(peer) {
                    const active = peer === btn;
                    peer.classList.toggle('is-active', active);
                    peer.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
                const items = document.querySelectorAll('#todayScheduleSection .sd-timeline-item');
                let visibleCount = 0;
                items.forEach(function(item) {
                    const status = item.getAttribute('data-sched-status') || '';
                    let show = true;
                    if (mode === 'remaining') {
                        show = status === 'ongoing' || status === 'upcoming';
                    } else if (mode === 'done') {
                        show = status === 'completed';
                    }
                    item.classList.toggle('is-filtered-hidden', !show);
                    if (show) visibleCount++;
                });
                const emptyFilterEl = document.getElementById('sdScheduleFilterEmpty');
                if (emptyFilterEl) {
                    emptyFilterEl.hidden = visibleCount > 0;
                }
            });
        });

        document.querySelectorAll('[data-announcement-toggle]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const copyId = btn.getAttribute('data-announcement-toggle');
                const copyEl = copyId ? document.getElementById(copyId) : null;
                if (!copyEl) return;
                const expanded = btn.getAttribute('aria-expanded') === 'true';
                copyEl.classList.toggle('is-clamped', expanded);
                btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                btn.textContent = expanded ? 'Read more' : 'Show less';
            });
        });
    });

    setInterval(tick, 1000);
})();
</script>
@endsection
