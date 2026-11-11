@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')

@php
    $presentDiff = $totalPresent - $yesterdayPresent;
    $lateDiff = $totalLate - $yesterdayLate;
    $absentDiff = $totalAbsent - $yesterdayAbsent;
    $rateDiff = $attendanceRate - $yesterdayRate;
@endphp

<header class="admin-home-heading">
    <div>
        <p class="admin-eyebrow">{{ now()->format('l, F j') }}</p>
        <h1>School overview</h1>
        <p>Start with a task, or check today's attendance below.</p>
    </div>
    <a href="{{ route('admin.reports') }}" class="saas-btn saas-btn-secondary"><i class="bi bi-bar-chart" aria-hidden="true"></i> View reports</a>
</header>

<section aria-labelledby="adminTasksTitle" class="admin-home-section">
    <h2 id="adminTasksTitle">What would you like to do?</h2>
    <div class="admin-task-grid">
        @foreach([
            ['Students', 'Find a student, review registrations or update a record.', 'admin.students', 'bi-people'],
            ['Class schedules', 'Assign sections and teachers, then publish the timetable.', 'admin.class-schedules.index', 'bi-calendar3'],
            ['Attendance records', 'Look up attendance by class, date or student.', 'admin.attendance', 'bi-clipboard-check'],
            ['School calendar', 'Review holidays and upcoming school dates.', 'admin.calendar', 'bi-calendar-event'],
        ] as [$label, $description, $destination, $icon])
            <a href="{{ route($destination) }}" class="admin-task-card">
                <span class="admin-task-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                <span><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
</section>

<section aria-labelledby="adminTodayTitle" class="admin-home-section">
    <div class="admin-section-title-row">
        <h2 id="adminTodayTitle">Today at a glance</h2>
        <span class="admin-secondary-text">{{ $totalLate }} late · {{ $totalAbsent }} absent</span>
    </div>
    <div class="admin-dashboard-kpis">
        <x-kpi-card label="Students" :value="$totalStudents" icon="bi-people" accent="gold" />
        <x-kpi-card label="Teachers" :value="$totalTeachers" icon="bi-person-badge" accent="info" />
        <x-kpi-card label="Present today" :value="$totalPresent" icon="bi-check-circle" accent="success"
            :delta="($presentDiff >= 0 ? '+'.$presentDiff : (string)$presentDiff)"
            :deltaType="$presentDiff >= 0 ? 'positive' : 'negative'" deltaText="vs yesterday" />
        <x-kpi-card label="Attendance rate" :value="$attendanceRate . '%'" icon="bi-percent" accent="gold"
            :delta="($rateDiff >= 0 ? '+'.$rateDiff : (string)$rateDiff) . '%'"
            :deltaType="$rateDiff >= 0 ? 'positive' : 'negative'" deltaText="vs yesterday" />
    </div>
</section>

<section aria-labelledby="adminReviewTitle" class="admin-home-section">
    <h2 id="adminReviewTitle">{{ Auth::user()->admin_sub_role === 'auditor' ? 'Review attendance' : 'Review requests' }}</h2>
    <div class="admin-review-links">
        @unless(Auth::user()->admin_sub_role === 'auditor')
            <a href="{{ route('admin.students', ['status' => 'pending']) }}"><i class="bi bi-person-check" aria-hidden="true"></i> Student registrations <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        @endunless
        <a href="{{ route('admin.excuses') }}"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Excuse reviews <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        <a href="{{ route('admin.corrections') }}"><i class="bi bi-pencil-square" aria-hidden="true"></i> Attendance corrections <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
    </div>
</section>

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
<div class="admin-dashboard-panels">
<div class="dash-animate admin-dashboard-panel">
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
<div class="dash-animate admin-dashboard-panel" id="at-risk-section">
    <x-card type="section" class="adm-card" style="min-width:0;">
        <x-slot:title>
            <div class="ent-section-title-icon" style="background:rgba(248,113,113,0.12);color:var(--ent-danger);">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            At-Risk Students
        </x-slot:title>
        <x-slot:headerActions>
            <a href="{{ route('admin.early-warnings') }}" class="ent-btn ent-btn-xs ent-btn-ghost">View all warnings <i class="bi bi-arrow-right"></i></a>
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
                                    <img src="{{ $student->profile_image ? (str_starts_with($student->profile_image, 'http') ? $student->profile_image : public_storage_url($student->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&background=800000&color=fff&size=30' }}" alt="">
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
                                <div class="ent-empty-text">No students with low attendance in the current results.</div>
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

@endsection
