@extends('layouts.app')
@section('page-title', 'Teacher Performance Report')

@section('content')
<div style="max-width:1100px;margin:0 auto;">
    <div style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <div style="font-size:1.5rem;font-weight:800;color:#f3e7cd;">Teacher Performance</div>
            <div style="font-size:0.88rem;color:#b39b82;margin-top:2px;">Analytics on teacher activity and overall class attendance rates</div>
        </div>
        <a href="javascript:window.print()" style="padding:9px 18px;background:#cfa46f;color:#1e1b18;border-radius:10px;font-size:0.85rem;font-weight:700;text-decoration:none;">
            <i class="bi bi-file-earmark-pdf-fill me-2"></i>Export PDF
        </a>
    </div>

    <div style="background:rgba(255,235,190,0.04);border:1px solid rgba(255,215,145,0.1);border-radius:14px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,215,145,0.12);">
                    <th style="padding:12px 16px;text-align:left;font-size:0.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;">Instructor</th>
                    <th style="padding:12px 16px;text-align:left;font-size:0.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;">Subjects Assigned</th>
                    <th style="padding:12px 16px;text-align:left;font-size:0.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;">Classes Conducted</th>
                    <th style="padding:12px 16px;text-align:left;font-size:0.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;">Avg Student Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse($performanceData as $data)
                <tr style="border-bottom:1px solid rgba(255,215,145,0.06);" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background=''">
                    <td style="padding:12px 16px;font-size:0.875rem;color:#f3e7cd;font-weight:500;">
                        {{ $data->teacher->name }}
                        <div style="font-size:0.75rem;color:#b39b82;font-weight:400;">{{ $data->teacher->email }}</div>
                    </td>
                    <td style="padding:12px 16px;font-size:0.875rem;color:#d4c5a9;">{{ $data->subjects_count }}</td>
                    <td style="padding:12px 16px;font-size:0.875rem;color:#d4c5a9;">{{ $data->total_classes }}</td>
                    <td style="padding:12px 16px;">
                        @if($data->attendance_rate >= 90)
                            <span style="color:#4ade80;font-weight:700;font-size:0.9rem;">{{ $data->attendance_rate }}%</span>
                        @elseif($data->attendance_rate >= 75)
                            <span style="color:#facc15;font-weight:700;font-size:0.9rem;">{{ $data->attendance_rate }}%</span>
                        @else
                            <span style="color:#f87171;font-weight:700;font-size:0.9rem;">{{ $data->attendance_rate }}%</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="padding:32px;text-align:center;color:#b39b82;font-size:0.875rem;">No teacher data available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
