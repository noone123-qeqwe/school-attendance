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
    <div class="adm-stat" style="padding: 22px 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 10px;">
        <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(207, 164, 111, 0.1); border: 1px solid rgba(207, 164, 111, 0.2); display: flex; align-items: center; justify-content: center; color: #d4b77d; font-size: 1.4rem; flex-shrink: 0;">
            <i class="bi bi-people-fill"></i>
        </div>
        <div style="width: 100%; display: flex; flex-direction: column; align-items: center; text-align: center;">
            <div style="font-size: 0.72rem; font-weight: 700; color: #8f826f; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; text-align: center; width: 100%;">Total Students</div>
            <div class="adm-stat-val" style="font-size: 1.8rem !important; text-align: center; width: 100%; display: block; line-height: 1.1;">{{ number_format($totalStudents) }}</div>
        </div>
    </div>
    
    <div class="adm-stat" style="padding: 22px 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 10px;">
        <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(207, 164, 111, 0.1); border: 1px solid rgba(207, 164, 111, 0.2); display: flex; align-items: center; justify-content: center; color: #d4b77d; font-size: 1.4rem; flex-shrink: 0;">
            <i class="bi bi-person-workspace"></i>
        </div>
        <div style="width: 100%; display: flex; flex-direction: column; align-items: center; text-align: center;">
            <div style="font-size: 0.72rem; font-weight: 700; color: #8f826f; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; text-align: center; width: 100%;">Instructors</div>
            <div class="adm-stat-val" style="font-size: 1.8rem !important; text-align: center; width: 100%; display: block; line-height: 1.1;">{{ number_format($totalTeachers) }}</div>
        </div>
    </div>

    <div class="adm-stat" style="padding: 22px 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 10px;">
        <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(207, 164, 111, 0.1); border: 1px solid rgba(207, 164, 111, 0.2); display: flex; align-items: center; justify-content: center; color: #d4b77d; font-size: 1.4rem; flex-shrink: 0;">
            <i class="bi bi-building"></i>
        </div>
        <div style="width: 100%; display: flex; flex-direction: column; align-items: center; text-align: center;">
            <div style="font-size: 0.72rem; font-weight: 700; color: #8f826f; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; text-align: center; width: 100%;">Departments</div>
            <div class="adm-stat-val" style="font-size: 1.8rem !important; text-align: center; width: 100%; display: block; line-height: 1.1;">{{ number_format($totalDepartments) }}</div>
        </div>
    </div>

    <div class="adm-stat" style="padding: 22px 16px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 10px;">
        <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(207, 164, 111, 0.1); border: 1px solid rgba(207, 164, 111, 0.2); display: flex; align-items: center; justify-content: center; color: #d4b77d; font-size: 1.4rem; flex-shrink: 0;">
            <i class="bi bi-diagram-3"></i>
        </div>
        <div style="width: 100%; display: flex; flex-direction: column; align-items: center; text-align: center;">
            <div style="font-size: 0.72rem; font-weight: 700; color: #8f826f; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; text-align: center; width: 100%;">Sections</div>
            <div class="adm-stat-val" style="font-size: 1.8rem !important; text-align: center; width: 100%; display: block; line-height: 1.1;">{{ number_format($totalSections) }}</div>
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

        <div class="table-responsive" style="margin: -20px; padding: 12px 20px;">
            <table class="adm-table" style="margin-bottom: 0; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 50%; text-align: left;">Subject & Teacher</th>
                        <th style="width: 25%; text-align: center;">Checked In</th>
                        <th style="width: 25%; text-align: right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activeSessions->take(5) as $session)
                    <tr>
                        <td data-label="Subject" style="text-align: left;">
                            <div style="font-weight:600;font-size:0.82rem;">{{ $session->subject?->name ?? $session->subject_code }}</div>
                            <div class="ent-text-muted" style="font-size:0.72rem;">{{ $session->creator?->name ?? 'Unknown' }}</div>
                        </td>
                        <td data-label="Checked In" style="text-align: center;">
                            <span class="ent-badge ent-badge-neutral">{{ $session->checked_in_count }}</span>
                        </td>
                        <td data-label="Status" style="text-align: right;">
                            <span class="session-status-badge active" style="margin-left: auto;">
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

