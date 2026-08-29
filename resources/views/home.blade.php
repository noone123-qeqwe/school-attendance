@extends('layouts.app')
@section('page-title', 'Student Dashboard')

@section('content')

@php
    $calYear   = request('cal_year',  now()->year);
    $calMonth  = request('cal_month', now()->month);
    $calStart  = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $calEnd    = $calStart->copy()->endOfMonth();
    $prevMonth = $calStart->copy()->subMonth();
    $nextMonth = $calStart->copy()->addMonth();

    $monthRecords = isset($records) ? $records->filter(fn($r) =>
        \Carbon\Carbon::parse($r->date)->year  == $calYear &&
        \Carbon\Carbon::parse($r->date)->month == $calMonth
    ) : collect();

    $dayRecordsMap = [];
    foreach ($monthRecords as $r) {
        $day = (int) \Carbon\Carbon::parse($r->date)->format('j');
        $dayRecordsMap[$day][] = $r;
    }

    $dayDotsMap = [];
    foreach ($dayRecordsMap as $day => $recs) {
        $dots = [];
        $statuses = collect($recs)->pluck('status')->unique();
        if ($statuses->contains('Present')) $dots[] = 'present';
        if ($statuses->contains('Late'))    $dots[] = 'late';
        if ($statuses->contains('Absent'))  $dots[] = 'absent';
        $dayDotsMap[$day] = $dots;
    }

    $calendarJson = [];
    foreach ($dayRecordsMap as $day => $recs) {
        $dateKey = \Carbon\Carbon::create($calYear, $calMonth, $day)->format('Y-m-d');
        $calendarJson[$dateKey] = collect($recs)->map(fn($r) => [
            'subject' => $r->subject->name ?? $r->subject_code,
            'code'    => $r->subject_code,
            'status'  => $r->status,
            'time_in' => $r->time_in ? \Carbon\Carbon::parse($r->time_in)->format('h:i A') : null,
        ])->values()->toArray();
    }

    $today     = now()->day;
    $todayFull = now()->toDateString();
    $isCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $startDow  = $calStart->dayOfWeek;

    $noClassDays = [];
    for ($d = 1; $d <= $calEnd->day; $d++) {
        if (\Carbon\Carbon::create($calYear, $calMonth, $d)->dayOfWeek === 0) {
            $noClassDays[] = $d;
        }
    }
    $isLatestMonth = $isCurrentMonth;

    $totalRecords = isset($records) ? $records->count() : 0;
    $greetHour = now()->hour;
    $greeting = $greetHour < 12 ? 'Good Morning' : ($greetHour < 17 ? 'Good Afternoon' : 'Good Evening');
@endphp

@if(session('error'))
<div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <i class="bi bi-exclamation-circle-fill"></i><span>{{ session('error') }}</span>
</div>
@endif

<!-- Active Warnings -->
@if(isset($activeWarnings) && $activeWarnings->count() > 0)
<div class="ent-alert ent-fade-up" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); margin-bottom: 24px; padding: 24px; border-radius: 16px;">
    <div class="d-flex align-items-center gap-3 mb-3">
        <div class="ent-alert-icon" style="background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.1);">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div class="ent-alert-body" style="flex: 1;">
            <div class="ent-alert-title" style="color: #fca5a5; font-weight: 700; font-size: 1.1rem; letter-spacing: -0.01em;">Action Required: Attendance Warning</div>
            <div class="ent-alert-text" style="color: #b39b82; font-size: 0.875rem;">You have {{ $activeWarnings->count() }} active warning(s). Please review your attendance immediately.</div>
        </div>
    </div>
    
    <div class="d-flex flex-column gap-3 mb-4">
        @foreach($activeWarnings as $warning)
        <div style="background: rgba(0,0,0,0.3); padding: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); transition: all 0.2s ease;">
            <div class="d-flex justify-content-between mb-2">
                <span style="color: #fca5a5; font-weight: 700; font-size: 0.875rem;"><i class="bi bi-exclamation-triangle-fill me-2" style="opacity: 0.8;"></i>{{ $warning->subject_code }}</span>
                <span style="color: #b39b82; font-size: 0.75rem;">{{ $warning->created_at->diffForHumans() }}</span>
            </div>
            <div style="font-size: 0.875rem; color: #d6b67b; line-height: 1.6;">{{ $warning->message }}</div>
        </div>
        @endforeach
    </div>
    
    <div class="d-flex gap-3 flex-wrap">
        <a href="{{ route('excuses') }}" class="ent-btn ent-btn-primary" style="background: linear-gradient(135deg, #dc2626, #b91c1c); border: 1px solid rgba(239,68,68,0.4); border-radius: 10px;">
            <i class="bi bi-file-text-fill"></i> Submit Excuse
        </a>
        <a href="{{ route('attendance.records') }}" class="ent-btn ent-btn-secondary" style="color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); border-radius: 10px;">
            View Records
        </a>
    </div>
</div>
@endif

