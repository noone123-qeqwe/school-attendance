@extends('layouts.pdf')

@section('title', 'Teacher Attendance Report')
@section('report-title', 'Teacher Attendance Report')
@section('footer-title', 'Teacher Attendance Report')
@section('footer-details', 'This report contains ' . $attendanceRecords->count() . ' attendance record' . ($attendanceRecords->count() !== 1 ? 's' : '') . ' for ' . $teacher->name)

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
                <strong style="color: #800000;">Total Subjects:</strong> {{ $teacherSubjects->count() }}
            </div>
        </div>
    </div>

    <!-- Applied Filters -->
    @if(!empty(array_filter($filters)))
    <div class="info-section">
        <div class="info-title">Applied Filters</div>
        <div class="filter-tags">
            @if(!empty($filters['date']))
                <div class="filter-tag"><strong>Date:</strong> {{ \Carbon\Carbon::parse($filters['date'])->format('M d, Y') }}</div>
            @endif
            @if(!empty($filters['student_name']))
                <div class="filter-tag"><strong>Student Name:</strong> {{ $filters['student_name'] }}</div>
            @endif
            @if(!empty($filters['status']))
                <div class="filter-tag"><strong>Status:</strong> {{ $filters['status'] }}</div>
            @endif
            @if(!empty($filters['subject']))
                <div class="filter-tag"><strong>Subject:</strong> {{ $filters['subject'] }}</div>
            @endif
        </div>
    </div>
    @endif

    @if($attendanceRecords->count() > 0)
    <!-- Statistics -->
    @php
        $totalRecords = $attendanceRecords->count();
        $presentCount = $attendanceRecords->where('status', 'Present')->count();
        $lateCount = $attendanceRecords->where('status', 'Late')->count();
        $absentCount = $attendanceRecords->where('status', 'Absent')->count();
        $excusedCount = $attendanceRecords->where('excused', true)->count();
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

    <!-- Attendance Records Table -->
    <table class="pdf-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">Student Information</th>
                <th style="width: 25%;">Subject</th>
                <th style="width: 12%;">Date</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 10%;">Time In</th>
                <th style="width: 13%;">Excused</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendanceRecords as $i => $record)
            <tr>
                <td class="text-center text-muted">{{ $i + 1 }}</td>
                <td>
                    <div class="font-semibold text-maroon" style="font-size: 11px;">{{ $record->user->name ?? '—' }}</div>
                    @if($record->user && $record->user->student_number)
                        <div class="text-muted" style="font-size: 9px; font-family: 'Courier New', monospace;">{{ $record->user->student_number }}</div>
                    @endif
                </td>
                <td>
                    <div class="font-semibold" style="font-size: 10px;">{{ $record->subject->name ?? $record->subject_code }}</div>
                </td>
                <td>
                    <div style="font-size: 10px;">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</div>
                </td>
                <td class="text-center">
                    @if($record->excused)
                        <span class="badge" style="background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%); color: #0c5460; border: 1px solid #7dd3fc;">Excused</span>
                    @elseif($record->status === 'Present')
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
                <td class="text-center">
                    @if($record->excused)
                        <span class="badge" style="background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%); color: #0c5460; border: 1px solid #7dd3fc;">✓ Excused</span>
                        @if($record->excuse_note)
                            <div class="text-muted" style="font-size: 8px; margin-top: 2px;">{{ Str::limit($record->excuse_note, 30) }}</div>
                        @endif
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Subject Summary -->
    @if($teacherSubjects->count() > 1)
    @php
        $subjectStats = $attendanceRecords->groupBy('subject.name');
    @endphp
    <div class="info-section">
        <div class="info-title">Subject Distribution</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 15px;">
            @foreach($subjectStats as $subjectName => $subjectRecords)
                <div class="stat-card" style="padding: 15px;">
                    <div class="stat-number" style="font-size: 18px;">{{ $subjectRecords->count() }}</div>
                    <div class="stat-label">{{ Str::limit($subjectName, 20) }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @else
    <!-- No Data -->
    <div class="no-data">
        <h3>No Attendance Records Found</h3>
        <p>No attendance records match the selected filters for your subjects. Please adjust your filters and try again.</p>
    </div>
    @endif
@endsection