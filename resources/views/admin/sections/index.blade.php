@extends('layouts.app')

@section('title', 'Sections')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Sections & Blocks</h1>
        <p class="saas-text-muted" style="margin:0;">Manage student groups and class sections.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button type="button" class="saas-btn saas-btn-secondary" onclick="openModal('importSectionModal')">
            <i class="bi bi-upload"></i> Import CSV
        </button>
        <button class="saas-btn saas-btn-primary" onclick="openModal('addSectionModal')">
            <i class="bi bi-plus-lg"></i> Add Section
        </button>
    </div>
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <div class="saas-card-header" style="gap:16px; flex-wrap:wrap;">
        <div class="saas-search" style="width:250px;">
            <i class="bi bi-search"></i>
            <input type="text" class="saas-search-input" placeholder="Search sections...">
        </div>
        
        <div style="display:flex; gap:12px; align-items:center;">
            <select class="saas-input saas-select" style="width:160px; padding:6px 30px 6px 12px;">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}">{{ $course->code }}</option>
                @endforeach
            </select>
            
            <select name="bulk_action" class="saas-input saas-select" style="width:140px; padding:6px 30px 6px 12px;">
                <option value="">Bulk Actions</option>
                <option value="delete">Delete Selected</option>
                <option value="export">Export Selected</option>
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
                    <th style="width:40px;"><input type="checkbox" style="accent-color:var(--saas-primary);" onclick="document.querySelectorAll('.section-checkbox').forEach(c => c.checked = this.checked)"></th>
                    <th>Section Name</th>
                    <th>Course Code</th>
                    <th>Year Level</th>
                    <th>Students Enrolled</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sections as $section)
                <tr>
                    <td><input type="checkbox" class="section-checkbox" name="selected_sections[]" value="{{ $section->id }}" style="accent-color:var(--saas-primary);"></td>
                    <td style="font-weight:500;">
                        <span class="saas-badge saas-badge-default" style="font-family:monospace;font-size:0.85rem;">{{ $section->name }}</span>
                    </td>
                    <td>
                        <span class="saas-text-muted">{{ $section->course->code ?? 'Unassigned' }}</span>
                    </td>
                    <td>
                        <span class="saas-badge saas-badge-info">Year {{ $section->year_level }}</span>
                    </td>
                    <td>
                        <span class="saas-text-muted">{{ $section->students()->count() ?? 0 }} students</span>
                    </td>
                    <td style="text-align:right;">
                        <a href="{{ route('admin.section.history', $section) }}" class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="History">
                            <i class="bi bi-clock-history"></i>
                        </a>
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
                        <i class="bi bi-diagram-3 saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No sections found</div>
                        <p class="saas-text-muted" style="margin-bottom:20px; max-width:400px; margin-inline:auto;">Add sections to group your students into manageable blocks.</p>
                        <button class="saas-btn saas-btn-primary" onclick="openModal('addSectionModal')">
                            <i class="bi bi-plus-lg"></i> Add Section
                        </button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($sections->hasPages())
    <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:space-between; align-items:center;">
        <div class="saas-text-muted">
            Showing {{ $sections->firstItem() ?? 0 }} to {{ $sections->lastItem() ?? 0 }} of {{ $sections->total() }} results
        </div>
        <div>
            {{ $sections->links() }}
        </div>
    </div>
    @endif
</div>

<!-- Add Section Modal -->
<div id="addSectionModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:100; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s;">
    <div class="saas-card" style="width:100%; max-width:400px; transform:scale(0.95); transition:transform 0.2s;" id="addSectionCard">
        <div class="saas-card-header">
            <div class="saas-heading saas-heading-sm">Add New Section</div>
            <button onclick="closeModal('addSectionModal')" style="background:none; border:none; color:var(--saas-text-muted); cursor:pointer;"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.sections.store') }}" method="POST">
            @csrf
            <div class="saas-card-body">
                <div class="saas-form-group">
                    <label class="saas-label">Course / Program</label>
                    <select name="course_id" class="saas-input saas-select" required>
                        <option value="">Select Course...</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="saas-form-group">
                    <label class="saas-label">Year Level</label>
                    <select name="year_level" class="saas-input saas-select" required>
                        <option value="1">Year 1</option>
                        <option value="2">Year 2</option>
                        <option value="3">Year 3</option>
                        <option value="4">Year 4</option>
                        <option value="5">Year 5</option>
                    </select>
                </div>
                <div class="saas-form-group" style="margin-bottom:0;">
                    <label class="saas-label">Section Name/Block</label>
                    <input type="text" name="name" class="saas-input" placeholder="e.g. BSCS-1A" required>
                </div>
            </div>
            <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:flex-end; gap:12px; background:rgba(0,0,0,0.2);">
                <button type="button" class="saas-btn saas-btn-secondary" onclick="closeModal('addSectionModal')">Cancel</button>
                <button type="submit" class="saas-btn saas-btn-primary">Save Section</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Section Modal -->
<div id="importSectionModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:100; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s;">
    <div class="saas-card" style="width:100%; max-width:400px; transform:scale(0.95); transition:transform 0.2s;" id="importSectionCard">
        <div class="saas-card-header">
            <div class="saas-heading saas-heading-sm">Import Sections</div>
            <button onclick="closeModal('importSectionModal')" style="background:none; border:none; color:var(--saas-text-muted); cursor:pointer;"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.sections.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="saas-card-body">
                <div class="saas-form-group">
                    <label class="saas-label">CSV File</label>
                    <input type="file" name="csv_file" class="saas-input" accept=".csv" required>
                    <div style="font-size:0.75rem; color:var(--saas-text-muted); margin-top:8px;">Format: <code>name, course_id, year_level</code></div>
                </div>
            </div>
            <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:flex-end; gap:12px; background:rgba(0,0,0,0.2);">
                <button type="button" class="saas-btn saas-btn-secondary" onclick="closeModal('importSectionModal')">Cancel</button>
                <button type="submit" class="saas-btn saas-btn-primary">Import</button>
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
