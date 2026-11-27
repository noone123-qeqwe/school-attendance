@extends('layouts.app')
@section('page-title', 'Dashboard')

@section('content')

@php
    $studentUser = Auth::user();
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

    $rateVal = $attendanceRate ?? null;
    $rateTone = $rateVal === null ? 'empty' : ($rateVal >= 85 ? 'good' : ($rateVal >= 75 ? 'warning' : 'danger'));
    $rateTier = $rateVal === null ? 'New' : ($rateVal >= 85 ? 'Good Standing' : ($rateVal >= 75 ? 'Fair Standing' : 'Needs Review'));
    $gaugeRadius = 38;
    $gaugeCircumference = round(2 * pi() * $gaugeRadius, 2);
    $gaugePct = $rateVal === null ? 0 : min(100, max(0, (float) $rateVal));
    $gaugeOffset = round($gaugeCircumference * (1 - ($gaugePct / 100)), 2);

    $totalPresentCount = (int) ($totalPresent ?? 0);
    $totalLateCount = (int) ($totalLate ?? 0);
    $totalAbsentCount = (int) ($totalAbsent ?? 0);
    $totalSessions = $totalPresentCount + $totalLateCount + $totalAbsentCount;
    $presentPct = $totalSessions > 0 ? round(($totalPresentCount / $totalSessions) * 100, 1) : 0;
    $latePct = $totalSessions > 0 ? round(($totalLateCount / $totalSessions) * 100, 1) : 0;
    $absentPct = $totalSessions > 0 ? max(0, round(100 - $presentPct - $latePct, 1)) : 0;

    $weeklyPulseCollection = collect($weeklyPulse ?? []);
    $subjectsCollection = collect($subjects ?? []);
    $subjectStatsCollection = collect($subjectStats ?? []);
    $pendingExcuses = (int) ($pendingExcusesCount ?? 0);
    $peerAvailable = app(\App\Services\PeerBiometricAttendanceService::class)->isAvailable();

    $metaParts = [];
    if (!empty($studentUser?->student_number)) {
        $metaParts[] = ['label' => 'ID ' . $studentUser->student_number, 'mono' => true];
    }
    $metaParts[] = ['label' => $studentUser?->course ?: 'BSCS', 'mono' => false];
    $metaParts[] = ['label' => 'Yr ' . ($studentUser?->year_level ?: '1') . ' · Sem ' . ($studentUser?->semester ?: '1'), 'mono' => false];
    if (!empty($studentUser?->section)) {
        $metaParts[] = ['label' => 'Sec ' . $studentUser->section, 'mono' => false];
    }
@endphp

