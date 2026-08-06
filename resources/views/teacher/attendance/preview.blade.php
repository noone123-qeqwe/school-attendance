@extends('layouts.app')
@section('page-title', 'Attendance PDF Preview')

@section('content')
<a href="{{ route('teacher.attendance', request()->query()) }}" style="display:inline-flex;align-items:center;gap:7px;font-size:.85rem;font-weight:600;color:#64748b;text-decoration:none;padding:8px 14px;border:1.5px solid #e2e8f0;border-radius:9px;background:white;margin-bottom:20px;" onmouseover="this.style.color='#059669';this.style.borderColor='#059669';" onmouseout="this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
    <i class="bi bi-arrow-left"></i> Back to Attendance
</a>

<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-file-earmark-pdf-fill"></i></div>
            PDF Preview: Attendance Records
            @if(array_filter($filters))
            <span style="background:#f0fdf4;color:#059669;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;border:1px solid #bbf7d0;">
                Filtered Results
            </span>
            @endif
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('teacher.attendance.pdf', request()->query()) }}" class="tch-btn tch-btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="bi bi-download"></i> Download PDF
            </a>
        </div>
    </div>

    <!-- Applied Filters -->
    @if(array_filter($filters))
    <div style="padding:14px 22px;border-bottom:1px solid #f8fafc;background:#f8fafc;">
        <div style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Applied Filters:</div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @if($filters['date'] ?? false)
            <span style="background:#eff6ff;color:#2563eb;padding:4px 10px;border-radius:99px;font-size:.72rem;font-weight:600;border:1px solid #bfdbfe;">
                Date: {{ \Carbon\Carbon::parse($filters['date'])->format('M d, Y') }}
            </span>
            @endif
            @if($filters['status'] ?? false)
            <span style="background:#f0fdf4;color:#16a34a;padding:4px 10px;border-radius:99px;font-size:.72rem;font-weight:600;border:1px solid #bbf7d0;">
                Status: {{ $filters['status'] }}
            </span>
            @endif
            @if($filters['subject'] ?? false)
            <span style="background:#fef7ff;color:#a855f7;padding:4px 10px;border-radius:99px;font-size:.72rem;font-weight:600;border:1px solid #e9d5ff;">
                Subject: {{ $filters['subject'] }}
            </span>
            @endif
            @if($filters['student_name'] ?? false)
            <span style="background:#fffbeb;color:#d97706;padding:4px 10px;border-radius:99px;font-size:.72rem;font-weight:600;border:1px solid #fed7aa;">
                Student: {{ $filters['student_name'] }}
            </span>
            @endif
        </div>
    </div>
    @endif

    <!-- PDF Content Preview -->
    <div style="padding:24px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
        <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:24px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
            
            <!-- PDF Header -->
            <div style="text-align:center;margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid #e2e8f0;">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width:68px;height:68px;border-radius:50%;border:3px solid #800000;margin:0 auto 12px auto;display:block;">
                <h1 style="font-size:1.5rem;font-weight:800;color:#1e293b;margin:0;">{{ config('app.name') }}</h1>
                <p style="font-size:.9rem;color:#64748b;margin:4px 0 0 0;">{{ config('app.subtitle', 'QR, GPS, and Biometric-Based Attendance Monitoring') }}</p>
                <h2 style="font-size:1.2rem;font-weight:700;color:#059669;margin:16px 0 0 0;">Attendance Records Report</h2>
                <p style="font-size:.8rem;color:#94a3b8;margin:4px 0 0 0;">Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
                <p style="font-size:.8rem;color:#94a3b8;margin:4px 0 0 0;">Teacher: {{ $teacher->name }}</p>
            </div>

            <!-- Summary -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-bottom:20px;">
                <div style="font-size:.85rem;font-weight:600;color:#1e293b;margin-bottom:8px;">Report Summary</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;">
                    <div>
                        <div style="font-size:.72rem;color:#64748b;">Total Records</div>
                        <div style="font-size:1.1rem;font-weight:700;color:#059669;">{{ $attendanceRecords->count() }}</div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:#64748b;">Present</div>
                        <div style="font-size:1.1rem;font-weight:700;color:#16a34a;">{{ $attendanceRecords->where('status', 'Present')->where('excused', false)->count() }}</div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:#64748b;">Late</div>
                        <div style="font-size:1.1rem;font-weight:700;color:#d97706;">{{ $attendanceRecords->where('status', 'Late')->where('excused', false)->count() }}</div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:#64748b;">Absent</div>
                        <div style="font-size:1.1rem;font-weight:700;color:#dc2626;">{{ $attendanceRecords->where('status', 'Absent')->where('excused', false)->count() }}</div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:#64748b;">Excused</div>
                        <div style="font-size:1.1rem;font-weight:700;color:#059669;">{{ $attendanceRecords->where('excused', true)->count() }}</div>
                    </div>
                </div>
            </div>

            <!-- Attendance Table Preview -->
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.8rem;">
                    <thead>
                        <tr style="background:#f1f5f9;border-bottom:2px solid #e2e8f0;">
                            <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">#</th>
                            <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">Student</th>
                            <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">Subject</th>
                            <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">Date</th>
                            <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">Status</th>
                            <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">Time In</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceRecords->take(10) as $i => $record)
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:8px;color:#6b7280;">{{ $i + 1 }}</td>
                            <td style="padding:8px;font-weight:600;color:#1f2937;">
                                {{ $record->user->name ?? 'â€”' }}
                                @if($record->user->student_number)
                                <br><span style="font-size:.7rem;color:#6b7280;">{{ $record->user->student_number }}</span>
                                @endif
                            </td>
                            <td style="padding:8px;color:#374151;">{{ $record->subject->name ?? $record->subject_code }}</td>
                            <td style="padding:8px;color:#374151;">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</td>
                            <td style="padding:8px;">
                                @if($record->excused)
                                <span style="background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:99px;font-size:.7rem;font-weight:600;">Excused</span>
                                @elseif($record->status === 'Present')
                                <span style="background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:99px;font-size:.7rem;font-weight:600;">Present</span>
                                @elseif($record->status === 'Late')
                                <span style="background:#fffbeb;color:#d97706;padding:2px 8px;border-radius:99px;font-size:.7rem;font-weight:600;">Late</span>
                                @else
                                <span style="background:#fef2f2;color:#dc2626;padding:2px 8px;border-radius:99px;font-size:.7rem;font-weight:600;">Absent</span>
                                @endif
                            </td>
                            <td style="padding:8px;color:#6b7280;">{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : 'â€”' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="padding:20px;text-align:center;color:#9ca3af;">No attendance records found with current filters</td>
                        </tr>
                        @endforelse
                        
                        @if($attendanceRecords->count() > 10)
                        <tr>
                            <td colspan="6" style="padding:12px;text-align:center;background:#f9fafb;color:#6b7280;font-style:italic;">
                                ... and {{ $attendanceRecords->count() - 10 }} more records (showing first 10 in preview)
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- PDF Footer Preview -->
            <div style="margin-top:24px;padding-top:16px;border-top:1px solid #e2e8f0;text-align:center;">
                <p style="font-size:.72rem;color:#9ca3af;margin:0;">
                    This document contains {{ $attendanceRecords->count() }} attendance record{{ $attendanceRecords->count() !== 1 ? 's' : '' }} â€¢ 
                    Generated by {{ $teacher->name }} â€¢ 
                    {{ now()->format('Y-m-d H:i:s') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="padding:16px 24px;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;">
        <div style="font-size:.8rem;color:#64748b;">
            Preview shows the first 10 records. The complete PDF will contain all {{ $attendanceRecords->count() }} attendance records.
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('teacher.attendance', request()->query()) }}" class="tch-btn tch-btn-ghost">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
            <a href="{{ route('teacher.attendance.pdf', request()->query()) }}" class="tch-btn tch-btn-primary">
                <i class="bi bi-download me-1"></i> Download PDF
            </a>
        </div>
    </div>
</div>
@endsection
