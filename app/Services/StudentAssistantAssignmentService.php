<?php

namespace App\Services;

use App\Models\ClassStudentAssistant;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentAssistantAssignmentService
{
    public function assign(Subject $subject, User $teacher, array $data, ?ClassStudentAssistant $replacement = null): ClassStudentAssistant
    {
        return DB::transaction(function () use ($subject, $teacher, $data, $replacement) {
            $subject = Subject::whereKey($subject->id)->lockForUpdate()->firstOrFail();
            abort_unless($teacher->isTeacher() && (int) $subject->instructor_id === (int) $teacher->id, 403);

            $student = User::whereKey($data['student_id'])->where('role', 'student')
                ->where('is_active', true)->first();
            if (!$student || !$subject->getAllStudents()->contains('id', $student->id)) {
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
            }

            $assignment = $existing ?: new ClassStudentAssistant([
                'subject_id' => $subject->id,
                'student_id' => $student->id,
            ]);
            $assignment->fill([
                'assigned_by_teacher_id' => $teacher->id,
                'active_slot' => $slot,
                'starts_at' => $data['starts_at'] === today()->toDateString()
                    ? now() : Carbon::parse($data['starts_at'])->startOfDay(),
                'expires_at' => Carbon::parse($data['expires_at'])->endOfDay(),
                'revoked_at' => null,
            ])->save();

            activity('student-assistants')->causedBy($teacher)->performedOn($assignment)
                ->withProperties([
                    'subject_id' => $subject->id,
                    'student_id' => $student->id,
                    'replaced_assignment_id' => $oldAssignment?->id,
                    'starts_at' => $assignment->starts_at->toIso8601String(),
                    'expires_at' => $assignment->expires_at->toIso8601String(),
                ])->log($oldAssignment ? 'student_assistant_changed' : 'student_assistant_assigned');

            return $assignment;
        });
    }

    public function revoke(Subject $subject, User $teacher, ClassStudentAssistant $assignment): void
    {
        DB::transaction(function () use ($subject, $teacher, $assignment) {
            $subject = Subject::whereKey($subject->id)->lockForUpdate()->firstOrFail();
            abort_unless($teacher->isTeacher() && (int) $subject->instructor_id === (int) $teacher->id, 403);

            $assignment = ClassStudentAssistant::whereKey($assignment->id)
                ->where('subject_id', $subject->id)->lockForUpdate()->firstOrFail();
            if ($assignment->active_slot === null || $assignment->revoked_at !== null) {
                throw ValidationException::withMessages(['assistant' => 'This assignment has already ended.']);
            }
            $assignment->update(['active_slot' => null, 'revoked_at' => now()]);

            activity('student-assistants')->causedBy($teacher)->performedOn($assignment)
                ->withProperties(['subject_id' => $subject->id, 'student_id' => $assignment->student_id])
                ->log('student_assistant_revoked');
        });
    }

    private function releaseExpiredSlots(Subject $subject): void
    {
        ClassStudentAssistant::where('subject_id', $subject->id)
            ->whereNotNull('active_slot')
            ->where('expires_at', '<', now())
            ->update(['active_slot' => null]);
    }
}
