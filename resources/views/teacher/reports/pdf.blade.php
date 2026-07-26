@extends('layouts.pdf')

@section('title')
@if($type === 'daily')
Teacher Daily Report - {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}
@elseif($type === 'monthly')
Teacher Monthly Report - {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}
@else
Teacher Attendance Percentage Report
@endif
@endsection

@section('report-title')
@if($type === 'daily')
Daily Attendance Report — {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}
@elseif($type === 'monthly')
Monthly Attendance Report — {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}
@else
Student Attendance Percentage Report
@endif
@endsection

@section('footer-title', 'Teacher Report')
@section('footer-details', 'This report contains sensitive student information and should be handled according to privacy policies')

@section('content')
    <!-- Teacher Information -->
    <div class="info-section">
        <div class="info-title">Teacher Information</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div>
                <strong style="color: #800000;">Name:</strong> {{ $teacher->name }}
            </div>
            @if($teacher->employee_id)
            <div>
                <strong style="color: #800000;">Employee ID:</strong> {{ $teacher->employee_id }}
            </div>
            @endif
            @if($teacher->department)
            <div>
                <strong style="color: #800000;">Department:</strong> {{ $teacher->department }}
            </div>
            @endif
            <div>
                <strong style="color: #800000;">Email:</strong> {{ $teacher->email }}
            </div>
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
                    <th style="width: 12%;">Course</th>
                    <th style="width: 8%;">Year</th>
                    <th style="width: 8%;">Total</th>
                    <th style="width: 8%;">Present</th>
                    <th style="width: 8%;">Absent</th>
                    <th style="width: 26%;">Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $i => $row)
                <tr>
                    <td class="text-center text-muted">{{ $i + 1 }}</td>
                    <td>
                        <div class="font-semibold text-maroon" style="font-size: 11px;">{{ $row['student']->name }}</div>
                        <div class="text-muted" style="font-size: 9px; font-family: 'Courier New', monospace;">{{ $row['student']->student_number }}</div>
                    </td>
                    <td>
                        <span class="badge badge-course">{{ $row['student']->course }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-year">Year {{ $row['student']->year_level }}</span>
                    </td>
                    <td class="text-center font-semibold">{{ $row['total'] }}</td>
                    <td class="text-center">
                        <span class="badge badge-present">{{ $row['present'] }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-absent">{{ $row['absent'] }}</span>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 60px; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; width: {{ $row['rate'] }}%; background: {{ $row['rate'] >= 75 ? '#16a34a' : '#dc2626' }}; border-radius: 4px;"></div>
                            </div>
                            <span style="font-weight: bold; color: {{ $row['rate'] >= 75 ? '#16a34a' : '#dc2626' }}; font-size: 11px;">
                                {{ $row['rate'] }}%
                            </span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted" style="padding: 40px;">
                        No attendance data available.
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
                    <th style="width: 25%;">Subject</th>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 15%;">Time In</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $i => $record)
                <tr>
                    <td class="text-center text-muted">{{ $i + 1 }}</td>
                    <td>
                        <div class="font-semibold text-maroon" style="font-size: 11px;">{{ $record->user->name ?? '—' }}</div>
                        @if($record->user && $record->user->student_number)
                            <div class="text-muted" style="font-size: 9px; font-family: 'Courier New', monospace;">{{ $record->user->student_number }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="font-semibold" style="font-size: 11px;">{{ $record->subject->name ?? $record->subject_code }}</div>
                        @if($record->subject_code)
                            <div class="text-muted" style="font-size: 9px;">{{ $record->subject_code }}</div>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</td>
                    <td class="text-center">
                        @if($record->status === 'Present')
                            <span class="badge badge-present">Present</span>
                        @elseif($record->status === 'Late')
                            <span class="badge badge-late">Late</span>
                        @else
                            <span class="badge badge-absent">Absent</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($record->time_in)
                            <div style="font-size: 10px; font-family: 'Courier New', monospace;">{{ \Carbon\Carbon::parse($record->time_in)->format('h:i A') }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted" style="padding: 40px;">
                        No attendance records found for this period.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    @endif
@endsection