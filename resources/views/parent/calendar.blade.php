@extends('layouts.app')

@section('page-title', 'School Calendar')

@section('content')
<div id="parentCalendarPage" class="holiday-dashboard">

{{-- ─── TOP HEADER ─── --}}
<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 28px;">
    <div>
        <h1 style="font-size: 1.45rem; font-weight: 800; color: #f3ede4; margin: 0; display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(220, 38, 38, 0.12); color: #f87171; border: 1px solid rgba(220, 38, 38, 0.25); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="bi bi-calendar-heart-fill"></i>
            </div>
            School & Events Calendar
        </h1>
        <p style="font-size: 0.84rem; color: #b39b82; margin: 6px 0 0 0;">View academic events, holiday schedules, class suspensions, and official campus announcements.</p>
    </div>
</div>

{{-- ─── CALENDAR CONTAINER ─── --}}
@php
    $hcalStart = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $hcalEnd = $hcalStart->copy()->endOfMonth();
    $hcalPrev = $hcalStart->copy()->subMonth();
    $hcalNext = $hcalStart->copy()->addMonth();
    $hcalStartDow = $hcalStart->dayOfWeek;
    $hcalIsCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $hcalToday = now()->day;
@endphp

<div class="hcal-container">
    <div class="hcal-calendar-pane">
        {{-- Month Navigation Header (Matches Screenshot) --}}
        <div class="hcal-nav">
            <a href="?hcal_year={{ $hcalPrev->year }}&hcal_month={{ $hcalPrev->month }}" class="hcal-nav-btn" title="Previous Month">
                <i class="bi bi-chevron-left"></i>
            </a>
            <div class="hcal-month-label">{{ $hcalStart->format('F Y') }}</div>
            <a href="?hcal_year={{ $hcalNext->year }}&hcal_month={{ $hcalNext->month }}" class="hcal-nav-btn" title="Next Month">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        {{-- Weekdays Header Bar --}}
        <div class="hcal-day-labels">
            @foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $lbl)
                <div class="hcal-day-label">{{ $lbl }}</div>
            @endforeach
        </div>

        {{-- 7-Column Days Grid --}}
        <div class="hcal-grid">
            {{-- Empty leading cells --}}
            @for($i = 0; $i < $hcalStartDow; $i++)
                <div class="hcal-day empty"></div>
            @endfor

            {{-- Day cells --}}
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
                <div class="hcal-day{{ $cls }}" onclick="openHcalDateDetails('{{ $dateKey }}', '{{ $formattedDate }}')" title="{{ $hasEvents ? collect($dayEvents)->pluck('name')->join(', ') : 'Click to view details' }}">
                    <div class="hcal-day-num">{{ $d }}</div>
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

        {{-- Legend --}}
        <div class="hcal-legend">
            <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#dc2626;"></div> National</div>
            <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#d97706;"></div> Local</div>
            <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#ea580c;"></div> School</div>
            <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#6366f1;"></div> No Class</div>
            <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#60a5fa;"></div> Announcement</div>
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
        <div class="hcal-modal-footer" id="hcalDetailsFooter" style="padding: 16px 24px; display: flex; justify-content: flex-end; align-items: center; border-top: 1px solid rgba(255, 255, 255, 0.06); background: rgba(0, 0, 0, 0.25);">
            <button type="button" class="hcal-btn-cancel" onclick="closeHcalDetailsModal()">Close</button>
        </div>
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
                    <i class="bi bi-calendar2-check"></i>
                </div>
                <h4 style="font-size: 1.05rem; font-weight: 700; color: #f3ede4; margin-bottom: 6px;">No Scheduled Events</h4>
                <p style="font-size: 0.82rem; color: #8f826f; margin-bottom: 0;">Regular school day with no active suspensions or announcements.</p>
            </div>
        `;
    } else {
        let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';
        events.forEach(evt => {
            const isHoliday = evt.source === 'holiday';
            const typeColorMap = {
                national: '#dc2626',
                local: '#d97706',
                school: '#ea580c',
                no_class: '#6366f1',
                announcement: '#60a5fa',
                event: '#a78bfa',
                school_event: '#a78bfa'
            };
            const borderColor = typeColorMap[evt.type] || '#cfa46f';
            
            html += `
                <div class="hcal-details-item" data-type="${evt.type}" style="border-left: 4px solid ${borderColor};">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <span class="hcal-event-type ${evt.type}" style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="bi ${isHoliday ? 'bi-calendar-heart' : 'bi-megaphone'}"></i>
                            ${evt.type_label || evt.type}
                        </span>
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

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.getElementById('hcalDetailsModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeHcalDetailsModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeHcalDetailsModal();
    }
});
</script>
</div>
@endsection
