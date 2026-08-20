@extends('layouts.app')
@section('page-title', 'Student Dashboard')

@section('content')

@php
    $calYear = $calYear ?? (int)request('cal_year', request('hcal_year', now()->year));
    $calMonth = $calMonth ?? (int)request('cal_month', request('hcal_month', now()->month));
    $hcalStart = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $hcalEnd = $hcalStart->copy()->endOfMonth();
    $hcalPrev = $hcalStart->copy()->subMonth();
    $hcalNext = $hcalStart->copy()->addMonth();
    $hcalStartDow = $hcalStart->dayOfWeek;
    $hcalIsCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $hcalToday = now()->day;
    $todayFull = now()->toDateString();
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
    .hcal-student-card {
        background: #0f0a08;
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 28px;
        padding: 32px 36px;
        width: 100%;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7);
        margin-bottom: 28px;
    }
    
    .hcal-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
    }
    
    .hcal-btn-arrow {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #cfa46f;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
    }
    
    .hcal-btn-arrow:hover {
        background: rgba(207, 164, 111, 0.15);
        border-color: rgba(207, 164, 111, 0.4);
        color: #ffffff;
        transform: translateY(-2px);
    }
    
    .hcal-current-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #fdfbf7;
        letter-spacing: -0.02em;
    }
    
    .hcal-weekdays-pill {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        margin-bottom: 16px;
        background: rgba(255, 255, 255, 0.025);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 16px;
        padding: 14px 0;
    }
    
    .hcal-weekday-item {
        text-align: center;
        font-size: 0.85rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #cfa46f;
    }
    
    .hcal-days-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 12px;
    }
    
    .hcal-tile {
        height: 68px;
        min-height: 68px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.04);
        background: rgba(255, 255, 255, 0.025);
        position: relative;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
        padding: 6px 0;
    }
    
    .hcal-tile:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(207, 164, 111, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    }
    
    .hcal-tile.empty {
        visibility: hidden;
        background: transparent;
        border-color: transparent;
        cursor: default;
    }
    
    .hcal-tile-num {
        font-size: 1.15rem;
        font-weight: 700;
        color: #f3ede4;
        line-height: 1;
    }
    
    /* Sunday Red Tint */
    .hcal-tile.sunday .hcal-tile-num {
        color: #f87171 !important;
    }
    
    /* Today Cell - Illuminated Glowing Border */
    .hcal-tile.today {
        background: rgba(255, 209, 102, 0.08) !important;
        border: 2px solid #ffd166 !important;
        box-shadow: 0 0 24px rgba(255, 209, 102, 0.28), inset 0 0 14px rgba(255, 209, 102, 0.1) !important;
    }
    
    .hcal-tile.today .hcal-tile-num {
        color: #ffffff !important;
        font-weight: 800;
    }
    
    /* Holiday / Event Days - Crimson Squircle with Centered Red Dot */
    .hcal-tile.holiday-day,
    .hcal-tile.has-event {
        background: rgba(220, 38, 38, 0.15) !important;
        border: 1.5px solid rgba(220, 38, 38, 0.5) !important;
    }
    
    .hcal-tile.holiday-day .hcal-tile-num,
    .hcal-tile.has-event .hcal-tile-num {
        color: #fca5a5 !important;
        font-weight: 800;
    }
    
    .hcal-tile-dots {
        display: flex;
        gap: 4px;
        margin-top: 5px;
        height: 6px;
        align-items: center;
        justify-content: center;
    }
    
    .hcal-tile-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #ef4444;
        box-shadow: 0 0 8px #ef4444;
        flex-shrink: 0;
    }
    
    .hcal-bottom-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px;
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .hcal-legend-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 14px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 99px;
        font-size: 0.78rem;
        color: #d4c8b8;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    
    .hcal-legend-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    @media (max-width: 768px) {
        .hcal-student-card {
            padding: 20px 16px;
            border-radius: 20px;
        }
        .hcal-current-title {
            font-size: 1.2rem;
        }
        .hcal-btn-arrow {
            width: 38px;
            height: 38px;
            font-size: 1rem;
        }
        .hcal-weekdays-pill {
            padding: 10px 0;
            gap: 4px;
        }
        .hcal-weekday-item {
            font-size: 0.75rem;
        }
        .hcal-days-grid {
            gap: 6px;
        }
        .hcal-tile {
            height: 50px;
            min-height: 50px;
            border-radius: 12px;
        }
        .hcal-tile-num {
            font-size: 0.92rem;
        }
        .hcal-bottom-legend {
            gap: 8px;
        }
        .hcal-legend-badge {
            padding: 4px 10px;
            font-size: 0.7rem;
        }
    }
