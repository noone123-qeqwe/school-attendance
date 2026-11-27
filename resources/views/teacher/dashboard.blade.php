@extends('layouts.app')
@section('page-title', 'Dashboard')

@section('content')
@php
    $teacherUser = Auth::user();
    $todayCollection = collect($todayClasses ?? []);
    $subjectsCollection = collect($teacherSubjects ?? []);

    $greetHour = now()->hour;
    $greeting = $greetHour < 12 ? 'Good Morning' : ($greetHour < 17 ? 'Good Afternoon' : 'Good Evening');
    $greetIcon = $greetHour < 12 ? 'bi-brightness-alt-high-fill' : ($greetHour < 17 ? 'bi-sun-fill' : 'bi-moon-stars-fill');

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
    <div class="portal-alert-rail" data-alert-group>
        @if(session('error'))
            <x-alert-banner class="portal-banner is-error" role="alert" data-alert-transient>
                <div class="portal-banner-lead">
                    <span class="portal-banner-icon" aria-hidden="true"><i class="bi bi-exclamation-circle-fill"></i></span>
                    <div><div class="portal-banner-title">{{ session('error') }}</div></div>
                </div>
            </x-alert-banner>
        @endif

        @if(session('success'))
            <x-alert-banner class="portal-banner is-success" role="status" data-alert-transient>
                <div class="portal-banner-lead">
                    <span class="portal-banner-icon" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></span>
                    <div><div class="portal-banner-title">{{ session('success') }}</div></div>
                </div>
            </x-alert-banner>
        @endif

        <x-alert-banner id="peerLiveNotice" class="portal-banner is-live" role="status" alert-key="peer-live-attendance"
                        dismiss-label="Dismiss live attendance notification" hidden>
            <span class="portal-banner-message" data-banner-message></span>
        </x-alert-banner>
        <x-alert-banner id="peerLiveConnection" class="portal-banner is-info" role="status" alert-key="peer-live-connection"
                        version="disconnected" dismiss-label="Dismiss live updates notice" hidden>
            <span class="portal-banner-message" data-banner-message></span>
        </x-alert-banner>
        <span id="peerLiveConnectionResolved" hidden></span>

        @if(isset($pendingExcuses) && $pendingExcuses > 0)
            <x-alert-banner class="portal-banner is-warning" role="region" aria-label="Pending excuse reviews"
                            alert-key="teacher-pending-excuses" :version="$pendingExcuses">
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
            </x-alert-banner>
        @else
            <span hidden data-alert-resolved="teacher-pending-excuses"></span>
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
                                <a href="{{ route('teacher.classroom.show', ['subjectCode' => $cls->code, 'tab' => 'records', 'date' => $targetCarbon->toDateString()]) }}" class="td-btn-records">
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
                    <a href="{{ route('teacher.classroom.index', ['task' => 'records']) }}" class="portal-stage-link">
                        <i class="bi bi-clipboard-check" aria-hidden="true"></i> Class Records
                    </a>
                    <a href="{{ route('teacher.students') }}" class="portal-stage-link">
                        <i class="bi bi-people" aria-hidden="true"></i> Student Record
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
    </div>
</div>
@endsection

@section('scripts')
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

// Peer verification events update this dashboard without a page reload.
(function subscribeToPeerAttendance() {
    let attempts = 0;
    const status = document.getElementById('peerLiveConnection');
    const connectionResolved = document.getElementById('peerLiveConnectionResolved');
    const seen = new Set();
    function setBannerMessage(bannerEl, message) {
        if (!bannerEl) return;
        const messageEl = bannerEl.querySelector('[data-banner-message]') || bannerEl;
        messageEl.textContent = message || '';
    }
    function setConnectionMessage(message) {
        if (!status) return;
        setBannerMessage(status, message);
        status.hidden = !message;
        if (message) connectionResolved?.removeAttribute('data-alert-resolved');
        else connectionResolved?.setAttribute('data-alert-resolved', status.dataset.alertKey);
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
                    notice.dataset.alertVersion = payload.attendance_id;
                    setBannerMessage(notice, `${payload.student_name} checked in by Peer Biometric for ${payload.subject_code}. Vouched by ${payload.voucher_student_name}.`);
                    notice.hidden = false;
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
</script>
@endsection
