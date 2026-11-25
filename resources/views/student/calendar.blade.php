@extends('layouts.app')

@section('page-title', 'Attendance & School Calendar')

@section('content')
<style>
/* ─── Squircle School Calendar ─────────────────────────── */
.scal-outer {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    max-width: 860px;
    margin: 0 auto;
    padding-bottom: 40px;
    gap: 20px;
}

.scal-card {
    background: #0f0a08;
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 28px;
    padding: 32px 36px;
    width: 100%;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7);
}

/* Nav row */
.scal-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}

.scal-nav-btn {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #cfa46f;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.scal-nav-btn:hover {
    background: rgba(207, 164, 111, 0.15);
    border-color: rgba(207, 164, 111, 0.4);
    color: #fff;
    transform: translateY(-2px);
}

.scal-nav-title {
    font-size: 1.4rem;
    font-weight: 800;
    color: #fdfbf7;
    letter-spacing: -0.02em;
}

/* Weekday headers */
.scal-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 8px;
    margin-bottom: 12px;
    background: rgba(255, 255, 255, 0.025);
    border: 1px solid rgba(255, 255, 255, 0.04);
    border-radius: 14px;
    padding: 12px 0;
}
.scal-wd {
    text-align: center;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #cfa46f;
}

/* Day grid */
.scal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
}

