@extends('layouts.app')

@section('page-title', 'Student Details')

@section('content')
<x-back-link :href="route('teacher.students')" label="Back to Students" variant="teacher-ghost" style="margin-bottom: 20px;" />

<!-- Student Info Card -->
<div class="tch-card" style="margin-bottom: 24px;">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background: rgba(122,26,26,0.22); color: var(--portal-gold);">
                <i class="bi bi-person-fill"></i>
            </div>
            Student Information
        </div>
        <button onclick="openWarningModal({{ $student->id }}, '{{ $student->name }}')" 
                class="tch-btn" style="background: rgba(251,113,133,0.16); color: var(--portal-rose); border: 1px solid rgba(251,113,133,0.35);">
            <i class="bi bi-exclamation-triangle"></i> Send Warning
        </button>
    </div>
    <div style="padding: 20px 22px;">
        <div class="row g-3 align-items-center">
            <div class="col-md-2">
                <img src="{{ $student->profile_image ? (str_starts_with($student->profile_image, 'http') ? $student->profile_image : public_storage_url($student->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&background=800000&color=fff' }}"
                     class="profile-avatar-frame" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
            </div>
            <div class="col-md-5">
                <h4 style="margin: 0; font-weight: 700; color: var(--portal-text);">{{ $student->name }}</h4>
                <p style="margin: 4px 0; color: var(--portal-muted);">{{ $student->student_number }}</p>
                <p style="margin: 4px 0; color: var(--portal-muted);">{{ $student->email }}</p>
                <div style="margin-top: 8px;">
                    <span class="badge-course">{{ $student->course }}</span>
                    <span class="badge-year">{{ $student->year_level }}</span>
                </div>
            </div>
            <div class="col-md-5">
                <div class="tch-stats" style="grid-template-columns: repeat(2, 1fr); gap: 12px; margin: 0;">
                    <div class="tch-stat" style="padding: 12px 16px;">
                        <div class="tch-stat-val" style="font-size: 1.4rem; color: #16a34a;">{{ $totalPresent }}</div>
                        <div class="tch-stat-lbl">Present</div>
                    </div>
                    <div class="tch-stat" style="padding: 12px 16px;">
                        <div class="tch-stat-val" style="font-size: 1.4rem; color: #d97706;">{{ $totalLate }}</div>
                        <div class="tch-stat-lbl">Late</div>
                    </div>
                    <div class="tch-stat" style="padding: 12px 16px;">
                        <div class="tch-stat-val" style="font-size: 1.4rem; color: #dc2626;">{{ $totalAbsent }}</div>
                        <div class="tch-stat-lbl">Absent</div>
                    </div>
                    <div class="tch-stat" style="padding: 12px 16px;">
                        <div class="tch-stat-val" style="font-size: 1.4rem; color: var(--tch-primary);">{{ $rate }}%</div>
                        <div class="tch-stat-lbl">Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Teacher Notes -->