<style>
    @media (max-width: 768px) {
        .hero-banner { padding: 16px !important; border-radius: 16px !important; box-shadow: none !important; }
        .hero-banner h1 { font-size: 1.5rem !important; margin-bottom: 4px !important; }
        .subject-stat-card { padding: 16px !important; box-shadow: none !important; border-radius: 12px !important; }
        .subject-stat-card .rate-text { font-size: 1.4rem !important; }
        .subject-stat-card .bg-glow { display: none !important; }
        .att-cal-wrap { border-radius: 12px !important; border: 1px solid rgba(255,255,255,0.03) !important; background: rgba(0,0,0,0.08) !important; }
        .att-cal-grid { padding: 0 8px 12px !important; gap: 2px !important; }
        .att-cal-cell { border-radius: 6px !important; }
        .att-cal-stats { padding: 10px 8px !important; }
        .att-cal-stat { border: none !important; padding: 4px 8px !important; background: transparent !important; }
    }
</style>

<!-- Hero Banner -->
<div class="premium-hero-card mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
        <div class="d-flex align-items-center gap-3">
            <div>
                <div style="color: var(--gold); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 4px;">{{ $greeting }}</div>
                <h1 style="color: #ffffff; font-weight: 800; margin: 0 0 6px 0; font-size: clamp(1.4rem, 5vw, 2.2rem); line-height: 1.1; letter-spacing: -0.5px;">{{ Auth::user()->name }}</h1>
                <div style="color: #b39b82; font-size: 0.88rem; font-weight: 500;">
                    {{ Auth::user()->course }} — Year {{ Auth::user()->year_level }}, Sem {{ Auth::user()->semester }}
                </div>
                <div class="mt-2 d-flex gap-2 flex-wrap align-items-center">
                    @php
                        $hasFingerprint = Auth::user()->webauthnCredentials()->exists();
                    @endphp
                    @if($hasFingerprint)
                        <a href="{{ route('settings') }}#tab-fingerprint" style="text-decoration:none;">
                            <span class="modern-chip modern-chip-present" title="Biometric Authentication Enabled">
                                <i class="bi bi-fingerprint"></i> Biometric Verified
                            </span>
                        </a>
                    @else
                        <a href="{{ route('settings') }}#tab-fingerprint" onclick="localStorage.setItem('active_settings_tab', 'fingerprint');" style="text-decoration:none;">
                            <span class="modern-chip modern-chip-gold" title="Click to register device fingerprint">
                                <i class="bi bi-fingerprint"></i> Set up Fingerprint
                            </span>
                        </a>
                    @endif
                </div>
                <!-- Mobile-only compact CTA -->
                <div class="d-md-none mt-3 d-flex gap-2">
                    <button type="button" onclick="openStudentScanner()" class="btn-modern-primary" style="padding: 8px 16px; font-size: 0.82rem;">
                        <i class="bi bi-qr-code-scan"></i> Scan QR
                    </button>
                    <a href="{{ route('excuses') }}" class="btn-modern-glass" style="padding: 8px 14px; font-size: 0.8rem;">
                        <i class="bi bi-envelope-paper-fill"></i> Excuse
                    </a>
                </div>
            </div>
        </div>
        <!-- Desktop: Clock + CTA (hidden on mobile) -->
        <div class="d-none d-md-flex flex-column gap-2" style="min-width: 250px;">
            <div class="hero-clock-pill" style="align-items: center; text-align: center; width: 100%;">
                <div class="hero-clock-time" style="justify-content: center;">
                    <i class="bi bi-clock"></i> <span id="studentClock">{{ now()->format('h:i A') }}</span>
                </div>
                <div class="hero-clock-date">{{ now()->format('l, F j, Y') }}</div>
            </div>
            <button type="button" onclick="openStudentScanner()" class="btn-modern-primary w-100 justify-content-center" style="padding: 12px; font-size: 0.95rem;">
                <i class="bi bi-qr-code-scan"></i> Scan Attendance QR
            </button>
            <a href="{{ route('excuses') }}" class="btn-modern-glass w-100 justify-content-center" style="padding: 10px; font-size: 0.88rem;">
                <i class="bi bi-envelope-paper-fill"></i> Submit Excuse / Leave
            </a>
        </div>
    </div>
</div>

<!-- Skeleton Stats -->
<div class="row g-3 mb-4" id="skelStats">
    <div class="col-md-3 col-6"><x-skeleton type="stat" /></div>
    <div class="col-md-3 col-6"><x-skeleton type="stat" /></div>
    <div class="col-md-3 col-6"><x-skeleton type="stat" /></div>
    <div class="col-md-3 col-6"><x-skeleton type="stat" /></div>
</div>

<!-- Quick Stats -->
<div class="ent-grid ent-grid-4 ent-mb-lg ent-fade-up ent-delay-2" id="realStats" style="display:none; gap:20px; margin-bottom:24px;">
    <x-card type="kpi" accent="success" label="Present" value="{{ $totalPresent ?? 0 }}" icon="bi bi-check-circle-fill" />
    <x-card type="kpi" accent="warning" label="Late" value="{{ $totalLate ?? 0 }}" icon="bi bi-clock-fill" />
    <x-card type="kpi" accent="danger" label="Absent" value="{{ $totalAbsent ?? 0 }}" icon="bi bi-x-circle-fill" />
    <x-card type="kpi" accent="gold" label="Subjects" value="{{ isset($subjects) ? count($subjects) : 0 }}" icon="bi bi-book-fill" />
