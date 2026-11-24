<section class="tch-card" id="attendance-reports" aria-labelledby="assistant-attendance-heading" style="scroll-margin-top:6rem;">
    <div class="tch-card-head">
        <h2 id="assistant-attendance-heading" class="tch-card-title mb-0">
            <i class="bi bi-clipboard-check" aria-hidden="true"></i> Attendance for review
        </h2>
    </div>
    <div class="p-3 p-sm-4">
        @if($canSubmitAttendance)
            <p style="color:#b39b82;">If a classmate is here and cannot check in, send their attendance to the teacher. It becomes official only after the teacher approves it.</p>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($attendanceReportStudents->isEmpty())
                <p class="mb-2" style="color:#b39b82;">There are no classmates available to submit for right now.</p>
            @else
                <form method="POST" action="{{ route('student-assistant.attendance-reports.store', $session) }}" class="d-grid gap-3">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-sm-7">
                            <label for="assistant-report-student" class="form-label">Classmate</label>
                            <select id="assistant-report-student" name="student_id" class="form-select" required @error('student_id') aria-invalid="true" @enderror>
                                <option value="">Choose a classmate</option>
                                @foreach($attendanceReportStudents as $student)
                                    <option value="{{ $student->id }}" @selected((string) old('student_id') === (string) $student->id)>{{ $student->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-5">
                            <label for="assistant-report-status" class="form-label">Observed status</label>
                            <select id="assistant-report-status" name="suggested_status" class="form-select" required @error('suggested_status') aria-invalid="true" @enderror>
                                <option value="">Choose a status</option>
                                <option value="present" @selected(old('suggested_status') === 'present')>Present</option>
                                <option value="late" @selected(old('suggested_status') === 'late')>Late</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="assistant-report-reason" class="form-label">Note <span class="fw-normal">(optional)</span></label>
                        <textarea id="assistant-report-reason" name="reason" class="form-control" rows="2" maxlength="250" aria-describedby="assistant-report-note-help" @error('reason') aria-invalid="true" @enderror>{{ old('reason') }}</textarea>
                        <div id="assistant-report-note-help" class="form-text" style="color:#b39b82;">Add a brief note about the check-in problem, up to 250 characters.</div>
                    </div>
                    <button type="submit" class="ent-btn ent-btn-primary text-wrap" style="width:max-content;max-width:100%;">Send for teacher review</button>
                </form>
            @endif
        @else
            <p style="color:#b39b82;">Your assignment currently allows you to manage the QR. You can still see the results of submissions you already sent.</p>
        @endif

        @if($attendanceReports->isNotEmpty())
            <h3 class="h6 mt-4 mb-3">Your submissions</h3>
            @include('student-assistant.report-list', ['showReportSession' => false])
        @endif
    </div>
</section>
