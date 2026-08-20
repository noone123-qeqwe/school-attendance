@extends('layouts.app')

@section('title', 'Admin Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}?v={{ filemtime(public_path('css/admin-theme.css')) }}">
@endpush

@section('content')

@php
    $presentDiff = $totalPresent - $yesterdayPresent;
    $lateDiff = $totalLate - $yesterdayLate;
    $absentDiff = $totalAbsent - $yesterdayAbsent;
    $rateDiff = $attendanceRate - $yesterdayRate;
@endphp

{{-- ─── MODERNIZED DASHBOARD HEADER ─── --}}
<div class="dash-header-bar dash-animate">
    <div class="dash-header-left">
        <h1 class="dash-title">Command Center</h1>
        <div class="dash-subtitle">
            <i class="bi bi-clock"></i> <span id="dashLiveClock" class="dash-live-clock">{{ now()->format('h:i:s A') }}</span> &mdash; <span id="dashLiveDate">{{ now()->format('l, F j, Y') }}</span>
        </div>
    </div>
    <div class="dash-header-right">
        <!-- Date Range Picker -->
        <div class="date-range-picker">
            <button type="button" class="date-range-btn" id="dateRangeBtn">
                <i class="bi bi-calendar-event"></i>
                <span id="dateRangeLabel">Today</span>
                <i class="bi bi-chevron-down" style="font-size:0.6rem; margin-left: 4px;"></i>
            </button>
            <div class="date-range-dropdown" id="dateRangeDropdown">
                <button type="button" class="date-range-option active" data-range="today">Today</button>
                <button type="button" class="date-range-option" data-range="yesterday">Yesterday</button>
                <button type="button" class="date-range-option" data-range="this_week">This Week</button>
                <button type="button" class="date-range-option" data-range="this_month">This Month</button>
            </div>
        </div>
        
        <a href="{{ route('admin.attendance.pdf') }}" class="adm-btn-primary ent-btn" style="border-radius: 12px; padding: 10px 20px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <i class="bi bi-cloud-arrow-down-fill"></i> Generate Report
        </a>
    </div>
</div>

{{-- ─── QUICK ACTIONS PANEL ─── --}}
<div class="quick-actions-grid dash-animate">
    <a href="{{ route('admin.students') }}" class="quick-action-btn">
        <div class="qa-icon" style="background: rgba(34, 197, 94, 0.12); color: #4ade80;">
            <i class="bi bi-people-fill"></i>
        </div>
        <span>Students</span>
    </a>
    <a href="{{ route('admin.teachers') }}" class="quick-action-btn">
        <div class="qa-icon" style="background: rgba(59, 130, 246, 0.12); color: #60a5fa;">
            <i class="bi bi-person-workspace"></i>
        </div>
        <span>Instructors</span>
    </a>
    <a href="{{ route('admin.attendance') }}" class="quick-action-btn">
        <div class="qa-icon" style="background: rgba(239, 68, 68, 0.12); color: #f87171;">
            <i class="bi bi-calendar-check-fill"></i>
        </div>
        <span>Attendance Logs</span>
    </a>
    <a href="{{ route('admin.calendar') }}" class="quick-action-btn">
        <div class="qa-icon" style="background: rgba(168, 85, 247, 0.12); color: #c084fc;">
            <i class="bi bi-calendar3-fill"></i>
        </div>
        <span>Holiday Calendar</span>
    </a>
    <a href="{{ route('admin.announcements.index') }}" class="quick-action-btn">
        <div class="qa-icon" style="background: rgba(236, 72, 153, 0.12); color: #f472b6;">
            <i class="bi bi-megaphone-fill"></i>
        </div>
        <span>Announcements</span>
    </a>
    <a href="{{ route('admin.activity.log') }}" class="quick-action-btn">
        <div class="qa-icon" style="background: rgba(245, 158, 11, 0.12); color: #fbbf24;">
            <i class="bi bi-journal-text"></i>
        </div>
        <span>Activity Logs</span>
    </a>
    @if(Auth::user()->admin_sub_role === 'super_admin' || is_null(Auth::user()->admin_sub_role))
    <a href="{{ route('admin.system-health.index') }}" class="quick-action-btn">
        <div class="qa-icon" style="background: rgba(20, 184, 166, 0.12); color: #2dd4bf;">
            <i class="bi bi-heart-pulse-fill"></i>
        </div>
        <span>System Health</span>
    </a>
    <a href="{{ route('admin.settings') }}" class="quick-action-btn">
        <div class="qa-icon" style="background: rgba(148, 163, 184, 0.12); color: #94a3b8;">
            <i class="bi bi-sliders"></i>
        </div>
        <span>Settings</span>
    </a>
    @endif
</div>

{{-- ─── SYSTEM ALERTS ─── --}}
@if($systemAlerts->count() > 0)
<div class="dash-animate" style="margin-bottom: 24px;" aria-live="polite">
    @foreach($systemAlerts as $alert)
    <div class="alert-card {{ $alert->severity }}">
        <div class="alert-icon">
            <i class="bi {{ $alert->icon }}"></i>
        </div>
        <div class="alert-message">{{ $alert->message }}</div>
        @if($alert->action)
            <a href="{{ $alert->action }}" class="alert-action">View <i class="bi bi-arrow-right"></i></a>
        @endif
    </div>
    @endforeach
</div>
@endif

{{-- ─── CORE ENTITY METRICS ─── --}}
<div class="stat-grid stat-grid-4 dash-animate" style="margin-bottom: 24px;">
    <div class="adm-stat" style="padding: 20px; display: flex; align-items: center; gap: 16px;">
        <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(207, 164, 111, 0.1); border: 1px solid rgba(207, 164, 111, 0.2); display: flex; align-items: center; justify-content: center; color: #d4b77d; font-size: 1.4rem; flex-shrink: 0;">
            <i class="bi bi-people-fill"></i>
        </div>
        <div>
            <div style="font-size: 0.72rem; font-weight: 700; color: #8f826f; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Total Students</div>
            <div class="adm-stat-val" style="font-size: 1.8rem !important;">{{ number_format($totalStudents) }}</div>
        </div>
    </div>
    
    <div class="adm-stat" style="padding: 20px; display: flex; align-items: center; gap: 16px;">
        <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(207, 164, 111, 0.1); border: 1px solid rgba(207, 164, 111, 0.2); display: flex; align-items: center; justify-content: center; color: #d4b77d; font-size: 1.4rem; flex-shrink: 0;">
            <i class="bi bi-person-workspace"></i>
        </div>
        <div>
            <div style="font-size: 0.72rem; font-weight: 700; color: #8f826f; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Instructors</div>
            <div class="adm-stat-val" style="font-size: 1.8rem !important;">{{ number_format($totalTeachers) }}</div>
        </div>
    </div>

    <div class="adm-stat" style="padding: 20px; display: flex; align-items: center; gap: 16px;">
        <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(207, 164, 111, 0.1); border: 1px solid rgba(207, 164, 111, 0.2); display: flex; align-items: center; justify-content: center; color: #d4b77d; font-size: 1.4rem; flex-shrink: 0;">
            <i class="bi bi-building"></i>
        </div>
        <div>
            <div style="font-size: 0.72rem; font-weight: 700; color: #8f826f; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Departments</div>
            <div class="adm-stat-val" style="font-size: 1.8rem !important;">{{ number_format($totalDepartments) }}</div>
        </div>
    </div>

    <div class="adm-stat" style="padding: 20px; display: flex; align-items: center; gap: 16px;">
        <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(207, 164, 111, 0.1); border: 1px solid rgba(207, 164, 111, 0.2); display: flex; align-items: center; justify-content: center; color: #d4b77d; font-size: 1.4rem; flex-shrink: 0;">
            <i class="bi bi-diagram-3"></i>
        </div>
        <div>
            <div style="font-size: 0.72rem; font-weight: 700; color: #8f826f; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Sections</div>
            <div class="adm-stat-val" style="font-size: 1.8rem !important;">{{ number_format($totalSections) }}</div>
        </div>
    </div>
</div>

{{-- ─── ATTENDANCE STATS & REAL-TIME TRENDS ─── --}}
<div class="stat-grid stat-grid-4 dash-animate" style="margin-bottom: 28px;">
    <!-- Present Card -->
    <div class="adm-stat" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between; min-height: 120px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="font-size: 0.72rem; font-weight: 700; color: #8f826f; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;" id="presentLabel">Present Today</div>
                <div class="adm-stat-val" id="presentVal" style="font-size: 2rem !important;">{{ number_format($totalPresent) }}</div>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.2); display: flex; align-items: center; justify-content: center; color: #4ade80; font-size: 1.2rem;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px;" id="presentTrendContainer">
            <span class="stat-trend {{ $presentDiff >= 0 ? 'up' : 'down' }}">
                <i class="bi {{ $presentDiff >= 0 ? 'bi-caret-up-fill' : 'bi-caret-down-fill' }}"></i>
                {{ abs($presentDiff) }}
            </span>
            <span class="stat-comparison">vs yesterday</span>
        </div>
    </div>

    <!-- Late Card -->
    <div class="adm-stat" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between; min-height: 120px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="font-size: 0.72rem; font-weight: 700; color: #8f826f; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;" id="lateLabel">Late Today</div>
                <div class="adm-stat-val" id="lateVal" style="font-size: 2rem !important;">{{ number_format($totalLate) }}</div>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.2); display: flex; align-items: center; justify-content: center; color: #fbbf24; font-size: 1.2rem;">
                <i class="bi bi-clock-fill"></i>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px;" id="lateTrendContainer">
            <span class="stat-trend {{ $lateDiff <= 0 ? 'up' : 'down' }}">
                <i class="bi {{ $lateDiff <= 0 ? 'bi-caret-down-fill' : 'bi-caret-up-fill' }}"></i>
                {{ abs($lateDiff) }}
            </span>
            <span class="stat-comparison">vs yesterday</span>
        </div>
    </div>

    <!-- Absent Card -->
    <div class="adm-stat" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between; min-height: 120px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="font-size: 0.72rem; font-weight: 700; color: #8f826f; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;" id="absentLabel">Absent Today</div>
                <div class="adm-stat-val" id="absentVal" style="font-size: 2rem !important;">{{ number_format($totalAbsent) }}</div>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.2); display: flex; align-items: center; justify-content: center; color: #f87171; font-size: 1.2rem;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px;" id="absentTrendContainer">
            <span class="stat-trend {{ $absentDiff <= 0 ? 'up' : 'down' }}">
                <i class="bi {{ $absentDiff <= 0 ? 'bi-caret-down-fill' : 'bi-caret-up-fill' }}"></i>
                {{ abs($absentDiff) }}
            </span>
            <span class="stat-comparison">vs yesterday</span>
        </div>
    </div>

    <!-- Overall Rate Card -->
    <div class="adm-stat" style="padding: 20px; display: flex; flex-direction: column; justify-content: space-between; min-height: 120px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div style="font-size: 0.72rem; font-weight: 700; color: #8f826f; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Overall Rate</div>
                <div class="adm-stat-val" id="rateVal" style="font-size: 2rem !important;">{{ $attendanceRate }}%</div>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(207, 164, 111, 0.1); border: 1px solid rgba(207, 164, 111, 0.2); display: flex; align-items: center; justify-content: center; color: #d4b77d; font-size: 1.2rem;">
                <i class="bi bi-speedometer2"></i>
            </div>
        </div>
        <div style="margin-top: 10px;">
            <div class="perf-bar" style="margin-top:8px;">
                <div id="rateProgressFill" class="perf-bar-fill {{ $attendanceRate >= 80 ? 'high' : ($attendanceRate >= 60 ? 'medium' : 'low') }}" style="width:{{ $attendanceRate }}%"></div>
            </div>
        </div>
    </div>
