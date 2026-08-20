@extends('layouts.app')

@section('page-title', 'School & Events Calendar')

@section('content')
<div id="calendarHubPage" class="holiday-dashboard">
<style>
    .holiday-dashboard .glass-card {
        background: rgba(26, 16, 14, 0.65);
        border: 1px solid rgba(255, 215, 145, 0.08);
        backdrop-filter: blur(20px);
        box-shadow: 0 32px 80px rgba(0,0,0,0.5);
        border-radius: 24px;
        overflow: hidden;
    }
    .holiday-dashboard .glass-card .adm-card-head {
        padding: 22px 28px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
    }
    .holiday-dashboard .glass-card .adm-card-title {
        font-size: 1.2rem;
        font-weight: 800;
        color: #f3ede4;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .holiday-dashboard .glass-card .adm-card-icon {
        background: rgba(207, 164, 111, 0.12);
        color: #cfa46f;
        box-shadow: inset 0 0 0 1px rgba(207, 164, 111, 0.25);
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.15rem;
    }
    
    /* ── Calendar View Switcher ── */
    .calendar-view-switcher {
        display: inline-flex;
        align-items: center;
        background: rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(207, 164, 111, 0.2);
        border-radius: 14px;
        padding: 4px;
        gap: 4px;
    }
    .view-switch-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        border-radius: 10px;
        font-size: 0.84rem;
        font-weight: 700;
        color: #b39b82;
        background: transparent;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
    }
    .view-switch-btn:hover {
        color: #f3ede4;
        background: rgba(255, 255, 255, 0.05);
    }
    .view-switch-btn.active {
        background: linear-gradient(135deg, rgba(207, 164, 111, 0.22), rgba(207, 164, 111, 0.08));
        border-color: rgba(207, 164, 111, 0.4);
        color: #cfa46f;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
    }

    .holiday-dashboard .calendar-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: flex-end;
    }
    .holiday-dashboard .calendar-header {
        font-weight: 700;
        color: #f3ede4;
        min-width: 150px;
        text-align: center;
        font-size: 1.05rem;
        letter-spacing: 0.5px;
    }
    .holiday-dashboard .adm-btn-ghost {
        background: rgba(255,255,255,0.04);
        color: #b39b82;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
        padding: 8px 14px;
        font-weight: 600;
        font-size: 0.82rem;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .holiday-dashboard .adm-btn-ghost:hover {
        background: rgba(255,255,255,0.08);
        color: #cfa46f;
        border-color: rgba(207, 164, 111, 0.3);
        transform: translateY(-1px);
    }
    .holiday-dashboard .adm-btn-primary {
        background: linear-gradient(135deg, #cfa46f, #b8893e);
        color: #1a0e0b;
        border: none;
        border-radius: 10px;
        padding: 8px 18px;
        font-weight: 700;
        font-size: 0.82rem;
        box-shadow: 0 4px 16px rgba(207, 164, 111, 0.25);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .holiday-dashboard .adm-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(207, 164, 111, 0.35);
    }
    
    /* FullCalendar Overrides */
    .fc {
        color: #b39b82;
    }
    .fc-theme-standard td, .fc-theme-standard th, .fc-theme-standard .fc-scrollgrid {
        border-color: rgba(255,255,255,0.06) !important;
    }
    .fc .fc-col-header-cell {
        background: rgba(255,255,255,0.02) !important;
        padding: 12px 0;
    }
    .fc .fc-col-header-cell-cushion {
        color: #b39b82;
        text-transform: uppercase;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-decoration: none !important;
    }
    .fc .fc-daygrid-day-number {
        color: #f3ede4;
        font-weight: 600;
        font-size: 0.95rem;
        padding: 12px !important;
        text-decoration: none !important;
    }
    .fc .fc-day-other .fc-daygrid-day-number {
        color: #6b5c4d;
    }
    .fc a {
        text-decoration: none !important;
    }
    .fc .fc-day-today {
        background: rgba(207, 164, 111, 0.06) !important;
        box-shadow: inset 0 0 0 1px rgba(207, 164, 111, 0.3) !important;
    }
    .fc .fc-daygrid-event {
        border: none !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
        border-radius: 6px !important;
        padding: 3px 6px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #1a0e0b !important;
        margin: 2px 6px !important;
        transition: all 0.2s;
    }
    .fc .fc-daygrid-event:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.4) !important;
        filter: brightness(1.1);
    }
    
    /* Legend */
    .legend-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 16px;
        padding: 14px 20px;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.04);
        border-radius: 14px;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.76rem;
        font-weight: 600;
        color: #b39b82;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    
    /* Event Details Modal */
    .event-modal-content {
        background: rgba(20, 10, 8, 0.95);
        border: 1px solid rgba(255,255,255,0.08);
        backdrop-filter: blur(20px);
        box-shadow: 0 32px 80px rgba(0,0,0,0.6);
        border-radius: 20px;
        color: #f3ede4;
    }
    .event-modal-header {
        border-bottom: 1px solid rgba(255,255,255,0.06);
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .event-modal-header .modal-title {
        font-size: 1.15rem;
        font-weight: 800;
        color: #f3ede4;
        margin: 0;
    }
    .event-modal-body {
        padding: 24px;
    }
    .event-detail-item {
        margin-bottom: 18px;
        padding-bottom: 18px;
        border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .event-detail-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    .event-detail-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
        color: #b39b82;
        margin-bottom: 6px;
    }
    .event-detail-value {
        font-size: 0.95rem;
        color: #f8fafc;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }
    .event-type-badge {
        display: inline-flex;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #1a0e0b !important;
    }
</style>

{{-- ─── TOP TOOLBAR WITH CALENDAR SWITCHER ─── --}}
<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 800; color: #f3ede4; margin: 0; display: flex; align-items: center; gap: 10px;">
            <i class="bi bi-calendar-event-fill" style="color: #cfa46f;"></i> Calendar Hub
        </h1>
        <p style="font-size: 0.82rem; color: #b39b82; margin: 4px 0 0 0;">Manage academic schedules, class sessions, exams, and school-wide holidays.</p>
    </div>

    {{-- Switch Button to toggle between the two calendars --}}
    <div class="calendar-view-switcher">
        <button type="button" class="view-switch-btn {{ $activeTab === 'school' ? 'active' : '' }}" id="btnViewSchool" onclick="switchCalendarView('school')">
            <i class="bi bi-calendar3"></i> School Calendar
        </button>
        <button type="button" class="view-switch-btn {{ $activeTab === 'holiday' ? 'active' : '' }}" id="btnViewHoliday" onclick="switchCalendarView('holiday')">
            <i class="bi bi-calendar-heart-fill"></i> Holiday & Events
        </button>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     CALENDAR 1: SCHOOL SCHEDULE & EVENTS (FullCalendar)
     ══════════════════════════════════════════════════════════ --}}
