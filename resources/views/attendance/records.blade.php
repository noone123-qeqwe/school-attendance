<section class="sf-card" id="attendance-records" aria-labelledby="attendanceRecordsTitle" tabindex="-1">
    @php
        $activeStatusFilter = $filters['status'] ?? '';
        $baseRecordParams = array_filter([
            'year' => $year,
            'month' => $month,
            'q' => $filters['q'] ?? null,
            'record_date' => $filters['record_date'] ?? null,
        ]);
        $activeFilterCount = count(array_filter([
            $filters['q'] ?? null,
            $filters['status'] ?? null,
            $filters['record_date'] ?? null,
        ]));
    @endphp

    <div class="sf-card-head">
        <div>
            <h2 class="sf-card-title" id="attendanceRecordsTitle">Attendance History</h2>
        </div>
        <div class="sf-filter-pills" role="navigation" aria-label="Filter records by status">
            <a href="{{ route('student.calendar', $baseRecordParams) }}#attendance-records"
               data-calendar-month-link
               class="sf-filter-pill {{ $activeStatusFilter === '' ? 'active' : '' }}"
               @if($activeStatusFilter === '') aria-current="true" @endif>
                All <span class="sf-mono" style="opacity:0.75;">{{ $totals['all'] }}</span>
            </a>
            <a href="{{ route('student.calendar', $activeStatusFilter === 'Present' ? $baseRecordParams : array_merge($baseRecordParams, ['status' => 'Present'])) }}#attendance-records"
               data-calendar-month-link
               class="sf-filter-pill {{ $activeStatusFilter === 'Present' ? 'active' : '' }}"
               @if($activeStatusFilter === 'Present') aria-current="true" @endif>
                Present <span class="sf-mono" style="opacity:0.75;">{{ $totals['Present'] ?? 0 }}</span>
            </a>
            <a href="{{ route('student.calendar', $activeStatusFilter === 'Late' ? $baseRecordParams : array_merge($baseRecordParams, ['status' => 'Late'])) }}#attendance-records"
               data-calendar-month-link
               class="sf-filter-pill {{ $activeStatusFilter === 'Late' ? 'active' : '' }}"
               @if($activeStatusFilter === 'Late') aria-current="true" @endif>
                Late <span class="sf-mono" style="opacity:0.75;">{{ $totals['Late'] ?? 0 }}</span>
            </a>
            <a href="{{ route('student.calendar', $activeStatusFilter === 'Absent' ? $baseRecordParams : array_merge($baseRecordParams, ['status' => 'Absent'])) }}#attendance-records"
               data-calendar-month-link
               class="sf-filter-pill {{ $activeStatusFilter === 'Absent' ? 'active' : '' }}"
               @if($activeStatusFilter === 'Absent') aria-current="true" @endif>
                Absent <span class="sf-mono" style="opacity:0.75;">{{ $totals['Absent'] ?? 0 }}</span>
            </a>
            <a href="{{ route('student.calendar', $activeStatusFilter === 'Excused' ? $baseRecordParams : array_merge($baseRecordParams, ['status' => 'Excused'])) }}#attendance-records"
               data-calendar-month-link
               class="sf-filter-pill {{ $activeStatusFilter === 'Excused' ? 'active' : '' }}"
               @if($activeStatusFilter === 'Excused') aria-current="true" @endif>
                Excused <span class="sf-mono" style="opacity:0.75;">{{ $totals['Excused'] ?? 0 }}</span>
            </a>
        </div>
    </div>

    {{-- Inline Search & Date Filter --}}
    <div style="padding:12px 16px;border-bottom:1px solid var(--sf-border);background:var(--sf-surface-2);">
        <form method="GET" action="{{ route('student.calendar') }}#attendance-records" class="sf-records-filter-form">
            <input type="hidden" name="year" value="{{ $year }}" data-calendar-month="year">
            <input type="hidden" name="month" value="{{ $month }}" data-calendar-month="month">
            @if(!empty($filters['status']))
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
            @endif
            <div>
                <label class="sf-label" for="attendanceRecordsSearch">Search course or instructor</label>
                <input type="search" class="sf-input" style="min-height:34px;font-size:12.5px;" id="attendanceRecordsSearch" name="q" maxlength="120" placeholder="Code, subject, or teacher..." value="{{ $filters['q'] ?? '' }}">
            </div>
            <div>
                <label class="sf-label" for="studentRecordDate">Date</label>
                <input type="date" class="sf-input" style="min-height:34px;font-size:12.5px;" id="studentRecordDate" name="record_date" value="{{ $filters['record_date'] ?? '' }}">
            </div>
            <button type="submit" class="sf-btn sf-btn-primary sf-btn-sm" style="height:34px;">Apply</button>
            @if($activeFilterCount > 0)
                <a class="sf-btn sf-btn-ghost sf-btn-sm" style="height:34px;" href="{{ route('student.calendar', compact('year', 'month')) }}#attendance-records" data-calendar-month-link>Clear</a>
            @endif
        </form>
    </div>

    {{-- Records List --}}
    <div class="sf-records-list" id="recordsContainer">
        @forelse($records as $record)
            @php
                $parsedDate = \Carbon\Carbon::parse($record->date);
                $clockInTime = $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('g:i A') : ($record->checked_in_at ? $record->checked_in_at->format('g:i A') : null);
                $clockInText = $clockInTime ?? ($record->status === 'Absent' ? 'No check-in' : '—');
                $instName = $record->subject?->instructorUser?->name ?? $record->subject?->instructor ?? 'Instructor TBA';
                $rawMethod = strtolower(trim((string) ($record->method ?? '')));
                $methodLabel = match ($rawMethod) {
                    'qr', 'qr_code', 'qr_scan' => 'QR scan',
                    'code', 'pin', 'session_code' => 'PIN code',
                    'manual_gps', 'gps' => 'GPS check-in',
                    'manual', 'teacher' => 'Teacher verified',
                    'peer', 'peer_snap' => 'Peer check-in',
                    '' => null,
                    default => \Illuminate\Support\Str::headline($rawMethod),
                };
                $displayStatus = ($record->excused || $record->status === 'Excused') ? 'Excused' : $record->status;
                $statusSlug = match (strtolower((string) $displayStatus)) {
                    'present' => 'present',
                    'late' => 'late',
                    'excused' => 'excused',
                    default => 'absent',
                };
                $pillClass = match ($statusSlug) {
                    'present' => 'is-emerald',
                    'late' => 'is-amber',
                    'excused' => 'is-sky',
                    default => 'is-danger',
                };
            @endphp
            <article class="sf-record-item record-card" data-status="{{ $record->status }}" data-excused="{{ $record->excused || $record->status === 'Excused' ? '1' : '0' }}">
                <div class="sf-record-main">
                    <div class="sf-record-left">
                        <span class="sf-status-dot is-{{ $statusSlug }}" aria-hidden="true"></span>
                        <div style="min-width:0;flex:1;">
                            <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                                @if($record->subject_code)
                                    <span class="sf-code-chip is-rose">{{ $record->subject_code }}</span>
                                @endif
                                <strong style="font-size:13px;color:var(--sf-text);">{{ $record->subject->name ?? $record->subject_name ?? $record->subject_code }}</strong>
                            </div>
                            <div style="font-size:11.5px;color:var(--sf-text-muted);margin-top:3px;display:flex;gap:8px;flex-wrap:wrap;">
                                <span class="sf-mono">{{ $parsedDate->format('D, M j, Y') }}</span>
                                <span>·</span>
                                <span class="sf-mono">{{ $clockInText }}</span>
                                @if($methodLabel)
                                    <span>·</span>
                                    <span>{{ $methodLabel }}</span>
                                @endif
                                <span>·</span>
                                <span>{{ $instName }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="sf-record-actions">
                        <span class="sf-pill {{ $pillClass }}">{{ $displayStatus }}</span>

                        @if($record->status === 'Absent' && !$record->excuseSubmission)
                            <button type="button" class="sf-btn sf-btn-secondary sf-btn-sm"
                                    onclick="openExcuseModal({{ $record->id }}, {{ \Illuminate\Support\Js::from($record->subject->name ?? $record->subject_code) }}, '{{ $parsedDate->format('M d, Y') }}')">
                                Excuse
                            </button>
                        @elseif($record->excuseSubmission)
                            <span class="sf-pill {{ $record->excuseSubmission->status === 'approved' ? 'is-emerald' : ($record->excuseSubmission->status === 'rejected' ? 'is-danger' : 'is-amber') }}">
                                Excuse: {{ ucfirst($record->excuseSubmission->status) }}
                            </span>
                        @endif

                        @if($record->correction)
                            <span class="sf-pill is-sky">Appeal: {{ ucfirst($record->correction->status) }}</span>
                        @elseif(in_array($record->status, ['Late', 'Absent']))
                            <button type="button" class="sf-btn sf-btn-ghost sf-btn-sm"
                                    onclick="openCorrectionModal({{ $record->id }}, {{ \Illuminate\Support\Js::from($record->subject->name ?? $record->subject_code) }}, '{{ $parsedDate->format('M d, Y') }}', '{{ $record->status }}')">
                                Appeal
                            </button>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="sf-empty-state">
                <span class="sf-empty-icon-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                </span>
                <h3>{{ $records->total() ? 'No records on this page' : 'No attendance records yet' }}</h3>
                <p>Use Check in to scan a classroom QR code or enter your instructor's 6-digit PIN.</p>
                <a href="{{ route('mobile.scan') }}" class="sf-btn sf-btn-secondary sf-btn-sm" style="margin-top:6px;">Open Scanner</a>
            </div>
        @endforelse
    </div>

    @if($records->hasPages())
        <div class="sf-pagination-wrap" style="padding:12px 16px;border-top:1px solid var(--sf-border);" data-calendar-month-pagination>
            {{ $records->links('pagination::bootstrap-5') }}
        </div>
    @endif
</section>

{{-- Excuse Submission Modal --}}
<div class="sf-modal-backdrop" id="excuseModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="excuseModalTitle">
    <div class="sf-modal-card">
        <div class="sf-modal-head">
            <div>
                <h3 id="excuseModalTitle">Submit Excuse</h3>
                <p id="excuseDetails">Provide supporting documentation for this absence</p>
            </div>
            <button type="button" class="sf-modal-close" aria-label="Close excuse form" onclick="closeExcuseModal()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form id="excuseForm" method="POST" action="{{ route('excuses.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="attendance_id" id="attendanceId">
            <div class="sf-modal-body" style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label class="sf-label" for="reason">Reason for Absence *</label>
                    <select name="reason" id="reason" class="sf-select" required>
                        <option value="">Select a reason</option>
                        <option value="Medical/Health Issues">Medical / Health Issues</option>
                        <option value="Family Emergency">Family Emergency</option>
                        <option value="Personal Emergency">Personal Emergency</option>
                        <option value="Transportation Issues">Transportation Issues</option>
                        <option value="Weather Conditions">Weather Conditions</option>
                        <option value="Academic Activity">Academic Activity</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="sf-label" for="description">Explanation *</label>
                    <textarea name="description" id="description" class="sf-textarea" placeholder="Briefly explain why you missed this class..." required></textarea>
                </div>
                <div>
                    <label class="sf-label" for="attachments">Supporting Document (Optional)</label>
                    <input type="file" name="attachments[]" id="attachments" class="sf-input" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                </div>
            </div>
            <div class="sf-modal-foot">
                <button type="button" class="sf-btn sf-btn-ghost" onclick="closeExcuseModal()">Cancel</button>
                <button type="submit" class="sf-btn sf-btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
    <button type="button" class="sf-modal-circle-close sf-mobile-only" onclick="closeExcuseModal()" aria-label="Close excuse modal">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
</div>

{{-- Attendance Appeal / Correction Modal --}}
<div class="sf-modal-backdrop" id="correctionModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="corrModalTitle">
    <div class="sf-modal-card">
        <div class="sf-modal-head">
            <div>
                <h3 id="corrModalTitle">Appeal Attendance Record</h3>
                <p id="corrDetails">Request an attendance correction review</p>
            </div>
            <button type="button" class="sf-modal-close" aria-label="Close appeal form" onclick="closeCorrectionModal()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form id="correctionForm" method="POST" action="{{ route('corrections.store') }}">
            @csrf
            <input type="hidden" name="attendance_id" id="corrAttendanceId">
            <div class="sf-modal-body">
                <label class="sf-label" for="corrReason">Reason for Appeal / Correction *</label>
                <textarea name="reason" id="corrReason" class="sf-textarea" placeholder="Explain why this attendance record should be corrected..." required rows="4"></textarea>
            </div>
            <div class="sf-modal-foot">
                <button type="button" class="sf-btn sf-btn-ghost" onclick="closeCorrectionModal()">Cancel</button>
                <button type="submit" class="sf-btn sf-btn-primary">Submit Appeal</button>
            </div>
        </form>
    </div>
    <button type="button" class="sf-modal-circle-close sf-mobile-only" onclick="closeCorrectionModal()" aria-label="Close appeal modal">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
</div>

<script @cspNonce>
function openExcuseModal(attendanceId, subjectName, date) {
    document.getElementById('attendanceId').value = attendanceId;
    document.getElementById('excuseDetails').textContent = subjectName + ' · Absent on ' + date;
    window.openSfModal?.('excuseModal');
}
function closeExcuseModal() {
    window.closeSfModal?.('excuseModal');
    document.getElementById('excuseForm')?.reset();
}
function openCorrectionModal(attendanceId, subjectName, date, currentStatus) {
    document.getElementById('corrAttendanceId').value = attendanceId;
    document.getElementById('corrDetails').textContent = subjectName + ' · ' + date + ' (' + currentStatus + ')';
    window.openSfModal?.('correctionModal');
}
function closeCorrectionModal() {
    window.closeSfModal?.('correctionModal');
    document.getElementById('correctionForm')?.reset();
}
</script>
