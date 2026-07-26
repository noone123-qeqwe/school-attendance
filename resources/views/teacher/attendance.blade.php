@extends('teacher.layout')
@section('page-title', 'Attendance Records')

@section('content')
<style>
    .tch-attendance-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .tch-filter-row {
        padding: 18px 22px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        align-items: flex-end;
    }
    .tch-filter-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 220px;
        max-width: 280px;
        flex: 1 1 220px;
    }
    .tch-filter-field label {
        font-size: .68rem;
        font-weight: 700;
        color: #d4b16e;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .tch-filter-field input,
    .tch-filter-field select {
        width: 100%;
    }
    .tch-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }
    .tch-table thead th {
        padding: 16px 18px;
        text-align: left;
        font-size: .82rem;
        font-weight: 700;
        color: #f3e7cd;
        text-transform: uppercase;
        letter-spacing: .04em;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.04);
    }
    .tch-table tbody tr {
        background: transparent;
        border: none;
        border-radius: 0;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .tch-table tbody tr:hover {
        transform: translateY(-1px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.12);
    }
    .tch-table tbody td {
        padding: 16px 18px;
        vertical-align: top;
        color: #f8fafc;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .tch-table tbody td:last-child {
        border-bottom: none;
    }
    .tch-table tbody tr:last-child td {
        border-bottom: none;
    }
    .tch-table tbody td div,
    .tch-table tbody td span {
        color: inherit;
    }
    .tch-table tbody td[data-label="Status"] span,
    .tch-table tbody td[data-label="Excused"] span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    @media (max-width: 992px) {
        .tch-filter-field { min-width: 200px; }
        .tch-attendance-actions { justify-content: flex-start; }
    }
    @media (max-width: 768px) {
        .tch-filter-row {
            padding: 14px 16px;
        }
        .tch-filter-field {
            min-width: auto;
            flex: 1 1 100%;
        }
        .tch-attendance-actions {
            width: 100%;
            justify-content: flex-start;
        }
        .tch-table thead { display: none; }
        .tch-table tbody tr {
            display: block;
            border: none;
            border-radius: 0;
            margin-bottom: 14px;
            background: transparent;
            box-shadow: none;
        }
        .tch-table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255,215,145,0.08);
            font-size: .85rem;
        }
        .tch-table tbody td:last-child { border-bottom: none; }
        .tch-table tbody td::before {
            content: attr(data-label);
            font-size: .72rem;
            font-weight: 700;
            color: #d4b16e;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-right: 10px;
            flex-shrink: 0;
        }
    }
</style>

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
</div>
@endif

