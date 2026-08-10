@extends('layouts.app')

@section('page-title', 'Attendance Calendar')

@section('content')
<div class="ent-grid ent-grid-7-5">
    <!-- Main Calendar Panel -->
    <div class="ent-section" style="grid-column: span 1 / span 1;">
        <div class="ent-section-header">
            <div class="ent-section-title">
                <div class="ent-section-title-icon"><i class="bi bi-calendar-range"></i></div>
                Attendance Calendar
            </div>
            <div class="ent-dash-actions">
                <button type="button" onclick="previousMonth()" class="ent-btn ent-btn-secondary ent-btn-sm">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <span id="currentMonth" style="font-weight:700; color:var(--ent-text); min-width: 140px; text-align:center; display:inline-block;">
                    {{ \Carbon\Carbon::create($year ?? now()->year, $month ?? now()->month)->format('F Y') }}
                </span>
                <button type="button" onclick="nextMonth()" class="ent-btn ent-btn-secondary ent-btn-sm">
                    <i class="bi bi-chevron-right"></i>
                </button>
                <button type="button" onclick="goToToday()" class="ent-btn ent-btn-ghost ent-btn-sm ms-2">Today</button>
            </div>
        </div>
        <div class="ent-section-body no-pad">
            <div style="padding: 16px; overflow-x: auto;">
                <div id="calendar" style="min-height: 550px; min-width: 600px;"></div>
            </div>
        </div>
    </div>

    <!-- Side Panel -->
    <div style="display:flex; flex-direction:column; gap: var(--ent-space-md);">
        <!-- Legend -->
        <div class="ent-section">
            <div class="ent-section-header">
                <div class="ent-section-title">
                    <div class="ent-section-title-icon"><i class="bi bi-list-stars"></i></div>
                    Legend
                </div>
            </div>
            <div class="ent-section-body">
                <div class="ent-grid ent-grid-2" style="gap: 12px;">
                    <div class="d-flex align-items-center gap-2" style="font-size:0.85rem; color:var(--ent-text);">
                        <span style="width:12px; height:12px; border-radius:3px; background:#10b981; display:inline-block; box-shadow:0 0 10px rgba(16,185,129,0.3);"></span> Present
                    </div>
                    <div class="d-flex align-items-center gap-2" style="font-size:0.85rem; color:var(--ent-text);">
                        <span style="width:12px; height:12px; border-radius:3px; background:#f59e0b; display:inline-block; box-shadow:0 0 10px rgba(245,158,11,0.3);"></span> Late
                    </div>
                    <div class="d-flex align-items-center gap-2" style="font-size:0.85rem; color:var(--ent-text);">
                        <span style="width:12px; height:12px; border-radius:3px; background:#ef4444; display:inline-block; box-shadow:0 0 10px rgba(239,68,68,0.3);"></span> Absent
                    </div>
                    <div class="d-flex align-items-center gap-2" style="font-size:0.85rem; color:var(--ent-text);">
                        <span style="width:12px; height:12px; border-radius:3px; background:#ec4899; display:inline-block; box-shadow:0 0 10px rgba(236,72,153,0.3);"></span> Exam
                    </div>
                    <div class="d-flex align-items-center gap-2" style="font-size:0.85rem; color:var(--ent-text);">
                        <span style="width:12px; height:12px; border-radius:3px; background:#8b5cf6; display:inline-block; box-shadow:0 0 10px rgba(139,92,246,0.3);"></span> Event
                    </div>
                    <div class="d-flex align-items-center gap-2" style="font-size:0.85rem; color:var(--ent-text);">
                        <span style="width:12px; height:12px; border-radius:3px; background:#4ade80; display:inline-block; box-shadow:0 0 10px rgba(74,222,128,0.3);"></span> Holiday
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Summary -->
        <div class="ent-section">
            <div class="ent-section-header">
                <div class="ent-section-title">
                    <div class="ent-section-title-icon"><i class="bi bi-info-circle"></i></div>
                    Calendar Tips
                </div>
            </div>
            <div class="ent-section-body">
                <ul style="margin:0; padding-left:20px; font-size:0.85rem; color:var(--ent-text-muted); line-height:1.6;">
                    <li>Click on any specific <strong>Event</strong> chip in the calendar to view full details including location and exact time.</li>
                    <li>Click on any <strong>Day number</strong> to open a summary card for that day's scheduled subjects and recorded attendance.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
