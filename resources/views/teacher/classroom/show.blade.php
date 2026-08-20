@extends('layouts.app')
@section('portal-title', 'Manage Class - ' . $subject->name)

@push('styles')
<style>
    .classroom-header {
        background: linear-gradient(145deg, rgba(32,20,15,0.8) 0%, rgba(20,10,5,0.9) 100%);
        border: 1px solid rgba(207,164,111,0.2);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
    }
    
    .subject-badge {
        background: rgba(207,164,111,0.15);
        color: #d6b67b;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        font-family: monospace;
        display: inline-block;
        margin-bottom: 12px;
    }
    
    .subject-title {
        font-size: 2rem;
        font-weight: 800;
        color: #f3e7cd;
        margin-bottom: 20px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .info-card {
        background: rgba(207,164,111,0.05);
        padding: 16px;
        border-radius: 12px;
        border: 1px solid rgba(207,164,111,0.1);
    }
    
    .info-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: rgba(179,155,130,0.7);
        font-weight: 700;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }
    
    .info-value {
        font-size: 1.1rem;
        color: #f3e7cd;
        font-weight: 600;
    }
    
    .tab-nav {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        border-bottom: 2px solid rgba(207,164,111,0.2);
        padding-bottom: 0;
    }
    
    .tab-btn {
        padding: 12px 24px;
        background: transparent;
        border: none;
        color: #b39b82;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
    }
    
    .tab-btn.active {
        color: #f3e7cd;
        border-bottom-color: #cfa46f;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: linear-gradient(145deg, rgba(32,20,15,0.8) 0%, rgba(20,10,5,0.9) 100%);
        border: 1px solid rgba(207,164,111,0.2);
        border-radius: 16px;
        padding: 24px;
        transition: all 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        background: rgba(207,164,111,0.15);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #cfa46f;
        margin-bottom: 16px;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: #f3e7cd;
        margin-bottom: 4px;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: #b39b82;
        font-weight: 500;
    }
    
    .students-table {
        background: linear-gradient(145deg, rgba(32,20,15,0.8) 0%, rgba(20,10,5,0.9) 100%);
        border: 1px solid rgba(207,164,111,0.2);
        border-radius: 16px;
        overflow: hidden;
    }
    
    .students-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .students-table thead {
        background: rgba(207,164,111,0.1);
    }
    
    .students-table th {
        padding: 16px;
        text-align: left;
        color: #d6b67b;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(207,164,111,0.15);
    }
    
    .students-table td {
        padding: 16px;
        color: #f3e7cd;
        border-bottom: 1px solid rgba(255,255,255,0.03);
    }
    
    .students-table tbody tr:hover {
        background: rgba(207,164,111,0.05);
    }
    
    .badge-present { background: #28a745; color: white; padding: 4px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
    .badge-late { background: #ffc107; color: #000; padding: 4px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
    .badge-absent { background: #dc3545; color: white; padding: 4px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
    .badge-excused { background: #6c757d; color: white; padding: 4px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
    
    .btn-action {
        background: linear-gradient(135deg, #cfa46f 0%, #8f6e4a 100%);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-action:hover {
        background: linear-gradient(135deg, #dfb987 0%, #a8845c 100%);
        transform: translateY(-2px);
        color: white;
    }
    
    .btn-secondary {
        background: rgba(207,164,111,0.15);
        border: 1px solid rgba(207,164,111,0.3);
        color: #f3e7cd;
    }
    
    .btn-secondary:hover {
        background: rgba(207,164,111,0.25);
        color: #f3e7cd;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: rgba(30,20,15,0.4);
        border-radius: 16px;
        border: 1px dashed rgba(207,164,111,0.2);
    }
    
    .empty-state i {
        font-size: 3rem;
        color: #8f6e4a;
        margin-bottom: 16px;
    }
</style>
@endpush

@section('content')
<div class="classroom-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <a href="{{ route('teacher.classroom.index') }}" class="btn-secondary btn-action mb-3" style="font-size: 0.9rem;">
                <i class="bi bi-arrow-left"></i> Back to My Classes
            </a>
            <div class="subject-badge">{{ $subject->code }}</div>
            <h1 class="subject-title">{{ $subject->name }}</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('teacher.qr', $subject->code) }}" class="btn-action">
                <i class="bi bi-qr-code"></i> Start QR Session
            </a>
            <a href="{{ route('teacher.subjects.edit', $subject->id) }}" class="btn-secondary btn-action">
                <i class="bi bi-gear"></i> Edit Subject
            </a>
        </div>
    </div>
    
    <div class="info-grid">
        <div class="info-card">
            <div class="info-label">Schedule</div>
            <div class="info-value">
                @php
                    $days = $subject->schedules->pluck('day')->map(function($day) {
                        return ['Monday'=>'Mon','Tuesday'=>'Tue','Wednesday'=>'Wed','Thursday'=>'Thu','Friday'=>'Fri','Saturday'=>'Sat'][$day] ?? $day;
                    })->join(', ');
                @endphp
                {{ $days ?: 'No schedule set' }}
            </div>
        </div>
        <div class="info-card">
            <div class="info-label">Time</div>
            <div class="info-value">
                @if($subject->schedules->first())
                    {{ \Carbon\Carbon::parse($subject->schedules->first()->start_time)->format('h:i A') }} - 
                    {{ \Carbon\Carbon::parse($subject->schedules->first()->end_time)->format('h:i A') }}
                @else
                    TBA
                @endif
            </div>
        </div>
        <div class="info-card">
            <div class="info-label">Year Level / Semester</div>
            <div class="info-value">Year {{ $subject->year_level }} - Semester {{ $subject->semester }}</div>
        </div>
        <div class="info-card">
            <div class="info-label">Section</div>
            <div class="info-value">{{ $subject->section ?: 'All Sections' }}</div>
        </div>
    </div>
</div>

@php
    // Calculate statistics
    $totalStudents = $students->count();
    $totalRecords = $attendanceRecords->count();
    $presentCount = $attendanceRecords->where('status', 'Present')->count();
    $lateCount = $attendanceRecords->where('status', 'Late')->count();
    $absentCount = $attendanceRecords->where('status', 'Absent')->where('excused', false)->count();
    $excusedCount = $attendanceRecords->where('excused', true)->count();
    $attendanceRate = $totalRecords > 0 ? round((($presentCount + $lateCount) / $totalRecords) * 100) : 0;
@endphp

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        <div class="stat-value">{{ $totalStudents }}</div>
        <div class="stat-label">Total Students</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="stat-value">{{ $presentCount }}</div>
        <div class="stat-label">Present Records</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
        <div class="stat-value">{{ $lateCount }}</div>
        <div class="stat-label">Late Records</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
        <div class="stat-value">{{ $absentCount }}</div>
        <div class="stat-label">Absent Records</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-percent"></i></div>
        <div class="stat-value">{{ $attendanceRate }}%</div>
        <div class="stat-label">Attendance Rate</div>
    </div>
</div>

<div class="tab-nav">
    <button class="tab-btn active" data-tab="students">
        <i class="bi bi-people"></i> Students ({{ $totalStudents }})
    </button>
    <button class="tab-btn" data-tab="attendance">
        <i class="bi bi-list-check"></i> Attendance History ({{ $totalRecords }})
    </button>
</div>

<div class="tab-content active" id="students-tab">
    @if($students->isEmpty())
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <h3 style="color: #f3e7cd; font-weight: 700;">No Students Found</h3>
            <p style="color: #b39b82;">No students match this class's year level and semester.</p>
        </div>
    @else
        <div class="students-table">
            <table>
                <thead>
                    <tr>
                        <th>Student Number</th>
                        <th>Name</th>
                        <th>Year Level</th>
                        <th>Section</th>
                        <th class="text-center">Total Records</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Late</th>
                        <th class="text-center">Absent</th>
                        <th class="text-center">Attendance Rate</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                        @php
                            $studRecords = $student->attendances;
                            $studTotal = $studRecords->count();
                            $studPresent = $studRecords->where('status', 'Present')->count();
                            $studLate = $studRecords->where('status', 'Late')->count();
                            $studAbsent = $studRecords->where('status', 'Absent')->count();
                            $studRate = $studTotal > 0 ? round((($studPresent + $studLate) / $studTotal) * 100) : 0;
                        @endphp
                        <tr>
                            <td><span style="font-family: monospace; font-weight: 600;">{{ $student->student_number }}</span></td>
                            <td>{{ $student->name }}</td>
                            <td>Year {{ $student->year_level }}</td>
                            <td>{{ $student->section ?: '-' }}</td>
                            <td class="text-center">{{ $studTotal }}</td>
                            <td class="text-center"><span class="badge-present">{{ $studPresent }}</span></td>
                            <td class="text-center"><span class="badge-late">{{ $studLate }}</span></td>
                            <td class="text-center"><span class="badge-absent">{{ $studAbsent }}</span></td>
                            <td class="text-center"><strong>{{ $studRate }}%</strong></td>
                            <td class="text-center">
                                <a href="{{ route('teacher.student', $student->id) }}" class="btn-action btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="tab-content" id="attendance-tab">
    @if($attendanceRecords->isEmpty())
        <div class="empty-state">
            <i class="bi bi-list-check"></i>
            <h3 style="color: #f3e7cd; font-weight: 700;">No Attendance Records</h3>
            <p style="color: #b39b82;">No attendance has been recorded for this class yet.</p>
        </div>
    @else
        <div class="students-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Student Number</th>
                        <th>Time In</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendanceRecords as $record)
                        <tr>
                            <td>{{ $record->date->format('M d, Y') }}</td>
                            <td>{{ $record->user->name }}</td>
                            <td><span style="font-family: monospace; font-weight: 600;">{{ $record->user->student_number }}</span></td>
                            <td>{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '-' }}</td>
                            <td class="text-center">
                                @if($record->excused)
                                    <span class="badge-excused">Excused</span>
                                @elseif($record->status === 'Present')
                                    <span class="badge-present">Present</span>
                                @elseif($record->status === 'Late')
                                    <span class="badge-late">Late</span>
                                @else
                                    <span class="badge-absent">Absent</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button class="btn-action btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;" onclick="showEditModal({{ $record->id }}, '{{ $record->status }}', {{ $record->excused ? 'true' : 'false' }})">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all tabs
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            const tabId = this.dataset.tab + '-tab';
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    function showEditModal(recordId, status, excused) {
        // TODO: Implement edit attendance modal
        alert('Edit functionality - Record ID: ' + recordId);
    }
</script>
@endpush