</div>

<!-- Subject Attendance Breakdown -->
@if(isset($subjectStats) && $subjectStats->count() > 0)
<div class="mb-4">
    <x-card title="Subject Breakdown" icon="bi bi-bar-chart-fill">
        <x-slot name="headerActions">
            <span style="font-size: 0.75rem; color: #b39b82; font-weight: 600;">{{ $subjectStats->count() }} subjects</span>
        </x-slot>

        <style>
            .subject-stat-card:hover {
                transform: translateY(-2px);
                border-color: rgba(207,164,111,0.4) !important;
                box-shadow: 0 8px 25px rgba(0,0,0,0.4) !important;
            }
        </style>
        <div class="d-flex flex-column gap-3">
            @foreach($subjectStats as $stat)
                @php
                    $isNew = $stat->total == 0;
                    $rateColor = $isNew ? '#9ca3af' : ($stat->rate >= 90 ? '#4ade80' : ($stat->rate >= 75 ? '#fbbf24' : '#f87171'));
                    $rateBg = $isNew ? 'rgba(156,163,175,0.15)' : ($stat->rate >= 90 ? 'rgba(74,222,128,0.15)' : ($stat->rate >= 75 ? 'rgba(251,191,36,0.15)' : 'rgba(248,113,113,0.15)'));
                    $rateBorder = $isNew ? 'rgba(156,163,175,0.3)' : ($stat->rate >= 90 ? 'rgba(74,222,128,0.3)' : ($stat->rate >= 75 ? 'rgba(251,191,36,0.3)' : 'rgba(248,113,113,0.3)'));
                @endphp
                <div class="subject-stat-card" style="background: rgba(17, 9, 6, 0.7); border: 1px solid rgba(207,164,111,0.15); border-radius: 16px; padding: 20px; position: relative; overflow: hidden; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                    <!-- Background Glow -->
                    <div class="bg-glow" style="position: absolute; top: -40px; right: -40px; width: 120px; height: 120px; background: {{ $rateColor }}; border-radius: 50%; filter: blur(50px); opacity: 0.15; pointer-events: none;"></div>

                    <div class="d-flex justify-content-between align-items-start mb-3" style="position: relative; z-index: 2;">
                        <div>
                            <div style="font-weight: 800; color: #f3e7cd; font-size: 1.1rem; letter-spacing: -0.3px;">{{ $stat->name }}</div>
                            <div style="font-size: 0.8rem; color: #b39b82; margin-top: 6px; display: flex; align-items: center; gap: 6px;">
                                <span style="background: rgba(207,164,111,0.1); padding: 3px 8px; border-radius: 6px; font-weight: 700;">{{ $stat->code }}</span>
                                <span>•</span>
                                <span>{{ $stat->total }} Classes</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="rate-text" style="font-size: 1.7rem; font-weight: 900; color: {{ $rateColor }}; line-height: 1; text-shadow: 0 0 20px {{ $rateBg }};">{{ $stat->rate }}<span style="font-size: 1.1rem; opacity: 0.8;">%</span></div>
                            @if(!$isNew && $stat->rate < 75)
                                <div style="font-size: 0.7rem; color: #f87171; font-weight: 700; margin-top: 6px; display: inline-flex; align-items: center; gap: 4px; background: rgba(248,113,113,0.15); padding: 3px 10px; border-radius: 99px;">
                                    <i class="bi bi-exclamation-triangle-fill"></i> At Risk
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div style="position: relative; z-index: 2; height: 6px; background: rgba(255,255,255,0.06); border-radius: 99px; overflow: hidden; margin-bottom: 16px;">
                        <div style="height: 100%; width: 0%; background: {{ $rateColor }}; border-radius: 99px; transition: width 1.2s cubic-bezier(0.22, 1, 0.36, 1);" class="animated-progress" data-width="{{ $stat->rate }}%"></div>
                    </div>

                    <!-- Status Pills -->
                    <div class="d-flex gap-2 flex-wrap" style="position: relative; z-index: 2;">
                        <span style="font-size: 0.75rem; font-weight: 600; color: #4ade80; background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.25); padding: 4px 12px; border-radius: 99px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-check-circle-fill" style="font-size: 0.7rem;"></i> {{ $stat->present }} Present
                        </span>
                        @if($stat->late > 0)
                        <span style="font-size: 0.75rem; font-weight: 600; color: #fbbf24; background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.25); padding: 4px 12px; border-radius: 99px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-clock-fill" style="font-size: 0.7rem;"></i> {{ $stat->late }} Late
                        </span>
                        @endif
                        @if($stat->absent > 0)
                        <span style="font-size: 0.75rem; font-weight: 600; color: #f87171; background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.25); padding: 4px 12px; border-radius: 99px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-x-circle-fill" style="font-size: 0.7rem;"></i> {{ $stat->absent }} Absent
                        </span>
                        @endif
                        @if($stat->excused > 0)
                        <span style="font-size: 0.75rem; font-weight: 600; color: #60a5fa; background: rgba(96,165,250,0.1); border: 1px solid rgba(96,165,250,0.25); padding: 4px 12px; border-radius: 99px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-file-earmark-check-fill" style="font-size: 0.7rem;"></i> {{ $stat->excused }} Excused
                        </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    document.querySelectorAll('.animated-progress').forEach(function(el) {
                        el.style.width = el.getAttribute('data-width');
                    });
                }, 150);
            });
        </script>
    </x-card>
