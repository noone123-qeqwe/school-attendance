@extends('layouts.app')
@section('page-title', 'Attendance Calendar')

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
    $isCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $startDow  = $calStart->dayOfWeek;
    $isLatestMonth = $isCurrentMonth;
@endphp

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

<div class="row justify-content-center">
    <div class="col-lg-10">
        <x-card title="My Attendance Calendar" icon="bi bi-calendar-check-fill">
            <x-slot name="headerActions">
                <a href="{{ route('attendance.records') }}" class="btn btn-outline btn-sm">View List Records</a>
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

@endsection
