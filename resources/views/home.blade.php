@extends('layouts.app')

@section('content')


@php
    $calYear   = request('cal_year',  now()->year);
    $calMonth  = request('cal_month', now()->month);
    $calStart  = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $calEnd    = $calStart->copy()->endOfMonth();
    $prevMonth = $calStart->copy()->subMonth();
    $nextMonth = $calStart->copy()->addMonth();

    $monthRecords = $records->filter(fn($r) =>
        \Carbon\Carbon::parse($r->date)->year  == $calYear &&
        \Carbon\Carbon::parse($r->date)->month == $calMonth
    );

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

    $totalRecords = $records->count();
    $greetHour = now()->hour;
    $greeting = $greetHour < 12 ? 'Good Morning' : ($greetHour < 17 ? 'Good Afternoon' : 'Good Evening');
@endphp

<!-- ATTENDANCE DATE MODAL -->
<div class="att-modal-overlay" id="attModal">
    <div class="att-modal">
        <div class="att-modal-header">
            <div class="att-modal-title" id="modalDate">—</div>
            <button class="att-modal-close" onclick="closeAttModal()">✕</button>
        </div>
        <div class="att-modal-summary" id="modalSummary"></div>
        <div class="att-modal-list" id="modalList"></div>
    </div>
</div>

