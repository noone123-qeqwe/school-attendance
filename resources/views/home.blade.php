@extends('layouts.app')
@section('page-title', 'Student Dashboard')

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

    $eventsMap = $eventsMap ?? [];

    $dayDotsMap = [];
    for ($d = 1; $d <= $calEnd->day; $d++) {
        $dots = [];
        $dDateKey = \Carbon\Carbon::create($calYear, $calMonth, $d)->format('Y-m-d');

        // 1. Attendance dots (present, late, absent)
        if (isset($dayRecordsMap[$d])) {
            $statuses = collect($dayRecordsMap[$d])->pluck('status')->map(fn($s) => strtolower($s))->unique();
            if ($statuses->contains('present')) $dots[] = 'present';
            if ($statuses->contains('late'))    $dots[] = 'late';
            if ($statuses->contains('absent'))  $dots[] = 'absent';
        }

        // 2. Exam, Event, and Holiday dots
        if (isset($eventsMap[$dDateKey])) {
            $eTypes = collect($eventsMap[$dDateKey])->pluck('type')->unique();
            if ($eTypes->contains('exam'))    $dots[] = 'exam';
            if ($eTypes->contains('event'))   $dots[] = 'event';
            if ($eTypes->contains('holiday')) $dots[] = 'holiday';
        }

        if (!empty($dots)) {
            $dayDotsMap[$d] = array_values(array_unique($dots));
        }
    }

    $calendarJson = [];
    foreach ($dayRecordsMap as $day => $recs) {
        $dateKey = \Carbon\Carbon::create($calYear, $calMonth, $day)->format('Y-m-d');
        $dayName = \Carbon\Carbon::parse($dateKey)->format('l');

        $calendarJson[$dateKey] = collect($recs)->map(function($r) use ($dayName) {
            $sched = null;
            if ($r->subject && $r->subject->schedules) {
                $sched = $r->subject->schedules->firstWhere('day', $dayName) 
                      ?? $r->subject->schedules->first();
            }

            $schedStart = $sched ? \Carbon\Carbon::parse($sched->start_time)->format('g:i A') : null;
            $schedEnd   = $sched ? \Carbon\Carbon::parse($sched->end_time)->format('g:i A') : null;
            $schedText  = ($schedStart && $schedEnd) ? "{$schedStart} – {$schedEnd}" : 'Schedule TBA';

            $clockInTime = $r->time_in 
                ? \Carbon\Carbon::parse($r->time_in)->format('g:i A') 
                : ($r->checked_in_at ? $r->checked_in_at->format('g:i A') : null);

            $clockOutTime = $r->time_out 
                ? \Carbon\Carbon::parse($r->time_out)->format('g:i A') 
                : null;

            $status = $r->status ? ucfirst(strtolower($r->status)) : 'Absent';
            $clockInDisplay = $clockInTime ?? 'No clock-in';

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
                'schedule_start' => $schedStart,
                'schedule_end'   => $schedEnd,
                'status'         => $status,
                'status_lower'   => strtolower($status),
                'time_in'        => $clockInTime,
                'clock_in'       => $clockInDisplay,
                'time_out'       => $clockOutTime,
                'remarks'        => $remarks,
            ];
        })->values()->toArray();
    }

    $allRecordsJson = isset($records) ? $records->map(function($r) {
        $dayName = \Carbon\Carbon::parse($r->date)->format('l');
        $sched = null;
        if ($r->subject && $r->subject->schedules) {
            $sched = $r->subject->schedules->firstWhere('day', $dayName) 
                  ?? $r->subject->schedules->first();
        }

        $schedStart = $sched ? \Carbon\Carbon::parse($sched->start_time)->format('g:i A') : null;
        $schedEnd   = $sched ? \Carbon\Carbon::parse($sched->end_time)->format('g:i A') : null;
        $schedText  = ($schedStart && $schedEnd) ? "{$schedStart} – {$schedEnd}" : 'Schedule TBA';

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

        $dateObj = \Carbon\Carbon::parse($r->date);

        return [
            'id'             => $r->id,
            'date'           => $dateObj->format('Y-m-d'),
            'date_formatted' => $dateObj->format('F j, Y'),
            'date_month'     => $dateObj->format('Y-m'),
            'day_name'       => $dateObj->format('l'),
            'subject'        => $r->subject->name ?? $r->subject_name ?? $r->subject_code ?? 'Class',
            'code'           => $r->subject_code ?? ($r->subject->code ?? ''),
            'instructor'     => $instructorName,
            'schedule'       => $schedText,
            'status'         => $status,
            'status_lower'   => strtolower($status),
            'clock_in'       => $clockInDisplay,
            'clock_out'      => $clockOutTime ?? '—',
            'remarks'        => $remarks ?? '—',
            'excused'        => (bool) $r->excused,
        ];
    })->values()->toArray() : [];

    $distinctMonths = isset($records) ? $records->map(fn($r) => \Carbon\Carbon::parse($r->date)->format('Y-m'))->unique()->sortDesc() : collect();

    $today     = now()->day;
    $todayFull = now()->toDateString();
    $isCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $startDow  = $calStart->dayOfWeek;

    $noClassDays = [];
    for ($d = 1; $d <= $calEnd->day; $d++) {
        if (\Carbon\Carbon::create($calYear, $calMonth, $d)->dayOfWeek === 0) {
            $noClassDays[] = $d;
        }
    }
    $isLatestMonth = $isCurrentMonth;

    $totalRecords = isset($records) ? $records->count() : 0;
    $greetHour = now()->hour;
    $greeting = $greetHour < 12 ? 'Good Morning' : ($greetHour < 17 ? 'Good Afternoon' : 'Good Evening');
@endphp

@if(session('error'))
<div class="mobile-error-toast" style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:12px;padding:10px 14px;font-size:.82rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;word-break:break-word;">
    <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;"></i><span>{{ session('error') }}</span>
</div>
@endif

<!-- Active Warnings -->
@if(isset($activeWarnings) && $activeWarnings->count() > 0)
<div class="ent-alert ent-fade-up mobile-warning-section">
    <div class="warning-header d-flex align-items-center gap-3 mb-3">
        <div class="ent-alert-icon warning-icon-box" style="flex-shrink: 0;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div class="ent-alert-body warning-header-body" style="flex: 1; min-width: 0;">
            <div class="ent-alert-title warning-title">Action Required: Attendance Warning</div>
            <div class="ent-alert-text warning-subtitle">You have {{ $activeWarnings->count() }} active warning(s). Please review your attendance immediately.</div>
        </div>
    </div>
    
    <div class="warning-cards-list d-flex flex-column gap-3 mb-3">
        @foreach($activeWarnings as $warning)
        <div class="warning-card-item">
            <div class="warning-card-header">
                <span class="warning-card-code"><i class="bi bi-exclamation-triangle-fill me-1" style="opacity: 0.85;"></i>{{ $warning->subject_code }}</span>
                <span class="warning-card-time"><i class="bi bi-clock me-1"></i>{{ $warning->created_at->diffForHumans() }}</span>
            </div>
            <div class="warning-card-message">{{ $warning->message }}</div>
        </div>
        @endforeach
    </div>
    
    <div class="warning-action-btns d-flex gap-2 flex-wrap">
        <a href="{{ route('excuses') }}" class="ent-btn ent-btn-primary warning-btn-submit">
            <i class="bi bi-file-text-fill me-1"></i> Submit Excuse
        </a>
        <a href="{{ route('attendance.records') }}" class="ent-btn ent-btn-secondary warning-btn-records">
            <i class="bi bi-journal-text me-1"></i> View Records
        </a>
    </div>
</div>
@endif