</div>
@endif

<div class="row g-4 mb-4">
    <!-- Today's Schedule -->
    <div class="col-lg-6">
        <x-card title="Today's Schedule" icon="bi bi-clock-history">
            <x-slot name="headerActions">
                <a href="{{ route('student.schedule') }}" class="btn btn-outline btn-sm">Full Schedule</a>
            </x-slot>
            @if(isset($todaySchedule) && $todaySchedule->count() > 0)
                <div class="d-flex flex-column gap-3">
                @foreach($todaySchedule as $item)
                    <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 16px; display: flex; justify-content: space-between; align-items: center;">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 4px; height: 40px; background: {{ $item->status === 'completed' ? '#4ade80' : ($item->status === 'ongoing' ? '#fbbf24' : ($item->status === 'missed' ? '#f87171' : 'var(--gold)')) }}; border-radius: 4px;"></div>
                            <div>
                                <div style="font-weight: 700; color: #f3e7cd; font-size: 1.1rem;">{{ $item->subject->name }}</div>
                                <div style="color: #b39b82; font-size: 0.85rem; margin-top: 4px;">
                                    {{ $item->start_time->format('g:i A') }} – {{ $item->end_time->format('g:i A') }} &nbsp;·&nbsp; {{ $item->subject->code }}
                                </div>
                            </div>
                        </div>
                        <div>
                            @if($item->status === 'completed') <x-badge type="present">Done</x-badge>
                            @elseif($item->status === 'ongoing') <x-badge type="late">Now</x-badge>
                            @elseif($item->status === 'missed') <x-badge type="absent">Missed</x-badge>
                            @else <x-badge type="info">Later</x-badge>
                            @endif
                        </div>
                    </div>
                @endforeach
                </div>
            @else
                <div class="empty-state text-center" style="padding: 40px 20px;">
                    <i class="bi bi-calendar-x" style="font-size: 3rem; color: #b39b82; opacity: 0.5;"></i>
                    <p style="color: #b39b82; font-size: 1rem; margin-top: 16px; font-weight: 600;">No classes scheduled today</p>
                </div>
            @endif
        </x-card>
    </div>

    <!-- School Calendar & Upcoming Events -->
    <div class="col-lg-6">
        <x-card title="Upcoming Events" icon="bi bi-calendar-event">
            <x-slot name="headerActions">
                <a href="{{ route('student.calendar') }}" class="btn btn-outline btn-sm">Full Calendar</a>
            </x-slot>
            @if(isset($calendarEvents) && $calendarEvents->count() > 0)
                <div class="d-flex flex-column gap-3">
                    @foreach($calendarEvents->take(5) as $event)
                        <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 16px; display: flex; justify-content: space-between; align-items: center;">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width: 48px; height: 48px; background: rgba(207,164,111,0.1); border: 1px solid rgba(207,164,111,0.2); border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 48px;">
                                    <span style="font-size: 0.7rem; font-weight: 800; color: #cfa46f; text-transform: uppercase; line-height: 1;">{{ \Carbon\Carbon::parse($event->date)->format('M') }}</span>
                                    <span style="font-size: 1.2rem; font-weight: 900; color: #f3e7cd; line-height: 1;">{{ \Carbon\Carbon::parse($event->date)->format('d') }}</span>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #f3e7cd; font-size: 1.1rem;">{{ $event->title }}</div>
                                    @if($event->type === 'announcement' && isset($event->author) && $event->author)
                                        @php
                                            $evtAuthorRole = $event->author_role ?? 'teacher';
                                            $evtIsAdmin = $evtAuthorRole === 'admin';
                                            $evtBadgeBg = $evtIsAdmin ? 'rgba(207,164,111,0.15)' : 'rgba(139,90,43,0.15)';
                                            $evtBadgeBorder = $evtIsAdmin ? 'rgba(207,164,111,0.35)' : 'rgba(139,90,43,0.35)';
                                            $evtBadgeColor = $evtIsAdmin ? '#CFA46F' : '#8B5A2B';
                                            $evtRoleLabel = $evtIsAdmin ? 'Administrator' : 'Instructor';
                                        @endphp
                                        <div style="display:flex;align-items:center;gap:6px;margin-top:4px;flex-wrap:wrap;">
                                            <span style="font-size:0.8rem;color:#b39b82;">{{ $event->author }}</span>
                                            <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:99px;font-size:0.65rem;font-weight:700;background:{{ $evtBadgeBg }};border:1px solid {{ $evtBadgeBorder }};color:{{ $evtBadgeColor }};">
                                                <span style="width:5px;height:5px;border-radius:50%;background:{{ $evtBadgeColor }};display:inline-block;"></span>
                                                {{ $evtRoleLabel }}
                                            </span>
                                        </div>
                                    @else
                                        <div style="color: #b39b82; font-size: 0.85rem; margin-top: 4px; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ strip_tags($event->content) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="d-none d-sm-block">
                                @if($event->type === 'holiday') <x-badge type="present">Holiday</x-badge>
                                @else <x-badge type="info">Event</x-badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state text-center" style="padding: 40px 20px;">
                    <i class="bi bi-calendar-x" style="font-size: 3rem; color: #b39b82; opacity: 0.5;"></i>
                    <p style="color: #b39b82; font-size: 1rem; margin-top: 16px; font-weight: 600;">No upcoming events or holidays</p>
                </div>
            @endif
        </x-card>
    </div>
