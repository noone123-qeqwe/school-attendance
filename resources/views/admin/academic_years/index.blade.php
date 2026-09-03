@extends('layouts.app')

@section('portal-title', 'Academic Terms')
@section('page-title', 'School Years & Semesters')
@section('page-sub', 'Manage academic periods, semester terms, and active school year')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">School Years & Semesters</h1>
        <p class="saas-text-muted" style="margin:0;">Configure academic periods, track terms, and switch the current active school year.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button class="saas-btn saas-btn-primary" onclick="openModal('addAcademicYearModal')">
            <i class="bi bi-plus-lg"></i> Add Term
        </button>
    </div>
</div>

@if($currentAcademicYear)
<div class="saas-card" style="margin-bottom:24px; background: linear-gradient(135deg, rgba(207,164,111,0.12), rgba(30,21,21,0.6)); border: 1px solid rgba(207,164,111,0.35);">
    <div style="padding: 20px 24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:16px;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(207,164,111,0.2); border:1px solid rgba(207,164,111,0.4); display:flex; align-items:center; justify-content:center; color:#cfa46f; font-size:1.4rem;">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
            <div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size:1.25rem; font-weight:800; color:#f3e7cd;">{{ $currentAcademicYear->name }} — {{ $currentAcademicYear->semester_label }}</span>
                    <span class="saas-badge saas-badge-success" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px;">Active Term</span>
                </div>
                <div class="saas-text-muted" style="font-size:0.85rem; margin-top:2px;">
                    Term duration: <strong style="color:#f3e7cd;">{{ $currentAcademicYear->start_date ? $currentAcademicYear->start_date->format('M d, Y') : 'N/A' }}</strong> to <strong style="color:#f3e7cd;">{{ $currentAcademicYear->end_date ? $currentAcademicYear->end_date->format('M d, Y') : 'N/A' }}</strong>
                    &bull; Total records logged: <strong style="color:#cfa46f;">{{ number_format($currentAcademicYear->attendances_count) }}</strong>
                </div>
            </div>
        </div>
        <div>
            <span class="saas-badge" style="background:rgba(16,185,129,0.15); color:#34d399; border:1px solid rgba(16,185,129,0.3); padding:8px 14px; font-weight:700;">
                <i class="bi bi-check-circle-fill me-1"></i> QR Sessions Linked to this Term
            </span>
        </div>
    </div>
</div>
@else
<div class="saas-card" style="margin-bottom:24px; border: 1px solid rgba(239,68,68,0.3); background: rgba(239,68,68,0.06);">
    <div style="padding: 16px 20px; display:flex; align-items:center; gap:12px; color:#fca5a5;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:1.3rem;"></i>
        <div>
            <strong>No active academic term set!</strong> Please set an active school year & semester below so attendance records and schedules map to the correct term.
        </div>
    </div>
</div>
@endif

