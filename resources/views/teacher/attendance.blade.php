@extends('teacher.layout')
@section('page-title', 'Attendance Records')

@section('content')

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
</div>
@endif

<x-card title="All Attendance Records" icon="bi bi-calendar-check-fill">
    <x-slot name="headerActions">
        @php
            $attendanceFilterQuery = request()->only(['date','status','subject','student_name']);
        @endphp
        <div class="d-flex gap-2">
            <a href="{{ route('teacher.attendance.preview', $attendanceFilterQuery) }}" class="btn btn-outline">
                <i class="bi bi-eye-fill"></i> Preview PDF
            </a>
            <a href="{{ route('teacher.reports') }}" class="btn btn-outline">
                <i class="bi bi-bar-chart-fill"></i> View Reports
            </a>
        </div>
    </x-slot>

    <!-- Filters -->
    <form method="GET" action="{{ route('teacher.attendance') }}" class="d-flex gap-3 flex-wrap mb-4 align-items-end">
        <div class="form-group mb-0" style="flex: 1; min-width: 200px;">
            <label class="text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Date</label>
            <input type="date" name="date" class="form-control" value="{{ request('date') }}">
        </div>
        <div class="form-group mb-0" style="flex: 1; min-width: 200px;">
            <label class="text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Student Name</label>
            <input type="text" name="student_name" class="form-control" placeholder="Name or ID" value="{{ request('student_name') }}">
        </div>
        <div class="form-group mb-0" style="flex: 1; min-width: 200px;">
            <label class="text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Status</label>
            <select name="status" class="form-control">
                <option value="">All</option>
                <option value="Present" {{ request('status')=='Present'?'selected':'' }}>Present</option>
                <option value="Late"    {{ request('status')=='Late'?'selected':'' }}>Late</option>
                <option value="Absent"  {{ request('status')=='Absent'?'selected':'' }}>Absent</option>
                <option value="Excused" {{ request('status')=='Excused'?'selected':'' }}>Excused</option>
            </select>
        </div>
        <div class="form-group mb-0" style="flex: 1; min-width: 200px;">
            <label class="text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Subject</label>
            <select name="subject" class="form-control">
                <option value="">All Subjects</option>
                @foreach($teacherSubjects as $subject)
                    <option value="{{ $subject->code }}" {{ request('subject') == $subject->code ? 'selected' : '' }}>
                        {{ $subject->code }} — {{ $subject->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group mb-0 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            @if(request()->hasAny(['date','status','subject','student_name']))
                <a href="{{ route('teacher.attendance') }}" class="btn btn-outline">Clear</a>
            @endif
        </div>
    </form>

    <x-data-table :headers="['#', 'Student', 'Subject', 'Date', 'Status', 'Time In', 'Excused']">
        @forelse($attendanceRecords as $i => $record)
        <tr>
            <td data-label="#">{{ $attendanceRecords->firstItem() + $i }}</td>
            <td data-label="Student">
                <div style="font-weight:600;">{{ $record->user->name ?? '—' }}</div>
                <div class="text-muted" style="font-size:.75rem;">{{ $record->user->student_number ?? '' }}</div>
            </td>
            <td data-label="Subject" style="font-weight:600;">{{ $record->subject->name ?? $record->subject_code }}</td>
            <td data-label="Date">
                <div style="font-weight:600;">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</div>
                <div class="text-muted" style="font-size:.75rem;">{{ \Carbon\Carbon::parse($record->date)->format('l') }}</div>
            </td>
            <td data-label="Status">
                @if($record->excused)
                    <x-badge type="excused">Excused</x-badge>
                @else
                    @php $recordStatus = strtolower($record->status ?? ''); @endphp
                    @if($recordStatus === 'present') <x-badge type="present">Present</x-badge>
                    @elseif($recordStatus === 'late')  <x-badge type="late">Late</x-badge>
                    @else <x-badge type="absent">Absent</x-badge>
                    @endif
                @endif
            </td>
            <td data-label="Time In">{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '—' }}</td>
            <td data-label="Excused">
                @if($record->excused)
                    <span class="text-success" style="font-size:.85rem; font-weight: 700;"><i class="bi bi-check-lg"></i> Excused</span>
                    @if($record->excuse_note)
                        <div class="text-muted" style="font-size:.75rem; margin-top:2px;">{{ $record->excuse_note }}</div>
                    @endif
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7">
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <p>No records found.</p>
                </div>
            </td>
        </tr>
        @endforelse
    </x-data-table>

    <!-- Pagination -->
    @if($attendanceRecords->hasPages())
        <div class="mt-4 d-flex justify-content-between align-items-center">
            <div class="text-muted" style="font-size:.85rem;">
                Showing {{ $attendanceRecords->firstItem() }}–{{ $attendanceRecords->lastItem() }} of {{ $attendanceRecords->total() }} records
            </div>
            <div>
                {{ $attendanceRecords->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</x-card>
@endsection