/* Base tile */
.scal-tile {
    height: 62px;
    min-height: 62px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.05);
    background: rgba(255, 255, 255, 0.025);
    position: relative;
    cursor: pointer !important;
    touch-action: manipulation;
    -webkit-tap-highlight-color: rgba(255, 209, 102, 0.2);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    gap: 4px;
    user-select: none;
    -webkit-user-select: none;
}
.scal-tile:hover {
    background: rgba(255, 255, 255, 0.07);
    border-color: rgba(207, 164, 111, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
}
.scal-tile:active {
    transform: scale(0.95);
    background: rgba(255, 209, 102, 0.12);
}
.scal-tile.empty {
    visibility: hidden;
    background: transparent !important;
    border-color: transparent !important;
    cursor: default;
    pointer-events: none;
}

/* Number label */
.scal-num {
    font-size: 1.05rem;
    font-weight: 700;
    color: #f3ede4;
    line-height: 1;
}

/* Sunday tint */
.scal-tile.sunday .scal-num { color: #f87171; }

/* Status dot indicator */
.scal-dots-row { display: flex; min-height: 5px; }
.scal-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #fff;
    opacity: 0.8;
}

/* TODAY — golden glow border */
.scal-tile.today {
    border: 2px solid #ffd166 !important;
    box-shadow: 0 0 16px rgba(255, 209, 102, 0.3), inset 0 0 10px rgba(255, 209, 102, 0.07) !important;
    background: rgba(255, 209, 102, 0.07) !important;
}
.scal-tile.today .scal-num {
    color: #ffd166;
}

/* Status fills */
.scal-tile.status-present {
    background: rgba(6, 78, 59, 0.5) !important;
    border-color: rgba(16, 185, 129, 0.35) !important;
}
.scal-tile.status-present .scal-dot { background: #10b981; }

.scal-tile.status-late {
    background: rgba(120, 80, 20, 0.5) !important;
    border-color: rgba(245, 158, 11, 0.35) !important;
}
.scal-tile.status-late .scal-dot { background: #f59e0b; }

.scal-tile.status-absent {
    background: rgba(100, 15, 15, 0.6) !important;
    border-color: rgba(239, 68, 68, 0.4) !important;
}
.scal-tile.status-absent .scal-dot { background: #ef4444; }

.scal-tile.status-event {
    background: rgba(60, 25, 120, 0.45) !important;
    border-color: rgba(139, 92, 246, 0.35) !important;
}
.scal-tile.status-event .scal-dot { background: #8b5cf6; }

.scal-tile.status-holiday {
    background: rgba(5, 60, 50, 0.45) !important;
    border-color: rgba(74, 222, 128, 0.35) !important;
}
.scal-tile.status-holiday .scal-dot { background: #4ade80; }

.scal-tile.status-exam {
    background: rgba(100, 20, 60, 0.5) !important;
    border-color: rgba(236, 72, 153, 0.35) !important;
}
.scal-tile.status-exam .scal-dot { background: #ec4899; }


/* Side panel */
.scal-side {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Legend card */
.scal-legend-card {
    background: #0f0a08;
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 22px;
    padding: 24px 28px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);
}
.scal-legend-title {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #cfa46f;
    margin-bottom: 16px;
}
.scal-legend-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.scal-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.83rem;
    color: #e0d6cc;
}
.scal-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 3px;
    flex-shrink: 0;
}

/* Tips card */
.scal-tips-card {
    background: rgba(207, 164, 111, 0.05);
    border: 1px solid rgba(207, 164, 111, 0.12);
    border-radius: 18px;
    padding: 20px 24px;
}
.scal-tips-title {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #cfa46f;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.scal-tips-list {
    margin: 0;
    padding-left: 18px;
    font-size: 0.83rem;
    color: rgba(224, 214, 204, 0.7);
    line-height: 1.65;
}

/* Responsive: desktop side-by-side */
@media (min-width: 860px) {
    .scal-outer {
        flex-direction: row;
        align-items: flex-start;
        max-width: 1100px;
    }
    .scal-card {
        flex: 1;
        min-width: 0;
    }
    .scal-side {
        width: 260px;
        flex-shrink: 0;
    }
}

/* Mobile tweaks */
@media (max-width: 640px) {
    .scal-card { padding: 18px 12px; border-radius: 20px; }
    .scal-tile { height: 50px; min-height: 50px; border-radius: 11px; }
    .scal-num  { font-size: 0.9rem; }
    .scal-grid { gap: 7px; }
    .scal-weekdays { gap: 7px; padding: 10px 0; }
    .scal-nav-title { font-size: 1.15rem; }
    .scal-nav-btn   { width: 44px; height: 44px; font-size: 1rem; border-radius: 11px; }
}

/* ─── Modal styles ───────────────────────────────────────── */
.ent-modal-content {
    background: #0f0a08;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 24px;
    box-shadow: 0 32px 80px rgba(0,0,0,0.7), inset 0 1px 0 rgba(255,255,255,0.05);
}
.ent-modal-header {
    border-bottom: 1px solid rgba(255,255,255,0.06);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(0,0,0,0.2);
    border-radius: 24px 24px 0 0;
}
.ent-modal-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #f3ede4;
    margin: 0;
}
.ent-modal-body  { padding: 24px; }
.ent-modal-row   { margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.04); }
.ent-modal-row:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
.ent-modal-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: #8b7d70; margin-bottom: 6px; }
.ent-modal-value { font-size: 0.95rem; color: #f3ede4; display: flex; align-items: center; gap: 8px; font-weight: 500; }

/* Bottom sheet */
#daySummaryModal,
#eventDetailModal {
    z-index: 10060 !important;
}
.modal-backdrop {
    z-index: 10050 !important;
}
.modal-dialog-bottom { display: flex; align-items: flex-end; min-height: 100%; margin: 0; padding: 0; }
@media (min-width: 576px) {
    .modal-dialog-bottom { align-items: center; margin: 1.75rem auto; max-width: 500px; min-height: calc(100% - 3.5rem); }
}
.bottom-sheet-content {
    background: #0f0a08;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 24px 24px 0 0;
    width: 100%;
    padding-bottom: calc(24px + env(safe-area-inset-bottom, 16px));
    box-shadow: 0 -10px 40px rgba(0,0,0,0.6);
}
@media (min-width: 576px) { .bottom-sheet-content { border-radius: 24px; padding-bottom: 0; } }
.bottom-sheet-handle { width: 40px; height: 4px; background: rgba(255,255,255,0.22); border-radius: 2px; margin: 12px auto; display: block; }

/* Subject cards */
.subject-card {
    background: rgba(255,255,255,0.025);
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 1px solid rgba(255,255,255,0.04);
    transition: background 0.2s;
}
.subject-card:hover { background: rgba(255,255,255,0.05); }
.subject-card-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.subject-card-info { flex: 1; }
.subject-card-title { font-weight: 600; font-size: 0.95rem; color: #f3ede4; margin-bottom: 2px; }
.subject-card-time  { font-size: 0.8rem; color: #8b7d70; margin-bottom: 1px; }
.subject-card-teacher { font-size: 0.8rem; color: #a09080; }
.subject-card-badge { padding: 5px 11px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; white-space: nowrap; }
</style>

{{-- ────────────────────────────── LAYOUT ────────────────────────────── --}}
@include('partials.student-page-header', ['title' => 'Attendance & school calendar', 'description' => 'Check school dates and review your attendance history.'])
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
                <button type="button" class="scal-nav-today-btn d-none d-md-inline-flex" id="scalTodayBtn" onclick="scalJumpToday()" title="Jump to current month">
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
                <p style="color:#9E8990; font-size:0.84rem; margin:0;">Loading calendar activity…</p>
            </div>
            <div class="d-flex align-items-center justify-content-between gap-2 mt-3 pt-2" style="border-top: 1px solid rgba(250, 247, 248, 0.08);">
                <a href="{{ route('student.calendar') }}#attendance-records" id="sdInspectorRecordsLink" class="d-none" style="color:#E898A8; font-size:0.8rem; font-weight:700; text-decoration:none;">
                    <i class="bi bi-funnel me-1" aria-hidden="true"></i>Filter records
                </a>
                <button type="button" id="sdInspectorModalBtn" class="btn btn-sm ms-auto" style="background: #1C0C11; border: 1px solid rgba(250,247,248,0.16); color:#FAF7F8; font-size:0.76rem; font-weight:700; border-radius:8px; padding:6px 12px;">
                    <i class="bi bi-arrows-angle-expand me-1" aria-hidden="true"></i>Details
                </button>
            </div>
        </section>

        {{-- Legend --}}
        <div class="scal-legend-card">
            <div class="scal-legend-title">Legend</div>
            <div class="scal-legend-grid">
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#34D399;"></span>
                    Present
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#FBBF24;"></span>
                    Late
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#FB7185;"></span>
                    Absent
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#EC4899;"></span>
                    Exam
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#A78BFA;"></span>
                    Event
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#4ADE80;"></span>
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
                        <i class="bi bi-clock" style="color:#E898A8;" aria-hidden="true"></i>
                        <span id="eventTime">Date</span>
                    </div>
                </div>
                <div class="ent-modal-row" id="eventLocationContainer">
                    <div class="ent-modal-label">Location</div>
                    <div class="ent-modal-value">
                        <i class="bi bi-geo-alt" style="color:#E898A8;" aria-hidden="true"></i>
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
            <div class="d-flex justify-content-between align-items-start px-4 pt-4 pb-3 border-bottom position-relative" style="border-color:rgba(250,247,248,0.08)!important;">
                <div>
                    <h3 style="font-weight:700;font-size:1.15rem;color:#FAF7F8;margin:0;padding-right:36px;" id="daySummaryTitle">Date</h3>
                    <div style="font-size:0.84rem;color:#9E8990;display:flex;align-items:center;gap:6px;margin-top:4px;" id="daySummarySubtitle">
                        <span id="daySummaryStatusDot" style="width:8px;height:8px;border-radius:50%;display:inline-block;"></span>
                        <span id="daySummaryStatusText">Status</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" aria-label="Close details" data-bs-dismiss="modal" onclick="closeDaySummaryModal()" style="position:absolute;top:16px;right:16px;min-width:44px;min-height:44px;"></button>
            </div>
            <div class="px-4 pb-4">
                <h6 style="font-size:0.76rem;font-weight:700;color:#9E8990;text-transform:uppercase;letter-spacing:0.06em;margin:18px 0 10px 0;" id="daySummarySectionTitle">Subjects</h6>
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
}
function scalNextMonth() {
    scalMonth++;
    if (scalMonth > 12) { scalMonth = 1; scalYear++; }
    scalLoadAndRender();
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
    }

    listEl.replaceChildren();
    if (scalDataState === 'loading') {
        listEl.innerHTML = '<p style="color:#9E8990;font-size:0.83rem;margin:0;">Loading day activity…</p>';
        return;
    }
    if (scalDataState === 'error') {
        listEl.innerHTML = '<p style="color:#FDA4AF;font-size:0.83rem;margin:0;">Unable to load activities.</p>';
        return;
    }
    if (!evts.length) {
        listEl.innerHTML = '<p style="color:#9E8990;font-size:0.83rem;margin:0;">No attendance or school events recorded on this date.</p>';
        return;
    }
    evts.forEach(ev => {
        const isAtt = (ev.type || '').toLowerCase() === 'attendance';
        const status = (ev.status || '').toLowerCase();
        const badgeColor = status === 'present' ? '#34D399' : (status === 'late' ? '#FBBF24' : (status === 'absent' ? '#FB7185' : '#C084FC'));
        const item = document.createElement('div');
        item.className = 'sd-day-inspector-item';
        item.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                <strong style="color:#FAF7F8;font-size:0.85rem;">${recordText(isAtt ? (ev.subject_name || ev.title) : ev.title)}</strong>
                <span style="color:${badgeColor};font-size:0.72rem;font-weight:700;text-transform:uppercase;flex-shrink:0;">${recordText(isAtt ? ev.status : (ev.type || 'event').replace('_', ' '))}</span>
            </div>
            <div style="color:#9E8990;font-size:0.76rem;margin-top:4px;">${recordText(ev.time_string || (isAtt ? 'Time not set' : 'All Day'))}${ev.instructor_name ? ' · ' + recordText(ev.instructor_name) : ''}</div>
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

                const colors = ['#8b5cf6','#10b981','#f59e0b','#3b82f6','#ec4899'];
                let hash = 0;
                const sn = ev.subject_name || ev.title || 'Subject';
                for (let i = 0; i < sn.length; i++) hash = sn.charCodeAt(i) + ((hash << 5) - hash);
                const iconColor = colors[Math.abs(hash) % colors.length];

                let iconCls = 'bi-journal-bookmark';
                const lc = sn.toLowerCase();
                if (lc.includes('code')||lc.includes('web')||lc.includes('prog')) iconCls = 'bi-code-slash';
                else if (lc.includes('math')||lc.includes('discrete'))            iconCls = 'bi-bar-chart-fill';
                else if (lc.includes('science')||lc.includes('physics'))          iconCls = 'bi-lightbulb';

                const badgeMap = {
                    absent:  { bg:'rgba(239,68,68,0.18)',  color:'#ef4444' },
                    present: { bg:'rgba(16,185,129,0.18)', color:'#10b981' },
                    late:    { bg:'rgba(245,158,11,0.18)', color:'#f59e0b' },
                };
                const badge = badgeMap[s] || { bg:'rgba(255,255,255,0.1)', color:'#fff' };

                content.insertAdjacentHTML('beforeend', `
                <div class="subject-card">
                    <div class="subject-card-icon" style="background:${iconColor}20;color:${iconColor};">
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
                const evColor = /^#[0-9a-f]{6}$/i.test(ev.color) ? ev.color : '#8b5cf6';
                content.insertAdjacentHTML('beforeend', `
                <div class="subject-card" style="border:1px dashed ${evColor}40;">
                    <div class="subject-card-icon" style="background:${evColor}20;color:${evColor};">
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
            if (hasAbsent)       { dot.style.background = '#ef4444'; txt.textContent = 'Absent'; }
            else if (hasLate)    { dot.style.background = '#f59e0b'; txt.textContent = 'Late'; }
            else if (hasPresent) { dot.style.background = '#10b981'; txt.textContent = 'Present'; }
            else if (hasEvent)   { dot.style.background = '#8b5cf6'; txt.textContent = 'School Event'; }
            else                 { dot.style.background = '#6b7280'; txt.textContent = 'No Status'; }
        }
    }

    const summaryLabel = hasAbsent ? 'Absent' : (hasLate ? 'Late' : (hasPresent ? 'Present' : (hasEvent ? 'Event' : '')));
    const summaryBg = hasAbsent ? 'rgba(239,68,68,0.16)' : (hasLate ? 'rgba(245,158,11,0.16)' : (hasPresent ? 'rgba(16,185,129,0.16)' : 'rgba(139,92,246,0.16)'));
    const summaryColor = hasAbsent ? '#fca5a5' : (hasLate ? '#fcd34d' : (hasPresent ? '#6ee7b7' : '#d8b4fe'));
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
