@extends('layouts.admin_premium')

@section('title', 'Attendance Logs')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Attendance Logs</h1>
        <p class="saas-text-muted" style="margin:0;">Monitor and filter all student attendance records.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <a href="{{ route('admin.attendance.export', request()->all()) }}" class="saas-btn saas-btn-success" style="background:rgba(74, 222, 128, 0.15); color:var(--saas-success); border-color:var(--saas-success);">
            <i class="bi bi-file-earmark-spreadsheet"></i> Excel
        </a>
        <a href="{{ route('admin.attendance.preview', request()->query()) }}" class="saas-btn saas-btn-secondary">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
    </div>
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <form method="GET" action="{{ route('admin.attendance') }}" class="saas-card-header" style="gap:16px; flex-wrap:wrap; display:flex;">
        <div class="saas-search" style="width:220px;">
            <i class="bi bi-search"></i>
            <input type="text" name="student_name" class="saas-search-input" placeholder="Search student..." value="{{ request('student_name') }}">
        </div>
        
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <input type="date" name="date" class="saas-input" value="{{ request('date') }}" style="width:140px; padding:6px 12px;">
            
            <select name="status" class="saas-input saas-select" style="width:120px; padding:6px 30px 6px 12px;">
                <option value="">Status (All)</option>
                <option value="Present" {{ request('status')=='Present'?'selected':'' }}>Present</option>
                <option value="Late"    {{ request('status')=='Late'?'selected':'' }}>Late</option>
                <option value="Absent"  {{ request('status')=='Absent'?'selected':'' }}>Absent</option>
                <option value="Excused" {{ request('status')=='Excused'?'selected':'' }}>Excused</option>
            </select>
            
            <select name="course" class="saas-input saas-select" style="width:130px; padding:6px 30px 6px 12px;">
                <option value="">Course (All)</option>
                <option value="BSCS" {{ request('course')=='BSCS'?'selected':'' }}>BSCS</option>
                <option value="BSIT" {{ request('course')=='BSIT'?'selected':'' }}>BSIT</option>
                <option value="BSIS" {{ request('course')=='BSIS'?'selected':'' }}>BSIS</option>
            </select>

            <select name="subject" class="saas-input saas-select" style="width:150px; padding:6px 30px 6px 12px;">
                <option value="">Subject (All)</option>
                @foreach($subjects as $s)<option value="{{ $s->code }}" {{ request('subject')==$s->code?'selected':'' }}>{{ $s->code }}</option>@endforeach
            </select>
            
            <button type="submit" class="saas-btn saas-btn-secondary" style="padding:6px 12px;">
                <i class="bi bi-funnel"></i> Filter
            </button>
            
            @if(request()->hasAny(['date','status','year_level','semester','course','subject','student_name']))
            <a href="{{ route('admin.attendance') }}" class="saas-btn saas-btn-secondary" style="padding:6px 12px; color:var(--saas-danger);">
                Clear
            </a>
            @endif
        </div>
    </form>
    
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Total Logs</th>
                    <th>Present</th>
                    <th>Late</th>
                    <th>Absent</th>
                    <th>Excused</th>
                    <th style="width:150px;">Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $currentDate = null;
                @endphp
                @forelse($logs as $log)
                @if($currentDate !== $log->date->format('Y-m-d'))
                    @php
                        $currentDate = $log->date->format('Y-m-d');
                    @endphp
                    <tr style="background:rgba(255,255,255,0.02);">
                        <td colspan="7" style="font-weight:600; font-size:0.95rem; padding:12px 20px; border-bottom:2px solid var(--saas-border);">
                            <i class="bi bi-calendar-event saas-text-muted" style="margin-right:8px;"></i> 
                            {{ $log->date->format('F j, Y - l') }}
                        </td>
                    </tr>
                @endif
                <tr>
                    <td>
                        <span class="saas-badge saas-badge-info" style="font-family:monospace; font-size:0.9rem;">{{ $log->subject_code }}</span>
                        <div class="saas-text-muted" style="font-size:0.75rem; margin-top:4px;">{{ $log->subject->name ?? 'Unknown Subject' }}</div>
                    </td>
                    <td>
                        <div style="font-size:1.1rem; font-weight:600;">{{ $log->total }}</div>
                        <div class="saas-text-muted" style="font-size:0.7rem;">Students</div>
                    </td>
                    <td>
                        <div style="font-size:1.1rem; font-weight:600; color:var(--saas-success);">{{ $log->present_count }}</div>
                    </td>
                    <td>
                        <div style="font-size:1.1rem; font-weight:600; color:var(--saas-warning);">{{ $log->late_count }}</div>
                    </td>
                    <td>
                        <div style="font-size:1.1rem; font-weight:600; color:var(--saas-danger);">{{ $log->absent_count }}</div>
                    </td>
                    <td>
                        <div style="font-size:1.1rem; font-weight:600; color:var(--saas-text-muted);">{{ $log->excused_count }}</div>
                    </td>
                    <td>
                        @php $rate = $log->total > 0 ? round((($log->present_count + $log->late_count) / $log->total) * 100) : 0; @endphp
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="flex:1; height:6px; background:var(--saas-border); border-radius:99px; overflow:hidden;">
                                <div style="height:100%; width:{{ $rate }}%; background:{{ $rate >= 80 ? 'var(--saas-success)' : ($rate >= 60 ? 'var(--saas-warning)' : 'var(--saas-danger)') }};"></div>
                            </div>
                            <div style="font-weight:600; font-size:0.85rem; width:40px; text-align:right;">{{ $rate }}%</div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-calendar-x saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No records found</div>
                        <p class="saas-text-muted" style="margin-bottom:20px; max-width:400px; margin-inline:auto;">There are no attendance records matching your filters.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($logs->hasPages())
    <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:space-between; align-items:center;">
        <div class="saas-text-muted">
            Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} results
        </div>
        <div>
            {{ $logs->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
