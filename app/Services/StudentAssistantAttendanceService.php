<?php

namespace App\Services;

use App\Events\TeacherAttendanceUpdated;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\StudentAssistantAttendanceReport;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StudentAssistantAttendanceService
{
    /** @return Collection<int, User> */
    public function eligibleStudents(AttendanceSession $session, User $assistant): Collection
    {
        $subject = $session->subject;
        $recordedIds = $this->attendanceForSession($session, $subject)->pluck('user_id');
        $reportedIds = StudentAssistantAttendanceReport::where('attendance_session_id', $session->id)
            ->pluck('student_id');
        $unavailableIds = $recordedIds->merge($reportedIds)->push($assistant->id);

        return $subject->getAllStudents()
            ->whereNotIn('id', $unavailableIds)
            ->filter(fn (User $student) => $this->studentIsEligible($student, $session))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function submit(AttendanceSession $session, User $assistant, array $data): void
    {
        DB::transaction(function () use ($session, $assistant, $data): void {
            $subject = Subject::where('code', $session->subject_code)->lockForUpdate()->firstOrFail();
            $session = AttendanceSession::whereKey($session->id)
                ->where('subject_code', $subject->code)->lockForUpdate()->firstOrFail();
            $session->setRelation('subject', $subject);
            Gate::forUser($assistant->refresh())->authorize('submitAssistantAttendance', $session);

            $student = User::find($data['student_id']);
            if (!$student || (int) $student->id === (int) $assistant->id
                || !$subject->hasStudent($student) || !$this->studentIsEligible($student, $session)) {
                throw ValidationException::withMessages(['student_id' => 'Select another eligible student in this class.']);
            }

            $alreadyReported = StudentAssistantAttendanceReport::where('attendance_session_id', $session->id)
                ->where('student_id', $student->id)->exists();
            if ($alreadyReported || $this->attendanceForSession($session, $subject)->where('user_id', $student->id)->exists()) {
                throw ValidationException::withMessages(['student_id' => 'This student already has attendance or a submission for this class meeting.']);
            }

            $report = StudentAssistantAttendanceReport::create([
                'attendance_session_id' => $session->id,
                'student_id' => $student->id,
                'submitted_by' => $assistant->id,
                'suggested_status' => $data['suggested_status'],
                'reason' => $data['reason'] ?? null,
            ]);

            activity('student-assistants')->causedBy($assistant)->performedOn($report)
                ->log('assistant_attendance_submitted');
        }, 3);
    }

    public function review(Subject $subject, StudentAssistantAttendanceReport $report, User $teacher, string $decision): void
    {
        try {
            DB::transaction(function () use ($subject, $report, $teacher, $decision): void {
                $subject = Subject::whereKey($subject->id)->lockForUpdate()->firstOrFail();
                abort_unless($teacher->isTeacher() && $teacher->isActive()
                    && (int) $subject->instructor_id === (int) $teacher->id, 403);
                $session = AttendanceSession::whereKey($report->attendance_session_id)
                    ->where('subject_code', $subject->code)->lockForUpdate()->firstOrFail();
                $report = StudentAssistantAttendanceReport::whereKey($report->id)->lockForUpdate()->firstOrFail();

                if ($report->review_status !== 'pending') {
                    throw ValidationException::withMessages(['decision' => 'This submission has already been reviewed.']);
                }

                $attendance = $decision === 'approve'
                    ? $this->approve($session, $subject, $report, $teacher)
                    : null;

                $report->update([
                    'review_status' => $attendance ? 'approved' : 'dismissed',
                    'reviewed_by' => $teacher->id,
                    'reviewed_at' => now(),
                    'attendance_id' => $attendance?->id,
                ]);

                activity('student-assistants')->causedBy($teacher)->performedOn($report)
                    ->withProperties(['attendance_id' => $attendance?->id])
                    ->log($attendance ? 'assistant_attendance_approved' : 'assistant_attendance_dismissed');
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['decision' => 'An attendance record was just created. Review it before dismissing this submission.']);
        }
    }

    private function approve(AttendanceSession $session, Subject $subject, StudentAssistantAttendanceReport $report, User $teacher): Attendance
    {
        $student = $report->student;
        if (!$student || !$subject->hasStudent($student) || !$this->studentIsEligible($student, $session)) {
            throw ValidationException::withMessages(['decision' => 'This student is no longer eligible for attendance in this class. You can dismiss the submission.']);
        }

        $existing = $this->attendanceForSession($session, $subject)->where('user_id', $student->id)
            ->lockForUpdate()->get();
        $attendance = $existing->first();
        if ($attendance && ($existing->count() !== 1 || !$this->isAutomaticAbsence($attendance))) {
            throw ValidationException::withMessages(['decision' => 'An official record already exists for this student. Review it in the class records before dismissing this submission.']);
        }

        $data = [
            'session_id' => $session->id,
            'status' => $report->suggested_status,
            'time_in' => $report->created_at->copy()->timezone('Asia/Manila')->format('H:i:s'),
            'method' => 'manual',
            'verification_channel' => 'assistant_review',
        ];
        if ($attendance) {
            $attendance->update($data);
        } else {
            $attendance = Attendance::create($data + [
                'user_id' => $student->id,
                'subject_id' => $subject->id,
                'subject_code' => $subject->code,
                'subject_name' => $subject->name,
                'class' => $subject->section ?? $student->section ?? 'Regular',
                'date' => $this->attendanceDate($session),
                'academic_year_id' => AcademicYear::where('is_current', true)->value('id'),
            ]);
        }

        DB::afterCommit(function () use ($teacher, $student, $subject, $attendance): void {
            try {
                TeacherAttendanceUpdated::dispatch(
                    $teacher->id, $student->name, $subject->code, $attendance->status,
                    'status_override', (string) $student->id, $attendance->id
                );
            } catch (\Throwable $exception) {
                Log::warning('Assistant attendance review broadcast failed', ['attendance_id' => $attendance->id]);
            }
        });

        return $attendance;
    }

    /** @return Builder<Attendance> */
    private function attendanceForSession(AttendanceSession $session, Subject $subject): Builder
    {
        return Attendance::withTrashed()
            ->whereDate('date', $this->attendanceDate($session))
            ->where(fn (Builder $query) => $query->where('subject_id', $subject->id)
                ->orWhere('subject_code', $subject->code));
    }

    private function attendanceDate(AttendanceSession $session): string
    {
        return $session->created_at->copy()->timezone('Asia/Manila')->toDateString();
    }

    private function studentIsEligible(User $student, AttendanceSession $session): bool
    {
        return $student->isActive()
            && (!$student->created_at || $student->created_at->lte($session->created_at));
    }

    private function isAutomaticAbsence(Attendance $attendance): bool
    {
        return !$attendance->trashed() && $attendance->method === Attendance::METHOD_AUTOMATIC_ABSENCE
            && $attendance->status === 'Absent' && !$attendance->excused && !$attendance->time_in
            && $attendance->created_at?->equalTo($attendance->updated_at);
    }
}