<div class="saas-card">
    <div class="saas-card-header" style="justify-content:space-between; align-items:center;">
        <div>
            <h3 style="font-size:1.05rem; font-weight:700; color:#f3e7cd; margin:0;">All Academic Periods</h3>
            <p class="saas-text-muted" style="margin:0; font-size:0.8rem;">Historical and scheduled school terms</p>
        </div>
    </div>

    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>
                    <th>School Year</th>
                    <th>Semester</th>
                    <th>Date Range</th>
                    <th>Attendance Records</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($academicYears as $year)
                <tr>
                    <td style="font-weight:700; color:#f3e7cd;">
                        <span style="font-size:0.95rem;">{{ $year->name }}</span>
                    </td>
                    <td>
                        <span class="saas-badge saas-badge-default">{{ $year->semester_label }}</span>
                    </td>
                    <td>
                        <span class="saas-text-muted">
                            {{ $year->start_date ? $year->start_date->format('M d, Y') : 'N/A' }} &ndash; {{ $year->end_date ? $year->end_date->format('M d, Y') : 'N/A' }}
                        </span>
                    </td>
                    <td>
                        <span style="font-weight:600; color:#cfa46f;">{{ number_format($year->attendances_count) }}</span>
                    </td>
                    <td>
                        @if($year->is_current)
                            <span class="saas-badge saas-badge-success"><i class="bi bi-check-circle-fill me-1"></i> Active</span>
                        @else
                            <span class="saas-badge saas-badge-default" style="opacity:0.7;">Inactive</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        <div style="display:inline-flex; gap:6px; align-items:center;">
                            @if(!$year->is_current)
                            <form action="{{ route('academic-years.set-current', $year->id) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="saas-btn saas-btn-secondary" style="padding:4px 10px; font-size:0.75rem; color:#34d399; border-color:rgba(52,211,153,0.3);" title="Set as Current Active Term">
                                    <i class="bi bi-lightning-charge-fill me-1"></i> Activate
                                </button>
                            </form>
                            @endif

                            <button type="button" class="saas-btn saas-btn-secondary" style="padding:4px 10px; font-size:0.75rem;" onclick="openEditModal({{ json_encode($year) }})">
                                <i class="bi bi-pencil"></i> Edit
                            </button>

                            @if(!$year->is_current && $year->attendances_count === 0)
                            <form action="{{ route('academic-years.destroy', $year->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this academic year term?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="saas-btn saas-btn-secondary" style="padding:4px 10px; font-size:0.75rem; color:#f87171; border-color:rgba(239,68,68,0.3);" title="Delete Term">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:36px; color:#a38b7d;">
                        <i class="bi bi-calendar-x" style="font-size:2rem; display:block; margin-bottom:8px; opacity:0.5;"></i>
                        No academic year terms defined yet. Click "Add Term" above to create one.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($academicYears->hasPages())
    <div style="padding:16px 20px; border-top:1px solid rgba(207,164,111,0.15);">
        {{ $academicYears->links() }}
    </div>
    @endif
</div>

<!-- Add Term Modal -->
<div class="saas-modal-backdrop" id="addAcademicYearModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div class="saas-card" style="width:100%; max-width:500px; margin:20px; box-shadow:0 24px 60px rgba(0,0,0,0.6);">
        <div class="saas-card-header" style="justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700; color:#f3e7cd;">Add School Year Term</h3>
            <button type="button" class="saas-btn saas-btn-secondary" style="padding:4px 8px;" onclick="closeModal('addAcademicYearModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form action="{{ route('academic-years.store') }}" method="POST">
            @csrf
            <div style="padding:20px;">
                <div style="margin-bottom:16px;">
                    <label class="saas-label" style="font-weight:600; font-size:0.85rem; color:#f3e7cd; margin-bottom:6px; display:block;">School Year Name</label>
                    <input type="text" name="name" class="saas-input" placeholder="e.g. 2026-2027" required style="width:100%;">
                    <small class="saas-text-muted">Standard format: YYYY-YYYY (e.g. 2026-2027)</small>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="saas-label" style="font-weight:600; font-size:0.85rem; color:#f3e7cd; margin-bottom:6px; display:block;">Semester / Term</label>
                    <select name="semester" class="saas-input saas-select" required style="width:100%;">
                        <option value="1">1st Semester</option>
                        <option value="2">2nd Semester</option>
                        <option value="3">Summer Term</option>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
                    <div>
                        <label class="saas-label" style="font-weight:600; font-size:0.85rem; color:#f3e7cd; margin-bottom:6px; display:block;">Start Date</label>
                        <input type="date" name="start_date" class="saas-input" required style="width:100%;">
                    </div>
                    <div>
                        <label class="saas-label" style="font-weight:600; font-size:0.85rem; color:#f3e7cd; margin-bottom:6px; display:block;">End Date</label>
                        <input type="date" name="end_date" class="saas-input" required style="width:100%;">
                    </div>
                </div>

                <div style="margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="is_current" id="add_is_current" value="1" style="accent-color:var(--saas-primary); width:16px; height:16px;">
                    <label for="add_is_current" style="font-size:0.85rem; color:#f3e7cd; cursor:pointer;">
                        Set as the current active school year immediately
                    </label>
                </div>
            </div>
            <div style="padding:16px 20px; background:rgba(0,0,0,0.2); border-top:1px solid rgba(207,164,111,0.15); display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="saas-btn saas-btn-secondary" onclick="closeModal('addAcademicYearModal')">Cancel</button>
                <button type="submit" class="saas-btn saas-btn-primary">Save Term</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Term Modal -->
