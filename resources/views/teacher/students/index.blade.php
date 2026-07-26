@extends('teacher.layout')

@section('page-title', 'My Students')

@section('content')
<style>
    .tch-students-table {
        width: 100%;
        min-width: 860px;
        border-collapse: collapse;
    }
    .tch-students-table thead th {
        padding: 16px 18px;
        text-align: left;
        font-size: .82rem;
        font-weight: 700;
        color: #f3e7cd;
        text-transform: uppercase;
        letter-spacing: .05em;
        border-bottom: 1px solid rgba(207,164,111,0.18);
        background: rgba(255,235,190,0.03);
    }
    .tch-students-table thead th:nth-child(1) { width: 26%; }
    .tch-students-table thead th:nth-child(2) { width: 22%; }
    .tch-students-table thead th:nth-child(3) { width: 14%; }
    .tch-students-table thead th:nth-child(4) { width: 18%; }
    .tch-students-table thead th:nth-child(5) { width: 10%; }
    .tch-students-table thead th:nth-child(6) { width: 18%; }
    .tch-students-table tbody tr {
        transition: background 0.2s ease;
    }
    .tch-students-table tbody tr:hover {
        background: rgba(255,235,190,0.06);
    }
    .tch-students-table tbody td {
        padding: 16px 18px;
        vertical-align: middle;
        color: #e7dcc8;
        font-size: .92rem;
        border-bottom: 1px solid rgba(207,164,111,0.12);
        text-align: left;
    }
    .tch-students-table tbody td:nth-child(6) {
        text-align: right;
        white-space: nowrap;
    }
    .tch-students-table tbody td:last-child {
        padding: 14px 18px;
    }
    .tch-students-table td > div {
        min-width: 0;
    }
    .tch-students-table .student-name {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .tch-students-table .student-meta {
        min-width: 0;
    }
    .tch-students-table .student-meta div {
        min-width: 0;
    }
    .tch-students-table .badge-course,
    .tch-students-table .badge-year {
        display: inline-flex;
        margin-right: 6px;
        margin-bottom: 4px;
    }
    .tch-students-table .view-btn {
        width: auto;
        height: auto;
        min-width: 84px;
        padding: 8px 12px;
        white-space: nowrap;
        font-size: 0.82rem;
        flex-shrink: 0;
    }
    .tch-students-table .view-btn i {
        margin-right: 6px;
        font-size: 0.95rem;
    }
    @media (max-width: 992px) {
        .tch-students-table { min-width: 720px; }
    }
    @media (max-width: 768px) {
        .tch-students-table { min-width: auto; }
        .tch-students-table thead { display: none; }
        .tch-students-table tbody tr {
            display: block;
            border: 1px solid rgba(207,164,111,0.18);
            border-radius: 14px;
            margin-bottom: 14px;
            background: rgba(255,235,190,0.05);
            box-shadow: 0 1px 6px rgba(0,0,0,0.12);
        }
        .tch-students-table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(207,164,111,0.12);
        }
        .tch-students-table tbody td:last-child { border-bottom: none; }
        .tch-students-table tbody td::before {
            content: attr(data-label);
            font-size: .72rem;
            font-weight: 700;
            color: #b39b82;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-right: 10px;
            flex-shrink: 0;
        }
    }
</style>
<div class="tch-stats">
    <div class="tch-stat">
        <div class="tch-stat-val" style="color: #e7d4b8;">{{ $students->count() }}</div>
        <div class="tch-stat-lbl">Total Students</div>
    </div>
    <div class="tch-stat">
        <div class="tch-stat-val" style="color: #cfa46f;">{{ $teacherSubjects->count() }}</div>
        <div class="tch-stat-lbl">My Subjects</div>
    </div>
    <div class="tch-stat">
        <div class="tch-stat-val" style="color: #d2945f;">{{ $students->where('year_level', '1st Year')->count() }}</div>
        <div class="tch-stat-lbl">1st Year</div>
    </div>
    <div class="tch-stat">
        <div class="tch-stat-val" style="color: #8a3b2e;">{{ $students->where('year_level', '4th Year')->count() }}</div>
        <div class="tch-stat-lbl">4th Year</div>
    </div>