<div class="container-fluid" style="max-width: 1200px;">

    @if(session('error'))
    <div class="premium-alert danger anim-slide-up">
        <div class="alert-icon-wrap" style="width: 36px; height: 36px; font-size: 1rem;">
            <i class="bi bi-exclamation-circle-fill text-white"></i>
        </div>
        <div style="flex:1;">
            <span style="font-size: 0.95rem; font-weight: 600; color: #fee2e2;">{{ session('error') }}</span>
        </div>
    </div>
    @endif

    <!-- ACTIVE WARNINGS BANNER -->
    @if(isset($activeWarnings) && $activeWarnings->count() > 0)
    <div class="glass-panel anim-slide-up" style="background: linear-gradient(135deg, rgba(220,38,38,0.15) 0%, rgba(153,27,27,0.05) 100%); border-color: rgba(239,68,68,0.3); padding: 24px; margin-bottom: 24px; position: relative;">
        <div style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 5rem; opacity: 0.05; pointer-events: none;">⚠️</div>
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="alert-icon-wrap" style="background: rgba(239,68,68,0.25);">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
            </div>
            <div>
                <div style="font-size: 1.15rem; font-weight: 800; color: #fca5a5;">Action Required: Attendance Warning</div>
                <div style="font-size: 0.88rem; color: #e7dcc8;">You have {{ $activeWarnings->count() }} active warning(s). Please review your attendance immediately.</div>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            @foreach($activeWarnings as $warning)
            <div style="background: rgba(0,0,0,0.2); padding: 14px 18px; border-radius: 14px; border: 1px solid rgba(239,68,68,0.15);">
                <div style="font-size: 0.88rem; font-weight: 700; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #fca5a5;"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $warning->subject_code }}</span>
                    <span style="opacity: 0.6; font-size: 0.75rem; font-weight: 600;">{{ $warning->created_at->diffForHumans() }}</span>
                </div>
                <div style="font-size: 0.85rem; color: #e7dcc8; line-height: 1.4;">{{ $warning->message }}</div>
            </div>
            @endforeach
        </div>
        <div style="margin-top: 18px; display: flex; gap: 12px;">
            <a href="{{ route('excuses') }}" class="premium-btn" style="background: linear-gradient(135deg, #dc2626, #991b1b); border-color: rgba(220,38,38,0.5);">
                <i class="bi bi-file-text-fill"></i> Submit Excuse
            </a>
            <a href="{{ route('attendance.records') }}" class="premium-btn" style="background: rgba(255,255,255,0.05);">
                View Records
            </a>
        </div>
    </div>
    @endif

    <!-- HERO BANNER -->
    <div class="hero-banner fade-up fade-up-1" style="margin-bottom: 24px;">
        <div class="hero-icon">🎓</div>
        <div class="hero-greeting">{{ $greeting }}</div>
        <div class="hero-title">{{ Auth::user()->name }}</div>
        <div class="hero-sub">
            {{ Auth::user()->course }} — Year {{ Auth::user()->year_level }}, Semester {{ Auth::user()->semester }}
        </div>
        <div class="hero-time">
            <i class="bi bi-clock"></i>
            <span id="liveTime">{{ now()->format('h:i A') }}</span>
            &nbsp;·&nbsp; {{ now()->format('l, F j, Y') }}
        </div>
    </div>

    <!-- HOLIDAY NOTIFICATION -->
    @if($todayHoliday)
    <div class="glass-panel anim-slide-up" style="background: linear-gradient(135deg, rgba(220,38,38,0.15) 0%, rgba(190,18,60,0.05) 100%); border-color: rgba(220,38,38,0.2); padding: 24px; margin-bottom: 24px; position: relative;">
        <div style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 3.5rem; opacity: 0.1;">🎉</div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <div class="alert-icon-wrap" style="background: rgba(239,68,68,0.15);">
                <span class="fs-4">🏖️</span>
            </div>
            <div>
                <div style="font-size: 1.15rem; font-weight: 800; color: #fca5a5;">{{ $todayHoliday->name }}</div>
                <div style="font-size: 0.82rem; color: #e7dcc8;">{{ $todayHoliday->type_label }} &bull; {{ $todayHoliday->date->format('F j, Y') }}</div>
            </div>
        </div>
        @if($todayHoliday->description)
        <div style="font-size: 0.85rem; color: #e7dcc8; margin-top: 8px;">{{ $todayHoliday->description }}</div>
        @endif
        <div style="background: rgba(0,0,0,0.2); padding: 12px 16px; border-radius: 12px; margin-top: 16px; font-size: 0.85rem; font-weight: 600; color: #f3e7cd;">
            <i class="bi bi-info-circle me-2" style="color: #4ade80;"></i>No classes today — Enjoy your holiday! 🎊
        </div>
    </div>
    @endif

    <!-- UPCOMING HOLIDAYS -->
    @if($upcomingHolidays->count() > 0)
    <div class="glass-panel anim-slide-up" style="padding: 20px 24px; margin-bottom: 24px;">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="tch-card-icon" style="background: rgba(248,113,113,0.15); color: #f87171; width: 36px; height: 36px; font-size: 1rem;">
                <i class="bi bi-calendar-event-fill"></i>
            </div>
            <div style="font-size: 1rem; font-weight: 700; color: #f3e7cd;">Upcoming Holidays</div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            @foreach($upcomingHolidays as $holiday)
            <div class="class-item" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 12px; background: rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.05);">
                <div>
                    <div style="font-size: 0.9rem; font-weight: 700; color: #f3e7cd;">{{ $holiday->name }}</div>
                    <div style="font-size: 0.75rem; color: #b39b82;">{{ $holiday->type_label }}</div>
                </div>
                <div style="font-size: 0.85rem; font-weight: 800; color: {{ $holiday->type_color }}; background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 8px;">
                    {{ $holiday->date->format('M j') }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- STATS ROW -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="glass-panel stat-card-premium anim-slide-up delay-1">
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #4ade80; opacity: 0.8;"></div>
                <i class="bi bi-check-circle-fill stat-icon-floating" style="color: #4ade80;"></i>
                <div class="stat-icon-solid" style="background: linear-gradient(135deg, rgba(34,197,94,0.15), rgba(22,163,74,0.05)); color: #4ade80; border: 1px solid rgba(34,197,94,0.2);">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <div class="stat-val-premium" data-count="{{ $totalPresent }}">0</div>
                    <div class="tch-stat-lbl">Present (All-time)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="glass-panel stat-card-premium anim-slide-up delay-2">
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #fbbf24; opacity: 0.8;"></div>
                <i class="bi bi-clock-fill stat-icon-floating" style="color: #fbbf24;"></i>
                <div class="stat-icon-solid" style="background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(217,119,6,0.05)); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2);">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <div>
                    <div class="stat-val-premium" data-count="{{ $totalLate }}">0</div>
                    <div class="tch-stat-lbl">Late (All-time)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="glass-panel stat-card-premium anim-slide-up delay-3">
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #f87171; opacity: 0.8;"></div>
                <i class="bi bi-x-circle-fill stat-icon-floating" style="color: #f87171;"></i>
                <div class="stat-icon-solid" style="background: linear-gradient(135deg, rgba(239,68,68,0.15), rgba(185,28,28,0.05)); color: #f87171; border: 1px solid rgba(239,68,68,0.2);">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <div class="stat-val-premium" data-count="{{ $totalAbsent }}">0</div>
                    <div class="tch-stat-lbl">Absent (All-time)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="glass-panel stat-card-premium anim-slide-up delay-3">
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #cfa46f; opacity: 0.8;"></div>
                <i class="bi bi-book-fill stat-icon-floating" style="color: #cfa46f;"></i>
                <div class="stat-icon-solid" style="background: linear-gradient(135deg, rgba(207,164,111,0.15), rgba(143,110,74,0.05)); color: #cfa46f; border: 1px solid rgba(207,164,111,0.2);">
                    <i class="bi bi-book-fill"></i>
                </div>
                <div>
                    <div class="stat-val-premium" data-count="{{ isset($subjects) ? count($subjects) : 0 }}">0</div>
                    <div class="tch-stat-lbl">Subjects Enrolled</div>
                </div>
            </div>
        </div>
    </div>

    <!-- STREAK + QUICK ACTIONS ROW -->
    <div class="anim-slide-up delay-3" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <div style="font-size: 0.95rem; font-weight: 600; color: rgba(245,234,215,0.75);">
                Overall Rate: <span style="color: {{ $attendanceRate >= 75 ? '#4ade80' : '#f87171' }}; font-weight: 800; font-size: 1.1rem;">{{ $attendanceRate }}%</span>
            </div>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="{{ route('student.classes') }}" class="premium-btn" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 8px 16px; font-size: 0.85rem;">
                <i class="bi bi-calendar-week"></i> My Schedule
            </a>
            <a href="{{ route('excuses') }}" class="premium-btn" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 8px 16px; font-size: 0.85rem;">
                <i class="bi bi-file-text"></i> Excuses
            </a>
            <a href="{{ route('attendance.records') }}" class="premium-btn" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 8px 16px; font-size: 0.85rem;">
                <i class="bi bi-list-check"></i> All Records
            </a>
        </div>
    </div>

    <!-- MAIN BENTO GRID -->
    <div class="bento-grid">

        <!-- TODAY'S SCHEDULE -->
        <div class="bento-item bento-item--schedule glass-panel anim-slide-up delay-2">
            <div class="section-header">
                <div class="section-icon" style="background: rgba(216,179,92,0.14); color: var(--gold);">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="section-title">Today's Schedule</div>
                    <div class="section-subtitle">{{ now()->format('l, M j') }}</div>
                </div>
            </div>
            @if($todaySchedule->count() > 0)
                @foreach($todaySchedule as $item)
                <div class="schedule-item">
                    <div class="schedule-timeline">
                        <div class="schedule-dot {{ $item->status }}"></div>
                    </div>
                    <div class="schedule-info">
                        <div class="schedule-subject">{{ $item->subject->name }}</div>
                        <div class="schedule-meta">
                            {{ $item->start_time->format('g:i A') }} – {{ $item->end_time->format('g:i A') }}
                            &nbsp;·&nbsp; {{ $item->subject->code }}
                        </div>
                    </div>
                    <div class="schedule-status-badge {{ $item->status }}">
                        @if($item->status === 'completed') ✓ Done
                        @elseif($item->status === 'ongoing') ⏱ Now
                        @elseif($item->status === 'missed') ✕ Missed
                        @else ○ Later
                        @endif
                    </div>
                </div>
                @endforeach
            @else
                <div class="empty-state-card">
                    <i class="bi bi-calendar-x"></i>
                    <p>No classes scheduled today</p>
                </div>
            @endif
        </div>

        <!-- ATTENDANCE CALENDAR -->
        <div class="bento-item bento-item--calendar glass-panel anim-slide-up delay-3">
            <div class="section-header">
                <div class="section-icon" style="background: rgba(37,99,235,0.14); color: #60a5fa;">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div>
                    <div class="section-title">Calendar</div>
                    <div class="section-subtitle">Attendance history</div>
                </div>
            </div>

            <div class="cal-header">
                <a href="?cal_year={{ $prevMonth->year }}&cal_month={{ $prevMonth->month }}" class="cal-nav">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <div class="cal-month">{{ $calStart->format('F Y') }}</div>
                @if(!$isLatestMonth)
                    <a href="?cal_year={{ $nextMonth->year }}&cal_month={{ $nextMonth->month }}" class="cal-nav">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @else
                    <div style="width:30px;"></div>
                @endif
            </div>

            <div class="cal-grid">
                @foreach(['S','M','T','W','T','F','S'] as $label)
                    <div class="cal-day-label">{{ $label }}</div>
                @endforeach

                @for($i = 0; $i < $startDow; $i++)
                    <div class="cal-day empty"></div>
                @endfor

                @for($d = 1; $d <= $calEnd->day; $d++)
                    @php
                        $isToday   = $isCurrentMonth && $d === $today;
                        $isFuture  = $isCurrentMonth && $d > $today;
                        $isNoClass = in_array($d, $noClassDays);
                        $dots      = $dayDotsMap[$d] ?? [];
                        $hasRecs   = !empty($dots);
                        $dateKey   = \Carbon\Carbon::create($calYear, $calMonth, $d)->format('Y-m-d');
                        $dateLabel = \Carbon\Carbon::create($calYear, $calMonth, $d)->format('F j, Y');

                        $isHoliday = false;
                        if ($isToday) {
                            $todayHolidayCheck = \App\Models\Holiday::where('date', $dateKey)->where('is_active', true)->first();
                            $isHoliday = $todayHolidayCheck !== null;
                        }

                        $cls = '';
                        if ($isNoClass || $isHoliday) $cls = 'no-class';
                        elseif ($isFuture) $cls = 'future';
                        elseif ($isToday) $cls = 'today';
                        if ($hasRecs && !$isNoClass && !$isFuture && !$isHoliday) $cls .= ' has-records';

                        $isClickable = ($hasRecs && !$isNoClass && !$isFuture && !$isHoliday) ||
                                      ($isToday && !$isNoClass && !$isHoliday);
                    @endphp
                    <div class="cal-day {{ $cls }}"
                         @if($isClickable)
                             onclick="openAttModal('{{ $dateKey }}', '{{ $dateLabel }}')"
                             title="Click to view details"
                         @endif>
                        <div class="cal-day-num">{{ $d }}</div>
                        @if($hasRecs && !$isNoClass && !$isFuture && !$isHoliday)
                            <div class="cal-dots">
                                @foreach($dots as $dot)
                                    <div class="cal-dot {{ $dot }}"></div>
                                @endforeach
                            </div>
                        @elseif($isToday && !$isNoClass && !$isHoliday)
                            <div class="cal-dots">
                                <div class="cal-dot" style="background:#94a3b8;width:4px;height:4px;opacity:0.4;"></div>
                            </div>
                        @endif
                    </div>
                @endfor
            </div>

            <div class="cal-legend">
                <div class="cal-legend-item">
                    <div class="cal-legend-dot" style="background:#16a34a;"></div> Present
                </div>
                <div class="cal-legend-item">
                    <div class="cal-legend-dot" style="background:#d97706;"></div> Late
                </div>
                <div class="cal-legend-item">
                    <div class="cal-legend-dot" style="background:#dc2626;"></div> Absent
                </div>
            </div>
        </div>

        <!-- RECENT ATTENDANCE + DONUT -->
        <div class="bento-item bento-item--recent glass-panel anim-slide-up delay-3">
            <!-- Donut Chart -->
            @if($totalRecords > 0)
            <div style="margin-bottom: 22px;">
                <div class="section-header" style="margin-bottom: 12px;">
                    <div class="section-icon" style="background: rgba(168,85,247,0.14); color: #c084fc;">
                        <i class="bi bi-pie-chart-fill"></i>
                    </div>
                    <div>
                        <div class="section-title">Attendance Rate</div>
                    </div>
                </div>
                <div style="position: relative; max-width: 180px; margin: 0 auto;">
                    <canvas id="attendanceDonut" width="180" height="180" style="padding: 10px;"></canvas>
                    <div class="rate-center">
                        <div class="rate-value">{{ $attendanceRate }}%</div>
                        <div class="rate-label">Rate</div>
                    </div>
                </div>
                <div class="donut-legend">
                    <div class="donut-legend-item"><div class="donut-legend-dot" style="background:#16a34a;"></div> {{ $totalPresent }}</div>
                    <div class="donut-legend-item"><div class="donut-legend-dot" style="background:#eab308;"></div> {{ $totalLate }}</div>
                    <div class="donut-legend-item"><div class="donut-legend-dot" style="background:#dc2626;"></div> {{ $totalAbsent }}</div>
                </div>
            </div>
            <div style="height: 1px; background: rgba(255,255,255,0.06); margin: 0 -22px 18px;"></div>
            @endif

            <!-- Recent Attendance -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <div class="section-header" style="margin-bottom: 0;">
                    <div class="section-icon" style="background: rgba(34,197,94,0.14); color: #4ade80;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <div class="section-title">Recent</div>
                    </div>
                </div>
                <a href="{{ route('attendance.records') }}" class="view-all-btn">View all</a>
            </div>

            <table class="att-table">
                <thead>
                    <tr><th>Date</th><th>Subject</th><th>Status</th><th>Time</th></tr>
                </thead>
                <tbody>
                    @forelse($records->take(5) as $record)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($record->date)->format('M d') }}</td>
                        <td class="subject-name">{{ $record->subject->name ?? $record->subject_code }}</td>
                        <td>
                            @if($record->excused)
                                <span class="status-badge" style="background:rgba(34,197,94,0.14);color:#86efac;">Excused</span>
                            @elseif($record->status == 'Present')
                                <span class="status-badge badge-present">Present</span>
                            @elseif($record->status == 'Late')
                                <span class="status-badge badge-late">Late</span>
                            @else
                                <span class="status-badge badge-absent">Absent</span>
                            @endif
                        </td>
                        <td>{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state-card">
                                <i class="bi bi-inbox"></i>
                                <p>No attendance records yet</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="attendance-cards">
                @forelse($records->take(5) as $record)
                <div class="attendance-card">
                    <div class="attendance-card-header">
                        <div class="attendance-date">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</div>
                        @if($record->excused)
                            <span class="attendance-status" style="background:rgba(34,197,94,0.16);color:#86efac;">Excused</span>
                        @elseif($record->status == 'Present')
                            <span class="attendance-status status-present">Present</span>
                        @elseif($record->status == 'Late')
                            <span class="attendance-status status-late">Late</span>
                        @else
                            <span class="attendance-status status-absent">Absent</span>
                        @endif
                    </div>
                    <div class="attendance-subject">{{ $record->subject->name ?? $record->subject_code }}</div>
                    <div class="attendance-time">
                        <i class="bi bi-clock"></i>
                        {{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : 'No time recorded' }}
                    </div>
                </div>
                @empty
                <div class="empty-state-card">
                    <i class="bi bi-inbox"></i>
                    <p>No attendance records yet</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── LIVE CLOCK ──
function updateClock() {
    const el = document.getElementById('liveTime');
    if (el) {
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes(), ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        el.textContent = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
    }
}
setInterval(updateClock, 30000);

// ── ANIMATED COUNTERS ──
function animateCounters() {
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count) || 0;
        if (target === 0) { el.textContent = '0'; return; }
        const duration = 1200;
        const start = performance.now();
        function tick(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            el.textContent = Math.round(eased * target);
            if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });
}