<div class="saas-modal-backdrop" id="editAcademicYearModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div class="saas-card" style="width:100%; max-width:500px; margin:20px; box-shadow:0 24px 60px rgba(0,0,0,0.6);">
        <div class="saas-card-header" style="justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.1rem; font-weight:700; color:#f3e7cd;">Edit School Year Term</h3>
            <button type="button" class="saas-btn saas-btn-secondary" style="padding:4px 8px;" onclick="closeModal('editAcademicYearModal')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="editAcademicYearForm" method="POST">
            @csrf
            @method('PUT')
            <div style="padding:20px;">
                <div style="margin-bottom:16px;">
                    <label class="saas-label" style="font-weight:600; font-size:0.85rem; color:#f3e7cd; margin-bottom:6px; display:block;">School Year Name</label>
                    <input type="text" id="edit_name" name="name" class="saas-input" required style="width:100%;">
                </div>

                <div style="margin-bottom:16px;">
                    <label class="saas-label" style="font-weight:600; font-size:0.85rem; color:#f3e7cd; margin-bottom:6px; display:block;">Semester / Term</label>
                    <select id="edit_semester" name="semester" class="saas-input saas-select" required style="width:100%;">
                        <option value="1">1st Semester</option>
                        <option value="2">2nd Semester</option>
                        <option value="3">Summer Term</option>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
                    <div>
                        <label class="saas-label" style="font-weight:600; font-size:0.85rem; color:#f3e7cd; margin-bottom:6px; display:block;">Start Date</label>
                        <input type="date" id="edit_start_date" name="start_date" class="saas-input" required style="width:100%;">
                    </div>
                    <div>
                        <label class="saas-label" style="font-weight:600; font-size:0.85rem; color:#f3e7cd; margin-bottom:6px; display:block;">End Date</label>
                        <input type="date" id="edit_end_date" name="end_date" class="saas-input" required style="width:100%;">
                    </div>
                </div>

                <div style="margin-bottom:8px; display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="is_current" id="edit_is_current" value="1" style="accent-color:var(--saas-primary); width:16px; height:16px;">
                    <label for="edit_is_current" style="font-size:0.85rem; color:#f3e7cd; cursor:pointer;">
                        Set as the current active school year
                    </label>
                </div>
            </div>
            <div style="padding:16px 20px; background:rgba(0,0,0,0.2); border-top:1px solid rgba(207,164,111,0.15); display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="saas-btn saas-btn-secondary" onclick="closeModal('editAcademicYearModal')">Cancel</button>
                <button type="submit" class="saas-btn saas-btn-primary">Update Term</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

function openEditModal(year) {
    const form = document.getElementById('editAcademicYearForm');
    form.action = `/admin/academic-years/${year.id}`;
    document.getElementById('edit_name').value = year.name || '';
    document.getElementById('edit_semester').value = year.semester || 1;
    document.getElementById('edit_start_date').value = year.start_date ? year.start_date.substring(0, 10) : '';
    document.getElementById('edit_end_date').value = year.end_date ? year.end_date.substring(0, 10) : '';
    document.getElementById('edit_is_current').checked = !!year.is_current;
    openModal('editAcademicYearModal');
}
</script>
@endsection
