@extends('layouts.mobile-app')

@section('title', 'Home - Smart Attendance')

@section('content')
@php
    $studentUser = auth()->user();
    $todaySchedCollection = collect($todaySchedule ?? []);
    $todayClassCount = $todaySchedCollection->count();
    $completedTodayCount = $todaySchedCollection->where('status', 'completed')->count();
    $liveClassItem = $todaySchedCollection->firstWhere('status', 'ongoing');
    $nextClassItem = $todaySchedCollection->firstWhere('status', 'upcoming');
    $featuredClass = $liveClassItem ?? $nextClassItem;
    $featuredRoom = trim((string) ($featuredClass?->schedule?->room ?? ''));
    $featuredInstructor = trim((string) ($featuredClass?->subject?->instructorUser?->name ?? $featuredClass?->subject?->instructor ?? ''));

    $rateVal = $overallRate ?? null;
    $rateTone = $rateVal === null ? 'empty' : ($rateVal >= 85 ? 'good' : ($rateVal >= 75 ? 'warning' : 'danger'));
    $rateTier = $rateVal === null ? 'New' : ($rateVal >= 85 ? 'Good Standing' : ($rateVal >= 75 ? 'Fair Standing' : 'Needs Review'));
    $gaugeRadius = 38;
    $gaugeCircumference = round(2 * pi() * $gaugeRadius, 2);
    $gaugePct = $rateVal === null ? 0 : min(100, max(0, (float) $rateVal));
    $gaugeOffset = round($gaugeCircumference * (1 - ($gaugePct / 100)), 2);

    $greetHour = now()->hour;
    $greetIcon = $greetHour < 12 ? 'bi-brightness-alt-high-fill' : ($greetHour < 17 ? 'bi-sun-fill' : 'bi-moon-stars-fill');
    $weeklyPulseCollection = collect($weeklyPulse ?? []);
    $subjectStatsCollection = collect($subjectStats ?? []);
    $pendingExcuses = (int) ($pendingExcusesCount ?? 0);
    $totalPresentCount = (int) ($totalPresent ?? 0);
    $totalLateCount = (int) ($totalLate ?? 0);
    $totalAbsentCount = (int) ($totalAbsent ?? 0);
    $totalSessionsCount = $totalPresentCount + $totalLateCount + $totalAbsentCount;
    $presentPct = $totalSessionsCount > 0 ? round(($totalPresentCount / $totalSessionsCount) * 100, 1) : 0;
    $latePct = $totalSessionsCount > 0 ? round(($totalLateCount / $totalSessionsCount) * 100, 1) : 0;
    $absentPct = $totalSessionsCount > 0 ? max(0, round(100 - $presentPct - $latePct, 1)) : 0;
    $peerAvailable = app(\App\Services\PeerBiometricAttendanceService::class)->isAvailable();

    $metaParts = [];
    if (!empty($studentUser?->student_number)) {
        $metaParts[] = ['label' => 'ID ' . $studentUser->student_number, 'mono' => true];
    }
    if (!empty($studentUser?->course)) {
        $metaParts[] = ['label' => $studentUser->course, 'mono' => false];
    }
    if (!empty($studentUser?->year_level)) {
        $metaParts[] = ['label' => 'Yr ' . $studentUser->year_level, 'mono' => false];
    }
    if (!empty($studentUser?->section)) {
        $metaParts[] = ['label' => 'Sec ' . $studentUser->section, 'mono' => false];
    }
@endphp

