@extends('layouts.app')

@section('portal-title', 'Attendance Reports')
@section('page-title', 'Reports & Analytics')
@section('page-sub', 'Generate, analyze, and export comprehensive school attendance metrics')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Reports & Analytics</h1>
        <p class="saas-text-muted" style="margin:0;">Comprehensive attendance metrics, class summaries, and exportable reports.</p>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="{{ route('admin.reports.csv', request()->all()) }}" class="saas-btn saas-btn-secondary" style="color:#34d399; border-color:rgba(52,211,153,0.3); text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </a>
        <a href="{{ route('admin.reports.pdf', request()->all()) }}" class="saas-btn saas-btn-primary" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
    </div>
</div>

<!-- Report Category Pills -->
@php
    $reportTypes = [
        'daily' => ['label' => 'Daily', 'icon' => 'calendar-day'],
        'weekly' => ['label' => 'Weekly', 'icon' => 'calendar2-week'],
        'monthly' => ['label' => 'Monthly', 'icon' => 'calendar-month'],
        'range' => ['label' => 'Date Range', 'icon' => 'calendar-range'],
        'late' => ['label' => 'Late List', 'icon' => 'clock-history'],
        'absent' => ['label' => 'Absenteeism', 'icon' => 'person-x'],
        'class_summary' => ['label' => 'Class Summary', 'icon' => 'building'],
        'subject_summary' => ['label' => 'Subject Summary', 'icon' => 'book'],
        'percentage' => ['label' => 'Student %', 'icon' => 'pie-chart'],
    ];
@endphp

<div style="display:flex; gap:8px; margin-bottom:20px; overflow-x:auto; padding-bottom:8px;">
    @foreach($reportTypes as $key => $meta)
    <a href="{{ route('admin.reports', array_merge(request()->except(['page']), ['type' => $key])) }}"
       class="saas-btn {{ $type === $key ? 'saas-btn-primary' : 'saas-btn-secondary' }}" 
       style="padding:8px 14px; font-size:0.85rem; white-space:nowrap; text-decoration:none;">
        <i class="bi bi-{{ $meta['icon'] }} me-1"></i> {{ $meta['label'] }}
    </a>
    @endforeach
</div>

