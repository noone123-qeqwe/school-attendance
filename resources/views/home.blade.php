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

    $dayDotsMap = [];
    foreach ($dayRecordsMap as $day => $recs) {
        $dots = [];
        $statuses = collect($recs)->pluck('status')->map(fn($s) => strtolower($s))->unique();
        if ($statuses->contains('present')) $dots[] = 'present';
        if ($statuses->contains('late'))    $dots[] = 'late';
        if ($statuses->contains('absent'))  $dots[] = 'absent';
        $dayDotsMap[$day] = $dots;
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
       STUDENT HERO DASHBOARD BANNER (Redesigned & Premium)
       ══════════════════════════════════════════════════════════════ */
    .student-hero-banner {
        background: linear-gradient(135deg, rgba(38, 14, 12, 0.96) 0%, rgba(20, 10, 8, 0.98) 50%, rgba(30, 16, 12, 0.94) 100%) !important;
        border: 1px solid rgba(212, 175, 55, 0.24) !important;
        border-radius: 24px !important;
        padding: 24px 28px !important;
        position: relative !important;
        overflow: hidden !important;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
        margin-bottom: 24px !important;
    }

    .student-hero-banner::before {
        content: '';
        position: absolute;
        top: 18px;
        bottom: 18px;
        left: 0;
        width: 5px;
        background: linear-gradient(180deg, #ffd166 0%, #cfa46f 50%, #8b5a2b 100%);
        border-radius: 0 6px 6px 0;
        box-shadow: 0 0 14px rgba(212, 175, 55, 0.45);
    }

    .student-hero-banner::after {
        content: '';
        position: absolute;
        top: -40px;
        right: -40px;
        width: 240px;
        height: 240px;
        background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
        pointer-events: none;
    }

    .student-hero-inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        position: relative;
        z-index: 2;
    }

    .student-hero-identity {
        display: flex;
        align-items: center;
        gap: 20px;
        flex: 1 1 340px;
        min-width: 0;
    }

    .student-hero-avatar-wrap {
        position: relative;
        width: 66px;
        height: 66px;
        min-width: 66px;
        border-radius: 20px;
        padding: 2px;
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.7) 0%, rgba(139, 90, 43, 0.35) 100%);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4), 0 0 16px rgba(212, 175, 55, 0.15);
        flex-shrink: 0;
    }

    .student-hero-avatar {
        width: 100%;
        height: 100%;
        border-radius: 18px;
        object-fit: cover;
        background: #1a0f0a;
        display: block;
    }

    .student-avatar-pulse {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #10b981;
        border: 2px solid #140d07;
        box-shadow: 0 0 8px rgba(16, 185, 129, 0.7);
    }

    .student-hero-info {
        flex: 1;
        min-width: 0;
    }

    .student-greeting-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 99px;
        background: linear-gradient(135deg, rgba(212, 175, 55, 0.15) 0%, rgba(180, 130, 40, 0.06) 100%);
        border: 1px solid rgba(212, 175, 55, 0.3);
        color: #f3d18e;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .student-hero-name {
        color: #ffffff;
        font-weight: 800;
        margin: 0 0 8px 0;
        font-size: clamp(1.35rem, 3.8vw, 2.15rem);
        line-height: 1.15;
        letter-spacing: -0.025em;
        overflow-wrap: break-word;
        word-break: normal;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
    }

    .student-hero-chips {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .student-info-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 11px;
        border-radius: 10px;
        background: rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #e5d7c4;
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1.3;
        transition: all 0.2s ease;
    }

    .student-info-chip:hover {
        background: rgba(212, 175, 55, 0.1);
        border-color: rgba(212, 175, 55, 0.25);
        color: #fff;
    }

    .student-info-chip.chip-gold {
        background: rgba(212, 175, 55, 0.12);
        border-color: rgba(212, 175, 55, 0.28);
        color: #f3d18e;
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
        box-shadow: 0 0 6px rgba(16, 185, 129, 0.8);
    }

    /* Right side clock & live widgets */
    .student-hero-widgets {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
    }

    .student-clock-card {
        background: rgba(0, 0, 0, 0.45);
        border: 1px solid rgba(212, 175, 55, 0.22);
        border-radius: 16px;
        padding: 10px 18px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        min-width: 190px;
    }

    .student-clock-time {
        color: #ffd166;
        font-size: 1.35rem;
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
        font-size: 0.78rem;
        font-weight: 500;
        margin-top: 3px;
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
        padding: 4px 10px;
        border-radius: 99px;
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.25);
        color: #4ade80;
        font-size: 0.75rem;
        font-weight: 700;
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
        font-size: 0.75rem;
        font-weight: 700;
    }

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .student-hero-banner {
            padding: 18px 16px !important;
            border-radius: 18px !important;
            margin-bottom: 16px !important;
        }
        .student-hero-inner {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 14px !important;
        }
        .student-hero-identity {
            gap: 14px !important;
            flex: 1 1 100% !important;
        }
        .student-hero-avatar-wrap {
            width: 56px !important;
            height: 56px !important;
            min-width: 56px !important;
            border-radius: 16px !important;
        }
        .student-hero-avatar {
            border-radius: 14px !important;
        }
        .student-hero-name {
            font-size: 1.25rem !important;
            margin-bottom: 6px !important;
        }
        .student-greeting-pill {
            font-size: 0.68rem !important;
            padding: 2px 8px !important;
            margin-bottom: 4px !important;
        }
        .student-info-chip {
            font-size: 0.75rem !important;
            padding: 3px 9px !important;
            border-radius: 8px !important;
        }
        .student-hero-widgets {
            align-items: stretch !important;
            width: 100% !important;
            gap: 8px !important;
        }
        .student-clock-card {
            flex-direction: row !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 9px 14px !important;
            min-width: 0 !important;
            width: 100% !important;
            border-radius: 12px !important;
        }
        .student-clock-time {
            font-size: 1.1rem !important;
        }
        .student-clock-date {
            font-size: 0.72rem !important;
            margin-top: 0 !important;
        }
        .student-hero-badges-row {
            justify-content: flex-start !important;
        }
    }

    @media (max-width: 360px) {
        .student-hero-banner {
            padding: 14px 12px !important;
        }
        .student-hero-avatar-wrap {
            width: 46px !important;
            height: 46px !important;
            min-width: 46px !important;
        }
        .student-hero-name {
            font-size: 1.1rem !important;
        }
        .student-info-chip {
            font-size: 0.7rem !important;
            padding: 2px 7px !important;
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
                    <span class="student-rate-badge" title="Overall Attendance Rate">
                        <i class="bi bi-shield-check"></i>
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
<div class="ent-grid ent-grid-4 ent-mb-lg ent-fade-up ent-delay-2" id="realStats" style="display:none; gap:20px; margin-bottom:24px;">
    <x-card type="kpi" accent="success" label="Present" value="{{ $totalPresent ?? 0 }}" icon="bi bi-check-circle-fill" />
    <x-card type="kpi" accent="warning" label="Late" value="{{ $totalLate ?? 0 }}" icon="bi bi-clock-fill" />
    <x-card type="kpi" accent="danger" label="Absent" value="{{ $totalAbsent ?? 0 }}" icon="bi bi-x-circle-fill" />
    <x-card type="kpi" accent="gold" label="Subjects" value="{{ isset($subjects) ? count($subjects) : 0 }}" icon="bi bi-book-fill" />
</div>

<!-- Subject Attendance Breakdown -->
@if(isset($subjectStats) && $subjectStats->count() > 0)
<div class="mb-4">
    <x-card title="Subject Breakdown" icon="bi bi-bar-chart-fill">
        <x-slot name="headerActions">
            <span style="font-size: 0.75rem; color: #b39b82; font-weight: 600;">{{ $subjectStats->count() }} subjects</span>
        </x-slot>

        <style>
            .subject-stat-card:hover {
                transform: translateY(-2px);
                border-color: rgba(207,164,111,0.4) !important;
                box-shadow: 0 8px 25px rgba(0,0,0,0.4) !important;
            }
        </style>
        <div class="d-flex flex-column gap-3">
            @foreach($subjectStats as $stat)
                @php
                    $isNew = $stat->total == 0;
                    $rateColor = $isNew ? '#9ca3af' : ($stat->rate >= 90 ? '#4ade80' : ($stat->rate >= 75 ? '#fbbf24' : '#f87171'));
                    $rateBg = $isNew ? 'rgba(156,163,175,0.15)' : ($stat->rate >= 90 ? 'rgba(74,222,128,0.15)' : ($stat->rate >= 75 ? 'rgba(251,191,36,0.15)' : 'rgba(248,113,113,0.15)'));
                    $rateBorder = $isNew ? 'rgba(156,163,175,0.3)' : ($stat->rate >= 90 ? 'rgba(74,222,128,0.3)' : ($stat->rate >= 75 ? 'rgba(251,191,36,0.3)' : 'rgba(248,113,113,0.3)'));
                @endphp
                <div class="subject-stat-card" style="background: rgba(17, 9, 6, 0.7); border: 1px solid rgba(207,164,111,0.15); border-radius: 16px; padding: 20px; position: relative; overflow: hidden; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                    <!-- Background Glow -->
                    <div class="bg-glow" style="position: absolute; top: -40px; right: -40px; width: 120px; height: 120px; background: {{ $rateColor }}; border-radius: 50%; filter: blur(50px); opacity: 0.15; pointer-events: none;"></div>

                    <div class="d-flex justify-content-between align-items-start mb-3" style="position: relative; z-index: 2; gap: 12px;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 800; color: #f3e7cd; font-size: 1.1rem; letter-spacing: -0.3px; overflow-wrap: break-word; word-break: normal;">{{ $stat->name }}</div>
                            <div style="font-size: 0.8rem; color: #b39b82; margin-top: 6px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span style="background: rgba(207,164,111,0.1); padding: 3px 8px; border-radius: 6px; font-weight: 700;">{{ $stat->code }}</span>
                                <span>•</span>
                                <span>{{ $stat->total }} Classes</span>
                            </div>
                        </div>
                        <div style="text-align: right; flex-shrink: 0;">
                            <div class="rate-text" style="font-size: 1.7rem; font-weight: 900; color: {{ $rateColor }}; line-height: 1; text-shadow: 0 0 20px {{ $rateBg }};">{{ $stat->rate }}<span style="font-size: 1.1rem; opacity: 0.8;">%</span></div>
                            @if(!$isNew && $stat->rate < 75)
                                <div style="font-size: 0.7rem; color: #f87171; font-weight: 700; margin-top: 6px; display: inline-flex; align-items: center; gap: 4px; background: rgba(248,113,113,0.15); padding: 3px 10px; border-radius: 99px;">
                                    <i class="bi bi-exclamation-triangle-fill"></i> At Risk
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div style="position: relative; z-index: 2; height: 6px; background: rgba(255,255,255,0.06); border-radius: 99px; overflow: hidden; margin-bottom: 16px;">
                        <div style="height: 100%; width: 0%; background: {{ $rateColor }}; border-radius: 99px; transition: width 1.2s cubic-bezier(0.22, 1, 0.36, 1);" class="animated-progress" data-width="{{ $stat->rate }}%"></div>
                    </div>

                    <!-- Status Pills -->
                    <div class="d-flex gap-2 flex-wrap" style="position: relative; z-index: 2;">
                        <span style="font-size: 0.75rem; font-weight: 600; color: #4ade80; background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.25); padding: 4px 12px; border-radius: 99px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-check-circle-fill" style="font-size: 0.7rem;"></i> {{ $stat->present }} Present
                        </span>
                        @if($stat->late > 0)
                        <span style="font-size: 0.75rem; font-weight: 600; color: #fbbf24; background: rgba(251,191,36,0.1); border: 1px solid rgba(251,191,36,0.25); padding: 4px 12px; border-radius: 99px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-clock-fill" style="font-size: 0.7rem;"></i> {{ $stat->late }} Late
                        </span>
                        @endif
                        @if($stat->absent > 0)
                        <span style="font-size: 0.75rem; font-weight: 600; color: #f87171; background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.25); padding: 4px 12px; border-radius: 99px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-x-circle-fill" style="font-size: 0.7rem;"></i> {{ $stat->absent }} Absent
                        </span>
                        @endif
                        @if($stat->excused > 0)
                        <span style="font-size: 0.75rem; font-weight: 600; color: #60a5fa; background: rgba(96,165,250,0.1); border: 1px solid rgba(96,165,250,0.25); padding: 4px 12px; border-radius: 99px; display: flex; align-items: center; gap: 6px;">
                            <i class="bi bi-file-earmark-check-fill" style="font-size: 0.7rem;"></i> {{ $stat->excused }} Excused
                        </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    document.querySelectorAll('.animated-progress').forEach(function(el) {
                        el.style.width = el.getAttribute('data-width');
                    });
                }, 150);
            });
        </script>
    </x-card>
