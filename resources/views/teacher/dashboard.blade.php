@extends('layouts.app')
@section('page-title', 'Dashboard')

@section('content')
@php
    $teacherUser = Auth::user();
    $todayCollection = collect($todayClasses ?? []);
    $subjectsCollection = collect($teacherSubjects ?? []);
    $recentCollection = collect($recentAttendance ?? []);
    $atRiskCollection = collect($atRiskStudents ?? []);

    $greetHour = now()->hour;
    $greeting = $greetHour < 12 ? 'Good Morning' : ($greetHour < 17 ? 'Good Afternoon' : 'Good Evening');
    $greetIcon = $greetHour < 12 ? 'bi-brightness-alt-high-fill' : ($greetHour < 17 ? 'bi-sun-fill' : 'bi-moon-stars-fill');

    $presentCount = (int) ($totalPresent ?? 0);
    $lateCount = (int) ($totalLate ?? 0);
    $absentCount = (int) ($totalAbsent ?? 0);
    // In AnalyticsService, totalPresent already includes Present + Late; keep on-time split clean
    $onTimeCount = max(0, $presentCount - $lateCount);
    $totalLogged = $presentCount + $absentCount;
    $attendanceRate = $totalLogged > 0 ? round(($presentCount / $totalLogged) * 100) : null;

    $rateTone = $attendanceRate === null ? 'empty' : ($attendanceRate >= 85 ? 'good' : ($attendanceRate >= 75 ? 'warning' : 'danger'));
    $rateTier = $attendanceRate === null ? 'Awaiting Sessions' : ($attendanceRate >= 85 ? 'Healthy Turnout' : ($attendanceRate >= 75 ? 'Moderate Turnout' : 'Needs Follow-Up'));

    $gaugeRadius = 38;
    $gaugeCircumference = round(2 * pi() * $gaugeRadius, 2);
    $gaugePct = $attendanceRate === null ? 0 : min(100, max(0, (float) $attendanceRate));
    $gaugeOffset = round($gaugeCircumference * (1 - ($gaugePct / 100)), 2);

    $onTimePct = $totalLogged > 0 ? round(($onTimeCount / $totalLogged) * 100, 1) : 0;
    $latePct = $totalLogged > 0 ? round(($lateCount / $totalLogged) * 100, 1) : 0;
    $absentPct = $totalLogged > 0 ? max(0, round(100 - $onTimePct - $latePct, 1)) : 0;

    $labelsArr = array_values((array) ($weeklyLabels ?? []));
    $wPresentArr = array_values((array) ($weeklyPresent ?? []));
    $wLateArr = array_values((array) ($weeklyLate ?? []));
    $wAbsentArr = array_values((array) ($weeklyAbsent ?? []));
    $dayTotals = [];
    for ($d = 0; $d < count($labelsArr); $d++) {
        $dayTotals[$d] = (int) ($wPresentArr[$d] ?? 0) + (int) ($wLateArr[$d] ?? 0) + (int) ($wAbsentArr[$d] ?? 0);
    }
    $maxDayTotal = !empty($dayTotals) ? max(1, max($dayTotals)) : 1;

    $metaParts = [];
    if (!empty($teacherUser?->employee_id)) {
        $metaParts[] = ['label' => 'ID ' . $teacherUser->employee_id, 'mono' => true];
    } else {
        $metaParts[] = ['label' => 'Faculty Workspace', 'mono' => false];
    }
    $metaParts[] = ['label' => $subjectsCollection->count() . ' ' . \Illuminate\Support\Str::plural('Class', $subjectsCollection->count()), 'mono' => false];
    $metaParts[] = ['label' => ($totalStudents ?? 0) . ' ' . \Illuminate\Support\Str::plural('Student', (int) ($totalStudents ?? 0)), 'mono' => false];

    $recordedTodayCount = $todayCollection->where('has_attendance_today', true)->count();
@endphp

