@extends('layouts.app')
@section('portal-title', 'Teacher Portal')
@section('page-title', 'Attendance Reports')
@section('page-sub', 'Analyze attendance trends and performance across your assigned subjects')

@section('content')
<style>
.tch-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
}
.tch-table thead th {
    padding: 14px 18px;
    text-align: left;
    font-size: .85rem;
    font-weight: 700;
    color: #f8e7d3;
    text-transform: uppercase;
    letter-spacing: .04em;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.tch-table tbody tr {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    transition: transform 0.15s ease;
}
.tch-table tbody tr:hover {
    background: rgba(255,255,255,0.07);
}
.tch-table tbody td {
    padding: 14px 18px;
    vertical-align: middle;
    color: #f3e7cd;
    font-size: .85rem;
}
.badge-present {
    background: rgba(16,185,129,0.18);
    color: #34d399;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
}
.badge-late {
    background: rgba(245,158,11,0.18);
    color: #fbbf24;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
}
.badge-absent {
    background: rgba(239,68,68,0.18);
    color: #f87171;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
}
.badge-excused {
    background: rgba(59,130,246,0.18);
    color: #60a5fa;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
}
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Class Attendance Reports</h1>
        <p class="saas-text-muted" style="margin:0;">View detailed student clock-ins, absence lists, and subject attendance rankings.</p>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="{{ route('teacher.reports.csv', request()->all()) }}" class="saas-btn saas-btn-secondary" style="color:#34d399; border-color:rgba(52,211,153,0.3); text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </a>
        <a href="{{ route('teacher.reports.pdf', request()->all()) }}" class="saas-btn saas-btn-primary" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
    </div>
</div>

<!-- Report Type Navigation Tabs -->
@php
    $teacherReportTabs = [
        'daily' => ['label' => 'Daily', 'icon' => 'calendar-day'],
        'weekly' => ['label' => 'Weekly', 'icon' => 'calendar2-week'],
        'monthly' => ['label' => 'Monthly', 'icon' => 'calendar-month'],
        'range' => ['label' => 'Date Range', 'icon' => 'calendar-range'],
        'late' => ['label' => 'Late Students', 'icon' => 'clock-history'],
        'absent' => ['label' => 'Absent Students', 'icon' => 'person-x'],
        'percentage' => ['label' => 'Student Ranking %', 'icon' => 'pie-chart'],
    ];
@endphp

<div style="display:flex; gap:8px; margin-bottom:20px; overflow-x:auto; padding-bottom:6px;">
    @foreach($teacherReportTabs as $key => $meta)
    <a href="{{ route('teacher.reports', array_merge(request()->except(['page']), ['type' => $key])) }}"
       class="saas-btn {{ $type === $key ? 'saas-btn-primary' : 'saas-btn-secondary' }}" 
       style="padding:8px 14px; font-size:0.85rem; white-space:nowrap; text-decoration:none;">
        <i class="bi bi-{{ $meta['icon'] }} me-1"></i> {{ $meta['label'] }}
    </a>
    @endforeach
</div>

<!-- Filter Bar -->
<div class="saas-card" style="margin-bottom:20px;">
    <div style="padding:16px 20px; background:rgba(0,0,0,0.15);">
        <form method="GET" action="{{ route('teacher.reports') }}" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
            <input type="hidden" name="type" value="{{ $type }}">

            @if($type === 'daily' || $type === 'weekly')
            <div class="saas-form-group" style="margin:0;">
                <label class="saas-label" style="font-size:0.75rem; color:#f3e7cd; margin-bottom:4px; display:block;">Select Date</label>
                <input type="date" name="date" class="saas-input" value="{{ $date }}" style="width:160px;">
            </div>
            @elseif($type === 'monthly')
            <div class="saas-form-group" style="margin:0;">
                <label class="saas-label" style="font-size:0.75rem; color:#f3e7cd; margin-bottom:4px; display:block;">Select Month</label>
                <input type="month" name="month" class="saas-input" value="{{ $month }}" style="width:160px;">
            </div>
            @else
            <div class="saas-form-group" style="margin:0;">
                <label class="saas-label" style="font-size:0.75rem; color:#f3e7cd; margin-bottom:4px; display:block;">Start Date</label>
                <input type="date" name="start_date" class="saas-input" value="{{ $start_date }}" style="width:150px;">
            </div>
            <div class="saas-form-group" style="margin:0;">
                <label class="saas-label" style="font-size:0.75rem; color:#f3e7cd; margin-bottom:4px; display:block;">End Date</label>
                <input type="date" name="end_date" class="saas-input" value="{{ $end_date }}" style="width:150px;">
            </div>
            @endif

            <div class="saas-form-group" style="margin:0;">
                <label class="saas-label" style="font-size:0.75rem; color:#f3e7cd; margin-bottom:4px; display:block;">Subject</label>
                <select name="subject" class="saas-input saas-select" style="width:160px;">
                    <option value="">All My Subjects</option>
                    @foreach($teacherSubjects as $ts)
                        <option value="{{ $ts->code }}" {{ request('subject') == $ts->code ? 'selected' : '' }}>{{ $ts->code }} - {{ $ts->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="saas-btn saas-btn-primary" style="padding:8px 16px;">
                <i class="bi bi-funnel-fill me-1"></i> Filter
            </button>

            @if(request()->hasAny(['subject', 'date', 'month', 'start_date', 'end_date']))
            <a href="{{ route('teacher.reports', ['type' => $type]) }}" class="saas-btn saas-btn-secondary" style="color:#f87171; border-color:rgba(239,68,68,0.3); text-decoration:none;">
                Reset
            </a>
            @endif
        </form>
    </div>

    <!-- Summary Metrics -->
    @if(in_array($type, ['daily', 'weekly', 'monthly', 'range', 'late', 'absent']))
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:12px; padding:14px 20px; background:rgba(0,0,0,0.06); border-bottom:1px solid rgba(207,164,111,0.15);">
        <div>
            <div class="saas-text-muted" style="font-size:0.75rem; text-transform:uppercase;">Total Logs</div>
            <div style="font-size:1.3rem; font-weight:800; color:#f3e7cd;">{{ number_format($stats['total'] ?? 0) }}</div>
        </div>
        <div>
            <div class="saas-text-muted" style="font-size:0.75rem; text-transform:uppercase; color:#34d399;">Present</div>
            <div style="font-size:1.3rem; font-weight:800; color:#34d399;">{{ number_format($stats['present'] ?? 0) }}</div>
        </div>
        <div>
            <div class="saas-text-muted" style="font-size:0.75rem; text-transform:uppercase; color:#fbbf24;">Late</div>
            <div style="font-size:1.3rem; font-weight:800; color:#fbbf24;">{{ number_format($stats['late'] ?? 0) }}</div>
        </div>
        <div>
            <div class="saas-text-muted" style="font-size:0.75rem; text-transform:uppercase; color:#f87171;">Absent</div>
            <div style="font-size:1.3rem; font-weight:800; color:#f87171;">{{ number_format($stats['absent'] ?? 0) }}</div>
        </div>
        <div>
            <div class="saas-text-muted" style="font-size:0.75rem; text-transform:uppercase; color:#60a5fa;">Excused</div>
            <div style="font-size:1.3rem; font-weight:800; color:#60a5fa;">{{ number_format($stats['excused'] ?? 0) }}</div>
        </div>
        <div>
            <div class="saas-text-muted" style="font-size:0.75rem; text-transform:uppercase; color:#cfa46f;">Attendance Rate</div>
            <div style="font-size:1.3rem; font-weight:800; color:#cfa46f;">{{ $stats['rate'] ?? 0 }}%</div>
        </div>
    </div>
    @endif

    <div style="padding:16px 20px; overflow-x:auto;">
        @if($type === 'percentage')
        <!-- STUDENT PERCENTAGE RANKINGS -->
        <table class="tch-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Course & Section</th>
                    <th>Total Classes</th>
                    <th>Present</th>
                    <th>Late</th>
                    <th>Absent</th>
                    <th>Attendance %</th>
                </tr>
            </thead>
            <tbody>
                @forelse($percentageData as $i => $row)
                <tr>
                    <td style="color:#b39b82;">{{ $i + 1 }}</td>
                    <td style="font-weight:700; color:#f3e7cd;">{{ $row['student']->name }}</td>
                    <td><span class="saas-badge saas-badge-default" style="font-family:monospace;">{{ $row['student']->student_number ?? 'N/A' }}</span></td>
                    <td><span class="saas-badge saas-badge-info">{{ $row['student']->course ?? 'N/A' }} {{ $row['student']->section ? '- ' . $row['student']->section : '' }}</span></td>
                    <td><strong>{{ $row['total'] }}</strong></td>
                    <td style="color:#34d399; font-weight:600;">{{ $row['present'] }}</td>
                    <td style="color:#fbbf24; font-weight:600;">{{ $row['late'] }}</td>
                    <td style="color:#f87171; font-weight:600;">{{ $row['absent'] }} @if($row['excused'] > 0)<small style="color:#60a5fa;">({{ $row['excused'] }} exc)</small>@endif</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-family:monospace; font-weight:800; color:{{ $row['rate'] >= 80 ? '#34d399' : ($row['rate'] >= 70 ? '#fbbf24' : '#f87171') }};">{{ $row['rate'] }}%</span>
                            <div style="flex:1; height:6px; background:rgba(255,255,255,0.1); border-radius:3px; overflow:hidden; max-width:70px;">
                                <div style="height:100%; width:{{ $row['rate'] }}%; background:{{ $row['rate'] >= 80 ? '#34d399' : ($row['rate'] >= 70 ? '#fbbf24' : '#f87171') }};"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:36px; color:#a38b7d;">
                        No student attendance records found for your subjects.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @else
        <!-- STANDARD RECORDS (Daily, Weekly, Monthly, Range, Late, Absent) -->
        <table class="tch-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Subject</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $i => $att)
                <tr>
                    <td style="color:#b39b82;">{{ $i + 1 }}</td>
                    <td style="font-weight:700;">{{ $att->date ? (is_string($att->date) ? \Carbon\Carbon::parse($att->date)->format('M d, Y') : $att->date->format('M d, Y')) : 'N/A' }}</td>
                    <td style="color:#cfa46f; font-family:monospace;">{{ $att->time_in ?? '—' }}</td>
                    <td style="font-weight:700; color:#f3e7cd;">{{ $att->user->name ?? 'Unknown' }}</td>
                    <td><span class="saas-badge saas-badge-default" style="font-family:monospace;">{{ $att->user->student_number ?? 'N/A' }}</span></td>
                    <td><span style="font-weight:700; color:#60a5fa;">{{ $att->subject_code }}</span></td>
                    <td>
                        @if($att->excused)
                            <span class="badge-excused">Excused</span>
                        @elseif($att->status === 'Present')
                            <span class="badge-present">Present</span>
                        @elseif($att->status === 'Late')
                            <span class="badge-late">Late</span>
                        @else
                            <span class="badge-absent">Absent</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:36px; color:#a38b7d;">
                        <i class="bi bi-file-earmark-bar-graph" style="font-size:2rem; display:block; margin-bottom:8px; opacity:0.5;"></i>
                        No attendance records found for this report period.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