</style>

{{-- Main Holiday & Events Calendar Widget --}}
<div class="hcal-student-card">
    {{-- Month Navigation Header --}}
    <div class="hcal-header-row">
        <a href="?cal_year={{ $hcalPrev->year }}&cal_month={{ $hcalPrev->month }}" class="hcal-btn-arrow" title="Previous Month">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div class="hcal-current-title">
            {{ $hcalStart->format('F Y') }}
        </div>
        <a href="?cal_year={{ $hcalNext->year }}&cal_month={{ $hcalNext->month }}" class="hcal-btn-arrow" title="Next Month">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>

    {{-- Weekdays Header Pill --}}
    <div class="hcal-weekdays-pill">
        @foreach(['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $dayLbl)
            <div class="hcal-weekday-item">{{ $dayLbl }}</div>
        @endforeach
    </div>

    {{-- Days Grid (Dark Squircles) --}}
    <div class="hcal-days-grid">
        {{-- Empty cells before month start --}}
        @for($i = 0; $i < $hcalStartDow; $i++)
            <div class="hcal-tile empty"></div>
        @endfor

        @for($d = 1; $d <= $hcalEnd->day; $d++)
            @php
                $dateKey = \Carbon\Carbon::create($calYear, $calMonth, $d)->format('Y-m-d');
                $formattedDate = \Carbon\Carbon::create($calYear, $calMonth, $d)->format('l, F j, Y');
                $isToday = $hcalIsCurrentMonth && $d === $hcalToday;
                $isSunday = \Carbon\Carbon::create($calYear, $calMonth, $d)->dayOfWeek === 0;
                $dayEvents = $hcalEventsMap[$dateKey] ?? [];
                $hasEvents = count($dayEvents) > 0;
                $isHoliday = collect($dayEvents)->where('source', 'holiday')->isNotEmpty();

                $cls = '';
                if ($isToday) $cls .= ' today';
                if ($isSunday) $cls .= ' sunday';
                if ($hasEvents) $cls .= ' has-event';
                if ($isHoliday) $cls .= ' holiday-day';
            @endphp
            <div class="hcal-tile{{ $cls }}" onclick="openStudentHcalDetails('{{ $dateKey }}', '{{ $formattedDate }}')" title="{{ $hasEvents ? collect($dayEvents)->pluck('name')->join(', ') : 'Click to view events' }}">
                <div class="hcal-tile-num">{{ $d }}</div>
                @if($hasEvents)
                    <div class="hcal-tile-dots">
                        @foreach(collect($dayEvents)->unique('type')->take(3) as $evt)
                            <div class="hcal-tile-dot"></div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endfor
    </div>

    {{-- Legend Pill Badges --}}
    <div class="hcal-bottom-legend">
        <div class="hcal-legend-badge"><div class="hcal-legend-dot" style="background:#dc2626; box-shadow: 0 0 6px #dc2626;"></div> National</div>
        <div class="hcal-legend-badge"><div class="hcal-legend-dot" style="background:#d97706; box-shadow: 0 0 6px #d97706;"></div> Local</div>
        <div class="hcal-legend-badge"><div class="hcal-legend-dot" style="background:#7c2d12; box-shadow: 0 0 6px #7c2d12;"></div> School</div>
        <div class="hcal-legend-badge"><div class="hcal-legend-dot" style="background:#6366f1; box-shadow: 0 0 6px #6366f1;"></div> No Class</div>
        <div class="hcal-legend-badge"><div class="hcal-legend-dot" style="background:#60a5fa; box-shadow: 0 0 6px #60a5fa;"></div> Announcement</div>
    </div>
</div>

{{-- ─── CALENDAR EVENT DETAILS POPUP MODAL ─── --}}
<div class="hcal-modal-overlay" id="hcalDetailsModalOverlay">
    <div class="hcal-modal hcal-details-modal" style="max-width: 540px;">
        <div class="hcal-modal-header" style="padding: 20px 24px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 38px; height: 38px; border-radius: 12px; background: rgba(207, 164, 111, 0.12); border: 1px solid rgba(207, 164, 111, 0.25); display: flex; align-items: center; justify-content: center; color: #cfa46f; font-size: 1.15rem; flex-shrink: 0;">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div>
                    <div class="hcal-modal-title" style="font-size: 1.1rem; line-height: 1.2;">Calendar Event Details</div>
                    <div id="hcalDetailsDateLabel" style="font-size: 0.78rem; color: #b39b82; font-weight: 600; margin-top: 2px;"></div>
                </div>
            </div>
            <button type="button" class="hcal-modal-close" onclick="closeStudentHcalDetails()">×</button>
        </div>
        <div class="hcal-modal-body" id="hcalDetailsBody" style="padding: 20px 24px; max-height: 460px; overflow-y: auto;">
            {{-- Dynamically populated via JS --}}
        </div>
        <div class="hcal-modal-footer" style="padding: 16px 24px; display: flex; justify-content: flex-end; border-top: 1px solid rgba(255, 255, 255, 0.06); background: rgba(0, 0, 0, 0.25);">
            <button type="button" class="hcal-btn-cancel" onclick="closeStudentHcalDetails()">Close</button>
        </div>
    </div>
</div>

<script>
window.studentHcalEventsMap = @json($hcalEventsMap ?? []);

function openStudentHcalDetails(dateKey, formattedDate) {
    document.getElementById('hcalDetailsDateLabel').textContent = formattedDate;
    const body = document.getElementById('hcalDetailsBody');
    const events = window.studentHcalEventsMap[dateKey] || [];
    
    if (events.length === 0) {
        body.innerHTML = `
            <div style="text-align: center; padding: 36px 16px;">
                <div style="width: 58px; height: 58px; border-radius: 16px; background: rgba(207, 164, 111, 0.08); border: 1px solid rgba(207, 164, 111, 0.2); color: #cfa46f; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; margin: 0 auto 16px;">
                    <i class="bi bi-calendar2-check"></i>
                </div>
                <h4 style="font-size: 1.05rem; font-weight: 700; color: #f3ede4; margin-bottom: 6px;">Regular Academic Day</h4>
                <p style="font-size: 0.82rem; color: #8f826f; margin-bottom: 0;">No holidays or campus announcements scheduled for this date.</p>
            </div>
        `;
    } else {
        let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';
        events.forEach(evt => {
            const isHoliday = evt.source === 'holiday';
            const typeColorMap = {
                national: '#dc2626',
                local: '#d97706',
                school: '#7c2d12',
                no_class: '#6366f1',
                announcement: '#60a5fa',
                event: '#a78bfa',
                school_event: '#a78bfa'
            };
            const borderColor = typeColorMap[evt.type] || '#cfa46f';
            
            html += `
                <div class="hcal-details-item" data-type="${evt.type}" style="border-left: 4px solid ${borderColor};">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span class="hcal-event-type ${evt.type}" style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="bi ${isHoliday ? 'bi-calendar-heart' : 'bi-megaphone'}"></i>
                            ${evt.type_label || evt.type}
                        </span>
                    </div>
                    <div style="font-size: 1.05rem; font-weight: 700; color: #f3ede4; margin-bottom: 4px;">${escapeHtml(evt.name)}</div>
                    ${evt.author ? `
                        <div style="font-size: 0.75rem; color: #b39b82; margin-bottom: 6px; display: flex; align-items: center; gap: 4px;">
                            <i class="bi bi-person"></i> Posted by <strong>${escapeHtml(evt.author)}</strong>
                        </div>
                    ` : ''}
                    ${evt.location ? `
                        <div style="font-size: 0.75rem; color: #b39b82; margin-bottom: 6px; display: flex; align-items: center; gap: 4px;">
                            <i class="bi bi-geo-alt"></i> ${escapeHtml(evt.location)}
                        </div>
                    ` : ''}
                    ${evt.description ? `
                        <div style="font-size: 0.82rem; color: #d4c8b8; line-height: 1.45; margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.05);">
                            ${escapeHtml(evt.description)}
                        </div>
                    ` : ''}
                </div>
            `;
        });
        html += '</div>';
        body.innerHTML = html;
    }
    
    document.getElementById('hcalDetailsModalOverlay').classList.add('active');
}

function closeStudentHcalDetails() {
    const overlay = document.getElementById('hcalDetailsModalOverlay');
    if (overlay) overlay.classList.remove('active');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.getElementById('hcalDetailsModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeStudentHcalDetails();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeStudentHcalDetails();
    }
});
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