@extends('layouts.app')

@section('page-title', 'Holiday & Events Calendar')

@section('content')
<div id="studentHolidayCalendarPage" class="holiday-dashboard">
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
        font-size: 0.88rem;
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
    
    /* Bottom Legend Pills (Matching Reference Image) */
    .hcal-pill-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px;
        margin-top: 28px;
        padding-top: 22px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .hcal-pill-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 20px;
        padding: 6px 14px;
        font-size: 0.76rem;
        font-weight: 700;
        color: #f3ede4;
        letter-spacing: 0.02em;
        transition: all 0.2s ease;
    }
    
    .hcal-pill-badge:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(207, 164, 111, 0.3);
    }
    
    .hcal-pill-dot {
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
            font-size: 0.72rem;
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
        .hcal-pill-badge {
            padding: 4px 10px;
            font-size: 0.7rem;
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
            <p style="font-size: 0.82rem; color: #b39b82; margin: 4px 0 0 0;">View academic holidays, campus events, and no-class announcements.</p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="?cal_year={{ now()->year }}&cal_month={{ now()->month }}" class="ent-btn ent-btn-secondary ent-btn-sm" style="border-radius: 10px; padding: 8px 16px; font-weight: 700;">
                Today
            </a>
        </div>
    </div>

    {{-- Main Squircle Calendar Card (Pixel-Perfect Match with Reference Image) --}}
    <div class="hcal-card-box">
        {{-- Navigation Row --}}
        <div class="hcal-header-row">
            <a href="?cal_year={{ $hcalPrev->year }}&cal_month={{ $hcalPrev->month }}" class="hcal-btn-arrow" title="Previous Month">
                <i class="bi bi-chevron-left"></i>
            </a>
            <div class="hcal-current-title">
                {{ $hcalStart->format('F Y') }}
            </div>
            <a href="?cal_year={{ $hcalNext->year }}&cal_month={{ $hcalNext->month }}" class="hcal-btn-arrow" title="Next Month">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        {{-- Weekdays Header Pill (S M T W T F S) --}}
        <div class="hcal-weekdays-pill">
            @foreach(['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $dayLbl)
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
                <div class="hcal-tile{{ $cls }}" onclick="openHcalDateDetails('{{ $dateKey }}', '{{ $formattedDate }}')" title="{{ $hasEvents ? collect($dayEvents)->pluck('name')->join(', ') : 'Click to view events' }}">
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

        {{-- Bottom Legend Badges (Pill Boxes matching Screenshot) --}}
        <div class="hcal-pill-legend">
            <div class="hcal-pill-badge"><div class="hcal-pill-dot" style="background:#ef4444; box-shadow: 0 0 6px #ef4444;"></div> National</div>
            <div class="hcal-pill-badge"><div class="hcal-pill-dot" style="background:#f59e0b; box-shadow: 0 0 6px #f59e0b;"></div> Local</div>
            <div class="hcal-pill-badge"><div class="hcal-pill-dot" style="background:#b91c1c; box-shadow: 0 0 6px #b91c1c;"></div> School</div>
            <div class="hcal-pill-badge"><div class="hcal-pill-dot" style="background:#6366f1; box-shadow: 0 0 6px #6366f1;"></div> No Class</div>
            <div class="hcal-pill-badge"><div class="hcal-pill-dot" style="background:#38bdf8; box-shadow: 0 0 6px #38bdf8;"></div> Announcement</div>
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
                    <i class="bi bi-calendar-check"></i>
                </div>
                <h4 style="font-size: 1.05rem; font-weight: 700; color: #f3ede4; margin-bottom: 6px;">Regular Academic Day</h4>
                <p style="font-size: 0.82rem; color: #8f826f; margin-bottom: 0;">No holidays or special announcements scheduled for this date.</p>
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
