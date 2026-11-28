@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
@php
    $user = Auth::user();
    $shell = $studentShellData ?? ['initials' => 'ST', 'shortName' => $user->name];
    $hasCustomAvatar = !empty($user->profile_image);
    $avatarUrl = $hasCustomAvatar ? $user->profile_photo_url_with_version : null;

    $hourNow = now()->hour;
    $greetingText = $hourNow < 12 ? 'Good Morning' : ($hourNow < 18 ? 'Good Afternoon' : 'Good Evening');
    $courseMeta = collect([
        $user->course,
        $user->year_level ? 'Year ' . $user->year_level : null,
        $user->semester ? 'Sem ' . $user->semester : null,
    ])->filter()->implode(' · ');

    $mobileCourseMeta = collect([
        $user->student_number ? 'ID ' . $user->student_number : null,
        trim(($user->course ?? '') . ' ' . ($user->year_level ?? '') . ($user->section ? '-' . $user->section : '')),
    ])->filter()->implode(' · ');

    $attentionCount = ($deviceLocked ? 1 : 0) + $activeWarnings->count() + $unresolvedAbsencesCount;
    $hasTodayClasses = $todaySchedule->isNotEmpty();
    $defaultScheduleTab = $hasTodayClasses ? 'today' : 'week';

    $totalUnitsFormatted = fmod((float) ($totalUnits ?? 0), 1.0) === 0.0
        ? (string) (int) ($totalUnits ?? 0)
        : number_format((float) ($totalUnits ?? 0), 1);
@endphp

