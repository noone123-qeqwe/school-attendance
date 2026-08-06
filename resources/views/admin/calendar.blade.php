@extends('layouts.app')

@section('page-title', 'Holiday & Events Calendar')

@section('content')
<div id="holidayCalendarPage" class="holiday-dashboard">
<style>
    /* Styling from previous admin calendar */
    #holidayCalendarPage { padding-bottom: 18px; }
    .holiday-dashboard .adm-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        margin-bottom: 24px;
    }
    .holiday-dashboard .adm-stat {
        flex: 1;
        min-width: 180px;
        padding: 22px 20px;
        border-radius: 24px;
        background: rgba(96, 14, 36, 0.18);
        border: 1px solid rgba(255,255,255,0.12);
        backdrop-filter: blur(16px);
        box-shadow: 0 28px 60px rgba(15,23,42,0.18);
        position: relative;
        overflow: hidden;
        animation: floatUp 0.8s ease both;
    }
    .holiday-dashboard .adm-stat::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top left, rgba(255,255,255,0.15), transparent 36%);
        opacity: 0.7;
        pointer-events: none;
    }
    .holiday-dashboard .adm-stat-val {
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -0.04em;
        margin-bottom: 6px;
        color: #f8fafc;
    }
    .holiday-dashboard .adm-stat-lbl {
        font-size: 0.78rem;
        opacity: 0.76;
        text-transform: uppercase;
        letter-spacing: 0.16em;
        color: #f8fafc;
    }
    .holiday-dashboard .glass-card {
        background: rgba(67, 12, 29, 0.18);
        border: 1px solid rgba(255,255,255,0.14);
        backdrop-filter: blur(20px);
        box-shadow: 0 32px 80px rgba(15,23,42,0.2);
        border-radius: 28px;
    }
    .holiday-dashboard .glass-card .adm-card-head {
        padding: 20px 22px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .holiday-dashboard .glass-card .adm-card-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: #f8fafc;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .holiday-dashboard .glass-card .adm-card-icon {
        background: rgba(255,255,255,0.08);
        color: #ffe4e6;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
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
    .holiday-dashboard .holiday-card {
        border-radius: 22px;
        padding: 18px 20px;
        margin-bottom: 14px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.12);
        backdrop-filter: blur(12px);
        display: flex;
        gap: 16px;
        align-items: flex-start;
        animation: fadeInUp .6s ease both;
    }
    .holiday-dashboard .holiday-card:nth-child(even) {
        background: rgba(255,255,255,0.04);
    }
    .holiday-dashboard .holiday-dot {
        width: 12px;
        height: 12px;
        border-radius: 999px;
        margin-top: 6px;
        flex-shrink: 0;
        box-shadow: 0 0 0 6px rgba(255,255,255,0.03);
    }
    .holiday-dashboard .holiday-name {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 4px;
    }
    .holiday-dashboard .holiday-meta {
        color: #cbd5e1;
        font-size: 0.82rem;
        line-height: 1.5;
    }
    .holiday-dashboard .holiday-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.12);
        margin-top: 8px;
    }
    .holiday-dashboard .holiday-card-actions {
        display: flex;
        gap: 8px;
    }
    .holiday-dashboard .holiday-card-actions button {
        min-width: 40px;
        width: 40px;
        aspect-ratio: 1;
        border-radius: 14px;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: transform .2s ease, background .2s ease;
    }
    .holiday-dashboard .holiday-card-actions button:hover {
        transform: translateY(-2px);
    }
    .holiday-dashboard .holiday-btn-edit {
        background: rgba(255,255,255,0.11);
        color: #bfdbfe;
    }
    .holiday-dashboard .holiday-btn-delete {
        background: rgba(220,38,38,0.16);
        color: #fecaca;
    }
    .holiday-dashboard .adm-input {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14);
        color: #f8fafc;
        border-radius: 12px;
        padding: 10px 14px;
        width: 100%;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .holiday-dashboard .adm-input option {
        background: #1e293b;
        color: #f8fafc;
    }
    .holiday-dashboard select.adm-input {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14);
        color: #f8fafc;
    }
    .holiday-dashboard select.adm-input option {
        background: #1e293b !important;
        color: #f8fafc !important;
    }
    .holiday-dashboard .adm-input::placeholder {
        color: rgba(241,245,249,0.6);
    }
    .holiday-dashboard .adm-input:focus {
        outline: none;
        border-color: rgba(255,255,255,0.32);
        box-shadow: 0 0 0 4px rgba(167,28,48,0.18);
    }
    .holiday-dashboard .form-label {
        color: #e2e8f0;
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 8px;
    }
    .holiday-dashboard .modal-content {
        background: rgba(34, 12, 25, 0.96);
        border: 1px solid rgba(255,255,255,0.18);
        box-shadow: 0 35px 80px rgba(15,23,42,0.35);
        border-radius: 24px;
    }
    .holiday-dashboard .modal-header,
    .holiday-dashboard .modal-footer {
        border-color: rgba(255,255,255,0.08);
    }
    .holiday-dashboard .modal-title {
        color: #f8fafc;
    }
    .holiday-dashboard .adm-card {
        background: transparent;
        border: none;
        box-shadow: none;
    }
    .holiday-dashboard .view-btn {
        border-radius: 14px;
    }
    @keyframes floatUp {
        from { opacity: 0; transform: translateY(18px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
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
    }
    .fc .fc-day-today {
        background: rgba(167,28,48,0.18) !important;
        box-shadow: inset 0 0 0 2px rgba(253,230,138,0.4) !important;
    }
</style>

<div class="adm-stats">
    <div class="adm-stat">
        <div class="adm-stat-val" style="color: #fde68a;">{{ $events->count() }}</div>
        <div class="adm-stat-lbl">Events This Month</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-val" style="color: #10b981;">{{ $events->where('type', 'holiday')->count() }}</div>
        <div class="adm-stat-lbl">Holidays</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-val" style="color: #3b82f6;">{{ $events->where('type', 'class')->count() + $events->where('type', 'exam')->count() }}</div>
        <div class="adm-stat-lbl">Classes & Exams</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-val" style="color: #f59e0b;">{{ $events->where('type', 'meeting')->count() }}</div>
        <div class="adm-stat-lbl">Meetings</div>
    </div>
</div>

<div class="row g-3">
    <!-- Calendar -->
    <div class="col-lg-8">
        <div class="adm-card glass-card">
            <div class="adm-card-head">
                <div class="adm-card-title">
                    <div class="adm-card-icon" style="background: rgba(255,255,255,0.12); color: #fde68a;">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    Holiday & Events Calendar
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
            <div style="padding: 20px;">
                <div id="calendar" style="min-height: 400px;"></div>
            </div>
        </div>
    </div>

    <!-- Event List & Add Form -->
    <div class="col-lg-4">
        <!-- Add Event Form -->
        <div class="adm-card glass-card" style="margin-bottom: 20px;">
            <div class="adm-card-head">
                <div class="adm-card-title">
                    <div class="adm-card-icon" style="background: rgba(255,255,255,0.12); color: #fecaca;">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    Add Event / Holiday
                </div>
            </div>
            <div style="padding: 20px;">
                <form method="POST" action="{{ route('admin.calendar.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Event Name</label>
                        <input type="text" name="name" class="adm-input" placeholder="e.g., Summer Break" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="adm-input" required id="addEventType" onchange="toggleTimeLocation(this.value, 'add')">
                            <option value="holiday">Holiday</option>
                            <option value="class">Class</option>
                            <option value="exam">Exam</option>
                            <option value="meeting">Meeting</option>
                            <option value="school_event">School Event</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="adm-input" required min="{{ now()->format('Y-m-d') }}">
                    </div>
                    
                    <div id="addTimeLocContainer" style="display: none;">
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
                    
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" class="adm-input" rows="2" placeholder="Additional details..."></textarea>
                    </div>
                    
                    <button type="submit" class="adm-btn adm-btn-primary" style="width: 100%;">
                        <i class="bi bi-plus"></i> Add Event
                    </button>
                </form>
            </div>
        </div>

        <!-- Events List -->
        <div class="adm-card glass-card">
            <div class="adm-card-head">
                <div class="adm-card-title">
                    <div class="adm-card-icon" style="background: rgba(255,255,255,0.12); color: #fde68a;">
                        <i class="bi bi-list-ul"></i>
                    </div>
                    This Month's Events
                </div>
            </div>
            <div style="padding: 20px;" id="eventListContainer">
                @forelse($events as $event)
                    @php
                        $color = match($event->type) {
                            'class' => '#3b82f6',
                            'exam' => '#ef4444',
                            'meeting' => '#f59e0b',
                            'school_event' => '#8b5cf6',
                            'holiday' => '#10b981',
                            default => '#6b7280'
                        };
                        $typeLabel = str_replace('_', ' ', $event->type);
                    @endphp
                    <div class="holiday-card">
                        <span class="holiday-dot" style="background: {{ $color }};"></span>
                        <div style="flex: 1;">
                            <div class="holiday-name">{{ $event->name }}</div>
                            <div class="holiday-meta">
                                {{ $event->date->format('M j, Y') }}
                                @if($event->type !== 'holiday')
                                    â€¢ {{ $event->start_time->format('h:i A') }} - {{ $event->end_time->format('h:i A') }}
                                @endif
                            </div>
                            @if($event->location)
                                <div class="holiday-meta" style="margin-top: 2px;">
                                    <i class="bi bi-geo-alt"></i> {{ $event->location }}
                                </div>
                            @endif
                            @if($event->description)
                                <div class="holiday-meta" style="margin-top: 6px; color: #cbd5e1;">
                                    {{ $event->description }}
                                </div>
                            @endif
                            <div class="holiday-chip" style="color: {{ $color }}">{{ $typeLabel }}</div>
                        </div>
                        <div class="holiday-card-actions">
                            <button type="button" onclick="editEvent({{ $event->id }}, '{{ addslashes($event->name) }}', '{{ addslashes($event->description) }}', '{{ $event->type }}', '{{ $event->date->format('Y-m-d') }}', '{{ $event->start_time ? $event->start_time->format('H:i') : '' }}', '{{ $event->end_time ? $event->end_time->format('H:i') : '' }}', '{{ addslashes($event->location) }}')" class="holiday-btn-edit view-btn">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" onclick="deleteEvent({{ $event->id }})" class="holiday-btn-delete view-btn">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div style="padding: 40px 20px; text-align: center; color: #cbd5e1;">
                        <i class="bi bi-calendar-x" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 8px;"></i>
                        <div>No events this month</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil" style="color: #fde68a;"></i>
                    Edit Event
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editEventForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body" style="padding: 20px;">
                    <div class="mb-3">
                        <label class="form-label">Event Name</label>
                        <input type="text" name="name" id="editName" class="adm-input" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" id="editType" class="adm-input" required onchange="toggleTimeLocation(this.value, 'edit')">
                            <option value="holiday">Holiday</option>
                            <option value="class">Class</option>
                            <option value="exam">Exam</option>
                            <option value="meeting">Meeting</option>
                            <option value="school_event">School Event</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" id="editDate" class="adm-input" required>
                    </div>
                    
                    <div id="editTimeLocContainer" style="display: none;">
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
                    
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" id="editDescription" class="adm-input" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="adm-btn adm-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-primary">
                        <i class="bi bi-check"></i> Update Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
let calendar;
let currentYear = {{ $year }};
let currentMonth = {{ $month }};

function toggleTimeLocation(type, prefix) {
    const container = document.getElementById(`${prefix}TimeLocContainer`);
    const start = document.getElementById(`${prefix}StartTime`);
    const end = document.getElementById(`${prefix}EndTime`);
    
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
        initialDate: `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`,
        headerToolbar: false,
        height: 'auto',
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
                            description: event.description
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
            alert(`${info.event.title}\nType: ${props.type.replace('_', ' ')}\n${props.location ? 'Location: ' + props.location + '\n' : ''}${props.description || ''}`);
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
    calendar.refetchEvents();
    
    // Skip updating side panel dynamically for now to keep things simple. Let the page reload via location.href if needed.
    const url = new URL(window.location);
    url.searchParams.set('year', currentYear);
    url.searchParams.set('month', currentMonth);
    window.location.href = url.toString();
}

function editEvent(id, name, description, type, date, startTime, endTime, location) {
    document.getElementById('editName').value = name;
    document.getElementById('editType').value = type;
    document.getElementById('editDate').value = date;
    document.getElementById('editStartTime').value = startTime;
    document.getElementById('editEndTime').value = endTime;
    document.getElementById('editLocation').value = location;
    document.getElementById('editDescription').value = description;
    
    document.getElementById('editEventForm').action = `/admin/calendar/${id}`;
    
    toggleTimeLocation(type, 'edit');
    
    new bootstrap.Modal(document.getElementById('editEventModal')).show();
}

function deleteEvent(id) {
    if (confirm('Are you sure you want to remove this event?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/calendar/${id}`;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</div>
@endsection