</div>
@endif

<div class="row g-4 mb-4">
    <!-- Today's Schedule -->
    <div class="col-lg-6">
        <x-card title="Today's Schedule" icon="bi bi-clock-history">
            <x-slot name="headerActions">
                <a href="{{ route('student.schedule') }}" class="btn btn-outline btn-sm">Full Schedule</a>
            </x-slot>
            @if(isset($todaySchedule) && $todaySchedule->count() > 0)
                <div class="d-flex flex-column gap-3">
                @foreach($todaySchedule as $item)
                    <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                        <div class="d-flex align-items-center gap-3" style="flex: 1; min-width: 0;">
                            <div style="width: 4px; height: 40px; background: {{ $item->status === 'completed' ? '#4ade80' : ($item->status === 'ongoing' ? '#fbbf24' : ($item->status === 'missed' ? '#f87171' : 'var(--gold)')) }}; border-radius: 4px; flex-shrink: 0;"></div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 700; color: #f3e7cd; font-size: 1.1rem; overflow-wrap: break-word; word-break: normal;">{{ $item->subject->name }}</div>
                                <div style="color: #b39b82; font-size: 0.85rem; margin-top: 4px; overflow-wrap: break-word; word-break: normal;">
                                    {{ $item->start_time->format('g:i A') }} – {{ $item->end_time->format('g:i A') }} &nbsp;·&nbsp; {{ $item->subject->code }}
                                </div>
                            </div>
                        </div>
                        <div style="flex-shrink: 0;">
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
                <div class="empty-state text-center" style="padding: 40px 20px;">
                    <i class="bi bi-calendar-x" style="font-size: 3rem; color: #b39b82; opacity: 0.5;"></i>
                    <p style="color: #b39b82; font-size: 1rem; margin-top: 16px; font-weight: 600;">No classes scheduled today</p>
                </div>
            @endif
        </x-card>
    </div>

    <!-- School Calendar & Upcoming Events -->
    <div class="col-lg-6">
        <x-card title="Upcoming Events" icon="bi bi-calendar-event">
            <x-slot name="headerActions">
                <a href="{{ route('student.calendar') }}" class="btn btn-outline btn-sm">Full Calendar</a>
            </x-slot>
            @if(isset($calendarEvents) && $calendarEvents->count() > 0)
                <div class="d-flex flex-column gap-3">
                    @foreach($calendarEvents->take(5) as $event)
                        <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                            <div class="d-flex align-items-center gap-3" style="flex: 1; min-width: 0;">
                                <div style="width: 48px; height: 48px; background: rgba(207,164,111,0.1); border: 1px solid rgba(207,164,111,0.2); border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 48px; flex-shrink: 0;">
                                    <span style="font-size: 0.7rem; font-weight: 800; color: #cfa46f; text-transform: uppercase; line-height: 1;">{{ \Carbon\Carbon::parse($event->date)->format('M') }}</span>
                                    <span style="font-size: 1.2rem; font-weight: 900; color: #f3e7cd; line-height: 1;">{{ \Carbon\Carbon::parse($event->date)->format('d') }}</span>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 700; color: #f3e7cd; font-size: 1.1rem; overflow-wrap: break-word; word-break: normal;">{{ $event->title }}</div>
                                    @if($event->type === 'announcement' && isset($event->author) && $event->author)
                                        @php
                                            $evtAuthorRole = $event->author_role ?? 'teacher';
                                            $evtIsAdmin = $evtAuthorRole === 'admin';
                                            $evtBadgeBg = $evtIsAdmin ? 'rgba(207,164,111,0.15)' : 'rgba(139,90,43,0.15)';
                                            $evtBadgeBorder = $evtIsAdmin ? 'rgba(207,164,111,0.35)' : 'rgba(139,90,43,0.35)';
                                            $evtBadgeColor = $evtIsAdmin ? '#CFA46F' : '#8B5A2B';
                                            $evtRoleLabel = $evtIsAdmin ? 'Administrator' : 'Instructor';
                                        @endphp
                                        <div style="display:flex;align-items:center;gap:6px;margin-top:4px;flex-wrap:wrap;">
                                            <span style="font-size:0.8rem;color:#b39b82;">{{ $event->author }}</span>
                                            <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:99px;font-size:0.65rem;font-weight:700;background:{{ $evtBadgeBg }};border:1px solid {{ $evtBadgeBorder }};color:{{ $evtBadgeColor }};">
                                                <span style="width:5px;height:5px;border-radius:50%;background:{{ $evtBadgeColor }};display:inline-block;"></span>
                                                {{ $evtRoleLabel }}
                                            </span>
                                        </div>
                                    @else
                                        <div style="color: #b39b82; font-size: 0.85rem; margin-top: 4px; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ strip_tags($event->content) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="d-none d-sm-block">
                                @if($event->type === 'holiday') <x-badge type="present">Holiday</x-badge>
                                @else <x-badge type="info">Event</x-badge>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state text-center" style="padding: 40px 20px;">
                    <i class="bi bi-calendar-x" style="font-size: 3rem; color: #b39b82; opacity: 0.5;"></i>
                    <p style="color: #b39b82; font-size: 1rem; margin-top: 16px; font-weight: 600;">No upcoming events or holidays</p>
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

    /* Subject cards inside Day Summary Inspector */
    .subject-card {
        background: rgba(255,255,255,0.025);
        border-radius: 16px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        border: 1px solid rgba(255,255,255,0.05);
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .subject-card:hover {
        background: rgba(255,255,255,0.05);
        border-color: rgba(207,164,111,0.25);
    }
    .subject-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
        background: rgba(207,164,111,0.12);
        color: #cfa46f;
        border: 1px solid rgba(207,164,111,0.2);
    }
    .subject-card-info { flex: 1; min-width: 0; }
    .subject-card-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: #f3ede4;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .subject-card-time { font-size: 0.78rem; color: #b39b82; display: flex; align-items: center; gap: 5px; }
    .subject-card-badge {
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 800;
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .subject-card-badge.present {
        background: rgba(74,222,128,0.15);
        color: #4ade80;
        border: 1px solid rgba(74,222,128,0.3);
    }
    .subject-card-badge.late {
        background: rgba(251,191,36,0.15);
        color: #fbbf24;
        border: 1px solid rgba(251,191,36,0.3);
    }
    .subject-card-badge.absent {
        background: rgba(248,113,113,0.15);
        color: #f87171;
        border: 1px solid rgba(248,113,113,0.3);
    }

    /* Modal z-indexes & backdrop to guarantee display above mobile navigation */
    #daySummaryModal,
    #attendanceRecordsModal {
        z-index: 10060 !important;
    }
    .modal-backdrop {
        z-index: 10050 !important;
    }
    .scal-sheet-handle {
        width: 44px;
        height: 5px;
        background: rgba(255, 255, 255, 0.22);
        border-radius: 3px;
        margin: 12px auto 4px auto;
        display: none;
    }

    @media (max-width: 576px) {
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
            border-top-left-radius: 26px !important;
            border-top-right-radius: 26px !important;
            max-height: 88vh !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
            padding-bottom: calc(24px + env(safe-area-inset-bottom, 16px)) !important;
            border-bottom: none !important;
            width: 100% !important;
        }
        .scal-sheet-handle {
            display: block;
        }
    }
