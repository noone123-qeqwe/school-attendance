@extends('layouts.app')
@section('page-title', 'Reports')

@section('content')
<style>
.tch-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 14px;
    table-layout: auto;
}
.tch-table thead th {
    padding: 18px 20px;
    text-align: left;
    font-size: .9rem;
    font-weight: 700;
    color: #f8e7d3;
    text-transform: uppercase;
    letter-spacing: .04em;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.tch-table tbody tr {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.tch-table tbody tr:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.14);
}
.tch-table tbody td {
    padding: 16px 18px;
    vertical-align: top;
    color: #d8c5a8;
    font-size: .85rem;
}
.tch-table tbody td div,
.tch-table tbody td span {
    display: block;
}
.badge-present {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(16,185,129,0.18);
    color: #22c55e;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
}
.badge-late {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(245,158,11,0.18);
    color: #f59e0b;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
}
.badge-absent {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(239,68,68,0.18);
    color: #f87171;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
}
.tch-table tbody td:last-child {
    padding-right: 20px;
}
.tch-table tbody td[data-label="Status"] {
    min-width: 110px;
}
.tch-card-head {
    gap: 12px;
    flex-wrap: wrap;
}
@media (max-width: 992px) {
    .tch-card-head {
        flex-direction: column;
        align-items: flex-start;
    }
}
@media (max-width: 768px) {
    .tch-table thead { display: none; }
    .tch-table tbody tr { display: block; border: 1px solid #f1f5f9; border-radius: 12px; margin-bottom: 16px; background: white; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .tch-table tbody td { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; padding: 14px 16px; border-bottom: 1px solid #f8fafc; font-size: .85rem; }
    .tch-table tbody td:last-child { border-bottom: none; }
    .tch-table tbody td::before { content: attr(data-label); font-size: .75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; margin-right: 10px; flex-shrink: 0; }
    .tch-table tbody tr:last-child td { border-bottom: none; }
}
</style>

<!-- Report type tabs -->
<div style="display:flex;gap:6px;margin-bottom:20px;">
    @foreach(['daily'=>'Daily Report','monthly'=>'Monthly Report','percentage'=>'Attendance %'] as $key=>$label)
    <a href="{{ route('teacher.reports', array_merge(request()->query(), ['type'=>$key])) }}"
       style="padding:9px 18px;border-radius:9px;font-size:.85rem;font-weight:600;text-decoration:none;transition:all .2s;
              {{ $type===$key ? 'background:#7c2d12;color:white;box-shadow:0 4px 12px rgba(124,45,18,.25);' : 'background:white;color:#64748b;border:1.5px solid #e2e8f0;' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

<!-- Filters -->
<div class="tch-card" style="margin-bottom:20px;">
    <div style="padding:16px 22px;">
        <form method="GET" action="{{ route('teacher.reports') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;">
            <input type="hidden" name="type" value="{{ $type }}">
            <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                @if($type === 'daily')
                <div>
                    <label style="font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:3px;">Date</label>
                    <input type="date" name="date" class="tch-input" value="{{ $date }}">
                </div>
                @elseif($type === 'monthly')
                <div>
                    <label style="font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:3px;">Month</label>
                    <input type="month" name="month" class="tch-input" value="{{ $month }}">
                </div>
                @endif
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                <button type="submit" class="tch-btn tch-btn-primary"><i class="bi bi-funnel me-1"></i>Generate</button>
                <button type="button" onclick="exportToExcel()" class="tch-btn" style="background:#059669;color:white;display:inline-flex;align-items:center;gap:6px;border:none;">
                    <i class="bi bi-file-earmark-excel-fill"></i> Export Excel
                </button>
                <button type="button" onclick="openPdfPreview()" class="tch-btn" style="background:#7c2d12;color:white;display:inline-flex;align-items:center;gap:6px;border:none;">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Export PDF
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background:#fff5f5;color:#7c2d12;"><i class="bi bi-bar-chart-fill"></i></div>
            @if($type==='daily') Daily Report â€” {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}
            @elseif($type==='monthly') Monthly Report â€” {{ \Carbon\Carbon::createFromFormat('Y-m',$month)->format('F Y') }}
            @else Attendance Percentage Report
            @endif
        </div>
        <span style="font-size:.78rem;color:#94a3b8;">{{ is_countable($data) ? count($data) : $data->count() }} records</span>
    </div>

    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        @if($type === 'percentage')
        <table class="tch-table">
            <thead><tr><th>#</th><th>Student</th><th>Course</th><th>Year</th><th>Total</th><th>Present</th><th>Absent</th><th>Rate</th></tr></thead>
            <tbody>
                @forelse($data as $i => $row)
                <tr>
                    <td data-label="#" style="color:#cbd5e1;font-size:.78rem;">{{ $i+1 }}</td>
                    <td data-label="Student">
                        <div style="font-weight:600;color:#f8e7d3;">{{ $row['student']->name }}</div>
                        <div style="font-size:.72rem;color:#d8c5a8;">{{ $row['student']->student_number }}</div>
                    </td>
                    <td data-label="Course"><span class="badge-course" style="color:#f8e7d3;">{{ $row['student']->course }}</span></td>
                    <td data-label="Year"><span class="badge-year">Year {{ $row['student']->year_level }}</span></td>
                    <td data-label="Total" style="font-weight:600;">{{ $row['total'] }}</td>
                    <td data-label="Present" style="color:#16a34a;font-weight:600;">{{ $row['present'] }}</td>
                    <td data-label="Absent" style="color:#dc2626;font-weight:600;">{{ $row['absent'] }}</td>
                    <td data-label="Rate">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;height:6px;background:#f1f5f9;border-radius:99px;overflow:hidden;min-width:60px;">
                                <div style="height:100%;width:{{ $row['rate'] }}%;background:{{ $row['rate']>=75?'#16a34a':'#dc2626' }};border-radius:99px;"></div>
                            </div>
                            <span style="font-size:.8rem;font-weight:700;color:{{ $row['rate']>=75?'#16a34a':'#dc2626' }};">{{ $row['rate'] }}%</span>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8"><div class="empty-state"><i class="bi bi-inbox"></i><p>No data.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
        @else
        <table class="tch-table">
            <thead><tr><th>#</th><th>Student</th><th>Subject</th><th>Date</th><th>Status</th><th>Time In</th></tr></thead>
            <tbody>
                @forelse($data as $i => $log)
                <tr>
                    <td data-label="#" style="color:#cbd5e1;font-size:.78rem;">{{ $i+1 }}</td>
                    <td data-label="Student">
                        <div style="font-weight:600;color:#f8e7d3;">{{ $log->user->name ?? 'â€”' }}</div>
                        <div style="font-size:.72rem;color:#d8c5a8;">{{ $log->user->student_number ?? '' }}</div>
                    </td>
                    <td data-label="Subject" style="font-weight:600;color:#f8e7d3;">{{ $log->subject->name ?? $log->subject_code }}</td>
                    <td data-label="Date" style="font-size:.85rem;color:#d8c5a8;">{{ \Carbon\Carbon::parse($log->date)->format('M d, Y') }}</td>
                    <td data-label="Status">
                        @if($log->status==='Present') <span class="badge-present">Present</span>
                        @elseif($log->status==='Late')  <span class="badge-late">Late</span>
                        @else <span class="badge-absent">Absent</span>
                        @endif
                    </td>
                    <td data-label="Time In" style="color:#64748b;">{{ $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('h:i A') : 'â€”' }}</td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="empty-state"><i class="bi bi-inbox"></i><p>No records for this period.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
        @endif
    </div>
</div>

<script>
function openPdfPreview() {
    const params = new URLSearchParams();
    params.append('type', '{{ $type }}');
    
    @if($type === 'daily')
        params.append('date', '{{ $date }}');
    @elseif($type === 'monthly')
        params.append('month', '{{ $month }}');
    @endif
    
    const url = '{{ route("teacher.reports.pdf") }}?' + params.toString();
    window.open(url, '_blank');
}

function exportToExcel() {
    const params = new URLSearchParams();
    params.append('type', '{{ $type }}');
    
    @if($type === 'daily')
        params.append('date', '{{ $date }}');
    @elseif($type === 'monthly')
        params.append('month', '{{ $month }}');
    @endif
    
    const url = '{{ route("teacher.reports.excel") }}?' + params.toString();
    window.location.href = url;
}
</script>
@endsection
