<section class="assistant-panel" id="attendance-reports" aria-labelledby="assistant-attendance-heading">
    <h2 id="assistant-attendance-heading" class="assistant-panel-title">Attendance for review</h2>
    @if($showSubmissionFeedback)
        <p id="assistant-submission-feedback" tabindex="-1" role="status" class="assistant-notice">
            <i class="bi bi-check-circle" aria-hidden="true"></i> Sent for teacher review.
        </p>
    @endif
    @if($canSubmitAttendance)
        <p class="assistant-muted">
            Use this when a classmate is here and cannot check in. The teacher approves submissions before they count as attendance.
        </p>

        @if($attendanceReportStudents->isEmpty())
            @error('student_id')
                <p class="assistant-report-feedback" role="alert" tabindex="-1">{{ $message }}</p>
            @enderror
            <div class="assistant-empty">
                <strong>No classmates to report</strong>
                <p class="assistant-muted mb-0">Classmates appear here when they have neither an attendance record nor an earlier submission for this session.</p>
            </div>
        @else
            @php
                $previousStudent = old('student_id');
                $previousStatus = old('suggested_status');
                $previousNote = old('reason');
            @endphp

            <form method="POST" action="{{ route('student-assistant.attendance-reports.store', $session) }}" class="assistant-report-form d-grid gap-3">
                @csrf
                <div>
                    <label for="assistant-report-student" class="form-label">Classmate</label>
                    <select id="assistant-report-student" name="student_id" class="assistant-classmate-select" required
                            @error('student_id') aria-invalid="true" aria-describedby="assistant-report-student-error" @enderror>
                        <option value="">Choose a classmate</option>
                        @foreach($attendanceReportStudents as $student)
                            <option value="{{ $student->id }}" @selected(is_scalar($previousStudent) && (string) $previousStudent === (string) $student->id)>{{ $student->name }}</option>
                        @endforeach
                    </select>
                    @error('student_id')
                        <p id="assistant-report-student-error" class="assistant-report-feedback" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <fieldset class="m-0">
                    <legend class="form-label">Observed status</legend>
                    <div class="assistant-report-choices d-grid gap-2">
                        @foreach(['present' => 'Present', 'late' => 'Late'] as $value => $label)
                            <label for="assistant-report-status-{{ $value }}" class="assistant-report-choice d-flex align-items-center gap-2">
                                <input id="assistant-report-status-{{ $value }}" type="radio" name="suggested_status" value="{{ $value }}" required
                                       @checked($previousStatus === $value) @error('suggested_status') aria-invalid="true" aria-describedby="assistant-report-status-error" @enderror>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('suggested_status')
                        <p id="assistant-report-status-error" class="assistant-report-feedback" role="alert">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div>
                    <label for="assistant-report-reason" class="form-label">Note <span class="assistant-muted fw-normal">(optional)</span></label>
                    <textarea id="assistant-report-reason" name="reason" class="form-control" rows="2" maxlength="250"
                              aria-describedby="assistant-report-note-help{{ $errors->has('reason') ? ' assistant-report-note-error' : '' }}"
                              @error('reason') aria-invalid="true" @enderror>{{ is_string($previousNote) ? $previousNote : '' }}</textarea>
                    <p id="assistant-report-note-help" class="assistant-muted form-text mb-0">Briefly explain what stopped the check-in. Up to 250 characters.</p>
                    @error('reason')
                        <p id="assistant-report-note-error" class="assistant-report-feedback" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="assistant-report-form-actions">
                    <button type="submit" class="assistant-button assistant-button--primary">
                        <i class="bi bi-send" aria-hidden="true"></i> Send for teacher review
                    </button>
                </div>
            </form>
        @endif
    @endif

    @if($attendanceReports->isNotEmpty())
        <div class="assistant-report-history">
            <h3 class="h6 mb-3">Your submissions for this session</h3>
            @include('student-assistant.report-list', ['showReportSession' => false])
        </div>
    @elseif($canSubmitAttendance)
        <p class="assistant-muted mt-3 mb-0">Your submissions and their review status will appear here.</p>
    @endif
</section>
