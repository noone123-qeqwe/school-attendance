@extends('layouts.admin_premium')
@section('page-title', 'Students PDF Preview')

@section('content')
<a href="{{ route('admin.students', request()->query()) }}" style="display:inline-flex;align-items:center;gap:7px;font-size:.85rem;font-weight:600;color:#64748b;text-decoration:none;padding:8px 14px;border:1.5px solid #e2e8f0;border-radius:9px;background:white;margin-bottom:20px;" onmouseover="this.style.color='#800000';this.style.borderColor='#800000';" onmouseout="this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
    <i class="bi bi-arrow-left"></i> Back to Students
</a>

<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-file-earmark-pdf-fill"></i></div>
            PDF Preview: Students List
            @if(array_filter($filters))
            <span style="background:#fff0f3;color:#6b0020;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;border:1px solid #f5c6cf;">
                Filtered Results
            </span>
            @endif
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('admin.students.pdf', request()->query()) }}" class="adm-btn adm-btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="bi bi-download"></i> Download PDF
            </a>
        </div>
    </div>

    <!-- PDF Content Preview with Enhanced Styling -->
    <div style="padding:24px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
        <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
            
            <!-- Enhanced PDF Header -->
            <div style="background:white;padding:40px 32px 36px 32px;text-align:center;border-radius:16px;border:1px solid #e5e7eb;position:relative;">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }} logo" style="width:72px;height:72px;object-fit:contain;margin:0 auto 18px;">
                <h1 style="font-size:2rem;font-weight:800;margin:0 0 8px 0;color:#111827;">{{ config('app.name') }}</h1>
                <p style="font-size:1rem;color:#4b5563;margin:0 0 18px 0;">{{ config('app.subtitle', 'QR, GPS, and Biometric-Based Attendance Monitoring') }}</p>
                <h2 style="font-size:1.4rem;font-weight:700;margin:0 0 6px 0;color:#991b1b;">Students Directory Report</h2>
                <p style="font-size:.95rem;color:#6b7280;margin:8px 0 0 0;">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</p>
            </div>

            <div style="padding:30px;">
                <!-- Applied Filters Section -->
                @if(array_filter($filters))
                <div style="background:linear-gradient(135deg, #fef7f7 0%, #fdf2f4 100%);border:1px solid #f5c6cf;border-left:4px solid #800000;padding:20px;border-radius:12px;margin-bottom:25px;">
                    <div style="font-weight:700;color:#800000;font-size:0.85rem;margin-bottom:12px;text-transform:uppercase;letter-spacing:0.5px;">Applied Filters</div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        @if($filters['search'] ?? false)
                        <span style="background:white;border:1px solid #e5e7eb;padding:6px 12px;border-radius:20px;font-size:0.75rem;color:#374151;font-weight:500;">
                            <strong>Search:</strong> {{ $filters['search'] }}
                        </span>
                        @endif
                        @if($filters['course'] ?? false)
                        <span style="background:white;border:1px solid #e5e7eb;padding:6px 12px;border-radius:20px;font-size:0.75rem;color:#374151;font-weight:500;">
                            <strong>Course:</strong> {{ $filters['course'] }}
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
                    </div>
                </div>
                @endif

                <!-- Enhanced Statistics Grid -->
                @php
                    $totalStudents = $students->count();
                    $courseStats = $students->groupBy('course');
                    $yearStats = $students->groupBy('year_level');
                    $totalAbsences = $students->sum(function($student) {
                        return $student->attendances->where('status', 'Absent')->count();
                    });
                @endphp
                
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:20px;margin-bottom:30px;">
                    <div style="text-align:center;background:white;border:1px solid #e5e7eb;border-radius:12px;padding:20px 15px;border-top:3px solid #800000;">
                        <div style="font-size:1.8rem;font-weight:800;color:#800000;margin-bottom:6px;">{{ $totalStudents }}</div>
                        <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">Total Students</div>
                    </div>
                    <div style="text-align:center;background:white;border:1px solid #e5e7eb;border-radius:12px;padding:20px 15px;border-top:3px solid #800000;">
                        <div style="font-size:1.8rem;font-weight:800;color:#800000;margin-bottom:6px;">{{ $courseStats->count() }}</div>
                        <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">Courses</div>
                    </div>
                    <div style="text-align:center;background:white;border:1px solid #e5e7eb;border-radius:12px;padding:20px 15px;border-top:3px solid #800000;">
                        <div style="font-size:1.8rem;font-weight:800;color:#800000;margin-bottom:6px;">{{ $yearStats->count() }}</div>
                        <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">Year Levels</div>
                    </div>
                    <div style="text-align:center;background:white;border:1px solid #e5e7eb;border-radius:12px;padding:20px 15px;border-top:3px solid #800000;">
                        <div style="font-size:1.8rem;font-weight:800;color:#800000;margin-bottom:6px;">{{ $totalAbsences }}</div>
                        <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">Total Absences</div>
                    </div>
                </div>

                <!-- Enhanced Students Table -->
                <div style="background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.1);margin-bottom:25px;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:linear-gradient(135deg, #800000 0%, #600000 100%);color:white;">
                                <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:5%;">#</th>
                                <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:30%;">Student Information</th>
                                <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:12%;">Student ID</th>
                                <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:12%;">Course</th>
                                <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:10%;">Year</th>
                                <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:10%;">Semester</th>
                                <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:10%;">Absences</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students->take(10) as $i => $student)
                            @php $absences = $student->attendances->where('status', 'Absent')->count(); @endphp
                            <tr style="border-bottom:1px solid #f1f5f9;{{ $i % 2 === 1 ? 'background:#fafbfc;' : '' }}">
                                <td style="padding:12px;text-align:center;color:#475569;">{{ $i + 1 }}</td>
                                <td style="padding:12px;">
                                    <div style="font-weight:700;color:#111827;font-size:0.9rem;">{{ $student->name }}</div>
                                    <div style="color:#475569;font-size:0.78rem;">{{ $student->email }}</div>
                                </td>
                                <td style="padding:12px;color:#111827;font-family:monospace;font-weight:600;font-size:0.85rem;">{{ $student->student_number }}</td>
                                <td style="padding:12px;color:#111827;font-weight:600;">{{ $student->course }}</td>
                                <td style="padding:12px;color:#111827;font-weight:600;">Year {{ $student->year_level }}</td>
                                <td style="padding:12px;color:#111827;font-weight:600;">{{ $student->semester }}{{ (int)$student->semester === 1 ? 'st' : 'nd' }}</td>
                                <td style="padding:12px;text-align:center;color:#111827;font-weight:600;">{{ $absences > 0 ? $absences : '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="padding:40px;text-align:center;color:#6b7280;">No students found with current filters</td>
                            </tr>
                            @endforelse
                            
                            @if($students->count() > 10)
                            <tr>
                                <td colspan="7" style="padding:16px;text-align:center;background:#f9fafb;color:#6b7280;font-style:italic;border-top:2px solid #e5e7eb;">
                                    ... and {{ $students->count() - 10 }} more students (showing first 10 in preview)
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Course Summary (if multiple courses) -->
                @if($courseStats->count() > 1)
                <div style="background:linear-gradient(135deg, #fef7f7 0%, #fdf2f4 100%);border:1px solid #f5c6cf;border-left:4px solid #800000;padding:20px;border-radius:12px;margin-bottom:25px;">
                    <div style="font-weight:700;color:#800000;font-size:0.85rem;margin-bottom:12px;text-transform:uppercase;letter-spacing:0.5px;">Course Distribution</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:15px;">
                        @foreach($courseStats as $course => $courseStudents)
                            <div style="text-align:center;background:white;border:1px solid #e5e7eb;border-radius:8px;padding:15px;border-top:3px solid #800000;">
                                <div style="font-size:1.4rem;font-weight:800;color:#800000;margin-bottom:6px;">{{ $courseStudents->count() }}</div>
                                <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">{{ $course }} Students</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Enhanced PDF Footer -->
            <div style="background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);border-top:3px solid #800000;padding:25px;text-align:center;color:#64748b;font-size:0.8rem;">
                <div style="font-weight:700;color:#800000;margin-bottom:4px;">{{ config('app.name') }} • Students Directory Report</div>
                <div style="color:#94a3b8;">This document contains {{ $students->count() }} student record{{ $students->count() !== 1 ? 's' : '' }} • Generated {{ now()->format('M d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="padding:16px 24px;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;">
        <div style="font-size:.8rem;color:#64748b;">
            Preview shows the first 10 records. The complete PDF will contain all {{ $students->count() }} students.
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('admin.students', request()->query()) }}" class="adm-btn adm-btn-ghost">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
            <a href="{{ route('admin.students.pdf', request()->query()) }}" class="adm-btn adm-btn-primary">
                <i class="bi bi-download me-1"></i> Download PDF
            </a>
        </div>
    </div>
</div>
@endsection
