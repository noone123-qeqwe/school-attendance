@extends('layouts.app')

@section('page-title', 'Attendance & Calendar')

@section('content')
{{-- ────────────────────────────── LAYOUT ────────────────────────────── --}}
@include('partials.student-page-header', ['title' => 'Attendance & School Calendar', 'description' => 'Check school dates and review your attendance history.'])
<div class="scal-outer">

    {{-- MAIN CALENDAR CARD --}}
    <div class="scal-card">
        {{-- Navigation --}}
        <div class="scal-nav">
            <button class="scal-nav-btn" onclick="scalPrevMonth()" title="Previous month" aria-label="Previous month">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-center">
                <span class="scal-nav-title" id="scalMonthLabel" aria-live="polite">{{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}</span>
                <button type="button" class="scal-nav-today-btn d-inline-flex" id="scalTodayBtn" onclick="scalJumpToday()" title="Jump to current month">
                    <i class="bi bi-calendar2-check" aria-hidden="true"></i> Today
                </button>
            </div>
            <button class="scal-nav-btn" onclick="scalNextMonth()" title="Next month" aria-label="Next month">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>

        {{-- Weekday headers --}}
        <div class="scal-weekdays">
            @foreach(['S','M','T','W','T','F','S'] as $d)
                <div class="scal-wd">{{ $d }}</div>
            @endforeach
        </div>

        @include('partials.calendar-month-grid')
    </div>

    {{-- SIDE PANEL --}}
    <div class="scal-side">
        {{-- Desktop Selected-Day Inspector --}}
        <section class="sd-day-inspector d-none d-lg-block" id="sdDesktopDayInspector" aria-live="polite">
            <div class="sd-day-inspector-head">
                <div>
                    <h2 class="sd-day-inspector-title" id="sdInspectorTitle">Select a day</h2>
                    <div class="sd-day-inspector-sub" id="sdInspectorSubtitle">Click any date tile to inspect attendance &amp; events</div>
                </div>
                <span class="badge rounded-pill" id="sdInspectorStatusBadge" style="display:none; font-size:0.72rem; font-weight:700; padding:4px 10px;"></span>
            </div>
            <div class="sd-day-inspector-list" id="sdInspectorContent">
                <p style="color:#ACA099; font-size:0.84rem; margin:0;">Loading calendar activity…</p>
            </div>
            <div class="d-flex align-items-center justify-content-between gap-2 mt-3 pt-2" style="border-top: 1px solid rgba(255, 255, 255, 0.08);">
                <a href="{{ route('student.calendar') }}#attendance-records" id="sdInspectorRecordsLink" class="d-none" style="color:#D4AF37; font-size:0.8rem; font-weight:700; text-decoration:none;">
                    <i class="bi bi-funnel me-1" aria-hidden="true"></i>Filter records
                </a>
                <button type="button" id="sdInspectorModalBtn" class="btn btn-sm ms-auto" style="background: #261B1B; border: 1px solid rgba(255, 255, 255,0.16); color:#FAF8F5; font-size:0.76rem; font-weight:700; border-radius:8px; padding:6px 12px;">
                    <i class="bi bi-arrows-angle-expand me-1" aria-hidden="true"></i>Details
                </button>
            </div>
        </section>

        {{-- Legend --}}
        <div class="scal-legend-card">
            <div class="scal-legend-title">Legend</div>
            <div class="scal-legend-grid">
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#4ADE80;"></span>
                    Present
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#FBBF24;"></span>
                    Late
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#F87171;"></span>
                    Absent
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#D4AF37;"></span>
                    Exam
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#7DD3FC;"></span>
                    Event
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#2DD4BF;"></span>
                    Holiday
                </div>
            </div>
        </div>

        {{-- Tips --}}
        <details class="scal-tips-card student-calendar-help">
            <summary>How to read this calendar</summary>
            <ul class="scal-tips-list">
                <li>Tap any <strong>day tile</strong> to view your subjects and attendance for that day.</li>
                <li>Tile color shows your <strong>dominant attendance status</strong> for the day.</li>
                <li>The <strong>highlighted border</strong> marks today.</li>
            </ul>
        </details>
    </div>
</div>

@include('partials.calendar-records')
@include('attendance.records')

{{-- ── Event Detail Modal ──────────────────────────────────────────────── --}}
<div class="modal fade" id="eventDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ent-modal-content w-100">
            <div class="ent-modal-header position-relative">
                <h5 class="ent-modal-title" id="eventTitle" style="padding-right: 24px;">Event</h5>
                <button type="button" class="btn-close btn-close-white" aria-label="Close details" data-bs-dismiss="modal" style="position:absolute;top:20px;right:20px;"></button>
            </div>
            <div class="ent-modal-body">
                <div class="ent-modal-row">
                    <div class="ent-modal-label">Event Type</div>
                    <div class="ent-modal-value">
                        <span id="eventTypeBadge" class="ent-badge" style="font-size:0.75rem;padding:4px 10px;border-radius:4px;box-shadow:0 2px 5px rgba(0,0,0,0.3);">Type</span>
                    </div>
                </div>
                <div class="ent-modal-row">
                    <div class="ent-modal-label">Date &amp; Time</div>
                    <div class="ent-modal-value">
                        <i class="bi bi-clock" style="color:#D4AF37;" aria-hidden="true"></i>
                        <span id="eventTime">Date</span>
                    </div>
                </div>
                <div class="ent-modal-row" id="eventLocationContainer">
                    <div class="ent-modal-label">Location</div>
                    <div class="ent-modal-value">
                        <i class="bi bi-geo-alt" style="color:#D4AF37;" aria-hidden="true"></i>
                        <span id="eventLocation">Location</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Day Summary Modal ───────────────────────────────────────────────── --}}
