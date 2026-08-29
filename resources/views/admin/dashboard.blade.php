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
<div class="premium-hero-card dash-animate">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
        <div>
            <h1 style="color: #ffffff; font-weight: 800; margin: 0 0 6px 0; font-size: clamp(1.4rem, 4vw, 2rem); letter-spacing: -0.5px;">Command Center</h1>
            <div style="color: #b39b82; font-size: 0.88rem; font-weight: 500;">
                Live operational analytics & campus attendance tracking
            </div>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="hero-clock-pill">
                <div class="hero-clock-time">
                    <i class="bi bi-clock"></i> <span id="dashLiveClock">{{ now()->format('h:i:s A') }}</span>
                </div>
                <div class="hero-clock-date" id="dashLiveDate">{{ now()->format('l, F j, Y') }}</div>
            </div>
            
            <a href="{{ route('admin.attendance.pdf') }}" class="btn-modern-gold" style="padding: 10px 18px;">
                <i class="bi bi-cloud-arrow-down-fill"></i> <span>Report</span>
            </a>
        </div>
    </div>
</div>

{{-- ─── QUICK ACTIONS PANEL ─── --}}
<div class="modern-qa-grid dash-animate">
    <a href="{{ route('admin.students') }}" class="modern-qa-tile">
        <div class="qa-icon-wrap" style="background: rgba(34, 197, 94, 0.14); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.25);">
            <i class="bi bi-people-fill"></i>
        </div>
        <span class="qa-label">Students</span>
    </a>
    <a href="{{ route('admin.teachers') }}" class="modern-qa-tile">
        <div class="qa-icon-wrap" style="background: rgba(59, 130, 246, 0.14); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.25);">
            <i class="bi bi-person-workspace"></i>
        </div>
        <span class="qa-label">Instructors</span>
    </a>
    <a href="{{ route('admin.attendance') }}" class="modern-qa-tile">
        <div class="qa-icon-wrap" style="background: rgba(239, 68, 68, 0.14); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25);">
            <i class="bi bi-clipboard-check-fill"></i>
        </div>
        <span class="qa-label">Attendance</span>
    </a>
    <a href="{{ route('admin.calendar') }}" class="modern-qa-tile">
        <div class="qa-icon-wrap" style="background: rgba(168, 85, 247, 0.14); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.25);">
            <i class="bi bi-calendar3-fill"></i>
        </div>
        <span class="qa-label">Calendar</span>
    </a>
    <a href="{{ route('admin.announcements.index') }}" class="modern-qa-tile">
        <div class="qa-icon-wrap" style="background: rgba(236, 72, 153, 0.14); color: #f472b6; border: 1px solid rgba(236, 72, 153, 0.25);">
            <i class="bi bi-megaphone-fill"></i>
        </div>
        <span class="qa-label">Announcements</span>
    </a>
    <a href="{{ route('admin.activity.log') }}" class="modern-qa-tile">
        <div class="qa-icon-wrap" style="background: rgba(245, 158, 11, 0.14); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.25);">
            <i class="bi bi-journal-text"></i>
        </div>
        <span class="qa-label">Activity Logs</span>
    </a>
    @if(Auth::user()->admin_sub_role === 'super_admin' || is_null(Auth::user()->admin_sub_role))
    <a href="{{ route('admin.system-health.index') }}" class="modern-qa-tile">
        <div class="qa-icon-wrap" style="background: rgba(20, 184, 166, 0.14); color: #2dd4bf; border: 1px solid rgba(20, 184, 166, 0.25);">
            <i class="bi bi-heart-pulse-fill"></i>
        </div>
        <span class="qa-label">System Health</span>
    </a>
    <a href="{{ route('admin.settings') }}" class="modern-qa-tile">
        <div class="qa-icon-wrap" style="background: rgba(148, 163, 184, 0.14); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.25);">
            <i class="bi bi-sliders"></i>
        </div>
        <span class="qa-label">Settings</span>
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
});
</script>
@endpush
