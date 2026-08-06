@extends('layouts.app')

@section('title', 'Subjects')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Subjects / Curriculum</h1>
        <p class="saas-text-muted" style="margin:0;">Manage course subjects, instructors, and prerequisites.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <button class="saas-btn saas-btn-primary" onclick="openModal('addSubjectModal')">
            <i class="bi bi-plus-lg"></i> Add Subject
        </button>
    </div>
</div>

<x-card type="section">
    <x-slot:title>Subjects List</x-slot:title>
    <x-slot:headerActions>
        <form method="GET" action="{{ route('admin.subjects') }}" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <div class="saas-search" style="width:200px;">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="saas-search-input" placeholder="Search code..." value="{{ request('search') }}">
            </div>
            <select name="course" class="saas-input saas-select" style="width:120px; padding:6px 30px 6px 12px;">
                <option value="">Course (All)</option>
                <option value="BSCS" {{ request('course')=='BSCS'?'selected':'' }}>BSCS</option>
                <option value="BSIT" {{ request('course')=='BSIT'?'selected':'' }}>BSIT</option>
                <option value="BSIS" {{ request('course')=='BSIS'?'selected':'' }}>BSIS</option>
            </select>
            <select name="year" class="saas-input saas-select" style="width:110px; padding:6px 30px 6px 12px;">
                <option value="">Year (All)</option>
                @foreach([1,2,3,4,5] as $y)
                    <option value="{{ $y }}" {{ request('year')==$y?'selected':'' }}>Year {{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="ent-btn ent-btn-sm ent-btn-secondary"><i class="bi bi-funnel"></i> Filter</button>
            @if(request()->hasAny(['search','course','year']))
                <a href="{{ route('admin.subjects') }}" class="ent-btn ent-btn-sm ent-btn-ghost text-danger">Clear</a>
            @endif
        </form>
    </x-slot:headerActions>
    
    <div class="ent-scroll-x" style="margin: -20px;">
        <table class="ent-table" style="min-width: 800px; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" style="accent-color:var(--ent-primary);"></th>
                    <th>Subject Code & Name</th>
                    <th>Course</th>
                    <th>Year/Sem</th>
                    <th>Instructor</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $subject)
                <tr>
                    <td><input type="checkbox" style="accent-color:var(--ent-primary);"></td>
                    <td data-label="Subject Code & Name">
                        <div style="font-weight:600; font-family:monospace; color:var(--ent-gold);">{{ $subject->code }}</div>
                        <div style="font-size:0.85rem; font-weight:500;">{{ $subject->name }}</div>
                        @if($subject->description)
                            <div class="ent-text-muted" style="font-size:0.75rem;">{{ Str::limit($subject->description, 40) }}</div>
                        @endif
                    </td>
                    <td data-label="Course">
                        <span class="ent-badge ent-badge-info">{{ $subject->course }}</span>
                    </td>
                    <td data-label="Year/Sem">
                        <span class="ent-badge ent-badge-neutral">Y{{ $subject->year }} - S{{ $subject->semester }}</span>
                    </td>
                    <td data-label="Instructor">
                        @if($subject->instructorUser)
                            <div style="display:flex;align-items:center;gap:6px;">
                                <i class="bi bi-person-workspace ent-text-muted"></i>
                                <span style="font-size:0.85rem;">{{ $subject->instructorUser->name }}</span>
                            </div>
                        @else
                            <span class="ent-text-muted" style="font-style:italic;">Unassigned</span>
                        @endif
                    </td>
                    <td data-label="Actions" style="text-align:right;">
                        <a href="{{ route('admin.enrollments.index', $subject) }}" class="ent-btn ent-btn-xs ent-btn-ghost" style="color:var(--ent-primary);" title="Manage Roster">
                            <i class="bi bi-people"></i>
                        </a>
                        <button class="ent-btn ent-btn-xs ent-btn-ghost" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('admin.subjects.destroy', $subject->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this subject?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="ent-btn ent-btn-xs ent-btn-ghost" style="color:var(--ent-danger);" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="ent-empty" style="padding:48px 20px;">
                            <div class="ent-empty-icon" style="width:64px;height:64px;font-size:2rem; margin-bottom:16px;">
                                <i class="bi bi-book-half"></i>
                            </div>
                            <div class="ent-empty-title">No subjects found</div>
                            <div class="ent-empty-text">There are no subjects matching your filters.</div>
                            <button class="ent-btn ent-btn-primary" onclick="openModal('addSubjectModal')" style="margin-top:16px;">
                                <i class="bi bi-plus-lg"></i> Add Subject
                            </button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($subjects->hasPages())
    <div style="border-top:1px solid var(--ent-border); padding-top:16px; margin-top:16px; display:flex; justify-content:space-between; align-items:center;">
        <div class="ent-text-muted" style="font-size:0.85rem;">
            Showing {{ $subjects->firstItem() ?? 0 }} to {{ $subjects->lastItem() ?? 0 }} of {{ $subjects->total() }} results
        </div>
        <div>
            {{ $subjects->links() }}
        </div>
    </div>
    @endif
</x-card>

<!-- Add Subject Modal -->
<div id="addSubjectModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:100; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s;">
    <div class="saas-card" style="width:100%; max-width:500px; transform:scale(0.95); transition:transform 0.2s;" id="addSubjectCard">
        <div class="saas-card-header">
            <div class="saas-heading saas-heading-sm">Add New Subject</div>
            <button onclick="closeModal('addSubjectModal')" style="background:none; border:none; color:var(--saas-text-muted); cursor:pointer;"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="{{ route('admin.subjects.store') }}" method="POST">
            @csrf
            <div class="saas-card-body">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="saas-form-group">
                        <label class="saas-label">Subject Code</label>
                        <input type="text" name="code" class="saas-input" placeholder="e.g. IT311" required>
                    </div>
                    <div class="saas-form-group">
                        <label class="saas-label">Course</label>
                        <select name="course" class="saas-input saas-select" required>
                            <option value="BSCS">BSCS</option>
                            <option value="BSIT">BSIT</option>
                            <option value="BSIS">BSIS</option>
                        </select>
                    </div>
                </div>
                
                <div class="saas-form-group">
                    <label class="saas-label">Subject Name</label>
                    <input type="text" name="name" class="saas-input" placeholder="e.g. Advanced Database Systems" required>
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="saas-form-group">
                        <label class="saas-label">Year Level</label>
                        <select name="year" class="saas-input saas-select" required>
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                    </div>
                    <div class="saas-form-group">
                        <label class="saas-label">Semester</label>
                        <select name="semester" class="saas-input saas-select" required>
                            <option value="1">1st Semester</option>
                            <option value="2">2nd Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                </div>
                
                <div class="saas-form-group" style="margin-bottom:0;">
                    <label class="saas-label">Description (Optional)</label>
                    <textarea name="description" class="saas-input" rows="3"></textarea>
                </div>
            </div>
            <div class="saas-card-body" style="border-top:1px solid var(--saas-border); display:flex; justify-content:flex-end; gap:12px; background:rgba(0,0,0,0.2);">
                <button type="button" class="saas-btn saas-btn-secondary" onclick="closeModal('addSubjectModal')">Cancel</button>
                <button type="submit" class="saas-btn saas-btn-primary">Save Subject</button>
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
