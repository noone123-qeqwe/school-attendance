<?php

namespace App\Actions\Attendance;

use App\Models\User;
use App\Models\Subject;
use App\Models\Holiday;
use App\Models\AcademicYear;
use App\Models\Attendance;
use Carbon\Carbon;

use Illuminate\Support\Collection;

class CalculateMissedAttendanceAction
{
    /**
     * Calculate unrecorded missed class sessions for a student per subject.
     *
     * @return array<string, int> Associative array of [subject_code => missed_count]
     */
    public function executePerSubject(User $student, ?Collection $subjects = null): array
    {
        if (!$student->year_level || !$student->semester) {
            return [];
        }

        $now = now('Asia/Manila');
        $todayDate = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        $cacheKey = "student_missed_att_{$student->id}_{$todayDate}_" . floor($now->minute / 5);
        if (!app()->environment('testing') && \Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return \Illuminate\Support\Facades\Cache::get($cacheKey);
        }

        // 1. Fetch relevant subjects with preloaded schedules
        if ($subjects === null) {
            $subjects = method_exists($student, 'getAllSubjects') && $student->isStudent()
                ? $student->getAllSubjects()
                : Subject::where('year_level', $student->year_level)
                    ->where('semester', $student->semester)
                    ->where(function ($q) use ($student) {
                        $q->whereNull('course')->orWhere('course', '')->orWhere('course', $student->course);
                    })
                    ->where(function ($q) use ($student) {
                        $q->whereNull('section')->orWhere('section', '')->orWhere('section', $student->section);
                    })
                    ->get();
        }

        if ($subjects->isEmpty()) {
            return [];
        }

        $subjects->loadMissing('schedules');

        // 2. Resolve tracking start date (bounded by student registration and enrollment)
        $earliestRecord = Attendance::where('user_id', $student->id)->min('date');
        $semesterStart = app()->environment('testing')
            ? AcademicYear::where('is_current', true)->value('start_date')
            : \Illuminate\Support\Facades\Cache::remember('current_academic_year_start', 600, function () {
                return AcademicYear::where('is_current', true)->value('start_date');
            });

        // Determine student registration timestamp and boundary (never count before registration)
        $studentRegisteredAt = $student->created_at 
            ? Carbon::parse($student->created_at)->timezone('Asia/Manila')
            : $now->copy();
        $studentRegDate = $studentRegisteredAt->copy()->startOfDay();

        // Earliest date of actual attendance record (if any historical record exists earlier)
        if ($earliestRecord) {
            $earliest = Carbon::parse($earliestRecord)->timezone('Asia/Manila')->startOfDay();
            if ($earliest->lt($studentRegDate)) {
                $studentRegDate = $earliest;
            }
        }

        // Pre-fetch explicit pivot enrollments if any
        $pivotEnrollments = \Illuminate\Support\Facades\Schema::hasTable('enrollments')
            ? \Illuminate\Support\Facades\DB::table('enrollments')
                ->where('user_id', $student->id)
                ->pluck('created_at', 'subject_id')
            : collect();

        if ($semesterStart && Carbon::parse($semesterStart)->lt($now)) {
            $semStart = Carbon::parse($semesterStart)->timezone('Asia/Manila')->startOfDay();
            // In both testing and production, a student registered after semester start only tracks from registration
            $startDate = $studentRegDate->gt($semStart) ? $studentRegDate : $semStart;
        } else {
            // When no academic year is configured, tracking strictly begins at the student's registration date
            $startDate = $studentRegDate;
        }

        // 3. Pre-fetch holidays in a single lookup list
        $holidays = Holiday::active()
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $todayDate)
            ->pluck('date')
            ->map(fn($d) => $d instanceof \DateTimeInterface ? $d->format('Y-m-d') : Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $holidaySet = array_flip($holidays);

        // 4. Pre-fetch all actual attendance counts grouped by subject in 1 query
        $actualCounts = Attendance::where('user_id', $student->id)
            ->whereIn('subject_code', $subjects->pluck('code'))
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $todayDate)
            ->selectRaw('subject_code, count(*) as count')
            ->groupBy('subject_code')
            ->pluck('count', 'subject_code');

        $missesPerSubject = [];

        foreach ($subjects as $subj) {
            $scheduledDays = $subj->schedules->pluck('day')->unique()->values();
            if ($scheduledDays->isEmpty()) {
                $missesPerSubject[$subj->code] = 0;
                continue;
            }

            // Subject-specific enrollment date if explicitly enrolled via pivot
            $subjStartDate = $startDate->copy();
            if ($pivotEnrollments->has($subj->id) && $pivotEnrollments->get($subj->id)) {
                $pivotDate = Carbon::parse($pivotEnrollments->get($subj->id))->timezone('Asia/Manila')->startOfDay();
                if ($pivotDate->gt($subjStartDate)) {
                    $subjStartDate = $pivotDate;
                }
            }

            $scheduledDaysSet = array_flip($scheduledDays->toArray());
            $expectedSessions = 0;
            $cursor = $subjStartDate->copy();

            while ($cursor->lte($now)) {
                $cursorDayName = $cursor->format('l');
                $cursorDateStr = $cursor->toDateString();

                if (!isset($holidaySet[$cursorDateStr]) && $cursorDayName !== 'Sunday') {
                    if (isset($scheduledDaysSet[$cursorDayName])) {
                        $daySchedules = $subj->schedules->where('day', $cursorDayName);
                        foreach ($daySchedules as $sched) {
                            // If today, only count sessions that have already concluded
                            if ($cursorDateStr === $todayDate && $sched->end_time >= $currentTime) {
                                continue;
                            }
                            // If the date is the student's registration date, skip sessions that started before registration!
                            if ($cursorDateStr === $studentRegisteredAt->toDateString() && $sched->start_time <= $studentRegisteredAt->format('H:i:s')) {
                                continue;
                            }
                            $expectedSessions++;
                        }
                    }
                }
                $cursor->addDay();
            }

            $actualRecords = $actualCounts->get($subj->code, 0);
            $misses = max(0, $expectedSessions - $actualRecords);
            $missesPerSubject[$subj->code] = $misses;
        }

        if (!app()->environment('testing')) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, $missesPerSubject, 300);
        }

        return $missesPerSubject;
    }

    /**
     * Calculate unrecorded missed class sessions for a student across all subjects.
     */
    public function execute(User $student, ?Collection $subjects = null): int
    {
        return array_sum($this->executePerSubject($student, $subjects));
    }
}