</div>

<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background: rgba(128,0,0,0.14); color: #800000;">
                <i class="bi bi-people-fill"></i>
            </div>
            My Students
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button onclick="window.location.href='{{ route('teacher.students.preview', request()->query()) }}'" 
                    class="tch-btn tch-btn-ghost" style="font-size: 0.8rem; color: #e7d4b8; border-color: rgba(207,164,111,0.2);">
                <i class="bi bi-eye-fill"></i> Preview PDF
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div style="padding: 16px 22px; border-bottom: 1px solid rgba(207,164,111,0.18);">
        <form method="GET" class="row g-2" data-live-search>
            <div class="col-md-3">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search name or student number..." class="tch-input" autocomplete="off" oninput="window.liveSearchTimer && clearTimeout(window.liveSearchTimer); window.liveSearchTimer = setTimeout(() => this.form.submit(), 0);">
            </div>
            <div class="col-md-2">
                <select name="course" class="tch-input">
                    <option value="">All Courses</option>
                    <option value="BSIT" {{ request('course') == 'BSIT' ? 'selected' : '' }}>BSIT</option>
                    <option value="BSCS" {{ request('course') == 'BSCS' ? 'selected' : '' }}>BSCS</option>
                    <option value="BSIS" {{ request('course') == 'BSIS' ? 'selected' : '' }}>BSIS</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="year_level" class="tch-input">
                    <option value="">All Years</option>
                    <option value="1st Year" {{ request('year_level') == '1st Year' ? 'selected' : '' }}>1st Year</option>
                    <option value="2nd Year" {{ request('year_level') == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                    <option value="3rd Year" {{ request('year_level') == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                    <option value="4th Year" {{ request('year_level') == '4th Year' ? 'selected' : '' }}>4th Year</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="semester" class="tch-input">
                    <option value="">All Semesters</option>
                    <option value="1st Semester" {{ request('semester') == '1st Semester' ? 'selected' : '' }}>1st Semester</option>
                    <option value="2nd Semester" {{ request('semester') == '2nd Semester' ? 'selected' : '' }}>2nd Semester</option>
                </select>
            </div>
            <div class="col-md-3">
                <div style="display: flex; gap: 6px;">
                    <button type="submit" class="tch-btn tch-btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('teacher.students') }}" class="tch-btn tch-btn-ghost">
                        <i class="bi bi-arrow-clockwise"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Students Table -->
    <div style="overflow-x: auto;">
        <table class="tch-table tch-students-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Course & Year</th>
                    <th>Semester</th>
                    <th>Attendance Rate</th>
                    <th>Total Records</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    @php
                        $total = $student->attendances->count();
                        $present = $student->attendances->whereIn('status', ['Present', 'Late'])->count();
                        $rate = $total > 0 ? round(($present / $total) * 100) : 0;
                    @endphp
                    <tr>
                        <td data-label="Student">
                            <div class="student-name" style="display: flex; align-items: center; gap: 10px;">
                                <img src="{{ $student->profile_image ? asset('storage/'.$student->profile_image) : asset('images/default-avatar.png') }}" 
                                     style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(207,164,111,0.24);">
                                <div class="student-meta">
                                    <div style="font-weight: 600; color: #f3e7cd;">{{ $student->name }}</div>
                                    <div style="font-size: 0.75rem; color: #b39b82;">{{ $student->student_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Course & Year">
                            <span class="badge-course">{{ $student->course }}</span>
                            <span class="badge-year">{{ $student->year_level }}</span>
                        </td>
                        @php
                            $semesterValue = $student->semester;
                            if (is_numeric($semesterValue)) {
                                $semesterLabel = $semesterValue == 1 ? '1st'
                                    : ($semesterValue == 2 ? '2nd'
                                    : ($semesterValue == 3 ? '3rd'
                                    : $semesterValue.'th'));
                            } else {
                                $semesterLabel = $semesterValue;
                            }
                        @endphp
                        <td data-label="Semester">{{ $semesterLabel }}</td>
                        <td data-label="Attendance Rate">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 60px; height: 6px; background: rgba(207,164,111,0.12); border-radius: 99px; overflow: hidden;">
                                    <div style="width: {{ $rate }}%; height: 100%; background: {{ $rate >= 75 ? '#cfa46f' : ($rate >= 50 ? '#d2945f' : '#8a3b2e') }}; border-radius: 99px;"></div>
                                </div>
                                <span style="font-size: 0.8rem; font-weight: 600; color: {{ $rate >= 75 ? '#cfa46f' : ($rate >= 50 ? '#d2945f' : '#8a3b2e') }};">{{ $rate }}%</span>
                            </div>
                        </td>
                        <td data-label="Total Records">
                            <span style="font-weight: 600;">{{ $total }}</span>
                            <span style="font-size: 0.75rem; color: #b39b82;">records</span>
                        </td>
                        <td data-label="Actions">
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                <a href="{{ route('teacher.student', $student) }}" class="view-btn" style="color:#e7d4b8;border-color:rgba(207,164,111,0.24);background:rgba(207,164,111,0.08);">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <button onclick="openWarningModal({{ $student->id }}, '{{ $student->name }}')" 
                                        class="view-btn" style="background: rgba(138,59,46,0.12); color: #8a3b2e; border-color: rgba(138,59,46,0.25);">
                                    <i class="bi bi-exclamation-triangle"></i> Warn
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="bi bi-people"></i>
                            <div>No students found in your subjects</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const debounce = (fn, delay = 450) => {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => fn(...args), delay);
            };
        };

        document.querySelectorAll('form[data-live-search] input[name="search"]').forEach(input => {
            const form = input.closest('form');
            if (!form) return;
            const submit = debounce(() => form.submit(), 450);
            input.addEventListener('input', submit);
        });
    });
</script>

<!-- Warning Modal -->
<div class="modal fade" id="warningModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(207,164,111,0.18);">
                <h5 class="modal-title" style="font-weight: 700; color: #f3e7cd;">
                    <i class="bi bi-exclamation-triangle" style="color: #8a3b2e;"></i>
                    Send Warning
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="warningForm" method="POST">
                @csrf
                <div class="modal-body" style="padding: 20px;">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #b39b82;">Student</label>
                        <div id="studentName" style="padding: 8px 12px; background: rgba(207,164,111,0.12); border-radius: 8px; color: #f3e7cd;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #374151;">Subject</label>
                        <select name="subject_code" class="tch-input" required>
                            <option value="">Select Subject</option>
                            @foreach($teacherSubjects as $subject)
                                <option value="{{ $subject->code }}">{{ $subject->code }} - {{ $subject->name }}</option>
                            @endforeach
                        </select>
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
                <div class="modal-footer" style="border-top: 1px solid rgba(207,164,111,0.18); padding: 16px 20px;">
                    <button type="button" class="tch-btn tch-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tch-btn" style="background: #8a3b2e; color: white;">
                        <i class="bi bi-send"></i> Send Warning
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Modal contrast fixes for readability (teacher students list) */
.modal-content { background: #ffffff !important; color: #0f172a !important; box-shadow: 0 10px 30px rgba(2,6,23,0.35); }
.modal-header, .modal-footer { border-color: #e6edf3 !important; }
.modal .form-label { color: #0f172a !important; font-weight: 700; }
.modal .tch-input, .modal .adm-input { background: #f8fafc !important; color: #0f172a !important; border-color: #e2e8f0 !important; }
.modal .tch-input::placeholder, .modal .adm-input::placeholder { color: #6b7280 !important; }
.modal .btn-close { filter: none; }
.modal .tch-btn.tch-btn-ghost { background: transparent !important; color: #374151 !important; border: 1px solid #e6edf3 !important; }
</style>

<script>
function openWarningModal(studentId, studentName) {
    document.getElementById('studentName').textContent = studentName;
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