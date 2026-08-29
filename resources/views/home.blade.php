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
                    <button type="button" onclick="openStudentScanner()" class="btn-modern-primary flex-fill justify-content-center" style="padding: 10px 16px; font-size: 0.85rem; font-weight: 700; border-radius: 12px;">
                        <i class="bi bi-qr-code-scan me-1"></i> Scan QR / Code
                    </button>
                    <a href="{{ route('excuses') }}" class="btn-modern-glass flex-fill justify-content-center" style="padding: 10px 14px; font-size: 0.85rem; font-weight: 600; border-radius: 12px;">
                        <i class="bi bi-envelope-paper-fill me-1"></i> Excuse
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

<!-- QR or Code Attendance Modal -->
<div id="studentScannerModal" class="scanner-modal-backdrop" style="display: none;">
    <div class="scanner-modal-card">
        
        <!-- Header & Top Floating Bar -->
        <div class="scanner-top-bar">
            <!-- Mode Switcher Tabs: QR or Code -->
            <div class="scanner-mode-switcher">
                <button type="button" id="tabScanMode" class="scanner-mode-tab active" onclick="switchScannerMode('scan')">
                    <i class="bi bi-qr-code-scan me-1"></i> Scan QR
                </button>
                <button type="button" id="tabCodeMode" class="scanner-mode-tab" onclick="switchScannerMode('code')">
                    <i class="bi bi-key-fill me-1"></i> Enter Code
                </button>
            </div>
            
            <div class="scanner-top-actions">
                <button type="button" id="torchCameraBtn" onclick="toggleTorch()" class="scanner-icon-btn" title="Toggle Flashlight">
                    <i class="bi bi-lightning-charge"></i>
                </button>
                <button type="button" id="flipCameraBtn" onclick="toggleCameraFacing()" class="scanner-icon-btn" title="Flip Camera">
                    <i class="bi bi-camera-reverse"></i>
                </button>
                <button type="button" onclick="closeStudentScanner()" class="scanner-icon-btn close-btn" title="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <!-- Mode 1: Camera Scanner View -->
        <div id="scannerActiveView" class="scanner-active-content">
            <div class="scanner-hero-heading">
                <h4 class="scanner-title">Scan Attendance QR</h4>
                <p class="scanner-sub">Align the teacher's classroom QR code within the frame</p>
            </div>

            <!-- Viewfinder Area with Glowing Corner Reticles -->
            <div id="scannerVideoContainer" class="scanner-viewfinder-wrapper">
                <div id="reader" class="scanner-reader-feed"></div>
                
                <!-- 4 Corner Reticle Accents -->
                <div class="reticle-corner top-left"></div>
                <div class="reticle-corner top-right"></div>
                <div class="reticle-corner bottom-left"></div>
                <div class="reticle-corner bottom-right"></div>

                <!-- Laser scanning beam -->
                <div id="scannerLaser" class="scanner-laser-line"></div>

                <!-- Guidance Pill -->
                <div class="scanner-guide-badge">
                    <i class="bi bi-viewfinder me-1"></i> Point at screen
                </div>

                <!-- Processing Overlay -->
                <div id="scannerProcessingOverlay" class="scanner-processing-overlay" style="display: none;">
                    <div class="spinner-border text-warning mb-3" style="width: 3.2rem; height: 3.2rem; border-width: 3px;" role="status"></div>
                    <div class="processing-title">Recording Attendance...</div>
                    <div class="processing-sub">Verifying session & GPS proximity</div>
                </div>

                <!-- Fallback Notice (Permission Blocked / Unsupported) -->
                <div id="scannerFallbackNotice" class="scanner-fallback-box" style="display: none;">
                    <div class="fallback-icon-wrap">
                        <i class="bi bi-camera-video-off"></i>
                    </div>
                    <h5 class="fallback-title">Camera Inactive</h5>
                    <p id="scannerFallbackText" class="fallback-text">Please allow camera permissions or switch to Code entry.</p>
                    <div class="d-flex justify-content-center gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="requestCameraAgain()" style="border-radius: 12px; font-weight: 700;">
                            <i class="bi bi-arrow-repeat me-1"></i> Retry Camera
                        </button>
                        <button type="button" class="btn btn-sm btn-warning text-dark" onclick="switchScannerMode('code')" style="border-radius: 12px; font-weight: 700;">
                            <i class="bi bi-key-fill me-1"></i> Use Code
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick switch link to code -->
            <div class="mt-3 text-center">
                <button type="button" class="scanner-manual-toggle-btn" onclick="switchScannerMode('code')">
                    <i class="bi bi-key-fill text-warning me-1"></i> Or enter 6-digit Code instead
                </button>
            </div>
        </div>

        <!-- Mode 2: Direct Code / PIN Input View -->
        <div id="scannerCodeView" class="scanner-active-content" style="display: none; padding: 10px 0;">
            <div style="width: 60px; height: 60px; border-radius: 20px; background: linear-gradient(135deg, #cfa46f, #8c6d46); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: #181614; margin: 0 auto 14px; box-shadow: 0 8px 24px rgba(207,164,111,0.3);">
                <i class="bi bi-key-fill"></i>
            </div>
            
            <h4 class="scanner-title">Enter Attendance Code</h4>
            <p class="scanner-sub">Type the 6-digit attendance code displayed on the teacher's screen</p>

            <div class="code-entry-container my-4">
                <input type="text" id="directSessionCodeInput" class="code-entry-input" placeholder="849 201" maxlength="9" autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false" oninput="formatSessionCodeInput(this)" onkeydown="handleCodeKeydown(event)">
                <div class="code-entry-hint mt-2">
                    <i class="bi bi-shield-check text-warning me-1"></i> 6-digit session PIN or QR token
                </div>
            </div>

            <div class="d-flex flex-column gap-2">
                <button type="button" id="codeSubmitBtn" class="btn scanner-primary-action-btn w-100" onclick="submitDirectCode()">
                    <i class="bi bi-check2-circle me-1"></i> Record Attendance
                </button>
                <button type="button" class="btn scanner-secondary-action-btn w-100" onclick="switchScannerMode('scan')">
                    <i class="bi bi-camera-fill me-1"></i> Switch to Camera Scan
                </button>
            </div>
        </div>

        <!-- Result View State -->
        <div id="scannerResultView" class="scanner-result-content" style="display: none;">
            <div id="resultStatusIcon" class="result-status-icon-wrap"></div>
            
            <h4 id="resultTitle" class="result-headline"></h4>
            <p id="resultSubtitle" class="result-caption"></p>

            <!-- Result Details Box -->
            <div id="resultDetailsBox" class="result-summary-card">
                <div class="result-row">
                    <span class="result-label">Status</span>
                    <span id="resultBadge" class="badge"></span>
                </div>
                <div class="result-row">
                    <span class="result-label">Subject</span>
                    <span id="resultSubject" class="result-val highlight"></span>
                </div>
                <div class="result-row">
                    <span class="result-label">Instructor</span>
                    <span id="resultInstructor" class="result-val"></span>
                </div>
                <div class="result-row">
                    <span class="result-label">Section</span>
                    <span id="resultSection" class="result-val"></span>
                </div>
                <div class="result-row no-border">
                    <span class="result-label">Recorded At</span>
                    <span id="resultTimestamp" class="result-val text-gold"></span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 w-100">
                <button type="button" id="resultDoneBtn" onclick="finishScanAndRefresh()" class="btn scanner-primary-action-btn">
                    <i class="bi bi-check-lg me-1"></i> Back to Dashboard
                </button>
                <button type="button" id="resultRetryBtn" onclick="resetScannerView()" class="btn scanner-secondary-action-btn" style="display: none;">
                    <i class="bi bi-arrow-repeat me-1"></i> Try Again
                </button>
            </div>
        </div>

    </div>