<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-calendar-check-fill"></i></div>
            All Attendance Records
        </div>
        <div class="tch-attendance-actions">
            @php
                $attendanceFilterQuery = request()->only(['date','status','subject','student_name']);
            @endphp
            <a href="{{ route('teacher.attendance.preview', $attendanceFilterQuery) }}" class="tch-btn tch-btn-ghost">
                <i class="bi bi-eye-fill"></i> Preview PDF
            </a>
            <a href="{{ route('teacher.reports') }}" class="tch-btn tch-btn-ghost">
                <i class="bi bi-bar-chart-fill"></i> View Reports
            </a>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('teacher.attendance') }}" class="tch-filter-row">
        <div class="tch-filter-field">
            <label>Date</label>
            <input type="date" name="date" class="tch-input" value="{{ request('date') }}">
        </div>
        <div class="tch-filter-field">
            <label>Student Name</label>
            <input type="text" name="student_name" class="tch-input" placeholder="Name or Student ID" value="{{ request('student_name') }}">
        </div>
        <div class="tch-filter-field">
            <label>Status</label>
            <select name="status" class="tch-input">
                <option value="">All</option>
                <option value="Present" {{ request('status')=='Present'?'selected':'' }}>Present</option>
                <option value="Late"    {{ request('status')=='Late'?'selected':'' }}>Late</option>
                <option value="Absent"  {{ request('status')=='Absent'?'selected':'' }}>Absent</option>
                <option value="Excused" {{ request('status')=='Excused'?'selected':'' }}>Excused</option>
            </select>
        </div>
        <div class="tch-filter-field">
            <label>Subject</label>
            <select name="subject" class="tch-input">
                <option value="">All Subjects</option>
                @foreach($teacherSubjects as $subject)
                    <option value="{{ $subject->code }}" {{ request('subject') == $subject->code ? 'selected' : '' }}>
                        {{ $subject->code }} — {{ $subject->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="tch-filter-field" style="flex:0 0 auto; display:flex; align-items:flex-end; gap:8px;">
            <button type="submit" class="tch-btn tch-btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
            @if(request()->hasAny(['date','status','subject','student_name']))
            <a href="{{ route('teacher.attendance') }}" class="tch-btn tch-btn-ghost">Clear</a>
            @endif
        </div>
    </form>

    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <table class="tch-table">
            <thead>
                <tr><th>#</th><th>Student</th><th>Subject</th><th>Date</th><th>Status</th><th>Time In</th><th>Excused</th></tr>
            </thead>
            <tbody>
                @forelse($attendanceRecords as $i => $record)
                <tr>
                    <td data-label="#" style="color:#cbd5e1;font-size:.78rem;">{{ $attendanceRecords->firstItem() + $i }}</td>
                    <td data-label="Student">
                        <div style="font-weight:600;color:#f8fafc;font-size:.875rem;">{{ $record->user->name ?? '—' }}</div>
                        <div style="font-size:.72rem;color:#cbd5e1;">{{ $record->user->student_number ?? '' }}</div>
                    </td>
                    <td data-label="Subject" style="font-weight:600;color:#f8fafc;">{{ $record->subject->name ?? $record->subject_code }}</td>
                    <td data-label="Date">
                        <div style="font-size:.85rem;font-weight:600;color:#f8fafc;">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</div>
                        <div style="font-size:.72rem;color:#cbd5e1;">{{ \Carbon\Carbon::parse($record->date)->format('l') }}</div>
                    </td>
                    <td data-label="Status">
                        @if($record->excused)
                            <span class="badge-excused" style="background:#f0fdf4;color:#16a34a;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;display:inline-block;">Excused</span>
                        @else
                            @php $recordStatus = strtolower($record->status ?? ''); @endphp
                            @if($recordStatus === 'present') <span class="badge-present">Present</span>
                            @elseif($recordStatus === 'late')  <span class="badge-late">Late</span>
                            @else <span class="badge-absent">Absent</span>
                            @endif
                        @endif
                    </td>
                    <td data-label="Time In" style="color:#cbd5e1;">{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '—' }}</td>
                    <td data-label="Excused">
                        @if($record->excused)
                            <span style="background:#f0fdf4;color:#16a34a;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;">✓ Excused</span>
                            @if($record->excuse_note)
                            <div style="font-size:.72rem;color:#94a3b8;margin-top:2px;">{{ $record->excuse_note }}</div>
                            @endif
                        @else
                            <span style="color:#94a3b8;font-size:.8rem;">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-calendar-x"></i><p>No records found.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($attendanceRecords->hasPages())
    <div style="padding:14px 22px;border-top:1px solid #f8fafc;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div style="font-size:.78rem;color:#94a3b8;">
            Showing {{ $attendanceRecords->firstItem() }}–{{ $attendanceRecords->lastItem() }} of {{ $attendanceRecords->total() }} records
        </div>
        <div style="display:flex;gap:4px;flex-wrap:wrap;">
            {{-- Previous --}}
            @if($attendanceRecords->onFirstPage())
                <span style="padding:6px 12px;border-radius:8px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#cbd5e1;font-size:.8rem;font-weight:600;">‹ Prev</span>
            @else
                <a href="{{ $attendanceRecords->previousPageUrl() }}" style="padding:6px 12px;border-radius:8px;border:1.5px solid #e2e8f0;background:white;color:#475569;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .2s;" onmouseover="this.style.borderColor='#7c2d12';this.style.color='#7c2d12';" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569';">‹ Prev</a>
            @endif

            {{-- Page numbers --}}
            @foreach($attendanceRecords->getUrlRange(max(1, $attendanceRecords->currentPage()-2), min($attendanceRecords->lastPage(), $attendanceRecords->currentPage()+2)) as $page => $url)
                @if($page == $attendanceRecords->currentPage())
                    <span style="padding:6px 12px;border-radius:8px;border:1.5px solid #7c2d12;background:#7c2d12;color:white;font-size:.8rem;font-weight:700;">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" style="padding:6px 12px;border-radius:8px;border:1.5px solid #e2e8f0;background:white;color:#475569;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .2s;" onmouseover="this.style.borderColor='#7c2d12';this.style.color='#7c2d12';" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569';">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Next --}}
            @if($attendanceRecords->hasMorePages())
                <a href="{{ $attendanceRecords->nextPageUrl() }}" style="padding:6px 12px;border-radius:8px;border:1.5px solid #e2e8f0;background:white;color:#475569;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .2s;" onmouseover="this.style.borderColor='#7c2d12';this.style.color='#7c2d12';" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569';">Next ›</a>
            @else
                <span style="padding:6px 12px;border-radius:8px;border:1.5px solid #e2e8f0;background:#f8fafc;color:#cbd5e1;font-size:.8rem;font-weight:600;">Next ›</span>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection