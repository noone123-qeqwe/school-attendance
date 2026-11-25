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
    $rateTone = $rateVal === null ? 'rate-empty' : ($rateVal >= 85 ? 'rate-good' : ($rateVal >= 75 ? 'rate-warning' : 'rate-danger'));
    $rateTier = $rateVal === null ? 'New' : ($rateVal >= 85 ? 'Good' : ($rateVal >= 75 ? 'Fair' : 'At Risk'));
    $gaugeRadius = 34;
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
@endphp

<div class="sd-mobile-cockpit mobile-home">
    @if(blank($studentUser?->section))
    <div role="status" class="sd-notice-banner">
        <span class="sd-notice-icon" aria-hidden="true"><i class="bi bi-info-circle-fill"></i></span>
        <div class="sd-notice-copy">
            <strong>Class section not assigned</strong>
            <span>Contact the registrar or school administrator to add your section and timetable.</span>
        </div>
    </div>
    @endif

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

    {{-- ── Identity & Primary Check-In Pass ─────────────────────────────── --}}
    <section class="sd-command-bar" aria-label="Student overview">
        <div class="sd-identity-cluster">
            <div class="sd-avatar-ring">
                <img src="{{ $studentUser->profile_photo_url_with_version }}" alt="{{ $studentUser->name }}">
                <span class="sd-avatar-live-dot" aria-hidden="true"></span>
            </div>
            <div class="sd-identity-copy">
                <div class="sd-kicker-row">
                    <span class="sd-greeting-pill">
                        <i class="bi {{ $greetIcon }}" aria-hidden="true"></i>
                        <span>Good {{ $greeting }}</span>
                    </span>
                    <span class="sd-clock-pill sd-mono">
                        <i class="bi bi-clock-fill" aria-hidden="true"></i>
                        <span id="mobileStudentClock">{{ now()->format('g:i A') }}</span>
                    </span>
                </div>
                <h2 class="sd-student-name">{{ $studentUser?->name }}</h2>
                <div class="sd-meta-ribbon">
                    @if(!empty($studentUser?->student_number))
                        <span class="sd-meta-tag is-id sd-mono">ID {{ $studentUser->student_number }}</span>
                    @endif
                    @if(!empty($studentUser?->course))
                        <span class="sd-meta-tag">{{ $studentUser->course }}</span>
                    @endif
                    @if(!empty($studentUser?->year_level))
                        <span class="sd-meta-tag">Yr {{ $studentUser->year_level }}</span>
                    @endif
                    @if(!empty($studentUser?->section))
                        <span class="sd-meta-tag">Sec {{ $studentUser->section }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="sd-command-actions">
            <button type="button" id="quickActionScanBtn" data-action="open-scanner" class="sd-cta-primary" aria-label="Scan QR Code">
                <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                <span>Scan QR</span>
            </button>
            <a href="{{ route('mobile.scan', ['open_code' => 1]) }}" class="sd-cta-secondary">
                <i class="bi bi-123" aria-hidden="true"></i>
                <span>Enter PIN</span>
            </a>
        </div>
    </section>

    {{-- ── Bento Hero: Live Focus Stage + Standing Ring & 7-Day Pulse ───── --}}
    <div class="sd-bento-hero">
        <section id="mobileSpotlight"
                 class="sd-focus-stage {{ $liveClassItem ? 'is-live' : ($nextClassItem ? 'is-next' : 'is-idle') }}"
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
                        <span id="mobileSpotlightTimer" class="sd-focus-timer sd-mono" aria-live="off"></span>
                        <span class="sd-focus-code sd-mono">{{ $featuredClass->subject->code }}</span>
                    </span>
                @else
                    <x-badge :type="$todayStatus === 'present' ? 'present' : ($todayStatus === 'absent' ? 'absent' : 'pending')" size="sm">
                        {{ $todayStatusText }}
                    </x-badge>
                @endif
            </div>

            <div class="sd-focus-body">
                <h3 class="sd-focus-title">
                    {{ $featuredClass?->subject?->name ?? ($todayClassCount ? 'All classes completed today' : 'Free schedule today') }}
                </h3>
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
                @else
                    <p class="sd-focus-note">
                        @if($checkInTime)
                            Checked in at {{ $checkInTime }}.
                        @else
                            {{ $todayStatusSubtext }}
                        @endif
                    </p>
                @endif
            </div>

            <div class="sd-focus-foot">
                <a href="{{ route('student.schedule') }}" class="sd-focus-link">
                    View schedule <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
                <span class="sd-day-progress-pill sd-tabular">
                    <strong>{{ $completedTodayCount }}/{{ $todayClassCount }}</strong> done today
                </span>
            </div>
        </section>

        <section class="sd-standing-card" aria-label="Attendance standing">
            <div class="sd-standing-top">
                <div class="sd-gauge-wrap">
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
                        <h3 class="sd-standing-title">Attendance Standing</h3>
                        <span class="sd-standing-tier is-{{ $rateTone }}">{{ $rateTier }}</span>
                    </div>
                    <p class="sd-standing-desc">
                        @if($weekTotal > 0)
                            This week: {{ $weekPresent }}/{{ $weekTotal }} present ({{ $attendanceRate }}%)
                        @else
                            No attendance records yet this week
                        @endif
                    </p>
                    <div class="sd-standing-chips">
                        @if(($streakCount ?? 0) > 0)
                            <span class="sd-mini-chip is-streak sd-tabular">
                                <i class="bi bi-fire" aria-hidden="true"></i> {{ $streakCount }} {{ $streakCount === 1 ? 'Day' : 'Days' }}
                            </span>
                        @endif
                        @if($pendingExcuses > 0)
                            <a href="{{ route('excuses') }}" class="sd-mini-chip is-pending sd-tabular">
                                <i class="bi bi-hourglass-split" aria-hidden="true"></i> {{ $pendingExcuses }} Pending
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            @if($weeklyPulseCollection->isNotEmpty())
            <div class="sd-pulse-strip-wrap">
                <div class="sd-pulse-strip-head">
                    <span class="sd-pulse-strip-label">7-Day Pulse</span>
                    <a href="{{ route('student.calendar') }}" class="sd-pulse-strip-hint" style="text-decoration:none;">Calendar</a>
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

    {{-- ── KPI Bento ─────────────────────────────────────────────────────── --}}
    <div class="sd-kpi-grid">
        <a href="{{ route('mobile.history') }}" class="sd-kpi-trigger">
            <div class="sd-kpi-tile" data-accent="success">
                <div class="sd-kpi-tile-top">
                    <span class="sd-kpi-badge" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></span>
                    <span class="sd-kpi-lbl">Present</span>
                </div>
                <div class="sd-kpi-val sd-tabular">{{ $totalPresentCount }}</div>
                <div class="sd-kpi-bar" aria-hidden="true"><span class="sd-kpi-bar-fill" style="width: {{ $totalSessionsCount > 0 ? round(($totalPresentCount / $totalSessionsCount) * 100) : 0 }}%;"></span></div>
            </div>
        </a>
        <a href="{{ route('mobile.history') }}" class="sd-kpi-trigger">
            <div class="sd-kpi-tile" data-accent="warning">
                <div class="sd-kpi-tile-top">
                    <span class="sd-kpi-badge" aria-hidden="true"><i class="bi bi-clock-fill"></i></span>
                    <span class="sd-kpi-lbl">Late</span>
                </div>
                <div class="sd-kpi-val sd-tabular">{{ $totalLateCount }}</div>
                <div class="sd-kpi-bar" aria-hidden="true"><span class="sd-kpi-bar-fill" style="width: {{ $totalSessionsCount > 0 ? round(($totalLateCount / $totalSessionsCount) * 100) : 0 }}%;"></span></div>
            </div>
        </a>
        <a href="{{ route('mobile.history') }}" class="sd-kpi-trigger">
            <div class="sd-kpi-tile" data-accent="danger">
                <div class="sd-kpi-tile-top">
                    <span class="sd-kpi-badge" aria-hidden="true"><i class="bi bi-x-circle-fill"></i></span>
                    <span class="sd-kpi-lbl">Absent</span>
                </div>
                <div class="sd-kpi-val sd-tabular">{{ $totalAbsentCount }}</div>
                <div class="sd-kpi-bar" aria-hidden="true"><span class="sd-kpi-bar-fill" style="width: {{ $totalSessionsCount > 0 ? round(($totalAbsentCount / $totalSessionsCount) * 100) : 0 }}%;"></span></div>
            </div>
        </a>
        <a href="{{ route('student.classes') }}" class="sd-kpi-trigger">
            <div class="sd-kpi-tile" data-accent="gold">
                <div class="sd-kpi-tile-top">
                    <span class="sd-kpi-badge" aria-hidden="true"><i class="bi bi-book-fill"></i></span>
                    <span class="sd-kpi-lbl">Subjects</span>
                </div>
                <div class="sd-kpi-val sd-tabular">{{ $subjectStatsCollection->count() }}</div>
                <div class="sd-kpi-bar" aria-hidden="true"><span class="sd-kpi-bar-fill" style="width: {{ $subjectStatsCollection->count() > 0 ? 100 : 0 }}%;"></span></div>
            </div>
        </a>
    </div>

    {{-- ── Quick Tools ───────────────────────────────────────────────────── --}}
    <nav class="sd-quick-dock" aria-label="Quick Actions">
        <a href="{{ route('student.schedule') }}" class="sd-dock-pill">
            <span class="sd-dock-icon" aria-hidden="true"><i class="bi bi-calendar2-week"></i></span>
            <span class="sd-dock-copy">
                <strong>Schedule</strong>
                <small>Weekly classes</small>
            </span>
        </a>
        <a href="{{ route('mobile.history') }}" class="sd-dock-pill">
            <span class="sd-dock-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
            <span class="sd-dock-copy">
                <strong>History</strong>
                <small>Attendance log</small>
            </span>
        </a>
        <a href="{{ route('excuses.create_general') }}" class="sd-dock-pill">
            <span class="sd-dock-icon" aria-hidden="true"><i class="bi bi-file-earmark-medical"></i></span>
            <span class="sd-dock-copy">
                <strong>Submit Excuse</strong>
                <small>Upload proof</small>
            </span>
        </a>
        <a href="{{ route('student.classes') }}" class="sd-dock-pill">
            <span class="sd-dock-icon" aria-hidden="true"><i class="bi bi-journal-bookmark"></i></span>
            <span class="sd-dock-copy">
                <strong>My Classes</strong>
                <small>Enrolled load</small>
            </span>
        </a>

        @if(!empty($hasActiveAssistantAssignment))
            <a href="{{ route('student-assistant.classes') }}" class="sd-dock-pill is-full">
                <span class="sd-dock-icon" aria-hidden="true"><i class="bi bi-qr-code"></i></span>
                <span class="sd-dock-copy">
                    <strong>Student Assistant</strong>
                    <small>Manage class QR &amp; reports</small>
                </span>
            </a>
        @endif

        @if(app(\App\Services\PeerBiometricAttendanceService::class)->isAvailable())
            <a href="{{ route('peer-snap.index') }}" class="sd-dock-pill is-full" aria-label="Check in for a Classmate">
                <span class="sd-dock-icon" aria-hidden="true"><i class="bi bi-person-bounding-box"></i></span>
                <span class="sd-dock-copy">
                    <strong>Check in for a Classmate</strong>
                    <small>Peer biometric check-in</small>
                </span>
            </a>
        @endif
    </nav>

    {{-- ── Today's Classes + Recent Activity ────────────────────────────── --}}
    <div class="sd-workspace-grid">
        <div class="sd-col-primary">
            @if($todaySchedCollection->isNotEmpty())
            <section class="sd-surface-card" aria-label="Today's Schedule">
                <header class="sd-surface-head">
                    <div class="sd-surface-title-group">
                        <span class="sd-surface-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                        <div>
                            <h3 class="sd-surface-title">Today's Classes</h3>
                            <p class="sd-surface-sub">{{ now()->format('l, M j') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('student.schedule') }}" class="sd-pill-btn">Full</a>
                </header>
                <div class="sd-surface-body">
                    <div class="sd-timeline">
                        @foreach($todaySchedCollection as $item)
                            @php
                                $statusColor = $item->status === 'completed' ? '#34d399' : ($item->status === 'ongoing' ? '#fbbf24' : ($item->status === 'missed' ? '#ff6b7a' : '#e48fa0'));
                                $roomName = trim((string) ($item->schedule?->room ?? ''));
                            @endphp
                            <div class="sd-timeline-row is-status-{{ $item->status }}" style="--tl-color: {{ $statusColor }};">
                                <div class="sd-tl-content">
                                    <div class="sd-tl-subject">{{ $item->subject->name }}</div>
                                    <div class="sd-tl-meta">
                                        <span class="sd-tl-timepill sd-tabular">{{ $item->start_time->format('g:i A') }} &ndash; {{ $item->end_time->format('g:i A') }}</span>
                                        <span class="sd-code-tag sd-mono">{{ $item->subject->code }}</span>
                                        @if($roomName !== '' && strtoupper($roomName) !== 'TBA')
                                            <span><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> {{ $roomName }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="sd-tl-status">
                                    @if($item->status === 'completed') <x-badge type="present" size="sm">Present</x-badge>
                                    @elseif($item->status === 'ongoing') <x-badge type="late" size="sm" pulse>Live</x-badge>
                                    @elseif($item->status === 'missed') <x-badge type="absent" size="sm">Missed</x-badge>
                                    @else <x-badge type="info" size="sm">Soon</x-badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif

            <section class="sd-surface-card" aria-label="Recent Activity">
                <header class="sd-surface-head">
                    <div class="sd-surface-title-group">
                        <span class="sd-surface-icon" aria-hidden="true"><i class="bi bi-activity"></i></span>
                        <div>
                            <h3 class="sd-surface-title">Recent Activity</h3>
                            <p class="sd-surface-sub">Latest check-ins</p>
                        </div>
                    </div>
                    <a href="{{ route('mobile.history') }}" class="sd-pill-btn">History</a>
                </header>
                <div class="sd-surface-body">
                    <div class="sd-recent-list">
                        @forelse($recentActivity as $date => $activities)
                            @foreach($activities as $activity)
                                @php
                                    $actTone = $activity['status'] === 'success' ? 'present' : ($activity['status'] === 'warning' ? 'late' : 'absent');
                                @endphp
                                <div class="sd-recent-row">
                                    <span class="sd-recent-icon is-{{ $actTone }}" aria-hidden="true">
                                        <i class="bi bi-{{ $activity['icon'] }}"></i>
                                    </span>
                                    <div class="sd-recent-copy">
                                        <div class="sd-recent-title">{{ $activity['subtitle'] ?? $activity['title'] }}</div>
                                        <div class="sd-recent-sub">{{ $date }} &middot; {{ $activity['title'] }}</div>
                                    </div>
                                    @if(!empty($activity['time']))
                                        <span class="sd-recent-time sd-tabular">{{ $activity['time'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        @empty
                            <div class="sd-empty-box is-compact">
                                <span class="sd-empty-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                                <p class="sd-empty-title">No recent activity</p>
                                <p class="sd-empty-sub">Your attendance logs will show up here after check-in.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        <div class="sd-col-secondary">
            @if($subjectStatsCollection->isNotEmpty())
            <section class="sd-surface-card" aria-label="Subject Attendance">
                <header class="sd-surface-head">
                    <div class="sd-surface-title-group">
                        <span class="sd-surface-icon" aria-hidden="true"><i class="bi bi-bar-chart-line-fill"></i></span>
                        <div>
                            <h3 class="sd-surface-title">Subject Attendance</h3>
                            <p class="sd-surface-sub">{{ $subjectStatsCollection->count() }} {{ \Illuminate\Support\Str::plural('subject', $subjectStatsCollection->count()) }}</p>
                        </div>
                    </div>
                    <a href="{{ route('student.classes') }}" class="sd-pill-btn">Classes</a>
                </header>
                <div class="sd-surface-body">
                    <div class="sd-subject-stack">
                        @foreach($subjectStatsCollection->take(4) as $sub)
                            @php
                                $hasRecords = $sub->total > 0;
                                $rateClass = !$hasRecords ? '' : ($sub->rate >= 85 ? 'is-good' : ($sub->rate >= 75 ? 'is-warn' : 'is-risk'));
                                $barColor = !$hasRecords ? 'rgba(236,224,228,0.16)' : ($sub->rate >= 85 ? '#34d399' : ($sub->rate >= 75 ? '#fbbf24' : '#ff6b7a'));
                            @endphp
                            <a href="{{ route('student.classes') }}" class="sd-subject-card" style="text-decoration:none;display:block;">
                                <div class="sd-subject-top">
                                    <div class="sd-subject-id">
                                        <span class="sd-subject-code sd-mono">{{ $sub->code }}</span>
                                        <span class="sd-subject-name">{{ $sub->name }}</span>
                                    </div>
                                    <span class="sd-subject-rate sd-tabular {{ $rateClass }}">{{ $hasRecords ? $sub->rate.'%' : 'No records' }}</span>
                                </div>
                                <div class="sd-meter-track" aria-hidden="true">
                                    <div class="sd-meter-fill" style="width: {{ $hasRecords ? min(100, max(0, $sub->rate)) : 0 }}%; background: {{ $barColor }};"></div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif

            @if(isset($announcements) && $announcements->isNotEmpty())
            <section class="sd-surface-card" aria-label="Campus Announcements">
                <header class="sd-surface-head">
                    <div class="sd-surface-title-group">
                        <span class="sd-surface-icon" aria-hidden="true"><i class="bi bi-megaphone-fill"></i></span>
                        <div>
                            <h3 class="sd-surface-title">Announcements</h3>
                            <p class="sd-surface-sub">Latest campus notices</p>
                        </div>
                    </div>
                </header>
                <div class="sd-surface-body">
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
        if (spot && timer) {
            const startMs = Number(spot.getAttribute('data-start-ms') || 0);
            const endMs = Number(spot.getAttribute('data-end-ms') || 0);
            const nowMs = now.getTime();
            if (startMs && endMs) {
                if (nowMs >= startMs && nowMs <= endMs) timer.textContent = formatRemaining(endMs - nowMs) + ' left';
                else if (nowMs < startMs) timer.textContent = 'Starts in ' + formatRemaining(startMs - nowMs);
                else timer.textContent = 'Ended';
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
