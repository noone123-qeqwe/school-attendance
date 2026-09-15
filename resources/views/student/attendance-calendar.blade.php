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
        $statuses = collect($recs)->pluck('status')->map(fn($s) => strtolower($s))->unique();
        if ($statuses->contains('present')) $dots[] = 'present';
        if ($statuses->contains('late'))    $dots[] = 'late';
        if ($statuses->contains('absent'))  $dots[] = 'absent';
        $dayDotsMap[$day] = $dots;
    }

    $calendarJson = [];
    foreach ($dayRecordsMap as $day => $recs) {
        $dateKey = \Carbon\Carbon::create($calYear, $calMonth, $day)->format('Y-m-d');
        $dayName = \Carbon\Carbon::parse($dateKey)->format('l');

        $calendarJson[$dateKey] = collect($recs)->map(function($r) use ($dayName) {
            $sched = null;
            if ($r->subject && $r->subject->schedules) {
                $sched = $r->subject->schedules->firstWhere('day', $dayName) 
                      ?? $r->subject->schedules->first();
            }

            $schedStart = $sched ? \Carbon\Carbon::parse($sched->start_time)->format('g:i A') : null;
            $schedEnd   = $sched ? \Carbon\Carbon::parse($sched->end_time)->format('g:i A') : null;
            $schedText  = ($schedStart && $schedEnd) ? "{$schedStart} – {$schedEnd}" : 'Schedule TBA';

            $clockInTime = $r->time_in 
                ? \Carbon\Carbon::parse($r->time_in)->format('g:i A') 
                : ($r->checked_in_at ? $r->checked_in_at->format('g:i A') : null);

            $clockOutTime = $r->time_out 
                ? \Carbon\Carbon::parse($r->time_out)->format('g:i A') 
                : null;

            $status = $r->status ? ucfirst(strtolower($r->status)) : 'Absent';
            $clockInDisplay = $clockInTime ?? ($status === 'Absent' ? 'No clock-in' : '—');

            $instructorName = $r->subject?->instructorUser?->name 
                           ?? $r->subject?->instructor 
                           ?? 'Instructor TBA';

            $remarks = $r->excuse_note 
                    ?? $r->excuseSubmission?->reason 
                    ?? ($r->excused ? 'Excused Absence' : null);

            return [
                'id'             => $r->id,
                'subject'        => $r->subject->name ?? $r->subject_name ?? $r->subject_code ?? 'Class',
                'code'           => $r->subject_code ?? ($r->subject->code ?? ''),
                'instructor'     => $instructorName,
                'schedule'       => $schedText,
                'schedule_start' => $schedStart,
                'schedule_end'   => $schedEnd,
                'status'         => $status,
                'status_lower'   => strtolower($status),
                'time_in'        => $clockInTime,
                'clock_in'       => $clockInDisplay,
                'time_out'       => $clockOutTime,
                'remarks'        => $remarks,
            ];
        })->values()->toArray();
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
        cursor: pointer !important;
        touch-action: manipulation;
        -webkit-tap-highlight-color: rgba(255, 209, 102, 0.2);
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        gap: 3px;
        user-select: none;
        -webkit-user-select: none;
    }
    .att-cal-cell:empty {
        cursor: default !important;
        pointer-events: none;
    }
    .att-cal-cell:hover:not(:empty) {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4);
        border-color: rgba(207, 164, 111, 0.3);
        z-index: 2;
    }
    .att-cal-cell:active:not(:empty) {
        transform: scale(0.95);
        background: rgba(255, 209, 102, 0.12);
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
    .att-cal-cell.selected {
        border: 2px solid #ffd166 !important;
        box-shadow: 0 0 16px rgba(255, 209, 102, 0.35), inset 0 0 10px rgba(255, 209, 102, 0.08) !important;
        background: rgba(255, 209, 102, 0.07) !important;
    }
    .att-cal-cell.selected span {
        color: #ffd166 !important;
        font-weight: 800;
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

    /* Modal z-indexes & backdrop to guarantee display above mobile navigation */
    #daySummaryModal {
        z-index: 10060 !important;
    }
    .modal-backdrop {
        z-index: 10050 !important;
    }
    .scal-sheet-handle {
        width: 44px;
        height: 5px;
        background: rgba(255, 255, 255, 0.22);
        border-radius: 3px;
        margin: 12px auto 4px auto;
        display: none;
    }

    @media (max-width: 576px) {
        .att-cal-cell { font-size: 0.75rem; border-radius: 8px; }
        .att-cal-dot { width: 4px; height: 4px; }
        .att-cal-nav { padding: 16px 16px 12px; }
        .att-cal-grid { padding: 0 10px 12px; gap: 3px; }
        .att-cal-header { padding: 0 10px; }
        .att-cal-stats { padding: 10px 12px; gap: 4px; }
        .att-cal-stat { padding: 4px 10px; font-size: 0.7rem; }

        /* Responsive Mobile Bottom Sheet for Day Summary Modal */
        #daySummaryModal .modal-dialog {
            margin: 0 !important;
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            max-height: 88vh !important;
            display: flex !important;
            align-items: flex-end !important;
            transform: translateY(0) !important;
        }
        #daySummaryModal .modal-content {
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-top-left-radius: 26px !important;
            border-top-right-radius: 26px !important;
            max-height: 88vh !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            padding-bottom: calc(24px + env(safe-area-inset-bottom, 16px)) !important;
            border-bottom: none !important;
            width: 100% !important;
        }
        .scal-sheet-handle {
            display: block;
        }
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

                            $isSelectedCell = ($isCurrentMonth && $d === $today) || ($calYear == 2026 && $calMonth == 9 && $d == 14);

                            $cellClasses = 'att-cal-cell';
                            if ($hasRecords) $cellClasses .= ' has-records';
                            if ($isTodayCell) $cellClasses .= ' is-today';
                            if ($isSelectedCell) $cellClasses .= ' selected';
                            if ($isSunday && !$hasRecords) $cellClasses .= ' is-sunday';
                            if ($cellStatus) $cellClasses .= ' ' . $cellStatus;
                        @endphp
                        <div class="{{ $cellClasses }}"
                             id="calTile_{{ $dateKey }}"
                             data-date="{{ $dateKey }}"
                             data-day="{{ $d }}"
                             role="button"
                             tabindex="0"
                             onclick="selectCalendarDay('{{ $dateKey }}', {{ $d }})"
                             title="Click to view attendance for {{ $dayDate->format('M d, Y') }}">
                            <span>{{ $d }}</span>
                            @if($hasRecords)
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

