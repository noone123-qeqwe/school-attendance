@extends('teacher.layout')
@section('page-title', 'Students PDF Preview')

@section('content')
<a href="{{ route('teacher.students', request()->query()) }}" style="display:inline-flex;align-items:center;gap:7px;font-size:.85rem;font-weight:600;color:#64748b;text-decoration:none;padding:8px 14px;border:1.5px solid #e2e8f0;border-radius:9px;background:white;margin-bottom:20px;" onmouseover="this.style.color='#059669';this.style.borderColor='#059669';" onmouseout="this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
    <i class="bi bi-arrow-left"></i> Back to Students
</a>

<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-file-earmark-pdf-fill"></i></div>
            PDF Preview: My Students List
            @if(array_filter($filters))
            <span style="background:#f0fdf4;color:#059669;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;border:1px solid #bbf7d0;">
                Filtered Results
            </span>
            @endif
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('teacher.students.pdf', request()->query()) }}" class="tch-btn tch-btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                <i class="bi bi-download"></i> Download PDF
            </a>
        </div>
    </div>

    <!-- Applied Filters -->
    @if(array_filter($filters))
    <div style="padding:14px 22px;border-bottom:1px solid #f8fafc;background:#f8fafc;">
        <div style="font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Applied Filters:</div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @if($filters['search'] ?? false)
            <span style="background:#eff6ff;color:#2563eb;padding:4px 10px;border-radius:99px;font-size:.72rem;font-weight:600;border:1px solid #bfdbfe;">
                Search: {{ $filters['search'] }}
            </span>
            @endif
            @if($filters['course'] ?? false)
            <span style="background:#f0fdf4;color:#16a34a;padding:4px 10px;border-radius:99px;font-size:.72rem;font-weight:600;border:1px solid #bbf7d0;">
                Course: {{ $filters['course'] }}
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
        </div>
    </div>
    @endif

    <!-- PDF Content Preview -->
    @include('teacher.students._report', ['forPdf' => false])

    <!-- Action Buttons -->
    <div style="padding:16px 24px;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;">
        <div style="font-size:.8rem;color:#64748b;">
            Preview shows the first 10 records. The complete PDF will contain all {{ $students->count() }} students.
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('teacher.students', request()->query()) }}" class="tch-btn tch-btn-ghost">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
            <a href="{{ route('teacher.students.pdf', request()->query()) }}" class="tch-btn tch-btn-primary">
                <i class="bi bi-download me-1"></i> Download PDF
            </a>
        </div>
    </div>
</div>
@endsection