/* FullCalendar Overrides for Enterprise Design */
.fc {
    color: var(--ent-text-secondary);
    font-family: 'Inter', sans-serif;
}
.fc-theme-standard td, .fc-theme-standard th, .fc-theme-standard .fc-scrollgrid {
    border-color: rgba(255,255,255,0.06) !important;
}
.fc .fc-col-header-cell {
    background: rgba(0,0,0,0.2) !important;
    padding: 12px 0;
}
.fc .fc-col-header-cell-cushion {
    color: var(--ent-text-muted);
    text-transform: uppercase;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-decoration: none !important;
}
.fc .fc-daygrid-day-number {
    color: var(--ent-text);
    font-weight: 600;
    font-size: 0.9rem;
    padding: 10px !important;
    text-decoration: none !important;
}
.fc .fc-day-other .fc-daygrid-day-number {
    color: var(--ent-text-muted);
    opacity: 0.4;
}
.fc a {
    text-decoration: none !important;
}
.fc .fc-day-today {
    background: rgba(207, 164, 111, 0.03) !important;
    box-shadow: inset 0 0 0 1px rgba(207, 164, 111, 0.2) !important;
}
.fc .fc-daygrid-event {
    border: none !important;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3) !important;
    border-radius: var(--ent-radius-xs) !important;
    padding: 2px 6px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #ffffff !important;
    text-shadow: 0 1px 1px rgba(0,0,0,0.3);
    margin: 2px 4px !important;
    transition: var(--ent-transition);
}
.fc .fc-daygrid-event:hover {
    transform: translateY(-1px) scale(1.02);
    box-shadow: 0 4px 12px rgba(0,0,0,0.4) !important;
    filter: brightness(1.15);
    z-index: 5;
}
.fc .fc-daygrid-day-frame {
    transition: background var(--ent-transition-fast);
}
.fc .fc-daygrid-day-frame:hover {
    background: rgba(255,255,255,0.02);
}
/* Force Saturday/Sunday displays */
.fc-day-sat, th.fc-day-sat, td.fc-day-sat { display: table-cell !important; }
.fc-day-sun, th.fc-day-sun, td.fc-day-sun { display: table-cell !important; }
.fc-scrollgrid th:last-child, .fc-scrollgrid td:last-child { display: table-cell !important; }
.fc-scrollgrid th:nth-child(7), .fc-scrollgrid td:nth-child(7) { display: table-cell !important; }

/* Ensure calendar table shows all 7 columns */
.fc .fc-scrollgrid-sync-table { width: 100% !important; min-width: 100% !important; }
.fc-col-header { display: table !important; width: 100% !important; }
.fc-daygrid-body { width: 100% !important; }
.fc-scrollgrid { width: 100% !important; }