<div class="portal-cockpit">
    {{-- ── 1. Open Editorial Masthead ────────────────────────────────────── --}}
    <header class="portal-masthead" aria-label="Faculty overview">
        <div class="portal-identity-cluster">
            <div class="portal-avatar-ring">
                <img src="{{ $teacherUser->profile_photo_url_with_version }}" alt="{{ $teacherUser->name }}">
                <span class="portal-avatar-live-dot" title="Active Session" aria-hidden="true"></span>
            </div>
            <div class="portal-identity-copy">
                <div class="portal-kicker-line">
                    <span class="portal-kicker-greeting">
                        <i class="bi {{ $greetIcon }}" aria-hidden="true"></i>
                        <span>{{ $greeting }}</span>
                    </span>
                    <span class="portal-kicker-sep" aria-hidden="true">&middot;</span>
                    <span>{{ ($targetDate ?? now())->format('l, M j') }}</span>
                    <span class="portal-kicker-sep" aria-hidden="true">&middot;</span>
                    <span id="teacherClock" class="portal-kicker-clock portal-mono" title="Campus Local Time">{{ now()->format('g:i A') }}</span>
                </div>
                <h1 class="portal-title">{{ $teacherUser->name }}</h1>
                <div class="portal-meta-ribbon">
                    @foreach($metaParts as $metaPart)
                        @if(!$loop->first)<span class="portal-meta-sep" aria-hidden="true">&middot;</span>@endif
                        <span class="portal-meta-item {{ $metaPart['mono'] ? 'portal-mono is-id' : '' }}">{{ $metaPart['label'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="portal-masthead-actions">
            <a href="{{ route('teacher.classroom.index', ['task' => 'attendance']) }}" class="portal-cta-primary">
                <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                <span>Take Attendance</span>
            </a>
            <a href="{{ route('teacher.reports.pdf') }}" target="_blank" rel="noopener" class="portal-cta-secondary">
                <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>
                <span>Download Report</span>
            </a>
        </div>
    </header>

    {{-- ── 2. Contextual Alert Rail (Flashes, Live Echo & Pending Reviews) ── --}}
    <div class="portal-alert-rail">
        @if(session('error'))
            <div class="portal-banner is-error" role="alert">
                <div class="portal-banner-lead">
                    <span class="portal-banner-icon" aria-hidden="true"><i class="bi bi-exclamation-circle-fill"></i></span>
                    <div><div class="portal-banner-title">{{ session('error') }}</div></div>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="portal-banner is-success" role="status">
                <div class="portal-banner-lead">
                    <span class="portal-banner-icon" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></span>
                    <div><div class="portal-banner-title">{{ session('success') }}</div></div>
                </div>
            </div>
        @endif

        <div id="peerLiveNotice" class="portal-banner is-live portal-banner-dismissible" role="status" aria-live="polite" hidden>
            <span class="portal-banner-message" data-banner-message></span>
            <button type="button" class="portal-banner-close" data-banner-dismiss="peerLiveNotice" aria-label="Dismiss live notification" title="Close">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
        <div id="peerLiveConnection" class="portal-banner is-info portal-banner-dismissible" role="status" hidden>
            <span class="portal-banner-message" data-banner-message></span>
            <button type="button" class="portal-banner-close" data-banner-dismiss="peerLiveConnection" aria-label="Dismiss live updates notice" title="Close">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        @if(isset($pendingExcuses) && $pendingExcuses > 0)
            <div class="portal-banner is-warning" role="region" aria-label="Pending excuse reviews">
                <div class="portal-banner-lead">
                    <span class="portal-banner-icon" aria-hidden="true"><i class="bi bi-file-earmark-text-fill"></i></span>
                    <div>
                        <div class="portal-banner-title">{{ $pendingExcuses }} Pending {{ \Illuminate\Support\Str::plural('Excuse', $pendingExcuses) }} Awaiting Review</div>
                        <div class="portal-banner-sub">Students or guardians have submitted absence documentation for your classes.</div>
                    </div>
                </div>
                <a href="{{ route('teacher.excuse.reviews') }}" class="portal-banner-btn">
                    Review Excuses <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        @endif
    </div>

    {{-- ── 3. Signature Asymmetric Hero Canvas (Teaching Stage + Telemetry) ─ --}}
    @php
        $targetCarbon = $targetDate ?? now();
        $dayName = $targetCarbon->format('l');
        $isViewingToday = $targetCarbon->isToday();
        $nowTime = now();

        $todayItems = $todayCollection->values()->map(function ($cls) use ($dayName, $isViewingToday, $nowTime) {
            $daySched = $cls->schedules->firstWhere('day', $dayName) ?? $cls->schedules->first();
            $roomName = trim((string) ($daySched?->room ?? $cls->room ?? ''));
            $hasTimes = !empty($daySched?->start_time) && !empty($daySched?->end_time);
            $startAt = $hasTimes ? \Carbon\Carbon::parse($daySched->start_time) : null;
            $endAt = $hasTimes ? \Carbon\Carbon::parse($daySched->end_time) : null;

            return (object) [
                'subject' => $cls,
                'room' => ($roomName !== '' && strtoupper($roomName) !== 'TBA') ? $roomName : '',
                'hasTimes' => $hasTimes,
                'startLabel' => $startAt ? $startAt->format('g:i A') : 'Flexible',
                'endLabel' => $endAt ? $endAt->format('g:i A') : 'Scheduled',
                'isLive' => $hasTimes && $isViewingToday && $nowTime->between($startAt, $endAt),
            ];
        });

        $focusIdx = $todayItems->search(fn ($item) => $item->isLive);
        if ($focusIdx === false) {
            $focusIdx = $todayItems->search(fn ($item) => empty($item->subject->has_attendance_today));
        }
        $focusIdx = $focusIdx === false ? 0 : $focusIdx;
        $hasLiveSession = $todayItems->contains(fn ($item) => $item->isLive);
    @endphp
    <div class="portal-hero-grid">
        {{-- Left: Today's Teaching Stage --}}
        <section class="portal-stage {{ $hasLiveSession ? 'is-live' : ($todayCollection->isNotEmpty() ? 'is-next' : 'is-idle') }}" aria-labelledby="teacherTodayHeading">
            <div class="portal-stage-head">
                <h2 id="teacherTodayHeading" class="portal-stage-eyebrow" style="margin: 0;">
                    <span class="portal-pulse-dot {{ $hasLiveSession ? 'is-live' : ($todayCollection->isNotEmpty() ? '' : 'is-done') }}" aria-hidden="true"></span>
                    {{ $hasLiveSession ? 'Class in Progress' : "Today's Teaching Schedule" }}
                </h2>
                <div class="d-flex align-items-center gap-3">
                    @if($todayCollection->isNotEmpty())
                        <span class="td-stage-progress portal-tabular">
                            <strong>{{ $recordedTodayCount }}/{{ $todayCollection->count() }}</strong> recorded
                        </span>
                    @endif
                    <a href="{{ route('teacher.classroom.index') }}" class="portal-panel-link">All classes <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>

            @if($todayItems->isNotEmpty())
                <div class="td-today-list">
                    @foreach($todayItems as $idx => $item)
                        @php
                            $cls = $item->subject;
                            $isFocus = ($idx === $focusIdx) && empty($cls->has_attendance_today);
                            $rowClasses = trim(implode(' ', array_filter([
                                'td-today-card',
                                !empty($cls->has_attendance_today) ? 'is-recorded' : null,
                                $item->isLive ? 'is-live' : null,
                                $isFocus ? 'is-focus' : null,
                            ])));
                        @endphp
                        <div class="{{ $rowClasses }}">
                            <div class="td-today-time portal-mono">
                                <span class="td-time-start">{{ $item->startLabel }}</span>
                                <span class="td-time-end">{{ $item->endLabel }}</span>
                            </div>

                            <div class="td-today-info">
                                <div class="td-today-top">
                                    <span class="td-today-code portal-mono">{{ $cls->code }}</span>
                                    <span class="td-today-name">{{ $cls->name }}</span>
                                </div>
                                <div class="td-today-meta portal-tabular">
                                    @if($item->room !== '')
                                        <span><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $item->room }}</span>
                                    @endif
                                    @if(!empty($cls->section))
                                        <span><i class="bi bi-people" aria-hidden="true"></i> Sec {{ $cls->section }}</span>
                                    @endif
                                    @if($cls->has_attendance_today)
                                        <span class="td-today-status is-recorded">
                                            <span class="td-status-dot" aria-hidden="true"></span>
                                            {{ $cls->class_health }}% &middot; {{ $cls->attendance_count_today }} checked in
                                        </span>
                                    @elseif($item->isLive)
                                        <span class="td-today-status is-live">
                                            <span class="td-status-dot" aria-hidden="true"></span>
                                            In session &middot; awaiting QR
                                        </span>
                                    @else
                                        <span class="td-today-status is-pending">
                                            <span class="td-status-dot" aria-hidden="true"></span>
                                            Session not started
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="td-today-actions">
                                <a href="{{ route('teacher.qr', $cls->code) }}" class="td-btn-qr {{ ($isFocus || $item->isLive) ? 'is-primary' : 'is-secondary' }}">
                                    <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Launch QR
                                </a>
                                <a href="{{ route('teacher.attendance', ['subject' => $cls->code, 'date' => $targetCarbon->toDateString()]) }}" class="td-btn-records">
                                    Records
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="portal-inline-empty" style="padding: 18px 0;">
                    <div>
                        <strong style="color: var(--portal-white); display: block; margin-bottom: 4px;">No classes scheduled for {{ $dayName }}</strong>
                        <span>Open any of your assigned classes anytime to launch a manual session, review rosters, or manage materials.</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('teacher.classroom.index') }}" class="portal-cta-secondary">View My Classes <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        <a href="{{ route('teacher.subjects.create') }}" class="portal-cta-secondary">Add Subject</a>
                    </div>
                </div>
            @endif

            <div class="portal-stage-foot">
                <div class="portal-stage-links">
                    <a href="{{ route('teacher.attendance') }}" class="portal-stage-link">
                        <i class="bi bi-clipboard-check" aria-hidden="true"></i> Attendance Logs
                    </a>
                    <a href="{{ route('teacher.students') }}" class="portal-stage-link">
                        <i class="bi bi-people" aria-hidden="true"></i> Student Roster
                    </a>
                    <a href="{{ route('teacher.subjects.create') }}" class="portal-stage-link">
                        <i class="bi bi-plus-circle" aria-hidden="true"></i> Add Class
                    </a>
                </div>
                <span class="td-stage-foot-note portal-tabular">
                    @if($todayCollection->isEmpty())
                        {{ $subjectsCollection->count() }} total {{ \Illuminate\Support\Str::plural('class', $subjectsCollection->count()) }}
                    @elseif($recordedTodayCount === $todayCollection->count())
                        <i class="bi bi-check2-circle text-success" aria-hidden="true"></i> All sessions recorded
                    @else
                        {{ $todayCollection->count() - $recordedTodayCount }} {{ \Illuminate\Support\Str::plural('session', $todayCollection->count() - $recordedTodayCount) }} remaining
                    @endif
                </span>
            </div>
        </section>

        {{-- Right: Attendance Telemetry & Interactive 7-Day Trend --}}
        <section class="portal-telemetry" aria-label="Daily Attendance Telemetry and 7-Day Trend">
            <div class="portal-standing-top">
                <div class="portal-gauge-wrap">
                    <svg class="portal-gauge-svg" viewBox="0 0 88 88" aria-hidden="true">
                        <circle class="portal-gauge-track" cx="44" cy="44" r="{{ $gaugeRadius }}"></circle>
                        <circle class="portal-gauge-fill is-{{ $rateTone }}"
                                cx="44" cy="44" r="{{ $gaugeRadius }}"
                                stroke-dasharray="{{ $gaugeCircumference }}"
                                stroke-dashoffset="{{ $gaugeOffset }}"></circle>
                    </svg>
                    <div class="portal-gauge-center">
                        <span class="portal-gauge-value portal-tabular is-{{ $rateTone }}">{{ $attendanceRate === null ? '—' : $attendanceRate . '%' }}</span>
                        <span class="portal-gauge-sub">Today</span>
                    </div>
                </div>

                <div style="min-width: 0; flex: 1 1 auto;">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-1">
                        <h2 style="margin: 0; font-size: 0.96rem; font-weight: 800; color: #ffffff;">Daily Turnout</h2>
                        <span class="portal-status-pill is-{{ $rateTone === 'good' ? 'present' : ($rateTone === 'warning' ? 'late' : ($rateTone === 'danger' ? 'absent' : 'muted')) }}">{{ $rateTier }}</span>
                    </div>
                    <p style="margin: 0; font-size: 0.79rem; color: var(--portal-text-soft);" class="portal-tabular">
                        @if($totalLogged > 0)
                            <strong>{{ $presentCount }}</strong> attended out of <strong>{{ $totalLogged }}</strong> logged check-ins
                        @else
                            Live check-ins appear here once a session starts
                        @endif
                    </p>
                </div>
            </div>

            {{-- Live Echo-Compatible KPI Strip --}}
            <div id="realStats" class="td-live-kpi-grid" role="region" aria-label="Today's check-in totals">
                <div class="td-kpi-pill" data-accent="success">
                    <span class="td-kpi-pill-label">Present</span>
                    <span class="ent-kpi-value">{{ $presentCount }}</span>
                </div>
                <div class="td-kpi-pill" data-accent="warning">
                    <span class="td-kpi-pill-label">Late</span>
                    <span class="ent-kpi-value">{{ $lateCount }}</span>
                </div>
                <div class="td-kpi-pill" data-accent="danger">
                    <span class="td-kpi-pill-label">Absent</span>
                    <span class="ent-kpi-value">{{ $absentCount }}</span>
                </div>
            </div>

            <div class="portal-breakdown-bar" role="img" aria-label="Today breakdown: {{ $onTimeCount }} on-time, {{ $lateCount }} late, {{ $absentCount }} absent">
                @if($totalLogged > 0)
                    @if($onTimePct > 0)<span class="portal-seg is-present" style="width: {{ $onTimePct }}%;"></span>@endif
                    @if($latePct > 0)<span class="portal-seg is-late" style="width: {{ $latePct }}%;"></span>@endif
                    @if($absentPct > 0)<span class="portal-seg is-absent" style="width: {{ $absentPct }}%;"></span>@endif
                @else
                    <span class="portal-seg is-empty" style="width: 100%;"></span>
                @endif
            </div>

            {{-- 7-Day Attendance Trend Bars (uses AnalyticsService weekly arrays) --}}
            @if(!empty($labelsArr))
            <div class="td-trend-section" id="tdWeeklyTrend">
                <div class="td-trend-head">
                    <span class="td-trend-title">7-Day Attendance Trend</span>
                    <span class="td-trend-readout portal-mono" id="tdTrendReadout">Tap a day</span>
                </div>
                <div class="td-trend-bars" role="group" aria-label="7-Day Attendance Volume">
                    @foreach($labelsArr as $idx => $dayLabel)
                        @php
                            $dPres = (int) ($wPresentArr[$idx] ?? 0);
                            $dLate = (int) ($wLateArr[$idx] ?? 0);
                            $dAbs = (int) ($wAbsentArr[$idx] ?? 0);
                            $dTot = $dPres + $dLate + $dAbs;
                            $hPres = $dTot > 0 ? round(($dPres / $maxDayTotal) * 100, 1) : 0;
                            $hLate = $dTot > 0 ? round(($dLate / $maxDayTotal) * 100, 1) : 0;
                            $hAbs = $dTot > 0 ? round(($dAbs / $maxDayTotal) * 100, 1) : 0;
                            $isLastDay = $idx === (count($labelsArr) - 1);
                        @endphp
                        <button type="button"
                                class="td-trend-col {{ $isLastDay ? 'is-today is-selected' : '' }}"
                                data-trend-summary="{{ $dayLabel }}: {{ $dPres }} Present · {{ $dLate }} Late · {{ $dAbs }} Absent"
                                aria-label="{{ $dayLabel }}: {{ $dPres }} present, {{ $dLate }} late, {{ $dAbs }} absent">
                            <div class="td-trend-track" aria-hidden="true">
                                @if($hPres > 0)<span class="td-trend-seg is-present" style="height: {{ $hPres }}%;"></span>@endif
                                @if($hLate > 0)<span class="td-trend-seg is-late" style="height: {{ $hLate }}%;"></span>@endif
                                @if($hAbs > 0)<span class="td-trend-seg is-absent" style="height: {{ $hAbs }}%;"></span>@endif
                            </div>
                            <span class="td-trend-total portal-mono">{{ $dTot }}</span>
                            <span class="td-trend-day">{{ $dayLabel }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            @endif
        </section>
    </div>

    {{-- ── 4. Asymmetric Operational Workspace ───────────────────────────── --}}
    <div class="portal-workspace">
        <div class="portal-col-main">
            {{-- School Calendar Panel --}}
            <section class="portal-panel teacher-dashboard-calendar" aria-labelledby="teacherCalendarHeading">
                <header class="portal-panel-head">
                    <div class="portal-panel-title-group">
                        <h2 id="teacherCalendarHeading" class="portal-panel-title">School Calendar</h2>
                        <span id="calendarTitle" class="portal-panel-meta portal-tabular">{{ now()->format('F Y') }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Calendar navigation">
                            <button type="button" id="calPrevBtn" class="btn btn-sm tch-btn-secondary" aria-label="Previous calendar period">
                                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                            </button>
                            <button type="button" id="calTodayBtn" class="btn btn-sm tch-btn-secondary">Today</button>
                            <button type="button" id="calNextBtn" class="btn btn-sm tch-btn-secondary" aria-label="Next calendar period">
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </button>
                        </div>
                        <a href="{{ route('teacher.calendar') }}" class="portal-panel-link ms-1">
                            Full calendar <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </header>

                <div class="portal-panel-body">
                    <div class="d-flex gap-3 flex-wrap mb-3" style="font-size: 0.76rem; font-weight: 600; color: var(--portal-muted);">
                        <span class="d-inline-flex align-items-center gap-1"><span style="width:8px;height:8px;border-radius:50%;background:#60a5fa;"></span> Class</span>
                        <span class="d-inline-flex align-items-center gap-1"><span style="width:8px;height:8px;border-radius:50%;background:#ec4899;"></span> Exam</span>
                        <span class="d-inline-flex align-items-center gap-1"><span style="width:8px;height:8px;border-radius:50%;background:#fbbf24;"></span> Meeting</span>
                        <span class="d-inline-flex align-items-center gap-1"><span style="width:8px;height:8px;border-radius:50%;background:#a78bfa;"></span> School Event</span>
                        <span class="d-inline-flex align-items-center gap-1"><span style="width:8px;height:8px;border-radius:50%;background:#4ade80;"></span> Holiday</span>
                    </div>
                    <div id="schoolCalendar"></div>
                </div>
            </section>

            {{-- Recent Attendance Ledger --}}
            <section class="portal-panel" aria-labelledby="teacherRecentHeading">
                <header class="portal-panel-head">
                    <div class="portal-panel-title-group">
                        <h2 id="teacherRecentHeading" class="portal-panel-title">Recent Attendance</h2>
                        <span class="portal-panel-meta">Latest check-ins across your subjects</span>
                    </div>
                    <a href="{{ route('teacher.attendance') }}" class="portal-panel-link">View all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </header>

                <div class="portal-panel-body" style="padding-bottom: 10px; border-bottom: 1px solid var(--portal-border-subtle);">
                    <input type="text" id="recentLogsSearch" class="td-search-input" placeholder="Search student name or class..." aria-label="Search recent attendance">
                </div>

                <div id="recentAttendanceRows" class="td-ledger-list">
                    @forelse($recentCollection->take(8) as $record)
                        @php
                            $recordStatus = strtolower((string) ($record->status ?? 'absent'));
                            $recordTone = $recordStatus === 'present' ? 'present' : ($recordStatus === 'late' ? 'late' : 'absent');
                            $studentName = $record->user->name ?? 'Unknown Student';
                            $initials = mb_strtoupper(mb_substr($studentName, 0, 2));
                            $loggedAt = $record->time_in
                                ? \Carbon\Carbon::parse($record->time_in)->format('g:i A')
                                : ($record->created_at ? $record->created_at->format('M j, g:i A') : '');
                        @endphp
                        <div class="attendance-row">
                            <div class="d-flex align-items-center gap-3" style="min-width: 0; flex: 1 1 200px;">
                                <div class="td-avatar-monogram portal-mono" aria-hidden="true">{{ $initials }}</div>
                                <div style="min-width: 0;">
                                    <div style="font-weight: 700; color: #ffffff; font-size: 0.88rem; overflow: hidden; text-overflow: ellipsis;">{{ $studentName }}</div>
                                    <div style="color: var(--portal-muted); font-size: 0.76rem;" class="portal-tabular">
                                        <span class="portal-code-tag portal-mono">{{ $record->subject_code }}</span>
                                        <span>{{ $record->subject->name ?? '' }}</span>
                                        @if($loggedAt)<span>&middot; {{ $loggedAt }}</span>@endif
                                    </div>
                                </div>
                            </div>
                            <span class="portal-status-pill is-{{ $recordTone }}">{{ ucfirst($recordStatus) }}</span>
                        </div>
                    @empty
                        <div class="empty-state portal-inline-empty">
                            <span>No check-in activity yet for your classes.</span>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="portal-col-aside">
            {{-- Early Warning / At-Risk Students --}}
            <section class="portal-panel" aria-labelledby="teacherAtRiskHeading">
                <header class="portal-panel-head">
                    <div class="portal-panel-title-group">
                        <h2 id="teacherAtRiskHeading" class="portal-panel-title">Early Warning</h2>
                        <span class="portal-panel-meta">Students below 80% attendance across your classes</span>
                    </div>
                    <a href="{{ route('teacher.absent') }}" class="portal-panel-link">Absent report <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </header>

                @if($atRiskCollection->isNotEmpty())
                    <div>
                        @foreach($atRiskCollection->take(5) as $stat)
                            @php
                                $atRiskUser = $stat->user;
                                $rateVal = (int) ($stat->rate ?? 0);
                            @endphp
                            @if($atRiskUser)
                            <a href="{{ route('teacher.student', $stat->user_id) }}" class="td-atrisk-row">
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <span style="font-size: 0.86rem; font-weight: 700; color: #ffffff;">{{ $atRiskUser->name }}</span>
                                    <span class="portal-mono" style="font-size: 0.82rem; font-weight: 800; color: var(--portal-rose);">{{ $rateVal }}%</span>
                                </div>
                                <div style="height: 5px; width: 100%; border-radius: 999px; background: rgba(255,255,255,0.07); overflow: hidden;" aria-hidden="true">
                                    <span style="display: block; height: 100%; width: {{ min(100, max(4, $rateVal)) }}%; background: var(--portal-rose); border-radius: 999px;"></span>
                                </div>
                                <div class="d-flex justify-content-between" style="font-size: 0.72rem; color: var(--portal-muted);">
                                    <span>{{ $atRiskUser->student_number ?? ($atRiskUser->course ?? 'Student') }}</span>
                                    <span class="portal-mono">{{ (int) ($stat->present_sessions ?? 0) }}/{{ (int) ($stat->total_sessions ?? 0) }} sessions</span>
                                </div>
                            </a>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="portal-inline-empty">
                        <span>All students are currently at or above 80% attendance.</span>
                    </div>
                @endif
            </section>

            {{-- Post Class Announcement --}}
            <section class="portal-panel" aria-labelledby="teacherAnnounceHeading">
                <header class="portal-panel-head">
                    <div class="portal-panel-title-group">
                        <h2 id="teacherAnnounceHeading" class="portal-panel-title">Post Class Announcement</h2>
                        <span class="portal-panel-meta">Notify students enrolled in a specific subject</span>
                    </div>
                </header>

                <div class="portal-panel-body">
                    <form action="{{ route('teacher.announcements.store') }}" method="POST" class="td-announce-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="teacherAnnouncementClass" style="font-size: 0.78rem; font-weight: 700; color: var(--portal-text-soft);">Class *</label>
                            <select id="teacherAnnouncementClass" name="target_id" class="form-select" required>
                                <option value="">Select a class...</option>
                                @foreach($subjectsCollection as $sub)
                                    <option value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="teacherAnnouncementTitle" style="font-size: 0.78rem; font-weight: 700; color: var(--portal-text-soft);">Title *</label>
                            <input id="teacherAnnouncementTitle" type="text" name="title" class="form-control" placeholder="e.g. Quiz Schedule Update" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="teacherAnnouncementMessage" style="font-size: 0.78rem; font-weight: 700; color: var(--portal-text-soft);">Message *</label>
                            <textarea id="teacherAnnouncementMessage" name="content" class="form-control" rows="3" placeholder="Write your notice for students..." required></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="teacherAnnouncementSchedule" style="font-size: 0.78rem; font-weight: 700; color: var(--portal-text-soft);">Publish later (optional)</label>
                            <input id="teacherAnnouncementSchedule" type="datetime-local" name="scheduled_for" class="form-control">
                        </div>
                        <button type="submit" class="portal-cta-primary w-100">
                            <i class="bi bi-send-fill" aria-hidden="true"></i>
                            <span>Publish Announcement</span>
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Event Detail Modal -->
<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content w-100">
            <div class="modal-header">
                <h5 class="modal-title" id="eventTitle">Event Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--portal-muted); font-weight: 700; margin-bottom: 4px;">Event Type</div>
                    <span id="eventTypeBadge" class="portal-code-tag">Event</span>
                </div>
                <div class="mb-3">
                    <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--portal-muted); font-weight: 700; margin-bottom: 4px;">Date</div>
                    <div id="eventDateText" style="color: #ffffff; font-weight: 600;">Date</div>
                </div>
                <div id="eventDescriptionContainer" class="mb-3" style="display: none;">
                    <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--portal-muted); font-weight: 700; margin-bottom: 4px;">Description</div>
                    <div id="eventDescription" style="color: var(--portal-text-soft); font-size: 0.9rem;"></div>
                </div>
                <div id="eventLocationContainer" style="display: none;">
                    <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--portal-muted); font-weight: 700; margin-bottom: 4px;">Location</div>
                    <div id="eventLocation" style="color: var(--portal-text-soft); font-size: 0.9rem;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script @cspNonce>
