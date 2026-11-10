<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;

final class StudentScheduleService
{
    /**
     * Return the student's subjects with section-specific schedule slots applied.
     * Legacy subject schedules remain available for subjects that have not been
     * moved to the section-aware class schedule table.
     *
     * @return Collection<int, Subject>
     */
    public function subjectsFor(User $student): Collection
    {
        if (!$student->isStudent()) {
            return collect();
        }

        $subjects = $student->getAllSubjects();
        if ($subjects->isEmpty()) {
            return $subjects;
        }

        $subjects->load(['schedules', 'instructorUser']);
        $sectionSchedules = ClassSchedule::query()
            ->whereIn('subject_id', $subjects->modelKeys())
            ->with(['section.course'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('subject_id');

        return $subjects->map(function (Subject $subject) use ($student, $sectionSchedules) {
            $configuredSlots = $sectionSchedules->get($subject->id, collect());

            if ($configuredSlots->isNotEmpty()) {
                // Never expose a section's timetable when the student's section
                // is missing or does not match the section assigned to the slot.
                $schedules = $configuredSlots
                    ->filter(fn (ClassSchedule $slot) => $this->belongsToStudentSection($slot, $student))
                    ->map(fn (ClassSchedule $slot) => (object) [
                        'id' => $slot->id,
                        'subject_id' => $slot->subject_id,
                        'day' => $slot->day_of_week,
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                        'room' => $slot->room,
                    ])
                    ->values();
            } else {
                $subjectSection = trim((string) $subject->section);
                $studentSection = trim((string) $student->section);
                $matchesSubjectSection = $subjectSection === ''
                    || ($studentSection !== '' && strcasecmp($subjectSection, $studentSection) === 0);
                $schedules = $matchesSubjectSection ? $subject->schedules->values() : collect();
            }

            $studentSubject = clone $subject;
            $studentSubject->setRelation('schedules', $schedules);

            return $studentSubject;
        })->values();
    }

    private function belongsToStudentSection(ClassSchedule $slot, User $student): bool
    {
        $section = $slot->section;
        $studentSection = trim((string) $student->section);
        $sectionCourse = trim((string) $section?->course?->code);
        $studentCourse = trim((string) ($student->course ?: 'BSCS'));

        return $studentSection !== ''
            && $section !== null
            && strcasecmp(trim((string) $section->name), $studentSection) === 0
            && (string) $section->year_level === (string) $student->year_level
            && $sectionCourse !== ''
            && strcasecmp($sectionCourse, $studentCourse) === 0;
    }
}
