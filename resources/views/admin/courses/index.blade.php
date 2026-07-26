@extends('layouts.admin_premium')

@section('title', 'Courses')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Courses / Programs</h1>
        <p class="saas-text-muted" style="margin:0;">Manage degree programs and course offerings.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button class="saas-btn saas-btn-secondary">
            <i class="bi bi-download"></i> Export
        </button>
        <button class="saas-btn saas-btn-primary" onclick="openModal('addCourseModal')">
            <i class="bi bi-plus-lg"></i> Add Course
        </button>
    </div>
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <div class="saas-card-header" style="gap:16px; flex-wrap:wrap;">
        <div class="saas-search" style="width:250px;">
            <i class="bi bi-search"></i>
            <input type="text" class="saas-search-input" placeholder="Search courses...">
        </div>
        
        <div style="display:flex; gap:12px; align-items:center;">
            <select class="saas-input saas-select" style="width:180px; padding:6px 30px 6px 12px;">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->code }}</option>
                @endforeach
            </select>
            
            <button class="saas-btn saas-btn-secondary" style="padding:6px 12px;">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </div>
    
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" style="accent-color:var(--saas-primary);"></th>
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
                    <td><input type="checkbox" style="accent-color:var(--saas-primary);"></td>
                    <td><span class="saas-badge saas-badge-info" style="font-family:monospace;font-size:0.8rem;">{{ $course->code }}</span></td>
                    <td style="font-weight:500;">{{ $course->name }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:6px;height:6px;border-radius:50%;background:var(--saas-gold);"></div>
                            <span class="saas-text-muted">{{ $course->department->name ?? 'Unassigned' }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="saas-badge saas-badge-default">{{ $course->sections()->count() ?? 0 }} active</span>
                    </td>
                    <td style="text-align:right;">
                        <button class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="saas-btn saas-btn-secondary" style="padding:4px 8px; color:var(--saas-danger);" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-mortarboard saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No courses found</div>
                        <p class="saas-text-muted" style="margin-bottom:20px; max-width:400px; margin-inline:auto;">Get started by adding your first degree program or course offering.</p>
                        <button class="saas-btn saas-btn-primary" onclick="openModal('addCourseModal')">
                            <i class="bi bi-plus-lg"></i> Add Course
                        </button>
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
<div id="addCourseModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:100; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s;">
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
@endsection

@push('scripts')
<script>
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