<style>
    /* ══════════════════════════════════════════════════════════════
       ATTENDANCE WARNING SECTION (Responsive Mobile & Desktop)
       ══════════════════════════════════════════════════════════════ */
    .mobile-warning-section {
        background: linear-gradient(145deg, rgba(239, 68, 68, 0.12) 0%, rgba(20, 10, 8, 0.9) 100%) !important;
        border: 1.5px solid rgba(239, 68, 68, 0.32) !important;
        border-radius: 18px !important;
        margin-bottom: 20px !important;
        padding: 20px !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35), 0 0 20px rgba(239, 68, 68, 0.08) !important;
        box-sizing: border-box !important;
        width: 100% !important;
        max-width: 100% !important;
        overflow: hidden !important;
    }

    .mobile-warning-section .warning-icon-box {
        background: rgba(239, 68, 68, 0.18) !important;
        color: #f87171 !important;
        border: 1px solid rgba(239, 68, 68, 0.25) !important;
        border-radius: 12px !important;
    }

    .mobile-warning-section .warning-title {
        color: #fca5a5 !important;
        font-weight: 700 !important;
        font-size: 1.05rem !important;
        letter-spacing: -0.01em !important;
        margin-bottom: 4px !important;
        overflow-wrap: break-word !important;
        word-wrap: break-word !important;
        word-break: normal !important;
    }

    .mobile-warning-section .warning-subtitle {
        color: #b39b82 !important;
        font-size: 0.82rem !important;
        line-height: 1.45 !important;
        margin: 0 !important;
        overflow-wrap: break-word !important;
        word-wrap: break-word !important;
        word-break: normal !important;
    }

    .mobile-warning-section .warning-card-item {
        background: rgba(0, 0, 0, 0.45) !important;
        padding: 14px !important;
        border-radius: 12px !important;
        border: 1px solid rgba(239, 68, 68, 0.22) !important;
        box-sizing: border-box !important;
        width: 100% !important;
        transition: all 0.2s ease !important;
    }

    .mobile-warning-section .warning-card-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        gap: 6px !important;
        margin-bottom: 8px !important;
        width: 100% !important;
    }

    .mobile-warning-section .warning-card-code {
        color: #fca5a5 !important;
        font-weight: 700 !important;
        font-size: 0.84rem !important;
        background: rgba(239, 68, 68, 0.15) !important;
        border: 1px solid rgba(239, 68, 68, 0.3) !important;
        padding: 2px 8px !important;
        border-radius: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
    }

    .mobile-warning-section .warning-card-time {
        color: #b39b82 !important;
        font-size: 0.72rem !important;
        font-weight: 500 !important;
    }

    .mobile-warning-section .warning-card-message {
        font-size: 0.85rem !important;
        color: #f3e7cd !important;
        line-height: 1.55 !important;
        margin: 0 !important;
        overflow-wrap: break-word !important;
        word-wrap: break-word !important;
        word-break: normal !important;
        hyphens: none !important;
    }

    .mobile-warning-section .warning-btn-submit {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
        color: #ffffff !important;
        border: 1px solid rgba(239, 68, 68, 0.5) !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-decoration: none !important;
        touch-action: manipulation !important;
        box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35) !important;
        transition: all 0.2s ease !important;
    }

    .mobile-warning-section .warning-btn-records {
        background: rgba(239, 68, 68, 0.08) !important;
        color: #fca5a5 !important;
        border: 1px solid rgba(239, 68, 68, 0.32) !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-decoration: none !important;
        touch-action: manipulation !important;
        transition: all 0.2s ease !important;
    }

    /* Desktop layout (>= 992px): Multi-column with ample space */
    @media (min-width: 992px) {
        .mobile-warning-section {
            display: flex !important;
            flex-direction: row !important;
            align-items: flex-start !important;
            gap: 20px !important;
            padding: 22px 24px !important;
        }
        .mobile-warning-section .warning-header {
            flex: 0 0 280px !important;
            max-width: 320px !important;
            margin-bottom: 0 !important;
        }
        .mobile-warning-section .warning-cards-list {
            flex: 1 1 auto !important;
            min-width: 0 !important;
            margin-bottom: 0 !important;
        }
        .mobile-warning-section .warning-action-btns {
            flex: 0 0 170px !important;
            flex-direction: column !important;
            gap: 10px !important;
            margin-top: 0 !important;
        }
        .mobile-warning-section .warning-action-btns > a {
            width: 100% !important;
            min-height: 42px !important;
            padding: 10px 14px !important;
        }
    }

    /* Mobile & Tablet layout (< 992px): Single-column full-width stacked */
    @media (max-width: 991.98px) {
        .mobile-warning-section {
            display: flex !important;
            flex-direction: column !important;
            gap: 14px !important;
            padding: 16px 14px !important;
            margin-bottom: 16px !important;
            border-radius: 16px !important;
        }
        .mobile-warning-section .warning-header {
            display: flex !important;
            flex-direction: row !important;
            align-items: flex-start !important;
            gap: 12px !important;
            width: 100% !important;
            margin-bottom: 0 !important;
        }
        .mobile-warning-section .warning-icon-box {
            width: 42px !important;
            height: 42px !important;
            min-width: 42px !important;
            font-size: 1.2rem !important;
            flex-shrink: 0 !important;
        }
        .mobile-warning-section .warning-header-body {
            flex: 1 1 auto !important;
            min-width: 0 !important;
        }
        .mobile-warning-section .warning-title {
            font-size: 0.98rem !important;
            line-height: 1.35 !important;
        }
        .mobile-warning-section .warning-subtitle {
            font-size: 0.78rem !important;
            line-height: 1.45 !important;
        }
        .mobile-warning-section .warning-cards-list {
            display: flex !important;
            flex-direction: column !important;
            gap: 10px !important;
            width: 100% !important;
            margin-bottom: 0 !important;
        }
        .mobile-warning-section .warning-card-item {
            width: 100% !important;
            padding: 13px 14px !important;
            border-radius: 12px !important;
        }
        .mobile-warning-section .warning-card-message {
            font-size: 0.85rem !important;
            line-height: 1.55 !important;
        }
        .mobile-warning-section .warning-action-btns {
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
            width: 100% !important;
            margin-top: 2px !important;
        }
        .mobile-warning-section .warning-action-btns > a {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            min-width: 0 !important;
            min-height: 44px !important;
            padding: 11px 16px !important;
            font-size: 0.85rem !important;
            font-weight: 700 !important;
            border-radius: 12px !important;
            box-sizing: border-box !important;
            text-align: center !important;
            white-space: normal !important;
        }
    }

    @media (max-width: 768px) {
        /* Hero banner compact */
        .hero-banner { padding: 14px !important; border-radius: 14px !important; box-shadow: none !important; }
        .hero-banner h1 { font-size: 1.2rem !important; margin-bottom: 4px !important; }

        /* Subject stat cards compact */
        .subject-stat-card { padding: 14px !important; box-shadow: none !important; border-radius: 12px !important; }
        .subject-stat-card .rate-text { font-size: 1.35rem !important; }
        .subject-stat-card .bg-glow { display: none !important; }

        /* Calendar compact */
        .att-cal-wrap { border-radius: 12px !important; border: 1px solid rgba(255,255,255,0.03) !important; background: rgba(0,0,0,0.08) !important; }
        .att-cal-grid { padding: 0 6px 10px !important; gap: 2px !important; }
        .att-cal-cell { border-radius: 6px !important; }
        .att-cal-stats { padding: 8px 6px !important; }
        .att-cal-stat { border: none !important; padding: 3px 6px !important; background: transparent !important; }

        /* Hero card CTA compact */
        .premium-hero-card .d-md-none.d-flex { flex-direction: column !important; gap: 8px !important; }
        .premium-hero-card .d-md-none .btn-modern-primary,
        .premium-hero-card .d-md-none .btn-modern-glass {
            width: 100% !important;
            justify-content: center !important;
            padding: 10px 14px !important;
            font-size: 0.82rem !important;
            min-height: 44px !important;
        }

        /* Error toast compact */
        .mobile-error-toast { font-size: 0.78rem !important; padding: 10px 12px !important; margin-bottom: 12px !important; }
    }

    /* Small screens (<= 360px) */
    @media (max-width: 360px) {
        .mobile-warning-section { padding: 12px 10px !important; border-radius: 14px !important; }
        .mobile-warning-section .warning-title { font-size: 0.9rem !important; }
        .mobile-warning-section .warning-subtitle { font-size: 0.74rem !important; }
        .mobile-warning-section .warning-card-item { padding: 10px 10px !important; }
        .mobile-warning-section .warning-card-message { font-size: 0.8rem !important; }
    }

    /* ══════════════════════════════════════════════════════════════
       STUDENT HERO DASHBOARD & QUICK NAV (Modern & Minimalist)
       ══════════════════════════════════════════════════════════════ */
    html {
        scroll-behavior: smooth;
    }

    .student-hero-banner {
        background: linear-gradient(135deg, rgba(32, 14, 11, 0.94) 0%, rgba(18, 10, 8, 0.98) 60%, rgba(26, 14, 10, 0.94) 100%) !important;
        border: 1px solid rgba(207, 164, 111, 0.22) !important;
        border-radius: 22px !important;
        padding: 20px 24px !important;
        position: relative !important;
        overflow: hidden !important;
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
        margin-bottom: 20px !important;
    }

    .student-hero-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent 0%, rgba(255, 209, 102, 0.7) 25%, rgba(207, 164, 111, 1) 50%, rgba(255, 209, 102, 0.7) 75%, transparent 100%);
    }

    .student-hero-inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        position: relative;
        z-index: 2;
    }

    .student-hero-identity {
        display: flex;
        align-items: center;
        gap: 16px;
        flex: 1 1 320px;
        min-width: 0;
    }

    .student-hero-avatar-wrap {
        position: relative;
        width: 58px;
        height: 58px;
        min-width: 58px;
        border-radius: 18px;
        padding: 2px;
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.65) 0%, rgba(139, 90, 43, 0.35) 100%);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
        flex-shrink: 0;
    }

    .student-hero-avatar {
        width: 100%;
        height: 100%;
        border-radius: 16px;
        object-fit: cover;
        background: #1a0f0a;
        display: block;
    }

    .student-avatar-pulse {
        position: absolute;
        bottom: 0px;
        right: 0px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #10b981;
        border: 2px solid #1a0f0a;
        box-shadow: 0 0 6px rgba(16, 185, 129, 0.85);
    }

    .student-hero-info {
        flex: 1;
        min-width: 0;
    }

    .student-greeting-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 2px 9px;
        border-radius: 99px;
        background: rgba(207, 164, 111, 0.12);
        border: 1px solid rgba(207, 164, 111, 0.25);
        color: #f3d18e;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .student-hero-name {
        color: #ffffff;
        font-weight: 800;
        margin: 0 0 6px 0;
        font-size: clamp(1.25rem, 2.5vw, 1.85rem);
        line-height: 1.2;
        letter-spacing: -0.02em;
        overflow-wrap: break-word;
        word-break: normal;
    }

    .student-hero-chips {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .student-info-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 8px;
        background: rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #e5d7c4;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1.3;
    }

    .student-info-chip.chip-gold {
        background: rgba(207, 164, 111, 0.12);
        border-color: rgba(207, 164, 111, 0.28);
        color: #ffd166;
    }

    .student-info-chip.chip-status {
        background: rgba(16, 185, 129, 0.12);
        border-color: rgba(16, 185, 129, 0.28);
        color: #6ee7b7;
    }

    .student-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        display: inline-block;
    }

    /* Right side widgets */
    .student-hero-widgets {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
    }

    .student-clock-card {
        background: rgba(0, 0, 0, 0.45);
        border: 1px solid rgba(207, 164, 111, 0.22);
        border-radius: 14px;
        padding: 8px 16px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
        min-width: 170px;
    }

    .student-clock-time {
        color: #ffd166;
        font-size: 1.25rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
        display: flex;
        align-items: center;
        gap: 6px;
        line-height: 1.2;
    }

    .student-clock-date {
        color: #b39b82;
        font-size: 0.75rem;
        font-weight: 500;
        margin-top: 2px;
    }

    .student-hero-badges-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .student-rate-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 11px;
        border-radius: 99px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .student-rate-badge.rate-good {
        background: rgba(16, 185, 129, 0.14);
        border: 1px solid rgba(16, 185, 129, 0.35);
        color: #34d399;
    }

    .student-rate-badge.rate-warning {
        background: rgba(245, 158, 11, 0.14);
        border: 1px solid rgba(245, 158, 11, 0.35);
        color: #fbbf24;
    }

    .student-rate-badge.rate-danger {
        background: rgba(239, 68, 68, 0.14);
        border: 1px solid rgba(239, 68, 68, 0.35);
        color: #f87171;
    }

    .student-streak-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 99px;
        background: rgba(245, 158, 11, 0.12);
        border: 1px solid rgba(245, 158, 11, 0.25);
        color: #fbbf24;
        font-size: 0.74rem;
        font-weight: 700;
    }

    /* ══════════════════════════════════════════════════════════════
       QUICK NAVIGATION BAR (Easy to Navigate Anchor Strip)
       ══════════════════════════════════════════════════════════════ */
    .student-quick-nav-wrap {
        position: sticky;
        top: 68px;
        z-index: 100;
        margin-bottom: 24px;
    }
    .student-quick-nav {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding: 6px 8px;
        background: rgba(15, 10, 8, 0.9);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1px solid rgba(207, 164, 111, 0.2);
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }
    .student-quick-nav::-webkit-scrollbar {
        display: none;
    }
    .student-quick-nav-item {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 16px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        color: #b39b82;
        font-size: 0.82rem;
        font-weight: 700;
        white-space: nowrap;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .student-quick-nav-item:hover,
    .student-quick-nav-item:focus {
        background: rgba(207, 164, 111, 0.15);
        border-color: rgba(207, 164, 111, 0.35);
        color: #ffd166;
        transform: translateY(-1px);
    }
    .student-quick-nav-item i {
        font-size: 0.92rem;
        color: #cfa46f;
    }

    /* ══════════════════════════════════════════════════════════════
       TODAY'S SCHEDULE (Modern Minimalist Timeline Cards)
       ══════════════════════════════════════════════════════════════ */
    .schedule-items-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .schedule-modern-card {
        background: rgba(0, 0, 0, 0.28);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 14px;
        padding: 14px 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        transition: all 0.2s ease;
    }
    .schedule-modern-card:hover {
        background: rgba(255, 255, 255, 0.02);
        border-color: rgba(207, 164, 111, 0.2);
        transform: translateX(2px);
    }
    .schedule-main-info {
        flex: 1;
        min-width: 0;
    }
    .schedule-subject-title {
        font-weight: 800;
        color: #f3ede4;
        font-size: 1.05rem;
        line-height: 1.3;
        overflow-wrap: break-word;
        word-break: normal;
    }
    .schedule-meta-row {
        color: #b39b82;
        font-size: 0.82rem;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .schedule-time-badge {
        color: #f3ede4;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
    }
    .schedule-code-badge {
        background: rgba(207, 164, 111, 0.1);
        border: 1px solid rgba(207, 164, 111, 0.2);
        color: #ffd166;
        padding: 2px 7px;
        border-radius: 6px;
        font-size: 0.74rem;
        font-weight: 700;
    }
    .schedule-instructor-badge {
        color: #8f826f;
        font-size: 0.78rem;
    }
    .schedule-status-col {
        flex-shrink: 0;
    }
    .schedule-empty-state {
        text-align: center;
        padding: 36px 16px;
    }

    /* ══════════════════════════════════════════════════════════════
       SUBJECT BREAKDOWN (Minimalist 2-Column Modern Grid)
       ══════════════════════════════════════════════════════════════ */
    .subject-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 14px;
    }
    .subject-minimal-card {
        background: rgba(17, 9, 6, 0.65);
        border: 1px solid rgba(207, 164, 111, 0.16);
        border-radius: 16px;
        padding: 16px 18px;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    }
    .subject-minimal-card:hover {
        transform: translateY(-2px);
        border-color: rgba(207, 164, 111, 0.38);
        background: rgba(22, 12, 8, 0.85);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
    }
    .subject-minimal-title {
        font-weight: 800;
        color: #f3ede4;
        font-size: 1.02rem;
        line-height: 1.25;
        overflow-wrap: break-word;
        word-break: normal;
    }
    .subject-minimal-meta {
        font-size: 0.78rem;
        color: #b39b82;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .subject-minimal-code {
        background: rgba(207, 164, 111, 0.12);
        border: 1px solid rgba(207, 164, 111, 0.25);
        color: #ffd166;
        padding: 2px 7px;
        border-radius: 6px;
        font-weight: 700;
    }
    .subject-minimal-rate {
        font-size: 1.5rem;
        font-weight: 900;
        line-height: 1;
    }
    .subject-at-risk-tag {
        font-size: 0.68rem;
        color: #f87171;
        font-weight: 700;
        margin-top: 4px;
        display: inline-flex;
        align-items: center;
        background: rgba(248, 113, 113, 0.14);
        border: 1px solid rgba(248, 113, 113, 0.3);
        padding: 2px 8px;
        border-radius: 99px;
    }
    .subject-minimal-progress-track {
        height: 5px;
        background: rgba(255, 255, 255, 0.06);
        border-radius: 99px;
        overflow: hidden;
        margin: 12px 0;
    }
    .subject-minimal-progress-fill {
        height: 100%;
        border-radius: 99px;
        transition: width 1s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .subject-minimal-pills {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .sub-pill {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 99px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .sub-pill.pill-present {
        color: #4ade80;
        background: rgba(74, 222, 128, 0.1);
        border: 1px solid rgba(74, 222, 128, 0.22);
    }
    .sub-pill.pill-late {
        color: #fbbf24;
        background: rgba(251, 191, 36, 0.1);
        border: 1px solid rgba(251, 191, 36, 0.22);
    }
    .sub-pill.pill-absent {
        color: #f87171;
        background: rgba(248, 113, 113, 0.1);
        border: 1px solid rgba(248, 113, 113, 0.22);
    }
    .sub-pill.pill-excused {
        color: #60a5fa;
        background: rgba(96, 165, 250, 0.1);
        border: 1px solid rgba(96, 165, 250, 0.22);
    }

    /* ══════════════════════════════════════════════════════════════
       CALENDAR MINIMALIST LEGEND STRIP
       ══════════════════════════════════════════════════════════════ */
    .scal-legend-strip {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
        padding: 14px 18px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
    }
    .scal-legend-items {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    .scal-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #f3ede4;
    }
    .scal-legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    .scal-legend-tip {
        font-size: 0.78rem;
        color: #b39b82;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        /* Container and spacing flow */
        .page-enter {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* Hero banner compact */
        .student-hero-banner {
            padding: 13px 14px !important;
            border-radius: 16px !important;
            margin-bottom: 10px !important;
        }
        .student-hero-inner {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 8px !important;
        }
        .student-hero-identity {
            gap: 10px !important;
            flex: 1 1 100% !important;
        }
        .student-hero-avatar-wrap {
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            border-radius: 12px !important;
        }
        .student-hero-avatar {
            border-radius: 10px !important;
        }
        .student-hero-name {
            font-size: 1.15rem !important;
            margin-bottom: 2px !important;
        }
        .student-greeting-pill {
            font-size: 0.72rem !important;
            margin-bottom: 2px !important;
        }
        .student-hero-chips {
            gap: 4px !important;
        }
        .student-info-chip {
            font-size: 0.72rem !important;
            padding: 2px 7px !important;
            border-radius: 6px !important;
        }
        .student-hero-widgets {
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
            width: 100% !important;
            gap: 8px !important;
        }
        .student-clock-card {
            padding: 4px 10px !important;
            min-width: 0 !important;
            border-radius: 10px !important;
            align-items: flex-start !important;
        }
        .student-clock-time {
            font-size: 0.95rem !important;
        }
        .student-clock-date {
            font-size: 0.68rem !important;
        }
        .student-hero-badges-row {
            justify-content: flex-end !important;
            gap: 6px !important;
        }
        .student-rate-badge,
        .student-streak-badge {
            padding: 3px 8px !important;
            font-size: 0.7rem !important;
        }

        /* Quick Stats KPI Grid */
        #realStats, #skelStats {
            gap: 8px !important;
            margin-bottom: 10px !important;
        }
        #realStats .ent-kpi-card {
            padding: 10px 12px !important;
            border-radius: 12px !important;
        }
        #realStats .ent-kpi-icon {
            width: 32px !important;
            height: 32px !important;
            font-size: 0.95rem !important;
            margin-bottom: 6px !important;
            border-radius: 8px !important;
        }
        #realStats .ent-kpi-label {
            font-size: 0.66rem !important;
            margin-bottom: 2px !important;
        }
        #realStats .ent-kpi-value {
            font-size: 1.35rem !important;
        }

        /* Quick Navigation Bar */
        .student-quick-nav-wrap {
            position: relative !important;
            top: 0 !important;
            margin-bottom: 10px !important;
        }
        .student-quick-nav {
            padding: 4px 6px !important;
            gap: 6px !important;
            border-radius: 14px !important;
        }
        .student-quick-nav-item {
            padding: 6px 12px !important;
            font-size: 0.78rem !important;
            border-radius: 10px !important;
            gap: 5px !important;
        }

        /* Today's Schedule Card */
        #todayScheduleSection {
            margin-bottom: 8px !important;
        }
        #todayScheduleSection .adm-card,
        #todayScheduleSection article {
            border-radius: 16px !important;
        }
        #todayScheduleSection .adm-card-head {
            padding: 12px 14px !important;
        }
        #todayScheduleSection .adm-card-title {
            font-size: 0.95rem !important;
            gap: 8px !important;
        }
        #todayScheduleSection .adm-card-icon {
            width: 30px !important;
            height: 30px !important;
            border-radius: 8px !important;
            font-size: 0.85rem !important;
        }
        #todayScheduleSection div[style*="padding: 22px 24px"],
        #todayScheduleSection div[style*="padding: 28px 32px"] {
            padding: 12px 14px !important;
        }
        #todayScheduleSection .schedule-items-grid {
            gap: 8px !important;
        }
        #todayScheduleSection .schedule-modern-card {
            padding: 12px 14px !important;
            border-radius: 12px !important;
            gap: 10px !important;
        }
        #todayScheduleSection .schedule-subject-title {
            font-size: 0.95rem !important;
            line-height: 1.25 !important;
        }
        #todayScheduleSection .schedule-meta-row {
            font-size: 0.76rem !important;
            gap: 6px !important;
            margin-top: 4px !important;
        }
        #todayScheduleSection .schedule-time-badge {
            font-size: 0.76rem !important;
        }
        #todayScheduleSection .schedule-code-badge {
            font-size: 0.7rem !important;
            padding: 1px 6px !important;
        }
        #todayScheduleSection .schedule-instructor-badge {
            font-size: 0.74rem !important;
        }
        #todayScheduleSection .schedule-empty-state {
            padding: 22px 14px !important;
        }

        /* Calendar (when loaded in modals or pages) */
        .scal-card {
            padding: 14px 12px !important;
            border-radius: 16px !important;
        }
        .scal-legend-strip {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 8px !important;
            padding: 10px 12px !important;
        }
        .scal-legend-items {
            gap: 8px 12px !important;
        }

        /* Bootstrap utility overrides on mobile */
        .row.g-4 {
            --bs-gutter-y: 0.75rem !important;
            --bs-gutter-x: 0.75rem !important;
        }
        .mb-4 {
            margin-bottom: 0.75rem !important;
        }
    }
