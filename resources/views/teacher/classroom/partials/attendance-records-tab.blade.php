@php
    $activeRecordFilters = array_filter($recordFilters ?? [], fn ($val) => filled($val));
    $exportQuery = array_merge($activeRecordFilters, [
        'subject' => $subject->code,
        'subject_id' => $subject->id,
    ]);
    $sessionLookup = collect($attendanceSessions ?? [])->keyBy('id');
@endphp

<div class="tc-panel">
    <div class="tc-panel-head">
        <div>
            <h2 class="tc-panel-title">Attendance Records</h2>
            <p class="tc-panel-sub">Complete class-scoped attendance log for {{ $subject->code }} &mdash; {{ $subject->name }}.</p>
        </div>
        <div class="tc-actions-bar">
            <a href="{{ route('teacher.attendance.preview', $exportQuery) }}" class="ent-btn ent-btn-secondary">
                <i class="bi bi-eye-fill" aria-hidden="true"></i> Preview PDF
            </a>
            <a href="{{ route('teacher.attendance.csv', $exportQuery) }}" class="ent-btn ent-btn-secondary">
                <i class="bi bi-filetype-csv" aria-hidden="true"></i> Export CSV
            </a>
            <a href="{{ route('teacher.attendance.xlsx', $exportQuery) }}" class="ent-btn ent-btn-secondary">
                <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Export Excel
            </a>
        </div>
    </div>

    <div class="tc-panel-body">
        <div class="tc-stats-strip mb-4" aria-label="Filtered attendance statistics">
            <div class="tc-stat-chip">
                <span class="tc-stat-label">{{ !empty($activeRecordFilters) ? 'Matching Records' : 'Total Records' }}</span>
                <span class="tc-stat-value portal-mono">{{ $filteredStats['total'] ?? 0 }}</span>
            </div>
            <div class="tc-stat-chip is-present">
                <span class="tc-stat-label">Present</span>
                <span class="tc-stat-value portal-mono">{{ $filteredStats['present'] ?? 0 }}</span>
            </div>
            <div class="tc-stat-chip is-late">
                <span class="tc-stat-label">Late</span>
                <span class="tc-stat-value portal-mono">{{ $filteredStats['late'] ?? 0 }}</span>
            </div>
            <div class="tc-stat-chip is-absent">
                <span class="tc-stat-label">Absent</span>
                <span class="tc-stat-value portal-mono">{{ $filteredStats['absent'] ?? 0 }}</span>
            </div>
            <div class="tc-stat-chip is-excused">
                <span class="tc-stat-label">Excused</span>
                <span class="tc-stat-value portal-mono">{{ $filteredStats['excused'] ?? 0 }}</span>
            </div>
            <div class="tc-stat-chip">
                <span class="tc-stat-label">Turnout Rate</span>
                <span class="tc-stat-value portal-mono">{{ $filteredStats['rate'] ?? 0 }}%</span>
            </div>
        </div>

        <form method="GET" action="{{ route('teacher.classroom.show', $subject->code) }}" class="tc-filter-bar mb-4" aria-label="Filter class attendance records">
            <input type="hidden" name="tab" value="records">

            <div class="tc-filter-field">
                <label class="ds-label" for="tcFilterDate">Date</label>
                <input type="date" id="tcFilterDate" name="date" class="ds-input" value="{{ $recordFilters['date'] ?? '' }}">
            </div>

            <div class="tc-filter-field">
                <label class="ds-label" for="tcFilterStudent">Student</label>
                <select id="tcFilterStudent" name="student_id" class="ds-select">
                    <option value="">All Enrolled Students</option>
                    @foreach($students as $stud)
                        <option value="{{ $stud->id }}" @selected((string) ($recordFilters['student_id'] ?? '') === (string) $stud->id)>
                            {{ $stud->name }}@if($stud->student_number) ({{ $stud->student_number }})@endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="tc-filter-field">
                <label class="ds-label" for="tcFilterSearch">Search student</label>
                <input type="search" id="tcFilterSearch" name="student_name" class="ds-input" placeholder="Name or student ID" value="{{ $recordFilters['student_name'] ?? '' }}">
            </div>

            <div class="tc-filter-field">
                <label class="ds-label" for="tcFilterStatus">Status</label>
                <select id="tcFilterStatus" name="status" class="ds-select">
                    <option value="">All Statuses</option>
                    @foreach(['Present', 'Late', 'Absent', 'Excused'] as $statusOption)
                        <option value="{{ $statusOption }}" @selected(($recordFilters['status'] ?? '') === $statusOption)>{{ $statusOption }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tc-filter-field">
                <label class="ds-label" for="tcFilterSession">Session</label>
                <select id="tcFilterSession" name="session_id" class="ds-select">
                    <option value="">All Sessions</option>
                    <option value="manual" @selected(($recordFilters['session_id'] ?? '') === 'manual')>Manual / Unlinked</option>
                    @foreach($attendanceSessions as $sess)
                        <option value="{{ $sess->id }}" @selected((string) ($recordFilters['session_id'] ?? '') === (string) $sess->id)>
                            {{ $sess->created_at?->format('M d, Y g:i A') ?? 'Session' }} ({{ $sess->session_code ?: '#' . $sess->id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="tc-filter-actions">
                <button type="submit" class="ent-btn ent-btn-primary">
                    <i class="bi bi-funnel" aria-hidden="true"></i> Filter
                </button>
                @if(!empty($activeRecordFilters))
                    <a href="{{ route('teacher.classroom.show', ['subjectCode' => $subject->code, 'tab' => 'records']) }}" class="ent-btn ent-btn-secondary">
                        Clear
                    </a>
                @endif
            </div>
        </form>

        <div id="tcRecordsFeedback" class="tc-inline-feedback" role="status" aria-live="polite" hidden></div>

        @if($attendanceRecords->isEmpty())
            <div class="empty-state tc-empty-state">
                <i class="bi bi-calendar-x" aria-hidden="true"></i>
                <h3>{{ !empty($activeRecordFilters) ? 'No matching attendance records' : 'No attendance records yet' }}</h3>
                <p>
                    {{ !empty($activeRecordFilters)
                        ? 'Try adjusting or clearing your filters to see all records for ' . $subject->code . '.'
                        : 'Start a live QR session or record roll call manually under Attendance Sessions.' }}
                </p>
                <div class="d-flex justify-content-center gap-2 flex-wrap mt-3">
                    @if(!empty($activeRecordFilters))
                        <a href="{{ route('teacher.classroom.show', ['subjectCode' => $subject->code, 'tab' => 'records']) }}" class="ent-btn ent-btn-secondary">Clear Filters</a>
                    @else
                        <a href="{{ route('teacher.qr', $subject->code) }}" class="ent-btn ent-btn-primary"><i class="bi bi-qr-code-scan" aria-hidden="true"></i> Start QR Session</a>
                        <button type="button" class="ent-btn ent-btn-secondary" data-switch-tab="sessions">Record Manually</button>
                    @endif
                </div>
            </div>
        @else
            <div class="tc-table-scroll">
                <table class="tch-table tc-records-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Session / Method</th>
                            <th>Time In</th>
                            <th>Status</th>
                            <th>Excused / Notes</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendanceRecords as $record)
                            @php
                                $resolvedStatus = $record->excused ? 'Excused' : ucfirst(strtolower((string) ($record->status ?? 'Absent')));
                                $statusBadgeType = match (strtolower($resolvedStatus)) {
                                    'present' => 'present',
                                    'late' => 'late',
                                    'excused' => 'excused',
                                    default => 'absent',
                                };
                                $linkedSession = $record->session ?? ($record->session_id ? $sessionLookup->get($record->session_id) : null);
                            @endphp
                            <tr data-record-row="{{ $record->id }}">
                                <td data-label="Date">
                                    <div style="font-weight: 700; color: #faf8f5;">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</div>
                                    <div style="font-size: 0.74rem; color: #a49890;">{{ \Carbon\Carbon::parse($record->date)->format('l') }}</div>
                                </td>
                                <td data-label="Student">
                                    @if($record->user)
                                        <a href="{{ route('teacher.student', $record->user_id) }}" class="tc-student-link">
                                            {{ $record->user->name }}
                                        </a>
                                        @if($record->user->student_number)
                                            <div class="portal-mono" style="font-size: 0.74rem; color: #a49890;">{{ $record->user->student_number }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">Unknown Student</span>
                                    @endif
                                </td>
                                <td data-label="Session / Method">
                                    @if($linkedSession)
                                        <div class="portal-mono" style="font-size: 0.78rem; color: #d4af37; font-weight: 700;">
                                            {{ $linkedSession->session_code ?: 'Session #' . $linkedSession->id }}
                                        </div>
                                        <div style="font-size: 0.73rem; color: #a49890;">{{ $linkedSession->created_at?->format('M d, g:i A') }}</div>
                                    @else
                                        <span class="tc-method-tag">{{ ucfirst(str_replace('_', ' ', (string) ($record->method ?: 'manual'))) }}</span>
                                    @endif
                                </td>
                                <td data-label="Time In" class="portal-mono">
                                    {{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '—' }}
                                </td>
                                <td data-label="Status">
                                    <div class="dropdown">
                                        <button
                                            type="button"
                                            id="statusBtn_{{ $record->id }}"
                                            class="btn btn-sm btn-outline dropdown-toggle tc-status-dropdown-btn"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <x-badge :type="$statusBadgeType">{{ $resolvedStatus }}</x-badge>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-dark">
                                            <li><a class="dropdown-item status-override" href="#" data-id="{{ $record->id }}" data-status="Present"><span class="text-success"><i class="bi bi-circle-fill me-2"></i>Present</span></a></li>
                                            <li><a class="dropdown-item status-override" href="#" data-id="{{ $record->id }}" data-status="Late"><span class="text-warning"><i class="bi bi-circle-fill me-2"></i>Late</span></a></li>
                                            <li><a class="dropdown-item status-override" href="#" data-id="{{ $record->id }}" data-status="Absent"><span class="text-danger"><i class="bi bi-circle-fill me-2"></i>Absent</span></a></li>
                                            <li><a class="dropdown-item status-override" href="#" data-id="{{ $record->id }}" data-status="Excused"><span class="text-info"><i class="bi bi-circle-fill me-2"></i>Excused</span></a></li>
                                        </ul>
                                    </div>
                                </td>
                                <td data-label="Excused / Notes" id="excuseCell_{{ $record->id }}">
                                    @if($record->excused)
                                        <span class="text-success" style="font-size: 0.82rem; font-weight: 700;"><i class="bi bi-check-lg" aria-hidden="true"></i> Excused</span>
                                        @if($record->excuse_note)
                                            <div style="font-size: 0.74rem; color: #d6ccc6; margin-top: 2px;">{{ $record->excuse_note }}</div>
                                        @endif
                                    @else
                                        <span style="color: #a49890;">—</span>
                                    @endif
                                </td>
                                <td data-label="Actions" class="text-end">
                                    <button
                                        type="button"
                                        class="ent-btn ent-btn-secondary btn-sm tc-correct-btn"
                                        data-correct-record
                                        data-record-id="{{ $record->id }}"
                                        data-student-name="{{ $record->user->name ?? 'Student' }}"
                                        data-record-date="{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}"
                                        data-status="{{ $resolvedStatus }}"
                                        data-excuse-note="{{ $record->excuse_note ?? '' }}">
                                        <i class="bi bi-pencil" aria-hidden="true"></i> Correct
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($attendanceRecords->hasPages() || $attendanceRecords->total() > 0)
                <div class="tc-pagination-bar">
                    <div class="tc-pagination-summary">
                        Showing {{ $attendanceRecords->firstItem() }}&ndash;{{ $attendanceRecords->lastItem() }} of {{ $attendanceRecords->total() }} records
                    </div>
                    @if($attendanceRecords->hasPages())
                        <div>
                            {{ $attendanceRecords->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>
</div>
