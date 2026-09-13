@extends('layouts.app')
@section('page-title', 'My Students')

@section('content')

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="adm-stat">
            <div class="adm-stat-lbl">Total Students</div>
            <div class="adm-stat-val">{{ $students->count() }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="adm-stat">
            <div class="adm-stat-lbl">My Subjects</div>
            <div class="adm-stat-val">{{ $teacherSubjects->count() }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="adm-stat">
            <div class="adm-stat-lbl">1st Year</div>
            <div class="adm-stat-val">{{ $students->where('year_level', '1st Year')->count() }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="adm-stat">
            <div class="adm-stat-lbl">4th Year</div>
            <div class="adm-stat-val">{{ $students->where('year_level', '4th Year')->count() }}</div>
        </div>
    </div>
</div>

<x-card title="My Students" icon="bi bi-people-fill">
    <x-slot name="headerActions">
        <div class="d-flex gap-2">
            <button onclick="window.location.href='{{ route('teacher.students.preview', request()->query()) }}'" 
                    class="btn btn-outline btn-sm">
                <i class="bi bi-eye-fill"></i> Preview PDF
            </button>
            <button onclick="window.location.href='{{ route('teacher.students.csv', request()->query()) }}'" 
                    class="btn btn-outline btn-sm">
                <i class="bi bi-filetype-csv"></i> Export CSV
            </button>
        </div>
    </x-slot>

    <!-- Filters -->
    <div class="mb-4">
        <form method="GET" class="row g-2 align-items-end" data-live-search>
            <div class="col-md-3">
                <label class="text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Name or ID..." class="form-control" autocomplete="off">
            </div>
            <div class="col-md-2">
                <label class="text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Course</label>
                <select name="course" class="form-control">
                    <option value="">All</option>
                    <option value="BSIT" {{ request('course') == 'BSIT' ? 'selected' : '' }}>BSIT</option>
                    <option value="BSCS" {{ request('course') == 'BSCS' ? 'selected' : '' }}>BSCS</option>
                    <option value="BSIS" {{ request('course') == 'BSIS' ? 'selected' : '' }}>BSIS</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Year</label>
                <select name="year_level" class="form-control">
                    <option value="">All</option>
                    <option value="1st Year" {{ request('year_level') == '1st Year' ? 'selected' : '' }}>1st Year</option>
                    <option value="2nd Year" {{ request('year_level') == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                    <option value="3rd Year" {{ request('year_level') == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                    <option value="4th Year" {{ request('year_level') == '4th Year' ? 'selected' : '' }}>4th Year</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Semester</label>
                <select name="semester" class="form-control">
                    <option value="">All</option>
                    <option value="1st Semester" {{ request('semester') == '1st Semester' ? 'selected' : '' }}>1st Semester</option>
                    <option value="2nd Semester" {{ request('semester') == '2nd Semester' ? 'selected' : '' }}>2nd Semester</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="{{ route('teacher.students') }}" class="btn btn-outline">
                    <i class="bi bi-arrow-clockwise"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Students Table -->
    <x-data-table :headers="['Student', 'Course & Year', 'Semester', 'Attendance Rate', 'Total Records', 'Actions']">
        @forelse($students as $student)
            @php
                $total = $student->attendances->count();
                $present = $student->attendances->whereIn('status', ['Present', 'Late'])->count();
                $rate = $total > 0 ? round(($present / $total) * 100) : 0;
            @endphp
            <tr>
                <td data-label="Student">
                    <div class="d-flex align-items-center gap-3">
                        <img src="{{ $student->profile_image ? (str_starts_with($student->profile_image, 'http') ? $student->profile_image : asset('storage/'.$student->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&background=800000&color=fff' }}" 
                             style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid var(--gold);">
                        <div>
                            <div style="font-weight: 600;">{{ $student->name }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">{{ $student->student_number }}</div>
                        </div>
                    </div>
                </td>
                <td data-label="Course & Year">
                    <x-badge type="info">{{ $student->course }}</x-badge>
                    <x-badge type="info">{{ $student->year_level }}</x-badge>
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
                    <div class="d-flex align-items-center gap-2">
                        <div style="width: 60px; height: 6px; background: rgba(255,255,255,0.1); border-radius: 99px; overflow: hidden;">
                            <div style="width: {{ $rate }}%; height: 100%; background: {{ $rate >= 75 ? 'var(--gold)' : ($rate >= 50 ? '#f59e0b' : '#ef4444') }}; border-radius: 99px;"></div>
                        </div>
                        <span style="font-size: 0.8rem; font-weight: 600; color: {{ $rate >= 75 ? 'var(--gold)' : ($rate >= 50 ? '#f59e0b' : '#ef4444') }};">{{ $rate }}%</span>
                    </div>
                </td>
                <td data-label="Total Records">
                    <span style="font-weight: 600;">{{ $total }}</span>
                    <span class="text-muted" style="font-size: 0.75rem;">records</span>
                </td>
                <td data-label="Actions">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('teacher.student', $student) }}" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem;">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <button onclick="openWarningModal({{ $student->id }}, '{{ addslashes($student->name) }}')" 
                                class="btn btn-primary" style="padding: 4px 10px; font-size: 0.8rem; background: var(--maroon); border: none;">
                            <i class="bi bi-exclamation-triangle"></i> Warn
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <div class="empty-state">
                        <i class="bi bi-people"></i>
                        <p>No students found in your subjects</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-data-table>
</x-card>

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
<div class="modal fade" id="warningModal" tabindex="-1" style="z-index: 1055;">
    <div class="modal-dialog">
        <div class="modal-content adm-card" style="border: none;">
            <div class="modal-header adm-card-head border-bottom-0 pb-0">
                <h5 class="modal-title text-white font-weight-bold">
                    <i class="bi bi-exclamation-triangle" style="color: var(--maroon-light);"></i>
                    Send Warning
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="warningForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Student</label>
                        <div id="studentName" class="form-control" style="background: rgba(255,255,255,0.05); cursor: not-allowed;" readonly></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Subject</label>
                        <select name="subject_code" class="form-control" required>
                            <option value="">Select Subject</option>
                            @foreach($teacherSubjects as $subject)
                                <option value="{{ $subject->code }}">{{ $subject->code }} - {{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Warning Type</label>
                        <select name="type" class="form-control" required onchange="toggleCustomMessage()">
                            <option value="warning_2">2 Consecutive Absences</option>
                            <option value="warning_3">3+ Absences (Final Notice)</option>
                            <option value="warning_consecutive_3">3 Consecutive Absences (OSAS Readmission Required)</option>
                            <option value="custom">Custom Message</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="customMessageDiv" style="display: none;">
                        <label class="form-label text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Custom Message</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Enter your custom warning message..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: var(--maroon); border: none;">
                        <i class="bi bi-send"></i> Send Warning
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