</style>

<!-- Hero Banner -->
<div class="student-hero-banner premium-hero-card mb-4">
    <div class="student-hero-inner">
        <!-- Identity: Avatar + Details -->
        <div class="student-hero-identity">
            <div class="student-hero-avatar-wrap">
                <img src="{{ Auth::user()->profile_photo_url_with_version }}" alt="{{ Auth::user()->name }}" class="student-hero-avatar">
                <div class="student-avatar-pulse" title="Active Student"></div>
            </div>
            <div class="student-hero-info">
                <div class="student-greeting-pill">
                    @if(now()->hour < 12)
                        <i class="bi bi-brightness-alt-high-fill text-warning"></i>
                    @elseif(now()->hour < 17)
                        <i class="bi bi-sun-fill" style="color: #fbbf24;"></i>
                    @else
                        <i class="bi bi-moon-stars-fill" style="color: #fbbf24;"></i>
                    @endif
                    <span>{{ $greeting }}</span>
                </div>
                <h1 class="student-hero-name">{{ Auth::user()->name }}</h1>
                <div class="student-hero-chips">
                    @if(!empty(Auth::user()->student_number))
                        <span class="student-info-chip chip-gold" title="Student Identification Number">
                            <i class="bi bi-person-badge-fill text-gold"></i>
                            <span>{{ Auth::user()->student_number }}</span>
                        </span>
                    @endif
                    <span class="student-info-chip" title="Enrolled Degree Program">
                        <i class="bi bi-mortarboard-fill" style="color: #60a5fa;"></i>
                        <span>{{ Auth::user()->course ?? 'BSCS' }}</span>
                    </span>
                    <span class="student-info-chip" title="Academic Standing">
                        <i class="bi bi-layers-fill" style="color: #a78bfa;"></i>
                        <span>Year {{ Auth::user()->year_level ?? '1' }} • Sem {{ Auth::user()->semester ?? '1' }}</span>
                    </span>
                    @if(!empty(Auth::user()->section))
                        <span class="student-info-chip" title="Class Section">
                            <i class="bi bi-people-fill" style="color: #34d399;"></i>
                            <span>Sec {{ Auth::user()->section }}</span>
                        </span>
                    @endif
                    <span class="student-info-chip chip-status" title="Enrollment Status">
                        <span class="student-status-dot"></span>
                        <span>Active Student</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Right Side: Live Clock & Campus Overview -->
        <div class="student-hero-widgets">
            <div class="student-clock-card hero-clock-pill">
                <div class="student-clock-time hero-clock-time">
                    <i class="bi bi-clock"></i>
                    <span id="studentClock">{{ now()->format('h:i A') }}</span>
                </div>
                <div class="student-clock-date hero-clock-date">{{ now()->format('l, F j, Y') }}</div>
            </div>
            <div class="student-hero-badges-row">
                @if(isset($attendanceRate))
                    @php
                        $rateVal = (float) $attendanceRate;
                        $rateClass = $rateVal >= 85 ? 'rate-good' : ($rateVal >= 75 ? 'rate-warning' : 'rate-danger');
                        $rateIcon = $rateVal >= 85 ? 'bi-shield-check' : ($rateVal >= 75 ? 'bi-shield-exclamation' : 'bi-exclamation-triangle-fill');
                    @endphp
                    <span class="student-rate-badge {{ $rateClass }}" title="Overall Attendance Rate: {{ $attendanceRate }}%">
                        <i class="bi {{ $rateIcon }}"></i>
                        <span>{{ $attendanceRate }}% Attendance</span>
                    </span>
                @endif
                @if(isset($streakCount) && $streakCount > 0)
                    <span class="student-streak-badge" title="Consecutive Attendance Streak">
                        <span>🔥 {{ $streakCount }} Day{{ $streakCount > 1 ? 's' : '' }} Streak</span>
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Skeleton Stats -->
<div class="row g-3 mb-4" id="skelStats">
    <div class="col-md-3 col-6"><x-skeleton type="stat" /></div>
    <div class="col-md-3 col-6"><x-skeleton type="stat" /></div>
    <div class="col-md-3 col-6"><x-skeleton type="stat" /></div>
    <div class="col-md-3 col-6"><x-skeleton type="stat" /></div>
