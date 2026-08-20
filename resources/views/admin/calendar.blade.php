@extends('layouts.app')

@section('page-title', 'Holiday & Events Calendar')

@section('content')
<div id="holidayCalendarPage" class="holiday-dashboard">
<style>
    .hcal-pro-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        max-width: 860px;
        margin: 0 auto;
        padding-bottom: 30px;
    }
    
    .hcal-top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .hcal-top-title {
        font-size: 1.4rem;
        font-weight: 800;
        color: #f3ede4;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .hcal-card-box {
        background: #0f0a08;
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 28px;
        padding: 32px 36px;
        width: 100%;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7);
    }
    
    .hcal-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
    }
    
    .hcal-btn-arrow {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #cfa46f;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
    }
    
    .hcal-btn-arrow:hover {
        background: rgba(207, 164, 111, 0.15);
        border-color: rgba(207, 164, 111, 0.4);
        color: #ffffff;
        transform: translateY(-2px);
    }
    
    .hcal-current-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #fdfbf7;
        letter-spacing: -0.02em;
    }
    
    .hcal-weekdays-pill {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        margin-bottom: 16px;
        background: rgba(255, 255, 255, 0.025);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 16px;
        padding: 14px 0;
    }
    
    .hcal-weekday-item {
        text-align: center;
        font-size: 0.82rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #cfa46f;
    }
    
    .hcal-days-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 12px;
    }
    
    .hcal-tile {
        height: 68px;
        min-height: 68px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.04);
        background: rgba(255, 255, 255, 0.025);
        position: relative;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        cursor: pointer;
        padding: 6px 0;
    }
    
    .hcal-tile:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(207, 164, 111, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
    }
    
    .hcal-tile.empty {
        visibility: hidden;
        background: transparent;
        border-color: transparent;
        cursor: default;
    }
    
    .hcal-tile-num {
        font-size: 1.15rem;
        font-weight: 700;
        color: #f3ede4;
        line-height: 1;
    }
    
    /* Sunday Red Tint */
    .hcal-tile.sunday .hcal-tile-num {
        color: #f87171 !important;
    }
    
    /* Today Cell - Illuminated Glowing Border */
    .hcal-tile.today {
        background: rgba(255, 209, 102, 0.08) !important;
        border: 2px solid #ffd166 !important;
        box-shadow: 0 0 24px rgba(255, 209, 102, 0.28), inset 0 0 14px rgba(255, 209, 102, 0.1) !important;
    }
    
    .hcal-tile.today .hcal-tile-num {
        color: #ffffff !important;
        font-weight: 800;
    }
    
    /* Holiday / Event Days - Crimson Squircle with Centered Red Dot */
    .hcal-tile.holiday-day,
    .hcal-tile.has-event {
        background: rgba(220, 38, 38, 0.15) !important;
        border: 1.5px solid rgba(220, 38, 38, 0.5) !important;
    }
    
    .hcal-tile.holiday-day .hcal-tile-num,
    .hcal-tile.has-event .hcal-tile-num {
        color: #fca5a5 !important;
        font-weight: 800;
    }
    
    .hcal-tile-dots {
        display: flex;
        gap: 4px;
        margin-top: 5px;
        height: 6px;
        align-items: center;
        justify-content: center;
    }
    
    .hcal-tile-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #ef4444;
        box-shadow: 0 0 8px #ef4444;
        flex-shrink: 0;
    }
    
    .hcal-bottom-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
        margin-top: 28px;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .hcal-bottom-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.78rem;
        color: #b39b82;
        font-weight: 600;
        letter-spacing: 0.03em;
    }
    
    .hcal-bottom-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    @media (max-width: 768px) {
        .hcal-card-box {
            padding: 20px 16px;
            border-radius: 20px;
        }
        .hcal-current-title {
            font-size: 1.2rem;
        }
        .hcal-btn-arrow {
            width: 38px;
            height: 38px;
            font-size: 1rem;
        }
        .hcal-weekdays-pill {
            padding: 10px 0;
            gap: 4px;
        }
        .hcal-weekday-item {
            font-size: 0.7rem;
        }
        .hcal-days-grid {
            gap: 6px;
        }
        .hcal-tile {
            height: 50px;
            min-height: 50px;
            border-radius: 12px;
        }
        .hcal-tile-num {
            font-size: 0.92rem;
        }
    }
