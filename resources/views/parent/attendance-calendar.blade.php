@extends('layouts.app')
@section('page-title', 'Attendance Calendar')

@section('content')

@php
    $calYear   = request('cal_year',  now()->year);
    $calMonth  = request('cal_month', now()->month);
    $calStart  = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $calEnd    = $calStart->copy()->endOfMonth();
    $prevMonth = $calStart->copy()->subMonth();
    $nextMonth = $calStart->copy()->addMonth();

    $monthRecords = isset($records) ? $records->filter(fn($r) =>
        \Carbon\Carbon::parse($r->date)->year  == $calYear &&
        \Carbon\Carbon::parse($r->date)->month == $calMonth
    ) : collect();

    $dayRecordsMap = [];
    foreach ($monthRecords as $r) {
        $day = (int) \Carbon\Carbon::parse($r->date)->format('j');
        $dayRecordsMap[$day][] = $r;
    }

    $dayDotsMap = [];
    foreach ($dayRecordsMap as $day => $recs) {
        $dots = [];
        $statuses = collect($recs)->pluck('status')->unique();
        if ($statuses->contains('Present')) $dots[] = 'present';
        if ($statuses->contains('Late'))    $dots[] = 'late';
        if ($statuses->contains('Absent'))  $dots[] = 'absent';
        $dayDotsMap[$day] = $dots;
    }

    $calendarJson = [];
    foreach ($dayRecordsMap as $day => $recs) {
        $dateKey = \Carbon\Carbon::create($calYear, $calMonth, $day)->format('Y-m-d');
        $calendarJson[$dateKey] = collect($recs)->map(function($r) {
            $schedStart = $r->subject?->start_time ? \Carbon\Carbon::parse($r->subject->start_time)->format('g:i A') : null;
            $schedEnd   = $r->subject?->end_time   ? \Carbon\Carbon::parse($r->subject->end_time)->format('g:i A')   : null;
            $schedText  = ($schedStart && $schedEnd) ? "{$schedStart} – {$schedEnd}" : ($r->subject?->schedule_time ?? 'Flexible Schedule');

            $clockInTime = $r->time_in 
                ? \Carbon\Carbon::parse($r->time_in)->format('g:i A') 
                : ($r->checked_in_at ? $r->checked_in_at->format('g:i A') : null);

            $clockOutTime = $r->time_out 
                ? \Carbon\Carbon::parse($r->time_out)->format('g:i A') 
                : null;

            $status = $r->status ? ucfirst(strtolower($r->status)) : 'Absent';
            $clockInDisplay = $clockInTime ?? ($status === 'Absent' ? 'No clock-in' : '—');

            $instructorName = $r->subject?->instructorUser?->name 
                           ?? $r->subject?->instructor 
                           ?? 'Instructor TBA';

            $remarks = $r->excuse_note 
                    ?? $r->excuseSubmission?->reason 
                    ?? ($r->excused ? 'Excused Absence' : null);

            return [
                'id'             => $r->id,
                'subject'        => $r->subject->name ?? $r->subject_name ?? $r->subject_code ?? 'Class',
                'code'           => $r->subject_code ?? ($r->subject->code ?? ''),
                'instructor'     => $instructorName,
                'schedule'       => $schedText,
                'status'         => $status,
                'status_lower'   => strtolower($status),
                'time_in'        => $clockInTime,
                'clock_in'       => $clockInDisplay,
                'time_out'       => $clockOutTime,
                'remarks'        => $remarks,
            ];
        })->values()->toArray();
    }

    $today     = now()->day;
    $isCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $startDow  = $calStart->dayOfWeek;
    $isLatestMonth = $isCurrentMonth;
@endphp

