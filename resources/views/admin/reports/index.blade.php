@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Reports & Analytics</h1>
        <p class="saas-text-muted" style="margin:0;">Generate and export attendance and system reports.</p>
    </div>
</div>

<div style="display:flex; gap:8px; margin-bottom:24px;">
    @foreach(['daily'=>'Daily Report', 'monthly'=>'Monthly Report', 'percentage'=>'Attendance %'] as $key=>$label)
    <a href="{{ route('admin.reports', array_merge(request()->query(), ['type'=>$key])) }}"
       class="saas-btn {{ $type===$key ? 'saas-btn-primary' : 'saas-btn-secondary' }}" 
       style="padding:8px 16px;">
        {{ $label }}
    </a>
    @endforeach
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <div class="saas-card-header" style="background:rgba(0,0,0,0.15);">
        <form method="GET" action="{{ route('admin.reports') }}" style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap; width:100%;">
            <input type="hidden" name="type" value="{{ $type }}">
            
            @if($type === 'daily')
            <div class="saas-form-group" style="margin:0;">
                <label class="saas-label" style="font-size:0.75rem;">Select Date</label>
                <input type="date" name="date" class="saas-input" value="{{ $date }}" style="width:200px;">
            </div>
            @elseif($type === 'monthly')
            <div class="saas-form-group" style="margin:0;">
                <label class="saas-label" style="font-size:0.75rem;">Select Month</label>
                <input type="month" name="month" class="saas-input" value="{{ $month }}" style="width:200px;">
            </div>
            @endif
            
            <button type="submit" class="saas-btn saas-btn-secondary">
                <i class="bi bi-arrow-clockwise"></i> Generate Report
            </button>
            <button type="button" class="saas-btn saas-btn-primary" style="margin-left:auto;">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </button>
        </form>
    </div>
    
    <div class="saas-card-body" style="background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--saas-border); display: flex; justify-content: space-between; align-items: center; padding: 12px 24px;">
        <div style="display: flex; gap: 24px; font-size: 0.85rem; color: var(--saas-text-muted);">
            <div>
                <strong><i class="bi bi-card-list"></i> Records:</strong> {{ $type === 'percentage' ? (isset($percentageData) ? count($percentageData) : 0) : (isset($logs) ? count($logs) : 0) }}
            </div>
            <div>
                <strong><i class="bi bi-funnel"></i> Filter:</strong> {{ $type === 'daily' ? 'Date (' . ($date ?? 'None') . ')' : ($type === 'monthly' ? 'Month (' . ($month ?? 'None') . ')' : 'All Time') }}
            </div>
            <div>
                <strong><i class="bi bi-file-earmark-text"></i> Type:</strong> {{ ucfirst($type) }}
            </div>
        </div>
        <div>
            <span class="saas-badge saas-badge-success" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2);">
                <i class="bi bi-check-circle"></i> Ready to Export
            </span>
        </div>
    </div>
    
    <div class="saas-table-container" style="border:none; border-radius:0;">
        @if($type === 'percentage')
        <table class="saas-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Attendance %</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($percentageData as $i => $row)
                <tr>
                    <td class="saas-text-muted">{{ $i + 1 }}</td>
                    <td style="font-weight:500;">{{ $row['student']->name }}</td>
                    <td><span class="saas-badge saas-badge-info">{{ $row['student']->course }}</span></td>
                    <td>Year {{ $row['student']->year_level }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-family:monospace; font-weight:600;">{{ $row['percentage'] }}%</span>
                            <div style="flex:1; height:6px; background:var(--saas-border); border-radius:3px; overflow:hidden; max-width:100px;">
                                <div style="height:100%; width:{{ $row['percentage'] }}%; background:{{ $row['percentage'] >= 80 ? 'var(--saas-success)' : ($row['percentage'] >= 60 ? 'var(--saas-warning)' : 'var(--saas-danger)') }};"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($row['percentage'] >= 80)
                            <span class="saas-badge saas-badge-success">Good</span>
                        @elseif($row['percentage'] >= 60)
                            <span class="saas-badge saas-badge-warning">Warning</span>
                        @else
                            <span class="saas-badge saas-badge-danger">Critical</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:48px 20px;">
                        <p class="saas-text-muted">No attendance data found to calculate percentages.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @else
        <table class="saas-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Year/Sem</th>
                    <th>Status</th>
                    <th>Time In</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $i => $log)
                <tr>
                    <td class="saas-text-muted">{{ $i + 1 }}</td>
                    <td style="font-weight:500;">{{ $log->user->name ?? 'N/A' }}</td>
                    <td><span class="saas-badge saas-badge-info">{{ $log->user->course ?? 'N/A' }}</span></td>
                    <td>Y{{ $log->user->year_level ?? '?' }} - S{{ $log->user->semester ?? '?' }}</td>
                    <td>
                        @if($log->status === 'Present')
                            <span class="saas-badge saas-badge-success">Present</span>
                        @elseif($log->status === 'Late')
                            <span class="saas-badge saas-badge-warning">Late</span>
                        @elseif($log->status === 'Absent')
                            <span class="saas-badge saas-badge-danger">Absent</span>
                        @else
                            <span class="saas-badge saas-badge-default">{{ $log->status }}</span>
                        @endif
                    </td>
                    <td style="font-family:monospace; color:var(--saas-text-muted);">
                        {{ $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('h:i A') : 'â€”' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-file-earmark-bar-graph saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No data available</div>
                        <p class="saas-text-muted" style="max-width:400px; margin-inline:auto;">There is no attendance data for the selected {{ $type==='daily'?'date':'month' }}.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