// Observe when stats are visible
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            observer.disconnect();
        }
    });
}, { threshold: 0.2 });
const statsRow = document.querySelector('.stats-row');
if (statsRow) observer.observe(statsRow);

// ── DONUT CHART ──
@if($totalRecords > 0)
@php
    $activeSegments = ($totalPresent > 0 ? 1 : 0) + ($totalLate > 0 ? 1 : 0) + ($totalAbsent > 0 ? 1 : 0);
    $donutBorder = $activeSegments > 1 ? 4 : 0;
@endphp
const donutCtx = document.getElementById('attendanceDonut');
if (donutCtx) {
    new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Late', 'Absent'],
            datasets: [{
                data: [{{ $totalPresent }}, {{ $totalLate }}, {{ $totalAbsent }}],
                backgroundColor: ['#16a34a', '#eab308', '#dc2626'],
                borderWidth: {{ $donutBorder }},
                borderColor: 'rgba(35,21,27,0.94)',
                hoverOffset: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            layout: { padding: 15 },
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(18,11,14,0.95)',
                    titleColor: '#f8e7d3',
                    bodyColor: '#f8e7d3',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    cornerRadius: 10,
                    padding: 10,
                }
            },
            animation: {
                animateRotate: true,
                duration: 1400,
            }
        }
    });
}
@endif

