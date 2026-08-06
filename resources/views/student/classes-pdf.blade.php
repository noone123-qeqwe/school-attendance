@extends('layouts.pdf')

@section('title', 'My Class Schedule')
@section('report-title', 'My Class Schedule')
@section('footer-title', 'Class Schedule')
@section('footer-details', 'Student: ' . $user->name . ' • Year ' . $user->year_level . ' • Semester ' . $user->semester . ' • Total subjects: ' . $subjects->count())

@section('content')
    <!-- Student Information -->
    <div class="info-section">
        <div class="info-title">Student Information</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div>
                <strong style="color: #800000;">Name:</strong> {{ $user->name }}
            </div>
            <div>
                <strong style="color: #800000;">Student Number:</strong> 
                <span style="font-family: 'Courier New', monospace; font-weight: bold;">{{ $user->student_number }}</span>
            </div>
            <div>
                <strong style="color: #800000;">Year Level:</strong> Year {{ $user->year_level }}
            </div>
            <div>
                <strong style="color: #800000;">Semester:</strong> {{ $user->semester }}{{ match((int)$user->semester){1=>'st',2=>'nd',3=>'rd',default=>'th'} }} Semester
            </div>
        </div>
    </div>

    @if($subjects->count() > 0)
    <!-- Statistics -->
    @php
        $totalSubjects = $subjects->count();
        $totalUnits = $subjects->sum('units');
        $subjectsWithSchedule = $subjects->where('start_time')->where('end_time')->count();
        $subjectsWithInstructor = $subjects->filter(function($s) { return $s->instructorUser !== null; })->count();
    @endphp
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $totalSubjects }}</div>
            <div class="stat-label">Total Subjects</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $totalUnits }}</div>
            <div class="stat-label">Total Units</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $subjectsWithSchedule }}</div>
            <div class="stat-label">With Schedule</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $subjectsWithInstructor }}</div>
            <div class="stat-label">With Instructor</div>
        </div>
    </div>

    <!-- Subjects Table -->
    <table class="pdf-table">
        <thead>
            <tr>
                <th style="width: 12%;">Code</th>
                <th style="width: 28%;">Subject</th>
                <th style="width: 12%;">Days</th>
                <th style="width: 18%;">Time</th>
                <th style="width: 8%;">Units</th>
                <th style="width: 15%;">Teacher</th>
                <th style="width: 7%;">Section</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $subject)
            <tr>
                <td>
                    <span class="badge badge-course" style="font-family: 'Courier New', monospace;">{{ $subject->code }}</span>
                </td>
                <td>
                    <div class="font-semibold text-maroon" style="font-size: 11px;">{{ $subject->name }}</div>
                </td>
                <td class="text-center">
                    @if($subject->days)
                        <span class="badge" style="background: linear-gradient(135deg, #e0f2fe 0%, #b3e5fc 100%); color: #0277bd; border: 1px solid #4fc3f7;">{{ $subject->days }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($subject->start_time && $subject->end_time)
                        <div style="font-size: 10px; font-family: 'Courier New', monospace;">
                            {{ \Carbon\Carbon::parse($subject->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($subject->end_time)->format('h:i A') }}
                        </div>
                    @else
                        <span class="text-muted">TBA</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($subject->units)
                        <span class="badge badge-year">{{ $subject->units }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($subject->instructorUser)
                        <div style="font-size: 10px;">{{ $subject->instructorUser->name }}</div>
                    @else
                        <span class="text-muted">TBA</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($subject->section)
                        <span class="badge badge-semester">{{ $subject->section }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @else
    <!-- No Data -->
    <div class="no-data">
        <h3>No Subjects Found</h3>
        <p>No subjects found for your year level and semester. Please contact your academic advisor.</p>
    </div>
    @endif
@endsection
