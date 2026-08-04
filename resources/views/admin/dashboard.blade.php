@extends('layouts.admin_premium')

@section('title', 'Admin Dashboard')

@section('content')

{{-- ─── MOBILE DASHBOARD HEADER ─── --}}
<div class="ent-mobile-header ent-fade-up">
    <div>
        <div class="ent-mobile-header-title">Command Center</div>
        <div class="ent-mobile-header-sub">Attendance & Academic Overview</div>
    </div>
    <div class="ent-mobile-header-date">
        <div class="ent-mobile-header-day">{{ now()->format('d') }}</div>
        <div>{{ now()->format('M Y') }}</div>
        <div>{{ now()->format('D') }}</div>
    </div>
</div>

{{-- ─── DESKTOP HEADER ─── --}}
<div class="ent-dash-header ent-fade-up ent-desktop-only">
    <div>
        <h1 class="ent-dash-title">Command Center</h1>
        <p class="ent-dash-subtitle">Overview of academic and attendance operations — {{ now()->format('l, F j, Y') }}</p>
    </div>
    <div class="ent-dash-actions">
        <span class="ent-btn ent-btn-secondary">
            <i class="bi bi-calendar"></i> Today: {{ now()->format('M d, Y') }}
        </span>
        <a href="{{ route('admin.attendance.pdf') }}" class="ent-btn ent-btn-primary">
            <i class="bi bi-cloud-arrow-down"></i> Generate Report
        </a>
    </div>
</div>

{{-- ─── SYSTEM ALERTS ─── --}}
@if($systemAlerts->count() > 0)
<div class="ent-fade-up ent-delay-1 ent-mb-md">
    @foreach($systemAlerts as $alert)
    <div class="ent-alert {{ $alert->severity === 'critical' ? 'danger' : ($alert->severity === 'warning' ? 'warning' : 'info') }}">
        <div class="ent-alert-icon">
            <i class="bi {{ $alert->icon }}"></i>
        </div>
        <div class="ent-alert-body">
            <div class="ent-alert-text">{{ $alert->message }}</div>
        </div>
        @if($alert->action)
            <a href="{{ $alert->action }}" class="ent-btn ent-btn-sm ent-btn-ghost" style="flex-shrink:0;">View <i class="bi bi-arrow-right"></i></a>
        @endif
    </div>
    @endforeach
</div>
@endif

{{-- ─── PRIMARY KPIs: Entity Counts ─── --}}
<div class="ent-grid ent-grid-4 ent-mb-md ent-fade-up ent-delay-1">
    <div class="ent-kpi-card" data-accent="gold">
        <div class="ent-kpi-icon"><i class="bi bi-people-fill"></i></div>
        <div class="ent-kpi-body">
            <div class="ent-kpi-label">Total Students</div>
            <div class="ent-kpi-value">{{ number_format($totalStudents) }}</div>
        </div>
    </div>

    <div class="ent-kpi-card" data-accent="gold">
        <div class="ent-kpi-icon"><i class="bi bi-person-workspace"></i></div>
        <div class="ent-kpi-body">
            <div class="ent-kpi-label">Instructors</div>
            <div class="ent-kpi-value">{{ number_format($totalTeachers) }}</div>
        </div>
    </div>

    <div class="ent-kpi-card" data-accent="gold">
        <div class="ent-kpi-icon"><i class="bi bi-building"></i></div>
        <div class="ent-kpi-body">
            <div class="ent-kpi-label">Departments</div>
            <div class="ent-kpi-value">{{ number_format($totalDepartments) }}</div>
        </div>
    </div>

    <div class="ent-kpi-card" data-accent="gold">
        <div class="ent-kpi-icon"><i class="bi bi-diagram-3"></i></div>
        <div class="ent-kpi-body">
            <div class="ent-kpi-label">Sections</div>
            <div class="ent-kpi-value">{{ number_format($totalSections) }}</div>
        </div>
    </div>
</div>

