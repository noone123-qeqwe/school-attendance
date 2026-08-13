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

<!-- Hero Banner -->
<div class="mb-4" style="background: linear-gradient(135deg, rgba(32,20,15,0.9) 0%, rgba(20,10,5,0.95) 100%); border: 1px solid rgba(207,164,111,0.25); border-radius: 24px; padding: 24px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
    <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: linear-gradient(180deg, var(--gold) 0%, #8f6e4a 100%);"></div>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
        <div class="d-flex align-items-center gap-3">
            <div class="d-none d-md-block" style="font-size: 3.5rem; line-height: 1; filter: drop-shadow(0 4px 10px rgba(207,164,111,0.3));">🎓</div>
            <div>
                <div style="color: var(--gold); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 4px;">{{ $greeting }}</div>
                <h1 style="color: #f3e7cd; font-weight: 800; margin: 0 0 6px 0; font-size: clamp(1.4rem, 5vw, 2.2rem); line-height: 1.1;">{{ Auth::user()->name }}</h1>
                <div style="color: #b39b82; font-size: 0.85rem; font-weight: 500;">
                    {{ Auth::user()->course }} — Year {{ Auth::user()->year_level }}, Sem {{ Auth::user()->semester }}
                </div>
                @if(isset($totalAbsent) && $totalAbsent === 0 && isset($totalPresent) && $totalPresent > 0)
                    <div class="mt-2">
                        <span style="background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.1);"><i class="bi bi-star-fill"></i> Perfect Attendance</span>
                    </div>
                @elseif(isset($totalPresent) && $totalPresent > 3)
                    <div class="mt-2">
                        <span style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.1);"><i class="bi bi-fire"></i> {{ $totalPresent }} Class Streak</span>
                    </div>
                @endif
                <!-- Mobile-only compact CTA -->
                <div class="d-md-none mt-3">
                    <a href="{{ route('excuses') }}" style="display: inline-flex; align-items: center; gap: 6px; background: rgba(207,164,111,0.12); color: #cfa46f; border: 1px solid rgba(207,164,111,0.25); text-decoration:none; padding: 8px 16px; border-radius: 10px; font-size: 0.8rem; font-weight: 600; transition: all 0.2s;">
                        <i class="bi bi-envelope-paper-fill"></i> Submit Excuse
                    </a>
                </div>
            </div>
        </div>
        <!-- Desktop: Clock + CTA (hidden on mobile) -->
        <div class="d-none d-md-flex flex-column gap-3" style="min-width: 250px;">
            <div style="background: rgba(0,0,0,0.3); padding: 16px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); text-align: center;">
                <div style="color: var(--gold); font-size: 1.6rem; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 8px; font-variant-numeric: tabular-nums;">
                    <i class="bi bi-clock"></i> <span id="studentClock">{{ now()->format('h:i A') }}</span>
                </div>
                <div style="color: #b39b82; font-size: 0.85rem; margin-top: 4px; font-weight: 500;">{{ now()->format('l, F j, Y') }}</div>
            </div>
            <a href="{{ route('excuses') }}" class="ent-btn w-100 d-flex justify-content-center align-items-center" style="background: rgba(255,255,255,0.05); color: var(--gold); border: 1px solid rgba(207,164,111,0.25); text-decoration:none; padding: 12px; border-radius: 14px; transition: all 0.2s;">
                <i class="bi bi-envelope-paper-fill me-2"></i> Submit Excuse / Leave
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
<div class="ent-grid ent-grid-4 ent-mb-lg ent-fade-up ent-delay-2" id="realStats" style="display:none; gap:24px; margin-bottom:24px;">
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
                    <div style="position: absolute; top: -40px; right: -40px; width: 120px; height: 120px; background: {{ $rateColor }}; border-radius: 50%; filter: blur(50px); opacity: 0.15; pointer-events: none;"></div>

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
                            <div style="font-size: 1.7rem; font-weight: 900; color: {{ $rateColor }}; line-height: 1; text-shadow: 0 0 20px {{ $rateBg }};">{{ $stat->rate }}<span style="font-size: 1.1rem; opacity: 0.8;">%</span></div>
                            @if(!$isNew && $stat->rate < 75)
                                <div style="font-size: 0.7rem; color: #f87171; font-weight: 700; margin-top: 6px; display: inline-flex; align-items: center; gap: 4px; background: rgba(248,113,113,0.15); padding: 3px 10px; border-radius: 99px;">
                                    <i class="bi bi-exclamation-triangle-fill"></i> At Risk
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div style="position: relative; z-index: 2; height: 8px; background: rgba(255,255,255,0.06); border-radius: 99px; overflow: hidden; margin-bottom: 16px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.3);">
                        <div style="height: 100%; width: 0%; background: linear-gradient(90deg, {{ $rateColor }}, {{ $rateColor }}dd); border-radius: 99px; transition: width 1.2s cubic-bezier(0.22, 1, 0.36, 1); box-shadow: 0 0 10px {{ $rateColor }};" class="animated-progress" data-width="{{ $stat->rate }}%"></div>
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
    /* ── UNIFIED ATTENDANCE CALENDAR ── */
    .att-cal-wrap {
        background: rgba(0,0,0,0.15);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 20px;
        overflow: hidden;
    }
    .att-cal-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px 16px;
    }
    .att-cal-nav-btn {
        width: 36px; height: 36px;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.04);
        color: #cfa46f;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 0.85rem;
        transition: all 0.2s;
        text-decoration: none;
    }
    .att-cal-nav-btn:hover {
        background: rgba(207,164,111,0.12);
        border-color: rgba(207,164,111,0.3);
        color: #cfa46f;
    }
    .att-cal-month {
        font-size: 1.15rem;
        font-weight: 800;
        color: #f3e7cd;
        letter-spacing: -0.3px;
    }
    .att-cal-header {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        padding: 0 16px;
        margin-bottom: 6px;
    }
    .att-cal-header span {
        font-size: 0.7rem;
        font-weight: 800;
        color: #8f826f;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 6px 0;
    }
    .att-cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
        padding: 0 16px 16px;
    }
    .att-cal-cell {
        aspect-ratio: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        color: rgba(248,231,211,0.35);
        background: transparent;
        position: relative;
        cursor: default;
        transition: all 0.2s;
        gap: 3px;
    }
    .att-cal-cell.has-records {
        cursor: pointer;
    }
    .att-cal-cell.has-records:hover {
        transform: scale(1.08);
        z-index: 2;
    }
    .att-cal-cell.is-today {
        box-shadow: inset 0 0 0 2px rgba(207,164,111,0.4);
    }
    .att-cal-cell.is-sunday {
        color: rgba(248,231,211,0.2);
    }

    /* Status colors */
    .att-cal-cell.status-present {
        background: rgba(74,222,128,0.15);
        color: #4ade80;
        border: 1px solid rgba(74,222,128,0.25);
    }
    .att-cal-cell.status-late {
        background: rgba(251,191,36,0.15);
        color: #fbbf24;
        border: 1px solid rgba(251,191,36,0.25);
    }
    .att-cal-cell.status-absent {
        background: rgba(248,113,113,0.15);
        color: #f87171;
        border: 1px solid rgba(248,113,113,0.25);
    }
    .att-cal-cell.status-mixed {
        background: rgba(96,165,250,0.12);
        color: #60a5fa;
        border: 1px solid rgba(96,165,250,0.2);
    }

    /* Status dots row inside the cell */
    .att-cal-dots {
        display: flex;
        gap: 3px;
        justify-content: center;
    }
    .att-cal-dot {
        width: 5px; height: 5px;
        border-radius: 50%;
    }
    .att-cal-dot.present { background: #4ade80; }
    .att-cal-dot.late    { background: #fbbf24; }
    .att-cal-dot.absent  { background: #f87171; }

    /* Stats bar */
    .att-cal-stats {
        display: flex;
        gap: 6px;
        padding: 14px 20px;
        border-top: 1px solid rgba(255,255,255,0.06);
        flex-wrap: wrap;
        justify-content: center;
    }
    .att-cal-stat {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 5px 14px;
        border-radius: 99px;
        border: 1px solid;
    }
    .att-cal-stat.present {
        color: #4ade80;
        background: rgba(74,222,128,0.08);
        border-color: rgba(74,222,128,0.2);
    }
    .att-cal-stat.late {
        color: #fbbf24;
        background: rgba(251,191,36,0.08);
        border-color: rgba(251,191,36,0.2);
    }
    .att-cal-stat.absent {
        color: #f87171;
        background: rgba(248,113,113,0.08);
        border-color: rgba(248,113,113,0.2);
    }
    .att-cal-stat .stat-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
    }
    .att-cal-stat.present .stat-dot { background: #4ade80; }
    .att-cal-stat.late .stat-dot    { background: #fbbf24; }
    .att-cal-stat.absent .stat-dot  { background: #f87171; }

    /* Detail panel */
    .att-cal-detail {
        display: none;
        border-top: 1px solid rgba(255,255,255,0.06);
        padding: 20px;
        animation: attDetailSlide 0.25s ease;
    }
    .att-cal-detail.active { display: block; }
    @keyframes attDetailSlide {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .att-detail-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .att-detail-date {
        font-size: 1rem;
        font-weight: 800;
        color: #f3e7cd;
    }
    .att-detail-close {
        width: 28px; height: 28px;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.04);
        color: #8f826f;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        font-size: 0.75rem;
        transition: all 0.2s;
    }
    .att-detail-close:hover {
        background: rgba(248,113,113,0.15);
        border-color: rgba(248,113,113,0.3);
        color: #f87171;
    }
    .att-detail-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: 10px;
        background: rgba(0,0,0,0.2);
        border: 1px solid rgba(255,255,255,0.04);
        margin-bottom: 6px;
    }
    .att-detail-subject {
        font-weight: 700;
        font-size: 0.85rem;
        color: #f3e7cd;
    }
    .att-detail-code {
        font-size: 0.72rem;
        color: #8f826f;
        margin-top: 2px;
    }
    .att-detail-time {
        font-size: 0.72rem;
        color: #8f826f;
        margin-top: 1px;
    }
    .att-detail-badge {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 99px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
    }
    .att-detail-badge.present {
        color: #4ade80;
        background: rgba(74,222,128,0.12);
        border: 1px solid rgba(74,222,128,0.25);
    }
    .att-detail-badge.late {
        color: #fbbf24;
        background: rgba(251,191,36,0.12);
        border: 1px solid rgba(251,191,36,0.25);
    }
    .att-detail-badge.absent {
        color: #f87171;
        background: rgba(248,113,113,0.12);
        border: 1px solid rgba(248,113,113,0.25);
    }
    .att-detail-empty {
        text-align: center;
        padding: 24px;
        color: #8f826f;
        font-size: 0.85rem;
        font-weight: 600;
    }

    @media (max-width: 576px) {
        .att-cal-cell { font-size: 0.75rem; border-radius: 8px; }
        .att-cal-dot { width: 4px; height: 4px; }
        .att-cal-nav { padding: 16px 16px 12px; }
        .att-cal-grid { padding: 0 10px 12px; gap: 3px; }
        .att-cal-header { padding: 0 10px; }
        .att-cal-stats { padding: 10px 12px; gap: 4px; }
        .att-cal-stat { padding: 4px 10px; font-size: 0.7rem; }
    }
</style>

<div class="row g-4 mb-4">
    <div class="col-12">
        <x-card title="Attendance Calendar" icon="bi bi-calendar-check-fill">
            <x-slot name="headerActions">
                <a href="{{ route('attendance.records') }}" class="btn btn-outline btn-sm">View Records</a>
            </x-slot>

            <div class="att-cal-wrap">
                {{-- Month Navigation --}}
                <div class="att-cal-nav">
                    <a href="?cal_year={{ $prevMonth->year }}&cal_month={{ $prevMonth->month }}" class="att-cal-nav-btn">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <div class="att-cal-month">{{ $calStart->format('F Y') }}</div>
                    @if(!$isLatestMonth)
                        <a href="?cal_year={{ $nextMonth->year }}&cal_month={{ $nextMonth->month }}" class="att-cal-nav-btn">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    @else
                        <div style="width:36px;"></div>
                    @endif
                </div>

                {{-- Day Labels --}}
                <div class="att-cal-header">
                    <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                </div>

                {{-- Calendar Grid --}}
                <div class="att-cal-grid">
                    {{-- Empty cells before the 1st --}}
                    @for($i = 0; $i < $startDow; $i++)
                        <div class="att-cal-cell"></div>
                    @endfor

                    @for($d = 1; $d <= $calEnd->day; $d++)
                        @php
                            $dayDate = \Carbon\Carbon::create($calYear, $calMonth, $d);
                            $dateKey = $dayDate->format('Y-m-d');
                            $isSunday = $dayDate->dayOfWeek === 0;
                            $isTodayCell = $isCurrentMonth && $d === $today;
                            $dayStatuses = $dayDotsMap[$d] ?? [];
                            $hasRecords = !empty($dayStatuses);

                            // Determine the primary status for cell coloring
                            $cellStatus = '';
                            if ($hasRecords) {
                                $statusCount = count($dayStatuses);
                                if ($statusCount > 1) {
                                    $cellStatus = 'status-mixed';
                                } elseif (in_array('present', $dayStatuses)) {
                                    $cellStatus = 'status-present';
                                } elseif (in_array('late', $dayStatuses)) {
                                    $cellStatus = 'status-late';
                                } elseif (in_array('absent', $dayStatuses)) {
                                    $cellStatus = 'status-absent';
                                }
                            }

                            $cellClasses = 'att-cal-cell';
                            if ($hasRecords) $cellClasses .= ' has-records';
                            if ($isTodayCell) $cellClasses .= ' is-today';
                            if ($isSunday && !$hasRecords) $cellClasses .= ' is-sunday';
                            if ($cellStatus) $cellClasses .= ' ' . $cellStatus;
                        @endphp
                        <div class="{{ $cellClasses }}"
                             @if($hasRecords) onclick="showAttDetail('{{ $dateKey }}', {{ $d }})" @endif
                             @if($hasRecords) title="Click to view details" @endif>
                            <span>{{ $d }}</span>
                            @if($hasRecords && count($dayStatuses) > 1)
                                <div class="att-cal-dots">
                                    @foreach($dayStatuses as $dot)
                                        <div class="att-cal-dot {{ $dot }}"></div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>

                {{-- Summary Stats Bar --}}
                @php
                    $calPresent = $monthRecords->where('status', 'Present')->count();
                    $calLate    = $monthRecords->where('status', 'Late')->count();
                    $calAbsent  = $monthRecords->where('status', 'Absent')->count();
                @endphp
                <div class="att-cal-stats">
                    <div class="att-cal-stat present">
                        <div class="stat-dot"></div>
                        <i class="bi bi-check-circle-fill" style="font-size:0.7rem;"></i> {{ $calPresent }} Present
                    </div>
                    <div class="att-cal-stat late">
                        <div class="stat-dot"></div>
                        <i class="bi bi-clock-fill" style="font-size:0.7rem;"></i> {{ $calLate }} Late
                    </div>
                    <div class="att-cal-stat absent">
                        <div class="stat-dot"></div>
                        <i class="bi bi-x-circle-fill" style="font-size:0.7rem;"></i> {{ $calAbsent }} Absent
                    </div>
                </div>

                {{-- Detail Panel (shown on day click) --}}
                <div class="att-cal-detail" id="attDetailPanel">
                    <div class="att-detail-header">
                        <div class="att-detail-date" id="attDetailDate"></div>
                        <div class="att-detail-close" onclick="hideAttDetail()">
                            <i class="bi bi-x-lg"></i>
                        </div>
                    </div>
                    <div id="attDetailBody"></div>
                </div>
            </div>

        </x-card>
    </div>
</div>

<script>
var attCalendarData = @json($calendarJson);

function showAttDetail(dateKey, day) {
    var panel = document.getElementById('attDetailPanel');
    var dateEl = document.getElementById('attDetailDate');
    var bodyEl = document.getElementById('attDetailBody');

    // Format the date nicely
    var dt = new Date(dateKey + 'T00:00:00');
    var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    dateEl.textContent = dt.toLocaleDateString('en-US', options);

    var records = attCalendarData[dateKey] || [];
    if (records.length === 0) {
        bodyEl.innerHTML = '<div class="att-detail-empty"><i class="bi bi-calendar-x" style="font-size:1.5rem;display:block;margin-bottom:8px;opacity:0.5;"></i>No attendance records for this day.</div>';
    } else {
        var html = '';
        records.forEach(function(r) {
            var statusClass = r.status === 'Present' ? 'present' : (r.status === 'Late' ? 'late' : 'absent');
            var statusIcon = r.status === 'Present' ? 'bi-check-circle-fill' : (r.status === 'Late' ? 'bi-clock-fill' : 'bi-x-circle-fill');
            html += '<div class="att-detail-row">';
            html += '  <div>';
            html += '    <div class="att-detail-subject">' + r.subject + '</div>';
            html += '    <div class="att-detail-code">' + r.code + '</div>';
            if (r.time_in) {
                html += '    <div class="att-detail-time"><i class="bi bi-clock" style="font-size:0.65rem;"></i> ' + r.time_in + '</div>';
            }
            html += '  </div>';
            html += '  <span class="att-detail-badge ' + statusClass + '"><i class="bi ' + statusIcon + '" style="font-size:0.65rem;"></i> ' + r.status + '</span>';
            html += '</div>';
        });
        bodyEl.innerHTML = html;
    }

    panel.classList.add('active');
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    // Highlight the selected cell
    document.querySelectorAll('.att-cal-cell.selected').forEach(function(el) {
        el.classList.remove('selected');
    });
}

function hideAttDetail() {
    document.getElementById('attDetailPanel').classList.remove('active');
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