<div class="modal fade" id="daySummaryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-bottom modal-dialog-scrollable">
        <div class="modal-content bottom-sheet-content">
            <div class="bottom-sheet-handle"></div>
            <div class="d-flex justify-content-between align-items-start px-4 pt-4 pb-3 border-bottom position-relative" style="border-color:rgba(255, 255, 255,0.08)!important;">
                <div>
                    <h3 style="font-weight:700;font-size:1.15rem;color:#FAF8F5;margin:0;padding-right:36px;" id="daySummaryTitle">Date</h3>
                    <div style="font-size:0.84rem;color:#ACA099;display:flex;align-items:center;gap:6px;margin-top:4px;" id="daySummarySubtitle">
                        <span id="daySummaryStatusDot" style="width:8px;height:8px;border-radius:50%;display:inline-block;"></span>
                        <span id="daySummaryStatusText">Status</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" aria-label="Close details" data-bs-dismiss="modal" onclick="closeDaySummaryModal()" style="position:absolute;top:16px;right:16px;min-width:44px;min-height:44px;"></button>
            </div>
            <div class="px-4 pb-4">
                <h6 style="font-size:0.76rem;font-weight:700;color:#ACA099;text-transform:uppercase;letter-spacing:0.06em;margin:18px 0 10px 0;" id="daySummarySectionTitle">Subjects</h6>
                <div id="daySummaryContent" class="d-flex flex-column gap-2">
                    <!-- dynamically populated -->
                </div>
                <a id="daySummaryRecordsLink" class="ent-btn ent-btn-ghost mt-3 d-none" onclick="closeDaySummaryModal()">View records for this day</a>
            </div>
        </div>
    </div>
</div>

<script @cspNonce>
/* ═══════════════════════════════════════════════════
   Squircle School Calendar — Vanilla JS Engine
   ═══════════════════════════════════════════════════ */
let scalYear  = {{ $year }};
let scalMonth = {{ $month }};

// Raw events from API: date string → array of event objects
let scalEventMap = {};
let scalDataState = 'loading';

const MONTH_NAMES = ['January','February','March','April','May','June',
                     'July','August','September','October','November','December'];

// Dominant status priority (worst-first so we pick the most visible)
const STATUS_PRIORITY = ['absent','late','present','exam','event','holiday'];