<style>
    /* ── UNIFIED ATTENDANCE CALENDAR (Dark Squircle Theme) ── */
    .att-cal-wrap {
        background: #0f0a08;
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7);
        padding: 12px;
    }
    .att-cal-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px 20px;
    }
    .att-cal-nav-btn {
        width: 44px; height: 44px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.03);
        color: #cfa46f;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 1.15rem;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
    }
    .att-cal-nav-btn:hover {
        background: rgba(207, 164, 111, 0.15);
        border-color: rgba(207, 164, 111, 0.4);
        color: #ffffff;
        transform: translateY(-1px);
    }
    .att-cal-month {
        font-size: 1.35rem;
        font-weight: 800;
        color: #fdfbf7;
        letter-spacing: -0.02em;
    }
    .att-cal-header {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        padding: 10px 0;
        margin: 0 10px 14px;
        background: rgba(255, 255, 255, 0.025);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 14px;
    }
    .att-cal-header span {
        font-size: 0.78rem;
        font-weight: 800;
        color: #cfa46f;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 0;
    }
    .att-cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 10px;
        padding: 0 10px 16px;
    }
    .att-cal-cell {
        height: 60px;
        min-height: 60px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.04);
        background: rgba(255, 255, 255, 0.025);
        font-size: 1.05rem;
        font-weight: 700;
        color: #f3ede4;
        position: relative;
        cursor: default;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        gap: 3px;
    }
    .att-cal-cell.has-records {
        cursor: pointer;
    }
    .att-cal-cell.has-records:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4);
        border-color: rgba(207, 164, 111, 0.3);
        z-index: 2;
    }
    .att-cal-cell.is-today {
        background: rgba(255, 209, 102, 0.08) !important;
        border: 2px solid #ffd166 !important;
        box-shadow: 0 0 20px rgba(255, 209, 102, 0.25), inset 0 0 12px rgba(255, 209, 102, 0.08) !important;
        color: #ffffff !important;
    }
    .att-cal-cell.is-sunday {
        color: #f87171 !important;
    }

    /* Status colors */
    .att-cal-cell.status-present {
        background: rgba(74, 222, 128, 0.14);
        color: #86efac;
        border: 1.5px solid rgba(74, 222, 128, 0.4);
    }
    .att-cal-cell.status-late {
        background: rgba(251, 191, 36, 0.14);
        color: #fde047;
        border: 1.5px solid rgba(251, 191, 36, 0.4);
    }
    .att-cal-cell.status-absent {
        background: rgba(220, 38, 38, 0.14);
        color: #fca5a5;
        border: 1.5px solid rgba(220, 38, 38, 0.45);
    }
    .att-cal-cell.status-mixed {
        background: rgba(96, 165, 250, 0.14);
        color: #93c5fd;
        border: 1.5px solid rgba(96, 165, 250, 0.4);
    }

    /* Status dots row inside the cell */
    .att-cal-dots {
        display: flex;
        gap: 3px;
        justify-content: center;
    }
    .att-cal-dot {
        width: 5px; height: 5px;
        border-radius: 50%;
    }
    .att-cal-dot.present { background: #4ade80; }
    .att-cal-dot.late    { background: #fbbf24; }
    .att-cal-dot.absent  { background: #f87171; }

    /* Stats bar */
    .att-cal-stats {
        display: flex;
        gap: 6px;
        padding: 14px 20px;
        border-top: 1px solid rgba(255,255,255,0.06);
        flex-wrap: wrap;
        justify-content: center;
    }
    .att-cal-stat {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 5px 14px;
        border-radius: 99px;
        border: 1px solid;
    }
    .att-cal-stat.present {
        color: #4ade80;
        background: rgba(74,222,128,0.08);
        border-color: rgba(74,222,128,0.2);
    }
    .att-cal-stat.late {
        color: #fbbf24;
        background: rgba(251,191,36,0.08);
        border-color: rgba(251,191,36,0.2);
    }
    .att-cal-stat.absent {
        color: #f87171;
        background: rgba(248,113,113,0.08);
        border-color: rgba(248,113,113,0.2);
    }
    .att-cal-stat .stat-dot {
        width: 7px; height: 7px;
        border-radius: 50%;
    }
    .att-cal-stat.present .stat-dot { background: #4ade80; }
    .att-cal-stat.late .stat-dot    { background: #fbbf24; }
    .att-cal-stat.absent .stat-dot  { background: #f87171; }

    /* Detail panel */
    .att-cal-detail {
        display: none;
        border-top: 1px solid rgba(255,255,255,0.06);
        padding: 20px;
        animation: attDetailSlide 0.25s ease;
    }
    .att-cal-detail.active { display: block; }
    @keyframes attDetailSlide {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .att-detail-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .att-detail-date {
        font-size: 1rem;
        font-weight: 800;
        color: #f3e7cd;
    }
    .att-detail-close {
        width: 28px; height: 28px;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.04);
        color: #8f826f;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        font-size: 0.75rem;
        transition: all 0.2s;
    }
    .att-detail-close:hover {
        background: rgba(248,113,113,0.15);
        border-color: rgba(248,113,113,0.3);
        color: #f87171;
    }
    .att-detail-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: 10px;
        background: rgba(0,0,0,0.2);
        border: 1px solid rgba(255,255,255,0.04);
        margin-bottom: 6px;
    }
    .att-detail-subject {
        font-weight: 700;
        font-size: 0.85rem;
        color: #f3e7cd;
    }
    .att-detail-code {
        font-size: 0.72rem;
        color: #8f826f;
        margin-top: 2px;
    }
    .att-detail-time {
        font-size: 0.72rem;
        color: #8f826f;
        margin-top: 1px;
    }
    .att-detail-badge {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 99px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
    }
    .att-detail-badge.present {
        color: #4ade80;
        background: rgba(74,222,128,0.12);
        border: 1px solid rgba(74,222,128,0.25);
    }
    .att-detail-badge.late {
        color: #fbbf24;
        background: rgba(251,191,36,0.12);
        border: 1px solid rgba(251,191,36,0.25);
    }
    .att-detail-badge.absent {
        color: #f87171;
        background: rgba(248,113,113,0.12);
        border: 1px solid rgba(248,113,113,0.25);
    }
    .att-detail-empty {
        text-align: center;
        padding: 24px;
        color: #8f826f;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .premium-select {
        border: 1px solid rgba(207, 164, 111, 0.3);
    }
    .premium-select:focus {
        border-color: rgba(207, 164, 111, 0.8);
        box-shadow: 0 0 0 0.25rem rgba(207, 164, 111, 0.25);
    }
    .premium-select option {
        background-color: #1e293b;
        color: #f3e7cd;
    }

    /* Modal Stacking Context & Backdrop */
    #daySummaryModal {
        z-index: 10060 !important;
    }
    .modal-backdrop {
        z-index: 10050 !important;
        backdrop-filter: blur(8px) !important;
        -webkit-backdrop-filter: blur(8px) !important;
        background: rgba(0, 0, 0, 0.72) !important;
    }

    #daySummaryModal .modal-dialog {
        transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    #daySummaryModal .modal-content {
        background: linear-gradient(180deg, #18120d 0%, #0d0806 100%) !important;
        border: 1px solid rgba(212, 175, 55, 0.2) !important;
        border-radius: 26px !important;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.85), 0 0 40px rgba(207, 164, 111, 0.08) !important;
        position: relative;
    }
    #daySummaryModal .modal-content::before {
        display: none !important;
    }

    .scal-sheet-handle,
    .day-modal-sheet-handle {
        width: 42px;
        height: 4.5px;
        background: rgba(255, 255, 255, 0.22);
        border-radius: 99px;
        margin: 12px auto 4px auto;
        display: none;
        flex-shrink: 0;
    }

    /* Top Action Bar */
    .day-modal-action-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px 8px 20px;
    }
    .day-nav-stepper {
        display: inline-flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 3px;
        gap: 2px;
    }
    .day-nav-step-btn {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: transparent;
        border: none;
        color: #cfa46f;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .day-nav-step-btn:hover {
        background: rgba(207, 164, 111, 0.15);
        color: #ffd166;
    }
    .day-nav-step-btn:active {
        transform: scale(0.92);
    }
    .day-nav-today-btn {
        height: 30px;
        padding: 0 10px;
        border-radius: 8px;
        background: rgba(207, 164, 111, 0.12);
        border: 1px solid rgba(207, 164, 111, 0.25);
        color: #ffd166;
        font-size: 0.75rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    .day-nav-today-btn:hover {
        background: rgba(207, 164, 111, 0.25);
        border-color: #ffd166;
        color: #fff;
    }
    .day-modal-close-btn {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #cfa46f;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .day-modal-close-btn:hover {
        background: rgba(239, 68, 68, 0.15);
        border-color: rgba(239, 68, 68, 0.35);
        color: #f87171;
        transform: rotate(90deg);
    }

    /* Date & Badges Title Row */
    .day-modal-header-content {
        padding: 4px 20px 14px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }
    .day-modal-date {
        font-size: 1.24rem;
        font-weight: 800;
        color: #f8fafc;
        letter-spacing: -0.015em;
        margin: 0 0 8px 0;
        line-height: 1.25;
    }
    .day-summary-badges {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .day-summary-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    .day-summary-chip.chip-count {
        background: rgba(207, 164, 111, 0.12);
        color: #f3ede4;
        border: 1px solid rgba(207, 164, 111, 0.25);
    }
    .day-summary-chip.chip-present {
        background: rgba(16, 185, 129, 0.12);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .day-summary-chip.chip-late {
        background: rgba(245, 158, 11, 0.12);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }
    .day-summary-chip.chip-absent {
        background: rgba(239, 68, 68, 0.12);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    .day-summary-chip.chip-empty {
        background: rgba(255, 255, 255, 0.04);
        color: #a8937e;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    /* Section Header */
    .day-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 16px 0 12px 0;
    }
    .day-section-label {
        font-size: 0.74rem;
        font-weight: 800;
        color: #cfa46f;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .day-section-icon {
        color: #cfa46f;
        font-size: 0.8rem;
    }
    .day-section-badge {
        font-size: 0.72rem;
        font-weight: 700;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #b39b82;
        padding: 2px 8px;
        border-radius: 6px;
    }

    /* Subject Detail Cards */
    .att-detail-card {
        background: linear-gradient(145deg, rgba(32, 24, 20, 0.9) 0%, rgba(18, 13, 11, 0.95) 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 16px 18px;
        position: relative;
        overflow: hidden;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        backdrop-filter: blur(12px);
    }
    .att-detail-card.status-border-present {
        border-left: 4px solid #10b981 !important;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.06);
    }
    .att-detail-card.status-border-late {
        border-left: 4px solid #f59e0b !important;
        box-shadow: 0 6px 20px rgba(245, 158, 11, 0.06);
    }
    .att-detail-card.status-border-absent {
        border-left: 4px solid #ef4444 !important;
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.06);
    }

    .att-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }
    .att-card-subject-title {
        font-weight: 800;
        font-size: 1.05rem;
        color: #f8fafc;
        line-height: 1.32;
        margin-bottom: 6px;
    }
    .att-card-meta {
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .att-code-pill {
        background: rgba(207, 164, 111, 0.15);
        color: #ffd166;
        border: 1px solid rgba(207, 164, 111, 0.35);
        font-weight: 800;
        font-size: 0.74rem;
        padding: 2px 8px;
        border-radius: 6px;
        letter-spacing: 0.04em;
    }
    .att-instructor-pill {
        color: #c5b3a1;
        font-size: 0.78rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .att-status-badge {
        padding: 5px 12px;
        border-radius: 10px;
        font-size: 0.74rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
    .att-status-badge.present {
        background: rgba(16, 185, 129, 0.12);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.35);
        box-shadow: 0 0 12px rgba(16, 185, 129, 0.15);
    }
    .att-status-badge.late {
        background: rgba(245, 158, 11, 0.12);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.35);
        box-shadow: 0 0 12px rgba(245, 158, 11, 0.15);
    }
    .att-status-badge.absent {
        background: rgba(239, 68, 68, 0.12);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.35);
        box-shadow: 0 0 12px rgba(239, 68, 68, 0.15);
    }

    /* Grid for Schedule & Clock-in */
    .att-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 10px;
        background: rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 14px;
        padding: 12px 14px;
    }
    .att-grid-col-label {
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #cfa46f;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .att-grid-col-val {
        font-size: 0.88rem;
        font-weight: 700;
        color: #f1f5f9;
    }

    /* Excuse Request Button for Absent Classes */
    .day-card-excuse-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 10px;
        padding: 8px 12px;
        border-radius: 10px;
        background: rgba(207, 164, 111, 0.08);
        border: 1px dashed rgba(207, 164, 111, 0.3);
        color: #ffd166;
        font-size: 0.76rem;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .day-card-excuse-btn:hover {
        background: rgba(207, 164, 111, 0.18);
        border-color: #ffd166;
        color: #fff;
    }

    /* Empty state */
    .day-empty-card {
        text-align: center;
        padding: 38px 20px;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 20px;
        border: 1px dashed rgba(255, 255, 255, 0.08);
    }
    .day-empty-icon {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        background: rgba(207, 164, 111, 0.1);
        border: 1px solid rgba(207, 164, 111, 0.22);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 14px;
        color: #ffd166;
        font-size: 1.6rem;
    }

    @media (max-width: 768px) {
        .att-cal-cell { font-size: 0.75rem; border-radius: 8px; }
        .att-cal-dot { width: 4px; height: 4px; }
        .att-cal-nav { padding: 16px 16px 12px; }
        .att-cal-grid { padding: 0 10px 12px; gap: 3px; }
        .att-cal-header { padding: 0 10px; }
        .att-cal-stats { padding: 10px 12px; gap: 4px; }
        .att-cal-stat { padding: 4px 10px; font-size: 0.7rem; }

        /* Responsive Mobile Bottom Sheet for Day Summary Modal */
        #daySummaryModal .modal-dialog {
            margin: 0 !important;
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            max-height: 88vh !important;
            display: flex !important;
            align-items: flex-end !important;
            transform: translateY(0) !important;
        }
        #daySummaryModal .modal-content {
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-top-left-radius: 28px !important;
            border-top-right-radius: 28px !important;
            max-height: 88vh !important;
            overflow-y: auto !important;
            padding-bottom: calc(24px + env(safe-area-inset-bottom, 20px)) !important;
            width: 100% !important;
            margin: 0 !important;
        }
        #daySummaryModal .modal-content::before {
            display: none !important;
        }
        .day-modal-sheet-handle,
        .scal-sheet-handle {
            display: block !important;
        }
        .day-modal-action-bar {
            padding: 10px 16px 6px 16px;
        }
        .day-modal-header-content {
            padding: 2px 16px 12px 16px;
        }
        .day-modal-date {
            font-size: 1.12rem;
        }
        .att-detail-card {
            padding: 14px 14px;
        }
    }
