@forelse($students as $student)
@php $absences = $student->attendances->where('status','Absent')->count(); @endphp
<tr>
    <td style="width:40px;"><input type="checkbox" class="student-checkbox" name="selected_students[]" value="{{ $student->id }}" style="accent-color:var(--gold);"></td>
    <td data-label="Student">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ $student->profile_image ? (str_starts_with($student->profile_image, 'http') ? $student->profile_image : asset('storage/'.$student->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&background=900000&color=fff' }}"
                 style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:1.5px solid rgba(207,164,111,0.5); box-shadow:0 2px 6px rgba(0,0,0,0.3);">
            <div>
                <div style="font-weight:600;font-size:0.875rem;color:#f3e7cd;letter-spacing:0.2px;">{{ $student->name }}</div>
                <div style="font-size:0.75rem;color:#b39b82;">{{ $student->email }}</div>
            </div>
        </div>
    </td>
    <td data-label="ID"><span style="font-family:monospace; color: var(--gold); font-weight: 600; font-size: 0.85rem;">{{ $student->student_number ?? '—' }}</span></td>
    <td data-label="Course &amp; Year">
        @if($student->course)
            <span style="background: rgba(96, 165, 250, 0.12); color: #93c5fd; border: 1px solid rgba(147, 197, 253, 0.25); font-weight: 600; padding: 3px 8px; border-radius: 6px; font-size: 0.775rem;">
                {{ $student->course }}
            </span>
        @endif
        <span style="margin-left:4px; color: #b39b82; font-size: 0.8rem;">Y{{ $student->year_level ?? '—' }} - S{{ $student->semester ?? '—' }}</span>
    </td>
    <td data-label="Bound Device">
        @if($student->deviceBinding)
            <span style="display:inline-flex; align-items:center; background:rgba(74,222,128,0.1); color:#86efac; border:1px solid rgba(134,239,172,0.25); padding:3px 8px; border-radius:6px; font-size:0.775rem; font-weight:500;">
                <i class="bi bi-phone me-1"></i>{{ $student->deviceBinding->device_name ?: 'Bound Device' }}
            </span>
        @else
            <span style="color: rgba(179,155,130,0.45); font-size: 0.8rem;">Unbound</span>
        @endif
    </td>
    <td data-label="Absences">
        @if($absences > 0)
            <span style="display:inline-block; min-width:24px; text-align:center; padding:2px 8px; border-radius:99px; font-size:0.75rem; font-weight:700; background:{{ $absences >= 3 ? 'rgba(239,68,68,0.2)' : 'rgba(234,179,8,0.2)' }}; color:{{ $absences >= 3 ? '#f87171' : '#fde047' }}; border:1px solid {{ $absences >= 3 ? 'rgba(239,68,68,0.35)' : 'rgba(234,179,8,0.35)' }};">
                {{ $absences }}
            </span>
        @else
            <span style="color: rgba(179,155,130,0.4);">—</span>
        @endif
    </td>
    <td data-label="Actions">
        <div class="d-flex gap-1 align-items-center">
            <a href="{{ route('admin.student', $student->id) }}" class="btn btn-sm" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; border: 1px solid rgba(207,164,111,0.25); background:rgba(207,164,111,0.06); color: var(--gold); border-radius:6px; transition:all 0.2s;" title="View Details">
                <i class="bi bi-eye"></i>
            </a>
            <a href="{{ route('admin.student.edit', $student->id) }}" class="btn btn-sm" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; border: 1px solid rgba(207,164,111,0.25); background:rgba(207,164,111,0.06); color: var(--gold); border-radius:6px; transition:all 0.2s;" title="Edit Student">
                <i class="bi bi-pencil"></i>
            </a>
            <form action="{{ route('admin.student.reset_device', $student->id) }}" method="POST" onsubmit="return confirm('Reset device binding for {{ addslashes($student->name) }}?')" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-sm" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; border: 1px solid rgba(74,222,128,0.25); background:rgba(74,222,128,0.06); color: #4ade80; border-radius:6px; transition:all 0.2s;" title="Reset Device Binding">
                    <i class="bi bi-phone"></i>
                </button>
            </form>
            <form action="{{ route('admin.student.destroy', $student->id) }}" method="POST" onsubmit="return confirm('Delete student {{ addslashes($student->name) }}?')" style="margin:0;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; border: 1px solid rgba(239,68,68,0.25); background:rgba(239,68,68,0.06); color: #f87171; border-radius:6px; transition:all 0.2s;" title="Delete Student">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="7" class="text-center" style="padding: 48px 20px;">
        <i class="bi bi-people" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5; color:#b39b82;"></i>
        <div style="font-size:1.1rem; margin-bottom:8px; color:#f3e7cd; font-weight: 600;">No students found</div>
        <p style="margin-bottom:20px; color:#b39b82;">There are no students matching your criteria.</p>
    </td>
</tr>
@endforelse
