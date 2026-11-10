<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Notification;
use App\Models\SchedulePublication;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class SchedulePublicationService
{
    /**
     * Return the current schedule fingerprint and the counts shown to administrators.
     * Subjects are semester-based in the current data model; they do not yet carry an
     * academic-year foreign key.
     *
     * @return array{hash: string, subject_count: int, schedule_count: int}
     */
    public function status(AcademicYear $academicYear): array
    {
        $snapshot = $this->snapshot($academicYear);

        return [
            'hash' => hash('sha256', json_encode($snapshot['data'], JSON_THROW_ON_ERROR)),
            'subject_count' => $snapshot['subjects']->count(),
            'schedule_count' => $snapshot['classSchedules']->isNotEmpty()
                ? $snapshot['classSchedules']->count()
                : $snapshot['subjects']->sum(fn (Subject $subject) => $subject->schedules->count()),
        ];
    }

    /**
     * Publish the current term's schedules and notify only recipients whose own
     * schedule fingerprint changed since the previous publication.
     *
     * @return array{published: bool, notified: int}
     */
    public function publish(AcademicYear $academicYear, int $adminId): array
    {
        $snapshot = $this->snapshot($academicYear);
        $scheduleHash = hash('sha256', json_encode($snapshot['data'], JSON_THROW_ON_ERROR));
        $hasSchedule = $snapshot['classSchedules']->isNotEmpty()
            || $snapshot['subjects']->contains(fn (Subject $subject) => $subject->schedules->isNotEmpty());
        $publication = SchedulePublication::query()
            ->where('academic_year_id', $academicYear->id)
            ->lockForUpdate()
            ->first();
        if (!$hasSchedule && !$publication) {
            throw ValidationException::withMessages([
                'publish' => 'Add at least one subject and schedule for this semester before publishing.',
            ]);
        }

        $recipients = $this->recipientHashes($snapshot['subjects'], $snapshot['classSchedules']);
        $previousHashes = $publication?->recipient_hashes ?? [];

        $changedRecipientIds = collect(array_unique(array_merge(array_keys($previousHashes), array_keys($recipients))))
            ->filter(fn (string $id) => ($previousHashes[$id]['hash'] ?? null) !== ($recipients[$id]['hash'] ?? null))
            ->map(fn ($id) => (int) $id)
            ->all();

        $scheduleUnchanged = $publication && hash_equals($publication->schedule_hash, $scheduleHash);
        if ($scheduleUnchanged && $changedRecipientIds === []) {
            return ['published' => false, 'notified' => 0];
        }

        $firstPublication = $publication === null;
        $now = now();
        $messages = [];
        $recipientModels = User::query()
            ->whereIn('id', $changedRecipientIds)
            ->whereIn('role', ['student', 'teacher'])
            ->where(function ($query) {
                $query->where('is_active', true)->orWhereNull('is_active');
            })
            ->get(['id', 'role']);

        foreach ($recipientModels as $recipient) {
            $kind = $firstPublication || !isset($previousHashes[(string) $recipient->id])
                ? 'published'
                : 'updated';
            $message = $recipient->role === 'teacher'
                ? "Your teaching schedule for {$academicYear->name} ({$academicYear->semester_label}) has been {$kind}. Open your Classes page to review your assigned classes."
                : "Your class schedule for {$academicYear->name} ({$academicYear->semester_label}) has been {$kind}. Open Classes / COR & Class Schedule to review or download it.";

            $messages[] = [
                'user_id' => $recipient->id,
                'sent_by' => $adminId,
                'type' => 'custom',
                'subject_code' => null,
                'message' => $message,
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($messages, 500) as $chunk) {
            Notification::query()->insert($chunk);
        }

        SchedulePublication::query()->updateOrCreate(
            ['academic_year_id' => $academicYear->id],
            [
                'published_by' => $adminId,
                'schedule_hash' => $scheduleHash,
                'recipient_hashes' => $recipients,
                'published_at' => $now,
            ],
        );

        return ['published' => true, 'notified' => count($messages)];
    }

    /**
     * @return array{data: array<string, mixed>, subjects: Collection<int, Subject>, classSchedules: Collection<int, ClassSchedule>}
     */
    private function snapshot(AcademicYear $academicYear): array
    {
        $subjects = Subject::query()
            ->where('semester', (int) $academicYear->semester)
            ->with(['schedules' => fn ($query) => $query->orderBy('day')->orderBy('start_time')->orderBy('end_time')])
            ->orderBy('id')
            ->get();

        $subjectIds = $subjects->modelKeys();
        $classSchedules = $subjectIds === []
            ? collect()
            : ClassSchedule::query()
                ->whereIn('subject_id', $subjectIds)
                ->with('section.course')
                ->orderBy('subject_id')
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->orderBy('section_id')
                ->get();

        $subjectData = $subjects->map(fn (Subject $subject) => [
            'id' => (int) $subject->id,
            'code' => (string) $subject->code,
            'name' => (string) $subject->name,
            'year_level' => (string) $subject->year_level,
            'semester' => (int) $subject->semester,
            'course' => (string) ($subject->course ?? ''),
            'section' => (string) ($subject->section ?? ''),
            'instructor_id' => $subject->instructor_id ? (int) $subject->instructor_id : null,
            'schedules' => $subject->schedules->map(fn ($schedule) => [
                'day' => (string) $schedule->day,
                'start_time' => (string) $schedule->start_time,
                'end_time' => (string) $schedule->end_time,
                'room' => (string) ($schedule->room ?? ''),
            ])->values()->all(),
        ])->values()->all();

        $classScheduleData = $classSchedules->map(fn (ClassSchedule $schedule) => [
            'subject_id' => (int) $schedule->subject_id,
            'teacher_id' => (int) $schedule->teacher_id,
            'section_id' => (int) $schedule->section_id,
            'day' => (string) $schedule->day_of_week,
            'start_time' => (string) $schedule->start_time,
            'end_time' => (string) $schedule->end_time,
            'room' => (string) ($schedule->room ?? ''),
        ])->values()->all();

        return [
            'data' => ['subjects' => $subjectData, 'class_schedules' => $classScheduleData],
            'subjects' => $subjects,
            'classSchedules' => $classSchedules,
        ];
    }

    /**
     * @param Collection<int, Subject> $subjects
     * @param Collection<int, ClassSchedule> $classSchedules
     * @return array<string, array{role: string, hash: string}>
     */
    private function recipientHashes(Collection $subjects, Collection $classSchedules): array
    {
        $studentEntries = [];
        $teacherEntries = [];
        $studentRosters = [];

        foreach ($subjects as $subject) {
            $subjectEntry = [
                'subject_id' => (int) $subject->id,
                'code' => (string) $subject->code,
                'name' => (string) $subject->name,
                'instructor_id' => $subject->instructor_id ? (int) $subject->instructor_id : null,
                'schedules' => $subject->schedules->map(fn ($schedule) => [
                    'day' => (string) $schedule->day,
                    'start_time' => (string) $schedule->start_time,
                    'end_time' => (string) $schedule->end_time,
                    'room' => (string) ($schedule->room ?? ''),
                ])->values()->all(),
            ];

            $studentRosters[$subject->id] = $subject->getAllStudents()->filter(fn (User $student) => $student->isActive());
            foreach ($studentRosters[$subject->id] as $student) {
                $studentEntries[$student->id][] = $subjectEntry;
            }

            if ($subject->instructor_id) {
                $teacherEntries[$subject->instructor_id][] = $subjectEntry;
            }
        }

        foreach ($classSchedules as $classSchedule) {
            $entry = [
                'subject_id' => (int) $classSchedule->subject_id,
                'section_id' => (int) $classSchedule->section_id,
                'teacher_id' => (int) $classSchedule->teacher_id,
                'day' => (string) $classSchedule->day_of_week,
                'start_time' => (string) $classSchedule->start_time,
                'end_time' => (string) $classSchedule->end_time,
                'room' => (string) ($classSchedule->room ?? ''),
            ];
            $teacherEntries[$classSchedule->teacher_id][] = $entry;

            $subject = $subjects->firstWhere('id', $classSchedule->subject_id);
            if (!$subject) {
                continue;
            }
            foreach ($studentRosters[$subject->id] ?? collect() as $student) {
                $section = $classSchedule->section;
                $sameSection = filled($student->section)
                    && $section !== null
                    && strcasecmp(trim((string) $student->section), trim((string) $section->name)) === 0
                    && (string) $student->year_level === (string) $section->year_level
                    && strcasecmp(trim((string) ($student->course ?: 'BSCS')), trim((string) $section->course?->code)) === 0;
                if ($sameSection) {
                    $studentEntries[$student->id][] = $entry;
                }
            }
        }

        return $this->hashEntries($studentEntries, 'student')
            + $this->hashEntries($teacherEntries, 'teacher');
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $entries
     * @return array<string, array{role: string, hash: string}>
     */
    private function hashEntries(array $entries, string $role): array
    {
        $hashes = [];
        foreach ($entries as $userId => $userEntries) {
            usort($userEntries, fn (array $a, array $b) => json_encode($a) <=> json_encode($b));
            $hashes[(string) $userId] = [
                'role' => $role,
                'hash' => hash('sha256', json_encode($userEntries, JSON_THROW_ON_ERROR)),
            ];
        }

        return $hashes;
    }
}