<div id="schoolCalendarSection" style="display: {{ $activeTab === 'school' ? 'block' : 'none' }};">
    <div class="adm-card glass-card">
        <div class="adm-card-head">
            <div class="adm-card-title">
                <div class="adm-card-icon">
                    <i class="bi bi-calendar3"></i>
                </div>
                School Calendar
            </div>
            <div class="calendar-controls">
                <button type="button" onclick="previousMonth()" class="adm-btn adm-btn-ghost" style="padding: 6px 10px;">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <span id="currentMonth" class="calendar-header">
                    {{ \Carbon\Carbon::create($year, $month)->format('F Y') }}
                </span>
                <button type="button" onclick="nextMonth()" class="adm-btn adm-btn-ghost" style="padding: 6px 10px;">
                    <i class="bi bi-chevron-right"></i>
                </button>
                <button type="button" onclick="goToToday()" class="adm-btn adm-btn-ghost" style="margin-left: 6px; margin-right: 6px;">
                    Today
                </button>
                <button type="button" class="adm-btn adm-btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                    <i class="bi bi-plus-lg"></i> Add Event
                </button>
            </div>
        </div>
        
        <div style="padding: 0 24px;">
            <div class="legend-container">
                <div class="legend-item"><div class="legend-dot" style="background: #60a5fa;"></div> Class</div>
                <div class="legend-item"><div class="legend-dot" style="background: #ec4899;"></div> Exam</div>
                <div class="legend-item"><div class="legend-dot" style="background: #a78bfa;"></div> School Event</div>
                <div class="legend-item"><div class="legend-dot" style="background: #4ade80;"></div> Holiday</div>
            </div>
        </div>
        
        <div style="padding: 24px; overflow-x: auto;">
            <div id="calendar" style="min-height: 580px; min-width: 700px;"></div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     CALENDAR 2: HOLIDAY & EVENTS CALENDAR (Grid + Popup)
     ══════════════════════════════════════════════════════════ --}}
