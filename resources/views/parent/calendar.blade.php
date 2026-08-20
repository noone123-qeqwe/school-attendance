@extends('layouts.app')

@section('page-title', 'Attendance Calendar')

@php
    $firstChild = $children->first();
    $selectedChildId = request('child_id', $firstChild?->id);
    $selectedChild = $children->firstWhere('id', $selectedChildId) ?? $firstChild;
@endphp

@section('content')
<style>
/* ─── Squircle Attendance Calendar (Parent) ──────────────── */
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
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    gap: 4px;
    user-select: none;
}
.scal-tile:hover {
    background: rgba(255, 255, 255, 0.07);
    border-color: rgba(207, 164, 111, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
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
.scal-tile.today .scal-num { color: #ffd166; }

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

/* Loading pulse */
.scal-tile.loading {
    animation: scal-pulse 1.4s ease-in-out infinite;
}
@keyframes scal-pulse {
    0%, 100% { opacity: 0.4; }
    50%       { opacity: 0.8; }
}

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
    .scal-card  { flex: 1; min-width: 0; }
    .scal-side  { width: 260px; flex-shrink: 0; }
}

/* Mobile tweaks */
@media (max-width: 640px) {
    .scal-card { padding: 20px 16px; border-radius: 20px; }
    .scal-tile { height: 50px; min-height: 50px; border-radius: 11px; }
    .scal-num  { font-size: 0.9rem; }
    .scal-grid { gap: 7px; }
    .scal-weekdays { gap: 7px; padding: 10px 0; }
    .scal-nav-title { font-size: 1.15rem; }
    .scal-nav-btn   { width: 40px; height: 40px; font-size: 1rem; border-radius: 11px; }
}

/* ── Custom Luxury Student Picker ─────────────────────── */
.scal-student-custom-picker {
    background: linear-gradient(135deg, rgba(32, 20, 15, 0.7) 0%, rgba(20, 10, 5, 0.85) 100%);
    border: 1px solid rgba(207, 164, 111, 0.25);
    border-radius: 18px;
    padding: 12px 18px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
    margin-bottom: 22px;
}
.scal-picker-title {
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #cfa46f;
}
.scal-picker-trigger {
    width: 100%;
    background: rgba(15, 10, 8, 0.95);
    border: 1px solid rgba(207, 164, 111, 0.35);
    border-radius: 14px;
    padding: 8px 14px;
    color: #f3ede4;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}
.scal-picker-trigger:hover, .scal-picker-trigger[aria-expanded="true"] {
    border-color: rgba(207, 164, 111, 0.65);
    background: rgba(26, 18, 14, 0.98);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.45), 0 0 0 2px rgba(207, 164, 111, 0.15);
}
.scal-picker-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #7A1A1A, #3a1010);
    border: 1.5px solid rgba(207, 164, 111, 0.5);
    color: #f3ede4;
    font-weight: 800;
    font-size: 0.78rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
}
.scal-picker-avatar.mini {
    width: 30px;
    height: 30px;
    font-size: 0.72rem;
}
.scal-picker-name {
    font-weight: 700;
    font-size: 0.92rem;
    color: #f3ede4;
    line-height: 1.2;
}
.scal-picker-sub {
    font-size: 0.72rem;
    color: #b39b82;
    font-family: monospace;
    margin-top: 1px;
}
.scal-picker-chevron {
    color: #cfa46f;
    font-size: 0.85rem;
    transition: transform 0.25s ease;
    flex-shrink: 0;
}
.scal-picker-trigger[aria-expanded="true"] .scal-picker-chevron {
    transform: rotate(180deg);
}
.scal-picker-menu {
    background: #140d07 !important;
    border: 1px solid rgba(207, 164, 111, 0.35) !important;
    border-radius: 16px !important;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(207, 164, 111, 0.1) !important;
    min-width: 280px;
    z-index: 1050;
    margin-top: 8px !important;
}
.scal-picker-item {
    width: 100%;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 10px;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #f3ede4;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 2px;
}
.scal-picker-item:hover {
    background: rgba(207, 164, 111, 0.1);
    border-color: rgba(207, 164, 111, 0.25);
    color: #fff;
    transform: translateX(2px);
}
.scal-picker-item.active {
    background: rgba(207, 164, 111, 0.15);
    border-color: rgba(207, 164, 111, 0.4);
    color: #fff;
}
.scal-picker-item-name {
    font-weight: 700;
    font-size: 0.88rem;
    color: #f3ede4;
}
.scal-picker-item-sub {
    font-size: 0.72rem;
    color: #b39b82;
    font-family: monospace;
}
.scal-picker-check {
    color: #cfa46f;
    font-size: 1.1rem;
    font-weight: 800;
}

/* Single student banner */
.scal-single-student-card {
    background: linear-gradient(135deg, rgba(32, 20, 15, 0.6) 0%, rgba(20, 10, 5, 0.8) 100%);
    border: 1px solid rgba(207, 164, 111, 0.2);
    border-radius: 16px;
    padding: 12px 18px;
    margin-bottom: 22px;
}
.scal-single-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #cfa46f;
}
.scal-single-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: #f3ede4;
}
.scal-single-badge {
    font-size: 0.78rem;
    font-weight: 600;
    color: #cfa46f;
    background: rgba(207, 164, 111, 0.1);
    border: 1px solid rgba(207, 164, 111, 0.25);
    padding: 4px 12px;
    border-radius: 99px;
    font-family: monospace;
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
.ent-modal-title  { font-size: 1.15rem; font-weight: 700; color: #f3ede4; margin: 0; }
.ent-modal-body   { padding: 24px; }
.ent-modal-row    { margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.04); }
.ent-modal-row:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
.ent-modal-label  { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; color: #8b7d70; margin-bottom: 6px; }
.ent-modal-value  { font-size: 0.95rem; color: #f3ede4; display: flex; align-items: center; gap: 8px; font-weight: 500; }

/* Bottom sheet */
.modal-dialog-bottom { display: flex; align-items: flex-end; min-height: 100%; margin: 0; padding: 0; }
@media (min-width: 576px) {
    .modal-dialog-bottom { align-items: center; margin: 1.75rem auto; max-width: 500px; min-height: calc(100% - 3.5rem); }
}
.bottom-sheet-content {
    background: #0f0a08;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 24px 24px 0 0;
    width: 100%;
    padding-bottom: env(safe-area-inset-bottom);
    box-shadow: 0 -10px 40px rgba(0,0,0,0.6);
}
@media (min-width: 576px) { .bottom-sheet-content { border-radius: 24px; } }
.bottom-sheet-handle { width: 40px; height: 4px; background: rgba(255,255,255,0.15); border-radius: 2px; margin: 12px auto; }

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
.subject-card-icon    { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.subject-card-info    { flex: 1; }
.subject-card-title   { font-weight: 600; font-size: 0.95rem; color: #f3ede4; margin-bottom: 2px; }
.subject-card-time    { font-size: 0.8rem; color: #8b7d70; margin-bottom: 1px; }
.subject-card-teacher { font-size: 0.8rem; color: #a09080; }
.subject-card-badge   { padding: 5px 11px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; white-space: nowrap; }

/* ── Monthly Stats Card ───────────────────────────────── */
.scal-stats-card {
    background: #0f0a08;
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 22px;
    padding: 20px 22px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);
}
.scal-stats-label {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #cfa46f;
    margin-bottom: 14px;
}
.scal-rate-wrap {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 14px;
}
.scal-rate-ring {
    width: 66px;
    height: 66px;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    font-weight: 800;
    transition: background 0.5s ease, color 0.3s ease;
}
.scal-stats-pills { display: flex; flex-direction: column; gap: 8px; flex: 1; }
.scal-stat-pill {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.78rem;
    color: #c0b5ad;
}
.scal-stat-pill-left { display: flex; align-items: center; gap: 7px; }
.scal-stat-pill-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.scal-stat-pill-val  { font-weight: 700; font-size: 0.88rem; color: #f3ede4; }

/* ── Today button ─────────────────────────────────────── */
.scal-today-btn {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #cfa46f;
    background: rgba(207,164,111,0.07);
    border: 1px solid rgba(207,164,111,0.2);
    border-radius: 6px;
    padding: 3px 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: block;
    margin-top: 5px;
}
.scal-today-btn:hover {
    background: rgba(207,164,111,0.18);
    border-color: rgba(207,164,111,0.5);
    color: #fff;
}

/* ── Multi-dot row on tiles ───────────────────────────── */
.scal-dots-row {
    display: flex;
    gap: 3px;
    align-items: center;
    justify-content: center;
    min-height: 7px;
}
.scal-mdot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    flex-shrink: 0;
}

</style>

{{-- ────────────────────────────── LAYOUT ────────────────────────────── --}}
<div class="scal-outer">

    {{-- MAIN CALENDAR CARD --}}
    <div class="scal-card">
        {{-- Navigation --}}
        <div class="scal-nav">
            <button class="scal-nav-btn" onclick="scalPrevMonth()" title="Previous month">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                <span class="scal-nav-title" id="scalMonthLabel">
                    {{ \Carbon\Carbon::now()->format('F Y') }}
                </span>
                <button class="scal-today-btn" onclick="scalGoToday()" id="scalTodayBtn">Today</button>
            </div>
            <button class="scal-nav-btn" onclick="scalNextMonth()" title="Next month">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>

        {{-- Custom Luxury Student Selector --}}
        @if($children->count() > 1)
        <div class="scal-student-custom-picker">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="scal-picker-title">
                        <i class="bi bi-person-badge-fill me-1" style="color: #cfa46f;"></i> Viewing Calendar for:
                    </span>
                </div>
                
                <div class="dropdown position-relative flex-grow-1" style="max-width: 380px; min-width: 250px;">
                    <button class="scal-picker-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="scalPickerBtn">
                        <div class="d-flex align-items-center gap-3 overflow-hidden">
                            <div class="scal-picker-avatar" id="activeStudentAvatar">
                                {{ strtoupper(substr($selectedChild->name ?? $firstChild->name, 0, 2)) }}
                            </div>
                            <div class="text-start overflow-hidden">
                                <div class="scal-picker-name text-truncate" id="activeStudentName">
                                    {{ $selectedChild->name ?? $firstChild->name }}
                                </div>
                                <div class="scal-picker-sub text-truncate" id="activeStudentSub">
                                    ID: {{ $selectedChild->student_number ?? $firstChild->student_number ?? 'Student' }}
                                </div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-down scal-picker-chevron"></i>
                    </button>
                    
                    <ul class="dropdown-menu dropdown-menu-end scal-picker-menu p-2">
                        @foreach($children as $child)
                        @php
                            $isSel = ($child->id == $selectedChildId || ($loop->first && !$selectedChildId));
                        @endphp
                        <li>
                            <button type="button" class="scal-picker-item {{ $isSel ? 'active' : '' }}" 
                                    data-child-id="{{ $child->id }}"
                                    data-name="{{ $child->name }}"
                                    data-initials="{{ strtoupper(substr($child->name, 0, 2)) }}"
                                    data-number="{{ $child->student_number ?? 'Student' }}"
                                    onclick="scalSelectChild(this, {{ $child->id }})">
                                <div class="scal-picker-avatar mini">
                                    {{ strtoupper(substr($child->name, 0, 2)) }}
                                </div>
                                <div class="flex-grow-1 text-start overflow-hidden">
                                    <div class="scal-picker-item-name text-truncate">{{ $child->name }}</div>
                                    <div class="scal-picker-item-sub text-truncate">ID: {{ $child->student_number ?? 'Student' }}</div>
                                </div>
                                <i class="bi bi-check2 scal-picker-check {{ $isSel ? 'd-block' : 'd-none' }}"></i>
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @elseif($children->count() === 1)
        <div class="scal-single-student-card">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="scal-picker-avatar">
                        {{ strtoupper(substr($firstChild->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="scal-single-label">Student Calendar</div>
                        <div class="scal-single-name">{{ $firstChild->name }}</div>
                    </div>
                </div>
                <div class="scal-single-badge">
                    <i class="bi bi-mortarboard-fill me-1"></i> ID: {{ $firstChild->student_number ?? 'Student' }}
                </div>
            </div>
        </div>
        @endif

        {{-- Weekday headers --}}
        <div class="scal-weekdays">
            @foreach(['S','M','T','W','T','F','S'] as $d)
                <div class="scal-wd">{{ $d }}</div>
            @endforeach
        </div>

        {{-- Day grid (rendered by JS) --}}
        <div class="scal-grid" id="scalGrid"></div>
    </div>

    {{-- SIDE PANEL --}}
    <div class="scal-side">
        {{-- Monthly Stats --}}
        <div class="scal-stats-card">
            <div class="scal-stats-label" id="scalStatsMonth">Loading…</div>
            <div class="scal-rate-wrap">
                <div class="scal-rate-ring" id="scalRateRing" style="color:#8b7d70;background:rgba(255,255,255,0.04);">—</div>
                <div class="scal-stats-pills">
                    <div class="scal-stat-pill">
                        <span class="scal-stat-pill-left">
                            <span class="scal-stat-pill-dot" style="background:#10b981;"></span>Present
                        </span>
                        <span class="scal-stat-pill-val" id="scalStatPresent">—</span>
                    </div>
                    <div class="scal-stat-pill">
                        <span class="scal-stat-pill-left">
                            <span class="scal-stat-pill-dot" style="background:#f59e0b;"></span>Late
                        </span>
                        <span class="scal-stat-pill-val" id="scalStatLate">—</span>
                    </div>
                    <div class="scal-stat-pill">
                        <span class="scal-stat-pill-left">
                            <span class="scal-stat-pill-dot" style="background:#ef4444;"></span>Absent
                        </span>
                        <span class="scal-stat-pill-val" id="scalStatAbsent">—</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="scal-legend-card">
            <div class="scal-legend-title">Legend</div>
            <div class="scal-legend-grid">
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#10b981; box-shadow:0 0 6px rgba(16,185,129,0.4);"></span>
                    Present
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#f59e0b; box-shadow:0 0 6px rgba(245,158,11,0.4);"></span>
                    Late
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#ef4444; box-shadow:0 0 6px rgba(239,68,68,0.4);"></span>
                    Absent
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#ec4899; box-shadow:0 0 6px rgba(236,72,153,0.4);"></span>
                    Exam
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#8b5cf6; box-shadow:0 0 6px rgba(139,92,246,0.4);"></span>
                    Event
                </div>
                <div class="scal-legend-item">
                    <span class="scal-legend-dot" style="background:#4ade80; box-shadow:0 0 6px rgba(74,222,128,0.4);"></span>
                    Holiday
                </div>
            </div>
        </div>

        {{-- Tips --}}
        <div class="scal-tips-card">
            <div class="scal-tips-title">
                <i class="bi bi-lightbulb"></i> Calendar Tips
            </div>
            <ul class="scal-tips-list">
                <li>Tap any <strong>day tile</strong> to view your child's subjects and attendance.</li>
                <li>Tile color shows the <strong>dominant attendance status</strong> for that day.</li>
                <li>The <strong>golden border</strong> marks today.</li>
                <li>A <strong>dashed border</strong> on a subject card means the absence was excused.</li>
            </ul>
        </div>
    </div>
</div>

{{-- ── Event Detail Modal ──────────────────────────────────────────────── --}}
<div class="modal fade" id="eventDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ent-modal-content w-100">
            <div class="ent-modal-header position-relative">
                <h5 class="ent-modal-title" id="eventTitle" style="padding-right: 24px;">Event</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="position:absolute;top:20px;right:20px;"></button>
            </div>
            <div class="ent-modal-body">
                <div class="ent-modal-row">
                    <div class="ent-modal-label">Event Type</div>
                    <div class="ent-modal-value">
                        <span id="eventTypeBadge" style="font-size:0.75rem;padding:4px 10px;border-radius:4px;box-shadow:0 2px 5px rgba(0,0,0,0.3);">Type</span>
                    </div>
                </div>
                <div class="ent-modal-row">
                    <div class="ent-modal-label">Date &amp; Time</div>
                    <div class="ent-modal-value">
                        <i class="bi bi-clock" style="color:#cfa46f;"></i>
                        <span id="eventTime">Date</span>
                    </div>
                </div>
                <div class="ent-modal-row" id="eventLocationContainer">
                    <div class="ent-modal-label">Location</div>
                    <div class="ent-modal-value">
                        <i class="bi bi-geo-alt" style="color:#cfa46f;"></i>
                        <span id="eventLocation">Location</span>
                    </div>
                </div>
                <div class="ent-modal-row" id="eventDescriptionContainer" style="display:none;">
                    <div class="ent-modal-label">Description</div>
                    <div class="ent-modal-value" style="font-size:0.9rem;font-weight:normal;color:#a09080;" id="eventDescription"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Day Summary Modal ───────────────────────────────────────────────── --}}
<div class="modal fade" id="daySummaryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-bottom modal-dialog-scrollable">
        <div class="modal-content bottom-sheet-content">
            <div class="bottom-sheet-handle d-none"></div>
            <div class="d-flex justify-content-between align-items-start px-4 pt-4 pb-3 border-bottom position-relative" style="border-color:rgba(255,255,255,0.06)!important;">
                <div>
                    <h3 style="font-weight:700;font-size:1.2rem;color:#f3ede4;margin:0;padding-right:24px;" id="daySummaryTitle">Date</h3>
                    <div style="font-size:0.85rem;color:#8b7d70;display:flex;align-items:center;gap:6px;margin-top:4px;" id="daySummarySubtitle">
                        <span id="daySummaryStatusDot" style="width:8px;height:8px;border-radius:50%;display:inline-block;"></span>
                        <span id="daySummaryStatusText">Status</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="position:absolute;top:20px;right:20px;"></button>
            </div>
            <div class="px-4 pb-4">
                <h6 style="font-size:0.78rem;font-weight:700;color:#8b7d70;text-transform:uppercase;letter-spacing:0.06em;margin:18px 0 10px 0;" id="daySummarySectionTitle">Subjects</h6>
                <div id="daySummaryContent" class="d-flex flex-column gap-2">
                    <!-- dynamically populated -->
                </div>
                <div style="font-size:0.78rem;color:rgba(255,255,255,0.4);text-align:center;margin-top:18px;">
                    <i class="bi bi-info-circle"></i> All subjects for this day are shown above.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* ═══════════════════════════════════════════════════
   Squircle Attendance Calendar — Parent View
   ═══════════════════════════════════════════════════ */
let scalYear       = new Date().getFullYear();
let scalMonth      = new Date().getMonth() + 1;
let scalChildId    = {{ $selectedChildId ?? 'null' }};
const scalChildCount = {{ $children->count() }};

// Raw events from API: date string → array of event objects
const scalEventMap = {};

const MONTH_NAMES = ['January','February','March','April','May','June',
                     'July','August','September','October','November','December'];

// Dominant status priority (worst-first)
const STATUS_PRIORITY = ['absent','late','present','exam','event','holiday'];

const STATUS_CLASS = {
    present : 'status-present',
    late    : 'status-late',
    absent  : 'status-absent',
    event   : 'status-event',
    holiday : 'status-holiday',
    exam    : 'status-exam',
};

/* ── Switch active child & select item ──────────────────── */
function scalSelectChild(btn, childId) {
    scalChildId = parseInt(childId);
    
    // Update active trigger text & avatar
    const name = btn.getAttribute('data-name');
    const initials = btn.getAttribute('data-initials');
    const number = btn.getAttribute('data-number');
    
    const activeName = document.getElementById('activeStudentName');
    const activeAvatar = document.getElementById('activeStudentAvatar');
    const activeSub = document.getElementById('activeStudentSub');
    
    if (activeName) activeName.textContent = name;
    if (activeAvatar) activeAvatar.textContent = initials;
    if (activeSub) activeSub.textContent = 'ID: ' + number;
    
    // Update menu items
    document.querySelectorAll('.scal-picker-item').forEach(item => {
        const isItem = parseInt(item.getAttribute('data-child-id')) === scalChildId;
        item.classList.toggle('active', isItem);
        const check = item.querySelector('.scal-picker-check');
        if (check) {
            check.classList.toggle('d-block', isItem);
            check.classList.toggle('d-none', !isItem);
        }
    });
    
    scalLoadAndRender();
}

function scalSwitchChild(childId) {
    scalChildId = parseInt(childId);
    const targetBtn = document.querySelector(`.scal-picker-item[data-child-id="${childId}"]`);
    if (targetBtn) {
        scalSelectChild(targetBtn, childId);
    } else {
        scalLoadAndRender();
    }
}

/* ── Go to today ─────────────────────────────────────────── */
function scalGoToday() {
    const now = new Date();
    scalYear  = now.getFullYear();
    scalMonth = now.getMonth() + 1;
    scalLoadAndRender();
}

/* ── Get ordered statuses present on a day ───────────────── */
function scalGetDayStatuses(dateStr) {
    const evts = scalEventMap[dateStr];
    if (!evts || evts.length === 0) return [];
    let hasPresent = false, hasLate = false, hasAbsent = false,
        hasEvent   = false, hasHoliday = false, hasExam = false;
    evts.forEach(ev => {
        const t = (ev.type || '').toLowerCase();
        const s = (ev.status || '').toLowerCase();
        if (t === 'attendance') {
            if (s === 'absent')       hasAbsent  = true;
            else if (s === 'late')    hasLate    = true;
            else if (s === 'present') hasPresent = true;
        } else if (t === 'exam')    hasExam    = true;
          else if (t === 'holiday') hasHoliday = true;
          else                      hasEvent   = true;
    });
    const result = [];
    if (hasAbsent)  result.push('absent');
    if (hasLate)    result.push('late');
    if (hasPresent) result.push('present');
    if (hasExam)    result.push('exam');
    if (hasEvent)   result.push('event');
    if (hasHoliday) result.push('holiday');
    return result;
}

/* ── Compute & update the monthly stats card ─────────────── */
function scalUpdateStats() {
    let present = 0, late = 0, absent = 0;
    Object.values(scalEventMap).forEach(evts => {
        evts.forEach(ev => {
            if ((ev.type || '').toLowerCase() === 'attendance') {
                const s = (ev.status || '').toLowerCase();
                if (s === 'present')      present++;
                else if (s === 'late')    late++;
                else if (s === 'absent')  absent++;
            }
        });
    });
    const total = present + late + absent;
    const rate  = total > 0 ? Math.round(((present + late) / total) * 100) : null;

    document.getElementById('scalStatsMonth').textContent =
        `${MONTH_NAMES[scalMonth-1]} ${scalYear} — Monthly Summary`;
    document.getElementById('scalStatPresent').textContent = present;
    document.getElementById('scalStatLate').textContent    = late;
    document.getElementById('scalStatAbsent').textContent  = absent;

    const ring = document.getElementById('scalRateRing');
    if (rate !== null) {
        const rateColor = rate >= 90 ? '#10b981' : rate >= 75 ? '#f59e0b' : '#ef4444';
        ring.textContent = rate + '%';
        ring.style.color      = rateColor;
        ring.style.background = `conic-gradient(${rateColor} ${rate}%, rgba(255,255,255,0.04) 0%)`;
        ring.style.boxShadow  = `0 0 0 4px ${rateColor}18, inset 0 0 0 3px rgba(0,0,0,0.4)`;
    } else {
        ring.textContent = '—';
        ring.style.color      = '#8b7d70';
        ring.style.background = 'rgba(255,255,255,0.04)';
        ring.style.boxShadow  = 'none';
    }
}

/* ── Fetch events for a month range ─────────────────────── */
function scalFetchEvents(year, month, cb) {
    const start   = `${year}-${String(month).padStart(2,'0')}-01`;
    const lastDay = new Date(year, month, 0).getDate();
    const end     = `${year}-${String(month).padStart(2,'0')}-${String(lastDay).padStart(2,'0')}`;
    const childParam = scalChildId ? `&child_id=${scalChildId}` : '';
    fetch(`{{ route('parent.calendar.data') }}?start=${start}&end=${end}${childParam}`)
        .then(r => r.json())
        .then(data => {
            Object.keys(scalEventMap).forEach(k => delete scalEventMap[k]);
            data.forEach(ev => {
                const dateStr = ev.start ? ev.start.split('T')[0] : null;
                if (!dateStr) return;
                if (!scalEventMap[dateStr]) scalEventMap[dateStr] = [];
                scalEventMap[dateStr].push(ev);
            });
            cb();
        })
        .catch(() => cb());
}

/* ── Derive dominant status for a day ───────────────────── */
function scalDominantStatus(dateStr) {
    const evts = scalEventMap[dateStr];
    if (!evts || evts.length === 0) return null;
    const statuses = new Set();
    evts.forEach(ev => {
        const t = (ev.type || '').toLowerCase();
        const s = (ev.status || '').toLowerCase();
        if (t === 'attendance') {
            if (s === 'absent')  statuses.add('absent');
            else if (s === 'late')    statuses.add('late');
            else if (s === 'present') statuses.add('present');
        } else if (t === 'exam')    statuses.add('exam');
        else if (t === 'holiday')   statuses.add('holiday');
        else                        statuses.add('event');
    });
    for (const p of STATUS_PRIORITY) { if (statuses.has(p)) return p; }
    return null;
}

/* ── Build & render the grid ────────────────────────────── */
const DOT_COLORS = {
    present : '#10b981',
    late    : '#f59e0b',
    absent  : '#ef4444',
    exam    : '#ec4899',
    event   : '#8b5cf6',
    holiday : '#4ade80',
};

function scalRender() {
    document.getElementById('scalMonthLabel').textContent =
        `${MONTH_NAMES[scalMonth-1]} ${scalYear}`;

    // Hide Today button if already on current month
    const now = new Date();
    const isCurrentMonth = (scalYear === now.getFullYear() && scalMonth === now.getMonth() + 1);
    document.getElementById('scalTodayBtn').style.opacity = isCurrentMonth ? '0' : '1';
    document.getElementById('scalTodayBtn').style.pointerEvents = isCurrentMonth ? 'none' : 'auto';

    const grid     = document.getElementById('scalGrid');
    const todayStr = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;

    const firstDay    = new Date(scalYear, scalMonth - 1, 1).getDay();
    const daysInMonth = new Date(scalYear, scalMonth, 0).getDate();

    let html = '';

    for (let i = 0; i < firstDay; i++) {
        html += '<div class="scal-tile empty"></div>';
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${scalYear}-${String(scalMonth).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const dow     = new Date(scalYear, scalMonth - 1, d).getDay();
        const isSun   = dow === 0;
        const isToday = dateStr === todayStr;
        const status  = scalDominantStatus(dateStr);
        const statuses = scalGetDayStatuses(dateStr);

        let classes = 'scal-tile';
        if (isSun)   classes += ' sunday';
        if (isToday) classes += ' today';
        if (status)  classes += ' ' + STATUS_CLASS[status];

        // Multi-dot row (up to 3 statuses)
        const dotsHtml = statuses.length > 0
            ? statuses.slice(0, 3).map(s =>
                `<span class="scal-mdot" style="background:${DOT_COLORS[s]|'#fff'};"></span>`
              ).join('')
            : '';

        html += `
        <div class="${classes}" data-date="${dateStr}" onclick="scalDayClick('${dateStr}')">
            <span class="scal-num">${d}</span>
            <div class="scal-dots-row">${dotsHtml}</div>
        </div>`;
    }

    grid.innerHTML = html;
    scalUpdateStats();
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

function scalLoadAndRender() {
    const grid = document.getElementById('scalGrid');
    grid.innerHTML = Array(35).fill('<div class="scal-tile loading"></div>').join('');
    scalFetchEvents(scalYear, scalMonth, () => scalRender());
    const url = new URL(window.location);
    url.searchParams.set('year',  scalYear);
    url.searchParams.set('month', scalMonth);
    window.history.pushState({}, '', url);
}

/* ── Day click → Day Summary Modal ─────────────────────── */
function scalDayClick(dateStr) {
    const evts = scalEventMap[dateStr] || [];
    const dateObj = new Date(dateStr + 'T00:00:00');
    document.getElementById('daySummaryTitle').textContent =
        dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

    const content = document.getElementById('daySummaryContent');
    content.innerHTML = '';

    let hasAbsent = false, hasPresent = false, hasLate = false, hasEvent = false;

    if (evts.length === 0) {
        document.getElementById('daySummarySubtitle').style.display = 'none';
        document.getElementById('daySummarySectionTitle').style.display = 'none';
        content.innerHTML = '<div style="text-align:center;color:#a0948a;font-size:0.95rem;padding:28px 0;">No activities recorded for this day.</div>';
    } else {
        document.getElementById('daySummarySectionTitle').style.display = 'block';

        evts.forEach(ev => {
            const t = (ev.type || '').toLowerCase();
            const s = (ev.status || '').toLowerCase();

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

                // Excused absences get a dashed border
                const excusedStyle = ev.excused ? 'border:2px dashed rgba(255,255,255,0.25);' : '';

                const studentNameHtml = (scalChildCount > 1 && ev.student_name)
                    ? `<div style="display:inline-flex;align-items:center;gap:4px;margin-top:3px;">
                          <i class="bi bi-person-fill" style="font-size:0.68rem;color:#cfa46f;"></i>
                          <span style="font-size:0.72rem;color:#cfa46f;font-weight:600;">${ev.student_name}</span>
                       </div>`
                    : '';

                content.insertAdjacentHTML('beforeend', `
                <div class="subject-card" style="${excusedStyle}">
                    <div class="subject-card-icon" style="background:${iconColor}20;color:${iconColor};">
                        <i class="bi ${iconCls}"></i>
                    </div>
                    <div class="subject-card-info">
                        <div class="subject-card-title">${ev.subject_name || ev.title}</div>
                        ${studentNameHtml}
                        <div class="subject-card-time" style="margin-top:2px;">${ev.time_string || 'Time not set'}</div>
                        <div class="subject-card-teacher">${ev.instructor_name || 'No Instructor'}${ev.excused ? ' &nbsp;<span style=\"font-size:0.7rem;color:#cfa46f;\">(Excused)</span>' : ''}</div>
                    </div>
                    <div class="subject-card-badge" style="background:${badge.bg};color:${badge.color};">${ev.status}</div>
                </div>`);
            } else {
                hasEvent = true;
                const evColor = ev.color || '#8b5cf6';
                content.insertAdjacentHTML('beforeend', `
                <div class="subject-card" style="border:1px dashed ${evColor}40;">
                    <div class="subject-card-icon" style="background:${evColor}20;color:${evColor};">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="subject-card-info">
                        <div class="subject-card-title">${ev.title}</div>
                        <div class="subject-card-time">${ev.time_string || 'All Day'}</div>
                        <div class="subject-card-teacher" style="text-transform:capitalize;">${(ev.type||'').replace('_',' ')}</div>
                    </div>
                </div>`);
            }
        });

        document.getElementById('daySummarySubtitle').style.display = 'flex';
        const dot = document.getElementById('daySummaryStatusDot');
        const txt = document.getElementById('daySummaryStatusText');
        if (hasAbsent)       { dot.style.background = '#ef4444'; txt.textContent = 'Absent'; }
        else if (hasLate)    { dot.style.background = '#f59e0b'; txt.textContent = 'Late'; }
        else if (hasPresent) { dot.style.background = '#10b981'; txt.textContent = 'Present'; }
        else if (hasEvent)   { dot.style.background = '#8b5cf6'; txt.textContent = 'School Event'; }
        else                 { dot.style.background = '#6b7280'; txt.textContent = 'No Status'; }
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('daySummaryModal')).show();
}

/* ── Init ───────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    document.body.appendChild(document.getElementById('eventDetailModal'));
    document.body.appendChild(document.getElementById('daySummaryModal'));
    scalLoadAndRender();

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
