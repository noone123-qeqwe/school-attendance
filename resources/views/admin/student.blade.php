@extends('layouts.app')
@section('page-title', $student->name)

@section('content')


<a href="{{ route('admin.students') }}" class="student-back-link">
    <i class="bi bi-arrow-left"></i> Back to Students
</a>

<!-- Profile header -->
<div class="adm-card" style="margin-bottom:20px;">
    <div style="padding:24px;display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
        <img src="{{ $student->profile_image ? (str_starts_with($student->profile_image, 'http') ? $student->profile_image : asset('storage/'.$student->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&background=800000&color=fff&size=200' }}"
             style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #fef3c7;box-shadow:0 4px 16px rgba(128,0,0,.12);">
        <div style="flex:1;">
            <div class="student-name">{{ $student->name }}</div>
            <div class="student-email">{{ $student->email }}</div>
            <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;">
                <span class="badge-course">{{ $student->course }}</span>
                <span class="badge-year">Year {{ $student->year_level }}</span>
                <span style="background:#f8fafc;color:#475569;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;border:1px solid #e2e8f0;">
                    {{ $student->semester }}{{ (int)$student->semester===1?'st':'nd' }} Semester
                </span>
                <span style="background:#f8fafc;color:#475569;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;border:1px solid #e2e8f0;font-family:monospace;">
                    {{ $student->student_number }}
                </span>
            </div>
        </div>
        <!-- Mini stats -->
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <div style="text-align:center;background:#f8fafc;border:1px solid #f1f5f9;border-radius:12px;padding:12px 18px;">
                <div class="student-stat-value">{{ $total }}</div>
                <div class="student-stat-label">Total</div>
            </div>
            <div style="text-align:center;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:12px 18px;">
                <div style="font-size:1.4rem;font-weight:800;color:#16a34a;">{{ $totalPresent }}</div>
                <div style="font-size:.68rem;font-weight:600;color:#16a34a;text-transform:uppercase;">Present</div>
            </div>
            <div style="text-align:center;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:12px 18px;">
                <div style="font-size:1.4rem;font-weight:800;color:#d97706;">{{ $totalLate }}</div>
                <div style="font-size:.68rem;font-weight:600;color:#d97706;text-transform:uppercase;">Late</div>
            </div>
            <div style="text-align:center;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:12px 18px;">
                <div style="font-size:1.4rem;font-weight:800;color:#dc2626;">{{ $totalAbsent }}</div>
                <div style="font-size:.68rem;font-weight:600;color:#dc2626;text-transform:uppercase;">Absent</div>
            </div>
            <div style="text-align:center;background:#f8fafc;border:1px solid #f1f5f9;border-radius:12px;padding:12px 18px;">
                <div style="font-size:1.4rem;font-weight:800;color:{{ $rate>=75?'#16a34a':'#dc2626' }};">{{ $rate }}%</div>
                <div style="font-size:.68rem;font-weight:600;color:#94a3b8;text-transform:uppercase;">Rate</div>
            </div>
        </div>
        <!-- Warning Button -->
        <button onclick="openWarningModal({{ $student->id }}, '{{ $student->name }}')" 
                class="adm-btn" style="background: #dc2626; color: white; display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-exclamation-triangle"></i> Send Warning
        </button>
    </div>
    <!-- Progress bar -->
    <div style="padding:0 24px 20px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
            <span style="font-size:.75rem;font-weight:600;color:#b39b82;">Attendance Rate</span>
            <span style="font-size:.75rem;font-weight:700;color:{{ $rate>=75?'#16a34a':'#dc2626' }};">{{ $rate }}%</span>
        </div>
        <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
            <div style="height:100%;width:{{ $rate }}%;background:{{ $rate>=75?'linear-gradient(90deg,#16a34a,#22c55e)':'linear-gradient(90deg,#dc2626,#ef4444)' }};border-radius:99px;transition:width 1s;"></div>
        </div>
    </div>
</div>

<!-- Attendance Records -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-shield-check-fill"></i></div>
            Attendance History
        </div>
        <span style="font-size:.78rem;color:#94a3b8;">{{ $total }} total records</span>
    </div>
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Time In</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $i => $record)
                <tr>
                    <td data-label="#" style="color:#cbd5e1;font-size:.78rem;">{{ $i + 1 }}</td>
                    <td data-label="Date">
                        <div class="attendance-date">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</div>
                        <div class="attendance-day">{{ \Carbon\Carbon::parse($record->date)->format('l') }}</div>
                    </td>
                    <td data-label="Subject" class="attendance-subject">{{ $record->subject->name ?? $record->subject_code }}</td>
                    <td data-label="Status">
                        @if($record->status === 'Present')
                            <span class="badge-present">Present</span>
                        @elseif($record->status === 'Late')
                            <span class="badge-late">Late</span>
                        @else
                            <span class="badge-absent">Absent</span>
                        @endif
                    </td>
                    <td data-label="Time In" class="attendance-time">{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : 'â€”' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No attendance records for this student.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="warningModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 14px; border: 1px solid rgba(255,255,255,0.1); background: rgba(26,14,11,0.95); box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <h5 class="modal-title" style="color: #f3e7cd;">
                    <i class="bi bi-exclamation-triangle" style="color: #dc2626;"></i>
                    Send Warning to {{ $student->name }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="warningForm" method="POST" action="{{ route('admin.student.warn', $student) }}">
                @csrf
                <div class="modal-body" style="padding: 20px;">
                    <div class="mb-3">
                        <label class="form-label" style="color: #b39b82; font-size: 0.85rem; font-weight: 600;">Subject</label>
                        <select name="subject_code" class="adm-input" required>
                            <option value="">Select Subject</option>
                            @php
                                $subjects = \App\Models\Subject::orderBy('name')->get();
                            @endphp
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->code }}">{{ $subject->code }} - {{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="color: #b39b82; font-size: 0.85rem; font-weight: 600;">Warning Type</label>
                        <select name="type" class="adm-input" required onchange="toggleCustomMessage()">
                            <option value="warning_2">2 Consecutive Absences</option>
                            <option value="warning_3">3+ Absences (Final Notice)</option>
                            <option value="warning_consecutive_3">3 Consecutive Absences (OSAS Readmission Required)</option>
                            <option value="custom">Custom Message</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="customMessageDiv" style="display: none;">
                        <label class="form-label" style="color: #b39b82; font-size: 0.85rem; font-weight: 600;">Custom Message</label>
                        <textarea name="message" class="adm-input" rows="3" placeholder="Enter your custom warning message..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05); padding: 16px 20px;">
                    <button type="button" class="adm-btn adm-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-primary" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border-color: #b91c1c;">
                        <i class="bi bi-send"></i> Send Warning
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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
