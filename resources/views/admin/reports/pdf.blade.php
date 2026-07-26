@extends('layouts.pdf')

@section('title')
{{ $title }} - {{ ucfirst($type) }} Report
@endsection

@section('report-title', $title)
@section('footer-title', 'Admin Report')
@section('footer-details', 'This report contains sensitive student information and should be handled according to privacy policies')

@section('content')
    <!-- Report Information -->
    <div class="info-section">
        <div class="info-title">Report Information</div>
        <div class="filter-tags">
            <div class="filter-tag"><strong>Type:</strong> {{ ucfirst($type) }}</div>
            @if($type === 'daily')
                <div class="filter-tag"><strong>Date:</strong> {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}</div>
            @elseif($type === 'monthly')
                <div class="filter-tag"><strong>Month:</strong> {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</div>
            @endif
            <div class="filter-tag"><strong>Total Records:</strong> {{ is_countable($data) ? count($data) : $data->count() }}</div>
            <div class="filter-tag"><strong>Generated:</strong> {{ now()->format('F j, Y h:i A') }}</div>
        </div>
    </div>

    @if($type === 'percentage')
        <!-- Summary Stats for Percentage Report -->
        @php
            $totalStudents = count($data);
            $goodAttendance = collect($data)->where('rate', '>=', 75)->count();
            $poorAttendance = $totalStudents - $goodAttendance;
            $avgRate = $totalStudents > 0 ? round(collect($data)->avg('rate')) : 0;
        @endphp
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">{{ $totalStudents }}</div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $goodAttendance }}</div>
                <div class="stat-label">Good Attendance (≥75%)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $poorAttendance }}</div>
                <div class="stat-label">Poor Attendance (<75%)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $avgRate }}%</div>
                <div class="stat-label">Average Rate</div>
            </div>
        </div>

        <!-- Percentage Table -->
        <table class="pdf-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">Student Information</th>
                    <th style="width: 12%;">Student ID</th>
                    <th style="width: 10%;">Course</th>
                    <th style="width: 8%;">Year</th>
                    <th style="width: 8%;">Total</th>
                    <th style="width: 8%;">Present</th>
                    <th style="width: 8%;">Absent</th>
                    <th style="width: 16%;">Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $i => $row)
                <tr>
                    <td class="text-center text-muted">{{ $i + 1 }}</td>
                    <td>
                        <div class="font-semibold text-maroon" style="font-size: 11px;">{{ $row['student']->name }}</div>
                    </td>
                    <td>
                        <div class="font-bold text-maroon" style="font-family: 'Courier New', monospace; font-size: 10px;">{{ $row['student']->student_number }}</div>
                    </td>
                    <td>
                        <span class="badge badge-course">{{ $row['student']->course }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-year">{{ $row['student']->year_level }}</span>
                    </td>
                    <td class="text-center font-semibold">{{ $row['total'] }}</td>
                    <td class="text-center">
                        <span class="badge badge-present">{{ $row['present'] }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-absent">{{ $row['absent'] }}</span>
                    </td>
                    <td class="text-center">
                        <span style="font-weight: bold; color: {{ $row['rate'] >= 75 ? '#16a34a' : '#dc2626' }}; font-size: 12px;">
                            {{ $row['rate'] }}%
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted" style="padding: 40px;">
                        No report data found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

    @else
        <!-- Daily/Monthly Report -->
        @php
            $totalRecords = is_countable($data) ? count($data) : $data->count();
            $presentCount = 0;
            $lateCount = 0;
            $absentCount = 0;
            
            foreach($data as $record) {
                if($record->status === 'Present') $presentCount++;
                elseif($record->status === 'Late') $lateCount++;
                else $absentCount++;
            }
        @endphp

        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">{{ $totalRecords }}</div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $presentCount }}</div>
                <div class="stat-label">Present</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $lateCount }}</div>
                <div class="stat-label">Late</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $absentCount }}</div>
                <div class="stat-label">Absent</div>
            </div>
        </div>

        <table class="pdf-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">Student Information</th>
                    <th style="width: 15%;">Student ID</th>
                    <th style="width: 20%;">Subject</th>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 11%;">Time In</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $i => $log)
                <tr>
                    <td class="text-center text-muted">{{ $i + 1 }}</td>
                    <td>
                        <div class="font-semibold text-maroon" style="font-size: 11px;">{{ $log->user->name ?? '—' }}</div>
                    </td>
                    <td>
                        @if($log->user && $log->user->student_number)
                            <div class="font-bold text-maroon" style="font-family: 'Courier New', monospace; font-size: 10px;">{{ $log->user->student_number }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="font-semibold" style="font-size: 10px;">{{ $log->subject->name ?? $log->subject_code }}</div>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($log->date)->format('M d, Y') }}</td>
                    <td class="text-center">
                        @if($log->status === 'Present')
                            <span class="badge badge-present">Present</span>
                        @elseif($log->status === 'Late')
                            <span class="badge badge-late">Late</span>
                        @else
                            <span class="badge badge-absent">Absent</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($log->time_in)
                            <div style="font-size: 10px; font-family: 'Courier New', monospace;">{{ \Carbon\Carbon::parse($log->time_in)->format('h:i A') }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding: 40px;">
                        No records found for this report.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    @endif
@endsection
