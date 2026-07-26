@extends('layouts.pdf')

@section('title', 'Students List Report')
@section('report-title', 'Students Directory Report')
@section('footer-title', 'Students Directory Report')

@section('footer-details', 'This document contains ' . $students->count() . ' student record' . ($students->count() !== 1 ? 's' : ''))

@section('content')
    <!-- Filters (if any) -->
    @if(!empty(array_filter($filters)))
    <div class="info-section">
        <div class="info-title">Applied Filters</div>
        <div class="filter-tags">
            @if(!empty($filters['search']))
                <div class="filter-tag"><strong>Search:</strong> {{ $filters['search'] }}</div>
            @endif
            @if(!empty($filters['course']))
                <div class="filter-tag"><strong>Course:</strong> {{ $filters['course'] }}</div>
            @endif
            @if(!empty($filters['year_level']))
                <div class="filter-tag"><strong>Year:</strong> {{ $filters['year_level'] }}</div>
            @endif
            @if(!empty($filters['semester']))
                <div class="filter-tag"><strong>Semester:</strong> {{ $filters['semester'] == '1' ? '1st' : '2nd' }}</div>
            @endif
        </div>
    </div>
    @endif

    @if($students->count() > 0)
    <!-- Statistics -->
    @php
        $totalStudents = $students->count();
        $courseStats = $students->groupBy('course');
        $yearStats = $students->groupBy('year_level');
        $totalAbsences = $students->sum(function($student) {
            return $student->attendances->where('status', 'Absent')->count();
        });
    @endphp
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $totalStudents }}</div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $courseStats->count() }}</div>
            <div class="stat-label">Courses</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $yearStats->count() }}</div>
            <div class="stat-label">Year Levels</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $totalAbsences }}</div>
            <div class="stat-label">Total Absences</div>
        </div>
    </div>

    <!-- Students Table -->
    <table class="pdf-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 30%;">Student Information</th>
                <th style="width: 12%;">Student ID</th>
                <th style="width: 12%;">Course</th>
                <th style="width: 10%;">Year</th>
                <th style="width: 10%;">Semester</th>
                <th style="width: 10%;">Absences</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $i => $student)
            @php $absences = $student->attendances->where('status', 'Absent')->count(); @endphp
            <tr>
                <td class="text-center text-muted">{{ $i + 1 }}</td>
                <td>
                    <div class="font-semibold text-maroon" style="font-size: 11px;">{{ $student->name }}</div>
                    <div class="text-muted" style="font-size: 9px;">{{ $student->email }}</div>
                </td>
                <td>
                    <div class="font-bold text-maroon" style="font-family: 'Courier New', monospace; font-size: 10px;">{{ $student->student_number }}</div>
                </td>
                <td>
                    <span class="badge badge-course">{{ $student->course }}</span>
                </td>
                <td>
                    <span class="badge badge-year">Year {{ $student->year_level }}</span>
                </td>
                <td>
                    <span class="badge badge-semester">{{ $student->semester }}{{ (int)$student->semester === 1 ? 'st' : 'nd' }}</span>
                </td>
                <td class="text-center">
                    @if($absences > 0)
                        <span class="badge badge-absent">{{ $absences }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Course Summary -->
    @if($courseStats->count() > 1)
    <div class="info-section">
        <div class="info-title">Course Distribution</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px;">
            @foreach($courseStats as $course => $courseStudents)
                <div class="stat-card" style="padding: 15px;">
                    <div class="stat-number" style="font-size: 20px;">{{ $courseStudents->count() }}</div>
                    <div class="stat-label">{{ $course }} Students</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @else
    <!-- No Data -->
    <div class="no-data">
        <h3>No Students Found</h3>
        <p>No students match the selected criteria. Please adjust your filters and try again.</p>
    </div>
    @endif
@endsection
