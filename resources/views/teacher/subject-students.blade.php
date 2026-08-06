@extends('layouts.app')
@section('page-title', 'Students - ' . $subject->name)

@section('content')
<style>
@media (max-width: 768px) {
    .tch-table thead { display: none; }
    .tch-table tbody tr { display: block; border: 1px solid rgba(207,164,111,0.18); border-radius: 12px; margin-bottom: 12px; background: rgba(32,20,15,0.95); box-shadow: 0 1px 8px rgba(0,0,0,.2); }
    .tch-table tbody td { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; border-bottom: 1px solid rgba(207,164,111,0.12); font-size: .82rem; }
    .tch-table tbody td:last-child { border-bottom: none; }
    .tch-table tbody td::before { content: attr(data-label); font-size: .7rem; font-weight: 700; color: #b39b82; text-transform: uppercase; letter-spacing: .5px; margin-right: 10px; flex-shrink: 0; }
    .tch-table tbody tr:last-child td { border-bottom: none; }
}
</style>

<!-- Subject Header -->
<div class="tch-card" style="margin-bottom: 20px;">
    <div style="padding: 20px 22px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: #f3e7cd; margin: 0 0 4px 0;">{{ $subject->name }}</h1>
                <p style="color: #b39b82; margin: 0; font-size: 0.875rem;">
                    {{ $subject->code }} â€¢ Year {{ $subject->year_level }} â€¢ Semester {{ $subject->semester }}
                    @if($subject->room) â€¢ Room {{ $subject->room }} @endif
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="{{ route('teacher.qr', $subject->code) }}" class="tch-btn tch-btn-primary" style="background:#800000;border-color:#8a3b2e;color:white;">
                    <i class="bi bi-qr-code me-1"></i> Start QR Attendance
                </a>
                <a href="{{ route('teacher.subjects') }}" class="tch-btn tch-btn-ghost" style="color:#e7d4b8;border-color:rgba(207,164,111,0.2);">
                    <i class="bi bi-arrow-left me-1"></i> Back to Subjects
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Students List -->
<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background:rgba(128,0,0,0.14);color:#800000;"><i class="bi bi-people-fill"></i></div>
            Enrolled Students
        </div>
        <span style="font-size:.78rem;color:#b39b82;">{{ $students->count() }} {{ Str::plural('student', $students->count()) }}</span>
    </div>

    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <table class="tch-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Total Records</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $i => $student)
                    @php
                        $totalRecords = $student->attendances->where('subject_code', $subject->code)->count();
                        $presentRecords = $student->attendances->where('subject_code', $subject->code)->whereIn('status', ['Present', 'Late'])->count();
                        $absentRecords = $student->attendances->where('subject_code', $subject->code)->where('status', 'Absent')->count();
                        $attendanceRate = $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100) : 0;
                    @endphp
                <tr>
                    <td data-label="#" style="color:#b39b82;font-size:.78rem;">{{ $i + 1 }}</td>
                    <td data-label="Student">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, rgba(128,0,0,0.9), rgba(163,93,80,0.95)); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.875rem; font-weight: 600;">
                                {{ substr($student->name, 0, 2) }}
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #f3e7cd; font-size: 0.875rem;">{{ $student->name }}</div>
                                <div style="font-size: 0.75rem; color: #b39b82;">{{ $student->student_number }}</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="Course">
                        <span class="badge-course">{{ $student->course }}</span>
                    </td>
                    <td data-label="Year">
                        <span class="badge-year">Year {{ $student->year_level }}</span>
                    </td>
                    <td data-label="Total Records" style="font-weight: 600; color: #e7d4b8;">{{ $totalRecords }}</td>
                    <td data-label="Present" style="color: #cfa46f; font-weight: 600;">{{ $presentRecords }}</td>
                    <td data-label="Absent" style="color: #8a3b2e; font-weight: 600;">{{ $absentRecords }}</td>
                    <td data-label="Attendance Rate">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="flex: 1; height: 6px; background: rgba(207,164,111,0.12); border-radius: 99px; overflow: hidden; min-width: 60px;">
                                <div style="height: 100%; width: {{ $attendanceRate }}%; background: {{ $attendanceRate >= 75 ? '#cfa46f' : '#8a3b2e' }}; border-radius: 99px;"></div>
                            </div>
                            <span style="font-size: 0.8rem; font-weight: 700; color: {{ $attendanceRate >= 75 ? '#cfa46f' : '#8a3b2e' }};">{{ $attendanceRate }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="bi bi-people"></i>
                            <h4>No Students Found</h4>
                            <p>No students are currently enrolled in this subject for Year {{ $subject->year_level }}, Semester {{ $subject->semester }}.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($students->count() > 0)
    @php
        $totalStudents = $students->count();
        $studentsWithGoodAttendance = $students->filter(function($student) use ($subject) {
            $totalRecords = $student->attendances->where('subject_code', $subject->code)->count();
            $presentRecords = $student->attendances->where('subject_code', $subject->code)->whereIn('status', ['Present', 'Late'])->count();
            $rate = $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100) : 0;
            return $rate >= 75;
        })->count();
        $averageAttendance = $students->map(function($student) use ($subject) {
            $totalRecords = $student->attendances->where('subject_code', $subject->code)->count();
            $presentRecords = $student->attendances->where('subject_code', $subject->code)->whereIn('status', ['Present', 'Late'])->count();
            return $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100) : 0;
        })->avg();
    @endphp

    <!-- Summary Stats -->
    <div class="row g-3 mt-4">
        <div class="col-md-3">
            <div class="tch-card">
                <div style="padding: 20px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: 800; color: #cfa46f; margin-bottom: 8px;">{{ $totalStudents }}</div>
                    <div style="font-size: 0.875rem; color: #b39b82; font-weight: 600;">Total Students</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="tch-card">
                <div style="padding: 20px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: 800; color: #cfa46f; margin-bottom: 8px;">{{ $studentsWithGoodAttendance }}</div>
                    <div style="font-size: 0.875rem; color: #b39b82; font-weight: 600;">Good Attendance (â‰¥75%)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="tch-card">
                <div style="padding: 20px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: 800; color: #8a3b2e; margin-bottom: 8px;">{{ $totalStudents - $studentsWithGoodAttendance }}</div>
                    <div style="font-size: 0.875rem; color: #b39b82; font-weight: 600;">Poor Attendance (<75%)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="tch-card">
                <div style="padding: 20px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: 800; color: #cfa46f; margin-bottom: 8px;">{{ round($averageAttendance) }}%</div>
                    <div style="font-size: 0.875rem; color: #b39b82; font-weight: 600;">Average Rate</div>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
