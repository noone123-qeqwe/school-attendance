<?php

namespace App\Services;

use App\Events\AttendanceQrChanged;
use App\Models\AttendanceQrToken;
use App\Models\AttendanceSession;
use App\Models\ClassStudentAssistant;
use App\Models\Notification;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StudentAssistantAssignmentService
{
    public function assign(Subject $subject, User $actor, array $data, ?ClassStudentAssistant $replacement = null): ClassStudentAssistant
    {
        return DB::transaction(function () use ($subject, $actor, $data, $replacement) {
            $subject = Subject::whereKey($subject->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manageAssignments', [ClassStudentAssistant::class, $subject]);

            $instructor = $actor->isTeacher() ? $actor : $subject->instructorUser;
            if (!$instructor?->isTeacher() || !$instructor->isActive()) {
                throw ValidationException::withMessages(['student_id' => 'Assign an active teacher to this class first.']);
            }

            $student = User::whereKey($data['student_id'])->where('role', 'student')
                ->where('is_active', true)->first();
            if (!$student || !$subject->hasStudent($student)) {
                throw ValidationException::withMessages(['student_id' => 'Select an active student enrolled in this class.']);
            }

            $this->releaseExpiredSlots($subject);

            $oldAssignment = null;
            if ($replacement) {
                $oldAssignment = ClassStudentAssistant::whereKey($replacement->id)
                    ->where('subject_id', $subject->id)->lockForUpdate()->firstOrFail();
                if ($oldAssignment->active_slot === null || $oldAssignment->revoked_at !== null || $oldAssignment->expires_at < now()) {
                    throw ValidationException::withMessages(['student_id' => 'The assistant to replace is no longer assigned.']);
                }
                if ((int) $oldAssignment->student_id === (int) $student->id) {
                    throw ValidationException::withMessages(['student_id' => 'Choose a different student as the replacement.']);
                }
            }

            $existing = ClassStudentAssistant::where('subject_id', $subject->id)
                ->where('student_id', $student->id)->lockForUpdate()->first();
            if ($existing && $existing->active_slot !== null) {
                throw ValidationException::withMessages(['student_id' => 'This student is already assigned.']);
            }

            $slot = $oldAssignment?->active_slot;
            if ($slot === null) {
                $occupied = ClassStudentAssistant::where('subject_id', $subject->id)
                    ->whereNotNull('active_slot')->pluck('active_slot')->all();
                $slot = collect([1, 2])->first(fn ($candidate) => !in_array($candidate, $occupied));
            }
            if ($slot === null) {
                throw ValidationException::withMessages(['student_id' => 'This class already has two Student Assistants.']);
            }

            if ($oldAssignment) {
                $oldAssignment->update(['active_slot' => null, 'revoked_at' => now()]);
                $this->invalidateAssistantQrTokens($subject, $oldAssignment->student_id, $actor);
            }

            $assignment = $existing ?: new ClassStudentAssistant([
                'subject_id' => $subject->id,
                'student_id' => $student->id,
            ]);
            $assignment->fill([
                'assigned_by_teacher_id' => $instructor->id,
                'assigned_by_admin_id' => $actor->isAdmin() ? $actor->id : null,
                'active_slot' => $slot,
                'starts_at' => $data['starts_at'] === today()->toDateString()
                    ? now() : Carbon::parse($data['starts_at'])->startOfDay(),
                'expires_at' => Carbon::parse($data['expires_at'])->endOfDay(),
                'revoked_at' => null,
                'can_submit_attendance' => (bool) ($data['can_submit_attendance'] ?? false),
            ])->save();

            activity('student-assistants')->causedBy($actor)->performedOn($assignment)
                ->withProperties([
                    'subject_id' => $subject->id,
                    'student_id' => $student->id,
                    'replaced_assignment_id' => $oldAssignment?->id,
                    'starts_at' => $assignment->starts_at->toIso8601String(),
                    'expires_at' => $assignment->expires_at->toIso8601String(),
                    'can_submit_attendance' => $assignment->can_submit_attendance,
                ])->log($oldAssignment ? 'student_assistant_changed' : 'student_assistant_assigned');
            $this->notifyInstructorOfAdminChange($subject, $actor, $oldAssignment ? 'replaced' : 'assigned');

            return $assignment;
        });
    }

    public function updatePermissions(Subject $subject, User $actor, ClassStudentAssistant $assignment, bool $canSubmitAttendance): void
    {
        DB::transaction(function () use ($subject, $actor, $assignment, $canSubmitAttendance) {
            $subject = Subject::whereKey($subject->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manageAssignments', [ClassStudentAssistant::class, $subject]);

            $assignment = ClassStudentAssistant::whereKey($assignment->id)
                ->where('subject_id', $subject->id)->lockForUpdate()->firstOrFail();
            if ($assignment->active_slot === null || $assignment->revoked_at !== null || $assignment->expires_at < now()) {
                throw ValidationException::withMessages(['assistant' => 'This assignment has already ended.']);
            }
            if ($assignment->can_submit_attendance === $canSubmitAttendance) {
                return;
            }
            if ($canSubmitAttendance && (!$assignment->student?->isActive() || !$subject->hasStudent($assignment->student))) {
                throw ValidationException::withMessages(['assistant' => 'Only active students enrolled in this class can submit attendance.']);
            }

            $assignment->update(['can_submit_attendance' => $canSubmitAttendance]);
            activity('student-assistants')->causedBy($actor)->performedOn($assignment)
                ->withProperties([
                    'subject_id' => $subject->id,
                    'student_id' => $assignment->student_id,
                    'can_submit_attendance' => $canSubmitAttendance,
                ])->log('student_assistant_permissions_changed');
            $this->notifyInstructorOfAdminChange($subject, $actor, 'updated the permissions of');
        });
    }

    public function revoke(Subject $subject, User $actor, ClassStudentAssistant $assignment): void
    {
        DB::transaction(function () use ($subject, $actor, $assignment) {
            $subject = Subject::whereKey($subject->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manageAssignments', [ClassStudentAssistant::class, $subject]);

            $assignment = ClassStudentAssistant::whereKey($assignment->id)
                ->where('subject_id', $subject->id)->lockForUpdate()->firstOrFail();
            if ($assignment->active_slot === null || $assignment->revoked_at !== null) {
                throw ValidationException::withMessages(['assistant' => 'This assignment has already ended.']);
            }
            $assignment->update(['active_slot' => null, 'revoked_at' => now()]);
            $this->invalidateAssistantQrTokens($subject, $assignment->student_id, $actor);

            activity('student-assistants')->causedBy($actor)->performedOn($assignment)
                ->withProperties(['subject_id' => $subject->id, 'student_id' => $assignment->student_id])
                ->log('student_assistant_revoked');
            $this->notifyInstructorOfAdminChange($subject, $actor, 'removed');
        });
    }

    private function notifyInstructorOfAdminChange(Subject $subject, User $actor, string $action): void
    {
        if (!$actor->isAdmin() || !$subject->instructorUser?->isTeacher()) {
            return;
        }

        Notification::create([
            'user_id' => $subject->instructor_id,
            'sent_by' => $actor->id,
            'type' => 'system',
            'subject_code' => $subject->code,
            'message' => "An administrator {$action} a Student Assistant for {$subject->code}. Open your classroom to view the assignment.",
        ]);
    }

    private function releaseExpiredSlots(Subject $subject): void
    {
        ClassStudentAssistant::where('subject_id', $subject->id)
            ->whereNotNull('active_slot')
            ->where('expires_at', '<', now())
            ->update(['active_slot' => null]);
    }

    private function invalidateAssistantQrTokens(Subject $subject, int $studentId, User $actor): void
    {
        // QR issuance also locks the session. Whichever operation wins that lock
        // finishes first; a revocation can then retire a QR issued in flight.
        $sessions = AttendanceSession::where('subject_code', $subject->code)
            ->where('active', true)->where('session_ends_at', '>', now())
            ->orderBy('id')->lockForUpdate()->get();

        foreach ($sessions as $session) {
            $token = AttendanceQrToken::where('active_session_id', $session->id)
                ->where('generated_by', $studentId)
                ->where('generator_type', 'student_assistant')
                ->lockForUpdate()->first();
            if (!$token) {
                continue;
            }

            $token->update(['active_session_id' => null, 'invalidated_at' => now()]);
            activity('attendance-qr')->causedBy($actor)->performedOn($session)
                ->withProperties([
                    'attendance_session_id' => $session->id,
                    'qr_token_id' => $token->id,
                    'generator_id' => $studentId,
                    'reason' => 'assistant_permission_revoked',
                ])->log('qr_invalidated');

            DB::afterCommit(function () use ($session, $token, $subject, $actor, $studentId) {
                try {
                    AttendanceQrChanged::dispatch(
                        $session->id, (int) ($subject->instructor_id ?: $session->created_by), $session->subject_code,
                        $token->id, $actor->name, $studentId,
                        'revoked', now()->timestamp,
                    );
                } catch (\Throwable $exception) {
                    Log::warning('Assistant QR revocation broadcast failed', [
                        'attendance_session_id' => $session->id,
                        'qr_token_id' => $token->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
        }
    }
}
