@extends('layouts.app')

@section('page-title', 'School Calendar')

@section('content')
<div id="schoolCalendarPage" class="holiday-dashboard">
<style>
    #schoolCalendarPage { padding-bottom: 18px; }
    .holiday-dashboard .glass-card {
        background: rgba(26, 26, 46, 0.5);
        border: 1px solid rgba(255,255,255,0.08);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        border-radius: 28px;
    }
    .holiday-dashboard .glass-card .adm-card-head {
        padding: 24px 32px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .holiday-dashboard .glass-card .adm-card-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: #f3e7cd;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .holiday-dashboard .glass-card .adm-card-icon {
        background: rgba(207, 164, 111, 0.1);
        color: #cfa46f;
        box-shadow: inset 0 0 0 1px rgba(207, 164, 111, 0.2);
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.2rem;
    }
    .holiday-dashboard .calendar-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        justify-content: flex-end;
    }
    .holiday-dashboard .calendar-header {
        font-weight: 700;
        color: #f3e7cd;
        min-width: 168px;
        text-align: center;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
    }
    .holiday-dashboard .adm-btn-ghost {
        background: rgba(255,255,255,0.04);
        color: #b39b82;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
        padding: 8px 12px;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .holiday-dashboard .adm-btn-ghost:hover {
        background: rgba(255,255,255,0.08);
        color: #cfa46f;
        border-color: rgba(207, 164, 111, 0.3);
        transform: translateY(-1px);
    }
    .holiday-dashboard .adm-btn-primary {
        background: linear-gradient(135deg, #d4a574, #cfa46f);
        color: #1a1a2e;
        border: none;
        border-radius: 10px;
        padding: 8px 20px;
        font-weight: 700;
        box-shadow: 0 8px 24px rgba(207, 164, 111, 0.2);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .holiday-dashboard .adm-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(207, 164, 111, 0.3);
    }
    
    /* FullCalendar Overrides - Dark Squircle Theme */
    .fc {
        color: #f3ede4;
        font-family: inherit;
    }
    .fc-theme-standard td, .fc-theme-standard th, .fc-theme-standard .fc-scrollgrid {
        border-color: rgba(255,255,255,0.04) !important;
    }
    .fc .fc-col-header-cell {
        background: rgba(255,255,255,0.025) !important;
        padding: 14px 0;
        border: none !important;
    }
    .fc .fc-col-header-cell-cushion {
        color: #cfa46f;
        text-transform: uppercase;
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-decoration: none !important;
    }
    .fc .fc-daygrid-day-frame {
        border-radius: 12px !important;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.03) !important;
        margin: 3px !important;
        min-height: 95px !important;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .fc .fc-daygrid-day-frame:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(207, 164, 111, 0.3) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4);
        z-index: 2;
    }
    .fc .fc-daygrid-day-number {
        color: #f3ede4;
        font-weight: 700;
        font-size: 1.05rem;
        padding: 8px 12px !important;
        text-decoration: none !important;
    }
    .fc .fc-day-sun .fc-daygrid-day-number {
        color: #f87171 !important;
    }
    .fc .fc-day-other .fc-daygrid-day-number {
        color: #5c4e40;
        opacity: 0.5;
    }
    .fc a {
        text-decoration: none !important;
    }
    .fc .fc-day-today .fc-daygrid-day-frame {
        background: rgba(255, 209, 102, 0.08) !important;
        border: 2px solid #ffd166 !important;
        box-shadow: 0 0 20px rgba(255, 209, 102, 0.25), inset 0 0 12px rgba(255, 209, 102, 0.08) !important;
    }
    .fc .fc-day-today .fc-daygrid-day-number {
        color: #ffffff !important;
        font-weight: 800 !important;
    }
    .fc .fc-daygrid-event {
        border: none !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
        border-radius: 8px !important;
        padding: 4px 8px;
        font-size: 0.78rem;
        font-weight: 700;
        color: #ffffff !important;
        margin: 2px 4px !important;
        transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .fc .fc-daygrid-event:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 6px 16px rgba(0,0,0,0.5) !important;
        filter: brightness(1.15);
        z-index: 5;
    }
    
    /* Force Saturday to display in case it is hidden by CSS */
    .fc-day-sat, th.fc-day-sat, td.fc-day-sat {
        display: table-cell !important;
    }
    .fc-scrollgrid th:last-child, .fc-scrollgrid td:last-child {
        display: table-cell !important;
    }
    .fc-scrollgrid th:nth-child(7), .fc-scrollgrid td:nth-child(7) {
        display: table-cell !important;
    }
    
    /* Ensure calendar table shows all 7 columns */
    .fc .fc-scrollgrid-sync-table { width: 100% !important; min-width: 100% !important; }
    .fc-col-header { display: table !important; width: 100% !important; }
    .fc-daygrid-body { width: 100% !important; }
    .fc-scrollgrid { width: 100% !important; }
    
    /* Mobile Responsive Enhancements */
    @media (max-width: 768px) {
        /* Mobile Page Layout */
        #schoolCalendarPage {
            padding-bottom: 0 !important;
        }
        
        .holiday-dashboard .glass-card {
            margin: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }
        
        /* Mobile Calendar Header */
        .holiday-dashboard .glass-card .adm-card-head {
            position: sticky !important;
            top: 0 !important;
            z-index: 100 !important;
            flex-direction: row !important;
            gap: 10px !important;
            padding: 12px 16px !important;
            align-items: center !important;
            background: rgba(26, 26, 46, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            border-bottom: 1px solid rgba(255,255,255,0.08) !important;
        }
        
        .holiday-dashboard .glass-card .adm-card-title {
            display: none !important; /* Hide title to save space */
        }
        
        /* Month Navigation */
        .holiday-dashboard .calendar-controls {
            width: 100% !important;
            justify-content: space-between !important;
            gap: 10px !important;
            flex-wrap: nowrap !important;
        }
        
        .holiday-dashboard .calendar-controls button {
            min-width: 48px !important;
            height: 48px !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: rgba(207, 164, 111, 0.12) !important;
            border: 1px solid rgba(207, 164, 111, 0.25) !important;
            border-radius: 14px !important;
            font-size: 1.3rem !important;
            flex-shrink: 0 !important;
            transition: all 0.2s ease !important;
        }
        
        .holiday-dashboard .calendar-controls button:active {
            transform: scale(0.95) !important;
        }
        
        .holiday-dashboard .calendar-header {
            flex: 1 !important;
            min-width: auto !important;
            font-size: 1.15rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.3px !important;
        }
        
        .holiday-dashboard .adm-btn-primary {
            min-width: 80px !important;
            height: 48px !important;
            padding: 0 18px !important;
            font-size: 0.95rem !important;
            border-radius: 14px !important;
        }
        
        /* Floating Legend */
        .holiday-dashboard .glass-card > div[style*="padding"]:first-of-type {
            position: fixed !important;
            bottom: 80px !important;
            right: 16px !important;
            left: 16px !important;
            z-index: 99 !important;
            padding: 0 !important;
        }
        
        .legend-container {
            margin-top: 0 !important;
            padding: 12px 16px !important;
            gap: 12px !important;
            background: rgba(26, 26, 46, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
        }
        
        .legend-item {
            font-size: 0.75rem !important;
            gap: 8px !important;
        }
        
        .legend-dot {
            width: 10px !important;
            height: 10px !important;
        }
        
        /* Calendar Container */
        .holiday-dashboard .glass-card > div:last-child {
            padding: 8px !important;
        }
        
        #calendar {
            min-width: 0 !important;
            width: 100% !important;
            min-height: auto !important;
        }
        
        /* FullCalendar Mobile Optimizations */
        .fc {
            font-size: 0.75rem !important;
            background: rgba(26, 26, 46, 0.6) !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
            box-shadow: 0 2px 12px rgba(0,0,0,0.2) !important;
        }
        
        /* Force calendar to fit width */
        .fc-scrollgrid,
        .fc-scrollgrid-sync-table {
            width: 100% !important;
            min-width: 0 !important;
        }
        
        .fc table {
            width: 100% !important;
            table-layout: fixed !important;
        }
        
        /* Day headers */
        .fc .fc-col-header-cell {
            padding: 12px 4px !important;
            background: linear-gradient(180deg, rgba(207, 164, 111, 0.08), rgba(207, 164, 111, 0.03)) !important;
            border-bottom: 2px solid rgba(207, 164, 111, 0.15) !important;
            border-right: none !important;
        }
        
        .fc .fc-col-header-cell-cushion {
            font-size: 0.7rem !important;
            font-weight: 800 !important;
            letter-spacing: 0.08em !important;
            color: #d4a574 !important;
        }
        
        /* Day cells - Larger touch targets */
        .fc .fc-daygrid-day {
            min-height: 85px !important;
            border-right: 1px solid rgba(255,255,255,0.04) !important;
            border-bottom: 1px solid rgba(255,255,255,0.04) !important;
        }
        
        .fc .fc-daygrid-day-frame {
            min-height: 85px !important;
            padding: 4px !important;
        }
        
        .fc .fc-daygrid-day-number {
            font-size: 1rem !important;
            font-weight: 700 !important;
            padding: 8px 10px !important;
            text-align: center !important;
        }
        
        /* Other month days */
        .fc .fc-day-other {
            opacity: 0.25 !important;
            background: rgba(0,0,0,0.2) !important;
        }
        
        /* Today cell - Enhanced */
        .fc .fc-day-today {
            background: linear-gradient(135deg, rgba(207, 164, 111, 0.18), rgba(207, 164, 111, 0.08)) !important;
            box-shadow: inset 0 0 0 2px rgba(207, 164, 111, 0.4) !important;
        }
        
        .fc .fc-day-today .fc-daygrid-day-number {
            color: #d4a574 !important;
            font-weight: 900 !important;
            background: rgba(207, 164, 111, 0.15) !important;
            border-radius: 8px !important;
            padding: 6px 12px !important;
        }
        
        /* Events - Better visibility */
        .fc .fc-daygrid-event {
            font-size: 0.7rem !important;
            padding: 3px 6px !important;
            margin: 2px !important;
            border-radius: 6px !important;
            min-height: 20px !important;
            transition: transform 0.15s ease !important;
        }
        
        .fc .fc-daygrid-event:active {
            transform: scale(0.98) !important;
        }
        
        .fc .fc-daygrid-event .fc-event-title {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        
        /* Better touch targets */
        .fc .fc-daygrid-day-top {
            padding: 2px !important;
            min-height: 36px !important;
            justify-content: center !important;
        }
        
        .fc .fc-daygrid-day-events {
            margin-top: 2px !important;
            min-height: 40px !important;
        }
        
        /* More events indicator */
        .fc .fc-daygrid-more-link {
            font-size: 0.65rem !important;
            font-weight: 700 !important;
            color: #d4a574 !important;
            background: rgba(207, 164, 111, 0.15) !important;
            padding: 2px 6px !important;
            border-radius: 4px !important;
            margin: 2px !important;
        }
        
        /* Event Modal - Bottom Sheet */
        .modal-dialog-centered {
            margin: 0 !important;
            max-width: 100% !important;
            height: 100vh !important;
            display: flex !important;
            align-items: flex-end !important;
        }
        
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.6) !important;
            backdrop-filter: blur(4px) !important;
        }
        
        .event-modal-content {
            border-radius: 24px 24px 0 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-height: 80vh !important;
        }
        
        .event-modal-header {
            padding: 24px 24px 16px !important;
            border-radius: 24px 24px 0 0 !important;
            position: relative !important;
        }
        
        .event-modal-header::before {
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
        
        .event-modal-header .modal-title {
            font-size: 1.2rem !important;
            margin-top: 12px !important;
            line-height: 1.3 !important;
        }
        
        .event-modal-body {
            padding: 20px 24px 32px !important;
            max-height: calc(80vh - 100px) !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }
        
        .event-detail-item {
            margin-bottom: 20px !important;
            padding-bottom: 20px !important;
        }
        
        .event-detail-label {
            font-size: 0.75rem !important;
            margin-bottom: 8px !important;
        }
        
        .event-detail-value {
            font-size: 1rem !important;
        }
        
        .btn-close {
            width: 36px !important;
            height: 36px !important;
            background-size: 16px !important;
            opacity: 0.7 !important;
        }
        
        /* Form inputs mobile */
        .holiday-dashboard .adm-input {
            font-size: 0.9rem !important;
            padding: 10px 14px !important;
        }
        
        .holiday-dashboard .form-label {
            font-size: 0.75rem !important;
        }
        
        /* Invitee chips mobile */
        .chip {
            font-size: 0.75rem !important;
            padding: 5px 12px !important;
        }
        
        /* Reschedule Modal Mobile */
        .modal-dialog-centered .modal-dialog {
            margin: 0.5rem !important;
        }
        
        .modal-footer {
            flex-direction: column !important;
            gap: 10px !important;
        }
        
        .modal-footer button {
            width: 100% !important;
        }
        
        /* Fix scrolling */
        body {
            overflow-x: hidden !important;
        }
        
        /* Tap highlighting */
        .fc .fc-daygrid-day {
            -webkit-tap-highlight-color: rgba(207, 164, 111, 0.15);
            cursor: pointer;
        }
        
        /* Swipe hint */
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
    
    /* Legend */
    .legend-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 24px;
        padding: 16px 24px;
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.04);
        border-radius: 16px;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #b39b82;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    
    /* Inputs and Chips for Modals */
    .holiday-dashboard .adm-input {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        color: #f3e7cd;
        border-radius: 10px;
        padding: 10px 14px;
        width: 100%;
        transition: all 0.2s ease;
    }
    .holiday-dashboard .adm-input:focus {
        outline: none;
        border-color: rgba(207, 164, 111, 0.4);
        box-shadow: 0 0 0 3px rgba(207, 164, 111, 0.15);
    }
    .holiday-dashboard .form-label {
        color: #b39b82;
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .invitee-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }
    .chip {
        background: rgba(207, 164, 111, 0.1);
        border: 1px solid rgba(207, 164, 111, 0.2);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #cfa46f;
    }
    .chip button {
        background: none;
        border: none;
        color: #cfa46f;
        padding: 0;
        display: flex;
        align-items: center;
        cursor: pointer;
        opacity: 0.7;
    }
    .chip button:hover {
        opacity: 1;
    }
    
    .search-results {
        background: rgba(15, 15, 20, 0.95);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        margin-top: 4px;
        max-height: 200px;
        overflow-y: auto;
        position: absolute;
        width: 100%;
        z-index: 10;
        display: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .search-result-item {
        padding: 12px 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .search-result-item:last-child {
        border-bottom: none;
    }
    .search-result-item:hover {
        background: rgba(255,255,255,0.05);
    }

    /* Event Details Modal Pro Max */
    .event-modal-content {
        background: rgba(10, 10, 10, 0.95);
        border: 1px solid rgba(255,255,255,0.08);
        backdrop-filter: blur(20px);
        box-shadow: 0 32px 80px rgba(0,0,0,0.6);
        border-radius: 24px;
        color: #f3e7cd;
    }
    .event-modal-header {
        border-bottom: 1px solid rgba(255,255,255,0.06);
        padding: 24px 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .event-modal-header .modal-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: #f3e7cd;
        margin: 0;
    }
    .event-modal-body {
        padding: 32px;
    }
    .event-detail-item {
        margin-bottom: 24px;
        padding-bottom: 24px;
        border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .event-detail-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    .event-detail-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
        color: #b39b82;
        margin-bottom: 8px;
    }
    .event-detail-value {
        font-size: 1.05rem;
        color: #f8fafc;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
    }
    .event-type-badge {
        display: inline-flex;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #1a1a2e !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
</style>

<div class="row g-3">
    <!-- Calendar -->
    <div class="col-12">
        <div class="adm-card glass-card">
            <div class="adm-card-head">
                <div class="adm-card-title">
                    <div class="adm-card-icon">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    School Calendar
                </div>
                <div class="calendar-controls">
                    <button type="button" onclick="previousMonth()" class="adm-btn adm-btn-ghost" style="padding: 6px 10px;">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <span id="currentMonth" class="calendar-header">
                        {{ \Carbon\Carbon::create($year, $month)->format('F Y') }}
                    </span>
                    <button type="button" onclick="nextMonth()" class="adm-btn adm-btn-ghost" style="padding: 6px 10px;">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button type="button" onclick="goToToday()" class="adm-btn adm-btn-ghost" style="margin-left: 10px; margin-right: 10px;">
                        Today
                    </button>
                </div>
            </div>
            
            <div style="padding: 0 20px;">
                <div class="legend-container">
                    <div class="legend-item"><div class="legend-dot" style="background: #60a5fa;"></div> Class</div>
                    <div class="legend-item"><div class="legend-dot" style="background: #ec4899;"></div> Exam</div>
                    <div class="legend-item"><div class="legend-dot" style="background: #a78bfa;"></div> School Event</div>
                    <div class="legend-item"><div class="legend-dot" style="background: #4ade80;"></div> Holiday</div>
                </div>
            </div>
            
            <div style="padding: 20px; overflow-x: auto;">
                <div id="calendar" style="min-height: 600px; min-width: 700px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content event-modal-content w-100">
            <div class="event-modal-header">
                <h5 class="modal-title" style="margin: 0; font-weight: 700;" id="eventTitle">Event Title</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="event-modal-body">
                <div class="event-detail-item">
                    <div class="event-detail-label">Event Type</div>
                    <div class="event-detail-value">
                        <span id="eventTypeBadge" class="event-type-badge">Type</span>
                    </div>
                </div>
                <div class="event-detail-item">
                    <div class="event-detail-label">Date & Time</div>
                    <div class="event-detail-value">
                        <i class="bi bi-clock" style="color: #94a3b8;"></i>
                        <span id="eventTime">Date</span>
                    </div>
                </div>
                <div class="event-detail-item" id="eventLocationContainer">
                    <div class="event-detail-label">Location</div>
                    <div class="event-detail-value">
                        <i class="bi bi-geo-alt" style="color: #94a3b8;"></i>
                        <span id="eventLocation">Location</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.21/index.global.min.js"></script>
<script>
let calendar;
let currentYear = {{ $year }};
let currentMonth = {{ $month }};

document.addEventListener('DOMContentLoaded', function() {
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
            fetch(`{{ route('teacher.calendar.data') }}?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`)
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
                            status: event.status
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
            const props = info.event.extendedProps;
            
            document.getElementById('eventTitle').textContent = info.event.title;
            
            const badge = document.getElementById('eventTypeBadge');
            badge.textContent = props.type.replace('_', ' ');
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
            
            new bootstrap.Modal(document.getElementById('eventDetailModal')).show();
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
    
    // Mobile touch enhancements
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
        
        // Add haptic feedback for interactions (if supported)
        if ('vibrate' in navigator) {
            // Wrap event handlers to add haptic feedback
            const originalEventClick = calendar.getOption('eventClick');
            calendar.setOption('eventClick', function(info) {
                navigator.vibrate(10);
                if (originalEventClick) originalEventClick(info);
            });
        }
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