<div class="sf-dash-stack">
    @include('partials.missing-section-notice')

    @if($assistantLiveSessions->isNotEmpty())
        @php $firstAssistSession = $assistantLiveSessions->first(); @endphp
        <div class="sf-assistant-strip" role="region" aria-label="Student assistant live duty">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                <span class="sf-live-dot" aria-hidden="true"></span>
                <div>
                    <strong style="color:var(--sf-text);">Student Assistant Live Session</strong>
                    <span style="color:var(--sf-text-secondary);margin-left:6px;">
                        {{ $firstAssistSession->subject_code }} · Ends {{ $firstAssistSession->session_ends_at->format('g:i A') }}
                        @if($assistantLiveSessions->count() > 1)
                            (+{{ $assistantLiveSessions->count() - 1 }} more)
                        @endif
                    </span>
                </div>
            </div>
            <a href="{{ route('student-assistant.classes') }}" class="sf-btn sf-btn-primary sf-btn-sm">
                Open Assistant QR
            </a>
        </div>
    @endif

    @if($todayHoliday)
        <div class="sf-flash" style="background:var(--sf-teal-bg);border:1px solid var(--sf-teal-border);color:var(--sf-teal);margin-bottom:0;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v4"/><path d="m6.8 14-3.5 2"/><path d="m20.7 16-3.5-2"/><path d="M6.8 10 3.3 8"/><path d="m20.7 8-3.5 2"/><path d="m9 22 3-8 3 8"/><path d="M8 22h8"/><circle cx="12" cy="12" r="4"/></svg>
            <div>
                <strong>{{ $todayHoliday->name }}</strong>
                <span style="margin-left:6px;opacity:0.9;">No regular classes scheduled today.</span>
            </div>
        </div>
    @endif

    {{-- Desktop Identity & KPI Strip --}}
    <section class="sf-card sf-identity-strip" aria-label="Student overview">
        <div class="sf-identity-left">
            <span class="sf-avatar is-square is-lg">
                @if($hasCustomAvatar)
                    <img src="{{ $avatarUrl }}" class="user-avatar-img" alt="{{ $user->name }}">
                @else
                    {{ $shell['initials'] }}
                @endif
            </span>
            <div style="min-width:0;">
                <div class="sf-identity-greeting">
                    <span class="sf-spark">✦ {{ $greetingText }}</span>
                    <span>·</span>
                    <span id="studentDateDesktop">{{ now()->format('l, M j, Y') }}</span>
                    <span class="sf-mono" id="studentClockDesktop">{{ now()->format('g:i:s A') }}</span>
                </div>
                <div class="sf-identity-name-row">
                    <h1 class="sf-identity-name">{{ $user->name }}</h1>
                    @if($user->student_number)
                        <span class="sf-code-chip">ID {{ $user->student_number }}</span>
                    @endif
                    @if($courseMeta !== '')
                        <span class="sf-identity-meta">{{ $courseMeta }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="sf-kpi-row">
            <a href="{{ route('student.calendar') }}" class="sf-kpi-tile">
                <div class="sf-kpi-top">
                    <span class="sf-kpi-label">Check-ins</span>
                    <svg class="sf-kpi-icon is-emerald" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                </div>
                <div class="sf-kpi-val-row">
                    <span class="sf-kpi-val">{{ $totalCheckins ?? 0 }}</span>
                    <span class="sf-kpi-sub">{{ $lastCheckinCode ? 'Last: ' . $lastCheckinCode : 'No logs yet' }}</span>
                </div>
            </a>

            <a href="{{ route('student.classes') }}" class="sf-kpi-tile">
                <div class="sf-kpi-top">
                    <span class="sf-kpi-label">Courses</span>
                    <svg class="sf-kpi-icon is-sky" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </div>
                <div class="sf-kpi-val-row">
                    <span class="sf-kpi-val">{{ $subjects->count() }}</span>
                    <span class="sf-kpi-sub">{{ $totalUnitsFormatted }} units</span>
                </div>
            </a>

            <a href="{{ route('student.calendar', ['status' => 'absent']) }}" class="sf-kpi-tile">
                <div class="sf-kpi-top">
                    <span class="sf-kpi-label">Standing</span>
                    <svg class="sf-kpi-icon is-rose" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                </div>
                <div class="sf-kpi-val-row">
                    <span class="sf-kpi-val {{ $attentionCount === 0 ? 'is-emerald' : 'is-danger' }}">{{ $attentionCount === 0 ? 'Clear' : $attentionCount }}</span>
                    <span class="sf-kpi-sub">{{ $attentionCount === 0 ? '0 flags' : \Illuminate\Support\Str::plural('flag', $attentionCount) }}</span>
                </div>
            </a>

            <a href="{{ route('excuses') }}" class="sf-kpi-tile">
                <div class="sf-kpi-top">
                    <span class="sf-kpi-label">Excuses</span>
                    <svg class="sf-kpi-icon is-amber" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                </div>
                <div class="sf-kpi-val-row">
                    <span class="sf-kpi-val">{{ $pendingExcusesCount ?? 0 }}</span>
                    <span class="sf-kpi-sub">pending</span>
                </div>
            </a>
        </div>
    </section>

    {{-- Mobile Compact Greeting & 2x2 KPI Grid --}}
    <div class="sf-mobile-greeting">
        <div class="sf-mobile-greeting-left">
            <div class="sf-identity-greeting" style="margin-bottom:0;">
                <span class="sf-spark">✦ {{ $greetingText }}</span>
            </div>
            <h1>{{ $shell['shortName'] }}</h1>
            @if($mobileCourseMeta !== '')
                <div class="sf-identity-meta">{{ $mobileCourseMeta }}</div>
            @endif
        </div>
        <div class="sf-mobile-greeting-date">
            <strong id="studentDateMobile">{{ now()->format('D, M j') }}</strong>
            <time id="studentClockMobile">{{ now()->format('g:i A') }}</time>
        </div>
    </div>

    <div class="sf-mobile-kpi-grid" aria-label="Quick stats">
        <a href="{{ route('student.calendar') }}" class="sf-kpi-tile">
            <div class="sf-kpi-top">
                <span class="sf-kpi-label">Check-ins</span>
                <svg class="sf-kpi-icon is-emerald" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <div class="sf-kpi-val-row">
                <span class="sf-kpi-val">{{ $totalCheckins ?? 0 }}</span>
                <span class="sf-kpi-sub">logged</span>
            </div>
        </a>
        <a href="{{ route('student.classes') }}" class="sf-kpi-tile">
            <div class="sf-kpi-top">
                <span class="sf-kpi-label">Courses</span>
                <svg class="sf-kpi-icon is-sky" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
            <div class="sf-kpi-val-row">
                <span class="sf-kpi-val">{{ $subjects->count() }}</span>
                <span class="sf-kpi-sub">{{ $totalUnitsFormatted }} units</span>
            </div>
        </a>
        <a href="{{ route('student.calendar', ['status' => 'absent']) }}" class="sf-kpi-tile">
            <div class="sf-kpi-top">
                <span class="sf-kpi-label">Absences</span>
                <svg class="sf-kpi-icon is-rose" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            </div>
            <div class="sf-kpi-val-row">
                <span class="sf-kpi-val {{ $unresolvedAbsencesCount === 0 ? 'is-emerald' : 'is-danger' }}">{{ $unresolvedAbsencesCount }}</span>
                <span class="sf-kpi-sub">{{ $unresolvedAbsencesCount === 0 ? 'clear' : 'unexcused' }}</span>
            </div>
        </a>
        <a href="{{ route('excuses') }}" class="sf-kpi-tile">
            <div class="sf-kpi-top">
                <span class="sf-kpi-label">Excuses</span>
                <svg class="sf-kpi-icon is-amber" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
            </div>
            <div class="sf-kpi-val-row">
                <span class="sf-kpi-val">{{ $pendingExcusesCount ?? 0 }}</span>
                <span class="sf-kpi-sub">pending</span>
            </div>
        </a>
    </div>

    {{-- Asymmetric Desktop Grid (Main + Right Rail) --}}
    <div class="sf-dash-grid">
        <div class="sf-dash-main">
            {{-- Hero Spotlight Card --}}
            @php
                $heroIsLive = in_array($heroSlot->mode, ['open_session', 'ongoing_pending', 'ongoing_checked_in'], true);
                $heroPillClass = match (true) {
                    $heroSlot->is_checked_in => 'is-emerald',
                    $heroSlot->mode === 'open_session' => 'is-emerald',
                    $heroSlot->mode === 'ongoing_pending' => 'is-rose',
                    $todayHoliday !== null && !$heroSlot->code => 'is-teal',
                    default => '',
                };
            @endphp
            <section class="sf-hero-card {{ $heroIsLive ? 'is-live' : '' }}" id="sdHeroSpotlight" aria-label="Next class and check-in">
                <div style="min-width:0;">
                    <div class="sf-hero-badges">
                        <span class="sf-hero-kicker" style="{{ $heroIsLive ? 'color:var(--sf-emerald);' : '' }}">
                            <span class="sf-hero-kicker-dot" aria-hidden="true"></span>
                            <span>{{ $heroSlot->kicker }}</span>
                        </span>

                        @if($heroSlot->code)
                            <span class="sf-code-chip is-rose">{{ $heroSlot->code }}</span>
                        @endif

                        @if($heroSlot->status_label)
                            <span class="sf-pill {{ $heroPillClass }}">
                                @if($heroSlot->is_checked_in)
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                @endif
                                {{ $heroSlot->status_label }}
                            </span>
                        @endif

                        @if($heroSlot->countdown_target_ms)
                            <span class="sf-pill sf-mono" id="sfHeroCountdown"
                                  data-target-ms="{{ $heroSlot->countdown_target_ms }}"
                                  data-prefix="{{ $heroSlot->countdown_prefix }}">
                                {{ $heroSlot->countdown_prefix }} --
                            </span>
                        @endif
                    </div>

                    <h2 class="sf-hero-title">{{ $heroSlot->title }}</h2>

                    @if($heroSlot->date_time_label || $heroSlot->room || $heroSlot->instructor)
                        <div class="sf-hero-meta">
                            @if($heroSlot->date_time_label)
                                <span class="sf-hero-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    <span>{{ $heroSlot->date_time_label }}</span>
                                </span>
                            @endif
                            @if($heroSlot->room)
                                <span class="sf-hero-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <span>{{ $heroSlot->room }}</span>
                                </span>
                            @endif
                            @if($heroSlot->instructor)
                                <span class="sf-hero-meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <span>{{ $heroSlot->instructor }}</span>
                                </span>
                            @endif
                        </div>
                    @endif

                    @if($heroSlot->confirmation_text)
                        <p class="sf-hero-confirm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                            <span>{{ $heroSlot->confirmation_text }}</span>
                            @if($heroSlot->confirmation_meta)
                                <span class="sf-pill is-emerald" style="margin-left:4px;">{{ $heroSlot->confirmation_meta }}</span>
                            @endif
                        </p>
                    @elseif($heroSlot->helper_text)
                        <p class="sf-hero-helper">{{ $heroSlot->helper_text }}</p>
                    @endif
                </div>

                <div @class(['sf-hero-checkin', 'sf-desktop-only' => !$peerAvailable])>
                    <div class="sf-hero-checkin-head">
                        <strong>Record attendance</strong>
                        @if($heroSlot->duration_min)
                            <span>{{ $heroSlot->duration_min }} min</span>
                        @endif
                    </div>
                    <p>Scan your instructor's rotating QR or enter the 6-digit session PIN.</p>
                    <div class="sf-hero-cta-row sf-desktop-only">
                        <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sf-btn sf-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/></svg>
                            <span>Scan QR</span>
                        </a>
                        <a href="{{ route('mobile.scan', ['open_code' => 1]) }}" class="sf-btn sf-btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" x2="20" y1="9" y2="9"/><line x1="4" x2="20" y1="15" y2="15"/><line x1="10" x2="8" y1="3" y2="21"/><line x1="16" x2="14" y1="3" y2="21"/></svg>
                            <span>Enter PIN</span>
                        </a>
                    </div>
                    @if($peerAvailable)
                        <a href="{{ route('mobile.peer-snap') }}" class="sf-link" style="margin-top:9px;font-size:11.5px;">
                            Need phone backup? Use Peer Snap →
                        </a>
                    @endif
                </div>
            </section>

            {{-- Class Schedule Card (Upcoming Week | Today Toggle) --}}
            <section class="sf-card" aria-label="Class schedule">
                <div class="sf-card-head">
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <h2 class="sf-card-title">Class schedule</h2>
                        <div class="sf-segmented" role="tablist" aria-label="Schedule filter">
                            <button type="button"
                                    class="sf-segmented-btn {{ $defaultScheduleTab === 'week' ? 'active' : '' }}"
                                    role="tab"
                                    aria-selected="{{ $defaultScheduleTab === 'week' ? 'true' : 'false' }}"
                                    data-sched-tab="week">
                                Upcoming Week
                            </button>
                            <button type="button"
                                    class="sf-segmented-btn {{ $defaultScheduleTab === 'today' ? 'active' : '' }}"
                                    role="tab"
                                    aria-selected="{{ $defaultScheduleTab === 'today' ? 'true' : 'false' }}"
                                    data-sched-tab="today">
                                Today ({{ now()->format('D') }})
                            </button>
                        </div>
                    </div>
                    <a href="{{ route('student.classes') }}" class="sf-link">Full timetable →</a>
                </div>

                {{-- Upcoming Week List --}}
                <div id="sfSchedPanelWeek" @if($defaultScheduleTab !== 'week') hidden @endif>
                    @if($weeklyClassRows->isNotEmpty())
                        <ul class="sf-sched-list" id="sfWeekSchedList">
                            @foreach($weeklyClassRows as $idx => $row)
                                <li class="sf-sched-row {{ $idx >= 2 ? 'sf-mobile-collapsible-row' : '' }}" data-collapsible-idx="{{ $idx }}">
                                    <div class="sf-sched-left">
                                        <span class="sf-code-chip is-rose">{{ $row->code }}</span>
                                        <div class="sf-sched-copy">
                                            <strong>{{ $row->name }}</strong>
                                            <span>{{ $row->room }} · {{ $row->instructor }}</span>
                                        </div>
                                    </div>
                                    <div class="sf-sched-right">
                                        <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sf-btn sf-btn-secondary sf-btn-sm sf-hover-checkin sf-desktop-only">
                                            Check in
                                        </a>
                                        <span class="sf-code-chip">{{ $row->days_label }}</span>
                                        <span class="sf-sched-time">{{ $row->time_range }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @if($weeklyClassRows->count() > 2)
                            <button type="button" class="sf-expand-more-btn sf-mobile-only" id="sfMobileExpandSchedBtn" data-expanded="false" data-extra-count="{{ $weeklyClassRows->count() - 2 }}">
                                Show {{ $weeklyClassRows->count() - 2 }} more classes
                            </button>
                        @endif
                    @else
                        <div class="sf-empty-inline">
                            <div class="sf-empty-inline-left">
                                <span class="sf-empty-icon-box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                </span>
                                <div>
                                    <strong>No enrolled subjects yet</strong>
                                    <p>Your weekly timetable will appear once subjects are assigned.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Today's Exact Timetable Slots --}}
                <div id="sfSchedPanelToday" @if($defaultScheduleTab !== 'today') hidden @endif>
                    @if($todaySchedule->isNotEmpty())
                        <ul class="sf-sched-list">
                            @foreach($todaySchedule as $slot)
                                @php
                                    $slotRoom = trim((string) ($slot->schedule->room ?? ''));
                                    $slotRoomClean = ($slotRoom !== '' && strtoupper($slotRoom) !== 'TBA') ? $slotRoom : 'Room TBA';
                                    $slotInstructor = $slot->subject->instructorUser?->name ?? $slot->subject->instructor ?? 'Instructor TBA';
                                    $slotAttendance = $slot->attendance;
                                    $slotStatusText = null;
                                    $slotPillTone = '';
                                    if ($slotAttendance) {
                                        $st = ucfirst(strtolower((string) $slotAttendance->status));
                                        $slotStatusText = $slot->has_multiple_slots ? ($st . ' · day record') : $st;
                                        $slotPillTone = match ($st) {
                                            'Present' => 'is-emerald',
                                            'Late' => 'is-amber',
                                            'Excused' => 'is-sky',
                                            default => 'is-danger',
                                        };
                                    } elseif ($slot->schedule_status === 'ongoing') {
                                        $slotStatusText = 'Happening now';
                                        $slotPillTone = 'is-rose';
                                    }
                                @endphp
                                <li class="sf-sched-row {{ $slot->schedule_status === 'ongoing' ? 'is-current' : '' }}">
                                    <div class="sf-sched-left">
                                        <span class="sf-code-chip is-rose">{{ $slot->subject->code }}</span>
                                        <div class="sf-sched-copy">
                                            <strong>{{ $slot->subject->name }}</strong>
                                            <span>{{ $slotRoomClean }} · {{ $slotInstructor }}</span>
                                        </div>
                                    </div>
                                    <div class="sf-sched-right">
                                        @if($slotStatusText)
                                            <span class="sf-pill {{ $slotPillTone }}">{{ $slotStatusText }}</span>
                                        @elseif($slot->active_session)
                                            <a href="{{ route('mobile.scan', ['open_scanner' => 1]) }}" class="sf-btn sf-btn-primary sf-btn-sm sf-desktop-only">Check in</a>
                                        @endif
                                        <span class="sf-sched-time">{{ $slot->start_time->format('g:i A') }} – {{ $slot->end_time->format('g:i A') }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="sf-empty-inline">
                            <div class="sf-empty-inline-left">
                                <span class="sf-empty-icon-box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
                                </span>
                                <div>
                                    <strong>{{ $todayHoliday ? $todayHoliday->name : 'No classes scheduled for ' . now()->format('l') }}</strong>
                                    <p>Switch to Upcoming Week to view your full class lineup.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Bottom 2-Column Sub-grid: Recent Attendance + Course Check-in Tracker --}}
            <div class="sf-dash-subgrid">
                <section class="sf-card" aria-label="Recent attendance">
                    <div class="sf-card-head">
                        <h2 class="sf-card-title">Recent attendance</h2>
                        <a href="{{ route('student.calendar') }}" class="sf-link">All records →</a>
                    </div>

                    @if($recentAttendance->isNotEmpty())
                        <ul class="sf-recent-list">
                            @foreach($recentAttendance as $rec)
                                @php
                                    $recStatus = $rec->excused ? 'Excused' : ucfirst(strtolower((string) $rec->status));
                                    $recDotClass = match ($recStatus) {
                                        'Present' => 'is-present',
                                        'Late' => 'is-late',
                                        'Excused' => 'is-excused',
                                        default => 'is-absent',
                                    };
                                    $recPillClass = match ($recStatus) {
                                        'Present' => 'is-emerald',
                                        'Late' => 'is-amber',
                                        'Excused' => 'is-sky',
                                        default => 'is-danger',
                                    };
                                    $recDateObj = \Carbon\Carbon::parse($rec->date);
                                    $recUrl = route('student.calendar', [
                                        'q' => $rec->subject_code,
                                        'record_date' => $recDateObj->toDateString(),
                                    ]) . '#attendance-records';
                                    $recName = $rec->subject?->name ?? $rec->subject_name ?? $rec->subject_code;
                                @endphp
                                <li>
                                    <a href="{{ $recUrl }}" class="sf-recent-row">
                                        <div class="sf-recent-left">
                                            <span class="sf-status-dot {{ $recDotClass }}" aria-hidden="true"></span>
                                            <div class="sf-recent-copy">
                                                <div class="sf-recent-title">
                                                    <span class="sf-mono">{{ $rec->subject_code }}</span>
                                                    <span>{{ $recName }}</span>
                                                </div>
                                                <div class="sf-recent-sub">
                                                    {{ $recDateObj->format('M j, Y') }}
                                                    @if($rec->time_in)
                                                        · {{ \Carbon\Carbon::parse($rec->time_in)->format('g:i A') }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <span class="sf-pill {{ $recPillClass }}">{{ $recStatus }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="sf-empty-inline">
                            <div class="sf-empty-inline-left">
                                <span class="sf-empty-icon-box">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/></svg>
                                </span>
                                <div>
                                    <strong>No check-ins logged yet</strong>
                                    <p>Your first class check-in will appear here.</p>
                                </div>
                            </div>
                            <a href="{{ route('mobile.scan') }}" class="sf-btn sf-btn-secondary sf-btn-sm sf-desktop-only">Try check-in</a>
                        </div>
                    @endif
                </section>

                <section class="sf-card sf-desktop-only" aria-label="Course check-in tracker">
                    <div class="sf-card-head">
                        <h2 class="sf-card-title">Course check-in tracker</h2>
                        <span class="sf-pill sf-mono">{{ $courseTrackerOverallPct ?? 0 }}% logged</span>
                    </div>

                    @if(!empty($courseTracker))
                        <div class="sf-tracker-list">
                            @foreach($courseTracker as $item)
                                <div>
                                    <div class="sf-tracker-item-top">
                                        <span class="sf-tracker-name">
                                            <span class="sf-mono">{{ $item['code'] }}</span>
                                            <span>{{ $item['name'] }}</span>
                                        </span>
                                        <span class="sf-tracker-ratio">{{ $item['logged'] }}/{{ $item['target'] }}</span>
                                    </div>
                                    <div class="sf-progress-track">
                                        <div class="sf-progress-fill" style="width: {{ $item['percent'] }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="sf-empty-inline">
                            <div class="sf-empty-inline-left">
                                <div>
                                    <strong>No enrolled courses</strong>
                                    <p>Progress bars appear once courses are enrolled.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </section>
            </div>
        </div>

        {{-- Right Rail --}}
        <div class="sf-dash-rail" aria-label="Calendar and attention">
            {{-- Mini Monthly Calendar (Desktop) --}}
            <section class="sf-card sf-desktop-only" aria-label="Monthly calendar preview">
                <div class="sf-card-head">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <h2 class="sf-card-title">{{ $miniCalendar['monthLabel'] }}</h2>
                        @if($user->semester)
                            <span class="sf-pill">Sem {{ $user->semester }}</span>
                        @endif
                    </div>
                    <a href="{{ route('student.calendar') }}" class="sf-link">Open calendar</a>
                </div>

                <div class="sf-minical-grid">
                    @foreach(['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $wd)
                        <div class="sf-minical-wd">{{ $wd }}</div>
                    @endforeach

                    @for($b = 0; $b < $miniCalendar['leadingBlanks']; $b++)
                        <div class="sf-minical-day" aria-hidden="true"></div>
                    @endfor

                    @for($d = 1; $d <= $miniCalendar['daysInMonth']; $d++)
                        @php
                            $isHighlight = $miniCalendar['highlightDay'] === $d;
                            $isToday = $miniCalendar['todayDay'] === $d;
                            $dotColor = null;
                            if (!empty($miniCalendar['presentDays'][$d])) {
                                $dotColor = 'var(--sf-emerald)';
                            } elseif (!empty($miniCalendar['excusedDays'][$d])) {
                                $dotColor = 'var(--sf-sky)';
                            } elseif (!empty($miniCalendar['classDays'][$d])) {
                                $dotColor = 'var(--sf-amber)';
                            }
                            $dateStr = sprintf('%04d-%02d-%02d', $miniCalendar['year'], $miniCalendar['month'], $d);
                        @endphp
                        <a href="{{ route('student.calendar', ['record_date' => $dateStr]) }}"
                           class="sf-minical-day {{ $isHighlight ? 'is-highlight' : '' }} {{ $isToday ? 'is-today' : '' }}"
                           title="{{ $dateStr }}">
                            <span>{{ $d }}</span>
                            @if($dotColor)
                                <span class="sf-minical-dot" style="background:{{ $dotColor }};"></span>
                            @endif
                        </a>
                    @endfor
                </div>

                <div class="sf-minical-legend">
                    <span class="sf-legend-item"><span class="sf-legend-dot" style="background:var(--sf-emerald);"></span> Present</span>
                    <span class="sf-legend-item"><span class="sf-legend-dot" style="background:var(--sf-amber);"></span> Class day</span>
                    <span class="sf-legend-item"><span class="sf-legend-dot" style="background:var(--sf-sky);"></span> Excused</span>
                </div>
            </section>

            {{-- Needs Your Attention --}}
            <section class="sf-card" aria-label="Needs your attention">
                <div class="sf-card-head">
                    <h2 class="sf-card-title">Needs your attention</h2>
                    <span class="sf-pill sf-mono {{ $attentionCount === 0 ? 'is-emerald' : 'is-danger' }}">{{ $attentionCount }}</span>
                </div>
                <div class="sf-card-body">
                    @if($attentionCount === 0)
                        <div class="sf-clear-box">
                            <span class="sf-clear-icon" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                            </span>
                            <div>
                                <strong>All clear</strong>
                                <p>No unexcused absences or device alerts.</p>
                            </div>
                        </div>
                    @else
                        @if($deviceLocked)
                            <div class="sf-attention-item">
                                <div style="font-weight:600;color:var(--sf-danger);margin-bottom:2px;">Attendance device locked</div>
                                <div style="color:var(--sf-text-soft);margin-bottom:6px;">{{ $deviceLockReason ?: 'Your registered check-in device is currently locked.' }}</div>
                                <a href="{{ route('settings') }}#tab-device" class="sf-link">Review device →</a>
                            </div>
                        @endif

                        @if($activeWarnings->isNotEmpty())
                            <div class="sf-attention-item" @include('partials.student-warning-alert-attributes')>
                                <div style="font-weight:600;color:var(--sf-amber-text);margin-bottom:4px;">
                                    {{ $activeWarnings->count() }} Attendance {{ \Illuminate\Support\Str::plural('Warning', $activeWarnings->count()) }}
                                </div>
                                @foreach($activeWarnings->take(2) as $warn)
                                    <div style="color:var(--sf-text-soft);font-size:11.5px;margin-bottom:3px;">{{ $warn->message }}</div>
                                @endforeach
                            </div>
                        @endif

                        @foreach($unresolvedAbsences as $absence)
                            @php
                                $absName = $absence->subject?->name ?? $absence->subject_name ?? $absence->subject_code;
                            @endphp
                            <div class="sf-attention-item" style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                                <div style="min-width:0;">
                                    <div style="font-weight:600;color:var(--sf-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <span class="sf-mono" style="color:var(--sf-danger);margin-right:4px;">{{ $absence->subject_code }}</span>
                                        {{ $absName }}
                                    </div>
                                    <div class="sf-mono" style="font-size:11px;color:var(--sf-text-muted);">
                                        Absent · {{ \Carbon\Carbon::parse($absence->date)->format('M j, Y') }}
                                    </div>
                                </div>
                                <a href="{{ route('excuses.create', $absence->id) }}" class="sf-btn sf-btn-secondary sf-btn-sm">
                                    Submit excuse
                                </a>
                            </div>
                        @endforeach
                    @endif
                </div>
            </section>

            {{-- Excuse Requests --}}
            <section class="sf-card sf-desktop-only" aria-label="Excuse requests">
                <div class="sf-card-head">
                    <h2 class="sf-card-title">Excuse requests</h2>
                    <a href="{{ route('excuses') }}" class="sf-link">View all →</a>
                </div>
                <div class="sf-card-body">
                    @if($recentExcuseRequests->isNotEmpty())
                        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px;">
                            @foreach($recentExcuseRequests as $exc)
                                @php
                                    $excTone = match ($exc->status) {
                                        'approved' => 'is-emerald',
                                        'rejected' => 'is-danger',
                                        default => 'is-amber',
                                    };
                                @endphp
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:8px 10px;border-radius:9px;background:var(--sf-surface-2);border:1px solid var(--sf-border);">
                                    <div style="min-width:0;">
                                        <div style="font-size:12px;font-weight:600;color:var(--sf-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            {{ $exc->attendance?->subject_code ?? 'General' }} · {{ $exc->reason }}
                                        </div>
                                        <div class="sf-mono" style="font-size:10.5px;color:var(--sf-text-muted);">
                                            {{ $exc->attendance?->date ? \Carbon\Carbon::parse($exc->attendance->date)->format('M j, Y') : $exc->created_at?->format('M j, Y') }}
                                        </div>
                                    </div>
                                    <span class="sf-pill {{ $excTone }}">{{ ucfirst($exc->status) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p style="margin:0 0 12px;font-size:12px;color:var(--sf-text-muted);">
                            Need to explain a missed session? Upload a document for faculty review.
                        </p>
                    @endif
                    <a href="{{ route('excuses.create_general') }}" class="sf-btn sf-btn-secondary sf-btn-block">
                        + Submit an excuse
                    </a>
                </div>
            </section>

            {{-- Announcements (shown when active announcements exist) --}}
            @if($announcements->isNotEmpty())
                <section class="sf-card" aria-label="School announcements">
                    <div class="sf-card-head">
                        <h2 class="sf-card-title">Announcements</h2>
                        <span class="sf-pill sf-mono">{{ $announcements->count() }}</span>
                    </div>
                    <div class="sf-card-body" style="display:flex;flex-direction:column;gap:10px;">
                        @foreach($announcements->take(3) as $ann)
                            <div style="padding:10px 12px;border-radius:10px;background:var(--sf-surface-2);border:1px solid var(--sf-border);">
                                <div style="font-size:12.5px;font-weight:600;color:var(--sf-text);margin-bottom:3px;">{{ $ann->title }}</div>
                                <div style="font-size:11.5px;color:var(--sf-text-secondary);line-height:1.45;">{{ \Illuminate\Support\Str::limit(strip_tags((string) $ann->content), 120) }}</div>
                                <div class="sf-mono" style="font-size:10.5px;color:var(--sf-text-muted);margin-top:5px;">{{ $ann->created_at?->diffForHumans() }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Shortcuts Footer Bar (Desktop) --}}
            <div class="sf-card sf-shortcuts-card sf-desktop-only">
                <span>Shortcuts</span>
                <div class="sf-shortcuts-list">
                    <span><kbd class="sf-kbd">Ctrl K</kbd> Search</span>
                    <span><kbd class="sf-kbd">Q</kbd> QR</span>
                    <span><kbd class="sf-kbd">P</kbd> PIN</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script @cspNonce>
(function () {
    'use strict';

    // 1. Live Clock
    function tickClock() {
        if (document.hidden) return;
        const now = new Date();
        const dDesk = document.getElementById('studentDateDesktop');
        const tDesk = document.getElementById('studentClockDesktop');
        const dMob = document.getElementById('studentDateMobile');
        const tMob = document.getElementById('studentClockMobile');
        if (dDesk) dDesk.textContent = now.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
        if (tDesk) tDesk.textContent = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true });
        if (dMob) dMob.textContent = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        if (tMob) tMob.textContent = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    }

    // 2. Live Hero Countdown (Dd Hh Mm Ss)
    function tickCountdown() {
        if (document.hidden) return;
        const el = document.getElementById('sfHeroCountdown');
        if (!el) return;
        const targetMs = Number(el.dataset.targetMs || 0);
        if (!targetMs) return;
        const prefix = el.dataset.prefix || 'Starts in';
        const diffSec = Math.max(0, Math.floor((targetMs - Date.now()) / 1000));
        if (diffSec <= 0) {
            el.textContent = 'Now active';
            return;
        }
        const days = Math.floor(diffSec / 86400);
        const hours = Math.floor((diffSec % 86400) / 3600);
        const mins = Math.floor((diffSec % 3600) / 60);
        const secs = diffSec % 60;
        const parts = [];
        if (days > 0) parts.push(days + 'd');
        if (hours > 0 || days > 0) parts.push(hours + 'h');
        parts.push(mins + 'm');
        parts.push(String(secs).padStart(2, '0') + 's');
        el.textContent = prefix + ' ' + parts.join(' ');
    }

    tickClock();
    tickCountdown();
    setInterval(tickClock, 1000);
    setInterval(tickCountdown, 1000);

    // 3. Schedule Segmented Filter Tabs
    const tabBtns = document.querySelectorAll('[data-sched-tab]');
    const panelWeek = document.getElementById('sfSchedPanelWeek');
    const panelToday = document.getElementById('sfSchedPanelToday');
    tabBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-sched-tab');
            tabBtns.forEach((b) => {
                const active = b.getAttribute('data-sched-tab') === target;
                b.classList.toggle('active', active);
                b.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            if (panelWeek) panelWeek.hidden = target !== 'week';
            if (panelToday) panelToday.hidden = target !== 'today';
        });
    });

    // 4. Mobile Collapsible Schedule Rows (Show first 2, toggle remaining)
    const expandBtn = document.getElementById('sfMobileExpandSchedBtn');
    const collapsibleRows = document.querySelectorAll('.sf-mobile-collapsible-row');
    function syncMobileCollapse() {
        if (!expandBtn) return;
        const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
        const expanded = expandBtn.dataset.expanded === 'true';
        collapsibleRows.forEach((row) => {
            row.style.display = (isMobile && !expanded) ? 'none' : '';
        });
        const extra = expandBtn.dataset.extraCount || collapsibleRows.length;
        expandBtn.textContent = expanded ? 'Show fewer classes' : ('Show ' + extra + ' more classes');
    }
    if (expandBtn) {
        expandBtn.addEventListener('click', () => {
            expandBtn.dataset.expanded = expandBtn.dataset.expanded === 'true' ? 'false' : 'true';
            syncMobileCollapse();
        });
        window.addEventListener('resize', syncMobileCollapse);
        syncMobileCollapse();
    }
})();
</script>
@endsection