// ── CALENDAR MODAL ──
const calendarData = @json($calendarJson);

function openAttModal(dateKey, dateLabel) {
    const records = calendarData[dateKey];

    document.getElementById('modalDate').textContent = dateLabel;

    if (!records || records.length === 0) {
        document.getElementById('modalSummary').innerHTML =
            '<span class="att-summary-pill" style="background:rgba(255,255,255,0.06);color:rgba(245,234,215,0.6);border:1px solid rgba(255,255,255,0.1);"><i class="bi bi-info-circle"></i> No records yet</span>';

        document.getElementById('modalList').innerHTML =
            '<div class="empty-state-card">' +
            '<i class="bi bi-calendar-x"></i>' +
            '<p>No attendance has been recorded for this date.</p>' +
            '</div>';

        document.getElementById('attModal').classList.add('active');
        return;
    }

    const present = records.filter(r => r.status === 'Present').length;
    const late    = records.filter(r => r.status === 'Late').length;
    const absent  = records.filter(r => r.status === 'Absent').length;

    let summaryHtml = '';
    if (present > 0) summaryHtml += `<span class="att-summary-pill present"><i class="bi bi-check-circle-fill"></i> Present: ${present}</span>`;
    if (late > 0)    summaryHtml += `<span class="att-summary-pill late"><i class="bi bi-clock-fill"></i> Late: ${late}</span>`;
    if (absent > 0)  summaryHtml += `<span class="att-summary-pill absent"><i class="bi bi-x-circle-fill"></i> Absent: ${absent}</span>`;
    document.getElementById('modalSummary').innerHTML = summaryHtml;

    let listHtml = '';
    records.forEach(r => {
        const badgeCls = r.status === 'Present' ? 'present' : r.status === 'Late' ? 'late' : 'absent';
        const timeHtml = r.time_in
            ? `<span class="att-modal-time"><i class="bi bi-clock me-1"></i>${r.time_in}</span>`
            : `<span class="att-modal-time">—</span>`;
        listHtml += `
            <div class="att-modal-row">
                <span class="att-modal-badge ${badgeCls}">${r.status}</span>
                <span class="att-modal-subject">${r.subject}</span>
                ${timeHtml}
            </div>`;
    });
    document.getElementById('modalList').innerHTML = listHtml;
    document.getElementById('attModal').classList.add('active');
}

function closeAttModal() {
    document.getElementById('attModal').classList.remove('active');
}

document.getElementById('attModal').addEventListener('click', function(e) {
    if (e.target === this) closeAttModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAttModal();
});
</script>
@endsection