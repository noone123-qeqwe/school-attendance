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
<div class="mb-4" style="background: linear-gradient(135deg, rgba(32,20,15,0.9) 0%, rgba(20,10,5,0.95) 100%); border: 1px solid rgba(207,164,111,0.25); border-radius: 24px; padding: 30px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
    <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: linear-gradient(180deg, var(--gold) 0%, #8f6e4a 100%);"></div>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
        <div class="d-flex align-items-center gap-4">
            <div style="font-size: 3rem;">🎓</div>
            <div>
                <div style="color: var(--gold); font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">{{ $greeting }}</div>
                <h1 style="color: #f3e7cd; font-weight: 800; margin: 0 0 6px 0; font-size: 2rem;">{{ Auth::user()->name }}</h1>
                <div style="color: #b39b82; font-size: 0.95rem;">
                    {{ Auth::user()->course }} — Year {{ Auth::user()->year_level }}, Semester {{ Auth::user()->semester }}
                </div>
            </div>
        </div>
        <div style="display:flex; flex-direction:column; gap:12px; text-align:right;">
            <div style="text-align: right; background: rgba(0,0,0,0.3); padding: 12px 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05);">
                <div style="color: var(--gold); font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                    <i class="bi bi-clock"></i> <span>{{ now()->format('h:i A') }}</span>
                </div>
                <div style="color: #b39b82; font-size: 0.85rem; margin-top: 2px;">{{ now()->format('l, F j, Y') }}</div>
            </div>
            <a href="{{ route('excuses') }}" class="ent-btn" style="background: rgba(255,255,255,0.1); color: var(--gold); border: 1px solid rgba(207,164,111,0.3); justify-content:center; text-decoration:none;">
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

        <div class="d-flex flex-column gap-3">
            @foreach($subjectStats as $stat)
                @php
                    $rateColor = $stat->rate >= 90 ? '#4ade80' : ($stat->rate >= 75 ? '#fbbf24' : '#f87171');
                    $rateBg = $stat->rate >= 90 ? 'rgba(74,222,128,0.1)' : ($stat->rate >= 75 ? 'rgba(251,191,36,0.1)' : 'rgba(248,113,113,0.1)');
                    $rateBorder = $stat->rate >= 90 ? 'rgba(74,222,128,0.2)' : ($stat->rate >= 75 ? 'rgba(251,191,36,0.2)' : 'rgba(248,113,113,0.2)');
                @endphp
                <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 14px; padding: 16px;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div style="font-weight: 700; color: #f3e7cd; font-size: 0.95rem;">{{ $stat->name }}</div>
                            <div style="font-size: 0.75rem; color: #b39b82; margin-top: 2px;">
                                <i class="bi bi-tag-fill" style="font-size: 0.65rem;"></i> {{ $stat->code }} · {{ $stat->total }} classes
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.4rem; font-weight: 800; color: {{ $rateColor }}; line-height: 1;">{{ $stat->rate }}%</div>
                            @if($stat->rate < 75)
                                <div style="font-size: 0.65rem; color: #f87171; font-weight: 600; margin-top: 2px;">
                                    <i class="bi bi-exclamation-triangle-fill"></i> At Risk
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div style="height: 6px; background: rgba(255,255,255,0.06); border-radius: 99px; overflow: hidden; margin-bottom: 10px;">
                        <div style="height: 100%; width: {{ $stat->rate }}%; background: {{ $rateColor }}; border-radius: 99px; transition: width 0.6s ease;"></div>
                    </div>

                    <!-- Status Pills -->
                    <div class="d-flex gap-2 flex-wrap">
                        <span style="font-size: 0.7rem; font-weight: 600; color: #4ade80; background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.2); padding: 3px 10px; border-radius: 99px;">
                            <i class="bi bi-check-circle-fill" style="font-size: 0.6rem;"></i> {{ $stat->present }} Present
                        </span>
                        @if($stat->late > 0)
                        <span style="font-size: 0.7rem; font-weight: 600; color: #fbbf24; background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.2); padding: 3px 10px; border-radius: 99px;">
                            <i class="bi bi-clock-fill" style="font-size: 0.6rem;"></i> {{ $stat->late }} Late
                        </span>
                        @endif
                        @if($stat->absent > 0)
                        <span style="font-size: 0.7rem; font-weight: 600; color: #f87171; background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.2); padding: 3px 10px; border-radius: 99px;">
                            <i class="bi bi-x-circle-fill" style="font-size: 0.6rem;"></i> {{ $stat->absent }} Absent
                        </span>
                        @endif
                        @if($stat->excused > 0)
                        <span style="font-size: 0.7rem; font-weight: 600; color: #60a5fa; background: rgba(96,165,250,0.1); border: 1px solid rgba(96,165,250,0.2); padding: 3px 10px; border-radius: 99px;">
                            <i class="bi bi-file-earmark-check-fill" style="font-size: 0.6rem;"></i> {{ $stat->excused }} Excused
                        </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
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

    <!-- Attendance Calendar (Simplified list view for now, could be full calendar if needed) -->
    <div class="col-lg-6">
        <x-card title="Recent Attendance" icon="bi bi-calendar3">
            <x-slot name="headerActions">
                <a href="{{ route('attendance.records') }}" class="btn btn-outline btn-sm">View All</a>
            </x-slot>

            <div class="d-flex flex-column gap-3">
                @if(isset($records) && $records->count() > 0)
                    @foreach($records->take(5) as $record)
                        <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 700; color: #f3e7cd;">{{ $record->subject->name ?? $record->subject_code }}</div>
                                <div style="color: #b39b82; font-size: 0.8rem; margin-top: 4px;">
                                    {{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}
                                    @if($record->time_in)
                                        &nbsp;·&nbsp; {{ \Carbon\Carbon::parse($record->time_in)->format('h:i A') }}
                                    @endif
                                </div>
                            </div>
                            <div>
                                @php $recordStatus = strtolower($record->status ?? ''); @endphp
                                @if($recordStatus === 'present') <x-badge type="present">Present</x-badge>
                                @elseif($recordStatus === 'late') <x-badge type="late">Late</x-badge>
                                @else <x-badge type="absent">Absent</x-badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="empty-state text-center" style="padding: 40px 20px;">
                        <i class="bi bi-clock-history" style="font-size: 3rem; color: #b39b82; opacity: 0.5;"></i>
                        <p style="color: #b39b82; font-size: 1rem; margin-top: 16px; font-weight: 600;">No recent attendance records</p>
                    </div>
                @endif
            </div>
        </x-card>
    </div>
</div>

<script>
// ── Skeleton → Content Reveal ──
document.addEventListener('DOMContentLoaded', function() {
    var skelStats = document.getElementById('skelStats');
    var realStats = document.getElementById('realStats');
    if (skelStats && realStats) { skelStats.style.display = 'none'; realStats.style.display = ''; }
});
</script>
@endsection