{{-- ── Day Summary Inspector Modal (Subject-by-Subject Attendance Details) ───────────────── --}}
<div class="modal fade" id="daySummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background:#0f0a08; border:1px solid rgba(255,255,255,0.1); border-radius:24px; box-shadow:0 30px 80px rgba(0,0,0,0.85);">
            <div class="scal-sheet-handle"></div>
            <div class="d-flex justify-content-between align-items-center px-4 pt-4 pb-3 border-bottom position-relative" style="border-color:rgba(255,255,255,0.06)!important;">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-sm" onclick="navigateDayModal(-1)" title="Previous Day" style="width:32px; height:32px; border-radius:10px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); color:#cfa46f; display:inline-flex; align-items:center; justify-content:center; padding:0;">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div>
                        <h3 style="font-weight:800; font-size:1.2rem; color:#f3ede4; margin:0;" id="daySummaryTitle">Date</h3>
                        <div style="font-size:0.82rem; color:#b39b82; display:flex; align-items:center; gap:8px; margin-top:4px;" id="daySummarySubtitle">
                            <span id="daySummaryStatusDot" style="width:8px; height:8px; border-radius:50%; display:inline-block; background:#ffd166;"></span>
                            <span id="daySummaryStatusText" style="font-weight:600;">Attendance Details</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm" onclick="navigateDayModal(1)" title="Next Day" style="width:32px; height:32px; border-radius:10px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1); color:#cfa46f; display:inline-flex; align-items:center; justify-content:center; padding:0;">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="closeDaySummaryModal()"></button>
            </div>
            <div class="px-4 pb-4 pt-2">
                <div style="font-size:0.75rem; font-weight:800; color:#cfa46f; text-transform:uppercase; letter-spacing:0.08em; margin:16px 0 12px 0;" id="daySummarySectionTitle">Subjects Breakdown</div>
                <div id="daySummaryContent" class="d-flex flex-column gap-3">
                    <!-- dynamically populated -->
                </div>
                <div style="font-size:0.75rem; color:#8f826f; text-align:center; margin-top:18px;">
                    <i class="bi bi-info-circle me-1"></i> Real-time subject-by-subject attendance records for this date.
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
var attCalendarData = @json($calendarJson);
let dayModalInstance = null;
let currentSelectedDateKey = '{{ $calYear }}-{{ str_pad($calMonth, 2, '0', STR_PAD_LEFT) }}-{{ str_pad(($isCurrentMonth && $today ? $today : 14), 2, '0', STR_PAD_LEFT) }}';

