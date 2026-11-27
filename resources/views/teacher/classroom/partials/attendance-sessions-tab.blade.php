<div class="tc-sessions-stack">
    <section class="tc-panel" aria-labelledby="tcSessionsHeading">
        <div class="tc-panel-head">
            <div>
                <h2 id="tcSessionsHeading" class="tc-panel-title">Attendance Sessions</h2>
                <p class="tc-panel-sub">Review QR sessions launched for {{ $subject->code }} or open session-filtered logs.</p>
            </div>
            <div class="tc-actions-bar">
                <a href="{{ route('teacher.qr', $subject->code) }}" class="ent-btn ent-btn-primary">
                    <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Start QR Session
                </a>
                <a href="{{ route('teacher.classroom.assistant-attendance.index', $subject->code) }}" class="ent-btn ent-btn-secondary">
                    <i class="bi bi-clipboard-check" aria-hidden="true"></i> Assistant Submissions
                </a>
            </div>
        </div>

        <div class="tc-panel-body">
            @if($attendanceSessions->isEmpty())
                <div class="empty-state tc-empty-state">
                    <i class="bi bi-qr-code" aria-hidden="true"></i>
                    <h3>No QR sessions started yet</h3>
                    <p>Start a live QR session for {{ $subject->code }} or mark manual roll call below.</p>
                </div>
            @else
                <div class="tc-session-list">
                    @foreach($attendanceSessions as $session)
                        @php
                            $sessionActive = method_exists($session, 'isSessionActive')
                                ? $session->isSessionActive()
                                : (bool) $session->active;
                        @endphp
                        <div class="tc-session-row">
                            <div class="tc-session-main">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="tc-code-pill portal-mono">{{ $session->session_code ?: 'Session #' . $session->id }}</span>
                                    <span class="portal-status-pill {{ $sessionActive ? 'is-present' : 'is-muted' }}">
                                        {{ $sessionActive ? 'Active' : 'Ended' }}
                                    </span>
                                    <strong style="color: #faf8f5; font-size: 0.9rem;">
                                        {{ $session->created_at?->format('M d, Y \· g:i A') ?? 'Scheduled session' }}
                                    </strong>
                                </div>
                                <div class="tc-session-meta portal-tabular">
                                    <span><i class="bi bi-people" aria-hidden="true"></i> {{ (int) ($session->attendances_count ?? 0) }} check-ins</span>
                                    <span><i class="bi bi-person" aria-hidden="true"></i> Host: {{ $session->creator?->name ?? 'Instructor' }}</span>
                                    @if($session->session_ends_at)
                                        <span><i class="bi bi-clock" aria-hidden="true"></i> Ended {{ $session->session_ends_at->format('g:i A') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="tc-session-actions">
                                <a href="{{ route('teacher.classroom.show', ['subjectCode' => $subject->code, 'tab' => 'records', 'session_id' => $session->id]) }}" class="ent-btn ent-btn-secondary btn-sm">
                                    View Records <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="tc-panel" aria-labelledby="tcManualRollCallHeading">
        <div class="tc-panel-head">
            <div>
                <h2 id="tcManualRollCallHeading" class="tc-panel-title">Manual Attendance Roll Call</h2>
                <p class="tc-panel-sub">Batch-record attendance for {{ $subject->code }} when running offline or manual roll call.</p>
            </div>
        </div>

        <div class="tc-panel-body">
            <div id="oaOfflineBannerSlot"></div>
            <form method="POST" action="{{ route('teacher.classroom.attendance.store', $subject->code) }}" id="manualAttendanceForm">
                @csrf
                <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-3">
                    <div class="ds-form-group mb-0" style="min-width: 210px;">
                        <label class="ds-label" for="manualAttendanceDate">Session Date</label>
                        <input type="date" id="manualAttendanceDate" name="date" class="ds-input" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" id="markAllPresentBtn" class="ent-btn ent-btn-secondary">
                            <i class="bi bi-check-all" aria-hidden="true"></i> Mark All Present
                        </button>
                        <button type="submit" class="ent-btn ent-btn-primary">
                            <i class="bi bi-save" aria-hidden="true"></i> Save Attendance
                        </button>
                    </div>
                </div>

                @if($students->isEmpty())
                    <div class="empty-state tc-empty-state">
                        <i class="bi bi-people" aria-hidden="true"></i>
                        <h3>No enrolled students</h3>
                        <p>No students are currently assigned to {{ $subject->code }}.</p>
                    </div>
                @else
                    <div class="tc-table-scroll">
                        <table class="tch-table">
                            <thead>
                                <tr>
                                    <th>Student Number</th>
                                    <th>Name</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    <tr data-student-id="{{ $student->id }}" data-student-name="{{ $student->name }}" data-student-number="{{ $student->student_number }}">
                                        <td data-label="Student Number">
                                            <span class="portal-mono">{{ $student->student_number ?: '—' }}</span>
                                        </td>
                                        <td data-label="Name" style="font-weight: 650; color: #faf8f5;">{{ $student->name }}</td>
                                        <td data-label="Status" class="text-center">
                                            <div class="d-flex gap-2 justify-content-center flex-wrap">
                                                <label class="attendance-radio-label">
                                                    <input type="radio" name="attendance[{{ $student->id }}]" value="Present" checked>
                                                    <span class="attendance-radio-pill present"><i class="bi bi-check-lg" aria-hidden="true"></i> Present</span>
                                                </label>
                                                <label class="attendance-radio-label">
                                                    <input type="radio" name="attendance[{{ $student->id }}]" value="Late">
                                                    <span class="attendance-radio-pill late"><i class="bi bi-clock" aria-hidden="true"></i> Late</span>
                                                </label>
                                                <label class="attendance-radio-label">
                                                    <input type="radio" name="attendance[{{ $student->id }}]" value="Absent">
                                                    <span class="attendance-radio-pill absent"><i class="bi bi-x-lg" aria-hidden="true"></i> Absent</span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </form>
        </div>
    </section>
</div>
