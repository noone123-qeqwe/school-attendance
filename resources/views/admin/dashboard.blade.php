@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')

{{-- â”€â”€â”€ MOBILE DASHBOARD HEADER â”€â”€â”€ --}}
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

{{-- â”€â”€â”€ DESKTOP HEADER â”€â”€â”€ --}}
<div class="ent-dash-header ent-fade-up ent-desktop-only">
    <div>
        <h1 class="ent-dash-title">Command Center</h1>
        <p class="ent-dash-subtitle">Overview of academic and attendance operations &mdash; {{ now()->format('l, F j, Y') }}</p>
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

{{-- ─── LIVE TODAY REGION ─── --}}
<div class="ent-fade-up" style="margin-bottom: 24px;">
    <h2 style="font-size: 1.2rem; font-weight: 700; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 10px;">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--ent-success); display: inline-block; box-shadow: 0 0 8px var(--ent-success);"></span>
        Live Today
    </h2>
</div>

{{-- ─── SYSTEM ALERTS ─── --}}
@if($systemAlerts->count() > 0)
<div class="ent-fade-up ent-delay-1 ent-mb-md" aria-live="polite">
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

{{-- ─── SKELETON KPIs (shown briefly while page paints) ─── --}}
<div class="ent-grid ent-grid-4 ent-mb-md skel-kpi-placeholder" id="skelKpis">
    <x-skeleton type="kpi" :count="4" />
</div>

{{-- ─── PRIMARY KPIs: Entity Counts ─── --}}
<div class="ent-grid ent-grid-4 ent-mb-md ent-fade-up ent-delay-1" id="realKpis" style="display:none;" aria-live="polite">
    <x-card type="kpi" icon="bi bi-people-fill" label="Total Students" value="{{ number_format($totalStudents) }}" />
    <x-card type="kpi" icon="bi bi-person-workspace" label="Instructors" value="{{ number_format($totalTeachers) }}" />
    <x-card type="kpi" icon="bi bi-building" label="Departments" value="{{ number_format($totalDepartments) }}" />
    <x-card type="kpi" icon="bi bi-diagram-3" label="Sections" value="{{ number_format($totalSections) }}" />
</div>

{{-- ─── ATTENDANCE KPIs ─── --}}
<div class="ent-grid ent-grid-4 ent-mb-md ent-fade-up ent-delay-2" aria-live="polite">
    @php
        $presentDiff = $totalPresent - $yesterdayPresent;
        $lateDiff = $totalLate - $yesterdayLate;
        $absentDiff = $totalAbsent - $yesterdayAbsent;
        $rateDiff = $attendanceRate - $yesterdayRate;
    @endphp
    <x-card type="kpi" accent="success" icon="bi bi-check-circle-fill" label="Present Today" value="{{ number_format($totalPresent) }}" trend="{{ abs($presentDiff) }} vs yesterday" trendDir="{{ $presentDiff >= 0 ? 'up' : 'down' }}" />
    <x-card type="kpi" accent="warning" icon="bi bi-clock-fill" label="Late Today" value="{{ number_format($totalLate) }}" trend="{{ abs($lateDiff) }} vs yesterday" trendDir="{{ $lateDiff <= 0 ? 'up' : 'down' }}" />
    <x-card type="kpi" accent="danger" icon="bi bi-x-circle-fill" label="Absent Today" value="{{ number_format($totalAbsent) }}" trend="{{ abs($absentDiff) }} vs yesterday" trendDir="{{ $absentDiff <= 0 ? 'up' : 'down' }}" />
    
    <x-card type="kpi" accent="{{ $attendanceRate >= 80 ? 'success' : 'danger' }}" icon="bi bi-speedometer2" label="Overall Rate" value="{{ $attendanceRate }}%" trend="{{ abs($rateDiff) }}% vs yesterday" trendDir="{{ $rateDiff >= 0 ? 'up' : 'down' }}">
        <div class="ent-progress" style="margin-top:8px;">
            <div class="ent-progress-fill {{ $attendanceRate >= 80 ? 'success' : 'danger' }}" style="width:{{ $attendanceRate }}%"></div>
        </div>
    </x-card>
</div>