{{-- ─── ATTENDANCE KPIs ─── --}}
<div class="ent-grid ent-grid-4 ent-mb-md ent-fade-up ent-delay-2">
    <div class="ent-kpi-card" data-accent="success">
        <div class="ent-kpi-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="ent-kpi-body">
            <div class="ent-kpi-label">Present Today</div>
            <div class="ent-kpi-value">{{ number_format($totalPresent) }}</div>
            @php $presentDiff = $totalPresent - $yesterdayPresent; @endphp
            <div class="ent-kpi-trend {{ $presentDiff >= 0 ? 'up' : 'down' }}">
                <i class="bi bi-{{ $presentDiff >= 0 ? 'arrow-up-short' : 'arrow-down-short' }}"></i>
                {{ abs($presentDiff) }} vs yesterday
            </div>
        </div>
    </div>

    <div class="ent-kpi-card" data-accent="warning">
        <div class="ent-kpi-icon"><i class="bi bi-clock-fill"></i></div>
        <div class="ent-kpi-body">
            <div class="ent-kpi-label">Late Today</div>
            <div class="ent-kpi-value">{{ number_format($totalLate) }}</div>
            @php $lateDiff = $totalLate - $yesterdayLate; @endphp
            <div class="ent-kpi-trend {{ $lateDiff <= 0 ? 'up' : 'down' }}">
                <i class="bi bi-{{ $lateDiff <= 0 ? 'arrow-down-short' : 'arrow-up-short' }}"></i>
                {{ abs($lateDiff) }} vs yesterday
            </div>
        </div>
    </div>

    <div class="ent-kpi-card" data-accent="danger">
        <div class="ent-kpi-icon"><i class="bi bi-x-circle-fill"></i></div>
        <div class="ent-kpi-body">
            <div class="ent-kpi-label">Absent Today</div>
            <div class="ent-kpi-value">{{ number_format($totalAbsent) }}</div>
            @php $absentDiff = $totalAbsent - $yesterdayAbsent; @endphp
            <div class="ent-kpi-trend {{ $absentDiff <= 0 ? 'up' : 'down' }}">
                <i class="bi bi-{{ $absentDiff <= 0 ? 'arrow-down-short' : 'arrow-up-short' }}"></i>
                {{ abs($absentDiff) }} vs yesterday
            </div>
        </div>
    </div>

    <div class="ent-kpi-card" data-accent="{{ $attendanceRate >= 80 ? 'success' : 'danger' }}">
        <div class="ent-kpi-icon"><i class="bi bi-speedometer2"></i></div>
        <div class="ent-kpi-body">
            <div class="ent-kpi-label">Overall Rate</div>
            <div class="ent-kpi-value">{{ $attendanceRate }}%</div>
            @php $rateDiff = $attendanceRate - $yesterdayRate; @endphp
            <div class="ent-kpi-trend {{ $rateDiff >= 0 ? 'up' : 'down' }}">
                <i class="bi bi-{{ $rateDiff >= 0 ? 'arrow-up-short' : 'arrow-down-short' }}"></i>
                {{ abs($rateDiff) }}% vs yesterday
            </div>
            <div class="ent-progress" style="margin-top:8px;">
                <div class="ent-progress-fill {{ $attendanceRate >= 80 ? 'success' : 'danger' }}" style="width:{{ $attendanceRate }}%"></div>
            </div>
        </div>
    </div>
</div>

{{-- ─── WEEKLY CHART ─── --}}
<div class="ent-section ent-mb-md ent-fade-up ent-delay-3">
    <div class="ent-section-header">
        <div class="ent-section-title">
            <div class="ent-section-title-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
            Weekly Attendance Trend
        </div>
        <div style="display:flex;gap:6px;">
            <span class="ent-badge ent-badge-success"><i class="bi bi-circle-fill" style="font-size:0.45rem;"></i> Present</span>
            <span class="ent-badge ent-badge-warning"><i class="bi bi-circle-fill" style="font-size:0.45rem;"></i> Late</span>
            <span class="ent-badge ent-badge-danger"><i class="bi bi-circle-fill" style="font-size:0.45rem;"></i> Absent</span>
        </div>
    </div>
    <div class="ent-section-body">
        <div id="weeklyChart" class="ent-chart-container" style="min-height:260px;"></div>
    </div>
</div>

