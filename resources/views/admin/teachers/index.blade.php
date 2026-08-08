@extends('layouts.app')

@section('title', 'Instructors')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Instructors</h1>
        <p class="saas-text-muted" style="margin:0;">Manage teaching faculty and employee records.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button type="button" class="saas-btn saas-btn-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload"></i> Import CSV
        </button>
        <a href="{{ route('admin.teachers.pdf', request()->query()) }}" class="saas-btn saas-btn-secondary">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
        <a href="{{ route('admin.teacher.create') }}" class="saas-btn saas-btn-primary">
            <i class="bi bi-plus-lg"></i> Add Instructor
        </a>
    </div>
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <form method="GET" action="{{ route('admin.teachers') }}" class="saas-card-header" style="gap:16px; flex-wrap:wrap; display:flex;">
        <div class="saas-search" style="width:250px;">
            <i class="bi bi-search"></i>
            <input type="text" name="search" class="saas-search-input" placeholder="Name or Employee ID" value="{{ request('search') }}">
        </div>
        
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <select name="department" class="saas-input saas-select" style="width:140px; padding:6px 30px 6px 12px;">
                <option value="">All Departments</option>
                <option value="CS" {{ request('department')=='CS'?'selected':'' }}>Computer Science</option>
                <option value="IT" {{ request('department')=='IT'?'selected':'' }}>Information Tech</option>
            </select>
            
            <select name="bulk_action" class="saas-input saas-select" style="width:140px; padding:6px 30px 6px 12px;">
                <option value="">Bulk Actions</option>
                <option value="delete">Delete Selected</option>
                <option value="export">Export Selected</option>
            </select>
            
            <button type="submit" class="saas-btn saas-btn-secondary" style="padding:6px 12px;">
                <i class="bi bi-funnel"></i> Filter
            </button>
            
            @if(request()->hasAny(['search','department']))
            <a href="{{ route('admin.teachers') }}" class="saas-btn saas-btn-secondary" style="padding:6px 12px; color:var(--saas-danger);">
                Clear
            </a>
            @endif
        </div>
    </form>
    
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" style="accent-color:var(--saas-primary);" onclick="document.querySelectorAll('.teacher-checkbox').forEach(c => c.checked = this.checked)"></th>
                    <th>Instructor</th>
                    <th>Employee ID</th>
                    <th>Department</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teachers as $teacher)
                <tr>
                    <td><input type="checkbox" class="teacher-checkbox" name="selected_teachers[]" value="{{ $teacher->id }}" style="accent-color:var(--saas-primary);"></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="{{ $teacher->profile_image ? (str_starts_with($teacher->profile_image, 'http') ? $teacher->profile_image : asset('storage/'.$teacher->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($teacher->name).'&background=900000&color=fff' }}"
                                 style="width:36px;height:36px;border-radius:var(--saas-radius-sm);object-fit:cover;border:1px solid var(--saas-border);">
                            <div>
                                <div style="font-weight:600;font-size:0.875rem;">{{ $teacher->name }}</div>
                                <div class="saas-text-muted" style="font-size:0.75rem;">{{ $teacher->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="saas-badge saas-badge-default" style="font-family:monospace;">{{ $teacher->employee_id ?? 'N/A' }}</span></td>
                    <td>
                        <span class="saas-badge saas-badge-info">{{ $teacher->department ?? 'General' }}</span>
                    </td>
                    <td style="text-align:right;">
                        <div style="display:flex; gap:6px; justify-content:flex-end;">
                            <a href="{{ route('admin.teacher.history', $teacher) }}" class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="History">
                                <i class="bi bi-clock-history"></i>
                            </a>
                            <a href="{{ route('admin.teacher.edit', $teacher) }}" class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.teacher.destroy', $teacher) }}" method="POST" onsubmit="return confirm('Delete {{ addslashes($teacher->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="saas-btn saas-btn-secondary" style="padding:4px 8px; color:var(--saas-danger);" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-person-workspace saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No instructors found</div>
                        <p class="saas-text-muted" style="margin-bottom:20px; max-width:400px; margin-inline:auto;">There are no instructors matching your criteria.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
</div>

<!-- Import CSV Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background: #ffffff; border: 1px solid var(--saas-border);">
            <form action="{{ route('admin.teachers.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header" style="border-bottom: 1px solid var(--saas-border);">
                    <h5 class="modal-title" style="color: var(--saas-text);"><i class="bi bi-upload me-2 text-primary"></i> Import Instructors</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info" style="background: rgba(14,165,233,0.1); border-color: rgba(14,165,233,0.2); color: #0284c7; font-size: 0.85rem;">
                        <strong>Format:</strong> CSV file with headers: <br>
                        <code>name, email, employee_id, department</code>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: var(--saas-text);">CSV File</label>
                        <input type="file" name="csv_file" class="form-control" style="background: #f8fafc; border: 1px solid var(--saas-border); color: var(--saas-text);" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--saas-border);">
                    <button type="button" class="saas-btn saas-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="saas-btn saas-btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