/* Mobile Responsive Enhancements */
@media (max-width: 768px) {
    /* Remove problematic grid on mobile */
    .ent-grid-7-5 {
        display: block !important;
    }
    
    /* Mobile Page Layout */
    .ent-section {
        margin: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }
    
    /* Mobile Calendar Header - Premium Glassmorphism */
    .ent-section-header {
        position: sticky !important;
        top: 0 !important;
        z-index: 100 !important;
        flex-direction: row !important;
        padding: 16px 20px !important;
        gap: 12px !important;
        align-items: center !important;
        background: rgba(18, 18, 24, 0.75) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 0 0 24px 24px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important;
        margin-bottom: 16px !important;
    }
    
    .ent-section-title {
        display: none !important; /* Hide title to save space */
    }
    
    /* Month Navigation - Horizontal Row */
    .ent-dash-actions {
        width: 100% !important;
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        justify-content: space-between !important;
        align-items: center !important;
        gap: 8px !important;
        margin: 0 !important;
    }
    
    /* Navigation buttons */
    .ent-dash-actions .ent-btn-secondary {
        width: 42px !important;
        max-width: 42px !important;
        min-width: 42px !important;
        height: 42px !important;
        padding: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: rgba(207, 164, 111, 0.15) !important;
        border: 1px solid rgba(207, 164, 111, 0.3) !important;
        color: var(--ent-gold) !important;
        border-radius: 14px !important;
        font-size: 1.2rem !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        flex-shrink: 0 !important;
        box-shadow: 0 4px 12px rgba(207, 164, 111, 0.15) !important;
    }
    
    .ent-dash-actions .ent-btn-secondary:active {
        transform: scale(0.92) !important;
        background: rgba(207, 164, 111, 0.25) !important;
        box-shadow: 0 2px 8px rgba(207, 164, 111, 0.1) !important;
    }
    
    /* Current month display */
    #currentMonth {
        flex: 1 !important;
        text-align: center !important;
        font-size: 1.15rem !important;
        font-weight: 800 !important;
        color: var(--ent-text) !important;
        min-width: auto !important;
        letter-spacing: 0.5px !important;
        white-space: nowrap !important;
    }
    
    /* Today button - Hidden on mobile */
    .ent-dash-actions .ent-btn-ghost {
        display: none !important;
    }
    
    /* Legend Inline Container */
    .ent-grid-7-5 > div:last-child {
        position: relative !important;
        bottom: auto !important;
        right: auto !important;
        z-index: 1 !important;
        display: block !important;
        width: 100% !important;
        padding-bottom: 90px !important; /* Leave room for mobile bottom nav */
    }
    
    .ent-grid-7-5 > div:last-child > div:first-child {
        box-shadow: none !important;
        border-radius: var(--ent-radius-md) !important;
        max-width: none !important;
    }
    
    /* Calendar Tips Mobile Styling */
    .ent-grid-7-5 > div:last-child > div:last-child {
        display: block !important;
        background: linear-gradient(145deg, rgba(207, 164, 111, 0.08), rgba(207, 164, 111, 0.02)) !important;
        border: 1px solid rgba(207, 164, 111, 0.15) !important;
        border-radius: 16px !important;
        margin-top: 24px !important;
        padding: 16px !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2) !important;
    }
    
    .ent-grid-7-5 > div:last-child > div:last-child .ent-section-header {
        background: transparent !important;
        border: none !important;
        padding: 0 0 12px 0 !important;
        position: relative !important;
        backdrop-filter: none !important;
    }
    
    .ent-grid-7-5 > div:last-child > div:last-child .ent-section-title {
        display: flex !important;
        color: var(--ent-gold) !important;
        font-size: 0.95rem !important;
        font-weight: 700 !important;
        align-items: center !important;
        gap: 8px !important;
    }
    
    .ent-grid-7-5 > div:last-child > div:last-child .ent-section-body {
        background: transparent !important;
    }
    
    /* Compact legend styling - Premium Pills */
    .ent-grid-2 {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
        gap: 10px !important;
    }
    
    .ent-grid-2 > div {
        font-size: 0.75rem !important;
        background: rgba(255, 255, 255, 0.03) !important;
        padding: 6px 12px !important;
        border-radius: 20px !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
    }
    
    /* Calendar Container - Full Width, No Scroll */
    .ent-section-body {
        padding: 0 !important;
        background: var(--ent-bg) !important;
    }
    
    .ent-section-body.no-pad > div,
    .ent-section-body > div {
        padding: 8px !important;
        overflow-x: auto !important;
        overflow-y: hidden !important;
        -webkit-overflow-scrolling: touch !important;
    }
    
    /* Calendar itself - Fit perfectly to screen */
    #calendar {
        min-width: 100% !important;
        width: 100% !important;
        min-height: auto !important;
    }
    
    /* FullCalendar Mobile Optimizations */
    .fc {
        font-size: 0.75rem !important;
        background: var(--ent-surface) !important;
        border-radius: 12px !important;
        overflow: hidden !important;
        border: 1px solid var(--ent-border) !important;
        box-shadow: 0 2px 12px rgba(0,0,0,0.2) !important;
    }
    
    /* Day headers - Clean & Elegant */
    .fc th, 
    .fc-theme-standard th,
    .fc .fc-col-header,
    .fc .fc-scrollgrid-sync-inner,
    .fc .fc-col-header-cell {
        padding: 12px 2px !important;
        background: transparent !important;
        border: none !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
    }
    
    .fc .fc-col-header-cell-cushion {
        font-size: 0.65rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.1em !important;
        color: rgba(207, 164, 111, 0.8) !important;
        text-transform: uppercase !important;
        padding: 0 !important;
    }
    
    /* Day cells - Seamless Grid */
    .fc .fc-daygrid-day {
        min-height: 60px !important;
        height: 60px !important;
        background: transparent !important;
        border: none !important;
        position: relative !important;
    }
    
    .fc .fc-daygrid-day-frame {
        min-height: 60px !important;
        height: 60px !important;
        padding: 4px 2px !important;
        position: relative !important;
        display: flex !important;
        flex-direction: column !important;
        border-bottom: 1px solid rgba(255,255,255,0.03) !important;
        border-right: 1px solid rgba(255,255,255,0.01) !important;
    }
    
    .fc .fc-daygrid-day-number {
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        padding: 4px !important;
        color: var(--ent-text) !important;
        width: 100% !important;
        text-align: center !important;
        display: block !important;
    }
    
    /* Today cell */
    .fc .fc-day-today {
        background: transparent !important;
        box-shadow: none !important;
    }
    
    .fc .fc-day-today .fc-daygrid-day-number {
        color: var(--ent-bg) !important;
        font-weight: 800 !important;
        background: var(--ent-gold) !important;
        border-radius: 50% !important;
        width: 32px !important;
        height: 32px !important;
        line-height: 32px !important;
        padding: 0 !important;
        margin: 3px auto !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 4px 12px rgba(207, 164, 111, 0.5) !important;
    }
    
    /* Events - Premium dots instead of text to save space */
    .fc .fc-daygrid-day-events {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
        gap: 3px !important;
        margin-top: 4px !important;
        min-height: 0 !important;
        padding: 0 4px !important;
    }
    
    .fc .fc-daygrid-event-harness {
        margin: 0 !important;
    }
    
    .fc .fc-daygrid-event {
        width: 6px !important;
        height: 6px !important;
        border-radius: 50% !important;
        margin: 0 !important;
        padding: 0 !important;
        min-height: 0 !important;
        border: none !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.4) !important;
        cursor: pointer !important;
    }
    
    .fc .fc-event-title, .fc .fc-event-time {
        display: none !important;
    }
    
    /* Center Modals on Mobile */
    .modal-dialog-centered,
    .modal-dialog-bottom {
        margin: 1.75rem auto !important;
        position: relative !important;
        bottom: auto !important;
        left: auto !important;
        right: auto !important;
        width: 90% !important;
        max-width: 400px !important;
        height: auto !important;
        display: flex !important;
        align-items: center !important;
        min-height: calc(100% - 3.5rem) !important;
    }
    
    .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.6) !important;
        backdrop-filter: blur(4px) !important;
    }
    
    .ent-modal-content {
        border-radius: 24px !important;
        background: rgba(22, 22, 28, 0.95) !important;
        backdrop-filter: blur(24px) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        margin: 0 auto !important;
        width: 100% !important;
        max-height: 85vh !important;
        transform-origin: center !important;
        position: relative !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5) !important;
        overflow: hidden !important;
    }
    
    .ent-modal-header {
        padding: 24px 24px 16px !important;
        border-radius: 0 !important;
        background: transparent !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        position: relative !important;
    }
    
    .ent-modal-header::before {
        content: '';
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        width: 48px;
        height: 5px;
        background: rgba(255,255,255,0.2);
        border-radius: 3px;
    }
    
    .ent-modal-title {
        font-size: 1.2rem !important;
        margin-top: 12px !important;
        line-height: 1.3 !important;
    }
    
    .ent-modal-body {
        padding: 20px 24px 32px !important;
        max-height: calc(80vh - 100px) !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }
    
    .ent-modal-row {
        margin-bottom: 20px !important;
        padding-bottom: 20px !important;
    }
    
    .ent-modal-label {
        font-size: 0.75rem !important;
        margin-bottom: 8px !important;
    }
    
    .ent-modal-value {
        font-size: 1rem !important;
    }
    
    .btn-close {
        width: 36px !important;
        height: 36px !important;
        background-size: 16px !important;
        opacity: 0.7 !important;
    }
    
    /* (Replaced by shared modal rules above) */
    
    .bottom-sheet-content {
        border-radius: 24px !important;
        background: rgba(22, 22, 28, 0.95) !important;
        backdrop-filter: blur(24px) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        max-height: 80vh !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5) !important;
        width: 100% !important;
        overflow-x: hidden !important;
    }
    
    .bottom-sheet-handle {
        margin: 16px auto 12px !important;
        width: 60px !important;
        height: 6px !important;
        border-radius: 4px !important;
        background: rgba(255, 255, 255, 0.15) !important;
    }
    
    /* Subject cards in day summary - Ultra Premium Glass */
    .subject-card {
        padding: 18px 16px !important;
        gap: 16px !important;
        border-radius: 20px !important;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02)) !important;
        backdrop-filter: blur(10px) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15) !important;
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
    }
    
    .subject-card:active {
        transform: scale(0.97) !important;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.04)) !important;
    }
    
    .subject-card-icon {
        width: 48px !important;
        height: 48px !important;
        font-size: 1.4rem !important;
        flex-shrink: 0 !important;
    }
    
    .subject-card-title {
        font-size: 1.05rem !important;
        line-height: 1.3 !important;
        font-weight: 700 !important;
    }
    
    .subject-card-time,
    .subject-card-teacher {
        font-size: 0.85rem !important;
        line-height: 1.5 !important;
    }
    
    .subject-card-badge {
        font-size: 0.75rem !important;
        padding: 6px 12px !important;
        white-space: nowrap !important;
        border-radius: 8px !important;
        font-weight: 700 !important;
    }
    
    /* Fix scrolling issues */
    body {
        overflow-x: hidden !important;
    }
    
    /* Improve tap highlighting */
    .fc .fc-daygrid-day {
        -webkit-tap-highlight-color: rgba(207, 164, 111, 0.15);
        cursor: pointer;
    }
    
    /* Loading state */
    .fc .fc-view-harness {
        min-height: 400px;
    }
    
    /* Swipe hint for month navigation */
    .swipe-hint {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: rgba(255, 255, 255, 0.7);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        z-index: 98;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s;
        backdrop-filter: blur(10px);
    }
    
    .swipe-hint.show {
        opacity: 1;
    }
}

