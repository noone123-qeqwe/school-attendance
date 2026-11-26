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
    $completedTodayCount = $todaySchedCollection->where('status', 'completed')->count();
    $remainingTodayCount = $todaySchedCollection->whereIn('status', ['ongoing', 'upcoming'])->count();

    $liveClassItem = $todaySchedCollection->firstWhere('status', 'ongoing');
    $nextClassItem = $todaySchedCollection->firstWhere('status', 'upcoming');
    $featuredClass = $liveClassItem ?? $nextClassItem;
    $featuredRoom = trim((string) ($featuredClass?->schedule?->room ?? ''));
    $featuredInstructor = trim((string) ($featuredClass?->subject?->instructorUser?->name ?? $featuredClass?->subject?->instructor ?? ''));

    $greetHour = now()->hour;
    $greeting = $greetHour < 12 ? 'Good Morning' : ($greetHour < 17 ? 'Good Afternoon' : 'Good Evening');
    $greetIcon = $greetHour < 12 ? 'bi-brightness-alt-high-fill' : ($greetHour < 17 ? 'bi-sun-fill' : 'bi-moon-stars-fill');

    $rateVal = $attendanceRate;
    $rateTone = $rateVal === null ? 'rate-empty' : ($rateVal >= 85 ? 'rate-good' : ($rateVal >= 75 ? 'rate-warning' : 'rate-danger'));
    $rateTier = $rateVal === null ? 'New' : ($rateVal >= 85 ? 'Good' : ($rateVal >= 75 ? 'Fair' : 'At Risk'));
    $gaugeRadius = 34;
    $gaugeCircumference = round(2 * pi() * $gaugeRadius, 2);
    $gaugePct = $rateVal === null ? 0 : min(100, max(0, (float) $rateVal));
    $gaugeOffset = round($gaugeCircumference * (1 - ($gaugePct / 100)), 2);

    $subjectCount = isset($subjects) ? count($subjects) : 0;
    $computedTotalAll = ($totalPresent ?? 0) + ($totalLate ?? 0) + ($totalAbsent ?? 0);
    $presentShare = $computedTotalAll > 0 ? round((($totalPresent ?? 0) / $computedTotalAll) * 100) : null;
    $lateShare = $computedTotalAll > 0 ? round((($totalLate ?? 0) / $computedTotalAll) * 100) : null;
    $absentShare = $computedTotalAll > 0 ? round((($totalAbsent ?? 0) / $computedTotalAll) * 100) : null;

    $weeklyPulseCollection = collect($weeklyPulse ?? []);
    $recentAttendanceCollection = collect($recentAttendances ?? []);
    $pendingExcuses = (int) ($pendingExcusesCount ?? 0);
@endphp