</style>

@php
    $hcalStart = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $hcalEnd = $hcalStart->copy()->endOfMonth();
    $hcalPrev = $hcalStart->copy()->subMonth();
    $hcalNext = $hcalStart->copy()->addMonth();
    $hcalStartDow = $hcalStart->dayOfWeek;
    $hcalIsCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $hcalToday = now()->day;
@endphp

<div class="hcal-pro-wrapper">
    {{-- Top Action Bar --}}
    <div class="hcal-top-bar">
        <div>
            <h1 class="hcal-top-title">
                <i class="bi bi-calendar-heart-fill" style="color: #f87171;"></i> Holiday & Events Calendar
            </h1>
            <p style="font-size: 0.82rem; color: #b39b82; margin: 4px 0 0 0;">Manage academic holidays, declarations, campus events, and schedules.</p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="?hcal_year={{ now()->year }}&hcal_month={{ now()->month }}" class="adm-btn adm-btn-ghost">
                Today
            </a>
            <button type="button" class="adm-btn adm-btn-primary" onclick="openHcalModal()">
                <i class="bi bi-plus-lg"></i> Add Holiday / Event
            </button>
        </div>
    </div>

    {{-- Main Squircle Calendar Card (Exact Visual Style) --}}
    <div class="hcal-card-box">
        {{-- Navigation Row --}}
        <div class="hcal-header-row">
            <a href="?hcal_year={{ $hcalPrev->year }}&hcal_month={{ $hcalPrev->month }}" class="hcal-btn-arrow" title="Previous Month">
                <i class="bi bi-chevron-left"></i>
            </a>
            <div class="hcal-current-title">
                {{ $hcalStart->format('F Y') }}
            </div>
            <a href="?hcal_year={{ $hcalNext->year }}&hcal_month={{ $hcalNext->month }}" class="hcal-btn-arrow" title="Next Month">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        {{-- Weekdays Header Pill --}}
        <div class="hcal-weekdays-pill">
            @foreach(['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'] as $dayLbl)
                <div class="hcal-weekday-item">{{ $dayLbl }}</div>
            @endforeach
        </div>

        {{-- Days Grid (Squircles) --}}
        <div class="hcal-days-grid">
            {{-- Empty cells before month start --}}
            @for($i = 0; $i < $hcalStartDow; $i++)
                <div class="hcal-tile empty"></div>
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
                <div class="hcal-tile{{ $cls }}" onclick="openHcalDateDetails('{{ $dateKey }}', '{{ $formattedDate }}')" title="{{ $hasEvents ? collect($dayEvents)->pluck('name')->join(', ') : 'Click to view or add events' }}">
                    <div class="hcal-tile-num">{{ $d }}</div>
                    @if($hasEvents)
                        <div class="hcal-tile-dots">
                            @foreach(collect($dayEvents)->unique('type')->take(3) as $evt)
                                <div class="hcal-tile-dot"></div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endfor
        </div>

        {{-- Legend --}}
        <div class="hcal-bottom-legend">
            <div class="hcal-bottom-item"><div class="hcal-bottom-dot" style="background:#dc2626;"></div> National</div>
            <div class="hcal-bottom-item"><div class="hcal-bottom-dot" style="background:#d97706;"></div> Local</div>
            <div class="hcal-bottom-item"><div class="hcal-bottom-dot" style="background:#7c2d12;"></div> School</div>
            <div class="hcal-bottom-item"><div class="hcal-bottom-dot" style="background:#6366f1;"></div> No Class</div>
            <div class="hcal-bottom-item"><div class="hcal-bottom-dot" style="background:#60a5fa;"></div> Announcement</div>
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
