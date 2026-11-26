@extends('layouts.app')
@section('page-title', 'Dashboard')

@section('content')

@php
    $todaySchedCollection = collect($todaySchedule ?? []);
    $todayClassCount = $todaySchedCollection->count();
    $completedTodayCount = $todaySchedCollection->where('status', 'completed')->count();

    $liveClassItem = $todaySchedCollection->firstWhere('status', 'ongoing');
    $nextClassItem = $todaySchedCollection->firstWhere('status', 'upcoming');
    $featuredClass = $liveClassItem ?? $nextClassItem;
    $featuredRoom = trim((string) ($featuredClass?->schedule?->room ?? ''));
    $featuredInstructor = trim((string) ($featuredClass?->subject?->instructorUser?->name ?? $featuredClass?->subject?->instructor ?? ''));

    $greetHour = now()->hour;
    $greeting = $greetHour < 12 ? 'Good Morning' : ($greetHour < 17 ? 'Good Afternoon' : 'Good Evening');
    $greetIcon = $greetHour < 12 ? 'bi-brightness-alt-high-fill' : ($greetHour < 17 ? 'bi-sun-fill' : 'bi-moon-stars-fill');
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

    {{-- ── 1. Student Identity Header Card ──────────────────────────────── --}}
    <section class="sd-command-bar" aria-label="Student overview">
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
    </section>

    {{-- ── 2. Today's Status Card ───────────────────────────────────────── --}}
    <div class="sd-bento-hero sd-bento-hero--solo">
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
    </div>

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
