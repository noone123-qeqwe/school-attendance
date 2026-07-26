@extends('teacher.layout')
@section('portal-title', 'Classroom - ' . $subject->name)

@push('styles')
<style>
    .class-header {
        background: linear-gradient(135deg, rgba(32,20,15,0.9) 0%, rgba(20,10,5,0.95) 100%);
        border: 1px solid rgba(207,164,111,0.25);
        border-radius: 24px;
        padding: 30px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        position: relative;
        overflow: hidden;
    }
    
    .class-header::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 6px; height: 100%;
        background: linear-gradient(180deg, #cfa46f 0%, #8f6e4a 100%);
    }

    .class-title-wrap {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .action-buttons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn-action {
        background: rgba(207,164,111,0.1);
        border: 1px solid rgba(207,164,111,0.3);
        color: #d6b67b;
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        text-decoration: none;
    }
    .btn-action:hover {
        background: rgba(207,164,111,0.2);
        color: #f3e7cd;
        transform: translateY(-2px);
    }
    
    .btn-primary-action {
        background: linear-gradient(135deg, #cfa46f, #8f6e4a);
        color: #fff;
        border: none;
    }
    .btn-primary-action:hover {
        background: linear-gradient(135deg, #dfb987, #a8845c);
        color: #fff;
    }

    /* Tabs */
    .unified-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 1px solid rgba(207,164,111,0.15);
        padding-bottom: 10px;
        overflow-x: auto;
    }
    
    .tab-btn {
        background: transparent;
        border: 1px solid transparent;
        color: #b39b82;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    
    .tab-btn:hover {
        background: rgba(207,164,111,0.05);
        color: #d6b67b;
    }
    
    .tab-btn.active {
        background: rgba(207,164,111,0.15);
        border-color: rgba(207,164,111,0.3);
        color: #f3e7cd;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease forwards;
    }
    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Data Tables */
    .data-card {
        background: rgba(30,20,15,0.6);
        border: 1px solid rgba(207,164,111,0.15);
        border-radius: 20px;
        padding: 24px;
    }
    
    .table-premium {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
    }
    .table-premium th {
        color: #b39b82;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
        padding: 0 16px 8px 16px;
        border-bottom: 1px solid rgba(207,164,111,0.2);
    }
    .table-premium td {
        background: rgba(255,255,255,0.02);
        padding: 16px;
        color: #e7dcc8;
        font-size: 0.9rem;
    }
    .table-premium tr td:first-child { border-radius: 12px 0 0 12px; border-left: 1px solid rgba(207,164,111,0.1); }
    .table-premium tr td:last-child { border-radius: 0 12px 12px 0; border-right: 1px solid rgba(207,164,111,0.1); }
    .table-premium tr td { border-top: 1px solid rgba(207,164,111,0.1); border-bottom: 1px solid rgba(207,164,111,0.1); }
    
    .table-premium tr:hover td {
        background: rgba(207,164,111,0.06);
    }

    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-present { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.3); }
    .status-absent { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
    .status-late { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }

    /* Custom Radio for Manual Attendance */
    .att-radio-group {
        display: flex; gap: 10px;
    }
    .att-radio {
        display: none;
    }
    .att-label {
        padding: 6px 14px;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.1);
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 600;
        color: #b39b82;
        transition: all 0.2s;
    }
    .att-radio[value="Present"]:checked + .att-label { background: rgba(34,197,94,0.2); border-color: #4ade80; color: #4ade80; }
    .att-radio[value="Absent"]:checked + .att-label { background: rgba(239,68,68,0.2); border-color: #f87171; color: #f87171; }
    .att-radio[value="Late"]:checked + .att-label { background: rgba(245,158,11,0.2); border-color: #fbbf24; color: #fbbf24; }
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success" style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.3); border-radius: 12px;">
    {{ session('success') }}
</div>
@endif

<!-- Class Header -->
<div class="class-header">
    <div class="class-title-wrap">
        <div>
            <div style="font-family: monospace; color: #cfa46f; font-weight: 700; margin-bottom: 6px;">{{ $subject->code }}</div>
            <h1 style="color: #f3e7cd; font-weight: 800; margin: 0 0 8px 0; font-size: 2rem;">{{ $subject->name }}</h1>
            <div style="color: #b39b82; font-size: 0.95rem; display: flex; gap: 16px; flex-wrap: wrap;">
                <span><i class="bi bi-mortarboard me-1"></i> Year {{ $subject->year_level }} - Sem {{ $subject->semester }}</span>
                <span><i class="bi bi-clock me-1"></i> {{ $subject->days ?: 'TBA' }} ({{ $subject->start_time ? \Carbon\Carbon::parse($subject->start_time)->format('h:i A') : 'TBA' }})</span>
                @if($subject->room)<span><i class="bi bi-geo-alt me-1"></i> Room {{ $subject->room }}</span>@endif
            </div>
        </div>
        <div class="action-buttons">
            <a href="{{ route('teacher.qr', $subject->code) }}" class="btn-action btn-primary-action">
                <i class="bi bi-qr-code-scan"></i> Start QR Attendance
            </a>
            <a href="{{ route('teacher.subjects.seating-chart', $subject->code) }}" class="btn-action">
                <i class="bi bi-grid-3x3-gap-fill"></i> Seating Chart
            </a>
            <a href="{{ route('teacher.subjects.edit', $subject->id) }}" class="btn-action">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
            <form action="{{ route('teacher.subjects.destroy', $subject->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this subject? All associated data will be removed.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-action" style="color:#f87171; border-color:rgba(239,68,68,0.3); background:rgba(239,68,68,0.1);">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="unified-tabs">
    <button class="tab-btn active" onclick="openTab('students')">
        <i class="bi bi-people-fill me-2"></i> Enrolled Students ({{ $students->count() }})
    </button>
    <button class="tab-btn" onclick="openTab('history')">
        <i class="bi bi-clock-history me-2"></i> Attendance History
    </button>
    <button class="tab-btn" onclick="openTab('manual')">
        <i class="bi bi-journal-check me-2"></i> Manual Entry
    </button>
</div>

<!-- Tab 1: Students Roster -->
<div id="students" class="tab-content active">
    <div class="data-card">
        <div style="overflow-x: auto;">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Course / Year</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $i => $student)
                        @php
                            $total = $student->attendances->count();
                            $present = $student->attendances->whereIn('status', ['Present', 'Late'])->count();
                            $absent = $student->attendances->where('status', 'Absent')->count();
                            $rate = $total > 0 ? round(($present / $total) * 100) : 0;
                        @endphp
                        <tr>
                            <td style="color:#b39b82;">{{ $i + 1 }}</td>
                            <td>
                                <div style="font-weight: 700; color: #f3e7cd;">{{ $student->name }}</div>
                                <div style="font-size: 0.75rem; color: #b39b82;">{{ $student->student_number }}</div>
                            </td>
                            <td>{{ $student->course }} - Y{{ $student->year_level }}</td>
                            <td style="color: #4ade80; font-weight: 600;">{{ $present }}</td>
                            <td style="color: #f87171; font-weight: 600;">{{ $absent }}</td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex: 1; height: 6px; background: rgba(255,255,255,0.05); border-radius: 10px; width: 60px;">
                                        <div style="height: 100%; border-radius: 10px; width: {{ $rate }}%; background: {{ $rate >= 75 ? '#cfa46f' : '#f87171' }}"></div>
                                    </div>
                                    <span style="font-weight: 700; font-size: 0.8rem; color: {{ $rate >= 75 ? '#cfa46f' : '#f87171' }}">{{ $rate }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center" style="padding: 40px; color: #b39b82;">No students enrolled yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab 2: Attendance History -->
<div id="history" class="tab-content">
    <div class="data-card">
        <div style="overflow-x: auto;">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Status</th>
                        <th>Recorded At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendanceRecords as $record)
                        <tr>
                            <td style="font-weight: 600;">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</td>
                            <td>{{ $record->user->name ?? 'Unknown' }}</td>
                            <td>
                                <span class="status-badge status-{{ strtolower($record->status) }}">
                                    {{ $record->status }}
                                </span>
                            </td>
                            <td style="color:#b39b82; font-size: 0.8rem;">
                                {{ $record->created_at->format('h:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center" style="padding: 40px; color: #b39b82;">No attendance records found for this class.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab 3: Manual Entry -->
<div id="manual" class="tab-content">
    <div class="data-card">
        <form action="{{ route('teacher.classroom.attendance.store', $subject->code) }}" method="POST">
            @csrf
            <div style="margin-bottom: 24px; max-width: 300px;">
                <label style="display: block; color: #b39b82; font-weight: 700; margin-bottom: 8px; font-size: 0.85rem; text-transform: uppercase;">Select Date</label>
                <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(207,164,111,0.3); color: #f3e7cd; padding: 12px; border-radius: 12px;">
            </div>

            <div style="overflow-x: auto; margin-bottom: 24px;">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th style="text-align: right;">Mark Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: #f3e7cd;">{{ $student->name }}</div>
                            </td>
                            <td>
                                <div class="att-radio-group" style="justify-content: flex-end;">
                                    <input type="radio" name="attendance[{{ $student->id }}]" value="Present" id="p_{{ $student->id }}" class="att-radio" checked>
                                    <label for="p_{{ $student->id }}" class="att-label">Present</label>
                                    
                                    <input type="radio" name="attendance[{{ $student->id }}]" value="Late" id="l_{{ $student->id }}" class="att-radio">
                                    <label for="l_{{ $student->id }}" class="att-label">Late</label>

                                    <input type="radio" name="attendance[{{ $student->id }}]" value="Absent" id="a_{{ $student->id }}" class="att-radio">
                                    <label for="a_{{ $student->id }}" class="att-label">Absent</label>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn-action btn-primary-action" style="padding: 14px 32px; font-size: 1rem;">
                    <i class="bi bi-save"></i> Save Attendance
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function openTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        // Remove active from all tabs
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        
        // Show target
        document.getElementById(tabId).classList.add('active');
        // Set active on clicked button
        event.currentTarget.classList.add('active');
    }
</script>
@endpush