function closeDaySummaryModal() {
    const modalEl = document.getElementById('daySummaryModal');
    if (modalEl) {
        if (dayModalInstance) {
            try { dayModalInstance.hide(); } catch (e) {}
        }
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.removeAttribute('aria-modal');
    }
    document.body.classList.remove('modal-open');
    const fb = document.querySelector('.scal-fallback-backdrop');
    if (fb) fb.remove();
}

function selectCalendarDay(dateKey, day, openModal = true) {
    document.querySelectorAll('.att-cal-cell.selected').forEach(el => el.classList.remove('selected'));

    const tile = document.getElementById('calTile_' + dateKey);
    if (tile) {
        tile.classList.add('selected');
    }
    currentSelectedDateKey = dateKey;

    if (openModal) {
        showAttDetail(dateKey, day);
    }
}

function navigateDayModal(delta) {
    if (!currentSelectedDateKey) return;
    const dt = new Date(currentSelectedDateKey + 'T00:00:00');
    dt.setDate(dt.getDate() + delta);
    const y = dt.getFullYear();
    const m = String(dt.getMonth() + 1).padStart(2, '0');
    const d = String(dt.getDate()).padStart(2, '0');
    const newDateKey = `${y}-${m}-${d}`;
    selectCalendarDay(newDateKey, dt.getDate(), true);
}

