@extends('layouts.app')

@section('portal-title', 'Attendance Monitoring')
@section('page-title', 'Attendance Management')
@section('page-sub', 'Monitor class summaries and individual student attendance records')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h1 class="saas-heading saas-heading-lg mb-1">Attendance Records</h1>
        <p class="saas-text-muted m-0">Review aggregated class attendance, drill down to individual students, and manage manual overrides.</p>
    </div>
    
    <div class="d-flex gap-2">
        <a href="{{ route('admin.activity.log') }}" class="btn btn-outline" style="border-color: rgba(96, 165, 250, 0.3); color: #60a5fa;">
            <i class="bi bi-shield-lock"></i> Audit Trail
        </a>
        <a href="{{ route('admin.attendance.export', request()->all()) }}" class="btn btn-outline" style="border-color: rgba(74, 222, 128, 0.3); color: #4ade80;">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </a>
        <a href="{{ route('admin.attendance.preview', request()->query()) }}" class="btn btn-outline" style="border-color: rgba(207,164,111,0.3); color: var(--gold);">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
    </div>
</div>

<!-- Tabs: Class Summaries vs Individual Student Records -->
<div style="display:flex; gap:10px; margin-bottom:20px; border-bottom:1px solid rgba(207,164,111,0.2); padding-bottom:12px;">
    <a href="{{ route('admin.attendance', array_merge(request()->except('page'), ['tab' => 'summary'])) }}" 
       style="padding:8px 18px; border-radius:10px; font-weight:700; font-size:0.9rem; text-decoration:none; transition:all 0.2s; {{ $tab === 'summary' ? 'background:#cfa46f; color:#110a0a;' : 'background:rgba(255,255,255,0.05); color:#f3e7cd; border:1px solid rgba(255,255,255,0.1);' }}">
        <i class="bi bi-grid-3x3-gap-fill me-1"></i> Class Summaries
    </a>
    <a href="{{ route('admin.attendance', array_merge(request()->except('page'), ['tab' => 'records'])) }}" 
       style="padding:8px 18px; border-radius:10px; font-weight:700; font-size:0.9rem; text-decoration:none; transition:all 0.2s; {{ $tab === 'records' ? 'background:#cfa46f; color:#110a0a;' : 'background:rgba(255,255,255,0.05); color:#f3e7cd; border:1px solid rgba(255,255,255,0.1);' }}">
        <i class="bi bi-person-lines-fill me-1"></i> Individual Student Records
    </a>
</div>