/* Custom Modals tailored to Enterprise Theme */
.ent-modal-content {
    background: var(--ent-bg);
    border: 1px solid var(--ent-border);
    border-radius: var(--ent-radius-xl);
    box-shadow: 0 32px 80px rgba(0,0,0,0.6), inset 0 1px 0 rgba(255,255,255,0.05);
}
.ent-modal-header {
    border-bottom: 1px solid rgba(255,255,255,0.06);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(0,0,0,0.2);
    border-radius: var(--ent-radius-xl) var(--ent-radius-xl) 0 0;
}
.ent-modal-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--ent-text);
    margin: 0;
}
.ent-modal-body {
    padding: 24px;
}
.ent-modal-row {
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.ent-modal-row:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
.ent-modal-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
    color: var(--ent-text-muted);
    margin-bottom: 6px;
}
.ent-modal-value {
    font-size: 0.95rem;
    color: var(--ent-text);
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

/* Bottom Sheet specifically for mobile-feel */
.modal-dialog-bottom {
    display: flex;
    align-items: flex-end;
    min-height: 100%;
    margin: 0;
    padding: 0;
}
@media (min-width: 576px) {
    .modal-dialog-bottom {
        align-items: center;
        margin: 1.75rem auto;
        max-width: 500px;
        min-height: calc(100% - 3.5rem);
    }
}
.bottom-sheet-content {
    background: var(--ent-surface);
    border: 1px solid var(--ent-border);
    border-radius: var(--ent-radius-2xl) var(--ent-radius-2xl) 0 0;
    width: 100%;
    padding-bottom: env(safe-area-inset-bottom);
    box-shadow: 0 -10px 40px rgba(0,0,0,0.5);
}
@media (min-width: 576px) {
    .bottom-sheet-content { border-radius: var(--ent-radius-xl); }
}
.bottom-sheet-handle {
    width: 40px;
    height: 4px;
    background: rgba(255,255,255,0.15);
    border-radius: 2px;
    margin: 12px auto;
}

/* Subject Cards for Day Summary */
.subject-card {
    background: rgba(255, 255, 255, 0.02);
    border-radius: var(--ent-radius-md);
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid rgba(255,255,255,0.03);
    transition: var(--ent-transition);
}
.subject-card:hover {
    background: rgba(255, 255, 255, 0.04);
}
.subject-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.subject-card-info {
    flex: 1;
}
.subject-card-title {
    font-weight: 600;
    font-size: 1.05rem;
    color: var(--ent-text);
    margin-bottom: 2px;
}
.subject-card-time {
    font-size: 0.85rem;
    color: var(--ent-text-muted);
    margin-bottom: 2px;
}
.subject-card-teacher {
    font-size: 0.85rem;
    color: var(--ent-text-secondary);
}
.subject-card-badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
}
</style>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ent-modal-content w-100">
            <div class="ent-modal-header position-relative">
                <h5 class="ent-modal-title" id="eventTitle" style="padding-right: 24px;">Event Title</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="position: absolute; top: 20px; right: 20px;"></button>
            </div>
            <div class="ent-modal-body">
                <div class="ent-modal-row">
                    <div class="ent-modal-label">Event Type</div>
                    <div class="ent-modal-value">
                        <span id="eventTypeBadge" class="ent-badge" style="font-size:0.75rem; padding: 4px 10px; border-radius:4px; box-shadow:0 2px 5px rgba(0,0,0,0.3);">Type</span>
                    </div>
                </div>
                <div class="ent-modal-row">
                    <div class="ent-modal-label">Date & Time</div>
                    <div class="ent-modal-value">
                        <i class="bi bi-clock" style="color: var(--ent-gold);"></i>
                        <span id="eventTime">Date</span>
                    </div>
                </div>
                <div class="ent-modal-row" id="eventLocationContainer">
                    <div class="ent-modal-label">Location</div>
                    <div class="ent-modal-value">
                        <i class="bi bi-geo-alt" style="color: var(--ent-gold);"></i>
                        <span id="eventLocation">Location</span>
                    </div>
                </div>                <div class="ent-modal-row" id="eventDescriptionContainer" style="display:none;">
                    <div class="ent-modal-label">Description</div>
                    <div class="ent-modal-value" style="font-size: 0.95rem; font-weight: normal; color: var(--ent-text-secondary);" id="eventDescription">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Day Summary Modal -->