</div>

{{-- ─── LIVE ATTENDANCE QR SESSIONS ─── --}}
<div class="dash-animate" style="margin-bottom: 28px;">
    <x-card type="section" class="adm-card" style="min-width:0;" aria-live="polite">
        <x-slot:title>
            <div class="ent-section-title-icon" style="background:rgba(74,222,128,0.12);color:var(--ent-success);">
                <i class="bi bi-broadcast"></i>
            </div>
            Live QR Sessions
            @if($activeSessionCount > 0)
                <span class="ent-badge ent-badge-success" id="activeSessionBadge">{{ $activeSessionCount }} active</span>
            @endif
        </x-slot:title>

        <div class="table-responsive" style="margin: -20px;">
            <table class="adm-table table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>Subject & Teacher</th>
                        <th>Checked In</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activeSessions->take(5) as $session)
                    <tr>
                        <td data-label="Subject">
                            <div style="font-weight:600;font-size:0.82rem;">{{ $session->subject?->name ?? $session->subject_code }}</div>
                            <div class="ent-text-muted" style="font-size:0.72rem;">{{ $session->creator?->name ?? 'Unknown' }}</div>
                        </td>
                        <td data-label="Checked In">
                            <span class="ent-badge ent-badge-neutral">{{ $session->checked_in_count }}</span>
                        </td>
                        <td data-label="Status">
                            <span class="session-status-badge active">
                                <span class="pulse-dot active"></span>
                                {{ $session->qr_status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3">
                            <div class="ent-empty" style="padding:32px 16px; border: none;">
                                <div class="ent-empty-icon" style="width:48px;height:48px;font-size:1.25rem;">
                                    <i class="bi bi-qr-code"></i>
                                </div>
                                <div class="ent-empty-text">No active QR sessions right now.</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>

{{-- ─── CHARTS & AT-RISK STUDENTS ─── --}}
<div class="row g-4 dash-animate" style="margin-bottom: 28px;">
    {{-- Weekly Chart --}}
    <div class="col-lg-7 col-12">
        <x-card type="section" class="adm-card" style="min-width:0; height: 100%;" icon="bi bi-bar-chart-line-fill" title="Weekly Attendance Trend">
            <x-slot:headerActions>
                <div style="display:flex;gap:6px;">
                    <span class="ent-badge ent-badge-success"><i class="bi bi-circle-fill" style="font-size:0.45rem;"></i> Present</span>
                    <span class="ent-badge ent-badge-warning"><i class="bi bi-circle-fill" style="font-size:0.45rem;"></i> Late</span>
                    <span class="ent-badge ent-badge-danger"><i class="bi bi-circle-fill" style="font-size:0.45rem;"></i> Absent</span>
                </div>
            </x-slot:headerActions>
            
            <div id="weeklyChart" class="ent-chart-container" style="min-height:260px;"></div>
        </x-card>
    </div>

    {{-- At-Risk Students --}}
    <div class="col-lg-5 col-12">
        <x-card type="section" class="adm-card" style="min-width:0; height: 100%;">
            <x-slot:title>
                <div class="ent-section-title-icon" style="background:rgba(248,113,113,0.12);color:var(--ent-danger);">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                At-Risk Students
            </x-slot:title>
            <x-slot:headerActions>
                <a href="{{ route('admin.students') }}" class="ent-btn ent-btn-xs ent-btn-ghost">View All <i class="bi bi-arrow-right"></i></a>
            </x-slot:headerActions>
            
            <div class="table-responsive" style="margin: -20px;">
                <table class="adm-table table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Rate</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($atRiskStudents->take(5) as $student)
                        <tr>
                            <td data-label="Student">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div class="ent-avatar ent-avatar-round" style="width:28px;height:28px;font-size:0.65rem;">
                                        <img src="{{ $student->profile_image ? (str_starts_with($student->profile_image, 'http') ? $student->profile_image : asset('storage/'.$student->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&background=800000&color=fff&size=28' }}" alt="">
                                    </div>
                                    <span class="ent-truncate" style="font-weight:600;font-size:0.8rem;max-width:120px;">{{ $student->name }}</span>
                                </div>
                            </td>
                            <td data-label="Course"><span style="font-size:0.75rem;" class="ent-text-muted">{{ $student->course }}</span></td>
                            <td data-label="Rate">
                                <span class="risk-badge {{ $student->attendance_rate >= 70 ? 'watch' : 'critical' }}">
                                    {{ $student->attendance_rate }}%
                                </span>
                            </td>
                            <td data-label="Action">
                                <a href="{{ route('admin.student', $student->id) }}" class="ent-btn ent-btn-xs ent-btn-ghost">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4">
                                <div class="ent-empty" style="padding:32px 16px; border: none;">
                                    <div class="ent-empty-icon" style="width:48px;height:48px;font-size:1.25rem;background:rgba(74,222,128,0.08);color:var(--ent-success);">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                    <div class="ent-empty-text">All students are performing well.</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>

{{-- ─── HOLIDAY & EVENTS CALENDAR ─── --}}
@php
    $hcalStart = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $hcalEnd = $hcalStart->copy()->endOfMonth();
    $hcalPrev = $hcalStart->copy()->subMonth();
    $hcalNext = $hcalStart->copy()->addMonth();
    $hcalStartDow = $hcalStart->dayOfWeek;
    $hcalIsCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $hcalToday = now()->day;
@endphp

<x-card type="section" class="adm-card dash-animate" style="margin-bottom: 28px;" title="Holiday & Events Calendar">
    <x-slot:icon>
        <div class="ent-section-title-icon" style="background:rgba(248,113,113,0.12);color:#f87171;">
            <i class="bi bi-calendar-heart-fill"></i>
        </div>
    </x-slot:icon>
    <x-slot:headerActions>
        <button type="button" class="ent-btn ent-btn-sm ent-btn-primary" onclick="openHcalModal()">
            <i class="bi bi-plus-lg"></i> Add Event
        </button>
    </x-slot:headerActions>
    
    <div style="padding:16px 20px;">
        <div class="hcal-container">
            {{-- Calendar Pane --}}
            <div class="hcal-calendar-pane">
                <div class="hcal-nav">
                    <a href="?hcal_year={{ $hcalPrev->year }}&hcal_month={{ $hcalPrev->month }}" class="hcal-nav-btn">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <div class="hcal-month-label">{{ $hcalStart->format('F Y') }}</div>
                    <a href="?hcal_year={{ $hcalNext->year }}&hcal_month={{ $hcalNext->month }}" class="hcal-nav-btn">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </div>

                <div class="hcal-day-labels">
                    @foreach(['S','M','T','W','T','F','S'] as $lbl)
                        <div class="hcal-day-label">{{ $lbl }}</div>
                    @endforeach
                </div>

                <div class="hcal-grid">
                    {{-- Empty cells before first day --}}
                    @for($i = 0; $i < $hcalStartDow; $i++)
                        <div class="hcal-day empty"></div>
                    @endfor

                    @for($d = 1; $d <= $hcalEnd->day; $d++)
                        @php
                            $dateKey = \Carbon\Carbon::create($calYear, $calMonth, $d)->format('Y-m-d');
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
                        <div class="hcal-day{{ $cls }}" @if($hasEvents) onclick="scrollToEvent('{{ $dateKey }}')" title="{{ collect($dayEvents)->pluck('name')->join(', ') }}" @endif>
                            <div class="hcal-day-num">{{ $d }}</div>
                            @if($hasEvents)
                                <div class="hcal-dots">
                                    @foreach(collect($dayEvents)->unique('type')->take(3) as $evt)
                                        <div class="hcal-dot {{ $evt['type'] }}"></div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>

                <div class="hcal-legend">
                    <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#dc2626;"></div> National</div>
                    <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#d97706;"></div> Local</div>
                    <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#7c2d12;"></div> School</div>
                    <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#6366f1;"></div> No Class</div>
                    <div class="hcal-legend-item"><div class="hcal-legend-dot" style="background:#60a5fa;"></div> Announcement</div>
                </div>
            </div>

            {{-- Events Sidebar --}}
            <div class="hcal-events-pane">
                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--ent-text-muted);margin-bottom:12px;">
                    <i class="bi bi-calendar-event"></i> Upcoming Events
                </div>
                @forelse($hcalUpcoming as $evt)
                    <div class="hcal-event-card" data-type="{{ $evt->type }}" data-date="{{ is_object($evt->date) ? $evt->date->format('Y-m-d') : $evt->date }}" id="evt-{{ is_object($evt->date) ? $evt->date->format('Y-m-d') : $evt->date }}">
                        <div class="hcal-event-type {{ $evt->type }}">
                            <i class="bi {{ $evt->source === 'holiday' ? 'bi-calendar-heart' : 'bi-megaphone' }}"></i>
                            {{ $evt->type_label }}
                        </div>
                        <div class="hcal-event-name">{{ $evt->name }}</div>
                        <div class="hcal-event-date">
                            <i class="bi bi-calendar3"></i> {{ $evt->date_formatted }}
                            @if(isset($evt->author))
                                @php
                                    $evtRole = 'admin';
                                    if (isset($evt->source) && $evt->source === 'announcement' && isset($evt->author_role)) {
                                        $evtRole = $evt->author_role;
                                    }
                                    $evtIsAdminRole = $evtRole === 'admin';
                                    $dashBadgeBg = $evtIsAdminRole ? 'rgba(207,164,111,0.15)' : 'rgba(139,90,43,0.15)';
                                    $dashBadgeBorder = $evtIsAdminRole ? 'rgba(207,164,111,0.35)' : 'rgba(139,90,43,0.35)';
                                    $dashBadgeColor = $evtIsAdminRole ? '#CFA46F' : '#8B5A2B';
                                    $dashRoleLabel = $evtIsAdminRole ? 'Admin' : 'Instructor';
                                @endphp
                                · <i class="bi bi-person"></i> {{ $evt->author }}
                                <span style="display:inline-flex;align-items:center;gap:3px;margin-left:4px;padding:1px 7px;border-radius:99px;font-size:0.6rem;font-weight:700;background:{{ $dashBadgeBg }};border:1px solid {{ $dashBadgeBorder }};color:{{ $dashBadgeColor }};vertical-align:middle;">
                                    <span style="width:4px;height:4px;border-radius:50%;background:{{ $dashBadgeColor }};display:inline-block;"></span>
                                    {{ $dashRoleLabel }}
                                </span>
                            @endif
                        </div>
                        @if($evt->description)
                            <div class="hcal-event-desc">{{ $evt->description }}</div>
                        @endif

                        @if($evt->source === 'holiday')
                        <div class="hcal-event-actions">
                            <button type="button" class="hcal-event-action-btn" onclick="openHcalEditModal({{ $evt->id }}, '{{ addslashes($evt->name) }}', '{{ $evt->description ? addslashes($evt->description) : '' }}', '{{ $evt->type }}', '{{ is_object($evt->date) ? $evt->date->format('Y-m-d') : $evt->date }}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('admin.calendar.destroy', $evt->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this holiday?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="hcal-event-action-btn danger"><i class="bi bi-trash3"></i></button>
                            </form>
                        </div>
                        @endif
                    </div>
                @empty
                    <div class="hcal-empty">
                        <div class="hcal-empty-icon"><i class="bi bi-calendar-x"></i></div>
                        <div class="hcal-empty-text">No upcoming events</div>
                    </div>
                @endforelse

                <button type="button" class="hcal-add-btn" onclick="openHcalModal()" style="margin-top:8px;">
                    <i class="bi bi-plus-circle"></i> Add Holiday / Event
                </button>
            </div>
        </div>
    </div>
</x-card>

{{-- ─── ADD/EDIT HOLIDAY MODAL ─── --}}
<div class="hcal-modal-overlay" id="hcalModalOverlay">
    <div class="hcal-modal">
        <div class="hcal-modal-header">
            <div class="hcal-modal-title" id="hcalModalTitle">Add Holiday / Event</div>
            <button type="button" class="hcal-modal-close" onclick="closeHcalModal()">×</button>
        </div>
        <form id="hcalForm" method="POST" action="{{ route('admin.calendar.store') }}">
            @csrf
            <div id="hcalMethodField"></div>
            <div class="hcal-modal-body">
                <div class="hcal-form-group">
                    <label class="hcal-form-label">Event Name *</label>
                    <input type="text" name="name" class="hcal-form-input" id="hcalName" required placeholder="e.g. Independence Day">
                </div>
                <div class="hcal-form-group">
                    <label class="hcal-form-label">Date *</label>
                    <input type="date" name="date" class="hcal-form-input" id="hcalDate" required>
                </div>
                <div class="hcal-form-group">
                    <label class="hcal-form-label">Type *</label>
                    <select name="type" class="hcal-form-select" id="hcalType" required>
                        <option value="national">National Holiday</option>
                        <option value="local">Local Holiday</option>
                        <option value="school">School Holiday</option>
                        <option value="no_class">No Classes</option>
                    </select>
                </div>
                <div class="hcal-form-group" style="margin-bottom:0;">
                    <label class="hcal-form-label">Description</label>
                    <textarea name="description" class="hcal-form-textarea" id="hcalDesc" placeholder="Optional description..."></textarea>
                </div>
            </div>
            <div class="hcal-modal-footer">
                <button type="button" class="hcal-btn-cancel" onclick="closeHcalModal()">Cancel</button>
                <button type="submit" class="hcal-btn-submit" id="hcalSubmitBtn">
                    <i class="bi bi-check-lg"></i> Save Event
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Ticking Live Clock ───
    function updateClock() {
        const clockEl = document.getElementById('dashLiveClock');
        if (!clockEl) return;
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('en-US', { hour12: true });
    }
    setInterval(updateClock, 1000);

    // ─── Date Range Dropdown Toggle ───
    const dateRangeBtn = document.getElementById('dateRangeBtn');
    const dateRangeDropdown = document.getElementById('dateRangeDropdown');
    
    if (dateRangeBtn && dateRangeDropdown) {
        dateRangeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dateRangeDropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', function() {
            dateRangeDropdown.classList.remove('show');
        });
    }

    // ─── Date Range Change Handler ───
    const options = document.querySelectorAll('.date-range-option');
    options.forEach(opt => {
        opt.addEventListener('click', function() {
            options.forEach(o => o.classList.remove('active'));
            this.classList.add('active');
            const range = this.getAttribute('data-range');
            document.getElementById('dateRangeLabel').textContent = this.textContent;
            fetchDashboardStats(range);
        });
    });

    // ─── AJAX Fetch Dashboard Stats ───
    function fetchDashboardStats(range) {
        // Apply slight loading opacity
        const statsToFade = ['presentVal', 'lateVal', 'absentVal', 'rateVal'];
        statsToFade.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.opacity = '0.5';
        });

        fetch(`{{ route('admin.dashboard.stats') }}?range=${range}`)
            .then(response => response.json())
            .then(data => {
                // Restore opacity
                statsToFade.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.style.opacity = '1';
                });

                // Update counts
                document.getElementById('presentVal').textContent = Number(data.present).toLocaleString();
                document.getElementById('lateVal').textContent = Number(data.late).toLocaleString();
                document.getElementById('absentVal').textContent = Number(data.absent).toLocaleString();
                document.getElementById('rateVal').textContent = data.rate + '%';
                
                // Update labels
                const labelSuffix = range === 'today' ? 'Today' : (range === 'yesterday' ? 'Yesterday' : (range === 'this_week' ? 'This Week' : 'This Month'));
                document.getElementById('presentLabel').textContent = 'Present ' + labelSuffix;
                document.getElementById('lateLabel').textContent = 'Late ' + labelSuffix;
                document.getElementById('absentLabel').textContent = 'Absent ' + labelSuffix;
                
                // Show/Hide comparison trends (only relevant for Today)
                const trends = ['presentTrendContainer', 'lateTrendContainer', 'absentTrendContainer'];
                trends.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.style.display = range === 'today' ? '' : 'none';
                    }
                });
                
                // Update Progress Bar color and width
                const progress = document.getElementById('rateProgressFill');
                if (progress) {
                    progress.style.width = data.rate + '%';
                    progress.className = 'perf-bar-fill ' + (data.rate >= 80 ? 'high' : (data.rate >= 60 ? 'medium' : 'low'));
                }
                
                // Update Chart
                if (window.weeklyChartInstance) {
                    window.weeklyChartInstance.updateOptions({
                        xaxis: { categories: data.chart.labels }
                    });
                    window.weeklyChartInstance.updateSeries([
                        { name: 'Present', data: data.chart.present },
                        { name: 'Late', data: data.chart.late },
                        { name: 'Absent', data: data.chart.absent }
                    ]);
                }
            })
            .catch(err => {
                console.error('Error fetching dashboard stats:', err);
                statsToFade.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.style.opacity = '1';
                });
            });
    }

    // ─── ApexCharts Trend Line Render ───
    var chartOptions = {
        series: [
            { name: 'Present', data: {!! json_encode($weeklyPresent) !!} },
            { name: 'Late', data: {!! json_encode($weeklyLate) !!} },
            { name: 'Absent', data: {!! json_encode($weeklyAbsent) !!} }
        ],
        chart: {
            type: 'bar',
            height: 260,
            stacked: true,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif',
            background: 'transparent',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: { enabled: true, delay: 150 },
                dynamicAnimation: { enabled: true, speed: 350 }
            }
        },
        colors: ['#4ade80', '#fbbf24', '#f87171'],
        plotOptions: {
            bar: {
                borderRadius: 6,
                borderRadiusApplication: 'end',
                horizontal: false,
                columnWidth: '50%',
            },
        },
        dataLabels: { enabled: false },
        xaxis: {
            categories: {!! json_encode($weeklyLabels) !!},
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: '#8f826f', fontSize: '11px', fontWeight: 500 } }
        },
        yaxis: {
            labels: { style: { colors: '#8f826f', fontSize: '11px' } }
        },
        grid: {
            borderColor: 'rgba(255,255,255,0.04)',
            strokeDashArray: 4,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { left: 8, right: 8 }
        },
        legend: { show: false },
        fill: { opacity: 0.9 },
        theme: { mode: 'dark' },
        tooltip: {
            theme: 'dark',
            style: { fontSize: '12px' },
            y: { formatter: function (val) { return val + " students" } }
        }
    };

    var chart = new ApexCharts(document.querySelector("#weeklyChart"), chartOptions);
    chart.render();
    window.weeklyChartInstance = chart; // Store globally for AJAX updates
});

