@extends('layouts.app')

@section('page-title', 'School Events Calendar')

@section('content')
<div id="holidayCalendarPage" class="holiday-dashboard">
<style>
    #schoolCalendarPage { padding-bottom: 18px; }
    .holiday-dashboard .glass-card {
        background: rgba(26, 26, 46, 0.4);
        border: 1px solid rgba(255,255,255,0.06);
        backdrop-filter: blur(20px);
        box-shadow: 0 32px 80px rgba(0,0,0,0.4);
        border-radius: 28px;
    }
    .holiday-dashboard .glass-card .adm-card-head {
        padding: 24px 32px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .holiday-dashboard .glass-card .adm-card-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: #f3e7cd;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .holiday-dashboard .glass-card .adm-card-icon {
        background: rgba(207, 164, 111, 0.1);
        color: #cfa46f;
        box-shadow: inset 0 0 0 1px rgba(207, 164, 111, 0.2);
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.2rem;
    }
    .holiday-dashboard .calendar-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        justify-content: flex-end;
    }
    .holiday-dashboard .calendar-header {
        font-weight: 700;
        color: #f3e7cd;
        min-width: 168px;
        text-align: center;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
    }
    .holiday-dashboard .adm-btn-ghost {
        background: rgba(255,255,255,0.04);
        color: #b39b82;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
        padding: 8px 12px;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .holiday-dashboard .adm-btn-ghost:hover {
        background: rgba(255,255,255,0.08);
        color: #cfa46f;
        border-color: rgba(207, 164, 111, 0.3);
        transform: translateY(-1px);
    }
    .holiday-dashboard .adm-btn-primary {
        background: linear-gradient(135deg, #d4a574, #cfa46f);
        color: #1a1a2e;
        border: none;
        border-radius: 10px;
        padding: 8px 20px;
        font-weight: 700;
        box-shadow: 0 8px 24px rgba(207, 164, 111, 0.2);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .holiday-dashboard .adm-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(207, 164, 111, 0.3);
    }
    
    /* FullCalendar Overrides for Pro Max */
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
        color: #f3e7cd;
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
        background: rgba(207, 164, 111, 0.05) !important;
        box-shadow: inset 0 0 0 1px rgba(207, 164, 111, 0.3) !important;
    }
    .fc .fc-daygrid-event {
        border: none !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
        border-radius: 6px !important;
        padding: 3px 6px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #1a1a2e !important;
        margin: 2px 6px !important;
        transition: all 0.2s;
    }
    .fc .fc-daygrid-event:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.4) !important;
        filter: brightness(1.1);
    }
    .fc .fc-daygrid-day-frame {
        transition: background 0.2s;
    }
    .fc .fc-daygrid-day-frame:hover {
        background: rgba(255,255,255,0.02);
    }
    
    /* Force Saturday to display in case it is hidden by CSS */
    .fc-day-sat, th.fc-day-sat, td.fc-day-sat {
        display: table-cell !important;
    }
    .fc-scrollgrid th:last-child, .fc-scrollgrid td:last-child {
        display: table-cell !important;
    }
    .fc-scrollgrid th:nth-child(7), .fc-scrollgrid td:nth-child(7) {
        display: table-cell !important;
    }
    
    /* Ensure calendar table shows all 7 columns */
    .fc .fc-scrollgrid-sync-table { width: 100% !important; min-width: 100% !important; }
    .fc-col-header { display: table !important; width: 100% !important; }
    .fc-daygrid-body { width: 100% !important; }
    .fc-scrollgrid { width: 100% !important; }
    
    /* Mobile Responsive Enhancements */
    @media (max-width: 768px) {
        /* Mobile Calendar Header */
        .holiday-dashboard .glass-card .adm-card-head {
            flex-direction: column;
            gap: 12px;
            padding: 16px 20px !important;
        }
        
        .holiday-dashboard .calendar-controls {
            width: 100%;
            justify-content: center !important;
        }
        
        .holiday-dashboard .calendar-controls button {
            min-width: 36px;
            padding: 6px 10px !important;
        }
        
        .holiday-dashboard .calendar-header {
            min-width: 140px !important;
            font-size: 0.95rem !important;
        }
        
        /* Legend mobile */
        .legend-container {
            padding: 12px 16px !important;
            gap: 12px !important;
        }
        
        .legend-item {
            font-size: 0.75rem !important;
            gap: 8px !important;
        }
        
        .legend-dot {
            width: 8px !important;
            height: 8px !important;
        }
        
        /* Calendar Container */
        .holiday-dashboard .glass-card > div[style*="padding"] {
            padding: 8px !important;
        }
        
        /* Optimize FullCalendar for mobile */
        .fc {
            font-size: 0.75rem;
        }
        
        .fc .fc-col-header-cell {
            padding: 8px 2px !important;
        }
        
        .fc .fc-col-header-cell-cushion {
            font-size: 0.65rem !important;
            letter-spacing: 0.5px !important;
        }
        
        .fc .fc-daygrid-day-number {
            font-size: 0.8rem !important;
            padding: 6px 4px !important;
        }
        
        .fc .fc-daygrid-day-frame {
            min-height: 60px;
        }
        
        .fc .fc-daygrid-event {
            font-size: 0.65rem !important;
            padding: 2px 4px !important;
            margin: 1px 2px !important;
        }
        
        /* Better touch targets */
        .fc .fc-daygrid-day-top {
            padding: 4px;
        }
        
        /* Event Modal Mobile */
        .event-modal-content {
            margin: 0.5rem;
            border-radius: 16px !important;
        }
        
        .event-modal-header {
            padding: 16px 20px !important;
        }
        
        .event-modal-header .modal-title {
            font-size: 1rem !important;
        }
        
        .event-modal-body {
            padding: 20px !important;
        }
        
        .event-detail-item {
            margin-bottom: 16px !important;
            padding-bottom: 16px !important;
        }
        
        .event-detail-label {
            font-size: 0.7rem !important;
        }
        
        .event-detail-value {
            font-size: 0.85rem !important;
        }
        
        /* Add/Edit Modal Mobile */
        .modal-dialog-centered,
        .modal-dialog-lg {
            margin: 0.5rem !important;
            max-width: calc(100% - 1rem) !important;
        }
        
        .modal-footer {
            flex-direction: column;
            gap: 8px;
        }
        
        .modal-footer button {
            width: 100% !important;
        }
        
        /* Form inputs mobile */
        .holiday-dashboard .adm-input {
            font-size: 0.9rem !important;
            padding: 8px 12px !important;
        }
        
        .holiday-dashboard .form-label {
            font-size: 0.75rem !important;
        }
        
        /* Row columns mobile */
        .row.g-3 > .col-12 {
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
        }
        
        .row.g-3 .col-6 {
            padding-left: 0.375rem !important;
            padding-right: 0.375rem !important;
        }
    }
    
    /* Legend */
    .legend-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 24px;
        padding: 16px 24px;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.04);
        border-radius: 16px;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #b39b82;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    
    /* Inputs and Chips for Modals */
    .holiday-dashboard .adm-input {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        color: #f3e7cd;
        border-radius: 10px;
        padding: 10px 14px;
        width: 100%;
        transition: all 0.2s ease;
    }
    .holiday-dashboard .adm-input:focus {
        outline: none;
        border-color: rgba(207, 164, 111, 0.4);
        box-shadow: 0 0 0 3px rgba(207, 164, 111, 0.15);
    }
    .holiday-dashboard .form-label {
        color: #b39b82;
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .invitee-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }
    .chip {
        background: rgba(207, 164, 111, 0.1);
        border: 1px solid rgba(207, 164, 111, 0.2);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #cfa46f;
    }
    .chip button {
        background: none;
        border: none;
        color: #cfa46f;
        padding: 0;
        display: flex;
        align-items: center;
        cursor: pointer;
        opacity: 0.7;
    }
    .chip button:hover {
        opacity: 1;
    }
    
    .search-results {
        background: rgba(15, 15, 20, 0.95);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        margin-top: 4px;
        max-height: 200px;
        overflow-y: auto;
        position: absolute;
        width: 100%;
        z-index: 10;
        display: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .search-result-item {
        padding: 12px 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .search-result-item:last-child {
        border-bottom: none;
    }
    .search-result-item:hover {
        background: rgba(255,255,255,0.05);
    }

    /* Event Details Modal Pro Max */
    .event-modal-content {
        background: rgba(10, 10, 10, 0.95);
        border: 1px solid rgba(255,255,255,0.08);
        backdrop-filter: blur(20px);
        box-shadow: 0 32px 80px rgba(0,0,0,0.6);
        border-radius: 24px;
        color: #f3e7cd;
    }
    .event-modal-header {
        border-bottom: 1px solid rgba(255,255,255,0.06);
        padding: 24px 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .event-modal-header .modal-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: #f3e7cd;
        margin: 0;
    }
    .event-modal-body {
        padding: 32px;
    }
    .event-detail-item {
        margin-bottom: 24px;
        padding-bottom: 24px;
        border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .event-detail-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    .event-detail-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
        color: #b39b82;
        margin-bottom: 8px;
    }
    .event-detail-value {
        font-size: 1.05rem;
        color: #f8fafc;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
    }
    .event-type-badge {
        display: inline-flex;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #1a1a2e !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
</style>

<div class="row g-3">
    <!-- Calendar -->
    <div class="col-12">
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
                    <button type="button" onclick="goToToday()" class="adm-btn adm-btn-ghost" style="margin-left: 10px; margin-right: 10px;">
                        Today
                    </button>
                    <button type="button" class="adm-btn adm-btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                        <i class="bi bi-plus-lg"></i> Add Event
                    </button>
                </div>
            </div>
            
            <div style="padding: 0 20px;">
                <div class="legend-container">
                    <div class="legend-item"><div class="legend-dot" style="background: #60a5fa;"></div> Class</div>
                    <div class="legend-item"><div class="legend-dot" style="background: #ec4899;"></div> Exam</div>
                    <div class="legend-item"><div class="legend-dot" style="background: #a78bfa;"></div> School Event</div>
                    <div class="legend-item"><div class="legend-dot" style="background: #4ade80;"></div> Holiday</div>
                </div>
            </div>
            
            <div style="padding: 20px; overflow-x: auto;">
                <div id="calendar" style="min-height: 600px; min-width: 700px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add Event Modal -->
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
                            <input type="text" name="name" class="adm-input" placeholder="e.g., Summer Break" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Type</label>
                            <select name="type" class="adm-input" required id="addEventType" onchange="toggleTimeLocation(this.value, 'add')">
                                <option value="class">Class</option>
                                <option value="exam">Exam</option>
                                <option value="meeting">Meeting</option>
                                <option value="school_event">School Event</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="adm-input" required min="{{ now()->format('Y-m-d') }}">
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
                                <input type="text" name="location" id="addLocation" class="adm-input" placeholder="e.g., Auditorium">
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

<!-- Edit Event Modal -->
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

<!-- Event Detail Modal (For generic viewing / delete) -->
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
                    <div class="event-detail-value" style="font-size: 0.95rem; font-weight: normal;" id="eventDescription">
                    </div>
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
let calendar;
let currentYear = {{ $year }};
let currentMonth = {{ $month }};
let activeEvent = null;

function toggleTimeLocation(type, prefix) {
    const container = document.getElementById(prefix + 'TimeLocContainer');
    const start = document.getElementById(prefix + 'StartTime');
    const end = document.getElementById(prefix + 'EndTime');
    
    if (type === 'holiday') {
        container.style.display = 'none';
        start.required = false;
        end.required = false;
    } else {
        container.style.display = 'block';
        start.required = true;
        end.required = true;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        initialDate: new Date(currentYear, currentMonth - 1, 1),
        headerToolbar: false,
        height: 'auto',
        firstDay: 0,
        weekends: true,
        hiddenDays: [],
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
            
            // Hide Edit/Delete buttons for auto-seeded holidays (read-only)
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
    
    // Mobile touch enhancements
    if (window.innerWidth <= 768) {
        // Add swipe gesture support for month navigation
        let touchStartX = 0;
        let touchEndX = 0;
        const calendarEl = document.getElementById('calendar');
        
        calendarEl.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        calendarEl.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipeGesture();
        }, { passive: true });
        
        function handleSwipeGesture() {
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    // Swiped left - next month
                    nextMonth();
                } else {
                    // Swiped right - previous month
                    previousMonth();
                }
            }
        }
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
    
    calendar.gotoDate(new Date(currentYear, currentMonth - 1, 1));
    calendar.refetchEvents();
    
    const url = new URL(window.location); url.searchParams.set('year', currentYear); url.searchParams.set('month', currentMonth); window.history.pushState({}, '', url);
}
</script>
</div>
@endsection
