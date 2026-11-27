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

    $eventsMap = $eventsMap ?? [];

    $dayDotsMap = [];
    for ($d = 1; $d <= $calEnd->day; $d++) {
        $dots = [];
        $dDateKey = \Carbon\Carbon::create($calYear, $calMonth, $d)->format('Y-m-d');

        // 1. Attendance dots (present, late, absent)
        if (isset($dayRecordsMap[$d])) {
            $statuses = collect($dayRecordsMap[$d])->pluck('status')->map(fn($s) => strtolower($s))->unique();
            if ($statuses->contains('present')) $dots[] = 'present';
            if ($statuses->contains('late'))    $dots[] = 'late';
            if ($statuses->contains('absent'))  $dots[] = 'absent';
        }

        // 2. Exam, Event, and Holiday dots
        if (isset($eventsMap[$dDateKey])) {
            $eTypes = collect($eventsMap[$dDateKey])->pluck('type')->unique();
            if ($eTypes->contains('exam'))    $dots[] = 'exam';
            if ($eTypes->contains('event'))   $dots[] = 'event';
            if ($eTypes->contains('holiday')) $dots[] = 'holiday';
        }

        if (!empty($dots)) {
            $dayDotsMap[$d] = array_values(array_unique($dots));
        }
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

@include('partials.student-page-header', ['title' => 'Attendance calendar', 'description' => 'Choose a date to see your attendance and class details.', 'actionUrl' => route('attendance.records'), 'actionLabel' => 'View records'])
<div class="row justify-content-center student-attendance-page">
    <div class="col-lg-10">
        <x-card title="My Attendance Calendar" icon="bi bi-calendar-check-fill">
            <x-slot name="headerActions">
                <a href="{{ route('attendance.records') }}" class="btn btn-outline btn-sm">View List Records</a>
            </x-slot>

            <div class="att-cal-wrap">
                {{-- Month Navigation --}}
                <div class="att-cal-nav">
                    <a href="?cal_year={{ $prevMonth->year }}&cal_month={{ $prevMonth->month }}" class="att-cal-nav-btn" aria-label="Previous month">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <div class="att-cal-month">{{ $calStart->format('F Y') }}</div>
                    @if(!$isLatestMonth)
                        <a href="?cal_year={{ $nextMonth->year }}&cal_month={{ $nextMonth->month }}" class="att-cal-nav-btn" aria-label="Next month">
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
                                } elseif (in_array('absent', $dayStatuses)) {
                                    $cellStatus = 'status-absent';
                                } elseif (in_array('late', $dayStatuses)) {
                                    $cellStatus = 'status-late';
                                } elseif (in_array('present', $dayStatuses)) {
                                    $cellStatus = 'status-present';
                                } elseif (in_array('exam', $dayStatuses)) {
                                    $cellStatus = 'status-exam';
                                } elseif (in_array('event', $dayStatuses)) {
                                    $cellStatus = 'status-event';
                                } elseif (in_array('holiday', $dayStatuses)) {
                                    $cellStatus = 'status-holiday';
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
                             aria-label="{{ $dayDate->format('F j, Y') }}{{ $hasRecords ? ', attendance recorded' : '' }}"
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
        <div class="modal-content">
            <div class="scal-sheet-handle day-modal-sheet-handle"></div>
            <!-- Action bar with Stepper & Close -->
            <div class="day-modal-action-bar">
                <div class="day-nav-stepper">
                    <button type="button" class="day-nav-step-btn" onclick="navigateDayModal(-1)" title="Previous Day" aria-label="Previous Day">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button" class="day-nav-today-btn" onclick="goToTodayModal()" title="Jump to Today">
                        Today
                    </button>
                    <button type="button" class="day-nav-step-btn" onclick="navigateDayModal(1)" title="Next Day" aria-label="Next Day">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <button type="button" class="day-modal-close-btn" data-bs-dismiss="modal" onclick="closeDaySummaryModal()" title="Close Inspector" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <!-- Header Content: Date & Dynamic Micro-badges -->
            <div class="day-modal-header-content">
                <h3 class="day-modal-date" id="daySummaryTitle">Date</h3>
                <div class="day-summary-badges" id="daySummarySubtitle">
                    <span class="day-summary-chip chip-empty" id="daySummaryStatusText">
                        <span id="daySummaryStatusDot" style="width:7px; height:7px; border-radius:50%; background:#D4AF37; display:inline-block;"></span>
                        Attendance Details
                    </span>
                </div>
            </div>
            <!-- Body Content -->
            <div class="px-4 pb-4 pt-1">
                <div id="daySummaryContent" class="d-flex flex-column gap-3">
                    <!-- dynamically populated with ATTENDANCE and EVENTS / IMPORTANT DATES sections -->
                </div>
                <div style="font-size:0.75rem; color:#A49890; text-align:center; margin-top:20px; display:flex; align-items:center; justify-content:center; gap:6px;">
                    <i class="bi bi-shield-check" style="color:#D4AF37;" aria-hidden="true"></i>
                    <span>Real-time records, events, and schedules for this date.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
var attCalendarData = @json($calendarJson);
var eventsCalendarData = @json($eventsMap ?? []);
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

function goToTodayModal() {
    const todayStr = '{{ now()->format("Y-m-d") }}';
    const dt = new Date(todayStr + 'T00:00:00');
    selectCalendarDay(todayStr, dt.getDate(), true);
}

function showAttDetail(dateKey, day) {
    currentSelectedDateKey = dateKey;
    const titleEl = document.getElementById('daySummaryTitle');
    const subtitleEl = document.getElementById('daySummarySubtitle');
    const contentEl = document.getElementById('daySummaryContent');

    const dt = new Date(dateKey + 'T00:00:00');
    const formattedDate = dt.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    if (titleEl) titleEl.textContent = formattedDate;

    const records = attCalendarData[dateKey] || [];
    const dayEvents = (eventsCalendarData && eventsCalendarData[dateKey]) ? eventsCalendarData[dateKey] : [];

    if (records.length === 0 && dayEvents.length === 0) {
        if (subtitleEl) {
            subtitleEl.innerHTML = `
                <span class="day-summary-chip chip-empty">
                    <i class="bi bi-calendar-x text-gold me-1"></i> No records or events
                </span>`;
        }
        if (contentEl) {
            contentEl.innerHTML = `
                <div class="day-empty-card" style="padding: 32px 20px; text-align: center;">
                    <div class="day-empty-icon" style="margin-bottom: 14px;">
                        <i class="bi bi-calendar2-x" style="font-size: 2rem; color: #D4AF37;"></i>
                    </div>
                    <div style="font-weight:700; font-size:1rem; color:#FAF8F5; margin-bottom:6px;">No records or events for this date.</div>
                    <div style="font-size:0.82rem; color:#A49890; max-width:300px; margin:0 auto; line-height:1.5;">There are no attendance records, classes, exams, events, or holidays scheduled for this date.</div>
                </div>`;
        }
    } else {
        // Dynamic summary badges in modal header
        let badgesHtml = '';
        if (records.length > 0) {
            const presentCount = records.filter(r => r.status_lower === 'present').length;
            const lateCount = records.filter(r => r.status_lower === 'late').length;
            const absentCount = records.filter(r => r.status_lower === 'absent').length;

            badgesHtml += `<span class="day-summary-chip chip-count"><i class="bi bi-journal-bookmark-fill me-1" style="color:#D4AF37;"></i> ${records.length} ${records.length === 1 ? 'Subject' : 'Subjects'}</span>`;
            if (presentCount > 0) {
                badgesHtml += `<span class="day-summary-chip chip-present"><i class="bi bi-check-circle-fill me-1"></i> ${presentCount} Present</span>`;
            }
            if (lateCount > 0) {
                badgesHtml += `<span class="day-summary-chip chip-late"><i class="bi bi-clock-fill me-1"></i> ${lateCount} Late</span>`;
            }
            if (absentCount > 0) {
                badgesHtml += `<span class="day-summary-chip chip-absent"><i class="bi bi-x-circle-fill me-1"></i> ${absentCount} Absent</span>`;
            }
        }

        if (dayEvents.length > 0) {
            const holidays = dayEvents.filter(e => e.type === 'holiday');
            const exams = dayEvents.filter(e => e.type === 'exam');
            const events = dayEvents.filter(e => e.type !== 'holiday' && e.type !== 'exam');

            if (holidays.length > 0) {
                badgesHtml += `<span class="day-summary-chip" style="background:rgba(74,222,128,0.15); color:#4ade80; border:1px solid rgba(74,222,128,0.3);"><i class="bi bi-flag-fill me-1"></i> ${holidays.length === 1 ? 'Holiday' : holidays.length + ' Holidays'}</span>`;
            }
            if (exams.length > 0) {
                badgesHtml += `<span class="day-summary-chip" style="background:rgba(212,175,55,0.15); color:#d4af37; border:1px solid rgba(212,175,55,0.3);"><i class="bi bi-award-fill me-1"></i> ${exams.length === 1 ? 'Exam' : exams.length + ' Exams'}</span>`;
            }
            if (events.length > 0) {
                badgesHtml += `<span class="day-summary-chip" style="background:rgba(125,211,252,0.15); color:#7dd3fc; border:1px solid rgba(125,211,252,0.3);"><i class="bi bi-calendar-event-fill me-1"></i> ${events.length === 1 ? 'Event' : events.length + ' Events'}</span>`;
            }
        }
        if (subtitleEl) subtitleEl.innerHTML = badgesHtml;

        let fullHtml = '';

        // 1. ATTENDANCE SECTION
        if (records.length > 0) {
            fullHtml += `
                <div class="day-section-header mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="day-section-icon"><i class="bi bi-journal-check"></i></span>
                        <span class="day-section-label">ATTENDANCE</span>
                    </div>
                    <span class="day-section-badge">${records.length} ${records.length === 1 ? 'Subject' : 'Subjects'}</span>
                </div>
                <div class="d-flex flex-column gap-3 mb-4">
            `;

            records.forEach(r => {
                const st = (r.status_lower || 'absent');
                let iconClass = 'bi-x-circle-fill';
                let clockInColor = '#f87171';
                let borderStatusClass = 'status-border-absent';

                if (st === 'present') {
                    iconClass = 'bi-check-circle-fill';
                    clockInColor = '#4ade80';
                    borderStatusClass = 'status-border-present';
                } else if (st === 'late') {
                    iconClass = 'bi-clock-fill';
                    clockInColor = '#fbbf24';
                    borderStatusClass = 'status-border-late';
                }

                const clockInText = r.clock_in || (st === 'absent' ? 'No clock-in' : '—');
                const isNoClockIn = clockInText.toLowerCase().includes('no clock-in');

                fullHtml += `
                    <div class="att-detail-card ${borderStatusClass}">
                        <div class="att-card-header">
                            <div style="flex:1; min-width:180px;">
                                <div class="att-card-subject-title">${escapeHtml(r.subject)}</div>
                                <div class="att-card-meta">
                                    <span class="att-code-pill">${escapeHtml(r.code)}</span>
                                    <span style="color:rgba(255,255,255,0.2);">•</span>
                                    <span class="att-instructor-pill"><i class="bi bi-person-fill text-gold-muted me-1"></i>${escapeHtml(r.instructor)}</span>
                                </div>
                            </div>
                            <span class="att-status-badge ${st}">
                                <i class="bi ${iconClass}"></i> ${escapeHtml(r.status)}
                            </span>
                        </div>

                        <div class="att-info-grid">
                            <div>
                                <div class="att-grid-col-label">
                                    <i class="bi bi-calendar3 text-gold"></i> Schedule
                                </div>
                                <div class="att-grid-col-val">${escapeHtml(r.schedule)}</div>
                            </div>
                            <div>
                                <div class="att-grid-col-label">
                                    <i class="bi bi-fingerprint text-gold"></i> Actual Clock-in
                                </div>
                                <div class="att-grid-col-val" style="color:${isNoClockIn ? '#f87171' : clockInColor};">
                                    ${isNoClockIn ? '<i class="bi bi-x-circle me-1"></i>' : (st === 'present' ? '<i class="bi bi-check2 me-1"></i>' : '<i class="bi bi-clock-history me-1"></i>')}
                                    ${escapeHtml(clockInText)}
                                </div>
                            </div>
                            ${r.time_out ? `
                            <div>
                                <div class="att-grid-col-label">
                                    <i class="bi bi-box-arrow-right"></i> Clock-out
                                </div>
                                <div class="att-grid-col-val" style="color:#D6CCC6;">${escapeHtml(r.time_out)}</div>
                            </div>` : ''}
                            ${r.remarks ? `
                            <div style="grid-column: 1 / -1;">
                                <div class="att-grid-col-label">
                                    <i class="bi bi-chat-left-text"></i> Remarks
                                </div>
                                <div style="font-size:0.82rem; font-weight:500; color:#F5DFA8;">${escapeHtml(r.remarks)}</div>
                            </div>` : ''}
                        </div>

                        ${st === 'absent' ? `
                        <a href="{{ route('excuses.create_general') }}" class="day-card-excuse-btn">
                            <span><i class="bi bi-file-earmark-medical me-1"></i> File Excuse Request for this Absence</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>` : ''}
                    </div>
                `;
            });

            fullHtml += `</div>`;
        }

        // 2. EVENTS / IMPORTANT DATES SECTION
        if (dayEvents.length > 0) {
            fullHtml += `
                <div class="day-section-header mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="day-section-icon" style="background: transparent; color: #7dd3fc;">
                            <i class="bi bi-calendar-event-fill"></i>
                        </span>
                        <span class="day-section-label">EVENTS / IMPORTANT DATES</span>
                    </div>
                    <span class="day-section-badge" style="background: rgba(125, 211, 252, 0.12); color: #7dd3fc; border: 1px solid rgba(125, 211, 252, 0.25);">
                        ${dayEvents.length} ${dayEvents.length === 1 ? 'Entry' : 'Entries'}
                    </span>
                </div>
                <div class="d-flex flex-column gap-3">
            `;

            dayEvents.forEach(evt => {
                const isExam = evt.type === 'exam';
                const isHoliday = evt.type === 'holiday';

                let badgeText = 'Event';
                let badgeStyle = 'background: rgba(125, 211, 252, 0.15); color: #7dd3fc; border: 1px solid rgba(125, 211, 252, 0.35);';
                let borderStyle = 'border-left: 3px solid #7dd3fc;';
                let iconClass = 'bi-calendar-event-fill';

                if (isExam) {
                    badgeText = 'Exam';
                    badgeStyle = 'background: rgba(212, 175, 55, 0.15); color: #d4af37; border: 1px solid rgba(212, 175, 55, 0.35);';
                    borderStyle = 'border-left: 3px solid #d4af37;';
                    iconClass = 'bi-award-fill';
                } else if (isHoliday) {
                    badgeText = 'Holiday';
                    badgeStyle = 'background: rgba(74, 222, 128, 0.15); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.35);';
                    borderStyle = 'border-left: 3px solid #4ade80;';
                    iconClass = 'bi-flag-fill';
                }

                let metaRows = '';

                // Subject row (e.g. Mathematics for Exam)
                if (evt.subject) {
                    metaRows += `
                        <div style="font-size: 0.92rem; font-weight: 700; color: #D4AF37; margin-top: 6px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-book-half" style="font-size: 0.85rem; color: #D4AF37;"></i>
                            <span>${escapeHtml(evt.subject)}</span>
                        </div>
                    `;
                }

                // Time row (e.g. 9:00 AM – 4:00 PM or 8:00 AM – 10:00 AM)
                if (evt.time) {
                    metaRows += `
                        <div style="font-size: 0.84rem; font-weight: 600; color: #FAF8F5; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-clock-fill" style="font-size: 0.8rem; color: #A49890;"></i>
                            <span>${escapeHtml(evt.time)}</span>
                        </div>
                    `;
                }

                // Location row (e.g. School Gym)
                if (evt.location) {
                    metaRows += `
                        <div style="font-size: 0.84rem; font-weight: 600; color: #D6CCC6; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-geo-alt-fill" style="font-size: 0.8rem; color: #A49890;"></i>
                            <span>${escapeHtml(evt.location)}</span>
                        </div>
                    `;
                }

                // Description row (e.g. No classes)
                if (evt.description && evt.description.trim() !== '') {
                    metaRows += `
                        <div style="font-size: 0.82rem; color: #A49890; margin-top: 6px; line-height: 1.45;">
                            ${escapeHtml(evt.description)}
                        </div>
                    `;
                }

                fullHtml += `
                    <div style="background: #261B1B; border: 1px solid rgba(255, 255, 255,0.08); ${borderStyle} border-radius: 12px; padding: 14px 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 9px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; ${badgeStyle} margin-bottom: 6px;">
                                    <i class="bi ${iconClass}"></i>
                                    <span>${badgeText}</span>
                                </div>
                                <div style="font-weight: 700; color: #FAF8F5; font-size: 1.02rem; line-height: 1.3;">
                                    ${escapeHtml(evt.title)}
                                </div>
                                ${metaRows}
                            </div>
                        </div>
                    </div>
                `;
            });

            fullHtml += `</div>`;
        }

        if (contentEl) contentEl.innerHTML = fullHtml;
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
