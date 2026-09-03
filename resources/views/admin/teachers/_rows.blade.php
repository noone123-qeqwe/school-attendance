@forelse($teachers as $teacher)
<tr>
    <td style="width:40px;"><input type="checkbox" class="teacher-checkbox" name="selected_teachers[]" value="{{ $teacher->id }}" style="accent-color:var(--gold);"></td>
    <td data-label="Instructor">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ $teacher->profile_image ? (str_starts_with($teacher->profile_image, 'http') ? $teacher->profile_image : asset('storage/'.$teacher->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($teacher->name).'&background=900000&color=fff' }}"
                 style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:1.5px solid rgba(207,164,111,0.5); box-shadow:0 2px 6px rgba(0,0,0,0.3);">
            <div>
                <div style="font-weight:600;font-size:0.875rem;color:#f3e7cd;letter-spacing:0.2px;display:flex;align-items:center;gap:6px;">
                    {{ $teacher->name }}
                    @if(!$teacher->isActive())
                        <span class="badge" style="background:rgba(239,68,68,0.2);color:#f87171;border:1px solid rgba(239,68,68,0.35);font-size:0.65rem;padding:2px 6px;">Deactivated</span>
                    @endif
                </div>
                <div style="font-size:0.75rem;color:#b39b82;">{{ $teacher->email }}</div>
            </div>
        </div>
    </td>
    <td data-label="Employee ID"><span style="font-family:monospace; color: var(--gold); font-weight: 600; font-size: 0.85rem;">{{ $teacher->employee_id ?? '—' }}</span></td>
    <td data-label="Department">
        <span style="background: rgba(96, 165, 250, 0.12); color: #93c5fd; border: 1px solid rgba(147, 197, 253, 0.25); font-weight: 600; padding: 3px 8px; border-radius: 6px; font-size: 0.775rem;">
            {{ $teacher->department ?? 'General' }}
        </span>
    </td>
    <td data-label="Position / Specialization">
        <div style="font-size:0.85rem; color:#f3e7cd; font-weight:500;">{{ $teacher->position ?? 'Instructor' }}</div>
        @if($teacher->specialization)
            <div style="font-size:0.75rem; color:#b39b82;">{{ $teacher->specialization }}</div>
        @endif
    </td>
    <td data-label="Actions">
        <div class="d-flex gap-1 align-items-center">
            <a href="{{ route('admin.teacher.edit', $teacher) }}" class="btn btn-sm" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; border: 1px solid rgba(207,164,111,0.25); background:rgba(207,164,111,0.06); color: var(--gold); border-radius:6px; transition:all 0.2s;" title="Edit Instructor">
                <i class="bi bi-pencil"></i>
            </a>
            @if($teacher->isActive())
            <form action="{{ route('admin.teacher.deactivate', $teacher) }}" method="POST" onsubmit="return confirm('Deactivate instructor {{ addslashes($teacher->name) }}? They will not be able to log in or create attendance sessions.')" style="margin:0;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-sm" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; border: 1px solid rgba(245,158,11,0.25); background:rgba(245,158,11,0.06); color: #fbbf24; border-radius:6px; transition:all 0.2s;" title="Deactivate Account">
                    <i class="bi bi-person-x"></i>
                </button>
            </form>
            @else
            <form action="{{ route('admin.teacher.reactivate', $teacher) }}" method="POST" onsubmit="return confirm('Reactivate instructor account for {{ addslashes($teacher->name) }}?')" style="margin:0;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-sm" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; border: 1px solid rgba(52,211,153,0.25); background:rgba(52,211,153,0.06); color: #34d399; border-radius:6px; transition:all 0.2s;" title="Reactivate Account">
                    <i class="bi bi-person-check"></i>
                </button>
            </form>
            @endif
            <form action="{{ route('admin.teacher.destroy', $teacher) }}" method="POST" onsubmit="return confirm('Delete instructor {{ addslashes($teacher->name) }}?')" style="margin:0;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; border: 1px solid rgba(239,68,68,0.25); background:rgba(239,68,68,0.06); color: #f87171; border-radius:6px; transition:all 0.2s;" title="Delete Instructor">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6" class="text-center" style="padding: 48px 20px;">
        <i class="bi bi-person-workspace" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.5; color:#b39b82;"></i>
        <div style="font-size:1.1rem; margin-bottom:8px; color:#f3e7cd; font-weight: 600;">No instructors found</div>
        <p style="margin-bottom:20px; color:#b39b82;">There are no instructors matching your criteria.</p>
    </td>
</tr>
@endforelse