// Status → CSS class & dot colour
const STATUS_CLASS = {
    present : 'status-present',
    late    : 'status-late',
    absent  : 'status-absent',
    event   : 'status-event',
    holiday : 'status-holiday',
    exam    : 'status-exam',
};

/* ── Derive dominant status for a day ───────────────────── */
function scalDominantStatus(dateStr) {
    const evts = scalEventMap[dateStr];
    if (!evts || evts.length === 0) return null;

    // Map raw type/status → our simplified statuses
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

    // Return highest priority
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

/* ── Navigate ───────────────────────────────────────────── */
function scalPrevMonth() {
    scalMonth--;
    if (scalMonth < 1) { scalMonth = 12; scalYear--; }
    scalLoadAndRender();
    window.StudentMotion?.animateCalendarShift(calendarGrid, 'prev');
}
function scalNextMonth() {
    scalMonth++;
    if (scalMonth > 12) { scalMonth = 1; scalYear++; }
    scalLoadAndRender();
    window.StudentMotion?.animateCalendarShift(calendarGrid, 'next');
}
function scalJumpToday() {
    const now = new Date();
    scalYear = now.getFullYear();
    scalMonth = now.getMonth() + 1;
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const dd = String(now.getDate()).padStart(2, '0');
    const todayStr = `${yyyy}-${mm}-${dd}`;
    const dsm = document.getElementById('daySummaryModal');
    if (dsm) dsm.dataset.date = todayStr;
    scalLoadAndRender();
}

function scalHighlightTile(dateStr) {
    if (!calendarGrid) return;
    calendarGrid.querySelectorAll('button[data-date]').forEach(tile => {
        tile.classList.toggle('is-selected-day', tile.dataset.date === dateStr);
    });
}

function scalRenderDesktopInspector(dateStr, evts, summaryText, summaryBg, summaryColor) {
    const titleEl = document.getElementById('sdInspectorTitle');
    const subEl = document.getElementById('sdInspectorSubtitle');
    const badgeEl = document.getElementById('sdInspectorStatusBadge');
    const listEl = document.getElementById('sdInspectorContent');
    const recLink = document.getElementById('sdInspectorRecordsLink');
    const modalBtn = document.getElementById('sdInspectorModalBtn');
    if (!listEl) return;

    const dateObj = new Date(dateStr + 'T00:00:00');
    if (titleEl) {
        titleEl.textContent = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    }
    if (subEl) {
        subEl.textContent = evts.length > 0 ? `${evts.length} scheduled or recorded ${evts.length === 1 ? 'item' : 'items'}` : 'No items logged';
    }
    if (badgeEl) {
        if (summaryText) {
            badgeEl.textContent = summaryText;
            badgeEl.style.background = summaryBg;
            badgeEl.style.color = summaryColor;
            badgeEl.style.display = 'inline-block';
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
        recLink.classList.toggle('d-none', !hasAtt);
    }
    if (modalBtn) {
        modalBtn.dataset.date = dateStr;
        modalBtn.hidden = scalDataState !== 'ready' || evts.length === 0;
    }

    listEl.replaceChildren();
    window.StudentMotion?.animateInspectorSwap(listEl);
    if (scalDataState === 'loading') {
        listEl.innerHTML = '<p style="color:#ACA099;font-size:0.83rem;margin:0;">Loading day activity…</p>';
        return;
    }
    if (scalDataState === 'error') {
        listEl.innerHTML = '<p style="color:#F87171;font-size:0.83rem;margin:0;">Unable to load activities.</p>';
        return;
    }
    if (!evts.length) {
        listEl.innerHTML = '<p style="color:#ACA099;font-size:0.83rem;margin:0;">No attendance or school events recorded on this date.</p>';
        return;
    }
    evts.forEach(ev => {
        const isAtt = (ev.type || '').toLowerCase() === 'attendance';
        const status = (ev.status || '').toLowerCase();
        const evType = (ev.type || '').toLowerCase();
        const badgeColor = status === 'present' ? '#4ADE80' : (status === 'late' ? '#FBBF24' : (status === 'absent' ? '#F87171' : (status === 'excused' ? '#7DD3FC' : (evType === 'holiday' ? '#4ADE80' : (evType === 'exam' ? '#D4AF37' : '#7DD3FC')))));
        const item = document.createElement('div');
        item.className = 'sd-day-inspector-item';
        item.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                <strong style="color:#FAF8F5;font-size:0.85rem;">${recordText(isAtt ? (ev.subject_name || ev.title) : ev.title)}</strong>
                <span style="color:${badgeColor};font-size:0.72rem;font-weight:700;text-transform:uppercase;flex-shrink:0;">${recordText(isAtt ? ev.status : (ev.type || 'event').replace('_', ' '))}</span>
            </div>
            <div style="color:#ACA099;font-size:0.76rem;margin-top:4px;">${recordText(ev.time_string || (isAtt ? 'Time not set' : 'All Day'))}${ev.instructor_name ? ' · ' + recordText(ev.instructor_name) : ''}</div>
        `;
        listEl.appendChild(item);
    });
}

function scalLoadAndRender(historyMode = 'push') {
    syncCalendarRecordsMonth(scalYear, scalMonth);
    loadCalendarMonth(@js(route('student.calendar.data', absolute: false)), scalYear, scalMonth, (state, events) => {
        scalDataState = state;
        scalEventMap = events;
        scalRender();
        const modalEl = document.getElementById('daySummaryModal');
        const selectedDate = modalEl?.dataset.date;
        if (selectedDate?.slice(0, 7) === calendarGrid.dataset.month) {
            scalRenderDay(selectedDate);
        } else if (window.innerWidth >= 1024 && calendarGrid?.dataset.month) {
            const now = new Date();
            const nowMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
            const defaultDate = nowMonth === calendarGrid.dataset.month
                ? `${nowMonth}-${String(now.getDate()).padStart(2, '0')}`
                : `${calendarGrid.dataset.month}-01`;
            if (modalEl) modalEl.dataset.date = defaultDate;
            scalRenderDay(defaultDate);
        }
    });

    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('year',  scalYear);
    url.searchParams.set('month', scalMonth);
    if (historyMode === 'push') window.history.pushState({}, '', url);
    else if (historyMode === 'replace') window.history.replaceState({}, '', url);
}

window.addEventListener('popstate', () => {
    const params = new URL(location.href).searchParams;
    const year = Number(params.get('year'));
    const month = Number(params.get('month'));
    const today = new Date();
    scalYear = Number.isInteger(year) && year >= 1900 && year <= 2200 ? year : today.getFullYear();
    scalMonth = Number.isInteger(month) && month >= 1 && month <= 12 ? month : today.getMonth() + 1;
    scalLoadAndRender('none');
});

/* ── Day click → Day Summary Modal ─────────────────────── */
function scalRenderDay(dateStr) {
    scalHighlightTile(dateStr);
    const evts = scalEventMap[dateStr] || [];
    const recordsLink = document.getElementById('daySummaryRecordsLink');
    if (recordsLink) {
        const recordsUrl = new URL(@json(route('student.calendar')), window.location?.origin || 'http://localhost');
        recordsUrl.search = new URLSearchParams({ year: scalYear, month: scalMonth, record_date: dateStr });
        recordsUrl.hash = 'attendance-records';
        recordsLink.href = recordsUrl.href;
        recordsLink.classList.toggle('d-none', scalDataState === 'ready' && !evts.some(event => event.type === 'attendance'));
    }
    const dateObj = new Date(dateStr + 'T00:00:00');
    const titleEl = document.getElementById('daySummaryTitle');
    if (titleEl) {
        titleEl.textContent =
            dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    }

    const content = document.getElementById('daySummaryContent');
    content.innerHTML = '';

    let hasAbsent = false, hasPresent = false, hasLate = false, hasEvent = false;

    if (scalDataState !== 'ready' || evts.length === 0) {
        const subEl = document.getElementById('daySummarySubtitle');
        const secEl = document.getElementById('daySummarySectionTitle');
        if (subEl) subEl.style.display = 'none';
        if (secEl) secEl.style.display = 'none';
        const message = scalDataState === 'loading'
            ? 'Loading attendance and events for this date…'
            : scalDataState === 'error'
                ? 'Attendance and events couldn’t load. Close this window to retry the month.'
                : 'No activities recorded for this day.';
        const notice = document.createElement('p');
        notice.className = 'text-center my-3';
        notice.textContent = message;
        content.append(notice);
    } else {
        const secEl = document.getElementById('daySummarySectionTitle');
        if (secEl) secEl.style.display = 'block';

        evts.forEach(ev => {
            const t  = (ev.type || '').toLowerCase();
            const s  = (ev.status || '').toLowerCase();

            if (t === 'attendance') {
                if (s === 'absent')  hasAbsent  = true;
                if (s === 'present') hasPresent = true;
                if (s === 'late')    hasLate    = true;

                const statusColorMap = {
                    absent: '#F87171',
                    present: '#4ADE80',
                    late: '#FBBF24',
                    excused: '#7DD3FC',
                };
                const iconColor = statusColorMap[s] || '#D4AF37';
                const sn = ev.subject_name || ev.title || 'Subject';

                let iconCls = 'bi-journal-bookmark';
                const lc = sn.toLowerCase();
                if (lc.includes('code')||lc.includes('web')||lc.includes('prog')) iconCls = 'bi-code-slash';
                else if (lc.includes('math')||lc.includes('discrete'))            iconCls = 'bi-bar-chart-fill';
                else if (lc.includes('science')||lc.includes('physics'))          iconCls = 'bi-lightbulb';

                const badgeMap = {
                    absent:  { bg:'rgba(248,113,113,0.16)', color:'#F87171' },
                    present: { bg:'rgba(74,222,128,0.16)',  color:'#4ADE80' },
                    late:    { bg:'rgba(251,191,36,0.16)',  color:'#FBBF24' },
                    excused: { bg:'rgba(125,211,252,0.16)', color:'#7DD3FC' },
                };
                const badge = badgeMap[s] || { bg:'rgba(255,255,255,0.08)', color:'#FAF8F5' };

                content.insertAdjacentHTML('beforeend', `
                <div class="subject-card">
                    <div class="subject-card-icon" style="background:transparent;color:${iconColor};">
                        <i class="bi ${iconCls}"></i>
                    </div>
                    <div class="subject-card-info">
                        <div class="subject-card-title">${recordText(ev.subject_name || ev.title)}</div>
                        <div class="subject-card-time">${recordText(ev.time_string || 'Time not set')}</div>
                        <div class="subject-card-teacher">${recordText(ev.instructor_name || 'No Instructor')}</div>
                    </div>
                    <div class="subject-card-badge" style="background:${badge.bg};color:${badge.color};">${recordText(ev.status)}</div>
                </div>`);
            } else {
                hasEvent = true;
                const evColor = t === 'holiday' ? '#4ADE80' : (t === 'exam' ? '#D4AF37' : '#7DD3FC');
                content.insertAdjacentHTML('beforeend', `
                <div class="subject-card" style="border-color:${evColor}66;">
                    <div class="subject-card-icon" style="background:transparent;color:${evColor};">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="subject-card-info">
                        <div class="subject-card-title">${recordText(ev.title)}</div>
                        <div class="subject-card-time">${recordText(ev.time_string || 'All Day')}</div>
                        <div class="subject-card-teacher" style="text-transform:capitalize;">${recordText((ev.type || '').replace('_', ' '))}</div>
                    </div>
                </div>`);
            }
        });

        // Status summary
        const subEl = document.getElementById('daySummarySubtitle');
        if (subEl) subEl.style.display = 'flex';
        const dot  = document.getElementById('daySummaryStatusDot');
        const txt  = document.getElementById('daySummaryStatusText');
        if (dot && txt) {
            if (hasAbsent)       { dot.style.background = '#F87171'; txt.textContent = 'Absent'; }
            else if (hasLate)    { dot.style.background = '#FBBF24'; txt.textContent = 'Late'; }
            else if (hasPresent) { dot.style.background = '#4ADE80'; txt.textContent = 'Present'; }
            else if (hasEvent)   { dot.style.background = '#7DD3FC'; txt.textContent = 'School Event'; }
            else                 { dot.style.background = '#ACA099'; txt.textContent = 'No Status'; }
        }
    }

    const summaryLabel = hasAbsent ? 'Absent' : (hasLate ? 'Late' : (hasPresent ? 'Present' : (hasEvent ? 'Event' : '')));
    const summaryBg = hasAbsent ? 'rgba(248,113,113,0.16)' : (hasLate ? 'rgba(251,191,36,0.16)' : (hasPresent ? 'rgba(74,222,128,0.16)' : 'rgba(125,211,252,0.16)'));
    const summaryColor = hasAbsent ? '#F87171' : (hasLate ? '#FBBF24' : (hasPresent ? '#4ADE80' : '#7DD3FC'));
    scalRenderDesktopInspector(dateStr, evts, summaryLabel, summaryBg, summaryColor);
}

function scalOpenDaySummaryModal(dateStr) {
    scalRenderDay(dateStr);
    const modalEl = document.getElementById('daySummaryModal');
    if (modalEl) {
        modalEl.dataset.date = dateStr;
        if (modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }
        try {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } catch (err) {
            console.warn('scalDayClick fallback:', err);
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            modalEl.setAttribute('aria-modal', 'true');
            modalEl.removeAttribute('aria-hidden');
            document.body.classList.add('modal-open');
            let fb = document.querySelector('.scal-fallback-backdrop');
            if (!fb) {
                fb = document.createElement('div');
                fb.className = 'modal-backdrop fade show scal-fallback-backdrop';
                fb.style.zIndex = '10050';
                document.body.appendChild(fb);
                fb.onclick = closeDaySummaryModal;
            }
        }
    }
}

function scalDayClick(dateStr) {
    scalRenderDay(dateStr);
    const modalEl = document.getElementById('daySummaryModal');
    if (modalEl) modalEl.dataset.date = dateStr;
    if (window.innerWidth < 1024) {
        scalOpenDaySummaryModal(dateStr);
    }
}

function closeDaySummaryModal() {
    const m = document.getElementById('daySummaryModal');
    if (m) {
        delete m.dataset.date;
        try { bootstrap.Modal.getOrCreateInstance(m).hide(); } catch (e) {}
        m.classList.remove('show');
        m.style.display = 'none';
        m.setAttribute('aria-hidden', 'true');
        m.removeAttribute('aria-modal');
    }
    document.body.classList.remove('modal-open');
    const fb = document.querySelector('.scal-fallback-backdrop');
    if (fb) fb.remove();
}

/* ── Init ───────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    const edm = document.getElementById('eventDetailModal');
    if (edm && edm.parentNode !== document.body) document.body.appendChild(edm);
    const dsm = document.getElementById('daySummaryModal');
    if (dsm) {
        if (dsm.parentNode !== document.body) document.body.appendChild(dsm);
        dsm.addEventListener('hidden.bs.modal', () => delete dsm.dataset.date);
    }
    const inspectorModalBtn = document.getElementById('sdInspectorModalBtn');
    if (inspectorModalBtn) {
        inspectorModalBtn.addEventListener('click', () => {
            const targetDate = inspectorModalBtn.dataset.date || document.getElementById('daySummaryModal')?.dataset.date;
            if (targetDate) scalOpenDaySummaryModal(targetDate);
        });
    }
    scalLoadAndRender('replace');

    // Touch swipe for mobile
    let tx = 0, ty = 0;
    const card = document.querySelector('.scal-card');
    card.addEventListener('touchstart', e => { tx = e.changedTouches[0].screenX; ty = e.changedTouches[0].screenY; }, { passive: true });
    card.addEventListener('touchend', e => {
        const dx = tx - e.changedTouches[0].screenX;
        const dy = Math.abs(ty - e.changedTouches[0].screenY);
        if (Math.abs(dx) > 70 && dy < 60) { dx > 0 ? scalNextMonth() : scalPrevMonth(); }
    }, { passive: true });
});
</script>
@endsection
