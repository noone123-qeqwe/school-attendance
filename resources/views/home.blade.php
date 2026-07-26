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
    <div class="fade-up" style="background: rgba(220,38,38,0.16); border: 1px solid rgba(220,38,38,0.3); color: #fee2e2; border-radius: 16px; padding: 14px 18px; font-size: 0.88rem; margin-bottom: 20px; display:flex;align-items:center;gap:10px;">
        <i class="bi bi-exclamation-circle-fill fs-5"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- ACTIVE WARNINGS BANNER -->
    @if(isset($activeWarnings) && $activeWarnings->count() > 0)
    <div class="fade-up" style="background: linear-gradient(135deg, rgba(220,38,38,0.95) 0%, rgba(153,27,27,0.98) 100%); border-radius: 20px; padding: 22px 26px; color: white; margin-bottom: 24px; position: relative; overflow: hidden; box-shadow: 0 16px 48px rgba(220,38,38,0.28); border: 1px solid rgba(255,255,255,0.15);">
        <div style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 5rem; opacity: 0.08; pointer-events: none;">⚠️</div>
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px; position: relative; z-index: 1;">
            <div style="width: 46px; height: 46px; background: rgba(255,255,255,0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">🚨</div>
            <div>
                <div style="font-size: 1.15rem; font-weight: 800; letter-spacing: -0.3px; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">Action Required: Attendance Warning</div>
                <div style="font-size: 0.88rem; opacity: 0.95; margin-top: 2px;">You have {{ $activeWarnings->count() }} active warning(s). Please review your attendance immediately.</div>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 10px; position: relative; z-index: 1;">
            @foreach($activeWarnings as $warning)
            <div style="background: rgba(0,0,0,0.25); padding: 14px 18px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.08);">
                <div style="font-size: 0.88rem; font-weight: 700; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #fca5a5;"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $warning->subject_code }}</span>
                    <span style="opacity: 0.6; font-size: 0.75rem; font-weight: 600;">{{ $warning->created_at->diffForHumans() }}</span>
                </div>
                <div style="font-size: 0.85rem; opacity: 0.9; line-height: 1.4;">{{ $warning->message }}</div>
            </div>
            @endforeach
        </div>
        <div style="margin-top: 18px; display: flex; gap: 12px; position: relative; z-index: 1;">
            <a href="{{ route('excuses') }}" style="background: white; color: #991b1b; padding: 10px 20px; border-radius: 10px; font-size: 0.85rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,0.2)'" onmouseout="this.style.transform=''; this.style.boxShadow=''">
                <i class="bi bi-file-text-fill"></i> Submit Excuse
            </a>
            <a href="{{ route('attendance.records') }}" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2); color: white; padding: 10px 20px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
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
    <div class="fade-up fade-up-2" style="background: linear-gradient(135deg, rgba(220,38,38,0.95) 0%, rgba(190,18,60,0.95) 100%); border-radius: 18px; padding: 20px 24px; color: white; margin-bottom: 20px; position: relative; overflow: hidden; box-shadow: 0 14px 44px rgba(190,18,60,0.3);">
        <div style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 3.5rem; opacity: 0.1;">🎉</div>
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 8px;">
            <div style="width: 42px; height: 42px; background: rgba(255,255,255,0.16); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">🏖️</div>
            <div>
                <div style="font-size: 1.05rem; font-weight: 700;">{{ $todayHoliday->name }}</div>
                <div style="font-size: 0.82rem; opacity: 0.9;">{{ $todayHoliday->type_label }} • {{ $todayHoliday->date->format('F j, Y') }}</div>
            </div>
        </div>
        @if($todayHoliday->description)
        <div style="font-size: 0.85rem; opacity: 0.9; margin-top: 4px;">{{ $todayHoliday->description }}</div>
        @endif
        <div style="background: rgba(255,255,255,0.12); padding: 8px 14px; border-radius: 10px; margin-top: 12px; font-size: 0.82rem; font-weight: 600;">
            <i class="bi bi-info-circle me-2"></i>No classes today — Enjoy your holiday! 🎊
        </div>
    </div>
    @endif

    <!-- UPCOMING HOLIDAYS -->
    @if($upcomingHolidays->count() > 0)
    <div class="fade-up fade-up-2" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 16px 20px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
            <div style="width: 30px; height: 30px; background: rgba(248,113,113,0.16); border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">📅</div>
            <div style="font-size: 0.88rem; font-weight: 700; color: #f8e7d3;">Upcoming Holidays</div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            @foreach($upcomingHolidays as $holiday)
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: rgba(255,255,255,0.04); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                <div>
                    <div style="font-size: 0.85rem; font-weight: 600; color: #f8e7d3;">{{ $holiday->name }}</div>
                    <div style="font-size: 0.72rem; color: rgba(245,234,215,0.55);">{{ $holiday->type_label }}</div>
                </div>
                <div style="font-size: 0.78rem; font-weight: 700; color: {{ $holiday->type_color }};">
                    {{ $holiday->date->format('M j') }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- STATS ROW -->
    <div class="stats-row">
        <div class="stat-card fade-up fade-up-2">
            <div class="stat-icon-wrap" style="background: rgba(34,197,94,0.14); color: #4ade80;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="stat-label">Present</div>
            <div class="stat-value" data-count="{{ $totalPresent }}">0</div>
            <div class="stat-sub">All-time records</div>
        </div>
        <div class="stat-card fade-up fade-up-3">
            <div class="stat-icon-wrap" style="background: rgba(245,158,11,0.14); color: #fbbf24;">
                <i class="bi bi-clock-fill"></i>
            </div>
            <div class="stat-label">Late</div>
            <div class="stat-value" data-count="{{ $totalLate }}">0</div>
            <div class="stat-sub">All-time records</div>
        </div>
        <div class="stat-card fade-up fade-up-4">
            <div class="stat-icon-wrap" style="background: rgba(239,68,68,0.14); color: #f87171;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div class="stat-label">Absent</div>
            <div class="stat-value" data-count="{{ $totalAbsent }}">0</div>
            <div class="stat-sub">All-time records</div>
        </div>
        <div class="stat-card fade-up fade-up-5">
            <div class="stat-icon-wrap" style="background: rgba(216,179,92,0.14); color: var(--gold);">
                <i class="bi bi-book-fill"></i>
            </div>
            <div class="stat-label">Subjects</div>
            <div class="stat-value" data-count="{{ isset($subjects) ? count($subjects) : 0 }}">0</div>
            <div class="stat-sub">Enrolled this semester</div>
        </div>
    </div>

    <!-- STREAK + QUICK ACTIONS ROW -->
    <div class="fade-up fade-up-5" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <div style="font-size: 0.82rem; font-weight: 600; color: rgba(245,234,215,0.55);">
                <span style="color: {{ $attendanceRate >= 75 ? '#4ade80' : '#f87171' }}; font-weight: 800;">{{ $attendanceRate }}%</span> attendance rate
            </div>
        </div>
        <div class="quick-actions" style="margin-bottom: 0;">
            <a href="{{ route('student.classes') }}" class="qa-btn">
                <i class="bi bi-calendar-week"></i> My Schedule
            </a>
            <a href="{{ route('excuses') }}" class="qa-btn">
                <i class="bi bi-file-text"></i> Excuses
            </a>
            <a href="{{ route('attendance.records') }}" class="qa-btn">
                <i class="bi bi-list-check"></i> All Records
            </a>
        </div>
    </div>

    <!-- MAIN BENTO GRID -->
    <div class="bento-grid">

        <!-- TODAY'S SCHEDULE -->
        <div class="bento-item bento-item--schedule fade-up fade-up-5">
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
        <div class="bento-item bento-item--calendar fade-up fade-up-5">
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
        <div class="bento-item bento-item--recent fade-up fade-up-6">
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