</div>

<!-- Quick Stats -->
<div class="ent-grid ent-grid-4 ent-mb-lg ent-fade-up ent-delay-2" id="realStats" style="display:none; gap:16px; margin-bottom:20px;">
    <x-card type="kpi" accent="success" label="Present" value="{{ $totalPresent ?? 0 }}" icon="bi bi-check-circle-fill" />
    <x-card type="kpi" accent="warning" label="Late" value="{{ $totalLate ?? 0 }}" icon="bi bi-clock-fill" />
    <x-card type="kpi" accent="danger" label="Absent" value="{{ $totalAbsent ?? 0 }}" icon="bi bi-x-circle-fill" />
    <x-card type="kpi" accent="gold" label="Subjects" value="{{ isset($subjects) ? count($subjects) : 0 }}" icon="bi bi-book-fill" />
</div>

<!-- Quick Navigation Bar -->
<div class="student-quick-nav-wrap mb-4">
    <div class="student-quick-nav">
        <a href="#todayScheduleSection" class="student-quick-nav-item">
            <i class="bi bi-clock-history"></i>
            <span>Today's Classes</span>
        </a>
        <a href="{{ route('student.attendance.calendar') }}" class="student-quick-nav-item">
            <i class="bi bi-calendar-check-fill"></i>
            <span>Attendance Calendar</span>
        </a>
        <button type="button" class="student-quick-nav-item" onclick="openSubjectBreakdownModal()" title="View Subject Attendance Breakdown">
            <i class="bi bi-bar-chart-fill"></i>
            <span>Subject Breakdown</span>
        </button>
        <button type="button" class="student-quick-nav-item" onclick="openAttendanceRecordsModal()" title="View Complete Attendance Records">
            <i class="bi bi-journal-text"></i>
            <span>View Records</span>
        </button>
    </div>
</div>