</style>

<div class="mb-4 d-flex justify-content-between align-items-end flex-wrap gap-3">
    <div>
        <h1 style="color: #f3e7cd; font-weight: 800; margin: 0 0 6px 0; font-size: 2rem;">Attendance Calendar</h1>
        <div style="color: #b39b82; font-size: 0.95rem;">
            @if($selectedChild)
                Attendance Calendar for {{ $selectedChild->name }}
            @else
                No student selected.
            @endif
        </div>
    </div>
    
    @if($children->count() > 1)
    <div class="ent-glass-panel px-3 py-2" style="border-radius: 12px; display: inline-flex; align-items: center; gap: 12px;">
        <i class="bi bi-person-badge text-gold" style="font-size: 1.2rem;"></i>
        <form id="child-select-form" method="GET" action="{{ route('parent.attendance.calendar') }}" class="m-0 p-0 d-flex align-items-center">
            <select name="child_id" onchange="document.getElementById('child-select-form').submit()" class="form-select form-select-sm premium-select" style="min-width: 200px; background-color: rgba(0,0,0,0.2); color: #f3e7cd; border-color: rgba(255,255,255,0.1);">
                @foreach($children as $child)
                    <option value="{{ $child->id }}" {{ $selectedChild && $selectedChild->id == $child->id ? 'selected' : '' }}>
                        {{ $child->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
    @endif
</div>

@if(!$selectedChild)
    <div class="alert alert-warning" style="background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3); color: #fbbf24;">
        You currently do not have any students linked to your account.
    </div>
@else
<div class="row justify-content-center">
    <div class="col-lg-12">
        <x-card title="Attendance Calendar" icon="bi bi-calendar-check-fill">
            <x-slot name="headerActions">
                <a href="{{ route('parent.child', $selectedChild) }}" class="btn btn-outline btn-sm">View List Records</a>
            </x-slot>

            <div class="att-cal-wrap">
                {{-- Month Navigation --}}
                <div class="att-cal-nav">
                    <a href="?child_id={{ $selectedChild->id }}&cal_year={{ $prevMonth->year }}&cal_month={{ $prevMonth->month }}" class="att-cal-nav-btn">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <div class="att-cal-month">{{ $calStart->format('F Y') }}</div>
                    @if(!$isLatestMonth)
                        <a href="?child_id={{ $selectedChild->id }}&cal_year={{ $nextMonth->year }}&cal_month={{ $nextMonth->month }}" class="att-cal-nav-btn">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    @else
                        <div style="width:36px;"></div>
                    @endif
                </div>

                {{-- Day Labels --}}
                <div class="att-cal-header">
                    <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                </div>

                {{-- Calendar Grid --}}
                <div class="att-cal-grid">
                    {{-- Empty cells before the 1st --}}
                    @for($i = 0; $i < $startDow; $i++)
                        <div class="att-cal-cell"></div>
                    @endfor

                    @for($d = 1; $d <= $calEnd->day; $d++)
                        @php
                            $dayDate = \Carbon\Carbon::create($calYear, $calMonth, $d);
                            $dateKey = $dayDate->format('Y-m-d');
                            $isSunday = $dayDate->dayOfWeek === 0;
                            $isTodayCell = $isCurrentMonth && $d === $today;
                            $dayStatuses = $dayDotsMap[$d] ?? [];
                            $hasRecords = !empty($dayStatuses);

                            // Determine the primary status for cell coloring
                            $cellStatus = '';
                            if ($hasRecords) {
                                $statusCount = count($dayStatuses);
                                if ($statusCount > 1) {
                                    $cellStatus = 'status-mixed';
                                } elseif (in_array('present', $dayStatuses)) {
                                    $cellStatus = 'status-present';
                                } elseif (in_array('late', $dayStatuses)) {
                                    $cellStatus = 'status-late';
                                } elseif (in_array('absent', $dayStatuses)) {
                                    $cellStatus = 'status-absent';
                                }
                            }

                            $cellClasses = 'att-cal-cell';
                            if ($hasRecords) $cellClasses .= ' has-records';
                            if ($isTodayCell) $cellClasses .= ' is-today';
                            if ($isSunday && !$hasRecords) $cellClasses .= ' is-sunday';
                            if ($cellStatus) $cellClasses .= ' ' . $cellStatus;
                        @endphp
                        <div class="{{ $cellClasses }}"
                             id="calTile_{{ $dateKey }}"
                             @if($hasRecords) role="button" tabindex="0" onclick="showAttDetail('{{ $dateKey }}', {{ $d }})" @endif
                             @if($hasRecords) title="Click to view details" @endif
                             style="touch-action: manipulation;">
                            <span>{{ $d }}</span>
                            @if($hasRecords && count($dayStatuses) > 1)
                                <div class="att-cal-dots">
                                    @foreach($dayStatuses as $dot)
                                        <div class="att-cal-dot {{ $dot }}"></div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>

                {{-- Summary Stats Bar --}}
                @php
                    $calPresent = $monthRecords->where('status', 'Present')->count();
                    $calLate    = $monthRecords->where('status', 'Late')->count();
                    $calAbsent  = $monthRecords->where('status', 'Absent')->count();
                @endphp
                <div class="att-cal-stats">
                    <div class="att-cal-stat present">
                        <div class="stat-dot"></div>
                        <i class="bi bi-check-circle-fill" style="font-size:0.7rem;"></i> {{ $calPresent }} Present
                    </div>
                    <div class="att-cal-stat late">
                        <div class="stat-dot"></div>
                        <i class="bi bi-clock-fill" style="font-size:0.7rem;"></i> {{ $calLate }} Late
                    </div>
                    <div class="att-cal-stat absent">
                        <div class="stat-dot"></div>
                        <i class="bi bi-x-circle-fill" style="font-size:0.7rem;"></i> {{ $calAbsent }} Absent
                    </div>
                </div>

                </div>
            </div>
        </x-card>
    </div>
</div>
@endif

{{-- ── Day Summary Inspector Modal (Subject-by-Subject Attendance Details) ───────────────── --}}
<div class="modal fade" id="daySummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="scal-sheet-handle day-modal-sheet-handle"></div>
            <!-- Action bar with Stepper & Close -->
            <div class="day-modal-action-bar">
                <div class="day-nav-stepper">
                    <button type="button" class="day-nav-step-btn" onclick="navigateDayModal(-1)" title="Previous Day" aria-label="Previous Day">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button" class="day-nav-today-btn" onclick="goToTodayModal()" title="Jump to Today">
                        Today
                    </button>
                    <button type="button" class="day-nav-step-btn" onclick="navigateDayModal(1)" title="Next Day" aria-label="Next Day">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <button type="button" class="day-modal-close-btn" data-bs-dismiss="modal" onclick="closeDaySummaryModal()" title="Close Inspector" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <!-- Header Content: Date & Dynamic Micro-badges -->
            <div class="day-modal-header-content">
                <h3 class="day-modal-date" id="daySummaryTitle">Date</h3>
                <div class="day-summary-badges" id="daySummarySubtitle">
                    <span class="day-summary-chip chip-empty" id="daySummaryStatusText">
                        <span id="daySummaryStatusDot" style="width:7px; height:7px; border-radius:50%; background:#ffd166; display:inline-block;"></span>
                        Attendance Details
                    </span>
                </div>
            </div>
            <!-- Body Content -->
            <div class="px-4 pb-4 pt-1">
                <div class="day-section-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="day-section-icon"><i class="bi bi-grid-1x2-fill"></i></span>
                        <span class="day-section-label" id="daySummarySectionTitle">Subjects Breakdown</span>
                    </div>
                    <span class="day-section-badge" id="daySummarySectionCount">1 Subject</span>
                </div>
                <div id="daySummaryContent" class="d-flex flex-column gap-3">
                    <!-- dynamically populated -->
                </div>
                <div style="font-size:0.75rem; color:#8f826f; text-align:center; margin-top:20px; display:flex; align-items:center; justify-content:center; gap:6px;">
                    <i class="bi bi-shield-check text-gold" style="color:#cfa46f;"></i>
                    <span>Real-time subject-by-subject attendance records for this date.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var attCalendarData = @json($calendarJson);
let dayModalInstance = null;
let currentSelectedDateKey = '{{ $calYear }}-{{ str_pad($calMonth, 2, '0', STR_PAD_LEFT) }}-{{ str_pad(($isCurrentMonth && $today ? $today : 14), 2, '0', STR_PAD_LEFT) }}';
const selectedChildId = {{ $selectedChild ? $selectedChild->id : 'null' }};

function closeDaySummaryModal() {
    const modalEl = document.getElementById('daySummaryModal');
    if (modalEl) {
        if (dayModalInstance) {
            try { dayModalInstance.hide(); } catch (e) {}
        }
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.removeAttribute('aria-modal');
    }
    document.body.classList.remove('modal-open');
    const fb = document.querySelector('.scal-fallback-backdrop');
    if (fb) fb.remove();
}

function selectCalendarDay(dateKey, day, openModal = true) {
    document.querySelectorAll('.att-cal-cell.selected').forEach(el => el.classList.remove('selected'));

    const tile = document.getElementById('calTile_' + dateKey);
    if (tile) {
        tile.classList.add('selected');
    }
    currentSelectedDateKey = dateKey;

    if (openModal) {
        showAttDetail(dateKey, day);
    }
}

function navigateDayModal(delta) {
    if (!currentSelectedDateKey) return;
    const dt = new Date(currentSelectedDateKey + 'T00:00:00');
    dt.setDate(dt.getDate() + delta);
    const y = dt.getFullYear();
    const m = String(dt.getMonth() + 1).padStart(2, '0');
    const d = String(dt.getDate()).padStart(2, '0');
    const newDateKey = `${y}-${m}-${d}`;
    selectCalendarDay(newDateKey, dt.getDate(), true);
}

function goToTodayModal() {
    const todayStr = '{{ now()->format("Y-m-d") }}';
    const dt = new Date(todayStr + 'T00:00:00');
    selectCalendarDay(todayStr, dt.getDate(), true);
}

function showAttDetail(dateKey, day) {
    currentSelectedDateKey = dateKey;
    const titleEl = document.getElementById('daySummaryTitle');
    const subtitleEl = document.getElementById('daySummarySubtitle');
    const contentEl = document.getElementById('daySummaryContent');
    const sectionCountEl = document.getElementById('daySummarySectionCount');

    const dt = new Date(dateKey + 'T00:00:00');
    const formattedDate = dt.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    if (titleEl) titleEl.textContent = formattedDate;

    const records = attCalendarData[dateKey] || [];
    if (sectionCountEl) {
        sectionCountEl.textContent = `${records.length} ${records.length === 1 ? 'Subject' : 'Subjects'}`;
    }

    if (records.length === 0) {
        if (subtitleEl) {
            subtitleEl.innerHTML = `
                <span class="day-summary-chip chip-empty">
                    <i class="bi bi-calendar-x text-gold me-1"></i> No recorded classes
                </span>`;
        }
        if (contentEl) {
            contentEl.innerHTML = `
                <div class="day-empty-card">
                    <div class="day-empty-icon">
                        <i class="bi bi-calendar2-check"></i>
                    </div>
                    <div style="font-weight:800; font-size:1.05rem; color:#f3ede4; margin-bottom:6px;">No Classes Recorded</div>
                    <div style="font-size:0.82rem; color:#8f826f; max-width:280px; margin:0 auto; line-height:1.5;">There are no class attendance entries scheduled or recorded for this date.</div>
                </div>`;
        }
    } else {
        const presentCount = records.filter(r => r.status_lower === 'present').length;
        const lateCount = records.filter(r => r.status_lower === 'late').length;
        const absentCount = records.filter(r => r.status_lower === 'absent').length;

        let badgesHtml = `<span class="day-summary-chip chip-count"><i class="bi bi-journal-bookmark-fill me-1" style="color:#cfa46f;"></i> ${records.length} ${records.length === 1 ? 'Subject' : 'Subjects'}</span>`;
        if (presentCount > 0) {
            badgesHtml += `<span class="day-summary-chip chip-present"><i class="bi bi-check-circle-fill me-1"></i> ${presentCount} Present</span>`;
        }
        if (lateCount > 0) {
            badgesHtml += `<span class="day-summary-chip chip-late"><i class="bi bi-clock-fill me-1"></i> ${lateCount} Late</span>`;
        }
        if (absentCount > 0) {
            badgesHtml += `<span class="day-summary-chip chip-absent"><i class="bi bi-x-circle-fill me-1"></i> ${absentCount} Absent</span>`;
        }
        if (subtitleEl) subtitleEl.innerHTML = badgesHtml;

        let html = '';
        records.forEach(r => {
            const statusKey = r.status_lower || (r.status ? r.status.toLowerCase() : 'absent');
            let statusIcon = 'bi-x-circle-fill';
            let borderClass = 'status-border-absent';
            let statusBadgeClass = 'absent';

            if (statusKey === 'present') {
                statusIcon = 'bi-check-circle-fill';
                borderClass = 'status-border-present';
                statusBadgeClass = 'present';
            } else if (statusKey === 'late') {
                statusIcon = 'bi-clock-fill';
                borderClass = 'status-border-late';
                statusBadgeClass = 'late';
            }

            const clockInVal = r.clock_in || (r.time_in ? r.time_in : (statusKey === 'absent' ? 'No clock-in' : '—'));
            const excuseLink = (statusKey === 'absent' && selectedChildId && r.id)
                ? `<a href="/parent/child/${selectedChildId}/excuse/${r.id}" class="day-card-excuse-btn">
                       <span><i class="bi bi-file-earmark-plus me-1"></i> Submit Excuse for Child</span>
                       <i class="bi bi-arrow-right-short" style="font-size:1.1rem;"></i>
                   </a>`
                : '';

            html += `
                <div class="att-detail-card ${borderClass}">
                    <div class="att-card-header">
                        <div style="flex:1; min-width:200px;">
                            <div class="att-card-subject-title">${r.subject || 'Subject'}</div>
                            <div class="att-card-meta">
                                ${r.code ? `<span class="att-code-pill">${r.code}</span>` : ''}
                                ${r.instructor ? `<span class="att-instructor-pill"><i class="bi bi-person-fill"></i> ${r.instructor}</span>` : ''}
                            </div>
                        </div>
                        <span class="att-status-badge ${statusBadgeClass}">
                            <i class="bi ${statusIcon}"></i> ${r.status || 'Status'}
                        </span>
                    </div>

                    <div class="att-info-grid">
                        <div>
                            <div class="att-grid-col-label"><i class="bi bi-calendar3"></i> Schedule</div>
                            <div class="att-grid-col-val">${r.schedule || 'Flexible'}</div>
                        </div>
                        <div>
                            <div class="att-grid-col-label"><i class="bi bi-box-arrow-in-right"></i> Actual Clock-in</div>
                            <div class="att-grid-col-val" style="${statusKey === 'absent' ? 'color:#f87171;' : ''}">${clockInVal}</div>
                        </div>
                    </div>

                    ${r.remarks ? `<div style="font-size:0.75rem; color:#ffd166; margin-top:8px; display:flex; align-items:center; gap:6px;"><i class="bi bi-info-circle-fill"></i> ${r.remarks}</div>` : ''}
                    ${excuseLink}
                </div>
            `;
        });
        if (contentEl) contentEl.innerHTML = html;
    }

    const modalEl = document.getElementById('daySummaryModal');
    if (modalEl) {
        if (modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }
        try {
            if (!dayModalInstance) {
                dayModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            }
            dayModalInstance.show();
        } catch (err) {
            console.warn('Day summary modal fallback:', err);
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
    if (window.triggerHaptic) window.triggerHaptic('light');
}

document.addEventListener('DOMContentLoaded', function() {
    const dModal = document.getElementById('daySummaryModal');
    if (dModal && dModal.parentNode !== document.body) {
        document.body.appendChild(dModal);
    }
});
</script>

@endsection
