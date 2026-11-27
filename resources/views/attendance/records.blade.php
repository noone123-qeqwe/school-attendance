<section class="records-page" id="attendance-records" aria-labelledby="attendanceRecordsTitle" tabindex="-1">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 class="page-header-title" id="attendanceRecordsTitle">Detailed attendance records</h2>
            <div class="page-header-sub">{{ $totals['all'] }} total records</div>
        </div>
    </div>

    <!-- Summary chips (Interactive status filters) -->
    @php
        $activeStatusFilter = $filters['status'] ?? '';
        $baseRecordParams = array_filter([
            'year' => $year,
            'month' => $month,
            'q' => $filters['q'] ?? null,
            'record_date' => $filters['record_date'] ?? null,
        ]);
    @endphp
    <div class="summary-chips">
        <a href="{{ route('student.calendar', $baseRecordParams) }}#attendance-records" data-calendar-month-link class="summary-chip {{ $activeStatusFilter === '' ? 'is-active-chip' : '' }}">
            <div class="summary-chip-value" style="color: #FAF8F5;">{{ $totals['all'] }}</div>
            <div class="summary-chip-label">Total Records</div>
        </a>
        <a href="{{ route('student.calendar', $activeStatusFilter === 'Present' ? $baseRecordParams : array_merge($baseRecordParams, ['status' => 'Present'])) }}#attendance-records" data-calendar-month-link class="summary-chip {{ $activeStatusFilter === 'Present' ? 'is-active-chip' : '' }}">
            <div class="summary-chip-value" style="color: #4ADE80;">{{ $totals['Present'] ?? 0 }}</div>
            <div class="summary-chip-label">Present</div>
        </a>
        <a href="{{ route('student.calendar', $activeStatusFilter === 'Late' ? $baseRecordParams : array_merge($baseRecordParams, ['status' => 'Late'])) }}#attendance-records" data-calendar-month-link class="summary-chip {{ $activeStatusFilter === 'Late' ? 'is-active-chip' : '' }}">
            <div class="summary-chip-value" style="color: #FBBF24;">{{ $totals['Late'] ?? 0 }}</div>
            <div class="summary-chip-label">Late</div>
        </a>
        <a href="{{ route('student.calendar', $activeStatusFilter === 'Absent' ? $baseRecordParams : array_merge($baseRecordParams, ['status' => 'Absent'])) }}#attendance-records" data-calendar-month-link class="summary-chip {{ $activeStatusFilter === 'Absent' ? 'is-active-chip' : '' }}">
            <div class="summary-chip-value" style="color: #F87171;">{{ $totals['Absent'] ?? 0 }}</div>
            <div class="summary-chip-label">Absent</div>
        </a>
        <a href="{{ route('student.calendar', $activeStatusFilter === 'Excused' ? $baseRecordParams : array_merge($baseRecordParams, ['status' => 'Excused'])) }}#attendance-records" data-calendar-month-link class="summary-chip {{ $activeStatusFilter === 'Excused' ? 'is-active-chip' : '' }}">
            <div class="summary-chip-value" style="color: #7DD3FC;">{{ $totals['Excused'] ?? 0 }}</div>
            <div class="summary-chip-label">Excused</div>
        </a>
    </div>

    <!-- Table card -->
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">
                <div class="table-title-icon">
                    <i class="bi bi-journal-check" aria-hidden="true"></i>
                </div>
                All Records
            </div>
        </div>

        <form method="GET" action="{{ route('student.calendar') }}#attendance-records" class="calendar-record-filters">
            <input type="hidden" name="year" value="{{ $year }}" data-calendar-month="year">
            <input type="hidden" name="month" value="{{ $month }}" data-calendar-month="month">
            <div>
                <label class="ent-label" for="attendanceRecordsSearch">Class or teacher</label>
                <input type="search" class="ent-input" id="attendanceRecordsSearch" name="q" maxlength="120" placeholder="Subject, code, or teacher" value="{{ $filters['q'] ?? '' }}">
            </div>
            <div>
                <label class="ent-label" for="studentRecordStatus">Status</label>
                <select id="studentRecordStatus" name="status" class="ent-input">
                    <option value="">All statuses</option>
                    @foreach(['Present', 'Late', 'Absent', 'Escaped', 'Excused'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="ent-label" for="studentRecordDate">Date</label>
                <input type="date" class="ent-input" id="studentRecordDate" name="record_date" value="{{ $filters['record_date'] ?? '' }}">
            </div>
            <div class="calendar-record-filter-actions">
                <button type="submit" class="ent-btn ent-btn-primary">Filter</button>
                @if(array_filter($filters))
                    <a class="ent-btn ent-btn-ghost" href="{{ route('student.calendar', compact('year', 'month')) }}#attendance-records" data-calendar-month-link>Clear</a>
                @endif
            </div>
        </form>
        <p class="student-results-count px-3">{{ $records->total() }} matching {{ Str::plural('record', $records->total()) }}</p>
        <div class="sd-records-thead d-none d-lg-grid" aria-hidden="true">
            <span>Course &amp; Date</span>
            <span>Session Details</span>
            <span>Status</span>
            <span class="text-end">Action</span>
        </div>
        <!-- Mobile-first Card Layout / Desktop Ledger Rows -->
        <div class="records-container" id="recordsContainer">
            @forelse($records as $record)
            <div class="record-card" data-status="{{ $record->status }}" data-excused="{{ $record->excused || $record->status === 'Excused' ? '1' : '0' }}">
                @php
                    $dayName = \Carbon\Carbon::parse($record->date)->format('l');
                    $sched = $record->subject?->schedules?->firstWhere('day', $dayName) ?? $record->subject?->schedules?->first();
                    $schedStr = $sched ? \Carbon\Carbon::parse($sched->start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($sched->end_time)->format('g:i A') : 'Schedule TBA';
                    $clockInTime = $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('g:i A') : ($record->checked_in_at ? $record->checked_in_at->format('g:i A') : null);
                    $clockInText = $clockInTime ?? ($record->status === 'Absent' ? 'No clock-in' : '—');
                    $instName = $record->subject?->instructorUser?->name ?? $record->subject?->instructor ?? 'Instructor TBA';
                    $methodLabel = match ($record->method) {
                        'qr' => 'QR scan',
                        'code' => 'PIN code',
                        'manual_gps' => 'GPS check-in',
                        'manual' => 'Teacher verified',
                        null, '' => null,
                        default => \Illuminate\Support\Str::headline($record->method),
                    };
                @endphp
                <div class="record-header">
                    <div class="record-subject">
                        <h4>{{ $record->subject->name ?? $record->subject_name ?? $record->subject_code }}</h4>
                        <div class="record-meta">
                            @if($record->subject_code)
                                <span>{{ $record->subject_code }}</span>
                            @endif
                            <span class="record-date">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</span>
                            <span class="record-day">{{ $dayName }}</span>
                            <span style="font-size:0.75rem; color:#D4AF37;"><i class="bi bi-person me-1" aria-hidden="true"></i>{{ $instName }}</span>
                        </div>
                    </div>
                    <div class="record-status">
                        @if($record->excused || $record->status === 'Excused')
                            <span class="status-badge badge-excused">Excused</span>
                        @elseif($record->status === 'Present')
                            <span class="status-badge badge-present">Present</span>
                        @elseif($record->status === 'Late')
                            <span class="status-badge badge-late">Late</span>
                        @elseif($record->status === 'Absent')
                            <span class="status-badge badge-absent">Absent</span>
                        @else
                            <span class="status-badge badge-absent">{{ $record->status }}</span>
                        @endif
                    </div>
                </div>
                
                <div class="record-body">
                    <div class="record-details">
                        <div class="detail-item">
                            <span class="detail-label">Schedule</span>
                            <span class="detail-value">{{ $schedStr }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Actual Clock-in</span>
                            <span class="detail-value" style="{{ $record->status === 'Absent' ? 'color:#f87171;' : ($record->status === 'Present' ? 'color:#4ade80;' : ($record->status === 'Late' ? 'color:#fbbf24;' : '')) }}">
                                {{ $clockInText }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Clock-out</span>
                            <span class="detail-value">{{ $record->time_out ? \Carbon\Carbon::parse($record->time_out)->format('g:i A') : '—' }}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Remarks</span>
                            <span class="detail-value">{{ $record->excuse_note ?? $record->excuseSubmission?->reason ?? ($record->excused ? 'Excused Absence' : '—') }}</span>
                        </div>
                        @if($methodLabel)
                            <div class="detail-item">
                                <span class="detail-label">Method</span>
                                <span class="detail-value">{{ $methodLabel }}</span>
                            </div>
                        @endif
                        @if($record->class)
                            <div class="detail-item">
                                <span class="detail-label">Section</span>
                                <span class="detail-value">{{ $record->class }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="record-actions" style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                        @if($record->status === 'Absent' && !$record->excuseSubmission)
                            <button class="excuse-btn" onclick="openExcuseModal({{ $record->id }}, {{ Illuminate\Support\Js::from($record->subject->name ?? $record->subject_code) }}, '{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}')">
                                <i class="bi bi-file-text"></i> Submit Excuse
                            </button>
                        @elseif($record->excuseSubmission)
                            <span class="excuse-status excuse-{{ $record->excuseSubmission->status }}">
                                <i class="bi bi-{{ $record->excuseSubmission->status === 'approved' ? 'check-circle' : ($record->excuseSubmission->status === 'rejected' ? 'x-circle' : 'clock') }}"></i>
                                Excuse: {{ ucfirst($record->excuseSubmission->status) }}
                            </span>
                        @endif

                        @if($record->correction)
                            <span class="excuse-status excuse-{{ $record->correction->status }}" style="background:rgba(212, 175, 55,0.14); color:#F5DFA8; border:1px solid rgba(212, 175, 55,0.28);">
                                <i class="bi bi-patch-question" aria-hidden="true"></i> Appeal: {{ ucfirst($record->correction->status) }}
                            </span>
                        @elseif(in_array($record->status, ['Late', 'Absent']))
                            <button type="button" class="excuse-btn" style="background:#261B1B !important; color:#FAF8F5 !important; border-color:rgba(255, 255, 255,0.16) !important;" onclick="openCorrectionModal({{ $record->id }}, {{ Illuminate\Support\Js::from($record->subject->name ?? $record->subject_code) }}, '{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}', '{{ $record->status }}')">
                                <i class="bi bi-pencil-square" aria-hidden="true"></i> Appeal Record
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>{{ $records->total() ? 'No records on this page.' : 'No attendance records match your filters.' }}</p>
            </div>
            @endforelse
        </div>
        <div class="px-3 pb-3" data-calendar-month-pagination>{{ $records->links() }}</div>
    </div>

</section>

<!-- Excuse Submission Modal -->
<div class="modal-overlay" id="excuseModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="excuseModalTitle">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title" id="excuseModalTitle">Submit Excuse</div>
            <button type="button" class="modal-close" aria-label="Close excuse form" onclick="closeExcuseModal()">✕</button>
        </div>
        <form id="excuseForm" method="POST" action="{{ route('excuses.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="attendance_id" id="attendanceId">
            <div class="modal-body">
                <div class="excuse-details" id="excuseDetails"></div>
                
                <div class="form-group">
                    <label class="form-label" for="reason">Reason for Absence <span style="color: #dc2626;">*</span></label>
                    <select name="reason" id="reason" class="form-select" required>
                        <option value="">Select a reason</option>
                        <option value="Medical/Health Issues">Medical/Health Issues</option>
                        <option value="Family Emergency">Family Emergency</option>
                        <option value="Personal Emergency">Personal Emergency</option>
                        <option value="Transportation Issues">Transportation Issues</option>
                        <option value="Weather Conditions">Weather Conditions</option>
                        <option value="Academic Activity">Academic Activity</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Detailed Description <span style="color: #dc2626;">*</span></label>
                    <textarea name="description" id="description" class="form-textarea" placeholder="Please provide a detailed explanation of your absence..." required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="attachments">Medical Certificate / Supporting File (Optional)</label>
                    <input type="file" name="attachments[]" id="attachments" class="form-input" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" style="padding: 10px 14px; font-size: 0.85rem;">
                    <small style="color: rgba(245,234,215,0.45); font-size: 0.75rem; margin-top: 4px; display: block;">Supports JPG, PNG, PDF, DOC up to 5MB</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeExcuseModal()">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="submit" class="btn excuse-submit-btn">
                    <i class="bi bi-send" aria-hidden="true"></i> Submit Excuse
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Attendance Correction / Appeal Modal -->
<div class="modal-overlay" id="correctionModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="corrModalTitle">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title" id="corrModalTitle">Appeal Attendance Record</div>
            <button type="button" class="modal-close" aria-label="Close appeal form" onclick="closeCorrectionModal()">✕</button>
        </div>
        <form id="correctionForm" method="POST" action="{{ route('corrections.store') }}">
            @csrf
            <input type="hidden" name="attendance_id" id="corrAttendanceId">
            <div class="modal-body">
                <div class="excuse-details" id="corrDetails"></div>
                <div class="form-group">
                    <label class="form-label" for="corrReason">Reason for Appeal / Correction <span style="color: #FB7185;">*</span></label>
                    <textarea name="reason" id="corrReason" class="form-textarea" placeholder="Explain why your attendance record should be corrected (e.g., scanner error, verified late arrival, technical glitch)..." required rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCorrectionModal()">
                    <i class="bi bi-x-circle" aria-hidden="true"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-check" aria-hidden="true"></i> Submit Appeal
                </button>
            </div>
        </form>
    </div>
</div>

<script @cspNonce>
// Keep these dialogs outside the page's animation and stacking contexts.
['excuseModal', 'correctionModal'].forEach(id => document.body.appendChild(document.getElementById(id)));
let recordDialogReturnFocus = null;
let recordDialogPreviousOverflow = '';
function recordText(value) {
    const element = document.createElement('span');
    element.textContent = String(value ?? '');
    return element.innerHTML;
}
function showRecordDialog(id) {
    const dialog = document.getElementById(id);
    recordDialogReturnFocus = document.activeElement;
    recordDialogPreviousOverflow = document.body.style.overflow;
    dialog.setAttribute('aria-hidden', 'false');
    dialog.classList.add('active');
    document.body.classList.add('student-record-dialog-open');
    document.body.style.overflow = 'hidden';
    dialog.querySelector('select, textarea, input:not([type="hidden"])')?.focus({ preventScroll: true });
}
function hideRecordDialog(id) {
    const dialog = document.getElementById(id);
    if (!dialog.classList.contains('active')) return;
    dialog.classList.remove('active');
    recordDialogReturnFocus?.focus({ preventScroll: true });
    dialog.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('student-record-dialog-open');
    document.body.style.overflow = recordDialogPreviousOverflow;
}
document.addEventListener('keydown', event => {
    const dialog = document.querySelector('#excuseModal.active, #correctionModal.active');
    if (!dialog || event.key !== 'Tab') return;
    const controls = Array.from(dialog.querySelectorAll('button:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href]')).filter(control => control.getClientRects().length);
    const first = controls[0], last = controls[controls.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
});
function openExcuseModal(attendanceId, subjectName, date) {
    document.getElementById('attendanceId').value = attendanceId;
    document.getElementById('excuseDetails').innerHTML = `
        <div style="font-weight: 700; color: #FAF8F5; margin-bottom: 6px; font-size: 1rem;">${recordText(subjectName)}</div>
        <div style="font-size: 0.84rem; color: #ACA099; margin-bottom: 4px;"><strong style="color: #D8CEC8;">Date:</strong> ${recordText(date)}</div>
        <div style="font-size: 0.84rem; color: #ACA099;">
            <strong style="color: #D8CEC8;">Status:</strong> 
            <span style="display:inline-block; padding: 2px 8px; border-radius: 6px; background: rgba(248,113,113,0.14); color: #F87171; font-weight: 700; margin-left: 4px; font-size: 0.74rem;">Absent</span>
        </div>
    `;
    showRecordDialog('excuseModal');
}

function closeExcuseModal() {
    hideRecordDialog('excuseModal');
    document.getElementById('excuseForm').reset();
}

function openCorrectionModal(attendanceId, subjectName, date, currentStatus) {
    document.getElementById('corrAttendanceId').value = attendanceId;
    document.getElementById('corrDetails').innerHTML = `
        <div style="font-weight: 700; color: #FAF8F5; margin-bottom: 6px; font-size: 1rem;">${recordText(subjectName)}</div>
        <div style="font-size: 0.84rem; color: #ACA099; margin-bottom: 4px;"><strong style="color: #D8CEC8;">Date:</strong> ${recordText(date)}</div>
        <div style="font-size: 0.84rem; color: #ACA099;">
            <strong style="color: #D8CEC8;">Recorded Status:</strong> 
            <span style="display:inline-block; padding: 2px 8px; border-radius: 6px; background: rgba(251,191,36,0.14); color: #FBBF24; font-weight: 700; margin-left: 4px; font-size: 0.74rem;">${recordText(currentStatus)}</span>
        </div>
    `;
    showRecordDialog('correctionModal');
}

function closeCorrectionModal() {
    hideRecordDialog('correctionModal');
    document.getElementById('correctionForm').reset();
}

// Close modal when clicking outside
document.getElementById('excuseModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeExcuseModal();
    }
});
document.getElementById('correctionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCorrectionModal();
    }
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('excuseModal').classList.contains('active')) {
            closeExcuseModal();
        }
        if (document.getElementById('correctionModal').classList.contains('active')) {
            closeCorrectionModal();
        }
    }
});

</script>