<div class="tch-card" style="margin-bottom: 24px;">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background: rgba(122,26,26,0.22); color: var(--portal-gold);">
                <i class="bi bi-journal-text"></i>
            </div>
            My Notes on {{ $student->name }}
        </div>
    </div>
    <div style="padding: 20px 22px;">
        <form action="{{ route('teacher.notes.store') }}" method="POST" style="margin-bottom: 20px;">
            @csrf
            <input type="hidden" name="student_id" value="{{ $student->id }}">
            <div class="form-group mb-2">
                <textarea name="note" class="form-control" rows="3" placeholder="Add a private note about this student..." required></textarea>
            </div>
            <button type="submit" class="tch-btn tch-btn-primary">Save Note</button>
        </form>

        @if(isset($notes) && $notes->count() > 0)
            <div class="notes-list">
                @foreach($notes as $note)
                    <div class="note-item" style="background: var(--portal-surface-2); border: 1px solid var(--portal-border-subtle); border-radius: 10px; padding: 15px; margin-bottom: 12px; position: relative;">
                        <p style="margin: 0 0 10px 0; font-size: 0.95rem; color: var(--portal-text); white-space: pre-wrap;">{{ $note->note }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small style="color: var(--portal-muted);">{{ $note->created_at->format('M j, Y h:i A') }}</small>
                            <form action="{{ route('teacher.notes.destroy', $note->id) }}" method="POST" onsubmit="return confirm('Delete this note?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" style="font-size: 0.8rem; text-decoration: none;">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align: center; color: var(--portal-muted); font-size: 0.9rem; padding: 20px 0;">
                No notes yet. Add one above.
            </div>
        @endif
    </div>
</div>

<!-- Attendance Records -->
<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background: rgba(122,26,26,0.22); color: var(--portal-gold);">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
            Attendance Records (My Subjects Only)
        </div>
        <div style="font-size: 0.8rem; color: var(--portal-muted);">
            Total: {{ $total }} records
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table class="tch-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Time</th>
                    <th>Excused</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        <td>{{ $record->date->format('M j, Y') }}</td>
                        <td>
                            <div>
                                <div style="font-weight: 600;">{{ $record->subject_code }}</div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">{{ $record->subject->name ?? 'N/A' }}</div>
                            </div>
                        </td>
                        <td>
                            @if($record->status === 'Present')
                                <span class="badge-present">Present</span>
                            @elseif($record->status === 'Late')
                                <span class="badge-late">Late</span>
                            @else
                                <span class="badge-absent">Absent</span>
                            @endif
                        </td>
                        <td>{{ $record->created_at->format('g:i A') }}</td>
                        <td>
                            @if($record->excused)
                                <span style="color: #16a34a; font-weight: 600;">
                                    <i class="bi bi-check-circle"></i> Yes
                                </span>
                                @if($record->excuse_note)
                                    <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 2px;">{{ $record->excuse_note }}</div>
                                @endif
                            @else
                                <span style="color: #94a3b8;">No</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <div>No attendance records found for your subjects</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Warning Modal -->
<div class="modal fade" id="warningModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-weight: 700;">
                    <i class="bi bi-exclamation-triangle" style="color: var(--portal-rose);"></i>
                    Send Warning to {{ $student->name }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="warningForm" method="POST" action="{{ route('teacher.student.warn', $student) }}">
                @csrf
                <div class="modal-body" style="padding: 20px;">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: var(--portal-text-soft);">Subject</label>
                        <select name="subject_code" class="tch-input" required>
                            <option value="">Select Subject</option>
                            @foreach($teacherSubjects as $subject)
                                <option value="{{ $subject->code }}">{{ $subject->code }} - {{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: var(--portal-text-soft);">Warning Type</label>
                        <select name="type" class="tch-input" required onchange="toggleCustomMessage()">
                            <option value="warning_2">2 Consecutive Absences</option>
                            <option value="warning_3">3+ Absences (Final Notice)</option>
                            <option value="warning_consecutive_3">3 Consecutive Absences (OSAS Readmission Required)</option>
                            <option value="custom">Custom Message</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="customMessageDiv" style="display: none;">
                        <label class="form-label" style="font-weight: 600; color: var(--portal-text-soft);">Custom Message</label>
                        <textarea name="message" class="tch-input" rows="3" placeholder="Enter your custom warning message..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 16px 20px;">
                    <button type="button" class="tch-btn tch-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tch-btn" style="background: rgba(251,113,133,0.2); color: var(--portal-rose); border: 1px solid rgba(251,113,133,0.35);">
                        <i class="bi bi-send"></i> Send Warning
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script @cspNonce>
function openWarningModal(studentId, studentName) {
    new bootstrap.Modal(document.getElementById('warningModal')).show();
}

function toggleCustomMessage() {
    const type = document.querySelector('select[name="type"]').value;
    const customDiv = document.getElementById('customMessageDiv');
    customDiv.style.display = type === 'custom' ? 'block' : 'none';
}
</script>
@endsection
