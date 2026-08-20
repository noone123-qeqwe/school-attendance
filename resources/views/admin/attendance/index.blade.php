@extends('layouts.app')

@section('title', 'Attendance Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h1 class="saas-heading saas-heading-lg mb-1">Attendance Logs</h1>
        <p class="saas-text-muted m-0">Monitor and filter all student attendance records.</p>
    </div>
    
    <div class="d-flex gap-2">
        <a href="{{ route('admin.activity.log') }}" class="btn btn-outline" style="border-color: rgba(96, 165, 250, 0.3); color: #60a5fa;">
            <i class="bi bi-shield-lock"></i> Audit Log
        </a>
        <a href="{{ route('admin.attendance.export', request()->all()) }}" class="btn btn-outline" style="border-color: rgba(74, 222, 128, 0.3); color: #4ade80;">
            <i class="bi bi-file-earmark-spreadsheet"></i> Excel
        </a>
        <a href="{{ route('admin.attendance.preview', request()->query()) }}" class="btn btn-outline" style="border-color: rgba(207,164,111,0.3); color: var(--gold);">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
    </div>
</div>

<x-card title="Attendance Records" icon="bi bi-clipboard-check">
    <x-slot name="headerActions">
        <form method="GET" action="{{ route('admin.attendance') }}" class="d-flex gap-3 align-items-center flex-wrap m-0">
            <div class="saas-search" style="width:220px;">
                <i class="bi bi-search"></i>
                <input type="text" name="student_name" class="saas-search-input" placeholder="Search student..." value="{{ request('student_name') }}">
            </div>
            
            <input type="date" name="date" class="form-control" value="{{ request('date') }}" style="width:140px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;">
            
            <select name="status" class="form-select" style="width:120px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;">
                <option value="">Status (All)</option>
                <option value="Present" {{ request('status')=='Present'?'selected':'' }}>Present</option>
                <option value="Late"    {{ request('status')=='Late'?'selected':'' }}>Late</option>
                <option value="Absent"  {{ request('status')=='Absent'?'selected':'' }}>Absent</option>
                <option value="Excused" {{ request('status')=='Excused'?'selected':'' }}>Excused</option>
            </select>
            
            <select name="course" class="form-select" style="width:130px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;">
                <option value="">Course (All)</option>
                <option value="BSCS" {{ request('course')=='BSCS'?'selected':'' }}>BSCS</option>
                <option value="BSIT" {{ request('course')=='BSIT'?'selected':'' }}>BSIT</option>
                <option value="BSIS" {{ request('course')=='BSIS'?'selected':'' }}>BSIS</option>
            </select>

            <select name="subject" class="form-select" style="width:150px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;">
                <option value="">Subject (All)</option>
                @foreach($subjects as $s)<option value="{{ $s->code }}" {{ request('subject')==$s->code?'selected':'' }}>{{ $s->code }}</option>@endforeach
            </select>
            
            <button type="submit" class="btn btn-primary" style="background: var(--gold); border: none;">
                <i class="bi bi-funnel"></i> Filter
            </button>
            
            @if(request()->hasAny(['date','status','year_level','semester','course','subject','student_name']))
            <a href="{{ route('admin.attendance') }}" class="btn btn-outline" style="color: #f87171; border-color: rgba(239,68,68,0.3);">
                Clear
            </a>
            @endif
        </form>
    </x-slot>
    
    <x-data-table :headers="['Subject', 'Total Logs', 'Present', 'Late', 'Absent', 'Excused', 'Attendance Rate']">
        @php $currentDate = null; @endphp
        @forelse($logs as $log)
        @if($currentDate !== $log->date->format('Y-m-d'))
            @php $currentDate = $log->date->format('Y-m-d'); @endphp
            <tr style="background:rgba(255,255,255,0.05);">
                <td colspan="7" style="font-weight:600; font-size:0.95rem; padding:12px 20px; color: var(--gold);">
                    <i class="bi bi-calendar-event me-2"></i> 
                    {{ $log->date->format('F j, Y - l') }}
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
</x-card>
@endsection
