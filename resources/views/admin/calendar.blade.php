@extends('layouts.app')

@section('page-title', 'Holiday & Events Calendar')

@section('content')
<div id="holidayCalendarPage" class="holiday-dashboard">
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
        background: rgba(220, 38, 38, 0.12);
        color: #f87171;
        box-shadow: inset 0 0 0 1px rgba(220, 38, 38, 0.25);
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.15rem;
    }

    .holiday-dashboard .calendar-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: flex-end;
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
        text-decoration: none;
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
        padding: 9px 20px;
        font-weight: 700;
        font-size: 0.84rem;
        box-shadow: 0 4px 16px rgba(207, 164, 111, 0.25);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .holiday-dashboard .adm-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(207, 164, 111, 0.35);
    }
</style>

{{-- ─── TOP HEADER ─── --}}
<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 800; color: #f3ede4; margin: 0; display: flex; align-items: center; gap: 10px;">
            <i class="bi bi-calendar-heart-fill" style="color: #f87171;"></i> Holiday & Events Calendar
        </h1>
        <p style="font-size: 0.82rem; color: #b39b82; margin: 4px 0 0 0;">Manage national & school holidays, suspensions, event schedules, and announcements.</p>
    </div>

    <button type="button" class="adm-btn adm-btn-primary" onclick="openHcalModal()">
        <i class="bi bi-plus-lg"></i> Add Holiday / Event
    </button>
</div>

{{-- ─── MAIN CALENDAR CARD ─── --}}
@php
    $hcalStart = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $hcalEnd = $hcalStart->copy()->endOfMonth();
    $hcalPrev = $hcalStart->copy()->subMonth();
    $hcalNext = $hcalStart->copy()->addMonth();
    $hcalStartDow = $hcalStart->dayOfWeek;
    $hcalIsCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $hcalToday = now()->day;
@endphp

<div class="adm-card glass-card">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
            Monthly Schedule
        </div>
        <div class="calendar-controls">
            <a href="?hcal_year={{ now()->year }}&hcal_month={{ now()->month }}" class="adm-btn adm-btn-ghost">
                Today
            </a>
            <a href="?hcal_year={{ $hcalPrev->year }}&hcal_month={{ $hcalPrev->month }}" class="adm-btn adm-btn-ghost" style="padding: 8px 12px;" title="Previous Month">
                <i class="bi bi-chevron-left"></i>
            </a>
            <span style="font-weight: 700; color: #f3ede4; min-width: 140px; text-align: center; font-size: 1.05rem;">
                {{ $hcalStart->format('F Y') }}
            </span>
            <a href="?hcal_year={{ $hcalNext->year }}&hcal_month={{ $hcalNext->month }}" class="adm-btn adm-btn-ghost" style="padding: 8px 12px;" title="Next Month">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>
    
    <div style="padding: 28px 24px;">
        <div class="hcal-container" style="display: block; max-width: 640px; margin: 0 auto;">
            <div class="hcal-calendar-pane" style="width: 100%;">
                <div class="hcal-nav" style="margin-bottom: 16px;">
                    <a href="?hcal_year={{ $hcalPrev->year }}&hcal_month={{ $hcalPrev->month }}" class="hcal-nav-btn">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <div class="hcal-month-label" style="font-size: 1.15rem; font-weight: 800;">{{ $hcalStart->format('F Y') }}</div>
                    <a href="?hcal_year={{ $hcalNext->year }}&hcal_month={{ $hcalNext->month }}" class="hcal-nav-btn">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>

                <div class="hcal-day-labels" style="gap: 8px; margin-bottom: 8px;">
                    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $lbl)
                        <div class="hcal-day-label" style="font-size: 0.76rem; font-weight: 700;">{{ $lbl }}</div>
                    @endforeach
                </div>

                <div class="hcal-grid" style="gap: 8px;">
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
                        <div class="hcal-day{{ $cls }}" style="height: 48px; border-radius: 10px;" onclick="openHcalDateDetails('{{ $dateKey }}', '{{ $formattedDate }}')" title="{{ $hasEvents ? collect($dayEvents)->pluck('name')->join(', ') : 'Click to view or add events' }}">
                            <div class="hcal-day-num" style="font-size: 0.85rem;">{{ $d }}</div>
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

                <div class="hcal-legend" style="margin-top: 24px; padding-top: 18px; gap: 16px; border-top: 1px solid rgba(255,255,255,0.06);">
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

{{-- ─── CALENDAR EVENT DETAILS POPUP MODAL ─── --}}
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
            {{-- Dynamically populated via JS --}}
        </div>
        <div class="hcal-modal-footer" id="hcalDetailsFooter" style="padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255, 255, 255, 0.06); background: rgba(0, 0, 0, 0.25);">
            <button type="button" class="hcal-btn-cancel" onclick="closeHcalDetailsModal()">Close</button>
            <button type="button" class="hcal-btn-submit" id="hcalDetailsAddBtn" onclick="addEventForCurrentDate()" style="padding: 8px 18px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-plus-lg"></i> Add Event
            </button>
        </div>
    </div>
</div>

{{-- ─── ADD/EDIT HOLIDAY & EVENT MODAL ─── --}}
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
                    <input type="text" name="name" class="hcal-form-input" id="hcalName" required placeholder="e.g. Midterm Examinations / National Heroes Day">
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
                        <option value="school_event">School Event</option>
                    </select>
                </div>
                <div class="hcal-form-group" style="margin-bottom:0;">
                    <label class="hcal-form-label">Description (Optional)</label>
                    <textarea name="description" class="hcal-form-textarea" id="hcalDesc" placeholder="Additional details or guidelines..."></textarea>
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

<script>
window.hcalEventsMap = @json($hcalEventsMap ?? []);
let selectedHcalDate = null;
let selectedHcalDateFormatted = '';

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
                event: '#a78bfa',
                school_event: '#a78bfa'
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
