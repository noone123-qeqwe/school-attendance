@extends('layouts.pdf')

@section('title', 'Attendance Logs Report')
@section('report-title', 'Student Attendance Logs')
@section('footer-title', 'Attendance Logs Report')

@section('footer-details')
This document contains {{ $logs->count() }} attendance record@if($logs->count() !== 1)s@endif
@endsection

@section('content')
    <!-- Filters (if any) -->
    @if(!empty(array_filter($filters)))
    <div class="info-section">
        <div class="info-title">Applied Filters</div>
        <div class="filter-tags">
            @if(!empty($filters['date']))
                <div class="filter-tag"><strong>Date:</strong> {{ \Carbon\Carbon::parse($filters['date'])->format('M d, Y') }}</div>
            @endif
            @if(!empty($filters['student_name']))
                <div class="filter-tag"><strong>Student:</strong> {{ $filters['student_name'] }}</div>
            @endif
            @if(!empty($filters['status']))
                <div class="filter-tag"><strong>Status:</strong> {{ $filters['status'] }}</div>
            @endif
            @if(!empty($filters['year_level']))
                <div class="filter-tag"><strong>Year:</strong> {{ $filters['year_level'] }}</div>
            @endif
            @if(!empty($filters['semester']))
                <div class="filter-tag"><strong>Semester:</strong> {{ $filters['semester'] == '1' ? '1st' : '2nd' }}</div>
            @endif
            @if(!empty($filters['subject']))
                <div class="filter-tag"><strong>Subject:</strong> {{ $filters['subject'] }}</div>
            @endif
        </div>
    </div>
    @endif

    @if($logs->count() > 0)
    <!-- Statistics -->
    @php
        $totalRecords = $logs->count();
        $presentCount = $logs->where('status', 'Present')->count();
        $lateCount = $logs->where('status', 'Late')->count();
        $absentCount = $logs->where('status', 'Absent')->count();
        $excusedCount = $logs->where('excused', true)->count();
    @endphp
    
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
        <div class="stat-card">
            <div class="stat-number">{{ $excusedCount }}</div>
            <div class="stat-label">Excused</div>
        </div>
    </div>

    <!-- Attendance Table -->
    <table class="pdf-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">Student Information</th>
                <th style="width: 20%;">Subject</th>
                <th style="width: 12%;">Date</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 10%;">Time In</th>
                <th style="width: 18%;">Excused</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $i => $log)
            <tr>
                <td class="text-center text-muted">{{ $i + 1 }}</td>
                <td>
                    <div class="font-semibold text-maroon" style="font-size: 11px;">{{ $log->user->name ?? '-' }}</div>
                    <div class="text-muted" style="font-size: 9px; font-family: 'Courier New', monospace;">{{ $log->user->student_number ?? '' }}</div>
                </td>
                <td>
                    <div class="font-semibold" style="font-size: 10px;">{{ $log->subject->name ?? $log->subject_code }}</div>
                </td>
                <td>
                    <div style="font-size: 10px;">{{ \Carbon\Carbon::parse($log->date)->format('M d, Y') }}</div>
                    <div class="text-muted" style="font-size: 9px;">{{ \Carbon\Carbon::parse($log->date)->format('l') }}</div>
                </td>
                <td>
                    @if($log->excused)
                        <span class="badge badge-present" style="font-size: 8px;">Excused</span>
                    @elseif($log->status === 'Present')
                        <span class="badge badge-present">Present</span>
                    @elseif($log->status === 'Late')
                        <span class="badge badge-late">Late</span>
                    @else
                        <span class="badge badge-absent">Absent</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($log->time_in)
                        <div class="font-semibold" style="font-size: 10px;">{{ \Carbon\Carbon::parse($log->time_in)->format('h:i A') }}</div>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @if($log->excused)
                        <span class="badge badge-present" style="font-size: 8px;">Excused</span>
                        @if($log->excuse_note)
                            <div class="text-muted" style="font-size: 8px; margin-top: 2px;">{{ Str::limit($log->excuse_note, 30) }}</div>
                        @endif
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Status Distribution -->
    <div class="info-section">
        <div class="info-title">Attendance Summary</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px;">
            <div class="stat-card" style="padding: 15px;">
                <div class="stat-number" style="color: #16a34a;">{{ $presentCount }}</div>
                <div class="stat-label">Present Records</div>
                <div style="font-size: 8px; color: #6b7280; margin-top: 4px;">
                    {{ $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100) : 0 }}% of total
                </div>
            </div>
            <div class="stat-card" style="padding: 15px;">
                <div class="stat-number" style="color: #d97706;">{{ $lateCount }}</div>
                <div class="stat-label">Late Records</div>
                <div style="font-size: 8px; color: #6b7280; margin-top: 4px;">
                    {{ $totalRecords > 0 ? round(($lateCount / $totalRecords) * 100) : 0 }}% of total
                </div>
            </div>
            <div class="stat-card" style="padding: 15px;">
                <div class="stat-number" style="color: #dc2626;">{{ $absentCount }}</div>
                <div class="stat-label">Absent Records</div>
                <div style="font-size: 8px; color: #6b7280; margin-top: 4px;">
                    {{ $totalRecords > 0 ? round(($absentCount / $totalRecords) * 100) : 0 }}% of total
                </div>
            </div>
            @if($excusedCount > 0)
            <div class="stat-card" style="padding: 15px;">
                <div class="stat-number" style="color: #059669;">{{ $excusedCount }}</div>
                <div class="stat-label">Excused Records</div>
                <div style="font-size: 8px; color: #6b7280; margin-top: 4px;">
                    {{ $totalRecords > 0 ? round(($excusedCount / $totalRecords) * 100) : 0 }}% of total
                </div>
            </div>
            @endif
        </div>
    </div>

    @else
    <!-- No Data -->
    <div class="no-data">
        <h3>No Attendance Records Found</h3>
        <p>No attendance records match the selected criteria. Please adjust your filters and try again.</p>
    </div>
    @endif
@endsection