<x-card title="{{ $tab === 'records' ? 'Student Attendance Logs' : 'Class & Subject Summaries' }}" icon="bi bi-clipboard-check">
    <x-slot name="headerActions">
        <form method="GET" action="{{ route('admin.attendance') }}" class="d-flex gap-3 align-items-center flex-wrap m-0">
            <input type="hidden" name="tab" value="{{ $tab }}">
            
            <div class="saas-search" style="width:200px;">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="saas-search-input" placeholder="Search student..." value="{{ request('search') ?? request('student_name') }}">
            </div>
            
            <input type="date" name="date" class="form-control" value="{{ request('date') }}" style="width:140px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;" title="Filter by specific date">
            
            @if($tab === 'records')
            <select name="status" class="form-select" style="width:130px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;">
                <option value="">Status (All)</option>
                <option value="Present" {{ request('status')=='Present'?'selected':'' }}>Present</option>
                <option value="Late"    {{ request('status')=='Late'?'selected':'' }}>Late</option>
                <option value="Absent"  {{ request('status')=='Absent'?'selected':'' }}>Absent</option>
                <option value="Excused" {{ request('status')=='Excused'?'selected':'' }}>Excused</option>
            </select>
            @endif
            
            <select name="subject" class="form-select" style="width:150px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;">
                <option value="">Subject (All)</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->code }}" {{ request('subject')==$s->code?'selected':'' }}>{{ $s->code }}</option>
                @endforeach
            </select>
            
            <button type="submit" class="btn btn-primary" style="background: var(--gold); border: none; color:#110a0a; font-weight:700;">
                <i class="bi bi-funnel"></i> Filter
            </button>
            
            @if(request()->hasAny(['date','status','year_level','semester','course','subject','search','student_name']))
            <a href="{{ route('admin.attendance', ['tab' => $tab]) }}" class="btn btn-outline" style="color: #f87171; border-color: rgba(239,68,68,0.3);">
                Clear
            </a>
            @endif
        </form>
    </x-slot>

    @if($tab === 'records')
    <!-- INDIVIDUAL RECORDS TABLE -->
    <x-data-table :headers="['Date & Time', 'Student', 'Student Number', 'Subject', 'Method', 'Status', 'Actions']">
        @forelse($records as $rec)
        <tr>
            <td data-label="Date & Time">
                <div style="font-weight:700; color:#f3e7cd;">{{ $rec->date ? (is_string($rec->date) ? \Carbon\Carbon::parse($rec->date)->format('M d, Y') : $rec->date->format('M d, Y')) : 'N/A' }}</div>
                <div style="font-size:0.75rem; color:#cfa46f;"><i class="bi bi-clock me-1"></i>{{ $rec->time_in ?? 'No punch time' }}</div>
            </td>
            <td data-label="Student">
                <div style="font-weight:700; color:#f3e7cd;">{{ $rec->user->name ?? 'Unknown Student' }}</div>
                <div style="font-size:0.75rem; color:#b39b82;">{{ $rec->user->course ?? 'Course N/A' }} {{ $rec->user->section ? '• Sec ' . $rec->user->section : '' }}</div>
            </td>
            <td data-label="Student Number">
                <span class="saas-badge saas-badge-default" style="font-family:monospace;">{{ $rec->user->student_number ?? 'N/A' }}</span>
            </td>
            <td data-label="Subject">
                <span style="font-weight:700; color:#60a5fa;">{{ $rec->subject_code }}</span>
                <div style="font-size:0.75rem; color:#b39b82;">{{ $rec->subject->name ?? '' }}</div>
            </td>
            <td data-label="Method">
                <span class="saas-badge saas-badge-default" style="font-size:0.75rem;">
                    <i class="bi bi-{{ ($rec->method === 'code') ? 'keyboard' : 'qr-code-scan' }} me-1"></i>
                    {{ strtoupper($rec->method ?? 'QR') }}
                </span>
            </td>
            <td data-label="Status">
                @if($rec->excused)
                    <span class="saas-badge" style="background:rgba(59,130,246,0.15); color:#60a5fa; border:1px solid rgba(59,130,246,0.3);">
                        <i class="bi bi-info-circle-fill me-1"></i> Excused
                    </span>
                @elseif($rec->status === 'Present')
                    <span class="saas-badge saas-badge-success">
                        <i class="bi bi-check-circle-fill me-1"></i> Present
                    </span>
                @elseif($rec->status === 'Late')
                    <span class="saas-badge saas-badge-warning">
                        <i class="bi bi-clock-history me-1"></i> Late
                    </span>
                @else
                    <span class="saas-badge" style="background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3);">
                        <i class="bi bi-x-circle-fill me-1"></i> Absent
                    </span>
                @endif
                @if($rec->excuse_note)
                    <div style="font-size:0.7rem; color:#b39b82; margin-top:3px; max-width:160px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;" title="{{ $rec->excuse_note }}">
                        <i class="bi bi-chat-left-text me-1"></i>{{ $rec->excuse_note }}
                    </div>
                @endif
            </td>
            <td data-label="Actions" style="text-align:right;">
                <button type="button" class="saas-btn saas-btn-secondary" style="padding:4px 10px; font-size:0.75rem;" 
                        onclick="openOverrideModal({{ $rec->id }}, '{{ addslashes($rec->user->name ?? 'Student') }}', '{{ $rec->status }}', {{ $rec->excused ? 'true' : 'false' }})">
                    <i class="bi bi-pencil-square me-1"></i> Override
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="p-0 border-0">
                <x-empty-state 
                    icon="calendar-x"
                    title="No Student Records Found"
                    message="There are no student attendance records matching your search or filters."
                />
            </td>
        </tr>
        @endforelse
    </x-data-table>

    @if($records->hasPages())
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <div style="color: #b39b82; font-size: 0.85rem;">
            Showing {{ $records->firstItem() ?? 0 }} to {{ $records->lastItem() ?? 0 }} of {{ $records->total() }} records
        </div>
        <div>
            {{ $records->links('pagination::bootstrap-4') }}
        </div>
    </div>
    @endif

    @else
    <!-- CLASS SUMMARIES TABLE -->
    <x-data-table :headers="['Subject', 'Total Logs', 'Present', 'Late', 'Absent', 'Excused', 'Attendance Rate']">
        @php $currentDate = null; @endphp
        @forelse($logs as $log)
        @php
            $logDateStr = $log->date instanceof \DateTimeInterface ? $log->date->format('Y-m-d') : (string)$log->date;
            $logDateFormatted = $log->date instanceof \DateTimeInterface ? $log->date->format('F j, Y - l') : \Carbon\Carbon::parse($log->date)->format('F j, Y - l');
        @endphp
        @if($currentDate !== $logDateStr)
            @php $currentDate = $logDateStr; @endphp
            <tr style="background:rgba(255,255,255,0.05);">
                <td colspan="7" style="font-weight:600; font-size:0.95rem; padding:12px 20px; color: var(--gold);">
                    <i class="bi bi-calendar-event me-2"></i> 
                    {{ $logDateFormatted }}
                </td>
            </tr>
        @endif
        <tr>
            <td data-label="Subject">
                <span style="font-family:monospace; font-size:0.9rem; color: #60a5fa; font-weight: 600;">{{ $log->subject_code }}</span>
                <div style="font-size:0.75rem; margin-top:4px; color: #b39b82;">{{ $log->subject->name ?? 'Unknown Subject' }}</div>
            </td>
            <td data-label="Total Logs">
                <div style="font-size:1.1rem; font-weight:600; color: #f3e7cd;">{{ $log->total }}</div>
                <div style="font-size:0.7rem; color: #b39b82;">Students</div>
            </td>
            <td data-label="Present">
                <div style="font-size:1.1rem; font-weight:600; color: #4ade80;">{{ $log->present_count }}</div>
            </td>
            <td data-label="Late">
                <div style="font-size:1.1rem; font-weight:600; color: #fbbf24;">{{ $log->late_count }}</div>
            </td>
            <td data-label="Absent">
                <div style="font-size:1.1rem; font-weight:600; color: #f87171;">{{ $log->absent_count }}</div>
            </td>
            <td data-label="Excused">
                <div style="font-size:1.1rem; font-weight:600; color: #b39b82;">{{ $log->excused_count }}</div>
            </td>
            <td data-label="Attendance Rate">
                @php $rate = $log->total > 0 ? round((($log->present_count + $log->late_count) / $log->total) * 100) : 0; @endphp
                <div class="d-flex align-items-center gap-2">
                    <div style="flex:1; height:6px; background: rgba(255,255,255,0.05); border-radius:99px; overflow:hidden;">
                        <div style="height:100%; width:{{ $rate }}%; background:{{ $rate >= 80 ? '#4ade80' : ($rate >= 60 ? '#fbbf24' : '#f87171') }};"></div>
                    </div>
                    <div style="font-weight:600; font-size:0.85rem; width:40px; text-align:right; color: #f3e7cd;">{{ $rate }}%</div>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="p-0 border-0">
                <x-empty-state 
                    icon="calendar-x"
                    title="No Attendance Records Found"
                    message="There are no attendance records matching your search or filters."
                />
            </td>
        </tr>
        @endforelse
    </x-data-table>
    
    @if($logs->hasPages())
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <div style="color: #b39b82; font-size: 0.85rem;">
            Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} results
        </div>
        <div>
            {{ $logs->links('pagination::bootstrap-4') }}
        </div>
    </div>
    @endif
    @endif