// ─── HOLIDAY CALENDAR MODALS ───
function openHcalModal() {
    document.getElementById('hcalModalTitle').textContent = 'Add Holiday / Event';
    document.getElementById('hcalForm').action = '{{ route("admin.calendar.store") }}';
    document.getElementById('hcalMethodField').innerHTML = '';
    document.getElementById('hcalName').value = '';
    document.getElementById('hcalDate').value = '';
    document.getElementById('hcalType').value = 'national';
    document.getElementById('hcalDesc').value = '';
    document.getElementById('hcalSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Save Event';
    document.getElementById('hcalModalOverlay').classList.add('active');
}

function openHcalEditModal(id, name, desc, type, date) {
    document.getElementById('hcalModalTitle').textContent = 'Edit Holiday';
    document.getElementById('hcalForm').action = '/admin/calendar/' + id;
    document.getElementById('hcalMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('hcalName').value = name;
    document.getElementById('hcalDate').value = date;
    document.getElementById('hcalType').value = type;
    document.getElementById('hcalDesc').value = desc;
    document.getElementById('hcalSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update Event';
    document.getElementById('hcalModalOverlay').classList.add('active');
}

function closeHcalModal() {
    document.getElementById('hcalModalOverlay').classList.remove('active');
}

document.getElementById('hcalModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeHcalModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeHcalModal();
});

function scrollToEvent(dateKey) {
    const el = document.getElementById('evt-' + dateKey);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.style.boxShadow = '0 0 0 2px rgba(207,164,111,0.5)';
        setTimeout(() => { el.style.boxShadow = ''; }, 2000);
    }
}
</script>
@endpush
