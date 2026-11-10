@extends('layouts.app')

@section('title', 'QR Management')

@section('content')
<div class="admin-page-heading">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">QR Code Management</h1>
        <p class="saas-text-muted" style="margin:0;">Find students and print their identification QR codes. Attendance sessions are managed by instructors.</p>
    </div>

    <div class="admin-page-actions">
        <button type="submit" class="saas-btn saas-btn-secondary" form="bulkPrintForm">
            <i class="bi bi-printer"></i> Bulk Print Selected
        </button>
    </div>
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <div class="saas-card-header">
        <form method="GET" action="{{ route('admin.qr') }}" class="admin-filter-toolbar w-100">
            <div class="saas-form-group m-0"><label for="qrSearch" class="saas-label">Student name or ID</label><input type="search" id="qrSearch" name="search" class="saas-input" value="{{ request('search') }}" maxlength="100"></div>
            <div class="saas-form-group m-0"><label for="qrCourse" class="saas-label">Course</label><select id="qrCourse" name="course" class="saas-input saas-select"><option value="">All courses</option>@foreach($courses as $code)<option value="{{ $code }}" @selected(request('course') === $code)>{{ $code }}</option>@endforeach</select></div>
            <div class="saas-form-group m-0"><label for="qrYear" class="saas-label">Year level</label><select id="qrYear" name="year" class="saas-input saas-select"><option value="">All years</option>@foreach(range(1,5) as $year)<option value="{{ $year }}" @selected((int) request('year') === $year)>Year {{ $year }}</option>@endforeach</select></div>
            <button type="submit" class="saas-btn saas-btn-secondary"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
            @if(request()->hasAny(['search', 'course', 'year']))<a href="{{ route('admin.qr') }}" class="saas-btn saas-btn-secondary">Clear</a>@endif
        </form>
    </div>
    <form id="bulkPrintForm" action="{{ route('admin.qr.bulk-print') }}" method="POST" target="_blank">
        @csrf
        <div style="padding:24px;">
            <div style="margin-bottom: 16px; padding: 0 4px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--saas-text); width: fit-content;">
                    <input type="checkbox" id="selectAllQR" style="accent-color: var(--saas-gold); width: 16px; height: 16px;" onclick="document.querySelectorAll('.qr-checkbox').forEach(cb => cb.checked = this.checked)">
                    <span style="font-size: 0.9rem; font-weight: 500;">Select All</span>
                </label>
            </div>
            <div class="admin-qr-grid">
            @forelse($students as $student)
            <div style="position:relative; border:1px solid var(--saas-border); border-radius:var(--saas-radius-md); padding:20px; background:rgba(255,255,255,0.02); display:flex; flex-direction:column; align-items:center; text-align:center;">
                <label class="admin-qr-select">
                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="qr-checkbox" style="accent-color: var(--saas-gold); width: 18px; height: 18px; cursor: pointer;">
                    <span class="visually-hidden">Select {{ $student->name }} for printing</span>
                </label>
                <div style="width:120px; height:120px; background:white; padding:10px; border-radius:12px; margin-bottom:16px;">
                    <img src="{{ $qrImages[$student->id] }}" style="width:100%; height:100%; object-fit:contain;" alt="QR code for {{ $student->name }}" loading="lazy">

                </div>

                <h3 style="font-size:1rem; font-weight:600; margin-bottom:4px;">{{ $student->name }}</h3>
                <div style="font-family:monospace; color:var(--saas-gold); font-size:0.85rem; margin-bottom:8px;">
                    {{ $student->student_number ?? 'ID: '.$student->id }}
                </div>
                <div style="display:flex; gap:6px; margin-bottom:16px;">
                    <span class="saas-badge saas-badge-info" style="font-size:0.7rem; padding:2px 6px;">{{ $student->course ?? 'N/A' }}</span>
                    <span class="saas-badge saas-badge-default" style="font-size:0.7rem; padding:2px 6px;">Year {{ $student->year_level ?? 'N/A' }}</span>
                </div>

                <button type="submit" form="singleQRPrint" name="student_ids[]" value="{{ $student->id }}" class="saas-btn saas-btn-secondary w-100"><i class="bi bi-printer" aria-hidden="true"></i> Print QR (PDF)</button>

            </div>
            @empty
            <div style="grid-column:1/-1; text-align:center; padding:40px;">
                <i class="bi bi-qr-code saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                <h3 class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No students found</h3>
                <p class="saas-text-muted">There are no students matching your search criteria.</p>
            </div>
            @endforelse
        </div>
    </div>
    </form>

    @if(isset($students) && $students->hasPages())
    <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:space-between; align-items:center;">
        <div class="saas-text-muted">
            Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} results
        </div>
        <div>
            {{ $students->links() }}
        </div>
    </div>
    @endif
</div>
<form id="singleQRPrint" action="{{ route('admin.qr.bulk-print') }}" method="POST" target="_blank">@csrf</form>
@endsection
