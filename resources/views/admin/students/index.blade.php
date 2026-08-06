@extends('layouts.admin_premium')

@section('title', 'Students')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h1 class="saas-heading saas-heading-lg mb-1">Students</h1>
        <p class="saas-text-muted m-0">Manage student enrollments and records.</p>
    </div>
    
    <div class="d-flex gap-2">
        <a href="{{ route('admin.students.preview', request()->query()) }}" class="btn btn-outline" style="border-color: rgba(207,164,111,0.3); color: var(--gold);">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
        <button type="button" class="btn btn-outline" style="border-color: rgba(207,164,111,0.3); color: var(--gold);" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload"></i> Import CSV
        </button>
        <a href="{{ route('admin.student.create') }}" class="btn btn-primary" style="background: var(--gold); border: none;">
            <i class="bi bi-plus-lg"></i> Add Student
        </a>
    </div>
</div>

<x-card title="Student Directory" icon="bi bi-people">
    <x-slot name="headerActions">
        <form method="GET" action="{{ route('admin.students') }}" class="d-flex gap-3 align-items-center flex-wrap m-0">
            <div class="saas-search" style="width:250px;">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="saas-search-input" placeholder="Name or Student ID" value="{{ request('search') }}">
            </div>
            
            <select name="course" class="form-select" style="width:140px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;">
                <option value="">All Courses</option>
                @foreach(['BSCS','BSIT','BSIS'] as $c)
                <option value="{{ $c }}" {{ request('course')==$c?'selected':'' }}>{{ $c }}</option>
                @endforeach
            </select>
            
            <select name="year_level" class="form-select" style="width:140px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;">
                <option value="">All Years</option>
                @foreach([1,2,3,4] as $y)
                <option value="{{ $y }}" {{ request('year_level')==$y?'selected':'' }}>Year {{ $y }}</option>
                @endforeach
            </select>
            
            <select name="semester" class="form-select" style="width:140px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;">
                <option value="">All Semesters</option>
                <option value="1" {{ request('semester')=='1'?'selected':'' }}>1st Sem</option>
                <option value="2" {{ request('semester')=='2'?'selected':'' }}>2nd Sem</option>
            </select>
            
            <button type="submit" class="btn btn-primary" style="background: var(--gold); border: none;">
                <i class="bi bi-funnel"></i> Filter
            </button>
            
            @if(request()->hasAny(['search','course','year_level','semester']))
            <a href="{{ route('admin.students') }}" class="btn btn-outline" style="color: #f87171; border-color: rgba(239,68,68,0.3);">
                Clear
            </a>
            @endif
        </form>
    </x-slot>
    
    <x-data-table :headers="['Student', 'ID', 'Course & Year', 'Absences', 'Actions']">
        @forelse($students as $student)
        @php $absences = $student->attendances->where('status','Absent')->count(); @endphp
        <tr>
            <td data-label="Student">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ $student->profile_image ? (str_starts_with($student->profile_image, 'http') ? $student->profile_image : asset('storage/'.$student->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&background=900000&color=fff' }}"
                         style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid var(--gold);">
                    <div>
                        <div style="font-weight:600;font-size:0.875rem;color:#f3e7cd;">{{ $student->name }}</div>
                        <div style="font-size:0.75rem;color:#b39b82;">{{ $student->email }}</div>
                    </div>
                </div>
            </td>
            <td data-label="ID"><span style="font-family:monospace; color: var(--gold);">{{ $student->student_number }}</span></td>
            <td data-label="Course & Year">
                <span style="color: #60a5fa; font-weight: 600;">{{ $student->course }}</span>
                <span style="margin-left:4px; color: #b39b82;">Y{{ $student->year_level }} - S{{ $student->semester }}</span>
            </td>
            <td data-label="Absences">
                @if($absences > 0)
                    <x-badge :type="$absences >= 3 ? 'absent' : 'late'">{{ $absences }}</x-badge>
                @else
                    <span style="color: rgba(179,155,130,0.5);">—</span>
                @endif
            </td>
            <td data-label="Actions">
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.student', $student->id) }}" class="btn btn-sm btn-outline" style="border-color: rgba(207,164,111,0.3); color: var(--gold);" title="View">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('admin.student.edit', $student->id) }}" class="btn btn-sm btn-outline" style="border-color: rgba(207,164,111,0.3); color: var(--gold);" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="{{ route('admin.student.destroy', $student->id) }}" method="POST" onsubmit="return confirm('Delete {{ addslashes($student->name) }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline" style="border-color: rgba(239,68,68,0.3); color: #f87171;" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-center" style="padding: 48px 20px;">
                <i class="bi bi-people" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5; color:#b39b82;"></i>
                <div style="font-size:1.1rem; margin-bottom:8px; color:#f3e7cd; font-weight: 600;">No students found</div>
                <p style="margin-bottom:20px; color:#b39b82;">There are no students matching your criteria.</p>
            </td>
        </tr>
        @endforelse
    </x-data-table>
    
    @if($students->hasPages())
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <div style="color: #b39b82; font-size: 0.85rem;">
            Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of {{ $students->total() }} results
        </div>
        <div>
            {{ $students->links('pagination::bootstrap-4') }}
        </div>
    </div>
    @endif
</x-card>

<!-- Import CSV Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background: #1a100c; border: 1px solid rgba(207,164,111,0.2);">
            <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header" style="border-bottom: 1px solid rgba(207,164,111,0.1);">
                    <h5 class="modal-title" style="color: #f3e7cd;"><i class="bi bi-upload me-2 text-warning"></i> Import Students</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info" style="background: rgba(14,165,233,0.1); border-color: rgba(14,165,233,0.2); color: #bae6fd; font-size: 0.85rem;">
                        <strong>Format:</strong> CSV file with headers: <br>
                        <code>name, email, student_number, year_level, section</code>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: #b39b82;">CSV File</label>
                        <input type="file" name="csv_file" class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd;" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(207,164,111,0.1);">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal" style="color: #b39b82;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--gold); border: none;">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