</div>

<style>
    /* ─── Squircle Attendance Calendar ─────────────────────────── */
    .scal-card {
        background: #0f0a08;
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 28px;
        padding: 28px 30px;
        width: 100%;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7);
    }
    .scal-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    .scal-nav-btn {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #cfa46f;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
    }
    .scal-nav-btn:hover {
        background: rgba(207, 164, 111, 0.15);
        border-color: rgba(207, 164, 111, 0.4);
        color: #fff;
        transform: translateY(-2px);
    }
    .scal-nav-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #fdfbf7;
        letter-spacing: -0.02em;
    }
    .scal-weekdays {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        margin-bottom: 12px;
        background: rgba(255, 255, 255, 0.025);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 14px;
        padding: 12px 0;
    }
    .scal-wd {
        text-align: center;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #cfa46f;
    }
    .scal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
    }
    .scal-tile {
        height: 56px;
        min-height: 56px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        background: rgba(255, 255, 255, 0.025);
        position: relative;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        gap: 4px;
        user-select: none;
    }
    .scal-tile:hover {
        background: rgba(255, 255, 255, 0.07);
        border-color: rgba(207, 164, 111, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    }
    .scal-tile.empty {
        visibility: hidden;
        background: transparent !important;
        border-color: transparent !important;
        cursor: default;
        pointer-events: none;
    }
    .scal-num {
        font-size: 1.05rem;
        font-weight: 700;
        color: #f3ede4;
        line-height: 1;
    }
    .scal-tile.sunday .scal-num { color: #f87171; }
    .scal-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        opacity: 0.9;
    }
    .scal-tile.today {
        border: 2px solid #ffd166 !important;
        box-shadow: 0 0 16px rgba(255, 209, 102, 0.3), inset 0 0 10px rgba(255, 209, 102, 0.07) !important;
        background: rgba(255, 209, 102, 0.07) !important;
    }
    .scal-tile.today .scal-num {
        color: #ffd166;
    }
    .scal-tile.status-present {
        background: rgba(6, 78, 59, 0.5) !important;
        border-color: rgba(16, 185, 129, 0.35) !important;
    }
    .scal-tile.status-late {
        background: rgba(120, 80, 20, 0.5) !important;
        border-color: rgba(245, 158, 11, 0.35) !important;
    }
    .scal-tile.status-absent {
        background: rgba(100, 15, 15, 0.6) !important;
        border-color: rgba(239, 68, 68, 0.4) !important;
    }
    .scal-tile.status-event {
        background: rgba(60, 25, 120, 0.45) !important;
        border-color: rgba(139, 92, 246, 0.35) !important;
    }
    .scal-tile.status-holiday {
        background: rgba(5, 60, 50, 0.45) !important;
        border-color: rgba(74, 222, 128, 0.35) !important;
    }
    .scal-tile.status-exam {
        background: rgba(100, 20, 60, 0.5) !important;
        border-color: rgba(236, 72, 153, 0.35) !important;
    }

    /* Subject cards inside Day Summary Inspector */
    .subject-card {
        background: rgba(255,255,255,0.025);
        border-radius: 16px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        border: 1px solid rgba(255,255,255,0.05);
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .subject-card:hover {
        background: rgba(255,255,255,0.05);
        border-color: rgba(207,164,111,0.25);
    }
    .subject-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
        background: rgba(207,164,111,0.12);
        color: #cfa46f;
        border: 1px solid rgba(207,164,111,0.2);
    }
    .subject-card-info { flex: 1; min-width: 0; }
    .subject-card-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: #f3ede4;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .subject-card-time { font-size: 0.78rem; color: #b39b82; display: flex; align-items: center; gap: 5px; }
    .subject-card-badge {
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 800;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .subject-card-badge.present {
        background: rgba(74,222,128,0.15);
        color: #4ade80;
        border: 1px solid rgba(74,222,128,0.3);
    }
    .subject-card-badge.late {
        background: rgba(251,191,36,0.15);
        color: #fbbf24;
        border: 1px solid rgba(251,191,36,0.3);
    }
    .subject-card-badge.absent {
        background: rgba(248,113,113,0.15);
        color: #f87171;
        border: 1px solid rgba(248,113,113,0.3);
    }

    @media (max-width: 576px) {
        .scal-card { padding: 18px 14px; border-radius: 20px; }
        .scal-nav { margin-bottom: 14px; }
        .scal-nav-btn { width: 38px; height: 38px; font-size: 1rem; border-radius: 10px; }
        .scal-nav-title { font-size: 1.15rem; }
        .scal-weekdays { gap: 4px; padding: 10px 0; border-radius: 10px; margin-bottom: 8px; }
        .scal-wd { font-size: 0.72rem; }
        .scal-grid { gap: 5px; }
        .scal-tile { height: 48px; min-height: 48px; border-radius: 10px; }
        .scal-num { font-size: 0.95rem; }
        .scal-dot { width: 4px; height: 4px; }
    }
</style>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="scal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; border-radius:10px; background:rgba(207,164,111,0.15); display:flex; align-items:center; justify-content:center; color:#cfa46f; font-size:1.1rem;">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <span style="font-size:1.15rem; font-weight:800; color:#f3e7cd;">Attendance Calendar</span>
                </div>
                <a href="{{ route('attendance.records') }}" class="btn btn-outline btn-sm" style="border-radius:10px; padding:6px 14px; font-weight:700; font-size:0.8rem;">View Records</a>
            </div>

            {{-- Month Navigation --}}
            <div class="scal-nav">
                <a href="?cal_year={{ $prevMonth->year }}&cal_month={{ $prevMonth->month }}" class="scal-nav-btn">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <div class="scal-nav-title">{{ $calStart->format('F Y') }}</div>
                @if(!$isLatestMonth)
                    <a href="?cal_year={{ $nextMonth->year }}&cal_month={{ $nextMonth->month }}" class="scal-nav-btn">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @else
                    <div style="width:44px;"></div>
                @endif
            </div>

            {{-- Day Labels --}}
            <div class="scal-weekdays">
                <div class="scal-wd">S</div>
                <div class="scal-wd">M</div>
                <div class="scal-wd">T</div>
                <div class="scal-wd">W</div>
                <div class="scal-wd">T</div>
                <div class="scal-wd">F</div>
                <div class="scal-wd">S</div>
            </div>

            {{-- Calendar Grid --}}
            <div class="scal-grid">
                {{-- Empty cells before the 1st --}}
                @for($i = 0; $i < $startDow; $i++)
                    <div class="scal-tile empty"></div>
                @endfor

                @for($d = 1; $d <= $calEnd->day; $d++)
                    @php
                        $dayDate = \Carbon\Carbon::create($calYear, $calMonth, $d);
                        $dateKey = $dayDate->format('Y-m-d');
                        $isSunday = $dayDate->dayOfWeek === 0;
                        $isTodayCell = $isCurrentMonth && $d === $today;
                        $dayStatuses = $dayDotsMap[$d] ?? [];
                        $hasRecords = !empty($dayStatuses);

                        $cellStatus = '';
                        $dotColor = '';
                        if ($hasRecords) {
                            if (in_array('absent', $dayStatuses)) {
                                $cellStatus = 'status-absent';
                                $dotColor = '#ef4444';
                            } elseif (in_array('late', $dayStatuses)) {
                                $cellStatus = 'status-late';
                                $dotColor = '#f59e0b';
                            } elseif (in_array('present', $dayStatuses)) {
                                $cellStatus = 'status-present';
                                $dotColor = '#10b981';
                            }
                        }

                        $tileClasses = 'scal-tile';
                        if ($hasRecords) $tileClasses .= ' has-records';
                        if ($isTodayCell) $tileClasses .= ' today';
                        if ($isSunday) $tileClasses .= ' sunday';
                        if ($cellStatus) $tileClasses .= ' ' . $cellStatus;
                    @endphp
                    <div class="{{ $tileClasses }}"
                         @if($hasRecords) onclick="showAttDetail('{{ $dateKey }}', {{ $d }})" @endif
                         @if($hasRecords) title="Click to view details" @endif>
                        <span class="scal-num">{{ $d }}</span>
                        @if($hasRecords)
                            <div class="scal-dot" style="background: {{ $dotColor }};"></div>
                        @endif
                    </div>
                @endfor
            </div>

            {{-- Legend and Tips --}}
            <div class="row g-3 mt-3">
                <div class="col-md-6 col-12">
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 18px; padding: 16px 20px;">
                        <div style="font-size: 0.72rem; font-weight: 800; color: #cfa46f; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px;">Legend</div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; font-weight:600; color:#f3ede4;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#10b981; display:inline-block;"></span> Present
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; font-weight:600; color:#f3ede4;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#f59e0b; display:inline-block;"></span> Late
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; font-weight:600; color:#f3ede4;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#ef4444; display:inline-block;"></span> Absent
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; font-weight:600; color:#f3ede4;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#ec4899; display:inline-block;"></span> Exam
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; font-weight:600; color:#f3ede4;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#8b5cf6; display:inline-block;"></span> Event
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; font-weight:600; color:#f3ede4;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#4ade80; display:inline-block;"></span> Holiday
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 18px; padding: 16px 20px;">
                        <div style="font-size: 0.72rem; font-weight: 800; color: #cfa46f; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px; display:flex; align-items:center; gap:6px;">
                            <i class="bi bi-lightbulb-fill"></i> Calendar Tips
                        </div>
                        <ul style="margin: 0; padding-left: 18px; font-size: 0.78rem; color: #b39b82; line-height: 1.6;">
                            <li>Tap any <strong>day tile</strong> to view your subjects and attendance for that day.</li>
                            <li>Tile color shows your dominant attendance status for the day.</li>
                            <li>The <strong>golden border</strong> marks today.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Day Summary Inspector Modal ───────────────────────────────────────────────── --}}
<div class="modal fade" id="daySummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background:#0f0a08; border:1px solid rgba(255,255,255,0.1); border-radius:24px; box-shadow:0 30px 80px rgba(0,0,0,0.8);">
            <div class="d-flex justify-content-between align-items-start px-4 pt-4 pb-3 border-bottom position-relative" style="border-color:rgba(255,255,255,0.06)!important;">
                <div>
                    <h3 style="font-weight:800;font-size:1.25rem;color:#f3ede4;margin:0;padding-right:24px;" id="daySummaryTitle">Date</h3>
                    <div style="font-size:0.85rem;color:#b39b82;display:flex;align-items:center;gap:8px;margin-top:6px;" id="daySummarySubtitle">
                        <span id="daySummaryStatusDot" style="width:8px;height:8px;border-radius:50%;display:inline-block;"></span>
                        <span id="daySummaryStatusText" style="font-weight:600;">Status</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="position:absolute;top:20px;right:20px;"></button>
            </div>
            <div class="px-4 pb-4 pt-2">
                <div style="font-size:0.75rem;font-weight:800;color:#cfa46f;text-transform:uppercase;letter-spacing:0.08em;margin:16px 0 12px 0;" id="daySummarySectionTitle">Subjects Breakdown</div>
                <div id="daySummaryContent" class="d-flex flex-column gap-2">
                    <!-- dynamically populated -->
                </div>
                <div style="font-size:0.75rem;color:#8f826f;text-align:center;margin-top:18px;">
                    <i class="bi bi-info-circle me-1"></i> All classes & attendance entries for this day are listed above.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var attCalendarData = @json($calendarJson);
let dayModalInstance = null;

function showAttDetail(dateKey, day) {
    const titleEl = document.getElementById('daySummaryTitle');
    const subText = document.getElementById('daySummaryStatusText');
    const subDot = document.getElementById('daySummaryStatusDot');
    const contentEl = document.getElementById('daySummaryContent');

    const dt = new Date(dateKey + 'T00:00:00');
    titleEl.textContent = dt.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });

    const records = attCalendarData[dateKey] || [];
    if (records.length === 0) {
        subDot.style.background = '#8f826f';
        subText.textContent = 'No attendance recorded';
        contentEl.innerHTML = `
            <div style="text-align:center; padding: 32px 16px; color:#8f826f; background:rgba(255,255,255,0.02); border-radius:14px; border:1px dashed rgba(255,255,255,0.06);">
                <i class="bi bi-calendar-x" style="font-size:2rem; display:block; margin-bottom:8px; opacity:0.5; color:#cfa46f;"></i>
                <div style="font-weight:600; font-size:0.9rem; color:#f3ede4;">No records for this date</div>
                <div style="font-size:0.78rem; margin-top:4px;">Enjoy your free time or check schedule!</div>
            </div>`;
    } else {
        const hasAbsent = records.some(r => r.status === 'Absent');
        const hasLate = records.some(r => r.status === 'Late');
        const allPresent = records.every(r => r.status === 'Present');

        if (hasAbsent) {
            subDot.style.background = '#ef4444';
            subText.textContent = 'Absent in ' + records.filter(r => r.status === 'Absent').length + ' class(es)';
        } else if (hasLate) {
            subDot.style.background = '#f59e0b';
            subText.textContent = 'Late in ' + records.filter(r => r.status === 'Late').length + ' class(es)';
        } else if (allPresent) {
            subDot.style.background = '#10b981';
            subText.textContent = '100% Present (' + records.length + ' class' + (records.length > 1 ? 'es' : '') + ')';
        } else {
            subDot.style.background = '#3b82f6';
            subText.textContent = records.length + ' classes attended';
        }

        let html = '';
        records.forEach(r => {
            const statusClass = (r.status || 'Present').toLowerCase();
            const statusIcon = statusClass === 'present' ? 'bi-check-circle-fill' : (statusClass === 'late' ? 'bi-clock-fill' : 'bi-x-circle-fill');
            html += `
                <div class="subject-card">
                    <div class="subject-card-icon">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                    <div class="subject-card-info">
                        <div class="subject-card-title">${r.subject || 'Subject'}</div>
                        <div style="font-size:0.75rem; color:#cfa46f; font-weight:700; margin-bottom:4px;">${r.code || ''}</div>
                        ${r.time_in ? `<div class="subject-card-time"><i class="bi bi-clock"></i> Clock-in: <strong>${r.time_in}</strong></div>` : ''}
                    </div>
                    <span class="subject-card-badge ${statusClass}">
                        <i class="bi ${statusIcon} me-1"></i>${r.status}
                    </span>
                </div>
            `;
        });
        contentEl.innerHTML = html;
    }

    if (!dayModalInstance) {
        dayModalInstance = new bootstrap.Modal(document.getElementById('daySummaryModal'));
    }
    dayModalInstance.show();
    if (window.triggerHaptic) window.triggerHaptic('light');
}
</script>