@php
    $hcalStart = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $hcalEnd = $hcalStart->copy()->endOfMonth();
    $hcalPrev = $hcalStart->copy()->subMonth();
    $hcalNext = $hcalStart->copy()->addMonth();
    $hcalStartDow = $hcalStart->dayOfWeek;
    $hcalIsCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $hcalToday = now()->day;
@endphp

<div id="holidayCalendarSection" style="display: {{ $activeTab === 'holiday' ? 'block' : 'none' }};">
    <div class="adm-card glass-card">
        <div class="adm-card-head">
            <div class="adm-card-title">
                <div class="adm-card-icon" style="background: rgba(220, 38, 38, 0.12); color: #f87171; box-shadow: inset 0 0 0 1px rgba(220, 38, 38, 0.25);">
                    <i class="bi bi-calendar-heart-fill"></i>
                </div>
                Holiday & Events Calendar
            </div>
            <div class="calendar-controls">
                <button type="button" class="adm-btn adm-btn-primary" onclick="openHcalModal()">
                    <i class="bi bi-plus-lg"></i> Add Holiday / Event
                </button>
            </div>
        </div>
        
        <div style="padding: 24px 28px;">
            <div class="hcal-container" style="display: block;">
                <div class="hcal-calendar-pane" style="width: 100%; max-width: 620px; margin: 0 auto;">
                    <div class="hcal-nav">
                        <a href="?view=holiday&hcal_year={{ $hcalPrev->year }}&hcal_month={{ $hcalPrev->month }}" class="hcal-nav-btn">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <div class="hcal-month-label" style="font-size: 1.05rem;">{{ $hcalStart->format('F Y') }}</div>
                        <a href="?view=holiday&hcal_year={{ $hcalNext->year }}&hcal_month={{ $hcalNext->month }}" class="hcal-nav-btn">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>

                    <div class="hcal-day-labels" style="gap: 6px; margin-bottom: 6px;">
                        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $lbl)
                            <div class="hcal-day-label">{{ $lbl }}</div>
                        @endforeach
                    </div>

                    <div class="hcal-grid" style="gap: 6px;">
                        {{-- Empty cells before first day --}}
                        @for($i = 0; $i < $hcalStartDow; $i++)
                            <div class="hcal-day empty"></div>
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
                            <div class="hcal-day{{ $cls }}" style="height: 48px;" onclick="openHcalDateDetails('{{ $dateKey }}', '{{ $formattedDate }}')" title="{{ $hasEvents ? collect($dayEvents)->pluck('name')->join(', ') : 'Click to view or add events' }}">
                                <div class="hcal-day-num" style="font-size: 0.82rem;">{{ $d }}</div>
                                @if($hasEvents)
                                    <div class="hcal-dots">
                                        @foreach(collect($dayEvents)->unique('type')->take(3) as $evt)
                                            <div class="hcal-dot {{ $evt['type'] }}"></div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endfor
                    </div>

                    <div class="hcal-legend" style="margin-top: 20px; padding-top: 16px; gap: 14px;">
                        <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#dc2626;"></div> National</div>
                        <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#d97706;"></div> Local</div>
                        <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#7c2d12;"></div> School</div>
                        <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#6366f1;"></div> No Class</div>
                        <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#60a5fa;"></div> Announcement</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── CALENDAR EVENT DETAILS POPUP MODAL (For Holiday Grid) ─── --}}
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
            <button type="button" class="hcal-modal-close" onclick="closeHcalDetailsModal()">×</button>
        </div>
        <div class="hcal-modal-body" id="hcalDetailsBody" style="padding: 20px 24px; max-height: 460px; overflow-y: auto;">
            {{-- Dynamically populated --}}
        </div>
        <div class="hcal-modal-footer" id="hcalDetailsFooter" style="padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255, 255, 255, 0.06); background: rgba(0, 0, 0, 0.25);">
            <button type="button" class="hcal-btn-cancel" onclick="closeHcalDetailsModal()">Close</button>
            <button type="button" class="hcal-btn-submit" id="hcalDetailsAddBtn" onclick="addEventForCurrentDate()" style="padding: 8px 18px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-plus-lg"></i> Add Event
            </button>
        </div>
    </div>
</div>