// Real-time campus clock (paused when tab is backgrounded)
(function() {
    const clockEl = document.getElementById('teacherClock');
    if (!clockEl) return;
    function tick() {
        if (document.hidden) return;
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        clockEl.textContent = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
    }
    setInterval(tick, 1000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) tick(); });
    tick();
})();

// 7-Day Trend Bar Readout Interaction
(function() {
    const readout = document.getElementById('tdTrendReadout');
    const cols = document.querySelectorAll('.td-trend-col[data-trend-summary]');
    if (!readout || !cols.length) return;
    const initial = cols[cols.length - 1];
    if (initial) readout.textContent = initial.dataset.trendSummary || 'Tap a day';
    cols.forEach(col => {
        const showSummary = () => {
            cols.forEach(c => c.classList.remove('is-selected'));
            col.classList.add('is-selected');
            readout.textContent = col.dataset.trendSummary || '';
        };
        col.addEventListener('click', showSummary);
        col.addEventListener('mouseenter', showSummary);
    });
})();

// Recent Attendance live search
(function() {
    const searchInput = document.getElementById('recentLogsSearch');
    if (!searchInput) return;
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        document.querySelectorAll('#recentAttendanceRows .attendance-row').forEach(row => {
            const text = (row.textContent || '').toLowerCase();
            row.style.display = text.includes(query) ? 'flex' : 'none';
        });
    });
})();