<div class="modal fade" id="daySummaryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-bottom modal-dialog-scrollable">
        <div class="modal-content bottom-sheet-content">
            <div class="bottom-sheet-handle d-none"></div>
            <div class="d-flex justify-content-between align-items-start px-4 pt-4 pb-3 border-bottom position-relative" style="border-color: rgba(255,255,255,0.06)!important;">
                <div>
                    <h3 style="font-weight:700; font-size:1.25rem; color:var(--ent-text); margin:0; padding-right: 24px;" id="daySummaryTitle">Date</h3>
                    <div style="font-size:0.85rem; color:var(--ent-text-muted); display:flex; align-items:center; gap:6px; margin-top:4px;" id="daySummarySubtitle">
                        <span id="daySummaryStatusDot" style="width:8px; height:8px; border-radius:50%; display:inline-block;"></span>
                        <span id="daySummaryStatusText">Status</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="position: absolute; top: 20px; right: 20px;"></button>
            </div>
            
            <div class="px-4 pb-4">
                <h6 style="font-size:0.85rem; font-weight:600; color:var(--ent-text-secondary); text-transform:uppercase; letter-spacing:0.05em; margin: 20px 0 12px 0;" id="daySummarySectionTitle">Subjects</h6>
                <div id="daySummaryContent" class="d-flex flex-column gap-2">
                    <!-- Dynamically populated -->
                </div>
                
                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.6); text-align: center; margin-top: 20px;">
                    <i class="bi bi-info-circle"></i> All subjects for the day are shown above.
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script>
let calendar;
let currentYear = {{ $year ?? now()->year }};
let currentMonth = {{ $month ?? now()->month }};
let activeEvent = null;

