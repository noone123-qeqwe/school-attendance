@extends('layouts.admin_premium')

@section('title', 'Students')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Students</h1>
        <p class="saas-text-muted" style="margin:0;">Manage student enrollments and records.</p>
    </div>
    
    <div style="display:flex; gap:12px;">
        <a href="{{ route('admin.students.preview', request()->query()) }}" class="saas-btn saas-btn-secondary">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
        <a href="{{ route('admin.student.create') }}" class="saas-btn saas-btn-primary">
            <i class="bi bi-plus-lg"></i> Add Student
        </a>
    </div>
</div>

<div class="saas-card" style="margin-bottom:24px;">
    <form method="GET" action="{{ route('admin.students') }}" class="saas-card-header" style="gap:16px; flex-wrap:wrap; display:flex;">
        <div class="saas-search" style="width:250px;">
            <i class="bi bi-search"></i>
            <input type="text" name="search" class="saas-search-input" placeholder="Name or Student ID" value="{{ request('search') }}">
        </div>
        
        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <select name="course" class="saas-input saas-select" style="width:140px; padding:6px 30px 6px 12px;">
                <option value="">All Courses</option>
                @foreach(['BSCS','BSIT','BSIS'] as $c)
                <option value="{{ $c }}" {{ request('course')==$c?'selected':'' }}>{{ $c }}</option>
                @endforeach
            </select>
            
            <select name="year_level" class="saas-input saas-select" style="width:140px; padding:6px 30px 6px 12px;">
                <option value="">All Years</option>
                @foreach([1,2,3,4] as $y)
                <option value="{{ $y }}" {{ request('year_level')==$y?'selected':'' }}>Year {{ $y }}</option>
                @endforeach
            </select>
            
            <select name="semester" class="saas-input saas-select" style="width:140px; padding:6px 30px 6px 12px;">
                <option value="">All Semesters</option>
                <option value="1" {{ request('semester')=='1'?'selected':'' }}>1st Sem</option>
                <option value="2" {{ request('semester')=='2'?'selected':'' }}>2nd Sem</option>
            </select>
            
            <button type="submit" class="saas-btn saas-btn-secondary" style="padding:6px 12px;">
                <i class="bi bi-funnel"></i> Filter
            </button>
            
            @if(request()->hasAny(['search','course','year_level','semester']))
            <a href="{{ route('admin.students') }}" class="saas-btn saas-btn-secondary" style="padding:6px 12px; color:var(--saas-danger);">
                Clear
            </a>
            @endif
        </div>
    </form>
    
    <div class="saas-table-container" style="border:none; border-radius:0;">
        <table class="saas-table">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" style="accent-color:var(--saas-primary);"></th>
                    <th>Student</th>
                    <th>ID</th>
                    <th>Course & Year</th>
                    <th>Absences</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                @php $absences = $student->attendances->where('status','Absent')->count(); @endphp
                <tr>
                    <td><input type="checkbox" style="accent-color:var(--saas-primary);"></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="{{ $student->profile_image ? asset('storage/'.$student->profile_image) : 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&background=900000&color=fff' }}"
                                 style="width:36px;height:36px;border-radius:var(--saas-radius-sm);object-fit:cover;border:1px solid var(--saas-border);">
                            <div>
                                <div style="font-weight:600;font-size:0.875rem;">{{ $student->name }}</div>
                                <div class="saas-text-muted" style="font-size:0.75rem;">{{ $student->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="saas-badge saas-badge-default" style="font-family:monospace;">{{ $student->student_number }}</span></td>
                    <td>
                        <span class="saas-badge saas-badge-info">{{ $student->course }}</span>
                        <span class="saas-badge saas-badge-default" style="margin-left:4px;">Y{{ $student->year_level }} - S{{ $student->semester }}</span>
                    </td>
                    <td>
                        @if($absences > 0)
                            <span class="saas-badge {{ $absences >= 3 ? 'saas-badge-danger' : 'saas-badge-warning' }}">{{ $absences }}</span>
                        @else
                            <span class="saas-text-muted">—</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        <div style="display:flex; gap:6px; justify-content:flex-end;">
                            <a href="{{ route('admin.student', $student->id) }}" class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.student.edit', $student->id) }}" class="saas-btn saas-btn-secondary" style="padding:4px 8px;" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.student.destroy', $student->id) }}" method="POST" onsubmit="return confirm('Delete {{ addslashes($student->name) }}?')">
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
                    <td colspan="6" style="text-align:center; padding:48px 20px;">
                        <i class="bi bi-people saas-text-muted" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5;"></i>
                        <div class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No students found</div>
                        <p class="saas-text-muted" style="margin-bottom:20px; max-width:400px; margin-inline:auto;">There are no students matching your criteria.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($students->hasPages())
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
@endsection
