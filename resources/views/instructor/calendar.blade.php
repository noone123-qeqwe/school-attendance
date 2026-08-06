@extends('layouts.app')

@section('page-title', 'My Calendar')

@section('content')
<div id="instructorCalendarPage" class="holiday-dashboard">
<style>
    /* Add base styling similar to the student view */
    #instructorCalendarPage { padding-bottom: 18px; }
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
        gap: 10px;
        align-items: center;
    }
    .holiday-dashboard .calendar-header {
        font-weight: 700;
        color: #f8fafc;
        min-width: 140px;
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
        padding: 8px 16px;
        box-shadow: 0 16px 32px rgba(124,58,58,0.24);
        transition: transform .2s ease, box-shadow .2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
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
        cursor: pointer;
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

    /* Modal Styling */
    .holiday-dashboard .modal-content {
        background: rgba(34, 12, 25, 0.96);
        border: 1px solid rgba(255,255,255,0.18);
        box-shadow: 0 35px 80px rgba(15,23,42,0.35);
        border-radius: 24px;
        color: #f8fafc;
    }
    .holiday-dashboard .modal-header {
        border-bottom: 1px solid rgba(255,255,255,0.08);
        padding: 20px 24px;
    }
    .holiday-dashboard .modal-body {
        padding: 24px;
    }
    .holiday-dashboard .modal-footer {
        border-top: 1px solid rgba(255,255,255,0.08);
        padding: 16px 24px;
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
    .holiday-dashboard .adm-input:focus {
        outline: none;
        border-color: rgba(255,255,255,0.32);
        box-shadow: 0 0 0 4px rgba(167,28,48,0.18);
    }
    .holiday-dashboard .form-label {
        color: #cbd5e1;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    /* Search and Chips */
    .invitee-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }
    .chip {
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 6px;
        color: #e2e8f0;
    }
    .chip button {
        background: none;
        border: none;
        color: #94a3b8;
        padding: 0;
        display: flex;
        align-items: center;
        cursor: pointer;
    }
    .chip button:hover {
        color: #f8fafc;
    }
    .search-results {
        background: #1e293b;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        margin-top: 4px;
        max-height: 200px;
        overflow-y: auto;
        position: absolute;
        width: 100%;
        z-index: 10;
        display: none;
    }
    .search-result-item {
        padding: 10px 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .search-result-item:hover {
        background: rgba(255,255,255,0.05);
    }
    .bulk-invite-btn {
        background: rgba(59, 130, 246, 0.15);
        border: 1px solid rgba(59, 130, 246, 0.3);
        color: #bfdbfe;
        border-radius: 8px;
        padding: 4px 10px;
        font-size: 0.75rem;
        cursor: pointer;
        transition: background 0.2s;
    }
    .bulk-invite-btn:hover {
        background: rgba(59, 130, 246, 0.25);
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
                    My Calendar
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
                    <button type="button" class="adm-btn adm-btn-primary" data-bs-toggle="modal" data-bs-target="#newMeetingModal">
                        <i class="bi bi-plus-lg"></i> New Meeting
                    </button>
                </div>
            </div>
            
            <div style="padding: 20px;">
                <div id="calendar" style="min-height: 600px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- New Meeting Modal -->
<div class="modal fade" id="newMeetingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule New Meeting</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="newMeetingForm" onsubmit="handleCreateMeeting(event)">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Meeting Name</label>
                            <input type="text" name="name" class="adm-input" required placeholder="e.g. Parent-Teacher Conference">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="adm-input" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="adm-input" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="adm-input" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location (Optional)</label>
                            <input type="text" name="location" class="adm-input" placeholder="e.g. Room 302 or Zoom Link">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description (Optional)</label>
                            <textarea name="description" class="adm-input" rows="2"></textarea>
                        </div>
                        
                        <!-- Invitees Section -->
                        <div class="col-12 mt-4">
                            <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                                <span>Invitees</span>
                                <div style="display: flex; gap: 8px;">
                                    <select id="searchRole" class="adm-input" style="padding: 2px 8px; font-size: 0.8rem; width: auto;">
                                        <option value="">Any Role</option>
                                        <option value="Student">Student</option>
                                        <option value="Parent-Guardian">Parent/Guardian</option>
                                        <option value="Instructor">Instructor</option>
                                    </select>
                                </div>
                            </label>
                            
                            <div style="position: relative;">
                                <input type="text" id="inviteeSearch" class="adm-input" placeholder="Search by name or email..." onkeyup="debounceSearch(this.value)">
                                <div id="searchResults" class="search-results"></div>
                            </div>
                            
                            <!-- Quick Bulk Invites -->
                            @if($classes->isNotEmpty())
                                <div class="mt-2" style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    @foreach($classes as $class)
                                        <button type="button" class="bulk-invite-btn" onclick="addBulkGroup('class_students', {{ $class->id }}, '{{ addslashes($class->name) }} Students')">
                                            + All {{ $class->name }} Students
                                        </button>
                                        <button type="button" class="bulk-invite-btn" onclick="addBulkGroup('class_parents', {{ $class->id }}, '{{ addslashes($class->name) }} Parents')">
                                            + All {{ $class->name }} Parents
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            
                            <div id="selectedInvitees" class="invitee-chips"></div>
                            
                            <!-- Hidden inputs will be appended here via JS -->
                            <div id="hiddenInviteeInputs"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="adm-btn adm-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-primary">Schedule Meeting</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Reschedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="rescheduleForm" onsubmit="handleReschedule(event)">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="rescheduleEventId">
                    <input type="hidden" id="rescheduleDate" name="date">
                    <input type="hidden" id="rescheduleStart" name="start_time">
                    <input type="hidden" id="rescheduleEnd" name="end_time">
                    
                    <p style="color: #cbd5e1; margin-bottom: 20px;">You are moving <strong id="rescheduleEventName" style="color: #f8fafc;"></strong> to a new time. Please provide a reason to notify attendees.</p>
                    
                    <div class="mb-3">
                        <label class="form-label">New Location (Optional)</label>
                        <input type="text" id="rescheduleLocation" name="location" class="adm-input">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Rescheduling</label>
                        <textarea name="reschedule_reason" class="adm-input" rows="3" required placeholder="e.g. Instructor is unavailable..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="adm-btn adm-btn-ghost" data-bs-dismiss="modal" onclick="calendar.refetchEvents()">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-primary">Confirm & Notify</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Event Modal -->
<div class="modal fade" id="viewEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewEventTitle">Event Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase;">Date & Time</label>
                    <div id="viewEventTime" style="font-size: 1.05rem;"></div>
                </div>
                <div class="mb-3" id="viewLocationContainer">
                    <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase;">Location</label>
                    <div id="viewEventLocation" style="font-size: 1.05rem;"></div>
                </div>
                <div class="mb-3" id="viewReasonContainer" style="display: none; padding: 12px; background: rgba(245, 158, 11, 0.1); border-left: 3px solid #f59e0b; border-radius: 4px;">
                    <label style="font-size: 0.75rem; text-transform: uppercase; color: #fcd34d;">Reschedule Reason</label>
                    <div id="viewEventReason" style="font-size: 0.95rem; margin-top: 4px;"></div>
                </div>
                
                <div id="viewAttendeesContainer" class="mt-4">
                    <label class="form-label" style="font-size: 0.75rem; text-transform: uppercase;">Attendees</label>
                    <div id="viewEventAttendees" style="max-height: 200px; overflow-y: auto;"></div>
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
let selectedInvitees = [];
let selectedGroups = [];

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        initialDate: `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`,
        headerToolbar: false,
        height: 'auto',
        editable: true,
        droppable: true,
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch(`{{ route('teacher.calendar.data') }}?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
                .then(response => response.json())
                .then(data => {
                    const events = data.map(event => ({
                        id: event.id,
                        title: event.title,
                        start: event.start,
                        end: event.end,
                        backgroundColor: event.color,
                        borderColor: event.color,
                        editable: event.editable,
                        extendedProps: {
                            type: event.type,
                            location: event.location,
                            status: event.status,
                            reschedule_reason: event.reschedule_reason,
                            attendees: event.attendees
                        }
                    }));
                    successCallback(events);
                })
                .catch(error => {
                    console.error('Error fetching calendar data:', error);
                    failureCallback(error);
                });
        },
        eventDrop: function(info) {
            if (!info.event.extendedProps.editable) {
                info.revert();
                alert("You don't have permission to edit this event.");
                return;
            }
            
            // Show reschedule modal
            document.getElementById('rescheduleEventId').value = info.event.id;
            document.getElementById('rescheduleEventName').textContent = info.event.title;
            
            const start = info.event.start;
            const end = info.event.end || new Date(start.getTime() + 60*60*1000);
            
            document.getElementById('rescheduleDate').value = start.toLocaleDateString('en-CA');
            document.getElementById('rescheduleStart').value = start.toLocaleTimeString('it-IT', {hour: '2-digit', minute:'2-digit'});
            document.getElementById('rescheduleEnd').value = end.toLocaleTimeString('it-IT', {hour: '2-digit', minute:'2-digit'});
            document.getElementById('rescheduleLocation').value = info.event.extendedProps.location || '';
            
            new bootstrap.Modal(document.getElementById('rescheduleModal')).show();
        },
        eventResize: function(info) {
             if (!info.event.extendedProps.editable) {
                info.revert();
                return;
            }
            
            // Similar to drop
            document.getElementById('rescheduleEventId').value = info.event.id;
            document.getElementById('rescheduleEventName').textContent = info.event.title;
            
            const start = info.event.start;
            const end = info.event.end;
            
            document.getElementById('rescheduleDate').value = start.toLocaleDateString('en-CA');
            document.getElementById('rescheduleStart').value = start.toLocaleTimeString('it-IT', {hour: '2-digit', minute:'2-digit'});
            document.getElementById('rescheduleEnd').value = end.toLocaleTimeString('it-IT', {hour: '2-digit', minute:'2-digit'});
            document.getElementById('rescheduleLocation').value = info.event.extendedProps.location || '';
            
            new bootstrap.Modal(document.getElementById('rescheduleModal')).show();
        },
        eventClick: function(info) {
            const props = info.event.extendedProps;
            document.getElementById('viewEventTitle').textContent = info.event.title;
            
            const start = info.event.start;
            const end = info.event.end;
            let timeStr = start.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });
            if (props.type !== 'holiday' && !info.event.allDay) {
                timeStr += ` â€¢ ${start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
                if (end) timeStr += ` - ${end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
            }
            document.getElementById('viewEventTime').textContent = timeStr;
            
            if (props.location) {
                document.getElementById('viewEventLocation').textContent = props.location;
                document.getElementById('viewLocationContainer').style.display = 'block';
            } else {
                document.getElementById('viewLocationContainer').style.display = 'none';
            }
            
            if (props.status === 'rescheduled' && props.reschedule_reason) {
                document.getElementById('viewEventReason').textContent = props.reschedule_reason;
                document.getElementById('viewReasonContainer').style.display = 'block';
            } else {
                document.getElementById('viewReasonContainer').style.display = 'none';
            }
            
            if (props.attendees && props.attendees.length > 0) {
                let html = '';
                props.attendees.forEach(a => {
                    const color = a.response === 'accepted' ? '#10b981' : (a.response === 'declined' ? '#ef4444' : '#94a3b8');
                    html += `<div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <span>${a.name}</span>
                                <span style="color: ${color}; text-transform: capitalize; font-size: 0.8rem;">${a.response}</span>
                             </div>`;
                });
                document.getElementById('viewEventAttendees').innerHTML = html;
                document.getElementById('viewAttendeesContainer').style.display = 'block';
            } else {
                document.getElementById('viewAttendeesContainer').style.display = 'none';
            }
            
            new bootstrap.Modal(document.getElementById('viewEventModal')).show();
        }
    });
    
    calendar.render();
});

// Navigation functions (previousMonth, nextMonth, goToToday, updateCalendar) are same as student
function previousMonth() { currentMonth--; if(currentMonth < 1) { currentMonth = 12; currentYear--; } updateCalendar(); }
function nextMonth() { currentMonth++; if(currentMonth > 12) { currentMonth = 1; currentYear++; } updateCalendar(); }
function goToToday() { const today = new Date(); currentYear = today.getFullYear(); currentMonth = today.getMonth() + 1; updateCalendar(); }
function updateCalendar() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('currentMonth').textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;
    calendar.gotoDate(`${currentYear}-${String(currentMonth).padStart(2, '0')}-01`);
    const url = new URL(window.location); url.searchParams.set('year', currentYear); url.searchParams.set('month', currentMonth); window.history.pushState({}, '', url);
}

// Search & Invites
let searchTimeout;
function debounceSearch(query) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => searchInvitees(query), 300);
}

function searchInvitees(query) {
    if (query.length < 2) {
        document.getElementById('searchResults').style.display = 'none';
        return;
    }
    
    const role = document.getElementById('searchRole').value;
    
    fetch(`{{ route('teacher.calendar.search-invitees') }}?q=${encodeURIComponent(query)}&role=${encodeURIComponent(role)}`)
        .then(res => res.json())
        .then(data => {
            const results = document.getElementById('searchResults');
            if (data.data.length === 0) {
                results.innerHTML = '<div class="search-result-item" style="color: #94a3b8;">No users found</div>';
            } else {
                results.innerHTML = data.data.map(user => `
                    <div class="search-result-item" onclick="addInvitee(${user.id}, '${user.name.replace(/'/g, "\\'")}')">
                        <div>
                            <div style="font-weight: 600; color: #f8fafc;">${user.name}</div>
                            <div style="font-size: 0.75rem; color: #94a3b8;">${user.role} â€¢ ${user.email}</div>
                        </div>
                    </div>
                `).join('');
            }
            results.style.display = 'block';
        });
}

function addInvitee(id, name) {
    if (!selectedInvitees.some(i => i.id === id)) {
        selectedInvitees.push({id, name});
        renderChips();
    }
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('inviteeSearch').value = '';
}

function addBulkGroup(type, classId, label) {
    if (!selectedGroups.some(g => g.type === type && g.classId === classId)) {
        selectedGroups.push({type, classId, label});
        renderChips();
    }
}

function removeInvitee(id) {
    selectedInvitees = selectedInvitees.filter(i => i.id !== id);
    renderChips();
}

function removeGroup(type, classId) {
    selectedGroups = selectedGroups.filter(g => !(g.type === type && g.classId === classId));
    renderChips();
}

function renderChips() {
    let html = '';
    
    // Render individual invitees
    selectedInvitees.forEach(inv => {
        html += `<div class="chip">
                    ${inv.name}
                    <button type="button" onclick="removeInvitee(${inv.id})"><i class="bi bi-x"></i></button>
                 </div>`;
    });
    
    // Render bulk groups
    selectedGroups.forEach(grp => {
        html += `<div class="chip" style="background: rgba(59, 130, 246, 0.2); border-color: rgba(59, 130, 246, 0.4); color: #bfdbfe;">
                    <i class="bi bi-people-fill" style="margin-right: 4px;"></i> ${grp.label}
                    <button type="button" onclick="removeGroup('${grp.type}', ${grp.classId})"><i class="bi bi-x"></i></button>
                 </div>`;
    });
    
    document.getElementById('selectedInvitees').innerHTML = html;
    
    // Update hidden inputs
    let inputsHtml = '';
    selectedInvitees.forEach(inv => {
        inputsHtml += `<input type="hidden" name="attendee_ids[]" value="${inv.id}">`;
    });
    selectedGroups.forEach((grp, index) => {
        inputsHtml += `<input type="hidden" name="attendee_groups[${index}][group_type]" value="${grp.type}">`;
        inputsHtml += `<input type="hidden" name="attendee_groups[${index}][class_id]" value="${grp.classId}">`;
    });
    document.getElementById('hiddenInviteeInputs').innerHTML = inputsHtml;
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#inviteeSearch') && !e.target.closest('#searchResults')) {
        document.getElementById('searchResults').style.display = 'none';
    }
});

function handleCreateMeeting(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    fetch(`{{ route('teacher.calendar.meetings.store') }}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('newMeetingModal')).hide();
            form.reset();
            selectedInvitees = [];
            selectedGroups = [];
            renderChips();
            calendar.refetchEvents();
        } else {
            alert(data.message || 'Error creating meeting');
        }
    })
    .catch(err => alert('An error occurred'));
}

function handleReschedule(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const id = document.getElementById('rescheduleEventId').value;
    
    // Ensure PUT method
    formData.append('_method', 'PUT');
    
    fetch(`/teacher/calendar/reschedule/${id}`, {
        method: 'POST', // Laravel accepts POST with _method=PUT
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('rescheduleModal')).hide();
            form.reset();
            calendar.refetchEvents();
        } else {
            alert(data.message || 'Error rescheduling event');
            calendar.refetchEvents(); // revert visually
        }
    })
    .catch(err => {
        alert('An error occurred');
        calendar.refetchEvents(); // revert visually
    });
}
</script>
</div>
@endsection
