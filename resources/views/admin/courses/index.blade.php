@extends('layouts.app')

@section('title', 'Courses')

@section('content')
@php($isReadOnlyAuditor = auth()->user()->admin_sub_role === 'auditor')
<div class="admin-page-heading">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Courses & programs</h1>
        <p class="saas-text-muted" style="margin:0;">Manage degree programs and course offerings.</p>
    </div>

    <div class="admin-page-actions">
        @unless($isReadOnlyAuditor)<button class="saas-btn saas-btn-primary" onclick="openModal('addCourseModal')">
            <i class="bi bi-plus-lg"></i> Add Course
        </button>@endunless
    </div>
</div>


<div class="saas-card" style="margin-bottom:24px;">
    <div class="saas-card-header">
        <form class="admin-filter-toolbar w-100" method="GET" action="{{ route('admin.courses.index') }}">
            <div class="saas-form-group m-0"><label class="saas-label" for="academicSearch">Search courses</label><input type="search" id="academicSearch" name="search" value="{{ request('search') }}" maxlength="100" class="saas-input" placeholder="Code or name"></div>
            <div class="saas-form-group m-0"><label class="saas-label" for="academicGroup">Department</label><select id="academicGroup" name="department_id" class="saas-input saas-select"><option value="">All departments</option>@foreach($departments as $group)<option value="{{ $group->id }}" @selected((string) request('department_id') === (string) $group->id)>{{ $group->code }}</option>@endforeach</select></div>
            <button type="submit" class="saas-btn saas-btn-secondary"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
            @if(request()->hasAny(['search', 'department_id', 'course_id', 'sort']))<a href="{{ route('admin.courses.index') }}" class="saas-btn saas-btn-secondary">Clear</a>@endif
        </form>
    </div>
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>

                    <th>Course Code</th>
                    <th>Program Name</th>
                    <th>Department</th>
                    <th>Sections</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                <tr>

                    <td><span class="saas-badge saas-badge-info" style="font-family:monospace;font-size:0.8rem;">{{ $course->code }}</span></td>
                    <td style="font-weight:500;">{{ $course->name }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:6px;height:6px;border-radius:50%;background:var(--saas-gold);"></div>
                            <span class="saas-text-muted">{{ $course->department->name ?? 'Unassigned' }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="saas-badge saas-badge-default">{{ $course->sections_count }} active</span>
                    </td>
                    <td style="text-align:right;">
                        @unless($isReadOnlyAuditor)
                        <button type="button" class="saas-btn saas-btn-secondary" title="Edit course" data-admin-edit-record="{{ json_encode($course->only(['code', 'name', 'description', 'department_id'])) }}" data-edit-target="courseEditor" data-edit-url="{{ route('admin.courses.update', $course) }}"><i class="bi bi-pencil" aria-hidden="true"></i><span class="visually-hidden">Edit course</span></button>
                        <form action="{{ route('admin.courses.destroy', $course) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this course?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="saas-btn saas-btn-secondary" title="Delete course" style="color:var(--saas-danger);"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden">Delete course</span></button>
                        </form>
                        @else <span class="saas-text-muted">Read only</span> @endunless

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-mortarboard saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No courses found</div>
                        <p class="saas-text-muted" style="margin-bottom:20px; max-width:400px; margin-inline:auto;">Get started by adding your first degree program or course offering.</p>
                        @unless($isReadOnlyAuditor)<button class="saas-btn saas-btn-primary" onclick="openModal('addCourseModal')">
                            <i class="bi bi-plus-lg"></i> Add Course
                        </button>@endunless
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($courses->hasPages())
    <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:space-between; align-items:center;">
        <div class="saas-text-muted">
            Showing {{ $courses->firstItem() ?? 0 }} to {{ $courses->lastItem() ?? 0 }} of {{ $courses->total() }} results
        </div>
        <div>
            {{ $courses->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Add Course Modal -->
<div class="saas-modal-backdrop" id="addCourseModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s;">
    <div class="saas-card" style="width:100%; max-width:500px; transform:scale(0.95); transition:transform 0.2s;" id="addCourseCard">
        <div class="saas-card-header">
            <div class="saas-heading saas-heading-sm">Add New Course</div>
            <button onclick="closeModal('addCourseModal')" style="background:none; border:none; color:var(--saas-text-muted); cursor:pointer;"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.courses.store') }}" method="POST">
            @csrf
            <div class="saas-card-body">
                <div class="saas-form-group">
                    <label class="saas-label">Department</label>
                    <select name="department_id" class="saas-input saas-select" required>
                        <option value="">Select Department...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">Course Code</label>
                    <input type="text" name="code" class="saas-input" placeholder="e.g. BSCS" required>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">Program Name</label>
                    <input type="text" name="name" class="saas-input" placeholder="e.g. Bachelor of Science in Computer Science" required>
                </div>
                <div class="saas-form-group" style="margin-bottom:0;">
                    <label class="saas-label">Description (Optional)</label>
                    <textarea name="description" class="saas-input" rows="3" placeholder="Brief description..."></textarea>
                </div>
            </div>
            <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:flex-end; gap:12px; background:rgba(0,0,0,0.2);">
                <button type="button" class="saas-btn saas-btn-secondary" onclick="closeModal('addCourseModal')">Cancel</button>
                <button type="submit" class="saas-btn saas-btn-primary">Save Course</button>
            </div>
        </form>
    </div>
</div>
@unless($isReadOnlyAuditor)
    @include('partials.admin-record-editor', ['editorId' => 'courseEditor', 'recordType' => 'course', 'editorDepartments' => $departments])
@endunless
@endsection

@push('scripts')
<script @cspNonce>
    function openModal(id) {
        const modal = document.getElementById(id);
        const card = modal.querySelector('.saas-card');
        modal.style.display = 'flex';
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
