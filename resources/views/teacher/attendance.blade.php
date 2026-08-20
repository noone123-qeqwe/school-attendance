@extends('layouts.app')
@section('page-title', 'Attendance Records')

@section('content')

@if(session('success'))
<div class="ds-inline-alert ds-inline-alert-success">
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
            <a href="{{ route('teacher.attendance.csv', $attendanceFilterQuery) }}" class="btn btn-outline">
                <i class="bi bi-filetype-csv"></i> Export CSV
            </a>
            <a href="{{ route('teacher.reports') }}" class="btn btn-outline">
                <i class="bi bi-bar-chart-fill"></i> View Reports
            </a>
        </div>
    </x-slot>

    <!-- Filters -->
    <form method="GET" action="{{ route('teacher.attendance') }}" class="d-flex gap-3 flex-wrap mb-4 align-items-end">
        <div class="ds-form-group mb-0" style="flex: 1; min-width: 200px;">
            <label class="ds-label">Date</label>
            <input type="date" name="date" class="ds-input" value="{{ request('date') }}">
        </div>
        <div class="ds-form-group mb-0" style="flex: 1; min-width: 200px;">
            <label class="ds-label">Student Name</label>
            <input type="text" name="student_name" class="ds-input" placeholder="Name or ID" value="{{ request('student_name') }}">
        </div>
        <div class="ds-form-group mb-0" style="flex: 1; min-width: 200px;">
            <label class="ds-label">Status</label>
            <select name="status" class="ds-select">
                <option value="">All</option>
                <option value="Present" {{ request('status')=='Present'?'selected':'' }}>Present</option>
                <option value="Late"    {{ request('status')=='Late'?'selected':'' }}>Late</option>
                <option value="Absent"  {{ request('status')=='Absent'?'selected':'' }}>Absent</option>
                <option value="Excused" {{ request('status')=='Excused'?'selected':'' }}>Excused</option>
            </select>
        </div>
        <div class="ds-form-group mb-0" style="flex: 1; min-width: 200px;">
            <label class="ds-label">Subject</label>
            <select name="subject" class="ds-select">
                <option value="">All Subjects</option>
                @foreach($teacherSubjects as $subject)
                    <option value="{{ $subject->code }}" {{ request('subject') == $subject->code ? 'selected' : '' }}>
                        {{ $subject->code }} â€” {{ $subject->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="ds-form-group mb-0 d-flex gap-2">
            <button type="submit" class="ent-btn ent-btn-primary"><i class="bi bi-funnel"></i> Filter</button>
            @if(request()->hasAny(['date','status','subject','student_name']))
                <a href="{{ route('teacher.attendance') }}" class="ent-btn ent-btn-secondary">Clear</a>
            @endif
        </div>
    </form>

    <x-data-table :headers="['#', 'Student', 'Subject', 'Date', 'Status', 'Time In', 'Excused']">
        @forelse($attendanceRecords as $i => $record)
        <tr>
            <td data-label="#">{{ $attendanceRecords->firstItem() + $i }}</td>
            <td data-label="Student">
                <div style="font-weight:600;">{{ $record->user->name ?? 'â€”' }}</div>
                <div class="text-muted" style="font-size:.75rem;">{{ $record->user->student_number ?? '' }}</div>
            </td>
            <td data-label="Subject" style="font-weight:600;">{{ $record->subject->name ?? $record->subject_code }}</td>
            <td data-label="Date">
                <div style="font-weight:600;">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</div>
                <div class="text-muted" style="font-size:.75rem;">{{ \Carbon\Carbon::parse($record->date)->format('l') }}</div>
            </td>
            <td data-label="Status">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 2px 10px; font-size: 0.75rem; border-color: rgba(207,164,111,0.2); color: #f3e7cd;" id="statusBtn_{{ $record->id }}">
                        @if($record->excused)
                            <x-badge type="excused">Excused</x-badge>
                        @else
                            @php $recordStatus = strtolower($record->status ?? ''); @endphp
                            @if($recordStatus === 'present') <x-badge type="present">Present</x-badge>
                            @elseif($recordStatus === 'late')  <x-badge type="late">Late</x-badge>
                            @else <x-badge type="absent">Absent</x-badge>
                            @endif
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark" style="font-size: 0.85rem;">
                        <li><a class="dropdown-item status-override" href="#" data-id="{{ $record->id }}" data-status="Present"><span class="text-success"><i class="bi bi-circle-fill me-2"></i>Present</span></a></li>
                        <li><a class="dropdown-item status-override" href="#" data-id="{{ $record->id }}" data-status="Late"><span class="text-warning"><i class="bi bi-circle-fill me-2"></i>Late</span></a></li>
                        <li><a class="dropdown-item status-override" href="#" data-id="{{ $record->id }}" data-status="Absent"><span class="text-danger"><i class="bi bi-circle-fill me-2"></i>Absent</span></a></li>
                        <li><a class="dropdown-item status-override" href="#" data-id="{{ $record->id }}" data-status="Excused"><span class="text-info"><i class="bi bi-circle-fill me-2"></i>Excused</span></a></li>
                    </ul>
                </div>
            </td>
            <td data-label="Time In">{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : 'â€”' }}</td>
            <td data-label="Excused">
                @if($record->excused)
                    <span class="text-success" style="font-size:.85rem; font-weight: 700;"><i class="bi bi-check-lg"></i> Excused</span>
                    @if($record->excuse_note)
                        <div class="text-muted" style="font-size:.75rem; margin-top:2px;">{{ $record->excuse_note }}</div>
                    @endif
                @else
                    <span class="text-muted">â€”</span>
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
                Showing {{ $attendanceRecords->firstItem() }}â€“{{ $attendanceRecords->lastItem() }} of {{ $attendanceRecords->total() }} records
            </div>
            <div>
                {{ $attendanceRecords->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</x-card>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const overrides = document.querySelectorAll('.status-override');
    
    overrides.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            const btnEl = document.getElementById('statusBtn_' + id);
            
            // Show loading
            const originalContent = btnEl.innerHTML;
            btnEl.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
            
            fetch(`/teacher/attendance/${id}/override`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status: status })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    // Update badge visually
                    let badgeHtml = '';
                    if (data.status === 'Excused' || data.excused) {
                        badgeHtml = '<x-badge type="excused">Excused</x-badge>';
                    } else if (data.status === 'Present') {
                        badgeHtml = '<x-badge type="present">Present</x-badge>';
                    } else if (data.status === 'Late') {
                        badgeHtml = '<x-badge type="late">Late</x-badge>';
                    } else {
                        badgeHtml = '<x-badge type="absent">Absent</x-badge>';
                    }
                    btnEl.innerHTML = badgeHtml;
                    
                    if (typeof Toastify !== 'undefined') {
                        Toastify({
                            text: data.message,
                            duration: 3000,
                            gravity: "top",
                            position: "right",
                            style: { background: "#4ade80", color: "#111827" }
                        }).showToast();
                    }
                } else {
                    btnEl.innerHTML = originalContent;
                    alert(data.message || 'Error updating status');
                }
            })
            .catch(err => {
                console.error(err);
                btnEl.innerHTML = originalContent;
                alert('A network error occurred.');
            });
        });
    });
});
</script>
@endsection