{{-- ─── LIVE SESSIONS + AT-RISK (Two Column) ─── --}}
<div class="ent-grid ent-grid-7-5 ent-mb-md ent-fade-up ent-delay-3">

    {{-- Live QR Sessions --}}
    <div class="ent-section" style="min-width:0;">
        <div class="ent-section-header">
            <div class="ent-section-title">
                <div class="ent-section-title-icon" style="background:rgba(74,222,128,0.12);color:var(--ent-success);">
                    <i class="bi bi-broadcast"></i>
                </div>
                Live QR Sessions
                @if($activeSessionCount > 0)
                    <span class="ent-badge ent-badge-success">{{ $activeSessionCount }} active</span>
                @endif
            </div>
        </div>
        <div class="ent-section-body no-pad">
            <div class="ent-scroll-x">
                <table class="ent-table" style="min-width:400px;">
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
                                <span class="ent-badge {{ strtolower($session->qr_status) == 'active' ? 'ent-badge-success' : 'ent-badge-warning' }}">
                                    <span class="ent-status-dot {{ strtolower($session->qr_status) == 'active' ? 'active' : 'warning' }}" style="width:6px;height:6px;"></span>
                                    {{ $session->qr_status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3">
                                <div class="ent-empty" style="padding:32px 16px;">
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
        </div>
    </div>

    {{-- At-Risk Students --}}
    <div class="ent-section" style="min-width:0;">
        <div class="ent-section-header">
            <div class="ent-section-title">
                <div class="ent-section-title-icon" style="background:rgba(248,113,113,0.12);color:var(--ent-danger);">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                At-Risk Students
            </div>
            <a href="{{ route('admin.students') }}" class="ent-btn ent-btn-xs ent-btn-ghost">View All <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="ent-section-body no-pad">
            <div class="ent-scroll-x">
                <table class="ent-table" style="min-width:320px;">
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
                                <span class="ent-badge {{ $student->attendance_rate >= 70 ? 'ent-badge-warning' : 'ent-badge-danger' }}">
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
                                <div class="ent-empty" style="padding:32px 16px;">
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
        </div>
    </div>
</div>

{{-- ─── HOLIDAY CALENDAR ─── --}}
@php
    $hcalStart = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $hcalEnd = $hcalStart->copy()->endOfMonth();
    $hcalPrev = $hcalStart->copy()->subMonth();
    $hcalNext = $hcalStart->copy()->addMonth();
    $hcalStartDow = $hcalStart->dayOfWeek;
    $hcalIsCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $hcalToday = now()->day;
@endphp

<div class="ent-section ent-mb-md ent-fade-up ent-delay-4">
    <div class="ent-section-header">
        <div class="ent-section-title">
            <div class="ent-section-title-icon" style="background:rgba(248,113,113,0.12);color:#f87171;">
                <i class="bi bi-calendar-heart-fill"></i>
            </div>
            Holiday & Events Calendar
        </div>
        <button type="button" class="ent-btn ent-btn-sm ent-btn-primary" onclick="openHcalModal()">
            <i class="bi bi-plus-lg"></i> Add Event
        </button>
    </div>
    <div class="ent-section-body" style="padding:16px 20px;">
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
                                · <i class="bi bi-person"></i> {{ $evt->author }}
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
                            <form action="{{ route('admin.holidays.destroy', $evt->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this holiday?');">
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
</div>

{{-- ─── ADD/EDIT HOLIDAY MODAL ─── --}}
<div class="hcal-modal-overlay" id="hcalModalOverlay">
    <div class="hcal-modal">
        <div class="hcal-modal-header">
            <div class="hcal-modal-title" id="hcalModalTitle">Add Holiday / Event</div>
            <button type="button" class="hcal-modal-close" onclick="closeHcalModal()">✕</button>
        </div>
        <form id="hcalForm" method="POST" action="{{ route('admin.holidays.store') }}">
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

{{-- ─── MOBILE QUICK ACTIONS ─── --}}
<div class="ent-mobile-actions ent-fade-up ent-delay-3">
    <a href="{{ route('admin.attendance.pdf') }}" class="ent-mobile-action-btn" style="background:rgba(207,164,111,0.1); border-color:var(--ent-gold); grid-column: span 2;">
        <i class="bi bi-cloud-arrow-down-fill"></i> Generate Report
    </a>
    <a href="{{ route('admin.students') }}" class="ent-mobile-action-btn">
        <i class="bi bi-people-fill"></i> Students
    </a>
    <a href="{{ route('admin.attendance') }}" class="ent-mobile-action-btn">
        <i class="bi bi-calendar-check-fill"></i> Attendance
    </a>
    <a href="{{ route('admin.subjects') }}" class="ent-mobile-action-btn">
        <i class="bi bi-book-fill"></i> Subjects
    </a>
    <a href="{{ route('admin.calendar') }}" class="ent-mobile-action-btn">
        <i class="bi bi-calendar3-fill"></i> Calendar
    </a>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var options = {
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

    var chart = new ApexCharts(document.querySelector("#weeklyChart"), options);
    chart.render();
});

// ── HOLIDAY CALENDAR MODAL ──
function openHcalModal() {
    document.getElementById('hcalModalTitle').textContent = 'Add Holiday / Event';
    document.getElementById('hcalForm').action = '{{ route("admin.holidays.store") }}';
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
    document.getElementById('hcalForm').action = '/admin/holidays/' + id;
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