<!-- Today's Schedule -->
<div class="row g-4 mb-4" id="todayScheduleSection">
    <div class="col-12">
        <x-card title="Today's Schedule" icon="bi bi-clock-history">
            <x-slot name="headerActions">
                <a href="{{ route('student.schedule') }}" class="btn btn-outline btn-sm" style="border-radius:10px; font-size:0.78rem; font-weight:700; color:#ffd166; border-color:rgba(255,209,102,0.35);">
                    <i class="bi bi-calendar3 me-1"></i> Full Schedule
                </a>
            </x-slot>
            @if(isset($todaySchedule) && $todaySchedule->count() > 0)
                <div class="schedule-items-grid">
                @foreach($todaySchedule as $item)
                    @php
                        $statusColor = $item->status === 'completed' ? '#4ade80' : ($item->status === 'ongoing' ? '#fbbf24' : ($item->status === 'missed' ? '#f87171' : 'var(--gold)'));
                    @endphp
                    <div class="schedule-modern-card" style="border-left: 3.5px solid {{ $statusColor }};">
                        <div class="schedule-main-info">
                            <div class="schedule-subject-title">{{ $item->subject->name }}</div>
                            <div class="schedule-meta-row">
                                <span class="schedule-time-badge">
                                    <i class="bi bi-clock-fill me-1" style="color: #cfa46f; font-size: 0.75rem;"></i>
                                    {{ $item->start_time->format('g:i A') }} – {{ $item->end_time->format('g:i A') }}
                                </span>
                                <span class="schedule-code-badge">{{ $item->subject->code }}</span>
                                @if(!empty($item->subject->instructorUser?->name ?? $item->subject->instructor))
                                    <span class="schedule-instructor-badge">
                                        <i class="bi bi-person-fill text-gold-muted me-1"></i>{{ $item->subject->instructorUser?->name ?? $item->subject->instructor }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="schedule-status-col">
                            @if($item->status === 'completed') <x-badge type="present">Done</x-badge>
                            @elseif($item->status === 'ongoing') <x-badge type="late">Now</x-badge>
                            @elseif($item->status === 'missed') <x-badge type="absent">Missed</x-badge>
                            @else <x-badge type="info">Later</x-badge>
                            @endif
                        </div>
                    </div>
                @endforeach
                </div>
            @else
                <div class="schedule-empty-state">
                    <i class="bi bi-calendar-x" style="font-size: 2.4rem; color: #b39b82; opacity: 0.45;"></i>
                    <p style="color: #b39b82; font-size: 0.95rem; margin-top: 12px; font-weight: 600;">No classes scheduled today</p>
                </div>
            @endif
        </x-card>
    </div>
</div>

<style>
    /* ─── Squircle Attendance Calendar ─────────────────────────── */
    .scal-card {
        background: #0f0a08;
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 28px;
        padding: 28px 30px;
        width: 100%;
        box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7);
    }
    .scal-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    .scal-nav-btn {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #cfa46f;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
    }
    .scal-nav-btn:hover {
        background: rgba(207, 164, 111, 0.15);
        border-color: rgba(207, 164, 111, 0.4);
        color: #fff;
        transform: translateY(-2px);
    }
    .scal-nav-title {
        font-size: 1.35rem;
        font-weight: 800;
        color: #fdfbf7;
        letter-spacing: -0.02em;
    }
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
    .scal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
    }
    .scal-tile {
        height: 56px;
        min-height: 56px;
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
    .scal-num {
        font-size: 1.05rem;
        font-weight: 700;
        color: #f3ede4;
        line-height: 1;
    }
    .scal-tile.sunday .scal-num { color: #f87171; }
    .scal-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        opacity: 0.9;
    }
    .scal-dots-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 3px;
        height: 6px;
    }
    .scal-dot.dot-present { background: #10b981; }
    .scal-dot.dot-late    { background: #f59e0b; }
    .scal-dot.dot-absent  { background: #ef4444; }
    .scal-dot.dot-exam    { background: #ec4899; }
    .scal-dot.dot-event   { background: #8b5cf6; }
    .scal-dot.dot-holiday { background: #4ade80; }

    /* Selected Tile: Rounded glowing gold border matching reference screenshot tile 14 */
    .scal-tile.selected {
        border: 2px solid #ffd166 !important;
        box-shadow: 0 0 16px rgba(255, 209, 102, 0.35), inset 0 0 10px rgba(255, 209, 102, 0.08) !important;
        background: rgba(255, 209, 102, 0.07) !important;
    }
    .scal-tile.selected .scal-num {
        color: #ffd166 !important;
        font-weight: 800;
    }
    .scal-tile.today:not(.selected) {
        border: 1px dashed rgba(255, 209, 102, 0.5) !important;
    }
    .scal-tile.today:not(.selected) .scal-num {
        color: #ffd166;
    }

    .scal-tile.status-present {
        background: rgba(6, 78, 59, 0.4) !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
    }
    .scal-tile.status-late {
        background: rgba(120, 80, 20, 0.4) !important;
        border-color: rgba(245, 158, 11, 0.3) !important;
    }
    .scal-tile.status-absent {
        background: rgba(100, 15, 15, 0.5) !important;
        border-color: rgba(239, 68, 68, 0.35) !important;
    }
    .scal-tile.status-mixed {
        background: rgba(255, 255, 255, 0.04) !important;
        border-color: rgba(207, 164, 111, 0.25) !important;
    }
    .scal-tile.status-event {
        background: rgba(60, 25, 120, 0.45) !important;
        border-color: rgba(139, 92, 246, 0.35) !important;
    }
    .scal-tile.status-holiday {
        background: rgba(5, 60, 50, 0.45) !important;
        border-color: rgba(74, 222, 128, 0.35) !important;
    }
    .scal-tile.status-exam {
        background: rgba(100, 20, 60, 0.5) !important;
        border-color: rgba(236, 72, 153, 0.35) !important;
    }

    .scal-view-records-btn {
        border-radius: 12px;
        padding: 6px 14px;
        font-weight: 700;
        font-size: 0.82rem;
        color: #ffd166;
        border: 1px solid rgba(255, 209, 102, 0.35);
        background: rgba(255, 209, 102, 0.06);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
    }
    .scal-view-records-btn:hover {
        background: rgba(255, 209, 102, 0.16);
        border-color: #ffd166;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(255, 209, 102, 0.2);
    }

    .scal-modal-nav-btn {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #cfa46f;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .scal-modal-nav-btn:hover {
        background: rgba(207, 164, 111, 0.2);
        color: #ffd166;
        border-color: rgba(207, 164, 111, 0.4);
    }

    /* ═══════════════════════════════════════════════════════════
       LUXURY DAY SUMMARY INSPECTOR MODAL & CARDS
       ═══════════════════════════════════════════════════════════ */
    #daySummaryModal,
    #attendanceRecordsModal,
    #subjectBreakdownModal {
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
        .scal-card { padding: 18px 14px; border-radius: 20px; }
        .scal-nav { margin-bottom: 14px; }
        .scal-nav-btn { width: 38px; height: 38px; font-size: 1rem; border-radius: 10px; }
        .scal-nav-title { font-size: 1.15rem; }
        .scal-weekdays { gap: 4px; padding: 10px 0; border-radius: 10px; margin-bottom: 8px; }
        .scal-wd { font-size: 0.72rem; }
        .scal-grid { gap: 5px; }
        .scal-tile { height: 48px; min-height: 48px; border-radius: 10px; }
        .scal-num { font-size: 0.95rem; }
        .scal-dot { width: 4px; height: 4px; }

        /* Responsive Mobile Bottom Sheet for Modals */
        #daySummaryModal .modal-dialog,
        #attendanceRecordsModal .modal-dialog {
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
        #daySummaryModal .modal-content,
        #attendanceRecordsModal .modal-content {
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            border-top-left-radius: 28px !important;
            border-top-right-radius: 28px !important;
            max-height: 88vh !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            padding-bottom: calc(24px + env(safe-area-inset-bottom, 16px)) !important;
            border-bottom: none !important;
            width: 100% !important;
        }
        .day-modal-sheet-handle {
            display: block !important;
        }
    }

    .subject-minimal-card {
        border-color: rgba(255, 255, 255, 0.06);
    }
    .subject-minimal-card:hover {
        background: rgba(255, 255, 255, 0.035) !important;
        border-color: rgba(207, 164, 111, 0.3) !important;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.45);
    }
</style>


{{-- ── Subject Attendance Breakdown Modal ─────────────────────────────── --}}
<div class="modal fade" id="subjectBreakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background:#0f0a08; border:1px solid rgba(255,255,255,0.1); border-radius:24px; box-shadow:0 30px 80px rgba(0,0,0,0.85);">
            {{-- Mobile sheet handle --}}
            <div class="scal-sheet-handle" style="display:none; width:42px; height:4.5px; background:rgba(255,255,255,0.22); border-radius:99px; margin:12px auto 4px auto;"></div>
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center px-4 pt-4 pb-3 border-bottom" style="border-color:rgba(255,255,255,0.06)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:40px; height:40px; border-radius:12px; background:rgba(139,92,246,0.15); border:1px solid rgba(139,92,246,0.3); display:flex; align-items:center; justify-content:center; color:#a78bfa; font-size:1.2rem;">
                        <i class="bi bi-pie-chart-fill"></i>
                    </div>
                    <div>
                        <h3 style="font-weight:800; font-size:1.2rem; color:#f3ede4; margin:0;">Subject Breakdown</h3>
                        <div style="font-size:0.78rem; color:#b39b82; margin-top:2px;">Attendance rate &amp; records per enrolled subject</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge" style="background:rgba(207,164,111,0.12); color:#ffd166; border:1px solid rgba(207,164,111,0.25); font-size:0.78rem; font-weight:700; padding:6px 12px; border-radius:10px;">
                        {{ count($subjectStats ?? []) }} Subjects
                    </span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            {{-- Body --}}
            <div class="px-4 py-4" style="max-height:72vh; overflow-y:auto;">
                @if(empty($subjectStats) || count($subjectStats) === 0)
                    <div style="text-align:center; padding:42px 20px; background:rgba(255,255,255,0.02); border-radius:18px; border:1px dashed rgba(255,255,255,0.08);">
                        <div style="width:52px; height:52px; border-radius:14px; background:rgba(207,164,111,0.1); border:1px solid rgba(207,164,111,0.2); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; color:#ffd166; font-size:1.4rem;">
                            <i class="bi bi-journal-x"></i>
                        </div>
                        <p style="color:#b39b82; font-size:0.9rem; font-weight:600; margin:0;">No enrolled subject records found</p>
                    </div>
                @else
                    <div class="row g-3">
                        @foreach($subjectStats as $sub)
                            @php
                                $brkRateColor = '#10b981';
                                $brkRateBg    = 'rgba(16,185,129,0.12)';
                                $brkRateBorder= 'rgba(16,185,129,0.3)';
                                if ($sub->rate < 75) {
                                    $brkRateColor  = '#ef4444';
                                    $brkRateBg     = 'rgba(239,68,68,0.12)';
                                    $brkRateBorder = 'rgba(239,68,68,0.3)';
                                } elseif ($sub->rate < 90) {
                                    $brkRateColor  = '#f59e0b';
                                    $brkRateBg     = 'rgba(245,158,11,0.12)';
                                    $brkRateBorder = 'rgba(245,158,11,0.3)';
                                }
                            @endphp
                            <div class="col-12 col-md-6">
                                <div class="subject-minimal-card" style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.06); border-radius:18px; padding:18px 20px; height:100%; display:flex; flex-direction:column; justify-content:space-between; transition:all 0.25s cubic-bezier(0.16,1,0.3,1);">
                                    <div>
                                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px;">
                                            <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                                <span style="background:rgba(207,164,111,0.15); color:#ffd166; border:1px solid rgba(207,164,111,0.25); font-size:0.75rem; font-weight:800; padding:3px 8px; border-radius:7px; flex-shrink:0;">{{ $sub->code }}</span>
                                                <span style="font-size:0.92rem; font-weight:700; color:#f3ede4; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $sub->name }}">{{ $sub->name }}</span>
                                            </div>
                                            <span class="badge" style="background:{{ $brkRateBg }}; color:{{ $brkRateColor }}; border:1px solid {{ $brkRateBorder }}; font-size:0.82rem; font-weight:800; padding:4px 10px; border-radius:8px; flex-shrink:0;">{{ $sub->rate }}%</span>
                                        </div>
                                        <div style="height:6px; background:rgba(255,255,255,0.06); border-radius:99px; overflow:hidden; margin-bottom:14px;">
                                            <div style="width:{{ min(100, $sub->rate) }}%; height:100%; background:{{ $brkRateColor }}; border-radius:99px; transition:width 0.6s cubic-bezier(0.16,1,0.3,1);"></div>
                                        </div>
                                    </div>
                                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; padding-top:12px; border-top:1px solid rgba(255,255,255,0.04);">
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <span style="font-size:0.76rem; color:#b39b82;"><strong style="color:#10b981;">{{ $sub->present }}</strong> Present</span>
                                            <span style="font-size:0.76rem; color:#b39b82;"><strong style="color:#f59e0b;">{{ $sub->late }}</strong> Late</span>
                                            <span style="font-size:0.76rem; color:#b39b82;"><strong style="color:#ef4444;">{{ $sub->absent }}</strong> Absent</span>
                                            @if($sub->excused > 0)
                                                <span style="font-size:0.76rem; color:#b39b82;"><strong style="color:#8b5cf6;">{{ $sub->excused }}</strong> Excused</span>
                                            @endif
                                        </div>
                                        <span style="font-size:0.72rem; color:#8f826f; font-weight:600;">{{ $sub->total }} Sessions</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            {{-- Footer --}}
            <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center" style="border-color:rgba(255,255,255,0.06)!important; background:rgba(255,255,255,0.01);">
                <a href="{{ route('student.classes') }}" class="btn btn-sm" style="background:rgba(207,164,111,0.1); border:1px solid rgba(207,164,111,0.3); color:#ffd166; font-weight:700; font-size:0.82rem; border-radius:12px; padding:7px 16px;">
                    <i class="bi bi-book-fill me-1"></i> My Classes
                </a>
                <button type="button" class="btn btn-sm" data-bs-dismiss="modal" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.1); color:#f3ede4; font-weight:700; font-size:0.82rem; border-radius:12px; padding:7px 18px;">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

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
                <div id="daySummaryContent" class="d-flex flex-column gap-3">
                    <!-- dynamically populated with ATTENDANCE and EVENTS / IMPORTANT DATES sections -->
                </div>
                <div style="font-size:0.75rem; color:#8f826f; text-align:center; margin-top:20px; display:flex; align-items:center; justify-content:center; gap:6px;">
                    <i class="bi bi-shield-check text-gold" style="color:#cfa46f;"></i>
                    <span>Real-time records, events, and schedules for this date.</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Attendance Records Modal (Triggered by "View Records" button) ─────────────────────── --}}