{{-- ─── AT-RISK STUDENTS ─── --}}
<div class="dash-animate" style="margin-bottom: 28px;">
    <x-card type="section" class="adm-card" style="min-width:0;">
        <x-slot:title>
            <div class="ent-section-title-icon" style="background:rgba(248,113,113,0.12);color:var(--ent-danger);">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            At-Risk Students
        </x-slot:title>
        <x-slot:headerActions>
            <a href="{{ route('admin.students') }}" class="ent-btn ent-btn-xs ent-btn-ghost">View All <i class="bi bi-arrow-right"></i></a>
        </x-slot:headerActions>
        
        <div class="table-responsive" style="margin: -20px; padding: 12px 20px;">
            <table class="adm-table" style="margin-bottom: 0; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 42%; text-align: left;">Student</th>
                        <th style="width: 28%; text-align: left;">Course</th>
                        <th style="width: 15%; text-align: center;">Rate</th>
                        <th style="width: 15%; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($atRiskStudents->take(5) as $student)
                    <tr>
                        <td data-label="Student" style="text-align: left;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="ent-avatar ent-avatar-round" style="width:30px;height:30px;font-size:0.7rem;flex-shrink:0;">
                                    <img src="{{ $student->profile_image ? (str_starts_with($student->profile_image, 'http') ? $student->profile_image : asset('storage/'.$student->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&background=800000&color=fff&size=30' }}" alt="">
                                </div>
                                <span class="ent-truncate" style="font-weight:600;font-size:0.82rem;color:#f3ede4;max-width:180px;">{{ $student->name }}</span>
                            </div>
                        </td>
                        <td data-label="Course" style="text-align: left;"><span style="font-size:0.75rem;color:#b39b82;" class="ent-text-muted">{{ $student->course }}</span></td>
                        <td data-label="Rate" style="text-align: center;">
                            <span class="risk-badge {{ $student->attendance_rate >= 70 ? 'watch' : 'critical' }}">
                                {{ $student->attendance_rate }}%
                            </span>
                        </td>
                        <td data-label="Action" style="text-align: right;">
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

@endsection

@push('scripts')
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
        const statsToFade = ['presentVal', 'lateVal', 'absentVal', 'rateVal'];
        statsToFade.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.opacity = '0.5';
        });

        fetch(`{{ route('admin.dashboard.stats') }}?range=${range}`)
            .then(response => response.json())
            .then(data => {
                statsToFade.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.style.opacity = '1';
                });

                document.getElementById('presentVal').textContent = Number(data.present).toLocaleString();
                document.getElementById('lateVal').textContent = Number(data.late).toLocaleString();
                document.getElementById('absentVal').textContent = Number(data.absent).toLocaleString();
                document.getElementById('rateVal').textContent = data.rate + '%';
                
                const labelSuffix = range === 'today' ? 'Today' : (range === 'yesterday' ? 'Yesterday' : (range === 'this_week' ? 'This Week' : 'This Month'));
                document.getElementById('presentLabel').textContent = 'Present ' + labelSuffix;
                document.getElementById('lateLabel').textContent = 'Late ' + labelSuffix;
                document.getElementById('absentLabel').textContent = 'Absent ' + labelSuffix;
                
                const trends = ['presentTrendContainer', 'lateTrendContainer', 'absentTrendContainer'];
                trends.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.style.display = range === 'today' ? '' : 'none';
                    }
                });
                
                const progress = document.getElementById('rateProgressFill');
                if (progress) {
                    progress.style.width = data.rate + '%';
                    progress.className = 'perf-bar-fill ' + (data.rate >= 80 ? 'high' : (data.rate >= 60 ? 'medium' : 'low'));
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
});
</script>
@endpush