</x-card>

<!-- Admin Attendance Override Modal -->
<div class="saas-modal-backdrop" id="overrideModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div class="saas-card" style="width:100%; max-width:480px; margin:20px; box-shadow:0 24px 60px rgba(0,0,0,0.6);">
        <div class="saas-card-header" style="justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700; color:#f3e7cd;">
                <i class="bi bi-shield-check text-warning me-1"></i> Admin Override Attendance
            </h3>
            <button type="button" class="saas-btn saas-btn-secondary" style="padding:4px 8px;" onclick="closeOverrideModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="overrideForm" method="POST">
            @csrf
            <div style="padding:20px;">
                <p style="font-size:0.9rem; color:#f3e7cd; margin-bottom:16px;">
                    Adjusting attendance for: <strong id="overrideStudentName" style="color:var(--gold);"></strong>
                </p>

                <div style="margin-bottom:16px;">
                    <label class="saas-label" style="font-weight:600; font-size:0.85rem; color:#f3e7cd; margin-bottom:6px; display:block;">New Status</label>
                    <select name="status" id="overrideStatusSelect" class="saas-input saas-select" required style="width:100%;">
                        <option value="Present">Present</option>
                        <option value="Late">Late</option>
                        <option value="Absent">Absent (Unexcused)</option>
                        <option value="Excused">Excused (Medical/Approved)</option>
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="saas-label" style="font-weight:600; font-size:0.85rem; color:#f3e7cd; margin-bottom:6px; display:block;">Reason for Override (Audit Trail)</label>
                    <textarea name="reason" class="saas-input" rows="3" required placeholder="State reason (e.g. excused medical note presented, scanner malfunction, manual verification)" style="width:100%;"></textarea>
                    <small class="saas-text-muted">This justification is permanently recorded in the system audit trail.</small>
                </div>
            </div>
            <div style="padding:16px 20px; background:rgba(0,0,0,0.2); border-top:1px solid rgba(207,164,111,0.15); display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="saas-btn saas-btn-secondary" onclick="closeOverrideModal()">Cancel</button>
                <button type="submit" class="saas-btn saas-btn-primary">Apply Override</button>
            </div>
        </form>
    </div>
</div>

<script>
function openOverrideModal(id, studentName, status, isExcused) {
    const form = document.getElementById('overrideForm');
    form.action = `/admin/attendance/${id}/override`;
    document.getElementById('overrideStudentName').textContent = studentName;
    const select = document.getElementById('overrideStatusSelect');
    if (isExcused) {
        select.value = 'Excused';
    } else {
        select.value = status;
    }
    const modal = document.getElementById('overrideModal');
    if (modal) modal.style.display = 'flex';
}

function closeOverrideModal() {
    const modal = document.getElementById('overrideModal');
    if (modal) modal.style.display = 'none';
}
</script>
@endsection