</div>

<style>
/* ── STUDENT SCANNER & CODE MODAL STYLES ── */
.scanner-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(8, 8, 10, 0.88);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.scanner-modal-card {
    background: linear-gradient(180deg, #1f1b17 0%, #131211 100%);
    border: 1px solid rgba(207, 164, 111, 0.28);
    border-radius: 28px;
    max-width: 470px;
    width: 100%;
    padding: 22px 20px;
    color: #ffffff;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(207, 164, 111, 0.12);
    text-align: center;
    position: relative;
    overflow: hidden;
    animation: scannerCardEnter 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes scannerCardEnter {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

/* Mode Switcher (QR vs Code) */
.scanner-mode-switcher {
    display: inline-flex;
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(207, 164, 111, 0.2);
    border-radius: 99px;
    padding: 3px;
    gap: 3px;
}

.scanner-mode-tab {
    background: transparent;
    border: none;
    color: #b39b82;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 99px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.scanner-mode-tab.active {
    background: linear-gradient(135deg, #cfa46f, #8c6d46);
    color: #181614;
    box-shadow: 0 4px 12px rgba(207, 164, 111, 0.35);
}

/* Top Floating Bar */
.scanner-top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.scanner-top-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.scanner-icon-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #f3e7cd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.scanner-icon-btn:hover, .scanner-icon-btn:active {
    background: rgba(207, 164, 111, 0.2);
    color: #ffffff;
    border-color: rgba(207, 164, 111, 0.4);
    transform: scale(1.06);
}

.scanner-icon-btn.active-torch {
    background: #cfa46f;
    color: #181614;
    border-color: #ffd700;
    box-shadow: 0 0 16px rgba(255, 215, 0, 0.6);
}

.scanner-icon-btn.close-btn:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border-color: rgba(239, 68, 68, 0.4);
}

/* Heading */
.scanner-hero-heading {
    margin-bottom: 14px;
}

.scanner-title {
    font-weight: 800;
    font-size: 1.25rem;
    color: #ffffff;
    margin-bottom: 2px;
    letter-spacing: -0.02em;
}

.scanner-sub {
    color: #b39b82;
    font-size: 0.82rem;
    margin-bottom: 0;
}

/* Code Entry Panel */
.code-entry-container {
    background: rgba(0, 0, 0, 0.4);
    border: 1.5px solid rgba(207, 164, 111, 0.35);
    border-radius: 22px;
    padding: 20px 16px;
    box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.6);
}

.code-entry-input {
    background: rgba(0, 0, 0, 0.6) !important;
    border: 2px solid rgba(207, 164, 111, 0.4) !important;
    color: #ffd700 !important;
    font-family: 'Consolas', 'Courier New', monospace !important;
    font-size: 2.2rem !important;
    font-weight: 900 !important;
    letter-spacing: 8px !important;
    text-align: center !important;
    border-radius: 16px !important;
    padding: 12px 10px !important;
    width: 100% !important;
    text-transform: uppercase !important;
    box-shadow: 0 0 16px rgba(207, 164, 111, 0.15) !important;
    transition: all 0.2s !important;
}

.code-entry-input:focus {
    border-color: #ffd700 !important;
    box-shadow: 0 0 24px rgba(255, 215, 0, 0.35) !important;
    outline: none !important;
}

.code-entry-hint {
    font-size: 0.78rem;
    color: #b39b82;
}

/* Viewfinder Area */
.scanner-viewfinder-wrapper {
    position: relative;
    border-radius: 24px;
    overflow: hidden;
    background: #000000;
    min-height: 270px;
    max-height: 320px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1.5px solid rgba(207, 164, 111, 0.35);
    box-shadow: inset 0 0 35px rgba(0, 0, 0, 0.85);
}

.scanner-reader-feed {
    width: 100% !important;
}

.scanner-reader-feed video {
    border-radius: 20px !important;
    object-fit: cover !important;
    width: 100% !important;
    max-height: 310px !important;
}

.scanner-reader-feed __scan_region__ {
    border: none !important;
}

/* Reticle Corner Markers */
.reticle-corner {
    position: absolute;
    width: 26px;
    height: 26px;
    border-color: #cfa46f;
    border-style: solid;
    border-width: 0;
    z-index: 12;
    pointer-events: none;
    filter: drop-shadow(0 0 6px rgba(207, 164, 111, 0.8));
}

.reticle-corner.top-left {
    top: 24px;
    left: 24px;
    border-top-width: 3.5px;
    border-left-width: 3.5px;
    border-top-left-radius: 12px;
}

.reticle-corner.top-right {
    top: 24px;
    right: 24px;
    border-top-width: 3.5px;
    border-right-width: 3.5px;
    border-top-right-radius: 12px;
}

.reticle-corner.bottom-left {
    bottom: 24px;
    left: 24px;
    border-bottom-width: 3.5px;
    border-left-width: 3.5px;
    border-bottom-left-radius: 12px;
}

.reticle-corner.bottom-right {
    bottom: 24px;
    right: 24px;
    border-bottom-width: 3.5px;
    border-right-width: 3.5px;
    border-bottom-right-radius: 12px;
}

/* Laser Scan Line */
.scanner-laser-line {
    position: absolute;
    left: 12%;
    right: 12%;
    height: 3px;
    background: linear-gradient(90deg, transparent, #cfa46f 30%, #ffd700 50%, #cfa46f 70%, transparent);
    box-shadow: 0 0 16px #ffd700, 0 0 30px rgba(207, 164, 111, 0.6);
    z-index: 15;
    animation: modernLaserScan 2s ease-in-out infinite;
    pointer-events: none;
}

@keyframes modernLaserScan {
    0% { top: 18%; opacity: 0.3; }
    50% { top: 78%; opacity: 1; }
    100% { top: 18%; opacity: 0.3; }
}

/* Guidance Badge */
.scanner-guide-badge {
    position: absolute;
    bottom: 12px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(18, 16, 14, 0.75);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #f3e7cd;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 99px;
    z-index: 16;
    pointer-events: none;
}

/* Processing Overlay */
.scanner-processing-overlay {
    position: absolute;
    inset: 0;
    background: rgba(14, 13, 12, 0.9);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    z-index: 25;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.processing-title {
    font-weight: 800;
    color: #ffffff;
    font-size: 1rem;
    letter-spacing: -0.01em;
}

.processing-sub {
    font-size: 0.78rem;
    color: #b39b82;
    margin-top: 2px;
}

/* Fallback Notice */
.scanner-fallback-box {
    padding: 24px 16px;
    color: #b39b82;
    text-align: center;
    z-index: 10;
}

.fallback-icon-wrap {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
}

.fallback-title {
    font-weight: 700;
    color: #ffffff;
    font-size: 1rem;
    margin-bottom: 2px;
}

.fallback-text {
    font-size: 0.8rem;
    color: #b39b82;
    max-width: 250px;
    margin: 0 auto;
}

.scanner-manual-toggle-btn {
    background: none;
    border: none;
    color: #cfa46f;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    padding: 6px 12px;
    border-radius: 8px;
    transition: all 0.2s;
}

.scanner-manual-toggle-btn:hover {
    color: #ffd700;
    background: rgba(207, 164, 111, 0.1);
}

/* Result View */
.scanner-result-content {
    padding: 8px 0 4px;
    text-align: center;
}

.result-status-icon-wrap {
    width: 74px;
    height: 74px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.3rem;
    margin: 0 auto 14px;
    animation: resultPop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes resultPop {
    0% { transform: scale(0.4); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.result-headline {
    font-weight: 800;
    font-size: 1.3rem;
    letter-spacing: -0.02em;
    margin-bottom: 4px;
}

.result-caption {
    color: #b39b82;
    font-size: 0.86rem;
    margin-bottom: 18px;
    line-height: 1.45;
}

.result-summary-card {
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    padding: 14px 18px;
    margin-bottom: 20px;
    text-align: left;
}

.result-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.result-row.no-border {
    border-bottom: none;
    padding-bottom: 2px;
}

.result-label {
    font-size: 0.75rem;
    color: #b39b82;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.result-val {
    font-size: 0.86rem;
    color: #f3e7cd;
    font-weight: 600;
    text-align: right;
}

.result-val.highlight {
    color: #ffffff;
    font-weight: 700;
}

.result-val.text-gold {
    color: #cfa46f;
    font-weight: 700;
}

.scanner-primary-action-btn {
    background: linear-gradient(135deg, #cfa46f, #8c6d46) !important;
    color: #181614 !important;
    font-weight: 800 !important;
    padding: 13px !important;
    border-radius: 16px !important;
    font-size: 0.95rem !important;
    border: none !important;
    box-shadow: 0 8px 24px rgba(207, 164, 111, 0.3) !important;
    transition: all 0.2s !important;
}

.scanner-secondary-action-btn {
    background: rgba(255, 255, 255, 0.08) !important;
    border: 1px solid rgba(255, 255, 255, 0.15) !important;
    color: #f3e7cd !important;
    font-weight: 700 !important;
    padding: 13px !important;
    border-radius: 16px !important;
    font-size: 0.95rem !important;
}

/* ── MOBILE ADAPTIVE VIEWPORT OPTIMIZATIONS ── */
@media (max-width: 640px) {
    .scanner-modal-backdrop {
        padding: 0;
        align-items: flex-end;
    }

    .scanner-modal-card {
        max-width: 100vw;
        width: 100vw;
        min-height: 92dvh;
        max-height: 96dvh;
        border-radius: 32px 32px 0 0;
        border-bottom: none;
        border-left: none;
        border-right: none;
        padding: 20px 18px max(24px, env(safe-area-inset-bottom)) 18px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .scanner-viewfinder-wrapper {
        min-height: 290px;
        max-height: 350px;
        border-radius: 26px;
    }

    .scanner-reader-feed video {
        max-height: 340px !important;
        border-radius: 24px !important;
    }

    .scanner-title {
        font-size: 1.35rem;
    }

    .code-entry-input {
        font-size: 2.4rem !important;
        letter-spacing: 6px !important;
        padding: 14px 10px !important;
    }

    .reticle-corner {
        width: 32px;
        height: 32px;
    }

    .reticle-corner.top-left { top: 20px; left: 20px; }
    .reticle-corner.top-right { top: 20px; right: 20px; }
    .reticle-corner.bottom-left { bottom: 20px; left: 20px; }
    .reticle-corner.bottom-right { bottom: 20px; right: 20px; }
}
</style>

<script nonce="{{ csp_nonce() }}" src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script nonce="{{ csp_nonce() }}">
let html5QrScanner = null;
let currentFacingMode = "environment";
let torchEnabled = false;
let studentGeoCoords = null;
let currentScannerMode = 'scan'; // 'scan' | 'code'

// Auto-capture GPS coords quietly for faster validation
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        pos => { studentGeoCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude, acc: pos.coords.accuracy }; },
        () => {},
        { enableHighAccuracy: true, timeout: 5000 }
    );
}

function switchScannerMode(mode) {
    currentScannerMode = mode;
    const tabScan = document.getElementById('tabScanMode');
    const tabCode = document.getElementById('tabCodeMode');
    const scanView = document.getElementById('scannerActiveView');
    const codeView = document.getElementById('scannerCodeView');
    const torchBtn = document.getElementById('torchCameraBtn');
    const flipBtn = document.getElementById('flipCameraBtn');

    if (mode === 'scan') {
        tabScan.classList.add('active');
        tabCode.classList.remove('active');
        scanView.style.display = 'block';
        codeView.style.display = 'none';
        torchBtn.style.display = 'flex';
        flipBtn.style.display = 'flex';
        startHtml5Scanner();
    } else {
        tabCode.classList.add('active');
        tabScan.classList.remove('active');
        scanView.style.display = 'none';
        codeView.style.display = 'block';
        torchBtn.style.display = 'none';
        flipBtn.style.display = 'none';

        // Stop camera while typing to save battery
        if (html5QrScanner) {
            html5QrScanner.stop().then(() => {
                html5QrScanner.clear();
            }).catch(() => {});
            html5QrScanner = null;
        }

        setTimeout(() => {
            const input = document.getElementById('directSessionCodeInput');
            if (input) input.focus();
        }, 100);
    }

    if (window.triggerHaptic) window.triggerHaptic('light');
}

function formatSessionCodeInput(el) {
    let val = el.value.replace(/[^0-9A-Za-z]/g, '').toUpperCase();
    if (val.length > 6) {
        val = val.substring(0, 6);
    }
    if (val.length > 3) {
        el.value = val.substring(0, 3) + ' ' + val.substring(3);
    } else {
        el.value = val;
    }

    // If 6 characters filled, trigger slight haptic
    if (val.length === 6 && window.triggerHaptic) {
        window.triggerHaptic('light');
    }
}

function handleCodeKeydown(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        submitDirectCode();
    }
}

function submitDirectCode() {
    const rawVal = (document.getElementById('directSessionCodeInput')?.value || '').trim();
    const cleanVal = rawVal.replace(/[^0-9A-Za-z]/g, '');
    
    if (!cleanVal) {
        alert('Please enter the 6-digit attendance code shown on the screen.');
        document.getElementById('directSessionCodeInput')?.focus();
        return;
    }

    onQrScanSuccess(cleanVal);
}

function openStudentScanner(initialMode = 'scan') {
    const modal = document.getElementById('studentScannerModal');
    modal.style.display = 'flex';
    resetScannerView();

    switchScannerMode(initialMode);
}

function startHtml5Scanner() {
    const fallbackNotice = document.getElementById('scannerFallbackNotice');
    fallbackNotice.style.display = 'none';

    try {
        if (typeof Html5Qrcode !== 'undefined') {
            if (html5QrScanner) {
                html5QrScanner.stop().catch(() => {}).finally(() => initScannerInstance());
            } else {
                initScannerInstance();
            }
        } else {
            showCameraError("Scanner library loading. Please switch to Enter Code mode.");
        }
    } catch (e) {
        showCameraError("Camera initialization failed: " + e.message);
    }
}

function initScannerInstance() {
    html5QrScanner = new Html5Qrcode("reader");
    const isMobile = window.innerWidth <= 640;
    const config = {
        fps: 15,
        qrbox: { width: isMobile ? 250 : 230, height: isMobile ? 250 : 230 },
        aspectRatio: 1.0
    };

    html5QrScanner.start(
        { facingMode: currentFacingMode },
        config,
        onQrScanSuccess
    ).then(() => {
        document.getElementById('scannerLaser').style.display = 'block';
    }).catch(err => {
        console.warn("Camera start failed:", err);
        showCameraError("Camera permission denied or camera not found. Allow camera access or enter the 6-digit code.");
    });
}

function showCameraError(msg) {
    document.getElementById('scannerFallbackNotice').style.display = 'block';
    document.getElementById('scannerFallbackText').textContent = msg;
    document.getElementById('scannerLaser').style.display = 'none';
}

function requestCameraAgain() {
    startHtml5Scanner();
}

function toggleCameraFacing() {
    currentFacingMode = currentFacingMode === "environment" ? "user" : "environment";
    startHtml5Scanner();
    if (window.triggerHaptic) window.triggerHaptic('light');
}

function toggleTorch() {
    if (!html5QrScanner) return;
    try {
        torchEnabled = !torchEnabled;
        const torchBtn = document.getElementById('torchCameraBtn');
        html5QrScanner.applyVideoConstraints({
            advanced: [{ torch: torchEnabled }]
        }).then(() => {
            if (torchEnabled) {
                torchBtn.classList.add('active-torch');
            } else {
                torchBtn.classList.remove('active-torch');
            }
        }).catch(() => {
            torchBtn.classList.remove('active-torch');
            alert('Torch / Flashlight is not supported on this camera device.');
        });
    } catch (e) {}
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

function resetScannerView() {
    document.getElementById('scannerActiveView').style.display = 'block';
    document.getElementById('scannerCodeView').style.display = 'none';
    document.getElementById('scannerResultView').style.display = 'none';
    document.getElementById('scannerProcessingOverlay').style.display = 'none';
    document.getElementById('directSessionCodeInput').value = '';
    
    if (currentScannerMode === 'scan' && !html5QrScanner && document.getElementById('studentScannerModal').style.display === 'flex') {
        startHtml5Scanner();
    }
}

async function onQrScanSuccess(decodedText) {
    if (html5QrScanner) {
        html5QrScanner.stop().catch(() => {});
    }

    document.getElementById('scannerProcessingOverlay').style.display = 'flex';
    document.getElementById('scannerLaser').style.display = 'none';

    // Play quick scan beep & haptic
    playScanBeep();
    if (window.triggerHaptic) window.triggerHaptic('medium');

    // Fetch fresh GPS if not present
    if (!studentGeoCoords && navigator.geolocation) {
        try {
            await new Promise((resolve) => {
                navigator.geolocation.getCurrentPosition(
                    pos => {
                        studentGeoCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude, acc: pos.coords.accuracy };
                        resolve();
                    },
                    () => resolve(),
                    { enableHighAccuracy: true, timeout: 3500 }
                );
            });
        } catch(e) {}
    }

    const payload = {
        token: decodedText.trim(),
        latitude: studentGeoCoords ? studentGeoCoords.lat : null,
        longitude: studentGeoCoords ? studentGeoCoords.lng : null,
        accuracy: studentGeoCoords ? studentGeoCoords.acc : null
    };

    try {
        const response = await fetch('{{ route("qr.scan.process") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        document.getElementById('scannerProcessingOverlay').style.display = 'none';

        if (response.ok && data.success) {
            renderScanSuccess(data);
        } else {
            renderScanError(data);
        }
    } catch (error) {
        document.getElementById('scannerProcessingOverlay').style.display = 'none';
        renderScanError({
            message: 'Network connection failed. Please verify your internet connection and try again.'
        });
    }
}

function renderScanSuccess(data) {
    document.getElementById('scannerActiveView').style.display = 'none';
    document.getElementById('scannerCodeView').style.display = 'none';
    document.getElementById('scannerResultView').style.display = 'block';

    const iconBox = document.getElementById('resultStatusIcon');
    const badge = document.getElementById('resultBadge');
    const title = document.getElementById('resultTitle');
    const subtitle = document.getElementById('resultSubtitle');
    const retryBtn = document.getElementById('resultRetryBtn');
    const doneBtn = document.getElementById('resultDoneBtn');

    retryBtn.style.display = 'none';
    doneBtn.style.display = 'block';

    if (data.already_clocked_in) {
        iconBox.style.background = 'rgba(59, 130, 246, 0.15)';
        iconBox.style.border = '2px solid rgba(59, 130, 246, 0.4)';
        iconBox.innerHTML = '<i class="bi bi-info-circle-fill" style="color: #60a5fa;"></i>';
        
        title.textContent = 'Already Clocked In';
        subtitle.textContent = data.message || 'You have already recorded your attendance for this class today.';
        
        badge.className = 'badge bg-info text-dark';
        badge.textContent = data.status || 'Present';
    } else {
        iconBox.style.background = 'rgba(16, 185, 129, 0.15)';
        iconBox.style.border = '2px solid rgba(16, 185, 129, 0.4)';
        iconBox.innerHTML = '<i class="bi bi-check2-circle" style="color: #34d399;"></i>';

        title.textContent = 'Attendance Recorded Successfully!';
        subtitle.textContent = `Your attendance has been recorded for ${data.subject || 'this class'}.`;

        const isPresent = (data.status || 'Present') === 'Present';
        badge.className = isPresent ? 'badge bg-success' : 'badge bg-warning text-dark';
        badge.textContent = data.status || 'Present';

        playSuccessChime();
        if (window.triggerHaptic) window.triggerHaptic('success');
    }

    document.getElementById('resultSubject').textContent = (data.subject || 'Subject') + (data.subject_code ? ' (' + data.subject_code + ')' : '');
    document.getElementById('resultInstructor').textContent = data.instructor || 'Instructor';
    document.getElementById('resultSection').textContent = data.section || 'Regular';
    document.getElementById('resultTimestamp').textContent = (data.date || '') + (data.time ? ' at ' + data.time : '');
}

function renderScanError(data) {
    document.getElementById('scannerActiveView').style.display = 'none';
    document.getElementById('scannerCodeView').style.display = 'none';
    document.getElementById('scannerResultView').style.display = 'block';

    const iconBox = document.getElementById('resultStatusIcon');
    const title = document.getElementById('resultTitle');
    const subtitle = document.getElementById('resultSubtitle');
    const retryBtn = document.getElementById('resultRetryBtn');
    const doneBtn = document.getElementById('resultDoneBtn');

    iconBox.style.background = 'rgba(239, 68, 68, 0.15)';
    iconBox.style.border = '2px solid rgba(239, 68, 68, 0.4)';
    iconBox.innerHTML = '<i class="bi bi-x-circle-fill" style="color: #f87171;"></i>';

    if (data.error_type === 'schedule_mismatch') {
        title.textContent = 'Schedule Mismatch';
    } else if (data.error_type === 'session_closed' || data.error_type === 'invalid_or_expired') {
        title.textContent = 'Code / QR Expired';
    } else if (data.error_type === 'outside_classroom') {
        title.textContent = 'Outside Classroom Range';
    } else {
        title.textContent = 'Unable to Record Attendance';
    }

    subtitle.textContent = data.message || 'The entered code or QR could not be processed. Please check with your instructor.';

    document.getElementById('resultDetailsBox').style.display = 'none';
    retryBtn.style.display = 'block';
    doneBtn.style.display = 'none';

    if (window.triggerHaptic) window.triggerHaptic('error');
}

function finishScanAndRefresh() {
    closeStudentScanner();
    window.location.reload();
}

function playScanBeep() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(800, ctx.currentTime);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
        osc.start();
        osc.stop(ctx.currentTime + 0.12);
    } catch(e) {}
}

function playSuccessChime() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(523.25, ctx.currentTime); // C5
        osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.1); // E5
        osc.frequency.setValueAtTime(783.99, ctx.currentTime + 0.2); // G5
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
        osc.start();
        osc.stop(ctx.currentTime + 0.5);
    } catch(e) {}
}

// Auto open scanner if directed with URL query
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open_scanner') === '1') {
        openStudentScanner();
    } else if (urlParams.get('open_code') === '1') {
        openStudentScanner('code');
    }
});
</script>

<script nonce="{{ csp_nonce() }}">
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