{{-- ─── ADD/EDIT HOLIDAY MODAL ─── --}}
<div class="hcal-modal-overlay" id="hcalModalOverlay">
    <div class="hcal-modal">
        <div class="hcal-modal-header">
            <div class="hcal-modal-title" id="hcalModalTitle">Add Holiday / Event</div>
            <button type="button" class="hcal-modal-close" onclick="closeHcalModal()">×</button>
        </div>
        <form id="hcalForm" method="POST" action="{{ route('admin.calendar.store') }}">
            @csrf
            <div id="hcalMethodField"></div>
            <div class="hcal-modal-body">
                <div class="hcal-form-group">
                    <label class="hcal-form-label">Event Name *</label>
                    <input type="text" name="name" class="hcal-form-input" id="hcalName" required placeholder="e.g. Independence Day">
                </div>
                <div class="hcal-form-group">
                    <label class="hcal-form-label">Date *</label>
                    <input type="date" name="date" class="hcal-form-input" id="hcalDate" required>
                </div>
                <div class="hcal-form-group">
                    <label class="hcal-form-label">Type *</label>
                    <select name="type" class="hcal-form-select" id="hcalType" required>
                        <option value="national">National Holiday</option>
                        <option value="local">Local Holiday</option>
                        <option value="school">School Holiday</option>
                        <option value="no_class">No Classes</option>
                    </select>
                </div>
                <div class="hcal-form-group" style="margin-bottom:0;">
                    <label class="hcal-form-label">Description</label>
                    <textarea name="description" class="hcal-form-textarea" id="hcalDesc" placeholder="Optional description..."></textarea>
                </div>
            </div>
            <div class="hcal-modal-footer">
                <button type="button" class="hcal-btn-cancel" onclick="closeHcalModal()">Cancel</button>
                <button type="submit" class="hcal-btn-submit" id="hcalSubmitBtn">
                    <i class="bi bi-check-lg"></i> Save Event
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ─── FULLCALENDAR: ADD EVENT MODAL ─── --}}
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content event-modal-content w-100">
            <div class="event-modal-header">
                <h5 class="modal-title" style="margin: 0; font-weight: 700;">Add Event / Holiday</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.calendar.store') }}">
                @csrf
                <div class="event-modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Event Name</label>
                            <input type="text" name="name" class="adm-input" placeholder="e.g., Midterm Examinations" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Type</label>
                            <select name="type" class="adm-input" required id="addEventType" onchange="toggleTimeLocation(this.value, 'add')">
                                <option value="class">Class</option>
                                <option value="exam">Exam</option>
                                <option value="meeting">Meeting</option>
                                <option value="school_event">School Event</option>
                                <option value="holiday">Holiday</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="adm-input" required>
                        </div>
                        
                        <div class="col-12" id="addTimeLocContainer" style="display: block;">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="start_time" id="addStartTime" class="adm-input">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="end_time" id="addEndTime" class="adm-input">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" id="addLocation" class="adm-input" placeholder="e.g., Main Auditorium">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Description (Optional)</label>
                            <textarea name="description" class="adm-input" rows="2" placeholder="Additional details..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.06); padding: 16px 32px;">
                    <button type="button" class="adm-btn adm-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-primary">Add Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── FULLCALENDAR: EDIT EVENT MODAL ─── --}}
<div class="modal fade" id="editEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content event-modal-content w-100">
            <div class="event-modal-header">
                <h5 class="modal-title" style="margin: 0; font-weight: 700;">
                    <i class="bi bi-pencil" style="color: #fde68a;"></i> Edit Event
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editEventForm" method="POST">
                @csrf
                @method('PUT')
                <div class="event-modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Event Name</label>
                            <input type="text" name="name" id="editName" class="adm-input" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Type</label>
                            <select name="type" id="editType" class="adm-input" required onchange="toggleTimeLocation(this.value, 'edit')">
                                <option value="class">Class</option>
                                <option value="exam">Exam</option>
                                <option value="meeting">Meeting</option>
                                <option value="school_event">School Event</option>
                                <option value="holiday">Holiday</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" id="editDate" class="adm-input" required>
                        </div>
                        <div class="col-12" id="editTimeLocContainer" style="display: none;">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="start_time" id="editStartTime" class="adm-input">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="end_time" id="editEndTime" class="adm-input">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" id="editLocation" class="adm-input">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description (Optional)</label>
                            <textarea name="description" id="editDescription" class="adm-input" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.06); padding: 16px 32px;">
                    <button type="button" class="adm-btn adm-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-primary">Update Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── FULLCALENDAR: EVENT DETAIL MODAL ─── --}}
