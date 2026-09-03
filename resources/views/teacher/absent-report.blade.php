@extends('layouts.app')

@section('page-title', 'Absent Report')

@section('content')
<div class="tch-stats">
    <div class="tch-stat">
        <div class="tch-stat-val">{{ $absentRecords->count() }}</div>
        <div class="tch-stat-lbl">Absent Today</div>
    </div>
    <div class="tch-stat">
        <div class="tch-stat-val">{{ $teacherSubjects->count() }}</div>
        <div class="tch-stat-lbl">My Subjects</div>
    </div>
    <div class="tch-stat">
        <div class="tch-stat-val">{{ $absentRecords->where('excused', false)->count() }}</div>
        <div class="tch-stat-lbl">Unexcused</div>
    </div>
    <div class="tch-stat">
        <div class="tch-stat-val">{{ $absentRecords->where('excused', true)->count() }}</div>
        <div class="tch-stat-lbl">Excused</div>
    </div>
</div>

<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon">
                <i class="bi bi-person-x-fill"></i>
            </div>
            Absent Students Report (My Subjects)
        </div>
    </div>

    <!-- Date Filter -->
    <div style="padding: 16px 22px; border-bottom: 1px solid rgba(255,215,145,0.08);">
        <form method="GET" class="filter-form">
            <div class="row g-3">
                <div class="col-md-3">
                    <label style="font-size: 0.8rem; font-weight: 600; color: #b39b82; margin-bottom: 4px; display: block;">Date</label>
                    <input type="date" name="date" value="{{ $date }}" class="adm-input" style="width: 100%; padding: 8px 12px; background: rgba(0,0,0,0.3); color: #f3e7cd; border: 1px solid rgba(255,215,145,0.15); border-radius: 8px;">
                </div>
                <div class="col-md-3">
                    <label style="font-size: 0.8rem; font-weight: 600; color: #b39b82; margin-bottom: 4px; display: block;">Year Level</label>
                    <select name="year_level" class="adm-input" style="width: 100%; padding: 8px 12px; background: rgba(0,0,0,0.3); color: #f3e7cd; border: 1px solid rgba(255,215,145,0.15); border-radius: 8px;">
                        <option value="" style="background: #111;">All Years</option>
                        <option value="1st Year" style="background: #111;" {{ request('year_level') == '1st Year' ? 'selected' : '' }}>1st Year</option>
                        <option value="2nd Year" style="background: #111;" {{ request('year_level') == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                        <option value="3rd Year" style="background: #111;" {{ request('year_level') == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                        <option value="4th Year" style="background: #111;" {{ request('year_level') == '4th Year' ? 'selected' : '' }}>4th Year</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label style="font-size: 0.8rem; font-weight: 600; color: #b39b82; margin-bottom: 4px; display: block;">Semester</label>
                    <select name="semester" class="adm-input" style="width: 100%; padding: 8px 12px; background: rgba(0,0,0,0.3); color: #f3e7cd; border: 1px solid rgba(255,215,145,0.15); border-radius: 8px;">
                        <option value="" style="background: #111;">All Semesters</option>
                        <option value="1st Semester" style="background: #111;" {{ request('semester') == '1st Semester' ? 'selected' : '' }}>1st Semester</option>
                        <option value="2nd Semester" style="background: #111;" {{ request('semester') == '2nd Semester' ? 'selected' : '' }}>2nd Semester</option>
                    </select>
                </div>
                <div class="col-md-3" style="display: flex; align-items: end; gap: 8px; flex-wrap: wrap;">
                    <button type="submit" class="tch-btn" style="white-space: nowrap;">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('teacher.absent') }}" class="adm-btn adm-btn-ghost" style="white-space: nowrap;">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Absent Records Table -->
    <div style="overflow-x: auto; padding: 22px;">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Subject</th>
                    <th>Course & Year</th>
                    <th>Time Marked</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($absentRecords as $record)
                    <tr>
                        <td data-label="Student">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="{{ $record->user->profile_image ? (str_starts_with($record->user->profile_image, 'http') ? $record->user->profile_image : asset('storage/'.$record->user->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($record->user->name).'&background=800000&color=fff' }}" 
                                     style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid #f1f5f9;">
                                <div>
                                    <div style="font-weight: 600; color: #1e293b;">{{ $record->user->name }}</div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">{{ $record->user->student_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Subject">
                            <div>
                                <div style="font-weight: 600;">{{ $record->subject_code }}</div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">{{ $record->subject->name ?? 'N/A' }}</div>
                            </div>
                        </td>
                        <td data-label="Course & Year">
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                <span class="badge-course">{{ $record->user->course }}</span>
                                <span class="badge-year">{{ $record->user->year_level }}</span>
                            </div>
                        </td>
                        <td data-label="Time">{{ $record->created_at->format('g:i A') }}</td>
                        <td data-label="Status">
                            @if($record->excused)
                                <div>
                                    <span style="color: #16a34a; font-weight: 600;">
                                        <i class="bi bi-check-circle"></i> Excused
                                    </span>
                                    @if($record->excuse_note)
                                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 2px;">{{ $record->excuse_note }}</div>
                                    @endif
                                </div>
                            @else
                                <span class="badge-absent">Unexcused</span>
                            @endif
                        </td>
                        <td data-label="Actions">
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                <a href="{{ route('teacher.student', $record->user) }}" class="view-btn">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                @if(!$record->excused)
                                    <button onclick="excuseAbsence({{ $record->id }})" class="view-btn" style="background: #f0fdf4; color: #16a34a; border-color: #bbf7d0;">
                                        <i class="bi bi-check"></i> Excuse
                                    </button>
                                @endif
                                <button onclick="openWarningModal({{ $record->user->id }}, '{{ $record->user->name }}', '{{ $record->subject_code }}')" 
                                        class="view-btn" style="background: #fef2f2; color: #dc2626; border-color: #fecaca;">
                                    <i class="bi bi-exclamation-triangle"></i> Warn
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="bi bi-calendar-check"></i>
                            <div>No absent records found for {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}</div>
                            <div style="font-size: 0.8rem; margin-top: 4px;">All students were present in your subjects!</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Excuse Modal -->
<div class="modal fade" id="excuseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #f1f5f9;">
                <h5 class="modal-title" style="font-weight: 700; color: #1e293b;">
                    <i class="bi bi-check-circle" style="color: #16a34a;"></i>
                    Excuse Absence
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="excuseForm" method="POST">
                @csrf
                <div class="modal-body" style="padding: 20px;">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #374151;">Excuse Note (Optional)</label>
                        <textarea name="excuse_note" class="tch-input" rows="3" placeholder="Reason for excusing this absence..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 20px;">
                    <button type="button" class="tch-btn tch-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tch-btn" style="background: #16a34a; color: white;">
                        <i class="bi bi-check"></i> Excuse Absence
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Warning Modal -->
<div class="modal fade" id="warningModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #f1f5f9;">
                <h5 class="modal-title" style="font-weight: 700; color: #1e293b;">
                    <i class="bi bi-exclamation-triangle" style="color: #dc2626;"></i>
                    Send Warning
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="warningForm" method="POST">
                @csrf
                <div class="modal-body" style="padding: 20px;">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #374151;">Student</label>
                        <div id="studentName" style="padding: 8px 12px; background: #f8fafc; border-radius: 8px; color: #1e293b;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #374151;">Subject</label>
                        <input type="hidden" name="subject_code" id="subjectCode">
                        <div id="subjectName" style="padding: 8px 12px; background: #f8fafc; border-radius: 8px; color: #1e293b;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #374151;">Warning Type</label>
                        <select name="type" class="tch-input" required onchange="toggleCustomMessage()">
                            <option value="warning_2">2 Consecutive Absences</option>
                            <option value="warning_3">3+ Absences (Final Notice)</option>
                            <option value="warning_consecutive_3">3 Consecutive Absences (OSAS Readmission Required)</option>
                            <option value="custom">Custom Message</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="customMessageDiv" style="display: none;">
                        <label class="form-label" style="font-weight: 600; color: #374151;">Custom Message</label>
                        <textarea name="message" class="tch-input" rows="3" placeholder="Enter your custom warning message..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 16px 20px;">
                    <button type="button" class="tch-btn tch-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tch-btn" style="background: #dc2626; color: white;">
                        <i class="bi bi-send"></i> Send Warning
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function excuseAbsence(attendanceId) {
    document.getElementById('excuseForm').action = `/teacher/attendance/${attendanceId}/excuse`;
    new bootstrap.Modal(document.getElementById('excuseModal')).show();
}

function openWarningModal(studentId, studentName, subjectCode) {
    document.getElementById('studentName').textContent = studentName;
    document.getElementById('subjectCode').value = subjectCode;
    document.getElementById('subjectName').textContent = subjectCode;
    document.getElementById('warningForm').action = `/teacher/student/${studentId}/warn`;
    new bootstrap.Modal(document.getElementById('warningModal')).show();
}

function toggleCustomMessage() {
    const type = document.querySelector('select[name="type"]').value;
    const customDiv = document.getElementById('customMessageDiv');
    customDiv.style.display = type === 'custom' ? 'block' : 'none';
}
</script>
@endsection
