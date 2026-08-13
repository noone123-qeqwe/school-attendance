@extends('layouts.pdf')

@section('title', 'Teacher Performance Report')
@section('report-title', 'Teacher Performance Report')
@section('footer-title', 'Teacher Performance Report')

@section('footer-details', 'Generated ' . now()->format('M d, Y'))

@section('content')
    <!-- Statistics -->
    @php
        $totalTeachers = $performanceData->count();
        $totalSubjects = $performanceData->sum('subjects_count');
        $totalClasses = $performanceData->sum('total_classes');
        $avgAttendanceRate = $totalTeachers > 0 ? round($performanceData->sum('attendance_rate') / $totalTeachers, 1) : 0;
    @endphp
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $totalTeachers }}</div>
            <div class="stat-label">Total Instructors</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $totalSubjects }}</div>
            <div class="stat-label">Total Subjects</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $totalClasses }}</div>
            <div class="stat-label">Total Classes Conducted</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $avgAttendanceRate }}%</div>
            <div class="stat-label">Avg Attendance Rate</div>
        </div>
    </div>

    <!-- Performance Table -->
    <table class="pdf-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">Instructor Information</th>
                <th style="width: 20%;">Subjects Assigned</th>
                <th style="width: 20%;">Classes Conducted</th>
                <th style="width: 20%;">Avg Attendance Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse($performanceData as $i => $data)
            <tr>
                <td class="text-center text-muted">{{ $i + 1 }}</td>
                <td>
                    <div class="font-semibold text-maroon" style="font-size: 11px;">{{ $data->teacher->name }}</div>
                    <div class="text-muted" style="font-size: 9px;">{{ $data->teacher->email }}</div>
                </td>
                <td class="text-center">
                    {{ $data->subjects_count }}
                </td>
                <td class="text-center">
                    {{ $data->total_classes }}
                </td>
                <td class="text-center">
                    <span class="badge {{ $data->attendance_rate >= 90 ? 'badge-present' : ($data->attendance_rate >= 75 ? 'badge-late' : 'badge-absent') }}">
                        {{ $data->attendance_rate }}%
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted" style="padding: 20px;">No performance data found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
@endsection
