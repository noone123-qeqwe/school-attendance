@extends('layouts.app')

@section('page-title', 'School Calendar')

@section('content')
<div id="schoolCalendarPage" class="holiday-dashboard">
<style>
    #schoolCalendarPage { padding-bottom: 18px; }
    .holiday-dashboard .glass-card {
        background: rgba(67, 12, 29, 0.18);
        border: 1px solid rgba(255,255,255,0.14);
        backdrop-filter: blur(20px);
        box-shadow: 0 32px 80px rgba(15,23,42,0.2);
        border-radius: 28px;
    }
    .holiday-dashboard .glass-card .adm-card-head {
        padding: 20px 22px;
    }
    .holiday-dashboard .glass-card .adm-card-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: #f8fafc;
    }
    .holiday-dashboard .glass-card .adm-card-icon {
        background: rgba(255,255,255,0.08);
        color: #ffe4e6;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
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
        color: #f8fafc;
        min-width: 168px;
        text-align: center;
    }
    .holiday-dashboard .adm-btn-ghost {
        background: rgba(255,255,255,0.08);
        color: #f8fafc;
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 12px;
        transition: all 0.2s ease;
    }
    .holiday-dashboard .adm-btn-ghost:hover {
        background: rgba(255,255,255,0.16);
    }
    .holiday-dashboard .adm-btn-primary {
        background: linear-gradient(135deg, #7f1d1d, #3b0215);
        color: #fff;
        border: none;
        border-radius: 12px;
        padding: 6px 16px;
        box-shadow: 0 16px 32px rgba(124,58,58,0.24);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .holiday-dashboard .adm-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 20px 40px rgba(124,58,58,0.32);
    }
    
    .fc .fc-button {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.12);
        color: #f8fafc;
    }
    .fc .fc-button:hover {
        background: rgba(255,255,255,0.16);
    }
    .fc .fc-button-primary {
        background: linear-gradient(135deg, rgba(139,15,22,0.92), rgba(97,6,25,0.92));
        border: none;
    }
    .fc .fc-daygrid-event {
        color: #fff !important;
        border: none !important;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2) !important;
        border-radius: 6px !important;
        padding: 2px 4px;
        font-size: 0.8rem;
    }
    .fc .fc-daygrid-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.3) !important;
    }
    .fc .fc-col-header-cell {
        background: rgba(255,255,255,0.06) !important;
        color: #f8fafc !important;
        padding: 8px 0;
    }
    .fc .fc-day-today {
        background: rgba(167,28,48,0.18) !important;
        box-shadow: inset 0 0 0 2px rgba(253,230,138,0.4) !important;
    }
    .fc .fc-event-title {
        font-weight: 600;
    }
    
    /* Event Details Modal */
    .event-modal-content {
        background: rgba(34, 12, 25, 0.96);
        border: 1px solid rgba(255,255,255,0.18);
        box-shadow: 0 35px 80px rgba(15,23,42,0.35);
        border-radius: 24px;
        color: #f8fafc;
    }
    .event-modal-header {
        border-bottom: 1px solid rgba(255,255,255,0.08);
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .event-modal-body {
        padding: 24px;
    }
    .event-detail-item {
        margin-bottom: 16px;
    }
    .event-detail-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #94a3b8;
        margin-bottom: 4px;
    }
    .event-detail-value {
        font-size: 1rem;
        color: #f8fafc;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .event-type-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .legend-container {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 16px;
        padding: 16px;
        background: rgba(255,255,255,0.04);
        border-radius: 16px;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        color: #e2e8f0;
    }
    .legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
</style>

<div class="row g-3">
    <!-- Calendar -->
    <div class="col-12">
        <div class="adm-card glass-card">
            <div class="adm-card-head">
                <div class="adm-card-title">
                    <div class="adm-card-icon" style="background: rgba(255,255,255,0.12); color: #fde68a;">
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
                    <button type="button" onclick="goToToday()" class="adm-btn adm-btn-primary" style="margin-left: 10px;">
                        Today
                    </button>
                </div>
            </div>
            
            <div style="padding: 0 20px;">
                <div class="legend-container">
                    <div class="legend-item"><div class="legend-dot" style="background: #3b82f6;"></div> Class</div>
                    <div class="legend-item"><div class="legend-dot" style="background: #ef4444;"></div> Exam</div>
                    <div class="legend-item"><div class="legend-dot" style="background: #f59e0b;"></div> Meeting</div>
                    <div class="legend-item"><div class="legend-dot" style="background: #8b5cf6;"></div> School Event</div>
                    <div class="legend-item"><div class="legend-dot" style="background: #10b981;"></div> Holiday</div>
                </div>
            </div>
            
            <div style="padding: 20px;">
                <div id="calendar" style="min-height: 600px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="event-modal-content w-100">
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
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
let calendar;
let currentYear = {{ $year }};
let currentMonth = {{ $month }};

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        initialDate: `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`,
        headerToolbar: false,
        height: 'auto',
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch(`{{ route('student.calendar.data') }}?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
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
                            status: event.status
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
            const props = info.event.extendedProps;
            
            document.getElementById('eventTitle').textContent = info.event.title;
            
            const badge = document.getElementById('eventTypeBadge');
            badge.textContent = props.type.replace('_', ' ');
            badge.style.backgroundColor = info.event.backgroundColor;
            
            const start = info.event.start;
            const end = info.event.end;
            
            let timeStr = start.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });
            if (props.type !== 'holiday' && !info.event.allDay) {
                timeStr += ` â€¢ ${start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
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
});

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
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    
    document.getElementById('currentMonth').textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;
    calendar.gotoDate(`${currentYear}-${String(currentMonth).padStart(2, '0')}-01`);
    
    const url = new URL(window.location);
    url.searchParams.set('year', currentYear);
    url.searchParams.set('month', currentMonth);
    window.history.pushState({}, '', url);
}
</script>
</div>
@endsection