document.addEventListener('DOMContentLoaded', function() {
    // Append modals to body to avoid stacking context issues
    document.body.appendChild(document.getElementById('eventDetailModal'));
    document.body.appendChild(document.getElementById('daySummaryModal'));

    const calendarEl = document.getElementById('calendar');
    
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        initialDate: new Date(currentYear, currentMonth - 1, 1),
        headerToolbar: false,
        height: 'auto',
        firstDay: 0,
        weekends: true,
        hiddenDays: [],
        dayHeaders: true,
        dayHeaderFormat: { weekday: 'short' },
        showNonCurrentDates: true,
        fixedWeekCount: false,
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch(`{{ route('calendar.data') }}?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
                .then(response => response.json())
                .then(data => {
                    const events = data.map(event => ({
                        id: event.id,
                        title: event.title,
                        start: event.start,
                        end: event.end,
                        backgroundColor: event.color,
                        borderColor: event.color,
                        extendedProps: {
                            type: event.type,
                            location: event.location,
                            description: event.description,
                            excused: event.excused,
                            status: event.status,
                            subject_name: event.subject_name,
                            instructor_name: event.instructor_name,
                            time_string: event.time_string
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
            if (window.innerWidth <= 768 && 'vibrate' in navigator) navigator.vibrate(10);
            activeEvent = info.event;
            const props = info.event.extendedProps;
            
            document.getElementById('eventTitle').textContent = info.event.title;
            
            const badge = document.getElementById('eventTypeBadge');
            badge.textContent = props.type ? props.type.replace('_', ' ') : 'Event';
            badge.style.backgroundColor = info.event.backgroundColor;
            
            const start = info.event.start;
            const end = info.event.end;
            
            let timeStr = start.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });
            if (props.type !== 'holiday' && !info.event.allDay) {
                timeStr += ` â€¢ ${start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
                if (end) {
                    timeStr += ` - ${end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
                }
            }
            document.getElementById('eventTime').textContent = timeStr;
            
            if (props.location) {
                document.getElementById('eventLocation').textContent = props.location;
                document.getElementById('eventLocationContainer').style.display = 'block';
            } else {
                document.getElementById('eventLocationContainer').style.display = 'none';
            }
            
            if (props.description) {
                document.getElementById('eventDescription').textContent = props.description;
                document.getElementById('eventDescriptionContainer').style.display = 'block';
            } else {
                document.getElementById('eventDescriptionContainer').style.display = 'none';
            }
            
            bootstrap.Modal.getOrCreateInstance(document.getElementById('eventDetailModal')).show();
        },
        dateClick: function(info) {
            if (window.innerWidth <= 768 && 'vibrate' in navigator) navigator.vibrate(10);
            const clickedDateStr = info.dateStr;
            const allEvents = calendar.getEvents();
            
            // Filter events that fall on the clicked date
            const dayEvents = allEvents.filter(event => {
                if (!event.start) return false;
                const eventDate = new Date(event.start.getTime() - (event.start.getTimezoneOffset() * 60000))
                                    .toISOString().split('T')[0];
                return eventDate === clickedDateStr;
            });
            
            // Format title
            const clickedDateObj = new Date(clickedDateStr + 'T00:00:00');
            document.getElementById('daySummaryTitle').textContent = clickedDateObj.toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' });
            
            const contentContainer = document.getElementById('daySummaryContent');
            contentContainer.innerHTML = '';
            
            let hasAbsent = false;
            let hasPresent = false;
            let hasLate = false;
            let hasEvent = false;
            
            if (dayEvents.length === 0) {
                document.getElementById('daySummarySubtitle').style.display = 'none';
                document.getElementById('daySummarySectionTitle').style.display = 'none';
                contentContainer.innerHTML = '<div style="text-align:center; color: #cbd5e1; font-size: 1.05rem; font-weight: 500; padding: 30px 0;">No activities recorded for this day.</div>';
            } else {
                document.getElementById('daySummarySectionTitle').style.display = 'block';
                
                dayEvents.forEach(event => {
                    const props = event.extendedProps;
                    
                    if (props.type === 'attendance') {
                        if (props.status === 'Absent') hasAbsent = true;
                        if (props.status === 'Present') hasPresent = true;
                        if (props.status === 'Late') hasLate = true;
                        
                        // Icon coloring logic
                        const colors = ['#8b5cf6', '#10b981', '#f59e0b', '#3b82f6', '#ec4899'];
                        // Use a simple hash of the subject name to pick a consistent color
                        let hash = 0;
                        const subjectNameStr = props.subject_name || event.title;
                        for (let i = 0; i < subjectNameStr.length; i++) hash = subjectNameStr.charCodeAt(i) + ((hash << 5) - hash);
                        const iconColor = colors[Math.abs(hash) % colors.length];
                        const bgIconColor = iconColor + '20'; // 20% opacity
                        
                        // Generic icon based on subject name keywords
                        let iconClass = 'bi-journal-bookmark';
                        const lowerName = subjectNameStr.toLowerCase();
                        if (lowerName.includes('code') || lowerName.includes('web') || lowerName.includes('prog')) iconClass = 'bi-code-slash';
                        else if (lowerName.includes('math') || lowerName.includes('discrete')) iconClass = 'bi-bar-chart-fill';
                        else if (lowerName.includes('science') || lowerName.includes('physics')) iconClass = 'bi-lightbulb';
                        
                        let badgeColor = 'rgba(255,255,255,0.1)';
                        let badgeTextColor = '#ffffff';
                        if (props.status === 'Absent') {
                            badgeColor = 'rgba(239, 68, 68, 0.2)';
                            badgeTextColor = '#ef4444';
                        } else if (props.status === 'Present') {
                            badgeColor = 'rgba(16, 185, 129, 0.2)';
                            badgeTextColor = '#10b981';
                        } else if (props.status === 'Late') {
                            badgeColor = 'rgba(245, 158, 11, 0.2)';
                            badgeTextColor = '#f59e0b';
                        }

                        let dashedStyle = props.excused ? 'border: 2px dashed rgba(255,255,255,0.3);' : '';

                        let html = `
                            <div class="subject-card" style="${dashedStyle}">
                                <div class="subject-card-icon" style="background-color: ${bgIconColor}; color: ${iconColor};">
                                    <i class="bi ${iconClass}"></i>
                                </div>
                                <div class="subject-card-info">
                                    <div class="subject-card-title">${props.subject_name || event.title}</div>
                                    <div class="subject-card-time">${props.time_string || 'Time not set'}</div>
                                    <div class="subject-card-teacher">${props.instructor_name || 'No Instructor'}</div>
                                </div>
                                <div class="subject-card-badge" style="background-color: ${badgeColor}; color: ${badgeTextColor};">
                                    ${props.status}
                                </div>
                            </div>
                        `;
                        contentContainer.insertAdjacentHTML('beforeend', html);
                    } else {
                        hasEvent = true;
                        let timeStr = '';
                        if (!event.allDay) {
                            timeStr = event.start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            if (event.end) {
                                timeStr += ` - ${event.end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`;
                            }
                        }
                        
                        let html = `
                            <div class="subject-card" style="border: 1px dashed ${event.backgroundColor};">
                                <div class="subject-card-icon" style="background-color: ${event.backgroundColor}20; color: ${event.backgroundColor};">
                                    <i class="bi bi-calendar-event"></i>
                                </div>
                                <div class="subject-card-info">
                                    <div class="subject-card-title">${event.title}</div>
                                    <div class="subject-card-time">${timeStr || 'All Day'}</div>
                                    <div class="subject-card-teacher" style="text-transform: capitalize;">${props.type ? props.type.replace('_', ' ') : 'Event'}</div>
                                </div>
                            </div>
                        `;
                        contentContainer.insertAdjacentHTML('beforeend', html);
                    }
                });
                
                // Set overall subtitle status
                document.getElementById('daySummarySubtitle').style.display = 'flex';
                const statusDot = document.getElementById('daySummaryStatusDot');
                const statusText = document.getElementById('daySummaryStatusText');
                
                if (hasAbsent) {
                    statusDot.style.backgroundColor = '#ef4444';
                    statusText.textContent = 'Absent';
                } else if (hasLate) {
                    statusDot.style.backgroundColor = '#f59e0b';
                    statusText.textContent = 'Late';
                } else if (hasPresent) {
                    statusDot.style.backgroundColor = '#10b981';
                    statusText.textContent = 'Present';
                } else if (hasEvent) {
                    statusDot.style.backgroundColor = '#8b5cf6';
                    statusText.textContent = 'School Event';
                } else {
                    statusDot.style.backgroundColor = '#6b7280';
                    statusText.textContent = 'No Status';
                }
            }
            
            bootstrap.Modal.getOrCreateInstance(document.getElementById('daySummaryModal')).show();
        },
        eventDidMount: function(info) {
            if (info.event.extendedProps.excused) {
                info.el.style.border = '2px dashed #fff';
            }
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
    
    // Mobile enhancements
    if (window.innerWidth <= 768) {
        // Swipe gesture support for month navigation (improved)
        let touchStartX = 0;
        let touchStartY = 0;
        let touchEndX = 0;
        let touchEndY = 0;
        const calendarEl = document.getElementById('calendar');
        
        calendarEl.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });
        
        calendarEl.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            touchEndY = e.changedTouches[0].screenY;
            handleSwipeGesture();
        }, { passive: true });
        
        function handleSwipeGesture() {
            const swipeThreshold = 80;
            const diffX = touchStartX - touchEndX;
            const diffY = Math.abs(touchStartY - touchEndY);
            
            // Only trigger if horizontal swipe is more dominant than vertical
            if (Math.abs(diffX) > swipeThreshold && diffY < swipeThreshold) {
                if (diffX > 0) {
                    // Swiped left - next month
                    nextMonth();
                    showSwipeHint('→ Next Month');
                } else {
                    // Swiped right - previous month
                    previousMonth();
                    showSwipeHint('← Previous Month');
                }
            }
        }
        
        // Show swipe hint temporarily
        function showSwipeHint(text) {
            let hint = document.querySelector('.swipe-hint');
            if (!hint) {
                hint = document.createElement('div');
                hint.className = 'swipe-hint';
                document.body.appendChild(hint);
            }
            hint.textContent = text;
            hint.classList.add('show');
            
            setTimeout(() => {
                hint.classList.remove('show');
            }, 800);
        }
        
        // Add haptic feedback for interactions (if supported) is now handled directly inside event handlers
    }
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
    calendar.gotoDate(new Date(currentYear, currentMonth - 1, 1));
    calendar.refetchEvents();
    
    const url = new URL(window.location);
    url.searchParams.set('year', currentYear);
    url.searchParams.set('month', currentMonth);
    window.history.pushState({}, '', url);
}
</script>
</div>
@endsection