<div class="sd-cockpit">
    {{-- ── 1. Open Editorial Masthead ────────────────────────────────────── --}}
    <header class="sd-masthead" aria-label="Student overview">
        <div class="sd-identity-cluster">
            <div class="sd-avatar-ring">
                <img src="{{ $studentUser->profile_photo_url_with_version }}" alt="{{ $studentUser->name }}">
            </div>
            <div class="sd-identity-copy">
                <div class="sd-kicker-line">
                    <span class="sd-kicker-greeting">
                        <i class="bi {{ $greetIcon }}" aria-hidden="true"></i>
                        <span>{{ $greeting }}</span>
                    </span>
                    <span class="sd-kicker-sep d-md-none" aria-hidden="true">&middot;</span>
                    <span class="d-md-none">{{ now()->format('l, M j') }}</span>
                    <span class="sd-kicker-sep" aria-hidden="true">&middot;</span>
                    <span id="studentClock" class="sd-kicker-clock sd-mono" title="Campus Local Time">{{ now()->format('g:i A') }}</span>
                </div>
                <h1 class="sd-student-name">{{ $studentUser->name }}</h1>
                <div class="sd-meta-ribbon">
                    @foreach($metaParts as $metaPart)
                        @if(!$loop->first)<span class="sd-meta-sep" aria-hidden="true">&middot;</span>@endif
                        <span class="sd-meta-item {{ $metaPart['mono'] ? 'sd-mono is-id' : '' }}">{{ $metaPart['label'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="sd-masthead-actions d-none d-md-flex">
            <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sd-cta-primary">
                <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                <span>Scan QR</span>
            </a>
            <a href="{{ route('mobile.scan', ['open_code' => 1]) }}" class="sd-cta-secondary">
                <i class="bi bi-123" aria-hidden="true"></i>
                <span>Enter PIN</span>
            </a>
        </div>
    </header>

    {{-- ── 2. Contextual Alert Rail (Conditional) ────────────────────────── --}}
    @include('partials.missing-section-notice')

    @if(session('error'))
    <div class="sd-error-banner" role="alert">
        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    @if(session('success'))
    <div class="sd-success-banner" role="status">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(isset($activeWarnings) && $activeWarnings->count() > 0)
    <section class="sd-warning-panel" role="region" aria-label="Attendance Warnings" @include('partials.student-warning-alert-attributes')>
        <div class="sd-warning-top">
            <div class="sd-warning-icon" aria-hidden="true">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <h2 class="sd-warning-title">Action Required: Attendance Warning</h2>
                <p class="sd-warning-sub">You have {{ $activeWarnings->count() }} active {{ \Illuminate\Support\Str::plural('warning', $activeWarnings->count()) }}. Review your attendance or submit supporting documentation.</p>
            </div>
        </div>

        <div class="sd-warning-list">
            @foreach($activeWarnings as $warning)
            <div class="sd-warning-card">
                <div class="sd-warning-card-head">
                    <span class="sd-warning-code sd-mono"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> {{ $warning->subject_code }}</span>
                    <span class="sd-warning-time">{{ $warning->created_at->diffForHumans() }}</span>
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
    @else
    <span hidden data-alert-resolved="student-attendance-warnings"></span>
    @endif

    {{-- ── 3. Signature Asymmetric Hero Canvas (Stage + Telemetry) ─────────── --}}
    <div class="sd-hero-grid">
        <section id="sdHeroSpotlight"
                 class="sd-stage {{ $liveClassItem ? 'is-live' : ($nextClassItem ? 'is-next' : 'is-idle') }}"
                 aria-labelledby="dashboardClassHeading"
                 @if($featuredClass)
                    data-start-ms="{{ $featuredClass->start_time->getTimestampMs() }}"
                    data-end-ms="{{ $featuredClass->end_time->getTimestampMs() }}"
                 @endif>
            <div class="sd-stage-head">
                <span class="sd-stage-eyebrow">
                    <span class="sd-pulse-dot {{ $liveClassItem ? 'is-live' : ($nextClassItem ? 'is-next' : 'is-done') }}" aria-hidden="true"></span>
                    {{ $liveClassItem ? 'Class in Progress' : ($nextClassItem ? 'Up Next Today' : "Today's Status") }}
                </span>
                @if($featuredClass)
                    <span class="sd-stage-badges">
                        <span id="sdSpotlightTimer" class="sd-stage-timer sd-mono" aria-live="off"></span>
                        <span class="sd-stage-code sd-mono">{{ $featuredClass->subject->code }}</span>
                    </span>
                @endif
            </div>

            <div class="sd-stage-body">
                <h2 id="dashboardClassHeading" class="sd-stage-title">
                    {{ $featuredClass?->subject?->name ?? ($todayClassCount ? 'All classes completed today' : 'Free schedule today') }}
                </h2>

                @if($featuredClass)
                    <div class="sd-stage-meta sd-tabular">
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
                            <div class="sd-session-meter-labels sd-mono">
                                <span>{{ $liveClassItem->start_time->format('g:i A') }}</span>
                                <span>{{ $liveClassItem->end_time->format('g:i A') }}</span>
                            </div>
                            <div class="sd-session-meter-track">
                                <span id="sdSpotlightProgressFill" class="sd-session-meter-fill" style="width: {{ $spotProgressPct }}%;"></span>
                            </div>
                        </div>
                    @endif
                @else
                    <p class="sd-stage-note">
                        {{ $todayClassCount ? 'All scheduled sessions for today are complete. Review your attendance standing or upcoming week below.' : 'No classes scheduled for today. Check your weekly timetable, subject standing, or excuse requests anytime.' }}
                    </p>
                @endif
            </div>

            <div class="sd-stage-foot">
                <div class="sd-stage-actions">
                    @if($liveClassItem && empty($alreadyClockedIn))
                        <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sd-stage-btn-primary">
                            <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Check in now
                        </a>
                    @elseif($liveClassItem && !empty($alreadyClockedIn))
                        <span class="sd-stage-checked-pill">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i> Checked in
                        </span>
                    @endif
                    <a href="{{ route('student.schedule', ['view' => 'weekly']) }}" class="sd-stage-link">
                        View timetable <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                    @if($peerAvailable)
                        <a href="{{ route('peer-snap.index') }}" class="sd-stage-link is-subtle">
                            <i class="bi bi-person-check" aria-hidden="true"></i> Check in for a classmate
                        </a>
                    @endif
                    @if(!$featuredClass)
                        <a href="{{ route('student.calendar') }}" class="sd-stage-link is-subtle">
                            Calendar <i class="bi bi-calendar-event" aria-hidden="true"></i>
                        </a>
                    @endif
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

        <section class="sd-telemetry" aria-label="Attendance Standing and Weekly Pulse">
            <div class="sd-standing-top">
                <div class="sd-gauge-wrap">
                    <svg class="sd-gauge-svg" viewBox="0 0 88 88" aria-hidden="true">
                        <circle class="sd-gauge-track" cx="44" cy="44" r="{{ $gaugeRadius }}"></circle>
                        <circle class="sd-gauge-fill is-{{ $rateTone }}"
                                cx="44" cy="44" r="{{ $gaugeRadius }}"
                                stroke-dasharray="{{ $gaugeCircumference }}"
                                stroke-dashoffset="{{ $gaugeOffset }}"></circle>
                    </svg>
                    <div class="sd-gauge-center">
                        <span class="sd-gauge-value sd-tabular is-{{ $rateTone }}">{{ $rateVal === null ? '—' : $rateVal.'%' }}</span>
                        <span class="sd-gauge-sub">Rate</span>
                    </div>
                </div>

                <div class="sd-standing-info">
                    <div class="sd-standing-head-row">
                        <h2 class="sd-standing-title">Attendance Standing</h2>
                        <span class="sd-standing-tier is-{{ $rateTone }}">{{ $rateTier }}</span>
                    </div>
                    <p class="sd-standing-desc sd-tabular">
                        @if($totalSessions > 0)
                            <strong>{{ $totalPresentCount + $totalLateCount }}</strong> of <strong>{{ $totalSessions }}</strong> {{ \Illuminate\Support\Str::plural('session', $totalSessions) }} attended
                        @else
                            Unlocks once your first class session is recorded
                        @endif
                    </p>

                    <div class="sd-standing-badges">
                        @if(($streakCount ?? 0) > 0)
                            <span class="sd-telemetry-chip is-streak sd-tabular">
                                <i class="bi bi-fire" aria-hidden="true"></i> {{ $streakCount }}d streak
                            </span>
                        @endif
                        @if($pendingExcuses > 0)
                            <a href="{{ route('excuses') }}" class="sd-telemetry-chip is-pending sd-tabular">
                                <i class="bi bi-hourglass-split" aria-hidden="true"></i> {{ $pendingExcuses }} pending {{ \Illuminate\Support\Str::plural('excuse', $pendingExcuses) }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="sd-breakdown-strip">
                <div class="sd-breakdown-bar" role="img" aria-label="Session distribution: {{ $totalPresentCount }} Present, {{ $totalLateCount }} Late, {{ $totalAbsentCount }} Absent">
                    @if($totalSessions > 0)
                        @if($presentPct > 0)<span class="sd-seg is-present" style="width: {{ $presentPct }}%;"></span>@endif
                        @if($latePct > 0)<span class="sd-seg is-late" style="width: {{ $latePct }}%;"></span>@endif
                        @if($absentPct > 0)<span class="sd-seg is-absent" style="width: {{ $absentPct }}%;"></span>@endif
                    @else
                        <span class="sd-seg is-empty" style="width: 100%;"></span>
                    @endif
                </div>
                <div class="sd-breakdown-legend sd-tabular">
                    <span class="sd-legend-item"><i class="sd-dot is-present" aria-hidden="true"></i> <strong>{{ $totalPresentCount }}</strong> Present</span>
                    <span class="sd-legend-item"><i class="sd-dot is-late" aria-hidden="true"></i> <strong>{{ $totalLateCount }}</strong> Late</span>
                    <span class="sd-legend-item"><i class="sd-dot is-absent" aria-hidden="true"></i> <strong>{{ $totalAbsentCount }}</strong> Absent</span>
                </div>
            </div>

            @if($weeklyPulseCollection->isNotEmpty())
            <div class="sd-pulse-band">
                <div class="sd-pulse-head">
                    <span class="sd-pulse-title">7-Day Pulse</span>
                    <span class="sd-pulse-hint">Tap a day</span>
                </div>
                <div class="sd-pulse-grid" role="group" aria-label="7-Day Attendance Pulse">
                    @foreach($weeklyPulseCollection as $pulse)
                        <button type="button"
                                class="sd-pulse-day is-status-{{ $pulse->status }} {{ $pulse->is_today ? 'is-today' : '' }}"
                                data-date-key="{{ $pulse->date_key }}"
                                data-day-label="{{ $pulse->label }}"
                                aria-label="Inspect {{ $pulse->label }} ({{ $pulse->status }})">
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

    {{-- ── 5. Asymmetric Operational Workspace ───────────────────────────── --}}
    <div class="sd-workspace">
        <div class="sd-col-main">
            {{-- Today's Schedule --}}
            <section class="sd-panel" aria-labelledby="sdTodayScheduleHeading">
                <header class="sd-panel-head">
                    <div class="sd-panel-title-group">
                        <h2 id="sdTodayScheduleHeading" class="sd-panel-title">Today's Schedule</h2>
                        <span class="sd-panel-meta sd-tabular">{{ now()->format('l, M j') }} &middot; {{ $todayClassCount }} {{ \Illuminate\Support\Str::plural('class', $todayClassCount) }}</span>
                    </div>

                    @if($todayClassCount > 0)
                        <div class="sd-segmented" role="group" aria-label="Filter today's classes">
                            <button type="button" class="sd-seg-btn is-active" data-sched-filter="all" aria-pressed="true">All <span class="sd-mono">{{ $todayClassCount }}</span></button>
                            <button type="button" class="sd-seg-btn" data-sched-filter="remaining" aria-pressed="false">Remaining <span class="sd-mono">{{ $remainingTodayCount }}</span></button>
                            <button type="button" class="sd-seg-btn" data-sched-filter="done" aria-pressed="false">Done <span class="sd-mono">{{ $completedTodayCount }}</span></button>
                        </div>
                    @else
                        <a href="{{ route('student.schedule', ['view' => 'weekly']) }}" class="sd-panel-link">Full week <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </header>

                <div id="sdScheduleLiveRegion" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

                @if($todayClassCount > 0)
                    <div class="sd-timeline" id="sdScheduleList">
                        @foreach($todaySchedCollection as $item)
                            @php
                                $roomName = trim((string) ($item->schedule?->room ?? ''));
                                $instName = trim((string) ($item->subject?->instructorUser?->name ?? $item->subject?->instructor ?? ''));
                                $filterBucket = in_array($item->status, ['ongoing', 'upcoming'], true) ? 'remaining' : ($item->status === 'completed' ? 'done' : 'missed');
                                $statusColor = match ($item->status) {
                                    'completed' => '#4ADE80',
                                    'ongoing' => '#fbbf24',
                                    'missed' => '#f87171',
                                    default => '#D4AF37',
                                };
                            @endphp
                            <div class="sd-timeline-row is-status-{{ $item->status }}"
                                 data-sched-bucket="{{ $filterBucket }}"
                                 style="--tl-color: {{ $statusColor }};">
                                <div class="sd-tl-timecol sd-tabular">
                                    <strong>{{ $item->start_time->format('g:i A') }}</strong>
                                    <span>{{ $item->end_time->format('g:i A') }}</span>
                                </div>
                                <div class="sd-tl-rail" aria-hidden="true">
                                    <span class="sd-tl-node"></span>
                                </div>
                                <div class="sd-tl-content">
                                    <div class="sd-tl-subject">{{ $item->subject->name }}</div>
                                    <div class="sd-tl-meta sd-tabular">
                                        <span class="sd-tl-timepill sd-mono">{{ $item->start_time->format('g:i A') }} &ndash; {{ $item->end_time->format('g:i A') }}</span>
                                        <span class="sd-code-tag sd-mono">{{ $item->subject->code }}</span>
                                        @if($roomName !== '' && strtoupper($roomName) !== 'TBA')
                                            <span><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $roomName }}</span>
                                        @endif
                                        @if($instName !== '')
                                            <span><i class="bi bi-person" aria-hidden="true"></i> {{ $instName }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="sd-tl-actions">
                                    @if($item->status === 'completed')
                                        <span class="sd-status-pill is-present"><i class="bi bi-check-lg" aria-hidden="true"></i> Present</span>
                                    @elseif($item->status === 'ongoing')
                                        @if(empty($alreadyClockedIn))
                                            <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sd-inline-cta">Check in</a>
                                        @else
                                            <span class="sd-status-pill is-present">Checked in</span>
                                        @endif
                                    @elseif($item->status === 'missed')
                                        <span class="sd-status-pill is-missed">Missed</span>
                                        @if($item->attendance && $item->attendance->id)
                                            <a href="{{ route('excuses.create', $item->attendance->id) }}" class="sd-inline-link">Excuse</a>
                                        @else
                                            <a href="{{ route('excuses.create_general') }}" class="sd-inline-link">Excuse</a>
                                        @endif
                                    @elseif($item->status === 'past')
                                        <span class="sd-status-pill is-muted">Ended</span>
                                    @else
                                        <span class="sd-status-pill is-upcoming">Upcoming</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="sdScheduleFilterEmpty" class="sd-inline-empty" hidden>
                        <span>No classes match the selected filter.</span>
                        <button type="button" class="sd-reset-btn" id="sdResetSchedFilterBtn">Show all classes</button>
                    </div>
                @else
                    <div class="sd-inline-empty is-quiet">
                        <span>No classes scheduled for {{ now()->format('l') }}.</span>
                        <a href="{{ route('student.schedule', ['view' => 'weekly']) }}" class="sd-panel-link">Open weekly timetable <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                @endif
            </section>

            {{-- Campus & Class Announcements --}}
            @if(isset($announcements) && $announcements->isNotEmpty())
            <section class="sd-panel" id="studentAnnouncementsSection" aria-labelledby="studentAnnouncementsHeading" data-alert-group>
                <header class="sd-panel-head">
                    <div class="sd-panel-title-group">
                        <h2 id="studentAnnouncementsHeading" class="sd-panel-title">Campus &amp; Class Announcements</h2>
                        <span class="sd-panel-meta">Latest notices from administration &amp; instructors</span>
                    </div>
                </header>

                <div class="sd-ann-stack">
                    @foreach($announcements as $announcement)
                        @php
                            $annContent = trim((string) $announcement->content);
                            $annNeedsClamp = \Illuminate\Support\Str::length($annContent) > 170 || substr_count($annContent, "\n") >= 3;
                            $annUrgent = \Illuminate\Support\Str::contains(strtolower((string) $announcement->title . ' ' . ($announcement->target_audience ?? '')), ['urgent', 'important', 'alert']);
                            $annCopyId = 'sdAnnouncementCopy' . $loop->index;
                        @endphp
                        <article class="sd-ann-card {{ $annUrgent ? 'is-urgent' : '' }}" data-alert-banner
                                 data-alert-key="student-announcement-{{ $announcement->id }}"
                                 data-alert-version="{{ $announcement->updated_at?->format('U.u') }}"
                                 data-alert-label="Dismiss announcement">
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
                                <span><i class="bi bi-person" aria-hidden="true"></i> {{ $announcement->author->name ?? 'Administration' }}</span>
                                <span><i class="bi bi-clock" aria-hidden="true"></i> {{ ($announcement->scheduled_for ?? $announcement->created_at)->diffForHumans() }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
            @endif
        </div>

        <div class="sd-col-aside">
            {{-- Subject Standing --}}
            <section class="sd-panel" aria-labelledby="sdSubjectStandingHeading">
                <header class="sd-panel-head">
                    <div class="sd-panel-title-group">
                        <h2 id="sdSubjectStandingHeading" class="sd-panel-title">Subject Standing</h2>
                        <span class="sd-panel-meta sd-tabular">{{ $subjectStatsCollection->count() }} enrolled {{ \Illuminate\Support\Str::plural('subject', $subjectStatsCollection->count()) }}</span>
                    </div>
                    <a href="{{ route('student.classes') }}" class="sd-panel-link">Classes <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </header>

                @if($subjectStatsCollection->isNotEmpty())
                    <div class="sd-subject-list" id="sdSubjectList">
                        @foreach($subjectStatsCollection as $sub)
                            @php
                                $hasRecords = $sub->total > 0;
                                $subTone = !$hasRecords ? 'is-new' : ($sub->rate >= 85 ? 'is-good' : ($sub->rate >= 75 ? 'is-warn' : 'is-risk'));
                                $isHiddenExtra = $loop->index >= 5;
                            @endphp
                            <a href="{{ route('student.classes') }}"
                               class="sd-subject-row {{ $isHiddenExtra ? 'is-collapsible' : '' }}"
                               @if($isHiddenExtra) hidden @endif>
                                <div class="sd-subject-row-top">
                                    <div class="sd-subject-id">
                                        <span class="sd-code-tag sd-mono">{{ $sub->code }}</span>
                                        <span class="sd-subject-name">{{ $sub->name }}</span>
                                    </div>
                                    <span class="sd-subject-rate sd-tabular {{ $subTone }}">
                                        {{ $hasRecords ? $sub->rate . '%' : 'New' }}
                                    </span>
                                </div>
                                <div class="sd-meter-track" aria-hidden="true">
                                    <span class="sd-meter-fill {{ $subTone }}" style="width: {{ $hasRecords ? min(100, max(3, $sub->rate)) : 0 }}%;"></span>
                                </div>
                                <div class="sd-subject-counts sd-mono">
                                    @if($hasRecords)
                                        <span>{{ $sub->present }}P</span>
                                        <span>&middot; {{ $sub->late }}L</span>
                                        <span>&middot; {{ $sub->absent }}A</span>
                                    @else
                                        <span>No sessions recorded yet</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>

                    @if($subjectStatsCollection->count() > 5)
                        <div class="sd-panel-foot">
                            <button type="button"
                                    class="sd-expand-btn"
                                    id="sdToggleSubjectsBtn"
                                    aria-expanded="false"
                                    data-total-count="{{ $subjectStatsCollection->count() }}">
                                Show all {{ $subjectStatsCollection->count() }} subjects
                            </button>
                        </div>
                    @endif
                @else
                    <div class="sd-inline-empty is-quiet">
                        <span>No subjects assigned yet. Enrolled subjects will appear once assigned by the registrar.</span>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>

{{-- ── 6. Day Summary Inspector Modal / Bottom Sheet ──────────────────────── --}}
<div class="modal fade" id="daySummaryModal" tabindex="-1" aria-labelledby="sdDayModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="day-modal-sheet-handle" aria-hidden="true"></div>
            <div class="day-modal-action-bar">
                <div class="day-nav-stepper" role="group" aria-label="Step between dates">
                    <button type="button" class="day-nav-step-btn" id="sdDayPrevBtn" aria-label="Previous day">
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="day-nav-today-btn" id="sdDayTodayBtn">Today</button>
                    <button type="button" class="day-nav-step-btn" id="sdDayNextBtn" aria-label="Next day">
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
                <button type="button" class="day-modal-close-btn" data-bs-dismiss="modal" aria-label="Close inspector">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <div class="day-modal-header-content">
                <h2 id="sdDayModalTitle" class="day-modal-date">Select a date</h2>
                <div id="sdDayModalSummary" class="day-summary-badges"></div>
            </div>
            <div class="modal-body" id="sdDayModalBody"></div>
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
(function() {
    const calendarEndpoint = @json(route('student.dashboard.calendar.data'));
    const excusesCreateBase = @json(url('/excuses/create'));
    const excusesGeneralUrl = @json(route('excuses.create_general'));
    const monthCache = new Map();
    let activeDateKey = null;
    let dayModalInstance = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

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
        const clockEl = document.getElementById('studentClock');
        if (clockEl) clockEl.textContent = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;

        const spotlight = document.getElementById('sdHeroSpotlight');
        if (spotlight) {
            const startMs = Number(spotlight.getAttribute('data-start-ms') || 0);
            const endMs = Number(spotlight.getAttribute('data-end-ms') || 0);
            const timerEl = document.getElementById('sdSpotlightTimer');
            const progressFill = document.getElementById('sdSpotlightProgressFill');
            const nowMs = now.getTime();

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

    function applyScheduleFilter(filterValue) {
        const rows = document.querySelectorAll('#sdScheduleList [data-sched-bucket]');
        let visibleCount = 0;
        rows.forEach(function(row) {
            const bucket = row.getAttribute('data-sched-bucket');
            const show = filterValue === 'all' || bucket === filterValue || (filterValue === 'remaining' && bucket === 'missed');
            row.hidden = !show;
            if (show) visibleCount++;
        });

        const emptyEl = document.getElementById('sdScheduleFilterEmpty');
        if (emptyEl) emptyEl.hidden = visibleCount > 0;

        document.querySelectorAll('[data-sched-filter]').forEach(function(btn) {
            const isActive = btn.getAttribute('data-sched-filter') === filterValue;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        const liveEl = document.getElementById('sdScheduleLiveRegion');
        if (liveEl) {
            liveEl.textContent = visibleCount === 1
                ? 'Showing 1 scheduled class.'
                : 'Showing ' + visibleCount + ' scheduled classes.';
        }
    }

    function parseIsoDate(dateKey) {
        const parts = String(dateKey || '').split('-').map(Number);
        if (parts.length !== 3 || parts.some(Number.isNaN)) return new Date();
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    function toIsoKey(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function shiftDateKey(dateKey, deltaDays) {
        const d = parseIsoDate(dateKey);
        d.setDate(d.getDate() + deltaDays);
        return toIsoKey(d);
    }

    async function loadMonthData(year, month) {
        const cacheKey = year + '-' + month;
        if (monthCache.has(cacheKey)) {
            return monthCache.get(cacheKey);
        }
        const url = calendarEndpoint + '?year=' + encodeURIComponent(year) + '&month=' + encodeURIComponent(month);
        const resp = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const json = await resp.json();
        monthCache.set(cacheKey, json);
        return json;
    }

    function renderDaySkeleton() {
        return '<div class="ds-skeleton-stack" aria-busy="true" aria-label="Loading day summary">' +
            '<div class="ds-skeleton-card"><div class="ds-skeleton-line is-title"></div><div class="ds-skeleton-line is-sub"></div><div class="ds-skeleton-line is-full"></div></div>' +
            '<div class="ds-skeleton-card"><div class="ds-skeleton-line is-title"></div><div class="ds-skeleton-line is-sub"></div></div>' +
        '</div>';
    }

    function renderDayInspector(dateKey) {
        activeDateKey = dateKey;
        const dateObj = parseIsoDate(dateKey);
        const titleEl = document.getElementById('sdDayModalTitle');
        const summaryEl = document.getElementById('sdDayModalSummary');
        const bodyEl = document.getElementById('sdDayModalBody');
        if (!titleEl || !summaryEl || !bodyEl) return;

        titleEl.textContent = dateObj.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        summaryEl.innerHTML = '';
        bodyEl.innerHTML = renderDaySkeleton();

        loadMonthData(dateObj.getFullYear(), dateObj.getMonth() + 1)
            .then(function(payload) {
                if (activeDateKey !== dateKey) return;
                const records = (payload.attendance && payload.attendance[dateKey]) ? payload.attendance[dateKey] : [];
                const events = (payload.events && payload.events[dateKey]) ? payload.events[dateKey] : [];

                const presentCount = records.filter(function(r) { return !r.excused && r.status_lower === 'present'; }).length;
                const lateCount = records.filter(function(r) { return !r.excused && r.status_lower === 'late'; }).length;
                const excusedCount = records.filter(function(r) { return Boolean(r.excused) || r.status_lower === 'excused'; }).length;
                const absentCount = records.filter(function(r) { return !r.excused && r.status_lower === 'absent'; }).length;

                let chips = '<span class="day-summary-chip chip-count">' + records.length + (records.length === 1 ? ' Class' : ' Classes') + '</span>';
                if (presentCount) chips += '<span class="day-summary-chip chip-present">' + presentCount + ' Present</span>';
                if (lateCount) chips += '<span class="day-summary-chip chip-late">' + lateCount + ' Late</span>';
                if (excusedCount) chips += '<span class="day-summary-chip chip-excused">' + excusedCount + ' Excused</span>';
                if (absentCount) chips += '<span class="day-summary-chip chip-absent">' + absentCount + ' Absent</span>';
                if (!records.length && !events.length) chips += '<span class="day-summary-chip chip-empty">No activity</span>';
                summaryEl.innerHTML = chips;

                let html = '';
                if (events.length) {
                    html += '<div class="day-section-header"><span class="day-section-label">School Events &amp; Holidays</span><span class="day-section-badge">' + events.length + '</span></div>';
                    html += '<div class="d-grid gap-2 mb-3">';
                    events.forEach(function(evt) {
                        html += '<div class="att-detail-card">' +
                            '<div class="att-card-subject-title">' + escapeHtml(evt.title) + '</div>' +
                            '<div class="att-card-meta">' +
                                (evt.type ? '<span class="att-code-pill sd-mono">' + escapeHtml(evt.type.toUpperCase()) + '</span>' : '') +
                                (evt.time ? '<span class="att-instructor-pill">' + escapeHtml(evt.time) + '</span>' : '') +
                                (evt.location ? '<span class="att-instructor-pill">' + escapeHtml(evt.location) + '</span>' : '') +
                            '</div>' +
                            (evt.description ? '<p class="mb-0 mt-2" style="font-size:0.82rem;color:var(--sdc-text-soft);">' + escapeHtml(evt.description) + '</p>' : '') +
                        '</div>';
                    });
                    html += '</div>';
                }

                if (records.length) {
                    html += '<div class="day-section-header"><span class="day-section-label">Attendance Records</span><span class="day-section-badge">' + records.length + '</span></div>';
                    html += '<div class="d-grid gap-2">';
                    records.forEach(function(rec) {
                        const isExcused = Boolean(rec.excused) || rec.status_lower === 'excused';
                        const tone = isExcused ? 'excused' : (rec.status_lower === 'present' ? 'present' : (rec.status_lower === 'late' ? 'late' : 'absent'));
                        const excuseHref = rec.id ? (excusesCreateBase + '/' + encodeURIComponent(rec.id)) : excusesGeneralUrl;
                        html += '<div class="att-detail-card status-border-' + tone + '">' +
                            '<div class="att-card-header">' +
                                '<div>' +
                                    '<div class="att-card-subject-title">' + escapeHtml(rec.subject) + '</div>' +
                                    '<div class="att-card-meta">' +
                                        (rec.code ? '<span class="att-code-pill sd-mono">' + escapeHtml(rec.code) + '</span>' : '') +
                                        '<span class="att-instructor-pill">' + escapeHtml(rec.instructor) + '</span>' +
                                    '</div>' +
                                '</div>' +
                                '<span class="att-status-badge ' + tone + '">' + escapeHtml(isExcused ? 'Excused' : rec.status) + '</span>' +
                            '</div>' +
                            '<div class="att-info-grid">' +
                                '<div><div class="att-grid-col-label">Schedule</div><div class="att-grid-col-val sd-tabular">' + escapeHtml(rec.schedule) + '</div></div>' +
                                '<div><div class="att-grid-col-label">Clock In</div><div class="att-grid-col-val sd-tabular">' + escapeHtml(rec.clock_in) + '</div></div>' +
                            '</div>' +
                            (tone === 'absent' && !rec.remarks ? '<a href="' + escapeHtml(excuseHref) + '" class="day-card-excuse-btn"><span>Submit excuse letter</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>' : '') +
                        '</div>';
                    });
                    html += '</div>';
                } else if (!events.length) {
                    html += '<div class="day-empty-card">' +
                        '<div class="day-empty-icon" aria-hidden="true"><i class="bi bi-calendar2-check"></i></div>' +
                        '<p class="mb-1 fw-bold" style="color:var(--sdc-text);">No records for this date</p>' +
                        '<p class="mb-0" style="font-size:0.82rem;color:var(--sdc-muted);">No attendance sessions or school events were recorded on ' + escapeHtml(dateKey) + '.</p>' +
                    '</div>';
                }

                bodyEl.innerHTML = html;
            })
            .catch(function() {
                if (activeDateKey !== dateKey) return;
                bodyEl.innerHTML = '<div class="day-empty-card">' +
                    '<p class="mb-2 fw-bold" style="color:var(--sdc-text);">Could not load day details</p>' +
                    '<button type="button" class="day-nav-today-btn" id="sdDayRetryBtn">Try again</button>' +
                '</div>';
                const retryBtn = document.getElementById('sdDayRetryBtn');
                if (retryBtn) {
                    retryBtn.addEventListener('click', function() {
                        monthCache.clear();
                        renderDayInspector(dateKey);
                    });
                }
            });
    }

    function openDayInspector(dateKey) {
        const modalEl = document.getElementById('daySummaryModal');
        if (!modalEl) return;
        renderDayInspector(dateKey);
        if (window.bootstrap && window.bootstrap.Modal) {
            dayModalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            dayModalInstance.show();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        tick();

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

        document.querySelectorAll('[data-sched-filter]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                applyScheduleFilter(btn.getAttribute('data-sched-filter') || 'all');
            });
        });

        const resetSchedBtn = document.getElementById('sdResetSchedFilterBtn');
        if (resetSchedBtn) {
            resetSchedBtn.addEventListener('click', function() {
                applyScheduleFilter('all');
            });
        }

        const toggleSubsBtn = document.getElementById('sdToggleSubjectsBtn');
        if (toggleSubsBtn) {
            toggleSubsBtn.addEventListener('click', function() {
                const expanded = toggleSubsBtn.getAttribute('aria-expanded') === 'true';
                document.querySelectorAll('#sdSubjectList .sd-subject-row.is-collapsible').forEach(function(el) {
                    el.hidden = expanded;
                });
                toggleSubsBtn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                const total = toggleSubsBtn.getAttribute('data-total-count') || '';
                toggleSubsBtn.textContent = expanded ? ('Show all ' + total + ' subjects') : 'Show fewer subjects';
            });
        }

        document.querySelectorAll('.sd-pulse-day[data-date-key]').forEach(function(dayBtn) {
            dayBtn.addEventListener('click', function() {
                const key = dayBtn.getAttribute('data-date-key');
                if (key) openDayInspector(key);
            });
        });

        const prevBtn = document.getElementById('sdDayPrevBtn');
        const nextBtn = document.getElementById('sdDayNextBtn');
        const todayBtn = document.getElementById('sdDayTodayBtn');
        if (prevBtn) prevBtn.addEventListener('click', function() {
            if (activeDateKey) renderDayInspector(shiftDateKey(activeDateKey, -1));
        });
        if (nextBtn) nextBtn.addEventListener('click', function() {
            if (activeDateKey) renderDayInspector(shiftDateKey(activeDateKey, 1));
        });
        if (todayBtn) todayBtn.addEventListener('click', function() {
            renderDayInspector(toIsoKey(new Date()));
        });

        const modalEl = document.getElementById('daySummaryModal');
        if (modalEl) {
            modalEl.addEventListener('keydown', function(e) {
                if (!activeDateKey) return;
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    renderDayInspector(shiftDateKey(activeDateKey, -1));
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    renderDayInspector(shiftDateKey(activeDateKey, 1));
                }
            });
        }
    });

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) tick();
    });
    const scheduleNextTick = () => {
        const delay = 60000 - (Date.now() % 60000) + 120;
        setTimeout(() => {
            tick();
            scheduleNextTick();
        }, delay);
    };
    scheduleNextTick();
})();
</script>
@endsection
