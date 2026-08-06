@php
    $forPdf = $forPdf ?? false;
    $logoSrc = $forPdf ? ('file://' . public_path('images/logo.png')) : asset('images/logo.png');
@endphp

<div style="padding:24px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
    <div style="background:white;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
        <div style="background:white;padding:40px 32px 36px 32px;text-align:center;border-radius:16px;border:1px solid #e5e7eb;position:relative;">
            <img src="{{ $logoSrc }}" alt="{{ config('app.name') }} logo" style="width:72px;height:72px;object-fit:contain;margin:0 auto 18px;">
            <h1 style="font-size:2rem;font-weight:800;margin:0 0 8px 0;color:#111827;">{{ config('app.name') }}</h1>
            <p style="font-size:1rem;color:#4b5563;margin:0 0 18px 0;">{{ config('app.subtitle', 'QR, GPS, and Biometric-Based Attendance Monitoring') }}</p>
            <h2 style="font-size:1.4rem;font-weight:700;margin:0 0 6px 0;color:#991b1b;">Subjects Directory Report</h2>
            <p style="font-size:.95rem;color:#6b7280;margin:8px 0 0 0;">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</p>
        </div>

        <div style="padding:30px;">
            @if(!empty(array_filter($filters)))
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

            @php
                $totalSubjects = $subjects->count();
                $courseStats = $subjects->groupBy('course');
                $yearStats = $subjects->groupBy('year_level');
                $totalUnits = $subjects->sum('units');
                $displaySubjects = $forPdf ? $subjects : $subjects->take(10);
            @endphp

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:20px;margin-bottom:30px;">
                <div style="text-align:center;background:white;border:1px solid #e5e7eb;border-radius:12px;padding:20px 15px;border-top:3px solid #800000;">
                    <div style="font-size:1.8rem;font-weight:800;color:#800000;margin-bottom:6px;">{{ $totalSubjects }}</div>
                    <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">Total Subjects</div>
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
                    <div style="font-size:1.8rem;font-weight:800;color:#800000;margin-bottom:6px;">{{ $totalUnits }}</div>
                    <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">Total Units</div>
                </div>
            </div>

            <div style="background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.1);margin-bottom:25px;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:linear-gradient(135deg, #800000 0%, #600000 100%);color:white;">
                            <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:5%;">#</th>
                            <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:12%;">Code</th>
                            <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:30%;">Subject Name</th>
                            <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:10%;">Course</th>
                            <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:8%;">Year</th>
                            <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:10%;">Semester</th>
                            <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:8%;">Units</th>
                            <th style="padding:15px 12px;text-align:left;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:17%;">Instructor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($displaySubjects as $i => $subject)
                        <tr style="border-bottom:1px solid #f1f5f9;{{ $i % 2 === 1 ? 'background:#fafbfc;' : '' }}">
                            <td style="padding:12px;text-align:center;color:#475569;">{{ $i + 1 }}</td>
                            <td style="padding:12px;color:#111827;font-weight:700;font-family:monospace;">{{ $subject->code }}</td>
                            <td style="padding:12px;color:#111827;font-weight:700;">{{ $subject->name }}</td>
                            <td style="padding:12px;color:#111827;font-weight:600;">{{ $subject->course ?? 'N/A' }}</td>
                            <td style="padding:12px;color:#111827;font-weight:600;">Year {{ $subject->year_level }}</td>
                            <td style="padding:12px;color:#111827;font-weight:600;">{{ $subject->semester }}{{ (int)$subject->semester === 1 ? 'st' : 'nd' }} Sem</td>
                            <td style="padding:12px;text-align:center;color:#111827;font-weight:600;">{{ $subject->units ?? '—' }}</td>
                            <td style="padding:12px;color:#111827;">{{ $subject->instructorUser->name ?? 'TBA' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" style="padding:40px;text-align:center;color:#6b7280;">No subjects found with current filters</td>
                        </tr>
                        @endforelse
                        @if(!$forPdf && $subjects->count() > 10)
                        <tr>
                            <td colspan="8" style="padding:16px;text-align:center;background:#f9fafb;color:#6b7280;font-style:italic;border-top:2px solid #e5e7eb;">
                                ... and {{ $subjects->count() - 10 }} more subjects (showing first 10 in preview)
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            @if($courseStats->count() > 1)
            <div style="background:linear-gradient(135deg, #fef7f7 0%, #fdf2f4 100%);border:1px solid #f5c6cf;border-left:4px solid #800000;padding:20px;border-radius:12px;margin-bottom:25px;">
                <div style="font-weight:700;color:#800000;font-size:0.85rem;margin-bottom:12px;text-transform:uppercase;letter-spacing:0.5px;">Course Distribution</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:15px;">
                    @foreach($courseStats as $course => $courseSubjects)
                        <div style="text-align:center;background:white;border:1px solid #e5e7eb;border-radius:8px;padding:15px;border-top:3px solid #800000;">
                            <div style="font-size:1.4rem;font-weight:800;color:#800000;margin-bottom:6px;">{{ $courseSubjects->count() }}</div>
                            <div style="font-size:0.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;">{{ $course }} Subjects</div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div style="background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);border-top:3px solid #800000;padding:25px;text-align:center;color:#64748b;font-size:0.8rem;">
            <div style="font-weight:700;color:#800000;margin-bottom:4px;">{{ config('app.name') }} • Subjects Directory Report</div>
            <div style="color:#94a3b8;">This document contains {{ $subjects->count() }} subject record{{ $subjects->count() !== 1 ? 's' : '' }} • Generated {{ now()->format('M d, Y \a\t g:i A') }}</div>
        </div>
    </div>
</div>