// Peer verification events update this dashboard without a page reload.
(function subscribeToPeerAttendance() {
    let attempts = 0;
    let connectionDismissed = false;
    const status = document.getElementById('peerLiveConnection');
    const seen = new Set();
    function setBannerMessage(bannerEl, message) {
        if (!bannerEl) return;
        const messageEl = bannerEl.querySelector('[data-banner-message]') || bannerEl;
        messageEl.textContent = message || '';
    }
    document.querySelectorAll('[data-banner-dismiss]').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-banner-dismiss');
            const banner = targetId ? document.getElementById(targetId) : btn.closest('.portal-banner');
            if (!banner) return;
            if (banner === status) {
                connectionDismissed = true;
            }
            banner.hidden = true;
        });
    });
    function setConnectionMessage(message) {
        if (!status) return;
        if (!message) {
            connectionDismissed = false;
            setBannerMessage(status, '');
            status.hidden = true;
            return;
        }
        setBannerMessage(status, message);
        status.hidden = connectionDismissed;
    }
    function connectionMessageFor(state) {
        if (state === 'connected') return '';
        if (state === 'initialized' || state === 'connecting') return 'Connecting to live attendance updates…';
        if (state === 'unavailable' || state === 'failed') {
            return 'Live updates are unavailable. Attendance data is saved; refresh after the live service reconnects.';
        }
        return 'Live updates are disconnected. Attendance data is saved while the connection recovers.';
    }
    function connect() {
        const echo = window.teacherEcho || window.Echo;
        if (!echo) {
            setConnectionMessage(connectionMessageFor('connecting'));
            if (++attempts < 20) setTimeout(connect, 500);
            else setConnectionMessage(connectionMessageFor('unavailable'));
            return;
        }
        try {
            echo.private('teacher-dashboard.{{ Auth::id() }}').listen('.attendance.peer.verified', payload => {
                if (!payload || !Number.isSafeInteger(Number(payload.attendance_id)) || seen.has(payload.attendance_id)) return;
                seen.add(payload.attendance_id);
                const notice = document.getElementById('peerLiveNotice');
                if (notice) {
                    setBannerMessage(notice, `${payload.student_name} checked in by Peer Biometric for ${payload.subject_code}. Vouched by ${payload.voucher_student_name}.`);
                    notice.hidden = false;
                }

                const list = document.getElementById('recentAttendanceRows');
                if (list) {
                    const empty = list.querySelector('.empty-state');
                    if (empty) empty.remove();
                    const row = document.createElement('div');
                    row.className = 'attendance-row';
                    const name = document.createElement('strong');
                    name.style.color = '#ffffff';
                    name.textContent = payload.student_name;
                    const detail = document.createElement('div');
                    detail.style.cssText = 'color:var(--portal-muted);font-size:.78rem;margin-top:3px;';
                    detail.textContent = `${payload.subject_code} · Peer Biometric · Vouched by ${payload.voucher_student_name}`;
                    row.append(name, detail);
                    list.prepend(row);
                    while (list.children.length > 8) list.lastElementChild.remove();
                }

                const present = document.querySelector('#realStats [data-accent="success"] .ent-kpi-value');
                if (present && /^\d+$/.test(present.textContent.trim())) present.textContent = String(Number(present.textContent.trim()) + 1);
                const absent = document.querySelector('#realStats [data-accent="danger"] .ent-kpi-value');
                if (payload.previous_status === 'Absent' && absent && /^\d+$/.test(absent.textContent.trim())) {
                    absent.textContent = String(Math.max(0, Number(absent.textContent.trim()) - 1));
                }
            });
            const connection = echo.connector?.pusher?.connection;
            connection?.bind('state_change', state => setConnectionMessage(connectionMessageFor(state.current)));
            setConnectionMessage(connectionMessageFor(connection?.state || 'unavailable'));
        } catch {
            setConnectionMessage(connectionMessageFor('unavailable'));
        }
    }
    connect();
})();