<!-- QR Camera Scanner Modal -->
<div id="studentScannerModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(16px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.12); border-radius: 24px; max-width: 460px; width: 100%; padding: 28px 24px; color: white; box-shadow: 0 25px 60px rgba(0,0,0,0.5); text-align: center; position: relative;">
        <button type="button" onclick="closeStudentScanner()" style="position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.1); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
            <i class="bi bi-x-lg"></i>
        </button>
        <div style="width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #800000, #991b1b); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 14px; box-shadow: 0 6px 18px rgba(128,0,0,0.4);">
            <i class="bi bi-qr-code-scan"></i>
        </div>
        <h4 style="font-weight: 800; font-size: 1.25rem; margin-bottom: 4px;">Scan Attendance QR</h4>
        <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 18px;">Point your camera at the teacher's classroom QR code</p>

        <div id="scannerVideoContainer" style="border-radius: 16px; overflow: hidden; background: #000; position: relative; min-height: 250px; display: flex; align-items: center; justify-content: center; border: 2px dashed rgba(207,164,111,0.4);">
            <div id="reader" style="width: 100%;"></div>
            <div id="scannerFallbackNotice" style="display: none; padding: 20px; color: #94a3b8; font-size: 0.85rem;">
                <i class="bi bi-camera-video-off" style="font-size: 2rem; display: block; margin-bottom: 8px; color: #f87171;"></i>
                Camera access unavailable. Paste the QR link or token below.
            </div>
        </div>

        <div class="mt-3 text-start">
            <label style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Or Enter Token / URL Manually</label>
            <div class="input-group mt-1">
                <input type="text" id="manualQrInput" class="form-control" placeholder="Paste scan URL or token..." style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.12); color: white; border-radius: 12px 0 0 12px; font-size: 0.85rem;">
                <button type="button" class="btn" style="background: #800000; color: white; border-radius: 0 12px 12px 0; font-weight: 700; padding: 0 16px;" onclick="submitManualQr()">
                    Go
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrScanner = null;

function openStudentScanner() {
    const modal = document.getElementById('studentScannerModal');
    modal.style.display = 'flex';
    document.getElementById('scannerFallbackNotice').style.display = 'none';

    try {
        if (typeof Html5Qrcode !== 'undefined') {
            html5QrScanner = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 220, height: 220 } };
            html5QrScanner.start({ facingMode: "environment" }, config, onQrScanSuccess)
                .catch(err => {
                    console.warn("Camera start failed, showing manual input fallback:", err);
                    document.getElementById('scannerFallbackNotice').style.display = 'block';
                });
        } else {
            document.getElementById('scannerFallbackNotice').style.display = 'block';
        }
    } catch (e) {
        document.getElementById('scannerFallbackNotice').style.display = 'block';
    }
}

function closeStudentScanner() {
    const modal = document.getElementById('studentScannerModal');
    modal.style.display = 'none';
    if (html5QrScanner) {
        html5QrScanner.stop().then(() => {
            html5QrScanner.clear();
        }).catch(() => {});
        html5QrScanner = null;
    }
}

function onQrScanSuccess(decodedText) {
    if (html5QrScanner) {
        html5QrScanner.stop().catch(() => {});
    }
    
    // Check if decoded text is a full URL or just a token
    if (decodedText.startsWith('http://') || decodedText.startsWith('https://')) {
        window.location.href = decodedText;
    } else {
        window.location.href = '/qr/scan/' + encodeURIComponent(decodedText.trim());
    }
}

function submitManualQr() {
    const val = (document.getElementById('manualQrInput')?.value || '').trim();
    if (!val) return;
    onQrScanSuccess(val);
}
</script>

<script>
// ── Skeleton → Content Reveal ──
document.addEventListener('DOMContentLoaded', function() {
    var skelStats = document.getElementById('skelStats');
    var realStats = document.getElementById('realStats');
    if (skelStats && realStats) { skelStats.style.display = 'none'; realStats.style.display = ''; }
});

// ── Real-time Clock ──
(function() {
    function tick() {
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        const short = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
        const clockEl = document.getElementById('studentClock');
        if (clockEl) clockEl.textContent = short;
    }
    setInterval(tick, 1000);
})();
</script>
@endsection