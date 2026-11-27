<div class="tch-stats">
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(251,113,133,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-person-x-fill" style="color:var(--portal-rose);font-size:1rem;" aria-hidden="true"></i>
        </div>
        <div class="tch-stat-val" style="color: var(--portal-rose);">{{ $absentRecordsCount }}</div>
        <div class="tch-stat-lbl">Absent ({{ \Carbon\Carbon::parse($absentDate)->format('M j') }})</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(212,175,55,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-journal-bookmark-fill" style="color:var(--portal-gold);font-size:1rem;" aria-hidden="true"></i>
        </div>
        <div class="tch-stat-val" style="color: var(--portal-gold);">{{ $teacherSubjects->count() }}</div>
        <div class="tch-stat-lbl">My Subjects</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(251,191,36,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-exclamation-circle" style="color:var(--portal-amber);font-size:1rem;" aria-hidden="true"></i>
        </div>
        <div class="tch-stat-val" style="color: var(--portal-amber);">{{ $absentUnexcusedCount }}</div>
        <div class="tch-stat-lbl">Unexcused</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(52,211,153,0.12);display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-check-circle" style="color:var(--portal-emerald);font-size:1rem;" aria-hidden="true"></i>
        </div>
        <div class="tch-stat-val" style="color: var(--portal-emerald);">{{ $absentExcusedCount }}</div>
        <div class="tch-stat-lbl">Excused</div>
    </div>
</div>

<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background: rgba(122,26,26,0.22); color: var(--portal-gold);">
                <i class="bi bi-person-x-fill" aria-hidden="true"></i>
            </div>
            <div>
                <div>Absent Students Report (My Subjects)</div>
                <div style="font-size: 0.78rem; font-weight: 500; color: #a49890;">
                    Track missed classes, excuse absences, or send early attendance warnings
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div style="padding: 16px 22px; border-bottom: 1px solid rgba(255,255,255,0.08);">
        <form method="GET" action="{{ route('teacher.absent') }}" class="filter-form">
            <div class="tch-filter-grid">
                <div class="tch-filter-field">
                    <label for="absentFilterDate" class="tch-filter-label">Date</label>
                    <input
                        type="date"
                        id="absentFilterDate"
                        name="date"
                        value="{{ $absentDate }}"
                        class="tch-input"
                        style="width: 100%; color-scheme: dark;">
                </div>
                <div class="tch-filter-field">
                    <label for="absentFilterYearLevel" class="tch-filter-label">Year Level</label>
                    <select id="absentFilterYearLevel" name="year_level" class="tch-input" style="width: 100%;">
                        <option value="">All Years</option>
                        <option value="1st Year" {{ request('year_level') == '1st Year' ? 'selected' : '' }}>1st Year</option>
                        <option value="2nd Year" {{ request('year_level') == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                        <option value="3rd Year" {{ request('year_level') == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                        <option value="4th Year" {{ request('year_level') == '4th Year' ? 'selected' : '' }}>4th Year</option>
                    </select>
                </div>
                <div class="tch-filter-field">
                    <label for="absentFilterSemester" class="tch-filter-label">Semester</label>
                    <select id="absentFilterSemester" name="semester" class="tch-input" style="width: 100%;">
                        <option value="">All Semesters</option>
                        <option value="1st Semester" {{ request('semester') == '1st Semester' ? 'selected' : '' }}>1st Semester</option>
                        <option value="2nd Semester" {{ request('semester') == '2nd Semester' ? 'selected' : '' }}>2nd Semester</option>
                    </select>
                </div>
                <div class="tch-filter-actions">
                    <button type="submit" class="tch-btn tch-btn-primary">
                        <i class="bi bi-search" aria-hidden="true"></i> Filter
                    </button>
                    <a href="{{ route('teacher.absent') }}" class="tch-btn tch-btn-ghost">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Absent Records Table -->
    <div style="overflow-x: auto;">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Subject</th>
                    <th>Course &amp; Year</th>
                    <th>Time Marked</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($absentRecords as $record)
                    <tr>
                        <td data-label="Student">
                            @if($record->user)
                                <div class="student-info">
                                    <img src="{{ $record->user->profile_image ? (str_starts_with($record->user->profile_image, 'http') ? $record->user->profile_image : public_storage_url($record->user->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($record->user->name).'&background=800000&color=fff' }}"
                                         class="student-avatar" alt="Student avatar">
                                    <div class="student-meta">
                                        <div class="name">{{ $record->user->name }}</div>
                                        <div class="subtext">{{ $record->user->student_number }}</div>
                                    </div>
                                </div>
                            @else
                                <div class="student-meta">
                                    <div class="name">Unknown Student</div>
                                </div>
                            @endif
                        </td>
                        <td data-label="Subject">
                            <div class="subject-meta">
                                <div class="title">{{ $record->subject_code }}</div>
                                <div class="subtext">{{ $record->subject->name ?? 'N/A' }}</div>
                            </div>
                        </td>
                        <td data-label="Course & Year">
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                @if($record->user?->course)
                                    <span class="badge-course">{{ $record->user->course }}</span>
                                @endif
                                @if($record->user?->year_level)
                                    <span class="badge-year">{{ $record->user->year_level }}</span>
                                @endif
                            </div>
                        </td>
                        <td data-label="Time Marked">{{ $record->created_at->format('g:i A') }}</td>
                        <td data-label="Status">
                            @if($record->excused)
                                <div>
                                    <span class="status-badge status-approved">
                                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i> Excused
                                    </span>
                                    @if($record->excuse_note)
                                        <div style="font-size: 0.76rem; color: #a49890; margin-top: 4px;">{{ $record->excuse_note }}</div>
                                    @endif
                                </div>
                            @else
                                <span class="status-badge status-rejected">Unexcused</span>
                            @endif
                        </td>
                        <td data-label="Actions" style="text-align: right;">
                            <div class="action-group">
                                @if($record->user)
                                    <a href="{{ route('teacher.student', $record->user) }}" class="action-view" style="text-decoration: none;">
                                        <i class="bi bi-eye" aria-hidden="true"></i> View
                                    </a>
                                @endif
                                @if(!$record->excused)
                                    <button type="button" data-absent-excuse-id="{{ $record->id }}" class="action-approve">
                                        <i class="bi bi-check" aria-hidden="true"></i> Excuse
                                    </button>
                                @endif
                                @if($record->user)
                                    <button
                                        type="button"
                                        data-absent-warn-student-id="{{ $record->user->id }}"
                                        data-absent-warn-student-name="{{ $record->user->name }}"
                                        data-absent-warn-subject-code="{{ $record->subject_code }}"
                                        class="action-reject">
                                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Warn
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="bi bi-calendar-check" aria-hidden="true"></i>
                            <div>No absent records found for {{ \Carbon\Carbon::parse($absentDate)->format('F j, Y') }}</div>
                            <div style="font-size: 0.8rem; margin-top: 4px;">All students were present in your subjects!</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Absent Excuse Modal -->
<div class="modal fade" id="absentExcuseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-weight: 700;">
                    <i class="bi bi-check-circle" style="color: var(--portal-emerald);" aria-hidden="true"></i>
                    Excuse Absence
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="absentExcuseForm" method="POST">
                @csrf
                <div class="modal-body" style="padding: 20px;">
                    <div class="mb-3">
                        <label for="absentExcuseNote" class="form-label" style="font-weight: 600; color: var(--portal-text-soft);">Excuse Note (Optional)</label>
                        <textarea id="absentExcuseNote" name="excuse_note" class="tch-input" rows="3" placeholder="Reason for excusing this absence..." style="width: 100%;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 16px 20px;">
                    <button type="button" class="tch-btn tch-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tch-btn" style="background: rgba(16,185,129,0.2); color: var(--portal-emerald); border: 1px solid rgba(52,211,153,0.35);">
                        <i class="bi bi-check" aria-hidden="true"></i> Excuse Absence
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Absent Warning Modal -->
<div class="modal fade" id="absentWarningModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-weight: 700;">
                    <i class="bi bi-exclamation-triangle" style="color: var(--portal-rose);" aria-hidden="true"></i>
                    Send Warning
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="absentWarningForm" method="POST">
                @csrf
                <div class="modal-body" style="padding: 20px;">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: var(--portal-text-soft);">Student</label>
                        <div id="absentWarnStudentName" style="padding: 8px 12px; background: var(--portal-canvas); border: 1px solid var(--portal-border-subtle); border-radius: 8px; color: var(--portal-text);"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: var(--portal-text-soft);">Subject</label>
                        <input type="hidden" name="subject_code" id="absentWarnSubjectCode">
                        <div id="absentWarnSubjectName" style="padding: 8px 12px; background: var(--portal-canvas); border: 1px solid var(--portal-border-subtle); border-radius: 8px; color: var(--portal-text);"></div>
                    </div>

                    <div class="mb-3">
                        <label for="absentWarningTypeSelect" class="form-label" style="font-weight: 600; color: var(--portal-text-soft);">Warning Type</label>
                        <select name="type" id="absentWarningTypeSelect" class="tch-input" style="width: 100%;" required>
                            <option value="warning_2">2 Consecutive Absences</option>
                            <option value="warning_3">3+ Absences (Final Notice)</option>
                            <option value="warning_consecutive_3">3 Consecutive Absences (OSAS Readmission Required)</option>
                            <option value="custom">Custom Message</option>
                        </select>
                    </div>

                    <div class="mb-3" id="absentCustomMessageDiv" style="display: none;">
                        <label for="absentWarnCustomMessage" class="form-label" style="font-weight: 600; color: var(--portal-text-soft);">Custom Message</label>
                        <textarea id="absentWarnCustomMessage" name="message" class="tch-input" rows="3" placeholder="Enter your custom warning message..." style="width: 100%;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 16px 20px;">
                    <button type="button" class="tch-btn tch-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tch-btn" style="background: rgba(251,113,133,0.2); color: var(--portal-rose); border: 1px solid rgba(251,113,133,0.35);">
                        <i class="bi bi-send" aria-hidden="true"></i> Send Warning
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script @cspNonce>
(function () {
    const absentTab = document.getElementById('absent-tab');
    if (!absentTab) return;

    const excuseForm = document.getElementById('absentExcuseForm');
    const excuseModalEl = document.getElementById('absentExcuseModal');
    const warningForm = document.getElementById('absentWarningForm');
    const warningModalEl = document.getElementById('absentWarningModal');
    const warnStudentNameEl = document.getElementById('absentWarnStudentName');
    const warnSubjectCodeInput = document.getElementById('absentWarnSubjectCode');
    const warnSubjectNameEl = document.getElementById('absentWarnSubjectName');
    const warningTypeSelect = document.getElementById('absentWarningTypeSelect');
    const customMessageDiv = document.getElementById('absentCustomMessageDiv');

    absentTab.addEventListener('click', (event) => {
        const excuseBtn = event.target.closest('[data-absent-excuse-id]');
        if (excuseBtn && excuseForm && excuseModalEl) {
            const attendanceId = excuseBtn.getAttribute('data-absent-excuse-id');
            excuseForm.action = `/teacher/attendance/${attendanceId}/excuse`;
            bootstrap.Modal.getOrCreateInstance(excuseModalEl).show();
            return;
        }

        const warnBtn = event.target.closest('[data-absent-warn-student-id]');
        if (warnBtn && warningForm && warningModalEl) {
            const studentId = warnBtn.getAttribute('data-absent-warn-student-id') || '';
            const studentName = warnBtn.getAttribute('data-absent-warn-student-name') || '';
            const subjectCode = warnBtn.getAttribute('data-absent-warn-subject-code') || '';
            if (warnStudentNameEl) warnStudentNameEl.textContent = studentName;
            if (warnSubjectCodeInput) warnSubjectCodeInput.value = subjectCode;
            if (warnSubjectNameEl) warnSubjectNameEl.textContent = subjectCode;
            warningForm.action = `/teacher/student/${studentId}/warn`;
            bootstrap.Modal.getOrCreateInstance(warningModalEl).show();
        }
    });

    warningTypeSelect?.addEventListener('change', () => {
        if (!customMessageDiv) return;
        customMessageDiv.style.display = warningTypeSelect.value === 'custom' ? 'block' : 'none';
    });
})();
</script>
