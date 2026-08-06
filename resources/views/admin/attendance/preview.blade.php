@extends('layouts.app')
@section('page-title', 'Attendance PDF Preview')

@section('content')
<a href="{{ route('admin.attendance', request()->query()) }}" style="display:inline-flex;align-items:center;gap:7px;font-size:.85rem;font-weight:600;color:#64748b;text-decoration:none;padding:8px 14px;border:1.5px solid #e2e8f0;border-radius:9px;background:white;margin-bottom:20px;" onmouseover="this.style.color='#800000';this.style.borderColor='#800000';" onmouseout="this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
    <i class="bi bi-arrow-left"></i> Back to Attendance Logs
</a>

<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-file-earmark-pdf-fill"></i></div>
            PDF Preview: Attendance Logs
            @if(array_filter($filters))
            <span style="background:#fff0f3;color:#6b0020;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;border:1px solid #f5c6cf;">
                Filtered Results
            </span>
            @endif
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('admin.attendance.pdf', request()->query()) }}" class="adm-btn adm-btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
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
            @if($filters['year_level'] ?? false)
            <span style="background:#fef7ff;color:#a855f7;padding:4px 10px;border-radius:99px;font-size:.72rem;font-weight:600;border:1px solid #e9d5ff;">
                Year: {{ $filters['year_level'] }}
            </span>
            @endif
            @if($filters['semester'] ?? false)
            <span style="background:#fffbeb;color:#d97706;padding:4px 10px;border-radius:99px;font-size:.72rem;font-weight:600;border:1px solid #fed7aa;">
                Semester: {{ $filters['semester'] }}{{ (int)$filters['semester'] === 1 ? 'st' : 'nd' }}
            </span>
            @endif
            @if($filters['subject'] ?? false)
            <span style="background:#fef2f2;color:#dc2626;padding:4px 10px;border-radius:99px;font-size:.72rem;font-weight:600;border:1px solid #fecaca;">
                Subject: {{ $filters['subject'] }}
            </span>
            @endif
            @if($filters['student_name'] ?? false)
            <span style="background:#f3f4f6;color:#374151;padding:4px 10px;border-radius:99px;font-size:.72rem;font-weight:600;border:1px solid #d1d5db;">
                Student: {{ $filters['student_name'] }}
            </span>
            @endif
        </div>
    </div>
    @endif

    <!-- PDF Content Preview with Enhanced Styling -->
    <div style="padding:24px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
        <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
            
            <!-- Enhanced PDF Header -->
            <div style="background:white;padding:40px 32px 36px 32px;text-align:center;border-radius:16px;border:1px solid #e5e7eb;position:relative;">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }} logo" style="width:72px;height:72px;object-fit:contain;margin:0 auto 18px;">
                <h1 style="font-size:2rem;font-weight:800;margin:0 0 8px 0;color:#111827;">{{ config('app.name') }}</h1>
                <p style="font-size:1rem;color:#4b5563;margin:0 0 18px 0;">{{ config('app.subtitle', 'QR, GPS, and Biometric-Based Attendance Monitoring') }}</p>
                <h2 style="font-size:1.4rem;font-weight:700;margin:0 0 6px 0;color:#991b1b;">Attendance Logs Report</h2>
                <p style="font-size:.95rem;color:#6b7280;margin:8px 0 0 0;">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</p>
            </div>

            <div style="padding:30px;">
                <!-- Applied Filters Section -->
                @if(array_filter($filters))
                <div style="background:linear-gradient(135deg, #fef7f7 0%, #fdf2f4 100%);border:1px solid #f5c6cf;border-left:4px solid #800000;padding:20px;border-radius:12px;margin-bottom:25px;">
                    <div style="font-weight:700;color:#800000;font-size:0.85rem;margin-bottom:12px;text-transform:uppercase;letter-spacing:0.5px;">Applied Filters</div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        @if($filters['date'] ?? false)
                        <span style="background:white;border:1px solid #e5e7eb;padding:6px 12px;border-radius:20px;font-size:0.75rem;color:#374151;font-weight:500;">
                            <strong>Date:</strong> {{ \Carbon\Carbon::parse($filters['date'])->format('M d, Y') }}
                        </span>
                        @endif
                        @if($filters['status'] ?? false)
                        <span style="background:white;border:1px solid #e5e7eb;padding:6px 12px;border-radius:20px;font-size:0.75rem;color:#374151;font-weight:500;">
                            <strong>Status:</strong> {{ $filters['status'] }}
                        </span>
                        @endif
                        @if($filters['year_level'] ?? false)
                        <span style="background:white;border:1px solid #e5e7eb;padding:6px 12px;border-radius:20px;font-size:0.75rem;color:#374151;font-weight:500;">
                            <strong>Year:</strong> {{ $filters['year_level'] }}
                        </span>
                        @endif
                        @if($filters['semester'] ?? false)
                        <span style="background:white;border:1px solid #e5e7eb;padding:6px 12px;border-radius:20px;font-size:0.75rem;color:#374151;font-weight:500;">
                            <strong>Semester:</strong> {{ $filters['semester'] }}{{ (int)$filters['semester'] === 1 ? 'st' : 'nd' }}
                        </span>
                        @endif
                        @if($filters['subject'] ?? false)
                        <span style="background:white;border:1px solid #e5e7eb;padding:6px 12px;border-radius:20px;font-size:0.75rem;color:#374151;font-weight:500;">
                            <strong>Subject:</strong> {{ $filters['subject'] }}
                        </span>
                        @endif
                        @if($filters['student_name'] ?? false)
                        <span style="background:white;border:1px solid #e5e7eb;padding:6px 12px;border-radius:20px;font-size:0.75rem;color:#374151;font-weight:500;">
                            <strong>Student:</strong> {{ $filters['student_name'] }}
                        </span>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Enhanced Statistics Grid -->
                @php
                    $totalRecords = $logs->count();
                    $presentCount = $logs->where('status', 'Present')->where('excused', false)->count();
                    $lateCount = $logs->where('status', 'Late')->where('excused', false)->count();
                    $absentCount = $logs->where('status', 'Absent')->where('excused', false)->count();
                    $excusedCount = $logs->where('excused', true)->count();
                @endphp
                
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:20px;margin-bottom:30px;">
                    <div style="text-align:center;background:white;border:1px solid #e5e7eb;border-radius:12px;padding:20px 15px;border-top:3px solid #800000;">
                        <div style="font-size:1.8rem;font-weight:800;color:#800000;margin-bottom:6px;">{{ $totalRecords }}</div>
                        <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">Total Records</div>
                    </div>
                    <div style="text-align:center;background:white;border:1px solid #e5e7eb;border-radius:12px;padding:20px 15px;border-top:3px solid #16a34a;">
                        <div style="font-size:1.8rem;font-weight:800;color:#16a34a;margin-bottom:6px;">{{ $presentCount }}</div>
                        <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">Present</div>
                    </div>
                    <div style="text-align:center;background:white;border:1px solid #e5e7eb;border-radius:12px;padding:20px 15px;border-top:3px solid #d97706;">
                        <div style="font-size:1.8rem;font-weight:800;color:#d97706;margin-bottom:6px;">{{ $lateCount }}</div>
                        <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">Late</div>
                    </div>
                    <div style="text-align:center;background:white;border:1px solid #e5e7eb;border-radius:12px;padding:20px 15px;border-top:3px solid #dc2626;">
                        <div style="font-size:1.8rem;font-weight:800;color:#dc2626;margin-bottom:6px;">{{ $absentCount }}</div>
                        <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">Absent</div>
                    </div>
                    <div style="text-align:center;background:white;border:1px solid #e5e7eb;border-radius:12px;padding:20px 15px;border-top:3px solid #0c5460;">
                        <div style="font-size:1.8rem;font-weight:800;color:#0c5460;margin-bottom:6px;">{{ $excusedCount }}</div>
                        <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">Excused</div>
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
                            <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">Excused</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs->take(10) as $i => $log)
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:8px;color:#6b7280;">{{ $i + 1 }}</td>
                            <td style="padding:8px;font-weight:600;color:#1f2937;">
                                {{ $log->user->name ?? 'â€”' }}
                                @if($log->user->student_number)
                                <br><span style="font-size:.7rem;color:#6b7280;">{{ $log->user->student_number }}</span>
                                @endif
                            </td>
                            <td style="padding:8px;color:#374151;">{{ $log->subject->name ?? $log->subject_code }}</td>
                            <td style="padding:8px;color:#374151;">{{ \Carbon\Carbon::parse($log->date)->format('M d, Y') }}</td>
                            <td style="padding:8px;">
                                @if($log->excused)
                                <span style="background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:99px;font-size:.7rem;font-weight:600;">Excused</span>
                                @elseif($log->status === 'Present')
                                <span style="background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:99px;font-size:.7rem;font-weight:600;">Present</span>
                                @elseif($log->status === 'Late')
                                <span style="background:#fffbeb;color:#d97706;padding:2px 8px;border-radius:99px;font-size:.7rem;font-weight:600;">Late</span>
                                @else
                                <span style="background:#fef2f2;color:#dc2626;padding:2px 8px;border-radius:99px;font-size:.7rem;font-weight:600;">Absent</span>
                                @endif
                            </td>
                            <td style="padding:8px;color:#6b7280;">{{ $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('h:i A') : 'â€”' }}</td>
                            <td style="padding:8px;">
                                @if($log->excused)
                                <span style="background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:99px;font-size:.7rem;font-weight:600;">âœ“ Yes</span>
                                @else
                                <span style="color:#9ca3af;">â€”</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="padding:20px;text-align:center;color:#9ca3af;">No attendance records found with current filters</td>
                        </tr>
                        @endforelse
                        
                        @if($logs->count() > 10)
                        <tr>
                            <td colspan="7" style="padding:12px;text-align:center;background:#f9fafb;color:#6b7280;font-style:italic;">
                                ... and {{ $logs->count() - 10 }} more records (showing first 10 in preview)
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- PDF Footer Preview -->
            <div style="margin-top:24px;padding-top:16px;border-top:1px solid #e2e8f0;text-align:center;">
                <p style="font-size:.72rem;color:#9ca3af;margin:0;">
                    This document contains {{ $logs->count() }} attendance record{{ $logs->count() !== 1 ? 's' : '' }} â€¢ 
                    Generated by {{ config('app.name') }} â€¢ 
                    {{ now()->format('Y-m-d H:i:s') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="padding:16px 24px;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;">
        <div style="font-size:.8rem;color:#64748b;">
            Preview shows the first 10 records. The complete PDF will contain all {{ $logs->count() }} attendance records.
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('admin.attendance', request()->query()) }}" class="adm-btn adm-btn-ghost">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
            <a href="{{ route('admin.attendance.pdf', request()->query()) }}" class="adm-btn adm-btn-primary">
                <i class="bi bi-download me-1"></i> Download PDF
            </a>
        </div>
    </div>
</div>
@endsection