// School Calendar Initialization
let schoolCalendar;
function updateCalendarTitle() {
    if (schoolCalendar && schoolCalendar.view) {
        const titleEl = document.getElementById('calendarTitle');
        if (titleEl) {
            titleEl.textContent = schoolCalendar.view.title;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('schoolCalendar');
    if (!calendarEl || typeof FullCalendar === 'undefined') return;

    schoolCalendar = new FullCalendar.Calendar(calendarEl, {
        initialView: window.matchMedia('(max-width: 767.98px)').matches ? 'listMonth' : 'dayGridMonth',
        headerToolbar: false,
        height: 'auto',
        firstDay: 0,
        weekends: true,
        fixedWeekCount: false,
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch(`{{ route('teacher.calendar.data') }}?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
                .then(response => response.json())
                .then(data => {
                    const events = data.map(event => ({
                        id: event.id,
                        title: event.name || event.title,
                        start: event.date || event.start,
                        end: event.end,
                        backgroundColor: event.color || '#4ade80',
                        borderColor: event.color || '#4ade80',
                        textColor: '#ffffff',
                        extendedProps: {
                            type: event.type_label || event.type,
                            description: event.description,
                            location: event.location,
                            status: event.status
                        }
                    }));
                    successCallback(events);
                })
                .catch(error => {
                    console.error('Error fetching calendar data:', error);
                    failureCallback(error);
                });
        },
        eventClick: function(info) {
            const event = info.event;
            const props = event.extendedProps || {};

            document.getElementById('eventTitle').textContent = event.title;
            document.getElementById('eventTypeBadge').textContent = props.type || 'Event';
            document.getElementById('eventDateText').textContent = event.start
                ? event.start.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
                : '';

            const descEl = document.getElementById('eventDescription');
            const descContainer = document.getElementById('eventDescriptionContainer');
            if (props.description) {
                descEl.textContent = props.description;
                descContainer.style.display = 'block';
            } else {
                descContainer.style.display = 'none';
            }

            const locEl = document.getElementById('eventLocation');
            const locContainer = document.getElementById('eventLocationContainer');
            if (props.location) {
                locEl.textContent = props.location;
                locContainer.style.display = 'block';
            } else {
                locContainer.style.display = 'none';
            }

            const modalEl = document.getElementById('eventDetailModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        },
        dayCellClassNames: function(arg) {
            const today = new Date();
            if (arg.date.toDateString() === today.toDateString()) {
                return ['fc-day-today'];
            }
            return [];
        }
    });

    schoolCalendar.render();
    updateCalendarTitle();

    document.getElementById('calPrevBtn')?.addEventListener('click', () => { schoolCalendar.prev(); updateCalendarTitle(); });
    document.getElementById('calTodayBtn')?.addEventListener('click', () => { schoolCalendar.today(); updateCalendarTitle(); });
    document.getElementById('calNextBtn')?.addEventListener('click', () => { schoolCalendar.next(); updateCalendarTitle(); });
});
</script>
@endsection