<div class="sd-cockpit">
    {{-- ── Missing Section / Error / Active Warnings ────────────────────── --}}
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
    <section class="sd-warning-panel" role="region" aria-label="Attendance Warnings">
        <div class="sd-warning-top">
            <div class="sd-warning-icon" aria-hidden="true">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <h2 class="sd-warning-title">Action Required: Attendance Warning</h2>
                <p class="sd-warning-sub">You have {{ $activeWarnings->count() }} active {{ \Illuminate\Support\Str::plural('warning', $activeWarnings->count()) }}. Please review your attendance immediately.</p>
            </div>
        </div>

        <div class="sd-warning-list">
            @foreach($activeWarnings as $warning)
            <div class="sd-warning-card">
                <div class="sd-warning-card-head">
                    <span class="sd-warning-code sd-mono"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> {{ $warning->subject_code }}</span>
                    <span class="sd-warning-time"><i class="bi bi-clock" aria-hidden="true"></i> {{ $warning->created_at->diffForHumans() }}</span>
                </div>
                <div class="sd-warning-msg">{{ $warning->message }}</div>
            </div>
            @endforeach
        </div>

        <div class="sd-warning-actions">
            <a href="{{ route('excuses') }}" class="sd-warning-btn-primary">
                <i class="bi bi-file-text-fill" aria-hidden="true"></i> Submit Excuse
            </a>
            <a href="{{ route('student.calendar') }}#attendance-records" class="sd-warning-btn-ghost">
                <i class="bi bi-journal-text" aria-hidden="true"></i> View Records
            </a>
        </div>
    </section>
    @endif

    {{-- ── 1. Top Command & Identity Ribbon ─────────────────────────────── --}}
    <section class="sd-command-bar" aria-label="Student identity and quick check-in">
        <div class="sd-identity-cluster">
            <div class="sd-avatar-ring">
                <img src="{{ Auth::user()->profile_photo_url_with_version }}" alt="{{ Auth::user()->name }}">
                <span class="sd-avatar-live-dot" title="Active Student" aria-hidden="true"></span>
            </div>
            <div class="sd-identity-copy">
                <div class="sd-kicker-row">
                    <span class="sd-greeting-pill">
                        <i class="bi {{ $greetIcon }}" aria-hidden="true"></i>
                        <span>{{ $greeting }}</span>
                    </span>
                    <span class="sd-clock-pill sd-mono" title="Campus Local Time">
                        <i class="bi bi-clock-fill" aria-hidden="true"></i>
                        <span id="studentClock">{{ now()->format('g:i A') }}</span>
                        <span aria-hidden="true">&middot;</span>
                        <span>{{ now()->format('D, M j') }}</span>
                    </span>
                </div>
                <h1 class="sd-student-name">{{ Auth::user()->name }}</h1>
                <div class="sd-meta-ribbon">
                    @if(!empty(Auth::user()->student_number))
                        <span class="sd-meta-tag is-id sd-mono" title="Student Identification Number">
                            <i class="bi bi-person-badge" aria-hidden="true"></i>
                            ID {{ Auth::user()->student_number }}
                        </span>
                    @endif
                    <span class="sd-meta-tag" title="Degree Program">
                        <i class="bi bi-mortarboard" aria-hidden="true"></i>
                        {{ Auth::user()->course ?? 'BSCS' }}
                    </span>
                    <span class="sd-meta-tag" title="Academic Standing">
                        <i class="bi bi-layers" aria-hidden="true"></i>
                        Yr {{ Auth::user()->year_level ?? '1' }} &middot; Sem {{ Auth::user()->semester ?? '1' }}
                    </span>
                    @if(!empty(Auth::user()->section))
                        <span class="sd-meta-tag" title="Class Section">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            Sec {{ Auth::user()->section }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="sd-command-actions">
            <a class="sd-cta-primary" href="{{ route('mobile.scan', ['open_scanner' => 1]) }}">
                <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                <span>Scan Attendance QR</span>
            </a>
            <a class="sd-cta-secondary" href="{{ route('mobile.scan', ['open_code' => 1]) }}">
                <i class="bi bi-123" aria-hidden="true"></i>
                <span>Enter 6-Digit PIN</span>
            </a>
        </div>
    </section>

    {{-- ── 2. Asymmetric Bento Hero: Live Focus Stage + Standing & 7-Day Pulse ── --}}
    <div class="sd-bento-hero">
        {{-- Live / Next Class Focus Stage --}}
        <section id="sdHeroSpotlight"
                 class="sd-focus-stage {{ $liveClassItem ? 'is-live' : ($nextClassItem ? 'is-next' : 'is-idle') }}"
                 aria-labelledby="dashboardClassHeading"
                 @if($featuredClass)
                    data-start-ms="{{ $featuredClass->start_time->getTimestampMs() }}"
                    data-end-ms="{{ $featuredClass->end_time->getTimestampMs() }}"
                 @endif>
            <div class="sd-focus-head">
                <span class="sd-focus-eyebrow">
                    <span class="sd-pulse-dot {{ $liveClassItem ? 'is-live' : ($nextClassItem ? 'is-next' : 'is-done') }}" aria-hidden="true"></span>
                    {{ $liveClassItem ? 'Class in Progress' : ($nextClassItem ? 'Up Next Today' : "Today's Status") }}
                </span>
                @if($featuredClass)
                    <span class="sd-focus-badges">
                        <span id="sdSpotlightTimer" class="sd-focus-timer sd-mono" aria-live="off"></span>
                        <span class="sd-focus-code sd-mono">{{ $featuredClass->subject->code }}</span>
                    </span>
                @endif
            </div>

            <div class="sd-focus-body">
                <h2 id="dashboardClassHeading" class="sd-focus-title">
                    {{ $featuredClass?->subject?->name ?? ($todayClassCount ? 'All classes completed today' : 'Free schedule today') }}
                </h2>

                @if($featuredClass)
                    <div class="sd-focus-meta sd-tabular">
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
                        <div class="sd-session-meter" aria-hidden="true">
                            <div class="sd-session-meter-track">
                                <span id="sdSpotlightProgressFill" class="sd-session-meter-fill" style="width: {{ $spotProgressPct }}%;"></span>
                            </div>
                        </div>
                    @endif
                @else
                    <p class="sd-focus-note">
                        {{ $todayClassCount ? 'You have wrapped up all scheduled sessions for today. Check your attendance log or upcoming week anytime.' : 'No sessions scheduled today. Review your weekly timetable, subject standing, or excuse requests at your pace.' }}
                    </p>
                @endif
            </div>

            <div class="sd-focus-foot">
                <div class="sd-focus-actions">
                    @if($liveClassItem && empty($alreadyClockedIn))
                        <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sd-focus-checkin-btn">
                            <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Check in now
                        </a>
                    @elseif($liveClassItem && !empty($alreadyClockedIn))
                        <span class="sd-focus-checked-pill">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i> Checked in
                        </span>
                    @endif
                    <a href="{{ route('student.schedule', ['view' => 'weekly']) }}" class="sd-focus-link">
                        View timetable <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
                <span class="sd-day-progress-pill sd-tabular">
                    <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                    @if($todayClassCount > 0)
                        <strong>{{ $completedTodayCount }}/{{ $todayClassCount }}</strong> done today
                    @else
                        <strong>0</strong> classes today
                    @endif
                </span>
            </div>
        </section>

        {{-- Standing Gauge + 7-Day Interactive Attendance Pulse --}}
        <section class="sd-standing-card" aria-label="Attendance standing and 7-day pulse">
            <div class="sd-standing-top">
                <div class="sd-gauge-wrap" title="{{ $rateVal === null ? 'Attendance rate appears after your first recorded class' : 'Overall Attendance Rate: '.$rateVal.'%' }}">
                    <svg class="sd-gauge-svg" viewBox="0 0 82 82" aria-hidden="true">
                        <circle class="sd-gauge-track" cx="41" cy="41" r="{{ $gaugeRadius }}"></circle>
                        <circle class="sd-gauge-fill is-{{ $rateTone }}"
                                cx="41" cy="41" r="{{ $gaugeRadius }}"
                                stroke-dasharray="{{ $gaugeCircumference }}"
                                stroke-dashoffset="{{ $gaugeOffset }}"></circle>
                    </svg>
                    <div class="sd-gauge-center">
                        <span class="sd-gauge-value sd-tabular is-{{ $rateTone }}">{{ $rateVal === null ? '—' : $rateVal.'%' }}</span>
                        <span class="sd-gauge-sub">Rate</span>
                    </div>
                </div>

                <div class="sd-standing-info">
                    <div class="sd-standing-label-row">
                        <h2 class="sd-standing-title">Attendance Standing</h2>
                        <span class="sd-standing-tier is-{{ $rateTone }}">{{ $rateTier }}</span>
                    </div>
                    <p class="sd-standing-desc">
                        @if($rateVal === null)
                            Your standing unlocks as soon as your first session is recorded.
                        @elseif($rateVal >= 85)
                            Strong consistency across {{ $computedTotalAll }} {{ \Illuminate\Support\Str::plural('session', $computedTotalAll) }}.
                        @elseif($rateVal >= 75)
                            Stay above 85% to keep your standing safe.
                        @else
                            Below the 75% threshold &mdash; prioritize upcoming check-ins.
                        @endif
                    </p>
                    <div class="sd-standing-chips">
                        @if(isset($streakCount) && $streakCount > 0)
                            <span class="sd-mini-chip is-streak sd-tabular">
                                <i class="bi bi-fire" aria-hidden="true"></i> {{ $streakCount }} {{ $streakCount === 1 ? 'Day' : 'Days' }} Streak
                            </span>
                        @endif
                        @if($pendingExcuses > 0)
                            <a href="{{ route('excuses') }}" class="sd-mini-chip is-pending sd-tabular">
                                <i class="bi bi-hourglass-split" aria-hidden="true"></i> {{ $pendingExcuses }} Pending {{ \Illuminate\Support\Str::plural('Excuse', $pendingExcuses) }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            @if($weeklyPulseCollection->isNotEmpty())
            <div class="sd-pulse-strip-wrap">
                <div class="sd-pulse-strip-head">
                    <span class="sd-pulse-strip-label">7-Day Attendance Pulse</span>
                    <span class="sd-pulse-strip-hint">Tap a day to inspect</span>
                </div>
                <div class="sd-pulse-grid" role="group" aria-label="Last 7 days attendance summary">
                    @foreach($weeklyPulseCollection as $pulse)
                        <button type="button"
                                class="sd-pulse-day is-status-{{ $pulse->status }} {{ $pulse->is_today ? 'is-today' : '' }}"
                                onclick="selectCalendarDay('{{ $pulse->date_key }}', {{ $pulse->day_num }}, true)"
                                title="{{ $pulse->label }}: {{ ucfirst($pulse->status === 'empty' ? 'No records' : $pulse->status) }}"
                                aria-label="Inspect {{ $pulse->label }}">
                            <span class="sd-pulse-day-short">{{ $pulse->day_short }}</span>
                            <span class="sd-pulse-day-num sd-tabular">{{ $pulse->day_num }}</span>
                            <span class="sd-pulse-pip" aria-hidden="true"></span>
                        </button>
                    @endforeach
                </div>
            </div>
            @endif
        </section>
    </div>

    {{-- ── 3. Quick Workspace Dock ──────────────────────────────────────── --}}
    <nav class="sd-quick-dock" aria-label="Student quick shortcuts">
        <a href="{{ route('student.schedule', ['view' => 'weekly']) }}" class="sd-dock-pill">
            <span class="sd-dock-icon" aria-hidden="true"><i class="bi bi-calendar-week"></i></span>
            <span class="sd-dock-copy">
                <strong>Weekly Schedule</strong>
                <small>Full class timetable</small>
            </span>
            <i class="bi bi-chevron-right sd-dock-arrow" aria-hidden="true"></i>
        </a>
        <a href="{{ route('student.classes') }}" class="sd-dock-pill">
            <span class="sd-dock-icon" aria-hidden="true"><i class="bi bi-journal-bookmark"></i></span>
            <span class="sd-dock-copy">
                <strong>My Classes</strong>
                <small>Subjects, units &amp; rooms</small>
            </span>
            <i class="bi bi-chevron-right sd-dock-arrow" aria-hidden="true"></i>
        </a>
        <a href="{{ route('student.calendar') }}#attendance-records" class="sd-dock-pill">
            <span class="sd-dock-icon" aria-hidden="true"><i class="bi bi-clipboard-check"></i></span>
            <span class="sd-dock-copy">
                <strong>Attendance Log</strong>
                <small>History, calendar &amp; appeals</small>
            </span>
            <i class="bi bi-chevron-right sd-dock-arrow" aria-hidden="true"></i>
        </a>
        <a href="{{ route('excuses.create_general') }}" class="sd-dock-pill">
            <span class="sd-dock-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
            <span class="sd-dock-copy">
                <strong>Submit Excuse</strong>
                <small>Upload documentation</small>
            </span>
            @if($pendingExcuses > 0)
                <span class="sd-dock-badge sd-tabular">{{ $pendingExcuses }}</span>
            @else
                <i class="bi bi-chevron-right sd-dock-arrow" aria-hidden="true"></i>
            @endif
        </a>
        <a href="{{ route('profile') }}" class="sd-dock-pill d-md-none">
            <span class="sd-dock-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
            <span class="sd-dock-copy"><strong>My Profile</strong></span>
            <i class="bi bi-chevron-right sd-dock-arrow" aria-hidden="true"></i>
        </a>
        @if(!empty($hasActiveAssistantAssignment))
            <a href="{{ route('student-assistant.classes') }}" class="sd-dock-pill d-md-none">
                <span class="sd-dock-icon" aria-hidden="true"><i class="bi bi-qr-code"></i></span>
                <span class="sd-dock-copy"><strong>Student Assistant</strong></span>
                <i class="bi bi-chevron-right sd-dock-arrow" aria-hidden="true"></i>
            </a>
        @endif
    </nav>

    {{-- ── 4. Attendance Overview KPI Bento ─────────────────────────────── --}}
    <section aria-labelledby="attendanceOverviewHeading">
        <div class="sd-section-head">
            <h2 id="attendanceOverviewHeading">Attendance Overview</h2>
            <a href="{{ route('student.calendar') }}#attendance-records">Open calendar &amp; log <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="sd-kpi-grid" id="realStats">
            <button type="button" class="sd-kpi-trigger" onclick="openAttendanceRecordsModal('present')" title="Filter attendance log by Present">
                <div class="sd-kpi-tile" data-accent="success">
                    <div class="sd-kpi-tile-top">
                        <span class="sd-kpi-badge" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></span>
                        <i class="bi bi-arrow-up-right sd-kpi-arrow" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="sd-kpi-num-row">
                            <span class="sd-kpi-val sd-tabular">{{ $totalPresent ?? 0 }}</span>
                            <span class="sd-kpi-lbl">Present</span>
                        </div>
                        <div class="sd-kpi-meta">{{ $presentShare !== null ? $presentShare.'% of recorded sessions' : 'On-time check-ins' }}</div>
                    </div>
                    <div class="sd-kpi-bar" aria-hidden="true">
                        <span class="sd-kpi-bar-fill" style="width: {{ $presentShare ?? 0 }}%;"></span>
                    </div>
                </div>
            </button>

            <button type="button" class="sd-kpi-trigger" onclick="openAttendanceRecordsModal('late')" title="Filter attendance log by Late">
                <div class="sd-kpi-tile" data-accent="warning">
                    <div class="sd-kpi-tile-top">
                        <span class="sd-kpi-badge" aria-hidden="true"><i class="bi bi-clock-fill"></i></span>
                        <i class="bi bi-arrow-up-right sd-kpi-arrow" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="sd-kpi-num-row">
                            <span class="sd-kpi-val sd-tabular">{{ $totalLate ?? 0 }}</span>
                            <span class="sd-kpi-lbl">Late</span>
                        </div>
                        <div class="sd-kpi-meta">{{ $lateShare !== null ? $lateShare.'% of recorded sessions' : 'Late arrivals' }}</div>
                    </div>
                    <div class="sd-kpi-bar" aria-hidden="true">
                        <span class="sd-kpi-bar-fill" style="width: {{ $lateShare ?? 0 }}%;"></span>
                    </div>
                </div>
            </button>

            <button type="button" class="sd-kpi-trigger" onclick="openAttendanceRecordsModal('absent')" title="Filter attendance log by Absent">
                <div class="sd-kpi-tile" data-accent="danger">
                    <div class="sd-kpi-tile-top">
                        <span class="sd-kpi-badge" aria-hidden="true"><i class="bi bi-x-circle-fill"></i></span>
                        <i class="bi bi-arrow-up-right sd-kpi-arrow" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="sd-kpi-num-row">
                            <span class="sd-kpi-val sd-tabular">{{ $totalAbsent ?? 0 }}</span>
                            <span class="sd-kpi-lbl">Absent</span>
                        </div>
                        <div class="sd-kpi-meta">{{ $absentShare !== null ? $absentShare.'% missed sessions' : 'Missed sessions' }}</div>
                    </div>
                    <div class="sd-kpi-bar" aria-hidden="true">
                        <span class="sd-kpi-bar-fill" style="width: {{ $absentShare ?? 0 }}%;"></span>
                    </div>
                </div>
            </button>

            <a class="sd-kpi-trigger" href="{{ route('student.classes') }}" title="View enrolled subjects">
                <div class="sd-kpi-tile" data-accent="gold">
                    <div class="sd-kpi-tile-top">
                        <span class="sd-kpi-badge" aria-hidden="true"><i class="bi bi-book-fill"></i></span>
                        <i class="bi bi-arrow-up-right sd-kpi-arrow" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="sd-kpi-num-row">
                            <span class="sd-kpi-val sd-tabular">{{ $subjectCount }}</span>
                            <span class="sd-kpi-lbl">Subjects</span>
                        </div>
                        <div class="sd-kpi-meta">{{ $computedTotalAll }} total {{ \Illuminate\Support\Str::plural('session', $computedTotalAll) }}</div>
                    </div>
                    <div class="sd-kpi-bar" aria-hidden="true">
                        <span class="sd-kpi-bar-fill" style="width: {{ $subjectCount > 0 ? 100 : 0 }}%;"></span>
                    </div>
                </div>
            </a>
        </div>
    </section>

    {{-- ── 5. Asymmetric Main Two-Column Workspace ──────────────────────── --}}
    <div class="sd-workspace-grid">
        {{-- Left Column: Today's Schedule + Recent Check-Ins --}}
        <div class="sd-col-primary">
            <section class="sd-surface-card" id="todayScheduleSection" aria-labelledby="todayScheduleHeading">
                <header class="sd-surface-head">
                    <div class="sd-surface-title-group">
                        <span class="sd-surface-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                        <div>
                            <h2 id="todayScheduleHeading" class="sd-surface-title">Today's Schedule</h2>
                            <p class="sd-surface-sub">{{ now()->format('l, F j') }} &middot; {{ $todayClassCount }} {{ \Illuminate\Support\Str::plural('class', $todayClassCount) }}</p>
                        </div>
                    </div>
                    <a href="{{ route('student.schedule', ['view' => 'weekly']) }}" class="sd-pill-btn">
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <span>Full Schedule</span>
                    </a>
                </header>

                <div class="sd-surface-body">
                    @if($todaySchedCollection->isNotEmpty())
                        @if($todayClassCount >= 3)
                            <div class="sd-filter-bar" role="group" aria-label="Filter today's classes">
                                <button type="button" class="sd-filter-btn is-active" data-sched-filter="all" aria-pressed="true">
                                    All ({{ $todayClassCount }})
                                </button>
                                <button type="button" class="sd-filter-btn" data-sched-filter="remaining" aria-pressed="false">
                                    Remaining ({{ $remainingTodayCount }})
                                </button>
                                <button type="button" class="sd-filter-btn" data-sched-filter="done" aria-pressed="false">
                                    Done ({{ $completedTodayCount }})
                                </button>
                            </div>
                        @endif

                        <div class="sd-timeline">
                            @foreach($todaySchedCollection as $item)
                                @php
                                    $statusColor = $item->status === 'completed' ? '#34d399' : ($item->status === 'ongoing' ? '#fbbf24' : ($item->status === 'missed' ? '#ff6b7a' : ($item->status === 'past' ? '#b0979f' : '#e48fa0')));
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
                                <div class="sd-timeline-row sd-timeline-item is-status-{{ $item->status }}" data-sched-status="{{ $item->status }}" style="--tl-color: {{ $statusColor }};">
                                    <div class="sd-tl-timecol sd-tabular" aria-hidden="true">
                                        <strong>{{ $item->start_time->format('g:i A') }}</strong>
                                        <span>{{ $item->end_time->format('g:i A') }}</span>
                                    </div>
                                    <div class="sd-tl-rail" aria-hidden="true">
                                        <span class="sd-tl-node"></span>
                                    </div>
                                    <div class="sd-tl-content">
                                        <div class="sd-tl-subject">{{ $item->subject->name }}</div>
                                        <div class="sd-tl-meta">
                                            <span class="sd-tl-timepill sd-tabular">
                                                <i class="bi bi-clock-fill" aria-hidden="true"></i>
                                                {{ $item->start_time->format('g:i A') }} &ndash; {{ $item->end_time->format('g:i A') }}
                                            </span>
                                            <span class="sd-code-tag sd-mono">{{ $item->subject->code }}</span>
                                            @if($instructorName !== '')
                                                <span><i class="bi bi-person-fill" aria-hidden="true"></i> {{ $instructorName }}</span>
                                            @endif
                                        </div>

                                        @if($item->status === 'ongoing')
                                            <div class="sd-tl-progress" aria-hidden="true">
                                                <div class="sd-tl-progress-fill" style="width: {{ $itemLivePct }}%;"></div>
                                            </div>
                                        @endif

                                        @if(($roomName !== '' && strtoupper($roomName) !== 'TBA') || $timeInText || $item->status === 'missed' || $item->status === 'ongoing')
                                            <div class="sd-tl-chips">
                                                @if($roomName !== '' && strtoupper($roomName) !== 'TBA')
                                                    <span class="sd-tl-chip"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> {{ $roomName }}</span>
                                                @endif
                                                @if($timeInText)
                                                    <span class="sd-tl-chip is-checked"><i class="bi bi-check2-circle" aria-hidden="true"></i> Checked in {{ $timeInText }}</span>
                                                @elseif($item->status === 'ongoing')
                                                    <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sd-tl-chip is-checkin"><i class="bi bi-qr-code-scan" aria-hidden="true"></i> Check in now</a>
                                                @elseif($item->status === 'missed')
                                                    <a href="{{ $excuseHref }}" class="sd-tl-chip is-excuse"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i> Submit excuse</a>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    <div class="sd-tl-status">
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
                        <div id="sdScheduleFilterEmpty" class="sd-filter-empty" hidden>
                            No classes match this filter right now.
                        </div>
                        <span id="sdScheduleFilterStatus" class="visually-hidden" role="status" aria-live="polite" aria-atomic="true"></span>
                    @else
                        <div class="sd-empty-box">
                            <span class="sd-empty-icon" aria-hidden="true"><i class="bi bi-calendar2-check"></i></span>
                            <p class="sd-empty-title">No classes scheduled today</p>
                            <p class="sd-empty-sub">Your timetable is clear for {{ now()->format('l') }}.</p>
                            <a href="{{ route('student.schedule', ['view' => 'weekly']) }}" class="sd-pill-btn mt-2">
                                <i class="bi bi-calendar3" aria-hidden="true"></i> Open Weekly Timetable
                            </a>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Recent Check-Ins Feed --}}
            @if($recentAttendanceCollection->isNotEmpty())
            <section class="sd-surface-card" aria-labelledby="recentCheckinsHeading">
                <header class="sd-surface-head">
                    <div class="sd-surface-title-group">
                        <span class="sd-surface-icon" aria-hidden="true"><i class="bi bi-activity"></i></span>
                        <div>
                            <h2 id="recentCheckinsHeading" class="sd-surface-title">Recent Activity</h2>
                            <p class="sd-surface-sub">Latest recorded attendance sessions</p>
                        </div>
                    </div>
                    <button type="button" class="sd-pill-btn" onclick="openAttendanceRecordsModal('all')">
                        <span>View all</span>
                    </button>
                </header>
                <div class="sd-surface-body">
                    <div class="sd-recent-list">
                        @foreach($recentAttendanceCollection as $rec)
                            @php
                                $recStatus = strtolower((string) ($rec->status ?? 'absent'));
                                $recIcon = $recStatus === 'present' ? 'bi-check-circle-fill' : ($recStatus === 'late' ? 'bi-clock-fill' : 'bi-x-circle-fill');
                                $recDateLabel = $rec->date?->isToday() ? 'Today' : ($rec->date?->isYesterday() ? 'Yesterday' : $rec->date?->format('M j, Y'));
                                $recTimeLabel = $rec->time_in ? \Carbon\Carbon::parse($rec->time_in)->format('g:i A') : 'No clock-in';
                            @endphp
                            <div class="sd-recent-row">
                                <span class="sd-recent-icon is-{{ $recStatus }}" aria-hidden="true">
                                    <i class="bi {{ $recIcon }}"></i>
                                </span>
                                <div class="sd-recent-copy">
                                    <div class="sd-recent-title">{{ $rec->subject->name ?? $rec->subject_name ?? $rec->subject_code ?? 'Class' }}</div>
                                    <div class="sd-recent-sub">
                                        <span class="sd-mono">{{ $rec->subject_code ?? $rec->subject?->code }}</span> &middot; {{ $recDateLabel }}
                                    </div>
                                </div>
                                <div class="sd-recent-right">
                                    <x-badge :type="$recStatus" size="sm">{{ ucfirst($recStatus) }}</x-badge>
                                    <span class="sd-recent-time sd-tabular">{{ $recTimeLabel }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif
        </div>

        {{-- Right Column: Subject Attendance Standing + Campus Announcements --}}
        <div class="sd-col-secondary">
            <section class="sd-surface-card sd-subject-breakdown-card" aria-labelledby="sdSubjectBreakdownHeading">
                <header class="sd-surface-head">
                    <div class="sd-surface-title-group">
                        <span class="sd-surface-icon" aria-hidden="true"><i class="bi bi-bar-chart-line-fill"></i></span>
                        <div>
                            <h2 id="sdSubjectBreakdownHeading" class="sd-surface-title">Subject Attendance</h2>
                            <p class="sd-surface-sub">Standing across {{ count($subjectStats ?? []) }} {{ \Illuminate\Support\Str::plural('subject', count($subjectStats ?? [])) }}</p>
                        </div>
                    </div>
                    @if(!empty($subjectStats) && count($subjectStats) > 0)
                        <button type="button" class="sd-pill-btn" onclick="openSubjectBreakdownModal()">
                            <span>View all</span>
                        </button>
                    @else
                        <a href="{{ route('student.classes') }}" class="sd-pill-btn">
                            <span>My Classes</span>
                        </a>
                    @endif
                </header>

                <div class="sd-surface-body">
                    @if(!empty($subjectStats) && count($subjectStats) > 0)
                        <div class="sd-subject-stack">
                            @foreach(collect($subjectStats)->take(5) as $sub)
                                @php
                                    $hasSubjectRecords = $sub->total > 0;
                                    $rateClass = !$hasSubjectRecords ? '' : ($sub->rate >= 85 ? 'is-good' : ($sub->rate >= 75 ? 'is-warn' : 'is-risk'));
                                    $barColor = !$hasSubjectRecords ? 'rgba(236,224,228,0.16)' : ($sub->rate >= 85 ? '#34d399' : ($sub->rate >= 75 ? '#fbbf24' : '#ff6b7a'));
                                    $barWidth = $hasSubjectRecords ? min(100, max(0, $sub->rate)) : 0;
                                @endphp
                                <button type="button"
                                        class="sd-subject-card"
                                        onclick="openSubjectBreakdownModal()"
                                        aria-label="View full breakdown including {{ $sub->code }} {{ $sub->name }}">
                                    <div class="sd-subject-top">
                                        <div class="sd-subject-id">
                                            <span class="sd-subject-code sd-mono">{{ $sub->code }}</span>
                                            <span class="sd-subject-name" title="{{ $sub->name }}">{{ $sub->name }}</span>
                                        </div>
                                        <span class="sd-subject-rate sd-tabular {{ $rateClass }}">{{ $hasSubjectRecords ? $sub->rate.'%' : 'No records' }}</span>
                                    </div>
                                    <div class="sd-meter-track" aria-hidden="true">
                                        <div class="sd-meter-fill" style="width: {{ $barWidth }}%; background: {{ $barColor }};"></div>
                                    </div>
                                    <div class="sd-subject-counts sd-tabular">
                                        <span><strong class="sd-c-present">{{ $sub->present }}</strong> present</span>
                                        <span><strong class="sd-c-late">{{ $sub->late }}</strong> late</span>
                                        <span><strong class="sd-c-absent">{{ $sub->absent }}</strong> absent</span>
                                        @if($hasSubjectRecords && $sub->rate < 75)
                                            <span class="sd-risk-badge">Below 75%</span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="sd-empty-box is-compact">
                            <span class="sd-empty-icon" aria-hidden="true"><i class="bi bi-journal-check"></i></span>
                            <p class="sd-empty-title">No session records yet</p>
                            <p class="sd-empty-sub">Subject metrics appear once sessions are recorded.</p>
                        </div>
                    @endif
                </div>
            </section>

            @if(isset($announcements) && $announcements->isNotEmpty())
            <section class="sd-surface-card" id="studentAnnouncementsSection" aria-labelledby="studentAnnouncementsHeading">
                <header class="sd-surface-head">
                    <div class="sd-surface-title-group">
                        <span class="sd-surface-icon" aria-hidden="true"><i class="bi bi-megaphone-fill"></i></span>
                        <div>
                            <h2 id="studentAnnouncementsHeading" class="sd-surface-title">Campus &amp; Class Announcements</h2>
                            <p class="sd-surface-sub">Latest notices from administration &amp; instructors</p>
                        </div>
                    </div>
                </header>
                <div class="sd-surface-body">
                    <div class="sd-ann-stack">
                        @foreach($announcements as $announcement)
                            @php
                                $annContent = trim((string) $announcement->content);
                                $annNeedsClamp = \Illuminate\Support\Str::length($annContent) > 170 || substr_count($annContent, "\n") >= 3;
                                $annUrgent = \Illuminate\Support\Str::contains(strtolower((string) $announcement->title . ' ' . ($announcement->target_audience ?? '')), ['urgent', 'important', 'alert']);
                                $annCopyId = 'sdAnnouncementCopy' . $loop->index;
                            @endphp
                            <article class="sd-ann-card {{ $annUrgent ? 'is-urgent' : '' }}">
                                <div class="sd-ann-head">
                                    <h3 class="sd-ann-title">{{ $announcement->title }}</h3>
                                    <span class="sd-code-tag sd-mono">{{ $announcement->target_audience ?? 'All' }}</span>
                                </div>
                                <p id="{{ $annCopyId }}" class="sd-ann-copy {{ $annNeedsClamp ? 'is-clamped' : '' }}">{{ $annContent }}</p>
                                @if($annNeedsClamp)
                                    <button type="button" class="sd-ann-toggle" data-announcement-toggle="{{ $annCopyId }}" aria-expanded="false">
                                        Read more
                                    </button>
                                @endif
                                <div class="sd-ann-meta">
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

{{-- ── Subject Attendance Breakdown Modal ─────────────────────────────── --}}
<div class="modal fade" id="subjectBreakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="scal-sheet-handle"></div>
            <div class="d-flex justify-content-between align-items-center px-4 pt-4 pb-3 border-bottom" style="border-color:rgba(236,224,228,0.1)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:40px; height:40px; border-radius:12px; background:rgba(110,27,44,0.48); border:1px solid rgba(236,224,228,0.24); display:flex; align-items:center; justify-content:center; color:#ffffff; font-size:1.15rem;">
                        <i class="bi bi-pie-chart-fill"></i>
                    </div>
                    <div>
                        <h3 style="font-weight:800; font-size:1.15rem; color:#fcf9fa; margin:0;">Subject Breakdown</h3>
                        <div style="font-size:0.78rem; color:#b0979f; margin-top:2px;">Attendance rate &amp; records per enrolled subject</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge" style="background:rgba(110,27,44,0.42); color:#fcf9fa; border:1px solid rgba(236,224,228,0.24); font-size:0.76rem; font-weight:700; padding:6px 12px; border-radius:10px;">
                        {{ count($subjectStats ?? []) }} {{ \Illuminate\Support\Str::plural('Subject', count($subjectStats ?? [])) }}
                    </span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="px-4 py-4" style="max-height:72vh; overflow-y:auto;">
                @if(empty($subjectStats) || count($subjectStats) === 0)
                    <div style="text-align:center; padding:42px 20px; background:rgba(255,255,255,0.025); border-radius:18px; border:1px dashed rgba(236,224,228,0.14);">
                        <div style="width:52px; height:52px; border-radius:14px; background:rgba(110,27,44,0.44); border:1px solid rgba(236,224,228,0.24); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; color:#ffffff; font-size:1.4rem;">
                            <i class="bi bi-journal-x"></i>
                        </div>
                        <p style="color:#dfd1d5; font-size:0.9rem; font-weight:600; margin:0;">No enrolled subject records found</p>
                    </div>
                @else
                    <div class="row g-3">
                        @foreach($subjectStats as $sub)
                            @php
                                $hasRecords = $sub->total > 0;
                                $brkRateColor = '#b0979f';
                                $brkRateBg    = 'rgba(236,224,228,0.1)';
                                $brkRateBorder= 'rgba(236,224,228,0.22)';
                                if ($hasRecords) {
                                    if ($sub->rate < 75) {
                                        $brkRateColor  = '#ff6b7a';
                                        $brkRateBg     = 'rgba(255,107,122,0.14)';
                                        $brkRateBorder = 'rgba(255,107,122,0.34)';
                                    } elseif ($sub->rate < 85) {
                                        $brkRateColor  = '#fbbf24';
                                        $brkRateBg     = 'rgba(245,158,11,0.14)';
                                        $brkRateBorder = 'rgba(245,158,11,0.32)';
                                    } else {
                                        $brkRateColor  = '#34d399';
                                        $brkRateBg     = 'rgba(16,185,129,0.14)';
                                        $brkRateBorder = 'rgba(16,185,129,0.32)';
                                    }
                                }
                            @endphp
                            <div class="col-12 col-md-6">
                                <div class="subject-minimal-card" style="background:rgba(15,4,7,0.5); border:1px solid rgba(236,224,228,0.1); border-radius:16px; padding:16px 18px; height:100%; display:flex; flex-direction:column; justify-content:space-between; transition:all 0.22s ease;">
                                    <div>
                                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px;">
                                            <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                                <span class="sd-mono" style="background:rgba(110,27,44,0.45); color:#ffffff; border:1px solid rgba(236,224,228,0.26); font-size:0.74rem; font-weight:800; padding:3px 8px; border-radius:7px; flex-shrink:0;">{{ $sub->code }}</span>
                                                <span style="font-size:0.9rem; font-weight:700; color:#fcf9fa; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $sub->name }}">{{ $sub->name }}</span>
                                            </div>
                                            <span class="badge sd-tabular" style="background:{{ $brkRateBg }}; color:{{ $brkRateColor }}; border:1px solid {{ $brkRateBorder }}; font-size:0.8rem; font-weight:800; padding:4px 9px; border-radius:8px; flex-shrink:0;">{{ $hasRecords ? $sub->rate.'%' : 'No records' }}</span>
                                        </div>
                                        <div style="height:5px; background:rgba(236,224,228,0.09); border-radius:99px; overflow:hidden; margin-bottom:13px;">
                                            <div style="width:{{ $hasRecords ? min(100, max(0, $sub->rate)) : 0 }}%; height:100%; background:{{ $brkRateColor }}; border-radius:99px;"></div>
                                        </div>
                                    </div>
                                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; padding-top:10px; border-top:1px solid rgba(236,224,228,0.07);">
                                        <div class="sd-tabular" style="display:flex; align-items:center; gap:10px;">
                                            <span style="font-size:0.75rem; color:#b0979f;"><strong style="color:#34d399;">{{ $sub->present }}</strong> Present</span>
                                            <span style="font-size:0.75rem; color:#b0979f;"><strong style="color:#fbbf24;">{{ $sub->late }}</strong> Late</span>
                                            <span style="font-size:0.75rem; color:#b0979f;"><strong style="color:#ff6b7a;">{{ $sub->absent }}</strong> Absent</span>
                                            @if($sub->excused > 0)
                                                <span style="font-size:0.75rem; color:#b0979f;"><strong style="color:#7dd3fc;">{{ $sub->excused }}</strong> Excused</span>
                                            @endif
                                        </div>
                                        <span class="sd-tabular" style="font-size:0.72rem; color:#9e818a; font-weight:600;">{{ $sub->total }} {{ \Illuminate\Support\Str::plural('Session', $sub->total) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center" style="border-color:rgba(236,224,228,0.1)!important; background:rgba(255,255,255,0.015);">
                <a href="{{ route('student.classes') }}" class="btn btn-sm" style="background:rgba(110,27,44,0.45); border:1px solid rgba(236,224,228,0.28); color:#ffffff; font-weight:700; font-size:0.82rem; border-radius:10px; padding:7px 16px;">
                    <i class="bi bi-book-fill me-1"></i> My Classes
                </a>
                <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="background:rgba(255,255,255,0.08); border:1px solid rgba(236,224,228,0.14); color:#fcf9fa; font-weight:700; font-size:0.82rem; border-radius:10px; padding:7px 18px;">
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
                        <span id="daySummaryStatusDot" style="width:7px; height:7px; border-radius:50%; background:#e48fa0; display:inline-block;"></span>
                        Attendance Details
                    </span>
                </div>
            </div>
            <div class="px-4 pb-4 pt-1">
                <div id="daySummaryContent" class="d-flex flex-column gap-3"></div>
                <div style="font-size:0.75rem; color:#b0979f; text-align:center; margin-top:20px; display:flex; align-items:center; justify-content:center; gap:6px;">
                    <i class="bi bi-shield-check" style="color:#e48fa0;"></i>
                    <span>Real-time records, events, and schedules for this date.</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Attendance Records Modal ───────────────────────────────────────── --}}
<div class="modal fade" id="attendanceRecordsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="scal-sheet-handle"></div>
            <div class="d-flex justify-content-between align-items-center px-4 pt-4 pb-3 border-bottom" style="border-color:rgba(236,224,228,0.08)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:40px; height:40px; border-radius:12px; background:rgba(110,27,44,0.48); border:1px solid rgba(236,224,228,0.24); display:flex; align-items:center; justify-content:center; color:#ffffff; font-size:1.15rem;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h3 style="font-weight:800; font-size:1.2rem; color:#fcf9fa; margin:0;">Attendance Records</h3>
                        <div style="font-size:0.8rem; color:#b0979f; margin-top:2px;">Complete attendance history per subject and date</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span id="recordsModalCount" class="badge sd-tabular" style="background:rgba(110,27,44,0.42); color:#fcf9fa; border:1px solid rgba(236,224,228,0.24); font-size:0.78rem; padding:6px 12px; border-radius:10px;">
                        {{ $totalRecords }} Records
                    </span>
                    <span id="recordsModalLiveStatus" class="visually-hidden" role="status" aria-live="polite" aria-atomic="true"></span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="px-4 py-3 border-bottom" style="background:rgba(255,255,255,0.015); border-color:rgba(236,224,228,0.07)!important;">
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <div style="position:relative;">
                            <i class="bi bi-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#b0979f; font-size:0.85rem;"></i>
                            <input type="text" id="recordsSearchInput" oninput="filterAttendanceRecords()" class="form-control form-control-sm" placeholder="Search subject, code, or instructor..." style="background:#14050a; border:1px solid rgba(236,224,228,0.14); color:#fcf9fa; border-radius:10px; padding-left:34px; font-size:0.82rem; height:38px;">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="recordsStatusFilter" onchange="filterAttendanceRecords()" class="form-select form-select-sm" style="background:#14050a; border:1px solid rgba(236,224,228,0.14); color:#fcf9fa; border-radius:10px; font-size:0.82rem; height:38px;">
                            <option value="all">All Statuses</option>
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                            <option value="excused">Excused</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="recordsMonthFilter" onchange="filterAttendanceRecords()" class="form-select form-select-sm" style="background:#14050a; border:1px solid rgba(236,224,228,0.14); color:#fcf9fa; border-radius:10px; font-size:0.82rem; height:38px;">
                            <option value="all">All Months</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="px-4 py-3" style="min-height:280px; max-height:60vh; overflow-y:auto;">
                <div id="recordsModalList" class="d-flex flex-column gap-2"></div>
            </div>

            <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center" style="border-color:rgba(236,224,228,0.08)!important; background:rgba(255,255,255,0.015);">
                <a href="{{ route('student.calendar') }}#attendance-records" class="btn btn-sm" style="border-radius:10px; font-weight:700; font-size:0.82rem; padding:7px 16px; background:rgba(110,27,44,0.45); border:1px solid rgba(236,224,228,0.28); color:#ffffff;">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Open Full History Page
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="border-radius:10px; font-weight:700; font-size:0.82rem; padding:7px 18px; background:rgba(255,255,255,0.08); border-color:rgba(236,224,228,0.14);">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
var attCalendarData = {};
var eventsCalendarData = {};
var loadedCalendarMonths = {};
var dashboardCalendarRequests = {};
var allAttendanceRecords = [];
let attendanceHistoryLoaded = false;
let attendanceHistoryRequest = null;
let dayModalInstance = null;
let recordsModalInstance = null;
let currentSelectedDateKey = '{{ $calYear }}-{{ str_pad($calMonth, 2, '0', STR_PAD_LEFT) }}-{{ str_pad(($isCurrentMonth && $today ? $today : 14), 2, '0', STR_PAD_LEFT) }}';

function loadDashboardCalendarData(year, month) {
    const targetYear = Number(year || {{ $calYear }});
    const targetMonth = Number(month || {{ $calMonth }});
    const monthKey = targetYear + '-' + String(targetMonth).padStart(2, '0');

    if (loadedCalendarMonths[monthKey]) {
        return Promise.resolve();
    }
    if (dashboardCalendarRequests[monthKey]) {
        return dashboardCalendarRequests[monthKey];
    }

    const url = '{{ route('student.dashboard.calendar.data') }}'
        + '?year=' + encodeURIComponent(targetYear)
        + '&month=' + encodeURIComponent(targetMonth);

    dashboardCalendarRequests[monthKey] = fetch(url, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    })
        .then(function(response) {
            if (!response.ok) throw new Error('Dashboard calendar request failed');
            return response.json();
        })
        .then(function(data) {
            if (data && data.attendance && typeof data.attendance === 'object') {
                Object.assign(attCalendarData, data.attendance);
            }
            if (data && data.events && typeof data.events === 'object') {
                Object.assign(eventsCalendarData, data.events);
            }
            loadedCalendarMonths[monthKey] = true;
        })
        .finally(function() {
            delete dashboardCalendarRequests[monthKey];
        });

    return dashboardCalendarRequests[monthKey];
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
    if (tile) tile.classList.add('selected');
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
    selectCalendarDay(`${y}-${m}-${d}`, dt.getDate(), true);
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

    const [yStr, mStr] = String(dateKey).split('-');
    const reqYear = Number(yStr || {{ $calYear }});
    const reqMonth = Number(mStr || {{ $calMonth }});
    const monthKey = reqYear + '-' + String(reqMonth).padStart(2, '0');

    if (!loadedCalendarMonths[monthKey]) {
        if (titleEl) titleEl.textContent = 'Loading calendar details';
        if (subtitleEl) subtitleEl.innerHTML = '<span class="day-summary-chip chip-empty">Preparing attendance and event details</span>';
        if (contentEl) {
            contentEl.innerHTML = `
                <div class="ds-skeleton-stack" aria-busy="true" aria-label="Loading day summary">
                    <div class="ds-skeleton-card">
                        <div class="ds-skeleton-line is-title"></div>
                        <div class="ds-skeleton-line is-sub"></div>
                        <div class="ds-skeleton-line is-full"></div>
                    </div>
                    <div class="ds-skeleton-card">
                        <div class="ds-skeleton-line is-title"></div>
                        <div class="ds-skeleton-line is-sub"></div>
                    </div>
                </div>`;
        }
        openDaySummaryInspector();
        loadDashboardCalendarData(reqYear, reqMonth).then(function() {
            if (loadedCalendarMonths[monthKey] && currentSelectedDateKey === dateKey) {
                showAttDetail(dateKey, day);
            }
        }).catch(function() {
            if (currentSelectedDateKey !== dateKey) return;
            if (subtitleEl) subtitleEl.textContent = 'Calendar unavailable';
            if (contentEl) contentEl.innerHTML = '<div class="day-empty-card" role="alert"><p>Could not load calendar details. Close this window and open the day again to retry.</p><a href="{{ route('student.calendar') }}" style="color:#e48fa0;">Open attendance and school calendar</a></div>';
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
            subtitleEl.innerHTML = `<span class="day-summary-chip chip-empty"><i class="bi bi-calendar-x me-1"></i> No records or events</span>`;
        }
        if (contentEl) {
            contentEl.innerHTML = `
                <div class="day-empty-card">
                    <div class="day-empty-icon"><i class="bi bi-calendar2-x"></i></div>
                    <div style="font-weight:800; font-size:1.02rem; color:#fcf9fa; margin-bottom:6px;">No records or events for this date.</div>
                    <div style="font-size:0.82rem; color:#b0979f; max-width:300px; margin:0 auto; line-height:1.5;">There are no attendance records, classes, exams, events, or holidays recorded for this date.</div>
                </div>`;
        }
    } else {
        let badgesHtml = '';
        if (records.length > 0) {
            const presentCount = records.filter(r => r.status_lower === 'present').length;
            const lateCount = records.filter(r => r.status_lower === 'late').length;
            const absentCount = records.filter(r => r.status_lower === 'absent').length;

            badgesHtml += `<span class="day-summary-chip chip-count"><i class="bi bi-journal-bookmark-fill me-1" style="color:#e48fa0;"></i> ${records.length} ${records.length === 1 ? 'Subject' : 'Subjects'}</span>`;
            if (presentCount > 0) badgesHtml += `<span class="day-summary-chip chip-present"><i class="bi bi-check-circle-fill me-1"></i> ${presentCount} Present</span>`;
            if (lateCount > 0) badgesHtml += `<span class="day-summary-chip chip-late"><i class="bi bi-clock-fill me-1"></i> ${lateCount} Late</span>`;
            if (absentCount > 0) badgesHtml += `<span class="day-summary-chip chip-absent"><i class="bi bi-x-circle-fill me-1"></i> ${absentCount} Absent</span>`;
        }

        if (dayEvents.length > 0) {
            const holidays = dayEvents.filter(e => e.type === 'holiday');
            const exams = dayEvents.filter(e => e.type === 'exam');
            const events = dayEvents.filter(e => e.type !== 'holiday' && e.type !== 'exam');

            if (holidays.length > 0) badgesHtml += `<span class="day-summary-chip" style="background:rgba(74,222,128,0.15); color:#4ade80; border:1px solid rgba(74,222,128,0.3);"><i class="bi bi-flag-fill me-1"></i> ${holidays.length === 1 ? 'Holiday' : holidays.length + ' Holidays'}</span>`;
            if (exams.length > 0) badgesHtml += `<span class="day-summary-chip" style="background:rgba(236,72,153,0.15); color:#ec4899; border:1px solid rgba(236,72,153,0.3);"><i class="bi bi-award-fill me-1"></i> ${exams.length === 1 ? 'Exam' : exams.length + ' Exams'}</span>`;
            if (events.length > 0) badgesHtml += `<span class="day-summary-chip" style="background:rgba(139,92,246,0.15); color:#c4b5fd; border:1px solid rgba(139,92,246,0.3);"><i class="bi bi-calendar-event-fill me-1"></i> ${events.length === 1 ? 'Event' : events.length + ' Events'}</span>`;
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
                                    <span class="att-instructor-pill"><i class="bi bi-person-fill me-1"></i>${escapeHtml(r.instructor)}</span>
                                </div>
                            </div>
                            <span class="att-status-badge ${st}">
                                <i class="bi ${iconClass}"></i> ${escapeHtml(r.status)}
                            </span>
                        </div>
                        <div class="att-info-grid">
                            <div>
                                <div class="att-grid-col-label"><i class="bi bi-calendar3"></i> Schedule</div>
                                <div class="att-grid-col-val">${escapeHtml(r.schedule)}</div>
                            </div>
                            <div>
                                <div class="att-grid-col-label"><i class="bi bi-fingerprint"></i> Actual Clock-in</div>
                                <div class="att-grid-col-val" style="color:${isNoClockIn ? '#f87171' : clockInColor};">
                                    ${escapeHtml(clockInText)}
                                </div>
                            </div>
                            ${r.time_out ? `
                            <div>
                                <div class="att-grid-col-label"><i class="bi bi-box-arrow-right"></i> Clock-out</div>
                                <div class="att-grid-col-val" style="color:#dfd1d5;">${escapeHtml(r.time_out)}</div>
                            </div>` : ''}
                            ${r.remarks ? `
                            <div style="grid-column: 1 / -1;">
                                <div class="att-grid-col-label"><i class="bi bi-chat-left-text"></i> Remarks</div>
                                <div style="font-size:0.82rem; font-weight:500; color:#e48fa0;">${escapeHtml(r.remarks)}</div>
                            </div>` : ''}
                        </div>
                        ${st === 'absent' ? `
                        <a href="{{ route('excuses.create_general') }}" class="day-card-excuse-btn">
                            <span><i class="bi bi-file-earmark-medical me-1"></i> File Excuse Request for this Absence</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>` : ''}
                    </div>`;
            });

            fullHtml += `</div>`;
        }

        if (dayEvents.length > 0) {
            fullHtml += `
                <div class="day-section-header mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="day-section-icon"><i class="bi bi-calendar-event-fill"></i></span>
                        <span class="day-section-label">EVENTS / IMPORTANT DATES</span>
                    </div>
                    <span class="day-section-badge">${dayEvents.length} ${dayEvents.length === 1 ? 'Entry' : 'Entries'}</span>
                </div>
                <div class="d-flex flex-column gap-3">
            `;

            dayEvents.forEach(evt => {
                const isExam = evt.type === 'exam';
                const isHoliday = evt.type === 'holiday';
                const badgeText = isExam ? 'Exam' : (isHoliday ? 'Holiday' : 'Event');
                const borderStyle = isExam ? 'border-left: 4px solid #ec4899;' : (isHoliday ? 'border-left: 4px solid #4ade80;' : 'border-left: 4px solid #8b5cf6;');
                const badgeStyle = isExam
                    ? 'background: rgba(236, 72, 153, 0.15); color: #f472b6; border: 1px solid rgba(236, 72, 153, 0.35);'
                    : (isHoliday
                        ? 'background: rgba(74, 222, 128, 0.15); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.35);'
                        : 'background: rgba(139, 92, 246, 0.15); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.35);');
                const iconClass = isExam ? 'bi-award-fill' : (isHoliday ? 'bi-flag-fill' : 'bi-calendar-event-fill');

                let metaRows = '';
                if (evt.subject) metaRows += `<div style="font-size:0.9rem; font-weight:700; color:#ffffff; margin-top:6px;"><i class="bi bi-book-half me-1" style="color:#e48fa0;"></i>${escapeHtml(evt.subject)}</div>`;
                if (evt.time) metaRows += `<div style="font-size:0.84rem; color:#fcf9fa; margin-top:4px;"><i class="bi bi-clock-fill me-1" style="color:#e48fa0;"></i>${escapeHtml(evt.time)}</div>`;
                if (evt.location) metaRows += `<div style="font-size:0.84rem; color:#dfd1d5; margin-top:4px;"><i class="bi bi-geo-alt-fill me-1" style="color:#e48fa0;"></i>${escapeHtml(evt.location)}</div>`;
                if (evt.description && evt.description.trim() !== '') metaRows += `<div style="font-size:0.81rem; color:#b0979f; margin-top:6px; line-height:1.45;">${escapeHtml(evt.description)}</div>`;

                fullHtml += `
                    <div style="background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.08); ${borderStyle} border-radius: 14px; padding: 15px 16px;">
                        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 10px; border-radius: 99px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; ${badgeStyle} margin-bottom: 6px;">
                            <i class="bi ${iconClass}"></i><span>${badgeText}</span>
                        </div>
                        <div style="font-weight: 800; color: #f3ede4; font-size: 1.04rem; line-height: 1.3;">${escapeHtml(evt.title)}</div>
                        ${metaRows}
                    </div>`;
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
    if (!modalEl) return;
    if (modalEl.parentNode !== document.body) document.body.appendChild(modalEl);
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

async function loadAttendanceRecordsForModal() {
    if (attendanceHistoryLoaded) {
        filterAttendanceRecords();
        return;
    }
    if (attendanceHistoryRequest) return attendanceHistoryRequest;

    const listEl = document.getElementById('recordsModalList');
    if (listEl) {
        listEl.innerHTML = `
            <div class="ds-skeleton-stack" aria-busy="true" aria-label="Loading attendance records">
                <div class="ds-skeleton-card"><div class="ds-skeleton-line is-title"></div><div class="ds-skeleton-line is-sub"></div><div class="ds-skeleton-line is-full"></div></div>
                <div class="ds-skeleton-card"><div class="ds-skeleton-line is-title"></div><div class="ds-skeleton-line is-sub"></div><div class="ds-skeleton-line is-full"></div></div>
                <div class="ds-skeleton-card"><div class="ds-skeleton-line is-title"></div><div class="ds-skeleton-line is-sub"></div></div>
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
                    <div style="text-align:center;padding:40px 20px;color:#b0979f;">
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
        if (modalEl.parentNode !== document.body) document.body.appendChild(modalEl);
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
    if (modalEl.parentNode !== document.body) document.body.appendChild(modalEl);
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
        if (monthVal !== 'all' && r.date_month !== monthVal) {
            return false;
        }
        return true;
    });

    renderAttendanceRecordsList(filtered);
}

function renderAttendanceRecordsList(items) {
    const listEl = document.getElementById('recordsModalList');
    const countEl = document.getElementById('recordsModalCount');
    const liveStatusEl = document.getElementById('recordsModalLiveStatus');
    if (!listEl) return;

    const countLabel = `${items.length} ${items.length === 1 ? 'Record' : 'Records'}`;
    if (countEl) {
        countEl.textContent = countLabel;
    }
    if (liveStatusEl) {
        liveStatusEl.textContent = `Showing ${items.length} matching attendance ${items.length === 1 ? 'record' : 'records'}.`;
    }

    if (items.length === 0) {
        listEl.innerHTML = `
            <div style="text-align:center; padding:40px 20px; color:#b0979f; background:rgba(255,255,255,0.025); border-radius:18px; border:1px dashed rgba(236,224,228,0.14);">
                <i class="bi bi-search" style="font-size:2rem; color:#e48fa0; display:block; margin-bottom:10px; opacity:0.75;"></i>
                <div style="font-weight:700; font-size:0.95rem; color:#fcf9fa;">No records match your filters</div>
                <div style="font-size:0.8rem; margin-top:4px;">Try clearing your search query or changing filters.</div>
            </div>`;
        return;
    }

    let html = '';
    items.forEach(r => {
        const st = (r.status_lower || 'absent');
        const iconClass = st === 'present' ? 'bi-check-circle-fill' : (st === 'late' ? 'bi-clock-fill' : 'bi-x-circle-fill');
        const clockInColor = st === 'present' ? '#4ade80' : (st === 'late' ? '#fbbf24' : '#ff6b7a');
        const clockInText = r.clock_in || (st === 'absent' ? 'No clock-in' : '—');
        const isNoClockIn = clockInText.toLowerCase().includes('no clock-in');

        html += `
            <div class="record-modal-item" style="background:rgba(15,4,7,0.52); border:1px solid rgba(236,224,228,0.09); border-radius:16px; padding:15px 17px; margin-bottom:8px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:10px; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:0.78rem; font-weight:700; color:#e48fa0; display:flex; align-items:center; gap:6px;">
                            <i class="bi bi-calendar-event"></i> ${escapeHtml(r.date_formatted)}
                            <span style="color:rgba(236,224,228,0.45); font-weight:400;">(${escapeHtml(r.day_name)})</span>
                        </div>
                        <h4 style="margin:4px 0 0; font-size:1rem; font-weight:800; color:#fcf9fa; line-height:1.3;">${escapeHtml(r.subject)}</h4>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span class="att-status-badge ${st}">
                            <i class="bi ${iconClass}"></i> ${escapeHtml(r.status)}
                        </span>
                        ${r.excused ? `<span style="padding:5px 10px; border-radius:8px; font-size:0.72rem; font-weight:700; background:rgba(110,27,44,0.45); color:#ffffff; border:1px solid rgba(236,224,228,0.3);">Excused</span>` : ''}
                    </div>
                </div>
                <div style="font-size:0.8rem; color:#e48fa0; margin-bottom:11px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span class="att-code-pill">${escapeHtml(r.code)}</span>
                    <span style="color:rgba(236,224,228,0.28);">•</span>
                    <span style="color:#dfd1d5;"><i class="bi bi-person me-1"></i>${escapeHtml(r.instructor)}</span>
                </div>
                <div class="att-info-grid">
                    <div>
                        <div class="att-grid-col-label"><i class="bi bi-clock"></i> Scheduled</div>
                        <div class="att-grid-col-val">${escapeHtml(r.schedule)}</div>
                    </div>
                    <div>
                        <div class="att-grid-col-label"><i class="bi bi-box-arrow-in-right"></i> Actual Clock-in</div>
                        <div class="att-grid-col-val" style="color:${isNoClockIn ? '#ff6b7a' : clockInColor};">${escapeHtml(clockInText)}</div>
                    </div>
                    <div>
                        <div class="att-grid-col-label"><i class="bi bi-box-arrow-right"></i> Clock-out</div>
                        <div class="att-grid-col-val" style="color:#dfd1d5;">${escapeHtml(r.clock_out)}</div>
                    </div>
                    ${r.remarks && r.remarks !== '—' && r.remarks !== 'None' ? `
                    <div style="grid-column:1 / -1;">
                        <div class="att-grid-col-label"><i class="bi bi-chat-left-text"></i> Remarks</div>
                        <div style="font-size:0.8rem; font-weight:500; color:#e48fa0;">${escapeHtml(r.remarks)}</div>
                    </div>` : ''}
                </div>
            </div>`;
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
        if (dModal.parentNode !== document.body) document.body.appendChild(dModal);
    }
    const rModal = document.getElementById('attendanceRecordsModal');
    if (rModal && rModal.parentNode !== document.body) document.body.appendChild(rModal);
    const sModal = document.getElementById('subjectBreakdownModal');
    if (sModal && sModal.parentNode !== document.body) document.body.appendChild(sModal);

    // Keyboard arrow stepper when Day Summary Inspector is open (WCAG 2.2 keyboard parity)
    document.addEventListener('keydown', function(event) {
        const modal = document.getElementById('daySummaryModal');
        if (!modal || !modal.classList.contains('show')) return;
        const tag = (document.activeElement && document.activeElement.tagName) || '';
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            navigateDayModal(-1);
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            navigateDayModal(1);
        }
    });
});
</script>

<script nonce="{{ csp_nonce() }}">
(function() {
    function formatRemainingMinutes(diffMs) {
        const totalMins = Math.max(1, Math.ceil(diffMs / 60000));
        if (totalMins < 60) return totalMins + 'm';
        const hours = Math.floor(totalMins / 60);
        const mins = totalMins % 60;
        return mins > 0 ? (hours + 'h ' + mins + 'm') : (hours + 'h');
    }

    function tick() {
        if (document.hidden) return;
        const now = new Date();
        let h = now.getHours();
        const m = now.getMinutes();
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
                    timerEl.textContent = formatRemainingMinutes(endMs - nowMs) + ' left';
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
                if (emptyFilterEl) emptyFilterEl.hidden = visibleCount > 0;
                const statusEl = document.getElementById('sdScheduleFilterStatus');
                if (statusEl) {
                    const label = mode === 'all' ? 'total' : mode;
                    statusEl.textContent = `Showing ${visibleCount} ${label} ${visibleCount === 1 ? 'class' : 'classes'} today.`;
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

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) tick();
    });
    setInterval(tick, 1000);
})();
</script>
@endsection