{{-- ─── LIVE SESSIONS (Full Width in Live Today) ─── --}}
<div class="ent-mb-md ent-fade-up ent-delay-3">
    <x-card type="section" style="min-width:0;" aria-live="polite">
        <x-slot:title>
            <div class="ent-section-title-icon" style="background:rgba(74,222,128,0.12);color:var(--ent-success);">
                <i class="bi bi-broadcast"></i>
            </div>
            Live QR Sessions
            @if($activeSessionCount > 0)
                <span class="ent-badge ent-badge-success">{{ $activeSessionCount }} active</span>
            @endif
        </x-slot:title>

        <div class="ent-scroll-x" style="margin: -20px;">
            <table class="ent-table" style="min-width:400px; margin-bottom: 0;">
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

{{-- ─── PAST RECORDS REGION ─── --}}
<div class="ent-fade-up" style="margin-top: 40px; margin-bottom: 24px;">
    <h2 style="font-size: 1.2rem; font-weight: 700; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; align-items: center; gap: 10px;">
        <i class="bi bi-clock-history"></i>
        Past Records & Analytics
    </h2>
</div>

{{-- ─── WEEKLY CHART & AT-RISK (Two Column) ─── --}}
<div class="ent-grid ent-grid-7-5 ent-mb-md ent-fade-up ent-delay-3">

    {{-- Weekly Chart --}}
    <x-card type="section" style="min-width:0;" icon="bi bi-bar-chart-line-fill" title="Weekly Attendance Trend">
        <x-slot:headerActions>
            <div style="display:flex;gap:6px;">
                <span class="ent-badge ent-badge-success"><i class="bi bi-circle-fill" style="font-size:0.45rem;"></i> Present</span>
                <span class="ent-badge ent-badge-warning"><i class="bi bi-circle-fill" style="font-size:0.45rem;"></i> Late</span>
                <span class="ent-badge ent-badge-danger"><i class="bi bi-circle-fill" style="font-size:0.45rem;"></i> Absent</span>
            </div>
        </x-slot:headerActions>
        
        <div id="weeklyChart" class="ent-chart-container" style="min-height:260px;"></div>
    </x-card>

    {{-- At-Risk Students --}}
    <x-card type="section" style="min-width:0;">
        <x-slot:title>
            <div class="ent-section-title-icon" style="background:rgba(248,113,113,0.12);color:var(--ent-danger);">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            At-Risk Students
        </x-slot:title>
        <x-slot:headerActions>
            <a href="{{ route('admin.students') }}" class="ent-btn ent-btn-xs ent-btn-ghost">View All <i class="bi bi-arrow-right"></i></a>
        </x-slot:headerActions>
        
        <div class="ent-scroll-x" style="margin: -20px;">
            <table class="ent-table" style="min-width:320px; margin-bottom: 0;">
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

{{-- â”€â”€â”€ HOLIDAY CALENDAR â”€â”€â”€ --}}
@php
    $hcalStart = \Carbon\Carbon::create($calYear, $calMonth, 1);
    $hcalEnd = $hcalStart->copy()->endOfMonth();
    $hcalPrev = $hcalStart->copy()->subMonth();
    $hcalNext = $hcalStart->copy()->addMonth();
    $hcalStartDow = $hcalStart->dayOfWeek;
    $hcalIsCurrentMonth = (now()->year == $calYear && now()->month == $calMonth);
    $hcalToday = now()->day;
@endphp

<x-card type="section" class="ent-mb-md ent-fade-up ent-delay-4" title="Holiday & Events Calendar">
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
                                Â· <i class="bi bi-person"></i> {{ $evt->author }}
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

{{-- â”€â”€â”€ ADD/EDIT HOLIDAY MODAL â”€â”€â”€ --}}
<div class="hcal-modal-overlay" id="hcalModalOverlay">
    <div class="hcal-modal">
        <div class="hcal-modal-header">
            <div class="hcal-modal-title" id="hcalModalTitle">Add Holiday / Event</div>
            <button type="button" class="hcal-modal-close" onclick="closeHcalModal()">âœ•</button>
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

{{-- â”€â”€â”€ MOBILE QUICK ACTIONS â”€â”€â”€ --}}
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
    // â”€â”€ Skeleton â†’ Content Reveal â”€â”€
    var skelKpis = document.getElementById('skelKpis');
    var realKpis = document.getElementById('realKpis');
    if (skelKpis && realKpis) {
        skelKpis.style.display = 'none';
        realKpis.style.display = '';
    }


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

// â”€â”€ HOLIDAY CALENDAR MODAL â”€â”€
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