<div class="modal fade" id="attendanceRecordsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background:#0f0a08; border:1px solid rgba(255,255,255,0.1); border-radius:24px; box-shadow:0 30px 80px rgba(0,0,0,0.85);">
            <div class="d-flex justify-content-between align-items-center px-4 pt-4 pb-3 border-bottom" style="border-color:rgba(255,255,255,0.06)!important;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:40px; height:40px; border-radius:12px; background:rgba(207,164,111,0.15); border:1px solid rgba(207,164,111,0.3); display:flex; align-items:center; justify-content:center; color:#ffd166; font-size:1.2rem;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h3 style="font-weight:800; font-size:1.25rem; color:#f3ede4; margin:0;">Attendance Records</h3>
                        <div style="font-size:0.8rem; color:#b39b82; margin-top:2px;">
                            Complete attendance history per subject and date
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span id="recordsModalCount" class="badge" style="background:rgba(255,209,102,0.12); color:#ffd166; border:1px solid rgba(255,209,102,0.25); font-size:0.8rem; padding:6px 12px; border-radius:10px;">
                        {{ $totalRecords }} Records
                    </span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            {{-- Filter Toolbar --}}
            <div class="px-4 py-3 border-bottom" style="background:rgba(255,255,255,0.015); border-color:rgba(255,255,255,0.05)!important;">
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <div style="position:relative;">
                            <i class="bi bi-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#8f826f; font-size:0.85rem;"></i>
                            <input type="text" id="recordsSearchInput" oninput="filterAttendanceRecords()" class="form-control form-control-sm" placeholder="Search subject, code, or instructor..." style="background:#140e0c; border:1px solid rgba(255,255,255,0.1); color:#f3ede4; border-radius:12px; padding-left:34px; font-size:0.82rem; height:38px;">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="recordsStatusFilter" onchange="filterAttendanceRecords()" class="form-select form-select-sm" style="background:#140e0c; border:1px solid rgba(255,255,255,0.1); color:#f3ede4; border-radius:12px; font-size:0.82rem; height:38px;">
                            <option value="all">All Statuses</option>
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                            <option value="excused">Excused</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="recordsMonthFilter" onchange="filterAttendanceRecords()" class="form-select form-select-sm" style="background:#140e0c; border:1px solid rgba(255,255,255,0.1); color:#f3ede4; border-radius:12px; font-size:0.82rem; height:38px;">
                            <option value="all">All Months</option>
                            @foreach($distinctMonths as $m)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::createFromFormat('Y-m', $m)->format('F Y') }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Records List Container --}}
            <div class="px-4 py-3" style="min-height:280px; max-height:60vh; overflow-y:auto;">
                <div id="recordsModalList" class="d-flex flex-column gap-2">
                    <!-- Populated via JavaScript -->
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center" style="border-color:rgba(255,255,255,0.06)!important; background:rgba(255,255,255,0.01);">
                <a href="{{ route('attendance.records') }}" class="btn btn-outline-warning btn-sm" style="border-radius:12px; font-weight:700; font-size:0.82rem; padding:7px 16px; border-color:rgba(255,209,102,0.4); color:#ffd166;">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Open Full History Page
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="border-radius:12px; font-weight:700; font-size:0.82rem; padding:7px 18px; background:rgba(255,255,255,0.08); border-color:rgba(255,255,255,0.1);">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
var attCalendarData = @json($calendarJson);
var eventsCalendarData = @json($eventsMap ?? []);
var allAttendanceRecords = @json($allRecordsJson);
let dayModalInstance = null;
let recordsModalInstance = null;
let currentSelectedDateKey = '{{ $calYear }}-{{ str_pad($calMonth, 2, '0', STR_PAD_LEFT) }}-{{ str_pad(($isCurrentMonth && $today ? $today : 14), 2, '0', STR_PAD_LEFT) }}';

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
    document.querySelectorAll('.scal-tile.selected').forEach(el => el.classList.remove('selected'));

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

    const dt = new Date(dateKey + 'T00:00:00');
    const formattedDate = dt.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    if (titleEl) titleEl.textContent = formattedDate;

    const records = attCalendarData[dateKey] || [];
    const dayEvents = (eventsCalendarData && eventsCalendarData[dateKey]) ? eventsCalendarData[dateKey] : [];

    if (records.length === 0 && dayEvents.length === 0) {
        if (subtitleEl) {
            subtitleEl.innerHTML = `
                <span class="day-summary-chip chip-empty">
                    <i class="bi bi-calendar-x text-gold me-1"></i> No records or events
                </span>`;
        }
        if (contentEl) {
            contentEl.innerHTML = `
                <div class="day-empty-card" style="padding: 36px 20px; text-align: center;">
                    <div class="day-empty-icon" style="margin-bottom: 14px;">
                        <i class="bi bi-calendar2-x" style="font-size: 2.4rem; color: #b39b82; opacity: 0.6;"></i>
                    </div>
                    <div style="font-weight:800; font-size:1.05rem; color:#f3ede4; margin-bottom:6px;">No records or events for this date.</div>
                    <div style="font-size:0.82rem; color:#8f826f; max-width:300px; margin:0 auto; line-height:1.5;">There are no attendance records, classes, exams, events, or holidays scheduled for this date.</div>
                </div>`;
        }
    } else {
        // Dynamic summary badges in modal header
        let badgesHtml = '';
        if (records.length > 0) {
            const presentCount = records.filter(r => r.status_lower === 'present').length;
            const lateCount = records.filter(r => r.status_lower === 'late').length;
            const absentCount = records.filter(r => r.status_lower === 'absent').length;

            badgesHtml += `<span class="day-summary-chip chip-count"><i class="bi bi-journal-bookmark-fill me-1" style="color:#cfa46f;"></i> ${records.length} ${records.length === 1 ? 'Subject' : 'Subjects'}</span>`;
            if (presentCount > 0) {
                badgesHtml += `<span class="day-summary-chip chip-present"><i class="bi bi-check-circle-fill me-1"></i> ${presentCount} Present</span>`;
            }
            if (lateCount > 0) {
                badgesHtml += `<span class="day-summary-chip chip-late"><i class="bi bi-clock-fill me-1"></i> ${lateCount} Late</span>`;
            }
            if (absentCount > 0) {
                badgesHtml += `<span class="day-summary-chip chip-absent"><i class="bi bi-x-circle-fill me-1"></i> ${absentCount} Absent</span>`;
            }
        }

        if (dayEvents.length > 0) {
            const holidays = dayEvents.filter(e => e.type === 'holiday');
            const exams = dayEvents.filter(e => e.type === 'exam');
            const events = dayEvents.filter(e => e.type !== 'holiday' && e.type !== 'exam');

            if (holidays.length > 0) {
                badgesHtml += `<span class="day-summary-chip" style="background:rgba(74,222,128,0.15); color:#4ade80; border:1px solid rgba(74,222,128,0.3);"><i class="bi bi-flag-fill me-1"></i> ${holidays.length === 1 ? 'Holiday' : holidays.length + ' Holidays'}</span>`;
            }
            if (exams.length > 0) {
                badgesHtml += `<span class="day-summary-chip" style="background:rgba(236,72,153,0.15); color:#ec4899; border:1px solid rgba(236,72,153,0.3);"><i class="bi bi-award-fill me-1"></i> ${exams.length === 1 ? 'Exam' : exams.length + ' Exams'}</span>`;
            }
            if (events.length > 0) {
                badgesHtml += `<span class="day-summary-chip" style="background:rgba(139,92,246,0.15); color:#c4b5fd; border:1px solid rgba(139,92,246,0.3);"><i class="bi bi-calendar-event-fill me-1"></i> ${events.length === 1 ? 'Event' : events.length + ' Events'}</span>`;
            }
        }
        if (subtitleEl) subtitleEl.innerHTML = badgesHtml;

        let fullHtml = '';

        // 1. ATTENDANCE SECTION
        if (records.length > 0) {
            fullHtml += `
                <div class="day-section-header mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="day-section-icon"><i class="bi bi-journal-check"></i></span>
                        <span class="day-section-label">ATTENDANCE</span>
                    </div>
                    <span class="day-section-badge">${records.length} ${records.length === 1 ? 'Subject' : 'Subjects'}</span>
                </div>
                <div class="d-flex flex-column gap-3 mb-4">
            `;

            records.forEach(r => {
                const st = (r.status_lower || 'absent');
                let iconClass = 'bi-x-circle-fill';
                let clockInColor = '#f87171';
                let borderStatusClass = 'status-border-absent';

                if (st === 'present') {
                    iconClass = 'bi-check-circle-fill';
                    clockInColor = '#34d399';
                    borderStatusClass = 'status-border-present';
                } else if (st === 'late') {
                    iconClass = 'bi-clock-fill';
                    clockInColor = '#fbbf24';
                    borderStatusClass = 'status-border-late';
                }

                const clockInText = r.clock_in || (st === 'absent' ? 'No clock-in' : '—');
                const isNoClockIn = clockInText.toLowerCase().includes('no clock-in');

                fullHtml += `
                    <div class="att-detail-card ${borderStatusClass}">
                        <div class="att-card-header">
                            <div style="flex:1; min-width:180px;">
                                <div class="att-card-subject-title">${escapeHtml(r.subject)}</div>
                                <div class="att-card-meta">
                                    <span class="att-code-pill">${escapeHtml(r.code)}</span>
                                    <span style="color:rgba(255,255,255,0.2);">•</span>
                                    <span class="att-instructor-pill"><i class="bi bi-person-fill text-gold-muted me-1"></i>${escapeHtml(r.instructor)}</span>
                                </div>
                            </div>
                            <span class="att-status-badge ${st}">
                                <i class="bi ${iconClass}"></i> ${escapeHtml(r.status)}
                            </span>
                        </div>

                        <div class="att-info-grid">
                            <div>
                                <div class="att-grid-col-label">
                                    <i class="bi bi-calendar3 text-gold"></i> Schedule
                                </div>
                                <div class="att-grid-col-val">${escapeHtml(r.schedule)}</div>
                            </div>
                            <div>
                                <div class="att-grid-col-label">
                                    <i class="bi bi-fingerprint text-gold"></i> Actual Clock-in
                                </div>
                                <div class="att-grid-col-val" style="color:${isNoClockIn ? '#f87171' : clockInColor};">
                                    ${isNoClockIn ? '<i class="bi bi-x-circle me-1"></i>' : (st === 'present' ? '<i class="bi bi-check2 me-1"></i>' : '<i class="bi bi-clock-history me-1"></i>')}
                                    ${escapeHtml(clockInText)}
                                </div>
                            </div>
                            ${r.time_out ? `
                            <div>
                                <div class="att-grid-col-label">
                                    <i class="bi bi-box-arrow-right text-gold"></i> Clock-out
                                </div>
                                <div class="att-grid-col-val" style="color:#b39b82;">${escapeHtml(r.time_out)}</div>
                            </div>` : ''}
                            ${r.remarks ? `
                            <div style="grid-column: 1 / -1;">
                                <div class="att-grid-col-label">
                                    <i class="bi bi-chat-left-text text-gold"></i> Remarks
                                </div>
                                <div style="font-size:0.82rem; font-weight:500; color:#ffd166;">${escapeHtml(r.remarks)}</div>
                            </div>` : ''}
                        </div>

                        ${st === 'absent' ? `
                        <a href="{{ route('excuses.create_general') }}" class="day-card-excuse-btn">
                            <span><i class="bi bi-file-earmark-medical me-1"></i> File Excuse Request for this Absence</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>` : ''}
                    </div>
                `;
            });

            fullHtml += `</div>`;
        }

        // 2. EVENTS / IMPORTANT DATES SECTION
        if (dayEvents.length > 0) {
            fullHtml += `
                <div class="day-section-header mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="day-section-icon" style="background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3);">
                            <i class="bi bi-calendar-event-fill"></i>
                        </span>
                        <span class="day-section-label">EVENTS / IMPORTANT DATES</span>
                    </div>
                    <span class="day-section-badge" style="background: rgba(139, 92, 246, 0.12); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.25);">
                        ${dayEvents.length} ${dayEvents.length === 1 ? 'Entry' : 'Entries'}
                    </span>
                </div>
                <div class="d-flex flex-column gap-3">
            `;

            dayEvents.forEach(evt => {
                const isExam = evt.type === 'exam';
                const isHoliday = evt.type === 'holiday';

                let badgeText = 'Event';
                let badgeStyle = 'background: rgba(139, 92, 246, 0.15); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.35);';
                let borderStyle = 'border-left: 4px solid #8b5cf6;';
                let iconClass = 'bi-calendar-event-fill';

                if (isExam) {
                    badgeText = 'Exam';
                    badgeStyle = 'background: rgba(236, 72, 153, 0.15); color: #f472b6; border: 1px solid rgba(236, 72, 153, 0.35);';
                    borderStyle = 'border-left: 4px solid #ec4899;';
                    iconClass = 'bi-award-fill';
                } else if (isHoliday) {
                    badgeText = 'Holiday';
                    badgeStyle = 'background: rgba(74, 222, 128, 0.15); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.35);';
                    borderStyle = 'border-left: 4px solid #4ade80;';
                    iconClass = 'bi-flag-fill';
                }

                let metaRows = '';

                // Subject row (e.g. Mathematics for Exam)
                if (evt.subject) {
                    metaRows += `
                        <div style="font-size: 0.95rem; font-weight: 700; color: #ffd166; margin-top: 6px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-book-half" style="font-size: 0.85rem; color: #cfa46f;"></i>
                            <span>${escapeHtml(evt.subject)}</span>
                        </div>
                    `;
                }

                // Time row (e.g. 9:00 AM – 4:00 PM or 8:00 AM – 10:00 AM)
                if (evt.time) {
                    metaRows += `
                        <div style="font-size: 0.85rem; font-weight: 600; color: #f3ede4; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-clock-fill" style="font-size: 0.8rem; color: #cfa46f;"></i>
                            <span>${escapeHtml(evt.time)}</span>
                        </div>
                    `;
                }

                // Location row (e.g. School Gym)
                if (evt.location) {
                    metaRows += `
                        <div style="font-size: 0.85rem; font-weight: 600; color: #b39b82; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-geo-alt-fill" style="font-size: 0.8rem; color: #cfa46f;"></i>
                            <span>${escapeHtml(evt.location)}</span>
                        </div>
                    `;
                }

                // Description row (e.g. No classes)
                if (evt.description && evt.description.trim() !== '') {
                    metaRows += `
                        <div style="font-size: 0.82rem; color: #8f826f; margin-top: 6px; line-height: 1.45;">
                            ${escapeHtml(evt.description)}
                        </div>
                    `;
                }

                fullHtml += `
                    <div style="background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.08); ${borderStyle} border-radius: 14px; padding: 16px 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 10px; border-radius: 99px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; ${badgeStyle} margin-bottom: 6px;">
                                    <i class="bi ${iconClass}"></i>
                                    <span>${badgeText}</span>
                                </div>
                                <div style="font-weight: 800; color: #f3ede4; font-size: 1.1rem; line-height: 1.3;">
                                    ${escapeHtml(evt.title)}
                                </div>
                                ${metaRows}
                            </div>
                        </div>
                    </div>
                `;
            });

            fullHtml += `</div>`;
        }

        if (contentEl) contentEl.innerHTML = fullHtml;
    }

    const modalEl = document.getElementById('daySummaryModal');
    if (modalEl) {
        if (modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }
        try {
            if (!dayModalInstance && window.bootstrap && window.bootstrap.Modal) {
                dayModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            }
            if (dayModalInstance) {
                dayModalInstance.show();
            } else {
                throw new Error('Bootstrap Modal not available');
            }
        } catch (err) {
            console.warn('Day summary modal fallback invoked:', err);
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

function openAttendanceRecordsModal() {
    renderAttendanceRecordsList(allAttendanceRecords);
    const modalEl = document.getElementById('attendanceRecordsModal');
    if (modalEl) {
        if (modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }
        try {
            if (!recordsModalInstance && window.bootstrap && window.bootstrap.Modal) {
                recordsModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            }
            if (recordsModalInstance) {
                recordsModalInstance.show();
            } else {
                throw new Error('Bootstrap Modal not available');
            }
        } catch (err) {
            console.warn('Records modal fallback invoked:', err);
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            modalEl.setAttribute('aria-modal', 'true');
            modalEl.removeAttribute('aria-hidden');
            document.body.classList.add('modal-open');
        }
    }
    if (window.triggerHaptic) window.triggerHaptic('light');
}

function openSubjectBreakdownModal() {
    const modalEl = document.getElementById('subjectBreakdownModal');
    if (!modalEl) return;
    if (modalEl.parentNode !== document.body) {
        document.body.appendChild(modalEl);
    }
    try {
        if (window.bootstrap && window.bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            throw new Error('Bootstrap not ready');
        }
    } catch (err) {
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.setAttribute('aria-modal', 'true');
        modalEl.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
    }
    if (window.triggerHaptic) window.triggerHaptic('light');
}

// Auto-open Breakdown modal when ?open_breakdown=1 is present in URL
(function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('open_breakdown') === '1') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(openSubjectBreakdownModal, 400);
        });
    }
})();