<div class="modal fade" id="eventDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content event-modal-content w-100">
            <div class="event-modal-header">
                <h5 class="modal-title" style="margin: 0; font-weight: 700;" id="eventTitle">Event Title</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="event-modal-body">
                <div class="event-detail-item">
                    <div class="event-detail-label">Event Type</div>
                    <div class="event-detail-value">
                        <span id="eventTypeBadge" class="event-type-badge">Type</span>
                    </div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label">Date & Time</div>
                    <div class="event-detail-value">
                        <i class="bi bi-clock" style="color: #94a3b8;"></i>
                        <span id="eventTime">Date</span>
                    </div>
                </div>
                <div class="event-detail-item" id="eventLocationContainer">
                    <div class="event-detail-label">Location</div>
                    <div class="event-detail-value">
                        <i class="bi bi-geo-alt" style="color: #94a3b8;"></i>
                        <span id="eventLocation">Location</span>
                    </div>
                </div>
                <div class="event-detail-item" id="eventDescriptionContainer" style="display:none;">
                    <div class="event-detail-label">Description</div>
                    <div class="event-detail-value" style="font-size: 0.95rem; font-weight: normal;" id="eventDescription"></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.06); padding: 16px 32px; display: flex; justify-content: space-between;">
                <button type="button" id="btnDeleteEvent" class="adm-btn adm-btn-ghost" style="color: #f87171; border-color: rgba(248, 113, 113, 0.3);" onclick="triggerDelete()">Delete Event</button>
                <button type="button" id="btnEditEvent" class="adm-btn adm-btn-primary" onclick="triggerEdit()">Edit Event</button>
            </div>
            
            <form id="deleteEventForm" method="POST" style="display:none;">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script>
window.hcalEventsMap = @json($hcalEventsMap ?? []);
let selectedHcalDate = null;
let selectedHcalDateFormatted = '';
let calendar;
let currentYear = {{ $year }};
let currentMonth = {{ $month }};
let activeEvent = null;

// ─── SWITCH BETWEEN CALENDARS ───
function switchCalendarView(view) {
    const schoolSection = document.getElementById('schoolCalendarSection');
    const holidaySection = document.getElementById('holidayCalendarSection');
    const btnSchool = document.getElementById('btnViewSchool');
    const btnHoliday = document.getElementById('btnViewHoliday');

    if (view === 'holiday') {
        schoolSection.style.display = 'none';
        holidaySection.style.display = 'block';
        btnSchool.classList.remove('active');
        btnHoliday.classList.add('active');
    } else {
        holidaySection.style.display = 'none';
        schoolSection.style.display = 'block';
        btnHoliday.classList.remove('active');
        btnSchool.classList.add('active');
        
        if (calendar) {
            setTimeout(() => {
                calendar.updateSize();
            }, 50);
        }
    }
    
    // Update URL query param quietly
    const url = new URL(window.location);
    url.searchParams.set('view', view);
    window.history.pushState({}, '', url);
}