function showAttDetail(dateKey, day) {
    currentSelectedDateKey = dateKey;
    const titleEl = document.getElementById('daySummaryTitle');
    const subText = document.getElementById('daySummaryStatusText');
    const subDot = document.getElementById('daySummaryStatusDot');
    const contentEl = document.getElementById('daySummaryContent');

    const dt = new Date(dateKey + 'T00:00:00');
    const formattedDate = dt.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    if (titleEl) titleEl.textContent = formattedDate;

    const records = attCalendarData[dateKey] || [];
    if (records.length === 0) {
        if (subDot) subDot.style.background = '#8f826f';
        if (subText) subText.textContent = 'No attendance recorded';
        if (contentEl) {
            contentEl.innerHTML = `
                <div style="text-align:center; padding: 36px 18px; color:#8f826f; background:rgba(255,255,255,0.02); border-radius:18px; border:1px dashed rgba(255,255,255,0.08);">
                    <div style="width:52px; height:52px; border-radius:16px; background:rgba(207,164,111,0.1); border:1px solid rgba(207,164,111,0.2); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; color:#cfa46f; font-size:1.5rem;">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <div style="font-weight:700; font-size:1rem; color:#f3ede4; margin-bottom:6px;">No attendance records for this date.</div>
                    <div style="font-size:0.82rem; color:#8f826f; max-width:280px; margin:0 auto;">There are no recorded class attendance entries for this day. Enjoy your free time or check your schedule!</div>
                </div>`;
        }
    } else {
        const presentCount = records.filter(r => r.status_lower === 'present').length;
        const lateCount = records.filter(r => r.status_lower === 'late').length;
        const absentCount = records.filter(r => r.status_lower === 'absent').length;

        let statusSummary = [];
        if (presentCount > 0) statusSummary.push(`${presentCount} Present`);
        if (lateCount > 0) statusSummary.push(`${lateCount} Late`);
        if (absentCount > 0) statusSummary.push(`${absentCount} Absent`);

        let dotColor = '#10b981';
        if (absentCount > 0 && presentCount === 0 && lateCount === 0) dotColor = '#ef4444';
        else if (lateCount > 0 && absentCount === 0 && presentCount === 0) dotColor = '#f59e0b';
        else if (statusSummary.length > 1) dotColor = '#ffd166';

        if (subDot) subDot.style.background = dotColor;
        if (subText) subText.textContent = `${records.length} ${records.length === 1 ? 'Subject' : 'Subjects'} (${statusSummary.join(', ')})`;

        let html = '';
        records.forEach(r => {
            const st = (r.status_lower || 'absent');
            let iconClass = 'bi-x-circle-fill';
            let clockInColor = '#f87171';

            if (st === 'present') {
                iconClass = 'bi-check-circle-fill';
                clockInColor = '#4ade80';
            } else if (st === 'late') {
                iconClass = 'bi-clock-fill';
                clockInColor = '#fbbf24';
            }

            const clockInText = r.clock_in || (st === 'absent' ? 'No clock-in' : '—');
            const isNoClockIn = clockInText.toLowerCase().includes('no clock-in');

            html += `
                <div class="att-detail-card" style="background:rgba(255,255,255,0.025); border:1px solid rgba(255,255,255,0.06); border-radius:18px; padding:16px 18px; transition:all 0.2s ease;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:12px; flex-wrap:wrap;">
                        <div style="flex:1; min-width:180px;">
                            <div style="font-weight:800; font-size:1.02rem; color:#f3ede4; line-height:1.3; margin-bottom:4px;">${escapeHtml(r.subject)}</div>
                            <div style="font-size:0.8rem; color:#cfa46f; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <span style="background:rgba(207,164,111,0.12); padding:2px 8px; border-radius:6px; font-weight:700; border:1px solid rgba(207,164,111,0.25);">${escapeHtml(r.code)}</span>
                                <span style="color:rgba(255,255,255,0.25);">•</span>
                                <span style="color:#b39b82;"><i class="bi bi-person me-1"></i>${escapeHtml(r.instructor)}</span>
                            </div>
                        </div>
                        <span class="subject-card-badge ${st}" style="padding:6px 12px; border-radius:10px; font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; display:inline-flex; align-items:center; gap:5px;">
                            <i class="bi ${iconClass}"></i> ${escapeHtml(r.status)}
                        </span>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px; background:rgba(0,0,0,0.25); border:1px solid rgba(255,255,255,0.03); border-radius:12px; padding:12px 14px;">
                        <div>
                            <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                                <i class="bi bi-calendar3 me-1"></i> Schedule
                            </div>
                            <div style="font-size:0.85rem; font-weight:600; color:#f3ede4;">${escapeHtml(r.schedule)}</div>
                        </div>
                        <div>
                            <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Actual Clock-in
                            </div>
                            <div style="font-size:0.85rem; font-weight:700; color:${isNoClockIn ? '#f87171' : clockInColor};">
                                ${escapeHtml(clockInText)}
                            </div>
                        </div>
                        ${r.time_out ? `
                        <div>
                            <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                                <i class="bi bi-box-arrow-right me-1"></i> Clock-out
                            </div>
                            <div style="font-size:0.85rem; font-weight:600; color:#b39b82;">${escapeHtml(r.time_out)}</div>
                        </div>` : ''}
                        ${r.remarks ? `
                        <div style="grid-column: 1 / -1;">
                            <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                                <i class="bi bi-chat-left-text me-1"></i> Remarks
                            </div>
                            <div style="font-size:0.82rem; font-weight:500; color:#ffd166;">${escapeHtml(r.remarks)}</div>
                        </div>` : ''}
                    </div>
                </div>
            `;
        });
        if (contentEl) contentEl.innerHTML = html;
    }

    const modalEl = document.getElementById('daySummaryModal');
    if (modalEl) {
        if (modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }
        try {
            if (!dayModalInstance && window.bootstrap && window.bootstrap.Modal) {
                dayModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            }
            if (dayModalInstance) {
                dayModalInstance.show();
            } else {
                throw new Error('Bootstrap Modal not available');
            }
        } catch (err) {
            console.warn('Day summary modal fallback invoked:', err);
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            modalEl.setAttribute('aria-modal', 'true');
            modalEl.removeAttribute('aria-hidden');
            document.body.classList.add('modal-open');
            let fb = document.querySelector('.scal-fallback-backdrop');
            if (!fb) {
                fb = document.createElement('div');
                fb.className = 'modal-backdrop fade show scal-fallback-backdrop';
                fb.style.zIndex = '10050';
                document.body.appendChild(fb);
                fb.onclick = closeDaySummaryModal;
            }
        }
    }
    if (window.triggerHaptic) window.triggerHaptic('light');
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

document.addEventListener('DOMContentLoaded', function() {
    // Relocate modal directly to document.body to escape any CSS transform/stacking context on mobile
    const dModal = document.getElementById('daySummaryModal');
    if (dModal && dModal.parentNode !== document.body) {
        document.body.appendChild(dModal);
    }

    // Direct event listener binding for all non-empty calendar cells
    document.querySelectorAll('.att-cal-cell[data-date]').forEach(tile => {
        tile.addEventListener('click', function(e) {
            const dKey = this.getAttribute('data-date');
            const dNum = parseInt(this.getAttribute('data-day'), 10);
            if (dKey) {
                selectCalendarDay(dKey, dNum, true);
            }
        });
        tile.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const dKey = this.getAttribute('data-date');
                const dNum = parseInt(this.getAttribute('data-day'), 10);
                if (dKey) {
                    selectCalendarDay(dKey, dNum, true);
                }
            }
        });
    });
});
</script>

@endsection