<!-- Filter Bar -->
<div class="saas-card" style="margin-bottom:24px;">
    <div class="saas-card-header" style="background:rgba(0,0,0,0.15); padding:16px 20px;">
        <form method="GET" action="{{ route('admin.reports') }}" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; width:100%;">
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
                <label class="saas-label" style="font-size:0.75rem; color:#f3e7cd; margin-bottom:4px; display:block;">Course</label>
                <select name="course" class="saas-input saas-select" style="width:130px;">
                    <option value="">All Courses</option>
                    @foreach($courses ?? [] as $c)
                        <option value="{{ $c->code }}" {{ request('course') == $c->code ? 'selected' : '' }}>{{ $c->code }}</option>
                    @endforeach
                </select>
            </div>

            <div class="saas-form-group" style="margin:0;">
                <label class="saas-label" style="font-size:0.75rem; color:#f3e7cd; margin-bottom:4px; display:block;">Subject</label>
                <select name="subject" class="saas-input saas-select" style="width:140px;">
                    <option value="">All Subjects</option>
                    @foreach($subjects ?? [] as $sub)
                        <option value="{{ $sub->code }}" {{ request('subject') == $sub->code ? 'selected' : '' }}>{{ $sub->code }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="saas-btn saas-btn-primary" style="padding:8px 16px;">
                <i class="bi bi-funnel-fill me-1"></i> Apply Filter
            </button>
            
            @if(request()->hasAny(['course', 'subject', 'start_date', 'end_date', 'date', 'month']))
            <a href="{{ route('admin.reports', ['type' => $type]) }}" class="saas-btn saas-btn-secondary" style="color:#f87171; border-color:rgba(239,68,68,0.3); text-decoration:none;">
                Reset
            </a>
            @endif
        </form>
    </div>

    <!-- Overview Metric Cards -->
    @if(in_array($type, ['daily', 'weekly', 'monthly', 'range', 'late', 'absent']))
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:12px; padding:16px 20px; background:rgba(0,0,0,0.08); border-bottom:1px solid rgba(207,164,111,0.15);">
        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.07); border-radius:10px; padding:12px 14px;">
            <div class="saas-text-muted" style="font-size:0.75rem; font-weight:600; text-transform:uppercase;">Total Punches</div>
            <div style="font-size:1.35rem; font-weight:800; color:#f3e7cd;">{{ number_format($stats['total'] ?? 0) }}</div>
        </div>
        <div style="background:rgba(16,185,129,0.06); border:1px solid rgba(16,185,129,0.2); border-radius:10px; padding:12px 14px;">
            <div class="saas-text-muted" style="font-size:0.75rem; font-weight:600; text-transform:uppercase; color:#34d399;">Present</div>
            <div style="font-size:1.35rem; font-weight:800; color:#34d399;">{{ number_format($stats['present'] ?? 0) }}</div>
        </div>
        <div style="background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.2); border-radius:10px; padding:12px 14px;">
            <div class="saas-text-muted" style="font-size:0.75rem; font-weight:600; text-transform:uppercase; color:#fbbf24;">Late</div>
            <div style="font-size:1.35rem; font-weight:800; color:#fbbf24;">{{ number_format($stats['late'] ?? 0) }}</div>
        </div>
        <div style="background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.2); border-radius:10px; padding:12px 14px;">
            <div class="saas-text-muted" style="font-size:0.75rem; font-weight:600; text-transform:uppercase; color:#f87171;">Absent</div>
            <div style="font-size:1.35rem; font-weight:800; color:#f87171;">{{ number_format($stats['absent'] ?? 0) }}</div>
        </div>
        <div style="background:rgba(59,130,246,0.06); border:1px solid rgba(59,130,246,0.2); border-radius:10px; padding:12px 14px;">
            <div class="saas-text-muted" style="font-size:0.75rem; font-weight:600; text-transform:uppercase; color:#60a5fa;">Excused</div>
            <div style="font-size:1.35rem; font-weight:800; color:#60a5fa;">{{ number_format($stats['excused'] ?? 0) }}</div>
        </div>
        <div style="background:rgba(207,164,111,0.08); border:1px solid rgba(207,164,111,0.25); border-radius:10px; padding:12px 14px;">
            <div class="saas-text-muted" style="font-size:0.75rem; font-weight:600; text-transform:uppercase; color:#cfa46f;">Attendance Rate</div>
            <div style="font-size:1.35rem; font-weight:800; color:#cfa46f;">{{ $stats['rate'] ?? 0 }}%</div>
        </div>
    </div>
    @endif

    <div class="saas-table-container" style="border:none; border-radius:0;">
        @if($type === 'percentage')
        <!-- STUDENT PERCENTAGE RANKING -->
        <table class="saas-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Course & Section</th>
                    <th>Total Scheduled</th>
                    <th>Present / Late</th>
                    <th>Absences</th>
                    <th>Attendance %</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($percentageData as $i => $row)
                <tr>
                    <td class="saas-text-muted">{{ $i + 1 }}</td>
                    <td style="font-weight:700; color:#f3e7cd;">{{ $row['student']->name }}</td>
                    <td><span class="saas-badge saas-badge-default" style="font-family:monospace;">{{ $row['student']->student_number ?? 'N/A' }}</span></td>
                    <td><span class="saas-badge saas-badge-info">{{ $row['student']->course ?? 'N/A' }} {{ $row['student']->section ? '- ' . $row['student']->section : '' }}</span></td>
                    <td><strong style="color:#f3e7cd;">{{ $row['total'] }}</strong></td>
                    <td><span style="color:#34d399; font-weight:600;">{{ $row['present'] }}</span> / <span style="color:#fbbf24; font-weight:600;">{{ $row['late'] }}</span></td>
                    <td><span style="color:#f87171; font-weight:600;">{{ $row['absent'] }}</span> @if($row['excused'] > 0)<small style="color:#60a5fa;">({{ $row['excused'] }} exc)</small>@endif</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-family:monospace; font-weight:800; font-size:0.95rem; color:{{ $row['rate'] >= 80 ? '#34d399' : ($row['rate'] >= 70 ? '#fbbf24' : '#f87171') }};">{{ $row['rate'] }}%</span>
                            <div style="flex:1; height:6px; background:rgba(255,255,255,0.1); border-radius:3px; overflow:hidden; max-width:80px;">
                                <div style="height:100%; width:{{ $row['rate'] }}%; background:{{ $row['rate'] >= 80 ? '#34d399' : ($row['rate'] >= 70 ? '#fbbf24' : '#f87171') }};"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($row['rate'] >= 80)
                            <span class="saas-badge saas-badge-success">Good Standing</span>
                        @elseif($row['rate'] >= 70)
                            <span class="saas-badge saas-badge-warning">At Risk</span>
                        @else
                            <span class="saas-badge" style="background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3);">Critical Warning</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:48px 20px; color:#a38b7d;">
                        <i class="bi bi-people" style="font-size:2rem; display:block; margin-bottom:8px; opacity:0.5;"></i>
                        No student attendance metrics found for the selected criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @elseif($type === 'class_summary')
        <!-- CLASS / SECTION SUMMARY -->
        <table class="saas-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Year Level</th>
                    <th>Section</th>
                    <th>Total Records</th>
                    <th>Present</th>
                    <th>Late</th>
                    <th>Absent</th>
                    <th>Excused</th>
                    <th>Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summaryData as $row)
                <tr>
                    <td style="font-weight:700; color:#60a5fa;">{{ $row->course }}</td>
                    <td>Year {{ $row->year_level }}</td>
                    <td><span class="saas-badge saas-badge-default" style="font-weight:700;">{{ $row->section }}</span></td>
                    <td><strong>{{ number_format($row->total_records) }}</strong></td>
                    <td style="color:#34d399; font-weight:600;">{{ number_format($row->present_count) }}</td>
                    <td style="color:#fbbf24; font-weight:600;">{{ number_format($row->late_count) }}</td>
                    <td style="color:#f87171; font-weight:600;">{{ number_format($row->absent_count) }}</td>
                    <td style="color:#60a5fa; font-weight:600;">{{ number_format($row->excused_count) }}</td>
                    <td>
                        <span style="font-weight:800; color:{{ $row->rate >= 80 ? '#34d399' : ($row->rate >= 70 ? '#fbbf24' : '#f87171') }};">
                            {{ $row->rate }}%
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:48px 20px; color:#a38b7d;">
                        No class records found for the selected period.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @elseif($type === 'subject_summary')
        <!-- SUBJECT SUMMARY -->
        <table class="saas-table">
            <thead>
                <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Total Logs</th>
                    <th>Present</th>
                    <th>Late</th>
                    <th>Absent</th>
                    <th>Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summaryData as $row)
                <tr>
                    <td style="font-weight:800; color:#60a5fa; font-family:monospace;">{{ $row->subject_code }}</td>
                    <td style="font-weight:600; color:#f3e7cd;">{{ $row->subject_name }}</td>
                    <td><strong>{{ number_format($row->total_records) }}</strong></td>
                    <td style="color:#34d399; font-weight:600;">{{ number_format($row->present_count) }}</td>
                    <td style="color:#fbbf24; font-weight:600;">{{ number_format($row->late_count) }}</td>
                    <td style="color:#f87171; font-weight:600;">{{ number_format($row->absent_count) }}</td>
                    <td>
                        <span style="font-weight:800; color:{{ $row->rate >= 80 ? '#34d399' : ($row->rate >= 70 ? '#fbbf24' : '#f87171') }};">
                            {{ $row->rate }}%
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:48px 20px; color:#a38b7d;">
                        No subject summaries available for the selected period.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @else
        <!-- STANDARD RECORDS VIEW (Daily, Weekly, Monthly, Range, Late, Absent) -->
        <table class="saas-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Date & Time</th>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $i => $att)
                <tr>
                    <td class="saas-text-muted">{{ $i + 1 }}</td>
                    <td>
                        <div style="font-weight:700; color:#f3e7cd;">{{ $att->date ? (is_string($att->date) ? $att->date : $att->date->format('M d, Y')) : 'N/A' }}</div>
                        <div style="font-size:0.75rem; color:#cfa46f;"><i class="bi bi-clock me-1"></i>{{ $att->time_in ?? 'No punch time' }}</div>
                    </td>
                    <td>
                        <div style="font-weight:700; color:#f3e7cd;">{{ $att->user->name ?? 'Unknown' }}</div>
                        <div style="font-size:0.75rem; color:#b39b82;">{{ $att->user->course ?? '' }} {{ $att->user->section ? '• Sec ' . $att->user->section : '' }}</div>
                    </td>
                    <td><span class="saas-badge saas-badge-default" style="font-family:monospace;">{{ $att->user->student_number ?? 'N/A' }}</span></td>
                    <td>
                        <span style="font-weight:700; color:#60a5fa;">{{ $att->subject_code }}</span>
                        <div style="font-size:0.75rem; color:#b39b82;">{{ $att->subject->name ?? '' }}</div>
                    </td>
                    <td>
                        @if($att->excused)
                            <span class="saas-badge" style="background:rgba(59,130,246,0.15); color:#60a5fa; border:1px solid rgba(59,130,246,0.3);">Excused</span>
                        @elseif($att->status === 'Present')
                            <span class="saas-badge saas-badge-success">Present</span>
                        @elseif($att->status === 'Late')
                            <span class="saas-badge saas-badge-warning">Late</span>
                        @else
                            <span class="saas-badge" style="background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3);">Absent</span>
                        @endif
                    </td>
                    <td>
                        <span class="saas-badge saas-badge-default" style="font-size:0.75rem;">
                            {{ strtoupper($att->method ?? 'QR') }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:48px 20px; color:#a38b7d;">
                        <i class="bi bi-journal-x" style="font-size:2.5rem; display:block; margin-bottom:12px; opacity:0.5;"></i>
                        No attendance records found matching this report criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
