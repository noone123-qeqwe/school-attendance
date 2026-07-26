<!-- Reusable report content used by preview and PDF exports -->
@php
    $forPdf = $forPdf ?? false;
@endphp

<div style="padding:24px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;padding:24px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
        <!-- PDF Header -->
        <div style="text-align:center;margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid #e2e8f0;">
            <img src="{{ $forPdf ? ('file://' . public_path('images/logo.png')) : asset('images/logo.png') }}" alt="Logo" style="width:68px;height:68px;border-radius:50%;border:3px solid #800000;margin:0 auto 12px auto;display:block;">
            <h1 style="font-size:1.5rem;font-weight:800;color:#1e293b;margin:0;">{{ config('app.name') }}</h1>
            <p style="font-size:.9rem;color:#64748b;margin:4px 0 0 0;">{{ config('app.subtitle', 'QR, GPS, and Biometric-Based Attendance Monitoring') }}</p>
            <h2 style="font-size:1.2rem;font-weight:700;color:#059669;margin:16px 0 0 0;">My Students List Report</h2>
            <p style="font-size:.8rem;color:#94a3b8;margin:4px 0 0 0;">Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
            <p style="font-size:.8rem;color:#94a3b8;margin:4px 0 0 0;">Teacher: {{ $teacher->name }}</p>
        </div>

        <!-- Summary -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-bottom:20px;">
            <div style="font-size:.85rem;font-weight:600;color:#1e293b;margin-bottom:8px;">Report Summary</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;">
                <div>
                    <div style="font-size:.72rem;color:#64748b;">My Students</div>
                    <div style="font-size:1.1rem;font-weight:700;color:#059669;">{{ $students->count() }}</div>
                </div>
                <div>
                    <div style="font-size:.72rem;color:#64748b;">My Subjects</div>
                    <div style="font-size:1.1rem;font-weight:700;color:#7c2d12;">{{ $teacherSubjects->count() }}</div>
                </div>
                @if($filters['course'] ?? false)
                <div>
                    <div style="font-size:.72rem;color:#64748b;">Course</div>
                    <div style="font-size:1.1rem;font-weight:700;color:#16a34a;">{{ $filters['course'] }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Students Table -->
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.8rem;">
                <thead>
                    <tr style="background:#f1f5f9;border-bottom:2px solid #e2e8f0;">
                        <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">#</th>
                        <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">Student Name</th>
                        <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">Student ID</th>
                        <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">Course</th>
                        <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">Year</th>
                        <th style="padding:8px;text-align:left;font-weight:700;color:#374151;">Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $display = $forPdf ? $students : $students->take(10);
                    @endphp
                    @forelse($display as $i => $student)
                    @php
                        $totalRecords = $student->attendances->count();
                        $presentLate = $student->attendances->whereIn('status', ['Present', 'Late'])->count();
                        $rate = $totalRecords > 0 ? round(($presentLate / $totalRecords) * 100) : 0;
                    @endphp
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:8px;color:#6b7280;">{{ $i + 1 }}</td>
                        <td style="padding:8px;font-weight:600;color:#1f2937;">{{ $student->name }}</td>
                        <td style="padding:8px;font-family:monospace;color:#374151;">{{ $student->student_number }}</td>
                        <td style="padding:8px;color:#374151;">{{ $student->course }}</td>
                        <td style="padding:8px;color:#374151;">{{ $student->year_level }}</td>
                        <td style="padding:8px;color:{{ $rate >= 75 ? '#16a34a' : ($rate >= 50 ? '#d97706' : '#dc2626') }};font-weight:600;">{{ $rate }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:20px;text-align:center;color:#9ca3af;">No students found with current filters</td>
                    </tr>
                    @endforelse

                    @if(!$forPdf && $students->count() > 10)
                    <tr>
                        <td colspan="6" style="padding:12px;text-align:center;background:#f9fafb;color:#6b7280;font-style:italic;">
                            ... and {{ $students->count() - 10 }} more students (showing first 10 in preview)
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- PDF Footer -->
        <div style="margin-top:24px;padding-top:16px;border-top:1px solid #e2e8f0;text-align:center;">
            <p style="font-size:.72rem;color:#9ca3af;margin:0;">
                This document contains {{ $students->count() }} student record{{ $students->count() !== 1 ? 's' : '' }} • 
                Generated by {{ $teacher->name }} • 
                {{ now()->format('Y-m-d H:i:s') }}
            </p>
        </div>
    </div>
</div>