function filterAttendanceRecords() {
    const searchVal = (document.getElementById('recordsSearchInput')?.value || '').toLowerCase().trim();
    const statusVal = (document.getElementById('recordsStatusFilter')?.value || 'all').toLowerCase();
    const monthVal = (document.getElementById('recordsMonthFilter')?.value || 'all');

    const filtered = allAttendanceRecords.filter(r => {
        if (searchVal) {
            const matchSubj = (r.subject || '').toLowerCase().includes(searchVal);
            const matchCode = (r.code || '').toLowerCase().includes(searchVal);
            const matchInst = (r.instructor || '').toLowerCase().includes(searchVal);
            if (!matchSubj && !matchCode && !matchInst) return false;
        }
        if (statusVal !== 'all') {
            if (statusVal === 'excused' && !r.excused) return false;
            if (statusVal !== 'excused' && r.status_lower !== statusVal) return false;
        }
        if (monthVal !== 'all') {
            if (r.date_month !== monthVal) return false;
        }
        return true;
    });

    renderAttendanceRecordsList(filtered);
}

function renderAttendanceRecordsList(items) {
    const listEl = document.getElementById('recordsModalList');
    const countEl = document.getElementById('recordsModalCount');
    if (!listEl) return;

    if (countEl) {
        countEl.textContent = `${items.length} ${items.length === 1 ? 'Record' : 'Records'}`;
    }

    if (items.length === 0) {
        listEl.innerHTML = `
            <div style="text-align:center; padding:40px 20px; color:#8f826f; background:rgba(255,255,255,0.02); border-radius:18px; border:1px dashed rgba(255,255,255,0.08);">
                <i class="bi bi-search" style="font-size:2rem; color:#cfa46f; display:block; margin-bottom:10px; opacity:0.6;"></i>
                <div style="font-weight:700; font-size:0.95rem; color:#f3ede4;">No records match your filters</div>
                <div style="font-size:0.8rem; margin-top:4px;">Try clearing your search query or changing filters.</div>
            </div>
        `;
        return;
    }

    let html = '';
    items.forEach(r => {
        const st = (r.status_lower || 'absent');
        let iconClass = 'bi-x-circle-fill';
        let clockInColor = '#f87171';

        if (st === 'present') {
            iconClass = 'bi-check-circle-fill';
            clockInColor = '#4ade80';
        } else if (st === 'late') {
            iconClass = 'bi-clock-fill';
            clockInColor = '#fbbf24';
        }

        const clockInText = r.clock_in || (st === 'absent' ? 'No clock-in' : '—');
        const isNoClockIn = clockInText.toLowerCase().includes('no clock-in');

        html += `
            <div class="record-modal-item" style="background:rgba(255,255,255,0.025); border:1px solid rgba(255,255,255,0.06); border-radius:18px; padding:16px 18px; transition:all 0.2s cubic-bezier(0.16,1,0.3,1); margin-bottom:12px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:10px; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:0.8rem; font-weight:700; color:#ffd166; display:flex; align-items:center; gap:6px;">
                            <i class="bi bi-calendar-event"></i> ${escapeHtml(r.date_formatted)}
                            <span style="color:rgba(255,255,255,0.3); font-weight:400;">(${escapeHtml(r.day_name)})</span>
                        </div>
                        <h4 style="margin:4px 0 0; font-size:1.05rem; font-weight:800; color:#f3ede4; line-height:1.3;">
                            ${escapeHtml(r.subject)}
                        </h4>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span class="subject-card-badge ${st}" style="padding:5px 12px; border-radius:8px; font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; display:inline-flex; align-items:center; gap:5px;">
                            <i class="bi ${iconClass}"></i> ${escapeHtml(r.status)}
                        </span>
                        ${r.excused ? `<span style="padding:5px 10px; border-radius:8px; font-size:0.72rem; font-weight:700; background:rgba(207,164,111,0.15); color:#ffd166; border:1px solid rgba(207,164,111,0.3);">Excused</span>` : ''}
                    </div>
                </div>

                <div style="font-size:0.8rem; color:#cfa46f; margin-bottom:12px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span style="background:rgba(207,164,111,0.12); padding:2px 8px; border-radius:6px; font-weight:700; border:1px solid rgba(207,164,111,0.25);">${escapeHtml(r.code)}</span>
                    <span style="color:rgba(255,255,255,0.25);">•</span>
                    <span style="color:#b39b82;"><i class="bi bi-person me-1"></i>${escapeHtml(r.instructor)}</span>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px; background:rgba(0,0,0,0.25); border:1px solid rgba(255,255,255,0.03); border-radius:12px; padding:12px 14px;">
                    <div>
                        <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                            <i class="bi bi-clock me-1"></i> Scheduled
                        </div>
                        <div style="font-size:0.82rem; font-weight:600; color:#f3ede4;">${escapeHtml(r.schedule)}</div>
                    </div>
                    <div>
                        <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Actual Clock-in
                        </div>
                        <div style="font-size:0.82rem; font-weight:700; color:${isNoClockIn ? '#f87171' : clockInColor};">
                            ${escapeHtml(clockInText)}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                            <i class="bi bi-box-arrow-right me-1"></i> Clock-out
                        </div>
                        <div style="font-size:0.82rem; font-weight:600; color:#b39b82;">${escapeHtml(r.clock_out)}</div>
                    </div>
                    ${r.remarks && r.remarks !== '—' && r.remarks !== 'None' ? `
                    <div style="grid-column:1 / -1;">
                        <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                            <i class="bi bi-chat-left-text me-1"></i> Remarks
                        </div>
                        <div style="font-size:0.8rem; font-weight:500; color:#ffd166;">${escapeHtml(r.remarks)}</div>
                    </div>` : ''}
                </div>
            </div>
        `;
    });
    listEl.innerHTML = html;
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

document.addEventListener('DOMContentLoaded', function() {
    // Relocate modals directly to document.body to escape any CSS transform/stacking context on mobile
    const dModal = document.getElementById('daySummaryModal');
    if (dModal && dModal.parentNode !== document.body) {
        document.body.appendChild(dModal);
    }
    const rModal = document.getElementById('attendanceRecordsModal');
    if (rModal && rModal.parentNode !== document.body) {
        document.body.appendChild(rModal);
    }

    // Direct event listener binding for all non-empty calendar tiles
    document.querySelectorAll('.scal-tile:not(.empty)').forEach(tile => {
        tile.addEventListener('click', function(e) {
            const dKey = this.getAttribute('data-date');
            const dNum = parseInt(this.getAttribute('data-day'), 10);
            if (dKey) {
                selectCalendarDay(dKey, dNum, true);
            }
        });
        tile.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const dKey = this.getAttribute('data-date');
                const dNum = parseInt(this.getAttribute('data-day'), 10);
                if (dKey) {
                    selectCalendarDay(dKey, dNum, true);
                }
            }
        });
    });
});
</script>

<script nonce="{{ csp_nonce() }}">
// ── Skeleton → Content Reveal ──
document.addEventListener('DOMContentLoaded', function() {
    var skelStats = document.getElementById('skelStats');
    var realStats = document.getElementById('realStats');
    if (skelStats && realStats) { skelStats.style.display = 'none'; realStats.style.display = ''; }
});

// ── Real-time Clock ──
(function() {
    function tick() {
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        const short = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
        const clockEl = document.getElementById('studentClock');
        if (clockEl) clockEl.textContent = short;
    }
    setInterval(tick, 1000);
})();
</script>
@endsection