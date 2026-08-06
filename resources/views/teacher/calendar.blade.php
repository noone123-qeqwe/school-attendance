@extends('layouts.app')

@section('page-title', 'Holiday Calendar')

@section('content')
<div id="holidayCalendarPage" class="holiday-dashboard">
<style>
    #holidayCalendarPage { padding-bottom: 18px; }
    .holiday-dashboard .tch-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        margin-bottom: 24px;
    }
    .holiday-dashboard .tch-stat {
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
    .holiday-dashboard .tch-stat::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top left, rgba(255,255,255,0.15), transparent 36%);
        opacity: 0.7;
        pointer-events: none;
    }
    .holiday-dashboard .tch-stat-val {
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -0.04em;
        margin-bottom: 6px;
        color: #f8fafc;
    }
    .holiday-dashboard .tch-stat-lbl {
        font-size: 0.78rem;
        opacity: 0.76;
        text-transform: uppercase;
        letter-spacing: 0.16em;
        color: #f8fafc;
    }
    .holiday-dashboard {
        background: rgba(3, 0, 6, 0.15);
        padding: 0 0 18px;
    }
    .holiday-dashboard .tch-card {
        background: rgba(67, 12, 29, 0.18);
        border: 1px solid rgba(255,255,255,0.14);
        backdrop-filter: blur(20px);
        box-shadow: 0 32px 80px rgba(15,23,42,0.2);
        border-radius: 28px;
    }
    .holiday-dashboard .tch-card-head {
        padding: 20px 22px;
    }
    .holiday-dashboard .tch-card-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: #f8fafc;
    }
    .holiday-dashboard .tch-card-icon {
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
    .holiday-dashboard .maroon-btn {
        background: linear-gradient(135deg, #7f1d1d, #3b0215);
        color: #fff;
        border: none;
        box-shadow: 0 16px 32px rgba(124,58,58,0.24);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .holiday-dashboard .maroon-btn:hover {
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
        color: #fde68a;
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
    .holiday-dashboard .tch-input {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14);
        color: #f8fafc;
    }
    .holiday-dashboard .tch-input option {
        background: #1e293b;
        color: #f8fafc;
    }
    .holiday-dashboard select.tch-input {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14);
        color: #f8fafc;
    }
    .holiday-dashboard select.tch-input option {
        background: #1e293b !important;
        color: #f8fafc !important;
    }
    .holiday-dashboard .tch-input::placeholder {
        color: rgba(241,245,249,0.6);
    }
    .holiday-dashboard .tch-input:focus {
        outline: none;
        border-color: rgba(255,255,255,0.32);
        box-shadow: 0 0 0 4px rgba(167,28,48,0.18);
    }
    .holiday-dashboard .form-label {
        color: #e2e8f0;
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
        background: linear-gradient(135deg, #9f1239, #4c0519) !important;
        color: #fff !important;
        border: none !important;
        box-shadow: 0 18px 30px rgba(120,40,60,0.24) !important;
        border-radius: 12px !important;
    }
    .fc .fc-daygrid-event:hover {
        transform: translateY(-2px);
        box-shadow: 0 22px 44px rgba(120,40,60,0.3) !important;
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

<div class="tch-stats">
    <div class="tch-stat">
        <div class="tch-stat-val" style="color: #fde68a;">{{ $holidays->count() }}</div>
        <div class="tch-stat-lbl">This Month</div>
    </div>
    <div class="tch-stat">
        <div class="tch-stat-val" style="color: #dc2626;">{{ $holidays->where('type', 'national')->count() }}</div>
        <div class="tch-stat-lbl">National</div>
    </div>
    <div class="tch-stat">
        <div class="tch-stat-val" style="color: #7c2d12;">{{ $holidays->where('type', 'school')->count() }}</div>
        <div class="tch-stat-lbl">School</div>
    </div>
    <div class="tch-stat">
        <div class="tch-stat-val" style="color: #6366f1;">{{ $holidays->where('type', 'no_class')->count() }}</div>
        <div class="tch-stat-lbl">No Classes</div>
    </div>
</div>

<div class="row g-3">
    <!-- Calendar -->
    <div class="col-lg-8">
        <div class="tch-card">
            <div class="tch-card-head">
                <div class="tch-card-title">
                    <div class="tch-card-icon" style="background: rgba(255,255,255,0.12); color: #fde68a;">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    Holiday Calendar
                </div>
                <div class="calendar-controls">
                    <button type="button" onclick="previousMonth()" class="tch-btn tch-btn-ghost" style="padding: 6px 10px;">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <span id="currentMonth" class="calendar-header">
                        {{ \Carbon\Carbon::create($year, $month)->format('F Y') }}
                    </span>
                    <button type="button" onclick="nextMonth()" class="tch-btn tch-btn-ghost" style="padding: 6px 10px;">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button type="button" onclick="goToToday()" class="tch-btn maroon-btn" style="margin-left: 10px;">
                        Today
                    </button>
                </div>
            </div>
            <div style="padding: 20px;">
                <div id="calendar" style="min-height: 400px;"></div>
            </div>
        </div>
    </div>

    <!-- Holiday List & Add Form -->
    <div class="col-lg-4">
        <!-- Add Holiday Form -->
        <div class="tch-card" style="margin-bottom: 20px;">
            <div class="tch-card-head">
                <div class="tch-card-title">
                    <div class="tch-card-icon" style="background: rgba(255,255,255,0.12); color: #fecaca;">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    Add Holiday
                </div>
            </div>
            <div style="padding: 20px;">
                <form method="POST" action="{{ route('teacher.holidays.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="tch-input" required min="{{ now()->format('Y-m-d') }}">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Holiday Name</label>
                        <input type="text" name="name" class="tch-input" placeholder="e.g., Eid'l Adha" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="tch-input" required>
                            <option value="national">National Holiday</option>
                            <option value="local">Local Holiday</option>
                            <option value="school">School Holiday</option>
                            <option value="no_class">No Classes</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" class="tch-input" rows="2" placeholder="Additional details..."></textarea>
                    </div>
                    
                    <button type="submit" class="tch-btn maroon-btn" style="width: 100%;">
                        <i class="bi bi-plus"></i> Add Holiday
                    </button>
                </form>
            </div>
        </div>

        <!-- Holiday List -->
        <div class="tch-card">
            <div class="tch-card-head">
                <div class="tch-card-title">
                    <div class="tch-card-icon" style="background: rgba(255,255,255,0.12); color: #fde68a;">
                        <i class="bi bi-list-ul"></i>
                    </div>
                    This Month's Holidays
                </div>
            </div>
            <div style="padding: 20px;" id="holidayListContainer">
                @forelse($holidays as $holiday)
                    <div class="holiday-card">
                        <span class="holiday-dot" style="background: {{ $holiday->type_color }};"></span>
                        <div style="flex: 1;">
                            <div class="holiday-name">{{ $holiday->name }}</div>
                            <div class="holiday-meta">
                                {{ $holiday->date->format('M j, Y') }} â€¢ 
                                <span style="color: {{ $holiday->type_color }};">{{ $holiday->type_label }}</span>
                            </div>
                            @if($holiday->description)
                                <div class="holiday-meta" style="margin-top: 6px; color: #cbd5e1;">
                                    {{ $holiday->description }}
                                </div>
                            @endif
                            <div class="holiday-chip">{{ $holiday->type_label }}</div>
                        </div>
                        <div class="holiday-card-actions">
                            <button type="button" onclick="editHoliday({{ $holiday->id }}, '{{ $holiday->name }}', '{{ $holiday->description }}', '{{ $holiday->type }}')" class="holiday-btn-edit view-btn">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" onclick="deleteHoliday({{ $holiday->id }})" class="holiday-btn-delete view-btn">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div style="padding: 40px 20px; text-align: center; color: #cbd5e1;">
                        <i class="bi bi-calendar-x" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 8px;"></i>
                        <div>No holidays this month</div>
                        <div style="font-size: 0.8rem; margin-top: 4px; color: #94a3b8;">Add holidays to prevent automatic absences</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Edit Holiday Modal -->
<div class="modal fade" id="editHolidayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil" style="color: #fde68a;"></i>
                    Edit Holiday
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editHolidayForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body" style="padding: 20px;">
                    <div class="mb-3">
                        <label class="form-label">Holiday Name</label>
                        <input type="text" name="name" id="editName" class="tch-input" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" id="editType" class="tch-input" required>
                            <option value="national">National Holiday</option>
                            <option value="local">Local Holiday</option>
                            <option value="school">School Holiday</option>
                            <option value="no_class">No Classes</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" id="editDescription" class="tch-input" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="tch-btn tch-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tch-btn maroon-btn">
                        <i class="bi bi-check"></i> Update Holiday
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

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        initialDate: `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`,
        headerToolbar: false,
        height: 'auto',
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch(`{{ route('teacher.calendar.data') }}?year=${currentYear}&month=${currentMonth}`)
                .then(response => response.json())
                .then(data => {
                    const events = data.map(holiday => ({
                        id: holiday.id,
                        title: holiday.name,
                        date: holiday.date,
                        backgroundColor: holiday.color,
                        borderColor: holiday.color,
                        textColor: 'white',
                        extendedProps: {
                            description: holiday.description,
                            type: holiday.type_label
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
            alert(`${info.event.title}\nType: ${info.event.extendedProps.type}\n${info.event.extendedProps.description || ''}`);
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
    
    // Fetch and update the side panel holidays without reloading
    fetch(`{{ route('teacher.calendar.data') }}?year=${currentYear}&month=${currentMonth}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('holidayListContainer');
            
            // Update stats
            const stats = document.querySelectorAll('.tch-stat-val');
            if (stats.length >= 4) {
                stats[0].textContent = data.length;
                stats[1].textContent = data.filter(h => h.type === 'national').length;
                stats[2].textContent = data.filter(h => h.type === 'school').length;
                stats[3].textContent = data.filter(h => h.type === 'no_class').length;
            }

            if (data.length === 0) {
                container.innerHTML = `
                    <div style="padding: 40px 20px; text-align: center; color: #cbd5e1;">
                        <i class="bi bi-calendar-x" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 8px;"></i>
                        <div>No holidays this month</div>
                        <div style="font-size: 0.8rem; margin-top: 4px; color: #94a3b8;">Add holidays to prevent automatic absences</div>
                    </div>`;
                return;
            }

            let html = '';
            data.forEach(holiday => {
                const dateObj = new Date(holiday.date);
                const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const descStr = holiday.description ? `<div class="holiday-meta" style="margin-top: 6px; color: #cbd5e1;">${holiday.description}</div>` : '';
                
                html += `
                    <div class="holiday-card">
                        <span class="holiday-dot" style="background: ${holiday.color};"></span>
                        <div style="flex: 1;">
                            <div class="holiday-name">${holiday.name}</div>
                            <div class="holiday-meta">
                                ${dateStr} â€¢ 
                                <span style="color: ${holiday.color};">${holiday.type_label}</span>
                            </div>
                            ${descStr}
                            <div class="holiday-chip">${holiday.type_label}</div>
                        </div>
                        <div class="holiday-card-actions">
                            <button type="button" onclick="editHoliday(${holiday.id}, '${holiday.name.replace(/'/g, "\\'")}', '${(holiday.description || '').replace(/'/g, "\\'")}', '${holiday.type}')" class="holiday-btn-edit view-btn">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" onclick="deleteHoliday(${holiday.id})" class="holiday-btn-delete view-btn">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        });
    
    const url = new URL(window.location);
    url.searchParams.set('year', currentYear);
    url.searchParams.set('month', currentMonth);
    window.history.pushState({}, '', url);
}

function editHoliday(id, name, description, type) {
    document.getElementById('editName').value = name;
    document.getElementById('editDescription').value = description || '';
    document.getElementById('editType').value = type;
    document.getElementById('editHolidayForm').action = `/teacher/holidays/${id}`;
    
    new bootstrap.Modal(document.getElementById('editHolidayModal')).show();
}

function deleteHoliday(id) {
    if (confirm('Are you sure you want to remove this holiday?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/teacher/holidays/${id}`;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
