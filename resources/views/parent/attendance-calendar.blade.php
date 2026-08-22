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
    /* ── UNIFIED ATTENDANCE CALENDAR (Dark Squircle Theme) ── */
    .att-cal-wrap {
        background: #0f0a08;
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7);
        padding: 12px;
    }
    .att-cal-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px 20px;
    }
    .att-cal-nav-btn {
        width: 44px; height: 44px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.03);
        color: #cfa46f;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 1.15rem;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
    }
    .att-cal-nav-btn:hover {
        background: rgba(207, 164, 111, 0.15);
        border-color: rgba(207, 164, 111, 0.4);
        color: #ffffff;
        transform: translateY(-1px);
    }
    .att-cal-month {
        font-size: 1.35rem;
        font-weight: 800;
        color: #fdfbf7;
        letter-spacing: -0.02em;
    }
    .att-cal-header {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        padding: 10px 0;
        margin: 0 10px 14px;
        background: rgba(255, 255, 255, 0.025);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 14px;
    }
    .att-cal-header span {
        font-size: 0.78rem;
        font-weight: 800;
        color: #cfa46f;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 0;
    }
    .att-cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 10px;
        padding: 0 10px 16px;
    }
    .att-cal-cell {
        height: 60px;
        min-height: 60px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.04);
        background: rgba(255, 255, 255, 0.025);
        font-size: 1.05rem;
        font-weight: 700;
        color: #f3ede4;
        position: relative;
        cursor: default;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        gap: 3px;
    }
    .att-cal-cell.has-records {
        cursor: pointer;
    }
    .att-cal-cell.has-records:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4);
        border-color: rgba(207, 164, 111, 0.3);
        z-index: 2;
    }
    .att-cal-cell.is-today {
        background: rgba(255, 209, 102, 0.08) !important;
        border: 2px solid #ffd166 !important;
        box-shadow: 0 0 20px rgba(255, 209, 102, 0.25), inset 0 0 12px rgba(255, 209, 102, 0.08) !important;
        color: #ffffff !important;
    }
    .att-cal-cell.is-sunday {
        color: #f87171 !important;
    }

    /* Status colors */
    .att-cal-cell.status-present {
        background: rgba(74, 222, 128, 0.14);
        color: #86efac;
        border: 1.5px solid rgba(74, 222, 128, 0.4);
    }
    .att-cal-cell.status-late {
        background: rgba(251, 191, 36, 0.14);
        color: #fde047;
        border: 1.5px solid rgba(251, 191, 36, 0.4);
    }
    .att-cal-cell.status-absent {
        background: rgba(220, 38, 38, 0.14);
        color: #fca5a5;
        border: 1.5px solid rgba(220, 38, 38, 0.45);
    }
    .att-cal-cell.status-mixed {
        background: rgba(96, 165, 250, 0.14);
        color: #93c5fd;
        border: 1.5px solid rgba(96, 165, 250, 0.4);
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
    
    .premium-select {
        border: 1px solid rgba(207, 164, 111, 0.3);
    }
    .premium-select:focus {
        border-color: rgba(207, 164, 111, 0.8);
        box-shadow: 0 0 0 0.25rem rgba(207, 164, 111, 0.25);
    }
    .premium-select option {
        background-color: #1e293b;
        color: #f3e7cd;
    }
</style>

<div class="mb-4 d-flex justify-content-between align-items-end flex-wrap gap-3">
    <div>
        <h1 style="color: #f3e7cd; font-weight: 800; margin: 0 0 6px 0; font-size: 2rem;">Attendance Calendar</h1>
        <div style="color: #b39b82; font-size: 0.95rem;">
            @if($selectedChild)
                Attendance Calendar for {{ $selectedChild->name }}
            @else
                No student selected.
            @endif
        </div>
    </div>
    
    @if($children->count() > 1)
    <div class="ent-glass-panel px-3 py-2" style="border-radius: 12px; display: inline-flex; align-items: center; gap: 12px;">
        <i class="bi bi-person-badge text-gold" style="font-size: 1.2rem;"></i>
        <form id="child-select-form" method="GET" action="{{ route('parent.attendance.calendar') }}" class="m-0 p-0 d-flex align-items-center">
            <select name="child_id" onchange="document.getElementById('child-select-form').submit()" class="form-select form-select-sm premium-select" style="min-width: 200px; background-color: rgba(0,0,0,0.2); color: #f3e7cd; border-color: rgba(255,255,255,0.1);">
                @foreach($children as $child)
                    <option value="{{ $child->id }}" {{ $selectedChild && $selectedChild->id == $child->id ? 'selected' : '' }}>
                        {{ $child->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
    @endif
</div>

@if(!$selectedChild)
    <div class="alert alert-warning" style="background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3); color: #fbbf24;">
        You currently do not have any students linked to your account.
    </div>
@else
<div class="row justify-content-center">
    <div class="col-lg-12">
        <x-card title="Attendance Calendar" icon="bi bi-calendar-check-fill">
            <x-slot name="headerActions">
                <a href="{{ route('parent.child', $selectedChild) }}" class="btn btn-outline btn-sm">View List Records</a>
            </x-slot>

            <div class="att-cal-wrap">
                {{-- Month Navigation --}}
                <div class="att-cal-nav">
                    <a href="?child_id={{ $selectedChild->id }}&cal_year={{ $prevMonth->year }}&cal_month={{ $prevMonth->month }}" class="att-cal-nav-btn">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <div class="att-cal-month">{{ $calStart->format('F Y') }}</div>
                    @if(!$isLatestMonth)
                        <a href="?child_id={{ $selectedChild->id }}&cal_year={{ $nextMonth->year }}&cal_month={{ $nextMonth->month }}" class="att-cal-nav-btn">
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

                </div>
            </div>
        </x-card>
    </div>
</div>
@endif

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

@endsection
