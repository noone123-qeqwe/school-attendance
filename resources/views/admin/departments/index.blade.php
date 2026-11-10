@extends('layouts.app')

@section('title', 'Departments')

@section('content')
@php($isReadOnlyAuditor = auth()->user()->admin_sub_role === 'auditor')
<div class="admin-page-heading">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Departments</h1>
        <p class="saas-text-muted" style="margin:0;">Manage academic departments and faculties.</p>
    </div>

    <div class="admin-page-actions">
        @unless($isReadOnlyAuditor)<button class="saas-btn saas-btn-primary" onclick="openModal('addDepartmentModal')">
            <i class="bi bi-plus-lg"></i> Add Department
        </button>@endunless
    </div>
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <div class="saas-card-header">
        <form class="admin-filter-toolbar w-100" method="GET" action="{{ route('admin.departments.index') }}">
            <div class="saas-form-group m-0"><label class="saas-label" for="academicSearch">Search departments</label><input type="search" id="academicSearch" name="search" value="{{ request('search') }}" maxlength="100" class="saas-input" placeholder="Code or name"></div>
            <div class="saas-form-group m-0"><label class="saas-label" for="academicSort">Sort order</label><select id="academicSort" name="sort" class="saas-input saas-select"><option value="newest">Newest first</option><option value="name" @selected(request('sort') === 'name')>Name (A–Z)</option></select></div>
            <button type="submit" class="saas-btn saas-btn-secondary"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
            @if(request()->hasAny(['search', 'department_id', 'course_id', 'sort']))<a href="{{ route('admin.departments.index') }}" class="saas-btn saas-btn-secondary">Clear</a>@endif
        </form>
    </div>
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>

                    <th>Code</th>
                    <th>Department Name</th>
                    <th>Description</th>
                    <th>Date Added</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $dept)
                <tr>

                    <td><span class="saas-badge saas-badge-default" style="font-family:monospace;">{{ $dept->code }}</span></td>
                    <td style="font-weight:500;">{{ $dept->name }}</td>
                    <td class="saas-text-muted" style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $dept->description ?: 'No description provided.' }}
                    </td>
                    <td class="saas-text-muted">{{ $dept->created_at->format('M d, Y') }}</td>
                    <td style="text-align:right;">
                        @unless($isReadOnlyAuditor)
                        <button type="button" class="saas-btn saas-btn-secondary" title="Edit department" data-admin-edit-record="{{ json_encode($dept->only(['code', 'name', 'description'])) }}" data-edit-target="departmentEditor" data-edit-url="{{ route('admin.departments.update', $dept) }}"><i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Edit department</span></button>
                        <form action="{{ route('admin.departments.destroy', $dept) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this department?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="saas-btn saas-btn-secondary" title="Delete department" style="color:var(--saas-danger);"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Delete department</span></button>
                        </form>
                        @else <span class="saas-text-muted">Read only</span> @endunless

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-building saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No departments found</div>
                        <p class="saas-text-muted" style="margin-bottom:20px; max-width:400px; margin-inline:auto;">Get started by adding your first academic department. Departments are used to group courses and sections.</p>
                        @unless($isReadOnlyAuditor)<button class="saas-btn saas-btn-primary" onclick="openModal('addDepartmentModal')">
                            <i class="bi bi-plus-lg"></i> Add Department
                        </button>@endunless
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($departments->hasPages())
    <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:space-between; align-items:center;">
        <div class="saas-text-muted">
            Showing {{ $departments->firstItem() ?? 0 }} to {{ $departments->lastItem() ?? 0 }} of {{ $departments->total() }} results
        </div>
        <div>
            {{ $departments->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Modal Component Structure -->
<div class="saas-modal-backdrop" id="addDepartmentModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s;">
    <div class="saas-card" style="width:100%; max-width:500px; transform:scale(0.95); transition:transform 0.2s;" id="addDepartmentCard">
        <div class="saas-card-header">
            <div class="saas-heading saas-heading-sm">Add New Department</div>
            <button onclick="closeModal('addDepartmentModal')" style="background:none; border:none; color:var(--saas-text-muted); cursor:pointer;"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.departments.store') }}" method="POST">
            @csrf
            <div class="saas-card-body">
                <div class="saas-form-group">
                    <label class="saas-label">Department Code</label>
                    <input type="text" name="code" class="saas-input" placeholder="e.g. CS, ENG, BUS" required>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">Department Name</label>
                    <input type="text" name="name" class="saas-input" placeholder="e.g. Computer Science" required>
                </div>
                <div class="saas-form-group" style="margin-bottom:0;">
                    <label class="saas-label">Description (Optional)</label>
                    <textarea name="description" class="saas-input" rows="3" placeholder="Brief description of the department..."></textarea>
                </div>
            </div>
            <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:flex-end; gap:12px; background:rgba(0,0,0,0.2);">
                <button type="button" class="saas-btn saas-btn-secondary" onclick="closeModal('addDepartmentModal')">Cancel</button>
                <button type="submit" class="saas-btn saas-btn-primary">Save Department</button>
            </div>
        </form>
    </div>
</div>
@unless($isReadOnlyAuditor)
    @include('partials.admin-record-editor', ['editorId' => 'departmentEditor', 'recordType' => 'department'])
@endunless
@endsection

@push('scripts')
<script @cspNonce>
    function openModal(id) {
        const modal = document.getElementById(id);
        const card = modal.querySelector('.saas-card');
        modal.style.display = 'flex';
        // Trigger reflow
        void modal.offsetWidth;
        modal.style.opacity = '1';
        card.style.transform = 'scale(1)';
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        const card = modal.querySelector('.saas-card');
        modal.style.opacity = '0';
        card.style.transform = 'scale(0.95)';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 200);
    }
</script>
@endpush