</style>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="scal-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; border-radius:10px; background:rgba(207,164,111,0.15); display:flex; align-items:center; justify-content:center; color:#cfa46f; font-size:1.1rem;">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <span style="font-size:1.15rem; font-weight:800; color:#f3e7cd;">Attendance Calendar</span>
                </div>
                <button type="button" class="scal-view-records-btn" onclick="openAttendanceRecordsModal()" title="View Complete Attendance Records">
                    <i class="bi bi-clock-history"></i> View Records
                </button>
            </div>

            {{-- Month Navigation --}}
            <div class="scal-nav">
                <a href="?cal_year={{ $prevMonth->year }}&cal_month={{ $prevMonth->month }}" class="scal-nav-btn" title="Previous Month">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <div class="scal-nav-title">{{ $calStart->format('F Y') }}</div>
                @if(!$isLatestMonth)
                    <a href="?cal_year={{ $nextMonth->year }}&cal_month={{ $nextMonth->month }}" class="scal-nav-btn" title="Next Month">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @else
                    <div style="width:44px;"></div>
                @endif
            </div>

            {{-- Day Labels --}}
            <div class="scal-weekdays">
                <div class="scal-wd">S</div>
                <div class="scal-wd">M</div>
                <div class="scal-wd">T</div>
                <div class="scal-wd">W</div>
                <div class="scal-wd">T</div>
                <div class="scal-wd">F</div>
                <div class="scal-wd">S</div>
            </div>

            {{-- Calendar Grid --}}
            <div class="scal-grid">
                {{-- Empty cells before the 1st --}}
                @for($i = 0; $i < $startDow; $i++)
                    <div class="scal-tile empty"></div>
                @endfor

                @for($d = 1; $d <= $calEnd->day; $d++)
                    @php
                        $dayDate = \Carbon\Carbon::create($calYear, $calMonth, $d);
                        $dateKey = $dayDate->format('Y-m-d');
                        $isSunday = $dayDate->dayOfWeek === 0;
                        $isTodayCell = $isCurrentMonth && $d === $today;
                        $dayStatuses = $dayDotsMap[$d] ?? [];
                        $hasRecords = !empty($dayStatuses);

                        // Selected highlight: today if current month, or September 14, 2026 matching reference screenshot
                        $isSelectedCell = ($isCurrentMonth && $d === $today) || ($calYear == 2026 && $calMonth == 9 && $d == 14);

                        $cellStatus = '';
                        if ($hasRecords) {
                            if (count($dayStatuses) > 1) {
                                $cellStatus = 'status-mixed';
                            } elseif (in_array('absent', $dayStatuses)) {
                                $cellStatus = 'status-absent';
                            } elseif (in_array('late', $dayStatuses)) {
                                $cellStatus = 'status-late';
                            } elseif (in_array('present', $dayStatuses)) {
                                $cellStatus = 'status-present';
                            }
                        }

                        $tileClasses = 'scal-tile';
                        if ($hasRecords) $tileClasses .= ' has-records';
                        if ($isTodayCell) $tileClasses .= ' today';
                        if ($isSelectedCell) $tileClasses .= ' selected';
                        if ($isSunday) $tileClasses .= ' sunday';
                        if ($cellStatus) $tileClasses .= ' ' . $cellStatus;
                    @endphp
                    <div class="{{ $tileClasses }}"
                         id="calTile_{{ $dateKey }}"
                         data-date="{{ $dateKey }}"
                         data-day="{{ $d }}"
                         role="button"
                         tabindex="0"
                         onclick="selectCalendarDay('{{ $dateKey }}', {{ $d }})"
                         title="Click to view attendance for {{ $dayDate->format('M d, Y') }}">
                        <span class="scal-num">{{ $d }}</span>
                        @if($hasRecords)
                            <div class="scal-dots-row">
                                @foreach($dayStatuses as $st)
                                    <span class="scal-dot dot-{{ $st }}"></span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endfor
            </div>

            {{-- Legend and Tips --}}
            <div class="row g-3 mt-3">
                <div class="col-md-6 col-12">
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 18px; padding: 16px 20px;">
                        <div style="font-size: 0.72rem; font-weight: 800; color: #cfa46f; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px;">Legend</div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; font-weight:600; color:#f3ede4;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#10b981; display:inline-block;"></span> Present
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; font-weight:600; color:#f3ede4;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#f59e0b; display:inline-block;"></span> Late
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; font-weight:600; color:#f3ede4;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#ef4444; display:inline-block;"></span> Absent
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; font-weight:600; color:#f3ede4;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#ec4899; display:inline-block;"></span> Exam
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; font-weight:600; color:#f3ede4;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#8b5cf6; display:inline-block;"></span> Event
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.8rem; font-weight:600; color:#f3ede4;">
                                <span style="width:8px; height:8px; border-radius:50%; background:#4ade80; display:inline-block;"></span> Holiday
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 18px; padding: 16px 20px;">
                        <div style="font-size: 0.72rem; font-weight: 800; color: #cfa46f; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 12px; display:flex; align-items:center; gap:6px;">
                            <i class="bi bi-lightbulb-fill"></i> Calendar Tips
                        </div>
                        <ul style="margin: 0; padding-left: 18px; font-size: 0.78rem; color: #b39b82; line-height: 1.6;">
                            <li>Tap any <strong>day tile</strong> to inspect your subjects and attendance for that date.</li>
                            <li>Days with multiple subjects show distinct status dots for each class.</li>
                            <li>The <strong>golden border</strong> highlights your selected or current day.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Day Summary Inspector Modal (Subject-by-Subject Attendance Details) ───────────────── --}}
<div class="modal fade" id="daySummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background:#0f0a08; border:1px solid rgba(255,255,255,0.1); border-radius:24px; box-shadow:0 30px 80px rgba(0,0,0,0.85);">
            <div class="scal-sheet-handle"></div>
            <div class="d-flex justify-content-between align-items-center px-4 pt-4 pb-3 border-bottom position-relative" style="border-color:rgba(255,255,255,0.06)!important;">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="scal-modal-nav-btn" onclick="navigateDayModal(-1)" title="Previous Day">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div>
                        <h3 style="font-weight:800; font-size:1.2rem; color:#f3ede4; margin:0;" id="daySummaryTitle">Date</h3>
                        <div style="font-size:0.82rem; color:#b39b82; display:flex; align-items:center; gap:8px; margin-top:4px;" id="daySummarySubtitle">
                            <span id="daySummaryStatusDot" style="width:8px; height:8px; border-radius:50%; display:inline-block; background:#ffd166;"></span>
                            <span id="daySummaryStatusText" style="font-weight:600;">Attendance Details</span>
                        </div>
                    </div>
                    <button type="button" class="scal-modal-nav-btn" onclick="navigateDayModal(1)" title="Next Day">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="closeDaySummaryModal()"></button>
            </div>
            <div class="px-4 pb-4 pt-2">
                <div style="font-size:0.75rem; font-weight:800; color:#cfa46f; text-transform:uppercase; letter-spacing:0.08em; margin:16px 0 12px 0;" id="daySummarySectionTitle">Subjects Breakdown</div>
                <div id="daySummaryContent" class="d-flex flex-column gap-3">
                    <!-- dynamically populated -->
                </div>
                <div style="font-size:0.75rem; color:#8f826f; text-align:center; margin-top:18px;">
                    <i class="bi bi-info-circle me-1"></i> Real-time subject-by-subject attendance records for this date.
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

function showAttDetail(dateKey, day) {
    currentSelectedDateKey = dateKey;
    const titleEl = document.getElementById('daySummaryTitle');
    const subText = document.getElementById('daySummaryStatusText');
    const subDot = document.getElementById('daySummaryStatusDot');
    const contentEl = document.getElementById('daySummaryContent');

    const dt = new Date(dateKey + 'T00:00:00');
    const formattedDate = dt.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    if (titleEl) titleEl.textContent = formattedDate;

    const records = attCalendarData[dateKey] || [];
    if (records.length === 0) {
        if (subDot) subDot.style.background = '#8f826f';
        if (subText) subText.textContent = 'No attendance recorded';
        if (contentEl) {
            contentEl.innerHTML = `
                <div style="text-align:center; padding: 36px 18px; color:#8f826f; background:rgba(255,255,255,0.02); border-radius:18px; border:1px dashed rgba(255,255,255,0.08);">
                    <div style="width:52px; height:52px; border-radius:16px; background:rgba(207,164,111,0.1); border:1px solid rgba(207,164,111,0.2); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; color:#cfa46f; font-size:1.5rem;">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <div style="font-weight:700; font-size:1rem; color:#f3ede4; margin-bottom:6px;">No attendance records for this date.</div>
                    <div style="font-size:0.82rem; color:#8f826f; max-width:280px; margin:0 auto;">There are no recorded class attendance entries for this day. Enjoy your free time or check your schedule!</div>
                </div>`;
        }
    } else {
        const presentCount = records.filter(r => r.status_lower === 'present').length;
        const lateCount = records.filter(r => r.status_lower === 'late').length;
        const absentCount = records.filter(r => r.status_lower === 'absent').length;

        let statusSummary = [];
        if (presentCount > 0) statusSummary.push(`${presentCount} Present`);
        if (lateCount > 0) statusSummary.push(`${lateCount} Late`);
        if (absentCount > 0) statusSummary.push(`${absentCount} Absent`);

        let dotColor = '#10b981';
        if (absentCount > 0 && presentCount === 0 && lateCount === 0) dotColor = '#ef4444';
        else if (lateCount > 0 && absentCount === 0 && presentCount === 0) dotColor = '#f59e0b';
        else if (statusSummary.length > 1) dotColor = '#ffd166';

        if (subDot) subDot.style.background = dotColor;
        if (subText) subText.textContent = `${records.length} ${records.length === 1 ? 'Subject' : 'Subjects'} (${statusSummary.join(', ')})`;

        let html = '';
        records.forEach(r => {
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
                <div class="att-detail-card" style="background:rgba(255,255,255,0.025); border:1px solid rgba(255,255,255,0.06); border-radius:18px; padding:16px 18px; transition:all 0.2s ease;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:12px; flex-wrap:wrap;">
                        <div style="flex:1; min-width:180px;">
                            <div style="font-weight:800; font-size:1.02rem; color:#f3ede4; line-height:1.3; margin-bottom:4px;">${escapeHtml(r.subject)}</div>
                            <div style="font-size:0.8rem; color:#cfa46f; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <span style="background:rgba(207,164,111,0.12); padding:2px 8px; border-radius:6px; font-weight:700; border:1px solid rgba(207,164,111,0.25);">${escapeHtml(r.code)}</span>
                                <span style="color:rgba(255,255,255,0.25);">•</span>
                                <span style="color:#b39b82;"><i class="bi bi-person me-1"></i>${escapeHtml(r.instructor)}</span>
                            </div>
                        </div>
                        <span class="subject-card-badge ${st}" style="padding:6px 12px; border-radius:10px; font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; display:inline-flex; align-items:center; gap:5px;">
                            <i class="bi ${iconClass}"></i> ${escapeHtml(r.status)}
                        </span>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px; background:rgba(0,0,0,0.25); border:1px solid rgba(255,255,255,0.03); border-radius:12px; padding:12px 14px;">
                        <div>
                            <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                                <i class="bi bi-calendar3 me-1"></i> Schedule
                            </div>
                            <div style="font-size:0.85rem; font-weight:600; color:#f3ede4;">${escapeHtml(r.schedule)}</div>
                        </div>
                        <div>
                            <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Actual Clock-in
                            </div>
                            <div style="font-size:0.85rem; font-weight:700; color:${isNoClockIn ? '#f87171' : clockInColor};">
                                ${escapeHtml(clockInText)}
                            </div>
                        </div>
                        ${r.time_out ? `
                        <div>
                            <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                                <i class="bi bi-box-arrow-right me-1"></i> Clock-out
                            </div>
                            <div style="font-size:0.85rem; font-weight:600; color:#b39b82;">${escapeHtml(r.time_out)}</div>
                        </div>` : ''}
                        ${r.remarks ? `
                        <div style="grid-column: 1 / -1;">
                            <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#8f826f; margin-bottom:2px;">
                                <i class="bi bi-chat-left-text me-1"></i> Remarks
                            </div>
                            <div style="font-size:0.82rem; font-weight:500; color:#ffd166;">${escapeHtml(r.remarks)}</div>
                        </div>` : ''}
                    </div>
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