<div class="sd-mobile-cockpit mobile-home">
    {{-- ── Open Mobile Editorial Masthead & Primary Check-In Dock ──────── --}}
    <header class="sd-masthead" aria-label="Student overview">
        <div class="sd-identity-cluster">
            <div class="sd-avatar-ring">
                <img src="{{ $studentUser->profile_photo_url_with_version }}" alt="{{ $studentUser->name }}">
                <span class="sd-avatar-live-dot" aria-hidden="true"></span>
            </div>
            <div class="sd-identity-copy">
                <div class="sd-kicker-line">
                    <span class="sd-kicker-greeting">
                        <i class="bi {{ $greetIcon }}" aria-hidden="true"></i>
                        <span>Good {{ $greeting }}</span>
                    </span>
                    <span class="sd-kicker-sep" aria-hidden="true">&middot;</span>
                    <span id="mobileStudentClock" class="sd-kicker-clock sd-mono">{{ now()->format('g:i A') }}</span>
                </div>
                <h2 class="sd-student-name">{{ $studentUser?->name }}</h2>
                <div class="sd-meta-ribbon">
                    @foreach($metaParts as $metaPart)
                        @if(!$loop->first)<span class="sd-meta-sep" aria-hidden="true">&middot;</span>@endif
                        <span class="sd-meta-item {{ $metaPart['mono'] ? 'sd-mono is-id' : '' }}">{{ $metaPart['label'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="sd-masthead-actions">
            <button type="button" id="quickActionScanBtn" data-action="open-scanner" class="sd-cta-primary" aria-label="Scan QR Code">
                <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                <span>Scan QR</span>
            </button>
            <a href="{{ route('mobile.scan', ['open_code' => 1]) }}" class="sd-cta-secondary">
                <i class="bi bi-123" aria-hidden="true"></i>
                <span>Enter PIN</span>
            </a>
        </div>
    </header>

    @include('partials.missing-section-notice', [
        'message' => 'Contact the registrar or school administrator to add your section and timetable.',
    ])

    @if(isset($activeWarnings) && $activeWarnings->count() > 0)
    <section class="sd-warning-panel" role="region" aria-label="Attendance Warnings">
        <div class="sd-warning-top">
            <div class="sd-warning-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div>
                <h2 class="sd-warning-title">Action Required: Attendance Warning</h2>
                <p class="sd-warning-sub">You have {{ $activeWarnings->count() }} active {{ \Illuminate\Support\Str::plural('warning', $activeWarnings->count()) }}.</p>
            </div>
        </div>
        <div class="sd-warning-list">
            @foreach($activeWarnings->take(2) as $warning)
            <div class="sd-warning-card">
                <div class="sd-warning-card-head">
                    <span class="sd-warning-code sd-mono">{{ $warning->subject_code }}</span>
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
    @endif

    {{-- ── Hero Stage + Attendance Standing Telemetry ───────────────────── --}}
    <div class="sd-hero-grid">
        <section id="mobileSpotlight"
                 class="sd-stage {{ $liveClassItem ? 'is-live' : ($nextClassItem ? 'is-next' : 'is-idle') }}"
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
                        <span id="mobileSpotlightTimer" class="sd-stage-timer sd-mono" aria-live="off"></span>
                        <span class="sd-stage-code sd-mono">{{ $featuredClass->subject->code }}</span>
                    </span>
                @else
                    <x-badge :type="$todayStatus === 'present' ? 'present' : ($todayStatus === 'absent' ? 'absent' : 'pending')" size="sm">
                        {{ $todayStatusText }}
                    </x-badge>
                @endif
            </div>

            <div class="sd-stage-body">
                <h3 class="sd-stage-title">
                    {{ $featuredClass?->subject?->name ?? ($todayClassCount ? 'All classes completed today' : 'Free schedule today') }}
                </h3>
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
                                <span id="mobileSpotlightProgressFill" class="sd-session-meter-fill" style="width: {{ $spotProgressPct }}%;"></span>
                            </div>
                        </div>
                    @endif
                @else
                    <p class="sd-stage-note">
                        @if($checkInTime)
                            Checked in at {{ $checkInTime }}.
                        @else
                            {{ $todayStatusSubtext }}
                        @endif
                    </p>
                @endif
            </div>

            <div class="sd-stage-foot">
                <a href="{{ route('student.schedule') }}" class="sd-stage-link">
                    View schedule <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
                <span class="sd-day-progress-pill sd-tabular">
                    <strong>{{ $completedTodayCount }}/{{ $todayClassCount }}</strong> done today
                </span>
            </div>
        </section>

        <section class="sd-telemetry" aria-label="Attendance standing">
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
                        <h3 class="sd-standing-title">Attendance Standing</h3>
                        <span class="sd-standing-tier is-{{ $rateTone }}">{{ $rateTier }}</span>
                    </div>
                    <p class="sd-standing-desc sd-tabular">
                        @if($weekTotal > 0)
                            This week: <strong>{{ $weekPresent }}/{{ $weekTotal }}</strong> present ({{ $attendanceRate }}%)
                        @else
                            No attendance records yet this week
                        @endif
                    </p>
                    <div class="sd-standing-badges">
                        @if(($streakCount ?? 0) > 0)
                            <span class="sd-telemetry-chip is-streak sd-tabular">
                                <i class="bi bi-fire" aria-hidden="true"></i> {{ $streakCount }} {{ $streakCount === 1 ? 'Day' : 'Days' }}
                            </span>
                        @endif
                        @if($pendingExcuses > 0)
                            <a href="{{ route('excuses') }}" class="sd-telemetry-chip is-pending sd-tabular">
                                <i class="bi bi-hourglass-split" aria-hidden="true"></i> {{ $pendingExcuses }} Pending
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="sd-breakdown-strip">
                <div class="sd-breakdown-bar" role="img" aria-label="Session distribution: {{ $totalPresentCount }} Present, {{ $totalLateCount }} Late, {{ $totalAbsentCount }} Absent">
                    @if($totalSessionsCount > 0)
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
                    <a href="{{ route('student.calendar') }}" class="sd-panel-link">Calendar <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="sd-pulse-grid">
                    @foreach($weeklyPulseCollection as $pulse)
                        <a href="{{ route('student.calendar') }}"
                           class="sd-pulse-day is-status-{{ $pulse->status }} {{ $pulse->is_today ? 'is-today' : '' }}"
                           aria-label="{{ $pulse->label }}">
                            <span class="sd-pulse-day-short">{{ $pulse->day_short }}</span>
                            <span class="sd-pulse-day-num sd-tabular">{{ $pulse->day_num }}</span>
                            <span class="sd-pulse-pip" aria-hidden="true"></span>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </section>
    </div>

    {{-- ── Swipeable Utility Rail ────────────────────────────────────────── --}}
    <nav class="sd-utility-rail" aria-label="Quick Actions">
        <a href="{{ route('student.schedule') }}" class="sd-util-item">
            <i class="bi bi-calendar2-week" aria-hidden="true"></i>
            <span class="sd-util-label">Schedule</span>
            @if($todayClassCount > 0)<span class="sd-util-count sd-mono">{{ $todayClassCount }}</span>@endif
        </a>
        <a href="{{ route('mobile.history') }}" class="sd-util-item">
            <i class="bi bi-clock-history" aria-hidden="true"></i>
            <span class="sd-util-label">History</span>
        </a>
        <a href="{{ route('excuses.create_general') }}" class="sd-util-item">
            <i class="bi bi-file-earmark-medical" aria-hidden="true"></i>
            <span class="sd-util-label">Submit Excuse</span>
            @if($pendingExcuses > 0)<span class="sd-util-count is-amber sd-mono">{{ $pendingExcuses }}</span>@endif
        </a>
        <a href="{{ route('student.classes') }}" class="sd-util-item">
            <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
            <span class="sd-util-label">My Classes</span>
            <span class="sd-util-count sd-mono">{{ $subjectStatsCollection->count() }}</span>
        </a>
        @if(!empty($hasActiveAssistantAssignment))
        <a href="{{ route('student-assistant.classes') }}" class="sd-util-item">
            <i class="bi bi-qr-code" aria-hidden="true"></i>
            <span class="sd-util-label">Student Assistant</span>
        </a>
        @endif
        @if($peerAvailable)
        <a href="{{ route('peer-snap.index') }}" class="sd-util-item" aria-label="Check in for a Classmate">
            <i class="bi bi-person-bounding-box" aria-hidden="true"></i>
            <span class="sd-util-label">Classmate Check-In</span>
        </a>
        @endif
    </nav>

    {{-- ── Today's Classes + Recent Activity + Subjects + Announcements ─── --}}
    <div class="sd-workspace">
        <div class="sd-col-main">
            @if($todaySchedCollection->isNotEmpty())
            <section class="sd-panel" aria-label="Today's Schedule">
                <header class="sd-panel-head">
                    <div class="sd-panel-title-group">
                        <h3 class="sd-panel-title">Today's Classes</h3>
                        <span class="sd-panel-meta">{{ now()->format('l, M j') }}</span>
                    </div>
                    <a href="{{ route('student.schedule') }}" class="sd-panel-link">Full <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </header>
                <div class="sd-timeline">
                    @foreach($todaySchedCollection as $item)
                        @php
                            $statusColor = $item->status === 'completed' ? '#34d399' : ($item->status === 'ongoing' ? '#fbbf24' : ($item->status === 'missed' ? '#fb7185' : '#D4AF37'));
                            $roomName = trim((string) ($item->schedule?->room ?? ''));
                        @endphp
                        <div class="sd-timeline-row is-status-{{ $item->status }}" style="--tl-color: {{ $statusColor }};">
                            <div class="sd-tl-timecol sd-tabular">
                                <strong>{{ $item->start_time->format('g:i A') }}</strong>
                                <span>{{ $item->end_time->format('g:i A') }}</span>
                            </div>
                            <div class="sd-tl-rail" aria-hidden="true"><span class="sd-tl-node"></span></div>
                            <div class="sd-tl-content">
                                <div class="sd-tl-subject">{{ $item->subject->name }}</div>
                                <div class="sd-tl-meta sd-tabular">
                                    <span class="sd-tl-timepill sd-mono">{{ $item->start_time->format('g:i A') }} &ndash; {{ $item->end_time->format('g:i A') }}</span>
                                    <span class="sd-code-tag sd-mono">{{ $item->subject->code }}</span>
                                    @if($roomName !== '' && strtoupper($roomName) !== 'TBA')
                                        <span><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $roomName }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="sd-tl-actions">
                                @if($item->status === 'completed') <x-badge type="present" size="sm">Present</x-badge>
                                @elseif($item->status === 'ongoing') <x-badge type="late" size="sm" pulse>Live</x-badge>
                                @elseif($item->status === 'missed') <x-badge type="absent" size="sm">Missed</x-badge>
                                @else <x-badge type="info" size="sm">Soon</x-badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif

            <section class="sd-panel" aria-label="Recent Activity">
                <header class="sd-panel-head">
                    <div class="sd-panel-title-group">
                        <h3 class="sd-panel-title">Recent Activity</h3>
                        <span class="sd-panel-meta">Latest check-ins</span>
                    </div>
                    <a href="{{ route('mobile.history') }}" class="sd-panel-link">History <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </header>
                <div class="sd-ledger">
                    @forelse($recentActivity as $date => $activities)
                        @foreach($activities as $activity)
                            @php
                                $actTone = $activity['status'] === 'success' ? 'present' : ($activity['status'] === 'warning' ? 'late' : 'absent');
                            @endphp
                            <div class="sd-ledger-row">
                                <span class="sd-ledger-dot is-{{ $actTone }}" aria-hidden="true"></span>
                                <div class="sd-ledger-copy">
                                    <div class="sd-ledger-title">{{ $activity['subtitle'] ?? $activity['title'] }}</div>
                                    <div class="sd-ledger-sub">{{ $date }} &middot; {{ $activity['title'] }}</div>
                                </div>
                                @if(!empty($activity['time']))
                                    <span class="sd-recent-time sd-tabular">{{ $activity['time'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    @empty
                        <div class="sd-inline-empty is-quiet">
                            <span>No recent activity yet. Your attendance logs appear here after check-in.</span>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="sd-col-aside">
            @if($subjectStatsCollection->isNotEmpty())
            <section class="sd-panel" aria-label="Subject Attendance">
                <header class="sd-panel-head">
                    <div class="sd-panel-title-group">
                        <h3 class="sd-panel-title">Subject Attendance</h3>
                        <span class="sd-panel-meta">{{ $subjectStatsCollection->count() }} {{ \Illuminate\Support\Str::plural('subject', $subjectStatsCollection->count()) }}</span>
                    </div>
                    <a href="{{ route('student.classes') }}" class="sd-panel-link">Classes <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </header>
                <div class="sd-subject-list">
                    @foreach($subjectStatsCollection->take(5) as $sub)
                        @php
                            $hasRecords = $sub->total > 0;
                            $subTone = !$hasRecords ? 'is-new' : ($sub->rate >= 85 ? 'is-good' : ($sub->rate >= 75 ? 'is-warn' : 'is-risk'));
                        @endphp
                        <a href="{{ route('student.classes') }}" class="sd-subject-row">
                            <div class="sd-subject-row-top">
                                <div class="sd-subject-id">
                                    <span class="sd-code-tag sd-mono">{{ $sub->code }}</span>
                                    <span class="sd-subject-name">{{ $sub->name }}</span>
                                </div>
                                <span class="sd-subject-rate sd-tabular {{ $subTone }}">{{ $hasRecords ? $sub->rate.'%' : 'New' }}</span>
                            </div>
                            <div class="sd-meter-track" aria-hidden="true">
                                <span class="sd-meter-fill {{ $subTone }}" style="width: {{ $hasRecords ? min(100, max(3, $sub->rate)) : 0 }}%;"></span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
            @endif

            @if(isset($announcements) && $announcements->isNotEmpty())
            <section class="sd-panel" aria-label="Campus Announcements">
                <header class="sd-panel-head">
                    <div class="sd-panel-title-group">
                        <h3 class="sd-panel-title">Announcements</h3>
                        <span class="sd-panel-meta">Latest campus notices</span>
                    </div>
                </header>
                <div class="sd-ann-stack">
                    @foreach($announcements as $announcement)
                        <article class="sd-ann-card">
                            <div class="sd-ann-head">
                                <h4 class="sd-ann-title">{{ $announcement->title }}</h4>
                                <span class="sd-code-tag sd-mono">{{ $announcement->target_audience ?? 'All' }}</span>
                            </div>
                            <p class="sd-ann-copy is-clamped">{{ trim((string) $announcement->content) }}</p>
                            <div class="sd-ann-meta">
                                <span><i class="bi bi-clock" aria-hidden="true"></i> {{ ($announcement->scheduled_for ?? $announcement->created_at)->diffForHumans() }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script @cspNonce>
(function() {
    function formatRemaining(diffMs) {
        const mins = Math.max(1, Math.ceil(diffMs / 60000));
        if (mins < 60) return mins + 'm';
        const h = Math.floor(mins / 60);
        const rem = mins % 60;
        return rem > 0 ? (h + 'h ' + rem + 'm') : (h + 'h');
    }
    function tick() {
        if (document.hidden) return;
        const now = new Date();
        let h = now.getHours();
        const m = now.getMinutes();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        const clock = document.getElementById('mobileStudentClock');
        if (clock) clock.textContent = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;

        const spot = document.getElementById('mobileSpotlight');
        const timer = document.getElementById('mobileSpotlightTimer');
        const progressFill = document.getElementById('mobileSpotlightProgressFill');
        if (spot && timer) {
            const startMs = Number(spot.getAttribute('data-start-ms') || 0);
            const endMs = Number(spot.getAttribute('data-end-ms') || 0);
            const nowMs = now.getTime();
            if (startMs && endMs) {
                if (nowMs >= startMs && nowMs <= endMs) {
                    timer.textContent = formatRemaining(endMs - nowMs) + ' left';
                    if (progressFill) {
                        const pct = Math.min(100, Math.max(4, Math.round(((nowMs - startMs) / Math.max(1, endMs - startMs)) * 100)));
                        progressFill.style.width = pct + '%';
                    }
                } else if (nowMs < startMs) {
                    timer.textContent = 'Starts in ' + formatRemaining(startMs - nowMs);
                } else {
                    timer.textContent = 'Ended';
                    if (progressFill) progressFill.style.width = '100%';
                }
            }
        }
    }
    document.addEventListener('DOMContentLoaded', tick);
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) tick();
    });
    setInterval(tick, 1000);
})();
</script>
@endpush