function toggleTimeLocation(type, prefix) {
    const container = document.getElementById(prefix + 'TimeLocContainer');
    const start = document.getElementById(prefix + 'StartTime');
    const end = document.getElementById(prefix + 'EndTime');
    
    if (type === 'holiday') {
        if (container) container.style.display = 'none';
        if (start) start.required = false;
        if (end) end.required = false;
    } else {
        if (container) container.style.display = 'block';
        if (start) start.required = true;
        if (end) end.required = true;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // ─── Initialize FullCalendar ───
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            initialDate: new Date(currentYear, currentMonth - 1, 1),
            headerToolbar: false,
            height: 'auto',
            firstDay: 0,
            weekends: true,
            dayHeaders: true,
            dayHeaderFormat: { weekday: 'short' },
            showNonCurrentDates: true,
            fixedWeekCount: false,
            events: function(fetchInfo, successCallback, failureCallback) {
                fetch(`{{ route('admin.calendar.data') }}?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
                    .then(response => response.json())
                    .then(data => {
                        const events = data.map(event => ({
                            id: event.id,
                            title: event.title,
                            start: event.start,
                            end: event.end,
                            backgroundColor: event.color,
                            borderColor: event.color,
                            extendedProps: {
                                type: event.type,
                                location: event.location,
                                description: event.description,
                                start_time: event.start_time,
                                end_time: event.end_time
                            }
                        }));
                        successCallback(events);
                    })
                    .catch(error => {
                        console.error('Error fetching calendar data:', error);
                        failureCallback(error);
                    });
            },
            eventClick: function(info) {
                activeEvent = info.event;
                const props = info.event.extendedProps;
                const isAutoHoliday = info.event.id && info.event.id.startsWith('hol_');
                
                document.getElementById('eventTitle').textContent = info.event.title;
                
                const badge = document.getElementById('eventTypeBadge');
                badge.textContent = props.type ? props.type.replace('_', ' ') : 'holiday';
                badge.style.backgroundColor = info.event.backgroundColor;
                
                const start = info.event.start;
                const end = info.event.end;
                
                let timeStr = start ? start.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' }) : '';
                if (props.type !== 'holiday' && !info.event.allDay) {
                    if (start) timeStr += ` • ${start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
                    if (end) {
                        timeStr += ` - ${end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
                    }
                }
                document.getElementById('eventTime').textContent = timeStr;
                
                if (props.location) {
                    document.getElementById('eventLocation').textContent = props.location;
                    document.getElementById('eventLocationContainer').style.display = 'block';
                } else {
                    document.getElementById('eventLocationContainer').style.display = 'none';
                }
                
                if (props.description) {
                    document.getElementById('eventDescription').textContent = props.description;
                    document.getElementById('eventDescriptionContainer').style.display = 'block';
                } else {
                    document.getElementById('eventDescriptionContainer').style.display = 'none';
                }
                
                const btnEdit = document.getElementById('btnEditEvent');
                const btnDelete = document.getElementById('btnDeleteEvent');
                if (isAutoHoliday) {
                    if (btnEdit) btnEdit.style.display = 'none';
                    if (btnDelete) btnDelete.style.display = 'none';
                } else {
                    if (btnEdit) btnEdit.style.display = '';
                    if (btnDelete) btnDelete.style.display = '';
                }
                
                new bootstrap.Modal(document.getElementById('eventDetailModal')).show();
            },
            dayCellClassNames: function(arg) {
                const today = new Date();
                if (arg.date.toDateString() === today.toDateString()) {
                    return ['fc-day-today'];
                }
                return [];
            }
        });
        
        calendar.render();
    }
});

function triggerEdit() {
    if (!activeEvent) return;
    bootstrap.Modal.getInstance(document.getElementById('eventDetailModal')).hide();
    
    const props = activeEvent.extendedProps;
    document.getElementById('editName').value = activeEvent.title;
    document.getElementById('editType').value = props.type;
    document.getElementById('editDate').value = activeEvent.start.toLocaleDateString('en-CA');
    
    if (props.type !== 'holiday' && !activeEvent.allDay) {
        document.getElementById('editStartTime').value = activeEvent.start.toLocaleTimeString('it-IT', {hour: '2-digit', minute:'2-digit'});
        if (activeEvent.end) {
            document.getElementById('editEndTime').value = activeEvent.end.toLocaleTimeString('it-IT', {hour: '2-digit', minute:'2-digit'});
        }
    }
    document.getElementById('editLocation').value = props.location || '';
    document.getElementById('editDescription').value = props.description || '';
    
    document.getElementById('editEventForm').action = `/admin/calendar/${activeEvent.id}`;
    toggleTimeLocation(props.type, 'edit');
    
    new bootstrap.Modal(document.getElementById('editEventModal')).show();
}

function triggerDelete() {
    if (!activeEvent) return;
    if (confirm('Are you sure you want to delete this event?')) {
        const form = document.getElementById('deleteEventForm');
        form.action = `/admin/calendar/${activeEvent.id}`;
        form.submit();
    }
}

function previousMonth() {
    currentMonth--;
    if (currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
    }
    updateCalendar();
}

function nextMonth() {
    currentMonth++;
    if (currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
    }
    updateCalendar();
}

function goToToday() {
    const today = new Date();
    currentYear = today.getFullYear();
    currentMonth = today.getMonth() + 1;
    updateCalendar();
}

function updateCalendar() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('currentMonth').textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;
    
    if (calendar) {
        calendar.gotoDate(new Date(currentYear, currentMonth - 1, 1));
        calendar.refetchEvents();
    }
    
    const url = new URL(window.location);
    url.searchParams.set('year', currentYear);
    url.searchParams.set('month', currentMonth);
    window.history.pushState({}, '', url);
}

// ─── HOLIDAY & EVENTS CALENDAR POPUP & MODALS ───
function openHcalDateDetails(dateKey, formattedDate) {
    selectedHcalDate = dateKey;
    selectedHcalDateFormatted = formattedDate;
    
    document.getElementById('hcalDetailsDateLabel').textContent = formattedDate;
    const body = document.getElementById('hcalDetailsBody');
    const events = window.hcalEventsMap[dateKey] || [];
    
    if (events.length === 0) {
        body.innerHTML = `
            <div style="text-align: center; padding: 36px 16px;">
                <div style="width: 58px; height: 58px; border-radius: 16px; background: rgba(207, 164, 111, 0.08); border: 1px solid rgba(207, 164, 111, 0.2); color: #cfa46f; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; margin: 0 auto 16px;">
                    <i class="bi bi-calendar2-plus"></i>
                </div>
                <h4 style="font-size: 1.05rem; font-weight: 700; color: #f3ede4; margin-bottom: 6px;">No Scheduled Events</h4>
                <p style="font-size: 0.82rem; color: #8f826f; margin-bottom: 20px;">There are no holidays or announcements scheduled for this date.</p>
                <button type="button" class="hcal-btn-submit" onclick="addEventForCurrentDate()" style="margin: 0 auto; display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; font-size: 0.82rem;">
                    <i class="bi bi-plus-lg"></i> Add Event for this Date
                </button>
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
                event: '#a78bfa'
            };
            const borderColor = typeColorMap[evt.type] || '#cfa46f';
            const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
            
            html += `
                <div class="hcal-details-item" data-type="${evt.type}" style="border-left: 4px solid ${borderColor};">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span class="hcal-event-type ${evt.type}" style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="bi ${isHoliday ? 'bi-calendar-heart' : 'bi-megaphone'}"></i>
                            ${evt.type_label || evt.type}
                        </span>
                        ${isHoliday ? `
                            <div style="display: flex; gap: 6px;">
                                <button type="button" class="hcal-event-action-btn" title="Edit Holiday" onclick="editFromDetails(${evt.id}, '${escapeHtml(evt.name)}', '${escapeHtml(evt.description || '')}', '${evt.type}', '${evt.date}')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="/admin/calendar/${evt.id}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this holiday?');">
                                    <input type="hidden" name="_token" value="${csrfToken}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="hcal-event-action-btn danger" title="Delete Holiday"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        ` : ''}
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

function closeHcalDetailsModal() {
    const overlay = document.getElementById('hcalDetailsModalOverlay');
    if (overlay) overlay.classList.remove('active');
}

function addEventForCurrentDate() {
    closeHcalDetailsModal();
    openHcalModal(selectedHcalDate);
}

function editFromDetails(id, name, desc, type, date) {
    closeHcalDetailsModal();
    openHcalEditModal(id, name, desc, type, date);
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

function openHcalModal(prefillDate = null) {
    document.getElementById('hcalModalTitle').textContent = 'Add Holiday / Event';
    document.getElementById('hcalForm').action = '{{ route("admin.calendar.store") }}';
    document.getElementById('hcalMethodField').innerHTML = '';
    document.getElementById('hcalName').value = '';
    document.getElementById('hcalDate').value = prefillDate || selectedHcalDate || '';
    document.getElementById('hcalType').value = 'national';
    document.getElementById('hcalDesc').value = '';
    document.getElementById('hcalSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Save Event';
    document.getElementById('hcalModalOverlay').classList.add('active');
}

function openHcalEditModal(id, name, desc, type, date) {
    document.getElementById('hcalModalTitle').textContent = 'Edit Holiday';
    document.getElementById('hcalForm').action = '/admin/calendar/' + id;
    document.getElementById('hcalMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('hcalName').value = name;
    document.getElementById('hcalDate').value = date;
    document.getElementById('hcalType').value = type;
    document.getElementById('hcalDesc').value = desc;
    document.getElementById('hcalSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update Event';
    document.getElementById('hcalModalOverlay').classList.add('active');
}

function closeHcalModal() {
    const overlay = document.getElementById('hcalModalOverlay');
    if (overlay) overlay.classList.remove('active');
}

document.getElementById('hcalModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeHcalModal();
});
document.getElementById('hcalDetailsModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeHcalDetailsModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeHcalModal();
        closeHcalDetailsModal();
    }
});
</script>
</div>
@endsection
