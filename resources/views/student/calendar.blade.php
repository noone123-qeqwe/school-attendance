@extends('layouts.app')

@section('page-title', 'Attendance & Calendar')

@section('content')
@php
    $exportParams = array_filter([
        'year' => $year,
        'month' => $month,
        'status' => $filters['status'] ?? null,
        'q' => $filters['q'] ?? null,
        'record_date' => $filters['record_date'] ?? null,
    ]);
    $hasRecordFilters = !empty(array_filter($filters ?? []));
@endphp

<div>
    <header class="sf-page-head">
        <div>
            <h1>Attendance &amp; Calendar</h1>
            <p>Monthly attendance overview, holidays, and recorded check-ins</p>
        </div>
        <div class="sf-page-actions">
            <a href="{{ route('mobile.scan') }}" class="sf-btn sf-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/></svg>
                <span>Check in</span>
            </a>
            <a href="{{ route('student.calendar.export-csv', $exportParams) }}" class="sf-btn sf-btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                <span>Export CSV</span>
            </a>
        </div>
    </header>

    {{-- Mobile Segmented Workspace Switcher --}}
    <div class="sf-cal-workspace-tabs" role="tablist" aria-label="Attendance workspace view">
        <button type="button"
                class="sf-cal-workspace-tab {{ !$hasRecordFilters ? 'active' : '' }}"
                role="tab"
                id="scalTabCalendar"
                aria-controls="scalWorkspaceCalendar"
                aria-selected="{{ !$hasRecordFilters ? 'true' : 'false' }}"
                data-cal-workspace-tab="calendar">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
            <span>Calendar</span>
        </button>
        <button type="button"
                class="sf-cal-workspace-tab {{ $hasRecordFilters ? 'active' : '' }}"
                role="tab"
                id="scalTabRecords"
                aria-controls="scalWorkspaceRecords"
                aria-selected="{{ $hasRecordFilters ? 'true' : 'false' }}"
                data-cal-workspace-tab="records">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>
            <span>Records</span>
            <span class="sf-pill sf-mono" style="padding:1px 6px;">{{ $totals['all'] ?? 0 }}</span>
        </button>
    </div>

    <div class="sf-cal-grid-layout">
        {{-- Left Column: Interactive Monthly Calendar + Selected Date Inspector --}}
        <div id="scalWorkspaceCalendar" role="tabpanel" aria-labelledby="scalTabCalendar">
            <section class="sf-card scal-card" aria-label="Monthly attendance calendar">
                <div class="scal-nav">
                    <button type="button" class="scal-nav-btn" onclick="scalPrevMonth()" title="Previous month" aria-label="Previous month">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <span class="scal-nav-title" id="scalMonthLabel" aria-live="polite">
                        {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
                    </span>
                    <button type="button" class="scal-nav-btn" onclick="scalNextMonth()" title="Next month" aria-label="Next month">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>

                <div class="scal-weekdays">
                    @foreach(['Su','Mo','Tu','We','Th','Fr','Sa'] as $d)
                        <div class="scal-wd">{{ $d }}</div>
                    @endforeach
                </div>

                @include('partials.calendar-month-grid')

                <div class="sf-minical-legend" style="flex-wrap:wrap;gap:8px 12px;">
                    <span class="sf-legend-item"><span class="sf-legend-dot" style="background:var(--sf-emerald);"></span> Present</span>
                    <span class="sf-legend-item"><span class="sf-legend-dot" style="background:var(--sf-amber);"></span> Late</span>
                    <span class="sf-legend-item"><span class="sf-legend-dot" style="background:var(--sf-danger);"></span> Absent</span>
                    <span class="sf-legend-item"><span class="sf-legend-dot" style="background:var(--sf-teal);"></span> Holiday</span>
                    <span class="sf-legend-item"><span class="sf-legend-dot" style="background:var(--sf-sky);"></span> Event</span>
                    <span class="sf-legend-item"><span class="sf-legend-dot" style="background:var(--sf-rose);"></span> Exam</span>
                </div>

                {{-- Embedded Selected-Date Inspector --}}
                <div class="sf-cal-inspector" id="sdDesktopDayInspector" aria-live="polite">
                    <div class="sf-cal-inspector-head">
                        <div class="sf-cal-inspector-date">
                            <span id="sdInspectorTitle">Select a day</span>
                        </div>
                        <span class="sf-pill" id="sdInspectorStatusBadge" style="display:none;"></span>
                    </div>
                    <div id="sdInspectorSubtitle" style="font-size:11.5px;color:var(--sf-text-muted);margin-bottom:8px;">
                        Click any date tile to inspect attendance &amp; school events.
                    </div>
                    <div class="sf-cal-inspector-list" id="sdInspectorContent">
                        <div style="font-size:12px;color:var(--sf-text-muted);">Loading calendar activity...</div>
                    </div>
                    <div style="margin-top:9px;display:flex;justify-content:flex-end;">
                        <a href="{{ route('student.calendar') }}#attendance-records" id="sdInspectorRecordsLink" class="sf-link" style="display:none;">
                            Filter records for this date →
                        </a>
                    </div>
                </div>
            </section>
        </div>

        {{-- Right Column: Attendance History Ledger --}}
        <div id="scalWorkspaceRecords" role="tabpanel" aria-labelledby="scalTabRecords">
            @include('partials.calendar-records')
            @include('attendance.records')
        </div>
    </div>
</div>

{{-- Hidden Day Summary Marker for calendar-month-grid aria-controls --}}
<div id="daySummaryModal" hidden></div>

<script @cspNonce>
let scalYear  = {{ $year }};
let scalMonth = {{ $month }};
let scalEventMap = {};
let scalDataState = 'loading';

const MONTH_NAMES = ['January','February','March','April','May','June',
                     'July','August','September','October','November','December'];
const STATUS_PRIORITY = ['absent','late','present','exam','event','holiday'];
const STATUS_CLASS = {
    present : 'status-present',
    late    : 'status-late',
    absent  : 'status-absent',
    event   : 'status-event',
    holiday : 'status-holiday',
    exam    : 'status-exam',
};

function scalDominantStatus(dateStr) {
    const evts = scalEventMap[dateStr];
    if (!evts || evts.length === 0) return null;
    const statuses = new Set();
    evts.forEach(ev => {
        const t = (ev.type || '').toLowerCase();
        const s = (ev.status || '').toLowerCase();
        if (t === 'attendance') {
            if (s === 'absent')  statuses.add('absent');
            else if (s === 'late') statuses.add('late');
            else if (s === 'present') statuses.add('present');
        } else if (t === 'exam')    statuses.add('exam');
        else if (t === 'holiday')   statuses.add('holiday');
        else                        statuses.add('event');
    });
    for (const p of STATUS_PRIORITY) {
        if (statuses.has(p)) return p;
    }
    return null;
}

function scalRender() {
    const monthName = MONTH_NAMES[scalMonth - 1];
    document.getElementById('scalMonthLabel').textContent = monthName + ' ' + scalYear;
    renderCalendarDates(scalYear, scalMonth, monthName);

    for (const tile of calendarGrid.querySelectorAll('button[data-date]')) {
        const status = scalDominantStatus(tile.dataset.date);
        tile.classList.remove(...Object.values(STATUS_CLASS));
        const dots = tile.querySelector('.scal-dots-row');
        dots.replaceChildren();
        if (status) {
            tile.classList.add(STATUS_CLASS[status]);
            const dot = document.createElement('span');
            dot.className = 'scal-dot';
            dots.append(dot);
        }
    }
}

function scalPrevMonth() {
    scalMonth--;
    if (scalMonth < 1) { scalMonth = 12; scalYear--; }
    scalLoadAndRender();
}
function scalNextMonth() {
    scalMonth++;
    if (scalMonth > 12) { scalMonth = 1; scalYear++; }
    scalLoadAndRender();
}

function scalHighlightTile(dateStr) {
    if (!calendarGrid) return;
    calendarGrid.querySelectorAll('button[data-date]').forEach(tile => {
        tile.classList.toggle('is-selected-day', tile.dataset.date === dateStr);
    });
}

function scalRenderDay(dateStr) {
    scalHighlightTile(dateStr);
    const evts = scalEventMap[dateStr] || [];
    const titleEl = document.getElementById('sdInspectorTitle');
    const subEl = document.getElementById('sdInspectorSubtitle');
    const badgeEl = document.getElementById('sdInspectorStatusBadge');
    const listEl = document.getElementById('sdInspectorContent');
    const recLink = document.getElementById('sdInspectorRecordsLink');
    const marker = document.getElementById('daySummaryModal');
    if (marker) marker.dataset.date = dateStr;

    const dateObj = new Date(dateStr + 'T00:00:00');
    if (titleEl) {
        titleEl.textContent = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    }
    if (subEl) {
        subEl.textContent = evts.length > 0
            ? (evts.length + ' ' + (evts.length === 1 ? 'item' : 'items') + ' on this date')
            : 'No attendance records or school events on this date.';
    }

    const dom = scalDominantStatus(dateStr);
    if (badgeEl) {
        if (dom) {
            badgeEl.textContent = dom.charAt(0).toUpperCase() + dom.slice(1);
            badgeEl.className = 'sf-pill ' + (dom === 'present' ? 'is-emerald' : (dom === 'late' ? 'is-amber' : (dom === 'absent' ? 'is-danger' : (dom === 'holiday' ? 'is-teal' : 'is-sky'))));
            badgeEl.style.display = 'inline-flex';
        } else {
            badgeEl.style.display = 'none';
        }
    }

    if (recLink) {
        const hasAtt = evts.some(ev => (ev.type || '').toLowerCase() === 'attendance');
        const recordsUrl = new URL(@json(route('student.calendar')), window.location?.origin || 'http://localhost');
        recordsUrl.search = new URLSearchParams({ year: scalYear, month: scalMonth, record_date: dateStr });
        recordsUrl.hash = 'attendance-records';
        recLink.href = recordsUrl.href;
        recLink.style.display = hasAtt ? 'inline-flex' : 'none';
    }

    if (!listEl) return;
    listEl.replaceChildren();

    if (scalDataState === 'loading') {
        listEl.innerHTML = '<div style="font-size:12px;color:var(--sf-text-muted);">Loading day activity...</div>';
        return;
    }
    if (scalDataState === 'error') {
        listEl.innerHTML = '<div style="font-size:12px;color:var(--sf-danger);">Unable to load calendar events.</div>';
        return;
    }
    if (!evts.length) return;

    evts.forEach(ev => {
        const isAtt = (ev.type || '').toLowerCase() === 'attendance';
        const status = (ev.status || '').toLowerCase();
        const evType = (ev.type || '').toLowerCase();
        const pillClass = status === 'present' ? 'is-emerald' : (status === 'late' ? 'is-amber' : (status === 'absent' ? 'is-danger' : (status === 'excused' ? 'is-sky' : (evType === 'holiday' ? 'is-teal' : 'is-rose'))));

        const row = document.createElement('div');
        row.className = 'sf-cal-event-item';

        const left = document.createElement('div');
        left.style.minWidth = '0';
        const name = document.createElement('div');
        name.style.cssText = 'font-weight:600;color:var(--sf-text);';
        name.textContent = isAtt ? (ev.subject_name || ev.title || 'Class') : (ev.title || 'Event');
        const meta = document.createElement('div');
        meta.style.cssText = 'font-size:11px;color:var(--sf-text-muted);';
        meta.textContent = (ev.time_string || (isAtt ? 'Logged' : 'All Day')) + (ev.instructor_name ? ' · ' + ev.instructor_name : '');
        left.append(name, meta);

        const pill = document.createElement('span');
        pill.className = 'sf-pill ' + pillClass;
        pill.textContent = isAtt ? (ev.status || 'Logged') : (evType.replace('_', ' ') || 'Event');

        row.append(left, pill);
        listEl.appendChild(row);
    });
}

function scalDayClick(dateStr) {
    scalRenderDay(dateStr);
}

function scalLoadAndRender(historyMode = 'push') {
    syncCalendarRecordsMonth(scalYear, scalMonth);
    loadCalendarMonth(@js(route('student.calendar.data', absolute: false)), scalYear, scalMonth, (state, events) => {
        scalDataState = state;
        scalEventMap = events;
        scalRender();
        const marker = document.getElementById('daySummaryModal');
        const selectedDate = marker?.dataset.date;
        if (selectedDate?.slice(0, 7) === calendarGrid.dataset.month) {
            scalRenderDay(selectedDate);
        } else if (calendarGrid?.dataset.month) {
            const now = new Date();
            const nowMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
            const defaultDate = nowMonth === calendarGrid.dataset.month
                ? `${nowMonth}-${String(now.getDate()).padStart(2, '0')}`
                : `${calendarGrid.dataset.month}-01`;
            scalRenderDay(defaultDate);
        }
    });

    const url = new URL(window.location);
    url.searchParams.set('year', scalYear);
    url.searchParams.set('month', scalMonth);
    if (historyMode === 'push') window.history.pushState({}, '', url);
    else if (historyMode === 'replace') window.history.replaceState({}, '', url);
}

window.addEventListener('popstate', () => {
    const params = new URL(location.href).searchParams;
    const y = Number(params.get('year'));
    const m = Number(params.get('month'));
    const today = new Date();
    scalYear = Number.isInteger(y) && y >= 1900 && y <= 2200 ? y : today.getFullYear();
    scalMonth = Number.isInteger(m) && m >= 1 && m <= 12 ? m : today.getMonth() + 1;
    scalLoadAndRender('none');
});

document.addEventListener('DOMContentLoaded', function () {
    // Mobile segmented tabs (Calendar | Records)
    const tabBtns = document.querySelectorAll('[data-cal-workspace-tab]');
    const calPanel = document.getElementById('scalWorkspaceCalendar');
    const recPanel = document.getElementById('scalWorkspaceRecords');
    let mobileTab = (window.location.hash === '#attendance-records' || @json($hasRecordFilters)) ? 'records' : 'calendar';

    function syncWorkspaceTabs() {
        const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
        tabBtns.forEach((b) => {
            const active = b.dataset.calWorkspaceTab === mobileTab;
            b.classList.toggle('active', active);
            b.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        if (calPanel) calPanel.hidden = isMobile && mobileTab !== 'calendar';
        if (recPanel) recPanel.hidden = isMobile && mobileTab !== 'records';
    }

    tabBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            mobileTab = btn.dataset.calWorkspaceTab || 'calendar';
            syncWorkspaceTabs();
        });
    });
    window.addEventListener('resize', syncWorkspaceTabs);
    syncWorkspaceTabs();

    // Hydrate SSR events or fetch
    const ssrEvents = @json($initialCalendarEvents ?? null);
    if (Array.isArray(ssrEvents)) {
        const key = calendarMonthKey(scalYear, scalMonth);
        const start = key + '-01';
        const end = key + '-' + new Date(scalYear, scalMonth, 0).getDate();
        const mapped = {};
        for (const ev of ssrEvents) {
            const d = typeof ev?.start === 'string' ? ev.start.split('T')[0] : '';
            if (d.length === 10 && d >= start && d <= end) {
                (mapped[d] ??= []).push(ev);
            }
        }
        scalDataState = 'ready';
        scalEventMap = mapped;
        if (calendarLoadStatus) calendarLoadStatus.textContent = '';
        if (calendarRetry) calendarRetry.hidden = true;
        syncCalendarRecordsMonth(scalYear, scalMonth);
        scalRender();
        const now = new Date();
        const nowMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
        const defaultDate = nowMonth === calendarGrid?.dataset.month
            ? `${nowMonth}-${String(now.getDate()).padStart(2, '0')}`
            : `${calendarGrid?.dataset.month}-01`;
        scalRenderDay(defaultDate);
    } else {
        scalLoadAndRender('replace');
    }

    // Touch swipe month navigation
    let tx = 0, ty = 0;
    const card = document.querySelector('.scal-card');
    if (card) {
        card.addEventListener('touchstart', e => { tx = e.changedTouches[0].screenX; ty = e.changedTouches[0].screenY; }, { passive: true });
        card.addEventListener('touchend', e => {
            const dx = tx - e.changedTouches[0].screenX;
            const dy = Math.abs(ty - e.changedTouches[0].screenY);
            if (Math.abs(dx) > 70 && dy < 60) { dx > 0 ? scalNextMonth() : scalPrevMonth(); }
        }, { passive: true });
    }
});
</script>
@endsection
