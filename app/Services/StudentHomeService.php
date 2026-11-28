<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassStudentAssistant;
use App\Models\DeviceBinding;
use App\Models\ExcuseSubmission;
use App\Models\Holiday;
use App\Models\User;
use App\Models\Warning;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentHomeService
{
    public function __construct(
        private readonly StudentScheduleService $scheduleService,
        private readonly PeerBiometricAttendanceService $peerAttendance,
        private readonly FeatureFlagService $featureFlags,
    ) {}

    /**
     * Build the context-aware dataset for the student home dashboard.
     *
     * @return array<string, mixed>
     */
    public function buildFor(User $student, ?Carbon $now = null): array
    {
        $now = ($now ?? now())->copy();
        $todayDate = $now->toDateString();

        $subjects = $this->scheduleService->subjectsFor($student);
        $subjectsByCode = $subjects->keyBy('code');
        $subjectCodes = $subjectsByCode->keys()->filter()->values();

        $schoolHolidays = Holiday::getUpcoming($todayDate, $now->copy()->addDays(7)->toDateString());
        $todayHoliday = $schoolHolidays->first(fn ($holiday) => $holiday->date->isSameDay($now));

        $todayAttendances = Attendance::where('user_id', $student->id)
            ->whereDate('date', $todayDate)
            ->get()
            ->keyBy('subject_code');

        $todaySchedule = $this->buildTodaySchedule($subjects, $todayHoliday, $todayAttendances, $now, $todayDate);
        $activeSessions = $this->loadActiveSessions($subjectCodes, $subjectsByCode, $todayAttendances, $todaySchedule, $now);
        $activeSessionsByCode = $activeSessions->keyBy('subject_code');

        // Annotate today's slots with live open session info so rows show real check-in availability.
        $todaySchedule = $todaySchedule->map(function (object $slot) use ($activeSessionsByCode) {
            $slot->active_session = $activeSessionsByCode->get($slot->subject->code);
            return $slot;
        });

        $openUncheckedSessions = $activeSessions->where('checked_in', false)->values();
        $currentSlot = $todaySchedule->firstWhere('schedule_status', 'ongoing');
        $currentClass = $currentSlot?->subject;
        $alreadyClockedIn = $currentSlot !== null && in_array($currentSlot->attendance?->status, ['Present', 'Late'], true);

        $upcomingClass = $this->findNextUpcomingClass($subjects, $schoolHolidays, $now);
        $assistantLiveSessions = $this->loadAssistantLiveSessions($student, $now);

        $deviceBinding = DeviceBinding::where('user_id', $student->id)->first();
        $deviceLocked = (bool) ($deviceBinding && $deviceBinding->isLocked());
        $deviceLockReason = $deviceLocked ? ($deviceBinding->locked_reason ?: null) : null;

        $recentAttendance = Attendance::with('subject')
            ->where('user_id', $student->id)
            ->whereDate('date', '<=', $todayDate)
            ->orderByDesc('date')
            ->orderByDesc('time_in')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $unresolvedAbsencesQuery = Attendance::with('subject')
            ->where('user_id', $student->id)
            ->where('status', 'Absent')
            ->where('excused', false)
            ->whereDate('date', '<=', $todayDate)
            ->whereDoesntHave('excuseSubmission', function ($query) use ($student) {
                $query->where('user_id', $student->id)->whereIn('status', ['pending', 'approved']);
            });

        $unresolvedAbsencesCount = (clone $unresolvedAbsencesQuery)->count();
        $unresolvedAbsences = $unresolvedAbsencesQuery
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        $recentExcuseRequests = ExcuseSubmission::with('attendance.subject')
            ->where('user_id', $student->id)
            ->whereHas('attendance', function ($query) use ($student, $todayDate) {
                $query->where('user_id', $student->id)
                    ->whereDate('date', '<=', $todayDate);
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        $activeWarnings = Warning::where('user_id', $student->id)
            ->where('created_at', '>=', $now->copy()->subDays(14))
            ->orderByDesc('created_at')
            ->get();

        $announcements = Announcement::with('author')
            ->published()
            ->visibleTo($student)
            ->latest()
            ->take(5)
            ->get();

        $attendanceStanding = $this->computeAttendanceStanding($student, $subjectsByCode, $todayDate);
        $peerAvailable = $this->peerAttendance->isAvailable();

        $focusState = $this->resolveFocusState(
            openUncheckedSessions: $openUncheckedSessions,
            currentSlot: $currentSlot,
            alreadyClockedIn: $alreadyClockedIn,
            upcomingClass: $upcomingClass,
            todaySchedule: $todaySchedule,
            todayHoliday: $todayHoliday,
            subjects: $subjects,
            now: $now,
        );

        $totalUnits = (float) $subjects->sum(fn ($s) => (float) ($s->units ?: 3));
        $pendingExcusesCount = ExcuseSubmission::where('user_id', $student->id)
            ->where('status', 'pending')
            ->count();
        $totalCheckins = (int) ($attendanceStanding['attended'] ?? 0);
        $lastCheckinCode = $recentAttendance->first()?->subject_code;

        $subjectLoggedCounts = Attendance::query()
            ->where('user_id', $student->id)
            ->whereDate('date', '<=', $todayDate)
            ->where(function ($q) {
                $q->whereIn('status', ['Present', 'Late', 'Excused'])
                    ->orWhere('excused', true);
            })
            ->selectRaw('subject_code, COUNT(*) as cnt')
            ->groupBy('subject_code')
            ->pluck('cnt', 'subject_code');

        $weeklyClassRows = $this->buildWeeklyClassRows($subjects, $subjectLoggedCounts, $now);
        $courseTracker = $this->buildCourseTracker($subjects, $subjectLoggedCounts);
        $courseTrackerOverallPct = $this->computeCourseTrackerOverallPct($courseTracker);
        $miniCalendar = $this->buildMiniCalendar($student, $subjects, $upcomingClass, $now);
        $heroSlot = $this->buildHeroSlot($focusState, $openUncheckedSessions, $currentSlot, $upcomingClass, $alreadyClockedIn, $now);

        return compact(
            'currentClass',
            'currentSlot',
            'alreadyClockedIn',
            'subjects',
            'todayHoliday',
            'activeWarnings',
            'todaySchedule',
            'upcomingClass',
            'recentAttendance',
            'unresolvedAbsences',
            'unresolvedAbsencesCount',
            'recentExcuseRequests',
            'announcements',
            'activeSessions',
            'openUncheckedSessions',
            'assistantLiveSessions',
            'deviceLocked',
            'deviceLockReason',
            'attendanceStanding',
            'peerAvailable',
            'focusState',
            'totalUnits',
            'pendingExcusesCount',
            'totalCheckins',
            'lastCheckinCode',
            'weeklyClassRows',
            'courseTracker',
            'courseTrackerOverallPct',
            'miniCalendar',
            'heroSlot',
        );
    }

    /**
     * Build compact Linear-style enrolled subject schedule rows for the dashboard schedule list.
     *
     * @param Collection<int, \App\Models\Subject> $subjects
     * @param Collection<string, int|string> $subjectLoggedCounts
     * @return Collection<int, object>
     */
    private function buildWeeklyClassRows(Collection $subjects, Collection $subjectLoggedCounts, Carbon $now): Collection
    {
        $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $shortMap = [
            'Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed',
            'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun',
        ];
        $todayName = $now->format('l');

        return $subjects->map(function ($subject) use ($dayOrder, $shortMap, $subjectLoggedCounts, $todayName) {
            $slots = collect($subject->schedules ?? []);
            $days = $slots->pluck('day')->filter()->unique()->values()->all();
            $orderedDays = array_values(array_intersect($dayOrder, $days));
            $dayLabel = !empty($orderedDays)
                ? implode('/', array_map(fn ($d) => $shortMap[$d] ?? substr($d, 0, 3), $orderedDays))
                : 'TBA';

            $firstSlot = $slots->sortBy('start_time')->first();
            $timeRange = ($firstSlot && filled($firstSlot->start_time) && filled($firstSlot->end_time))
                ? Carbon::parse($firstSlot->start_time)->format('g:i A') . ' – ' . Carbon::parse($firstSlot->end_time)->format('g:i A')
                : 'Schedule TBA';

            $durationMin = ($firstSlot && filled($firstSlot->start_time) && filled($firstSlot->end_time))
                ? max(15, (int) Carbon::parse($firstSlot->start_time)->diffInMinutes(Carbon::parse($firstSlot->end_time)))
                : null;

            $room = trim((string) ($firstSlot?->room ?? ''));
            $cleanRoom = ($room !== '' && strtoupper($room) !== 'TBA') ? $room : 'Room TBA';
            $instructor = $subject->instructorUser?->name ?? $subject->instructor ?? 'Instructor TBA';

            return (object) [
                'code' => (string) $subject->code,
                'name' => (string) $subject->name,
                'days_label' => $dayLabel,
                'raw_days' => $orderedDays,
                'is_today' => in_array($todayName, $orderedDays, true),
                'time_range' => $timeRange,
                'duration_min' => $durationMin,
                'room' => $cleanRoom,
                'instructor' => $instructor,
                'units' => (float) ($subject->units ?: 3),
                'logged_count' => (int) ($subjectLoggedCounts->get($subject->code) ?? 0),
                'sort_day' => !empty($orderedDays) ? array_search($orderedDays[0], $dayOrder, true) : 99,
                'sort_time' => $firstSlot?->start_time ?? '99:99:99',
            ];
        })->sortBy([
            ['sort_day', 'asc'],
            ['sort_time', 'asc'],
            ['code', 'asc'],
        ])->values();
    }

    /**
     * Build per-course check-in progress rows (target 18 semester sessions per course).
     *
     * @param Collection<int, \App\Models\Subject> $subjects
     * @param Collection<string, int|string> $subjectLoggedCounts
     * @return array<int, array{code: string, name: string, logged: int, target: int, percent: int}>
     */
    private function buildCourseTracker(Collection $subjects, Collection $subjectLoggedCounts): array
    {
        return $subjects->map(function ($subject) use ($subjectLoggedCounts): array {
            $logged = (int) ($subjectLoggedCounts->get($subject->code) ?? 0);
            $target = max(18, $logged);
            $percent = $target > 0 ? min(100, (int) round(($logged / $target) * 100)) : 0;

            return [
                'code' => (string) $subject->code,
                'name' => (string) $subject->name,
                'logged' => $logged,
                'target' => $target,
                'percent' => $percent,
            ];
        })->values()->all();
    }

    /**
     * @param array<int, array{code: string, name: string, logged: int, target: int, percent: int}> $tracker
     */
    private function computeCourseTrackerOverallPct(array $tracker): int
    {
        if (empty($tracker)) {
            return 0;
        }
        $logged = array_sum(array_column($tracker, 'logged'));
        $target = array_sum(array_column($tracker, 'target'));
        return $target > 0 ? min(100, (int) round(($logged / $target) * 100)) : 0;
    }

    /**
     * Build compact monthly mini-calendar data for the right rail of the desktop dashboard.
     *
     * @param Collection<int, \App\Models\Subject> $subjects
     * @return array<string, mixed>
     */
    private function buildMiniCalendar(User $student, Collection $subjects, ?object $upcomingClass, Carbon $now): array
    {
        // Show the month of the next upcoming class if today is at month-end and next class is next month, otherwise current month.
        $ref = $now->copy()->startOfMonth();
        if ($upcomingClass && $upcomingClass->start_time instanceof Carbon && $upcomingClass->start_time->month !== $now->month && $now->day >= $now->daysInMonth - 2) {
            $ref = $upcomingClass->start_time->copy()->startOfMonth();
        }

        $monthStart = $ref->copy()->startOfMonth()->toDateString();
        $monthEnd = $ref->copy()->endOfMonth()->toDateString();

        $monthRecords = Attendance::where('user_id', $student->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get(['date', 'status', 'excused']);

        $presentDays = [];
        $excusedDays = [];
        foreach ($monthRecords as $rec) {
            $dayNum = Carbon::parse($rec->date)->day;
            if ($rec->excused || strcasecmp((string) $rec->status, 'Excused') === 0) {
                $excusedDays[$dayNum] = true;
            } elseif (in_array($rec->status, ['Present', 'Late'], true)) {
                $presentDays[$dayNum] = true;
            }
        }

        $scheduledDayNames = [];
        foreach ($subjects as $subject) {
            foreach ($subject->schedules ?? [] as $sched) {
                if (filled($sched->day)) {
                    $scheduledDayNames[$sched->day] = true;
                }
            }
        }

        $holidayDays = Holiday::active()
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->pluck('date')
            ->mapWithKeys(fn ($d) => [Carbon::parse($d)->day => true])
            ->all();

        $classDays = [];
        for ($d = 1; $d <= $ref->daysInMonth; $d++) {
            $dayCarbon = $ref->copy()->day($d);
            if (isset($scheduledDayNames[$dayCarbon->format('l')]) && !isset($holidayDays[$d])) {
                $classDays[$d] = true;
            }
        }

        $todayDay = ($ref->year === $now->year && $ref->month === $now->month) ? $now->day : null;
        $highlightDay = ($upcomingClass && $upcomingClass->start_time instanceof Carbon && $upcomingClass->start_time->year === $ref->year && $upcomingClass->start_time->month === $ref->month)
            ? $upcomingClass->start_time->day
            : $todayDay;

        return [
            'monthLabel' => $ref->format('F Y'),
            'year' => $ref->year,
            'month' => $ref->month,
            'leadingBlanks' => $ref->dayOfWeek,
            'daysInMonth' => $ref->daysInMonth,
            'todayDay' => $todayDay,
            'highlightDay' => $highlightDay,
            'presentDays' => $presentDays,
            'excusedDays' => $excusedDays,
            'classDays' => $classDays,
            'holidayDays' => $holidayDays,
        ];
    }

    /**
     * Build the enriched hero card display object combining active session, ongoing class, or next upcoming class.
     */
    private function buildHeroSlot(
        object $focusState,
        Collection $openUncheckedSessions,
        ?object $currentSlot,
        ?object $upcomingClass,
        bool $alreadyClockedIn,
        Carbon $now,
    ): object {
        $targetSlot = null;
        $kicker = 'Your next class';
        $durationMin = null;
        $dateTimeLabel = $focusState->time_range;
        $countdownTargetMs = null;
        $countdownPrefix = 'Starts in';

        if ($focusState->mode === 'open_session') {
            $primarySession = $openUncheckedSessions->first();
            $targetSlot = $primarySession?->slot;
            $kicker = 'Attendance open';
            $countdownTargetMs = $focusState->end_ms;
            $countdownPrefix = 'Closes in';
            if ($targetSlot) {
                $durationMin = max(15, (int) $targetSlot->start_time->diffInMinutes($targetSlot->end_time));
                $dateTimeLabel = $targetSlot->start_time->format('D, M j') . ' · ' . $targetSlot->start_time->format('g:i A') . ' – ' . $targetSlot->end_time->format('g:i A');
            }
        } elseif ($currentSlot !== null) {
            $targetSlot = $currentSlot;
            $kicker = 'Happening now';
            $durationMin = max(15, (int) $currentSlot->start_time->diffInMinutes($currentSlot->end_time));
            $dateTimeLabel = $currentSlot->start_time->format('D, M j') . ' · ' . $currentSlot->start_time->format('g:i A') . ' – ' . $currentSlot->end_time->format('g:i A');
            $countdownTargetMs = $currentSlot->end_time->getTimestampMs();
            $countdownPrefix = 'Ends in';
        } elseif ($upcomingClass !== null) {
            $targetSlot = $upcomingClass;
            $kicker = 'Your next class';
            $durationMin = max(15, (int) $upcomingClass->start_time->diffInMinutes($upcomingClass->end_time));
            $dateTimeLabel = $upcomingClass->start_time->format('D, M j') . ' · ' . $upcomingClass->start_time->format('g:i A') . ' – ' . $upcomingClass->end_time->format('g:i A');
            $countdownTargetMs = $upcomingClass->start_time->getTimestampMs();
            $countdownPrefix = 'Starts in';
        }

        $room = $focusState->room;
        $instructor = $focusState->instructor;
        $code = $focusState->code;
        $title = $focusState->title;
        $statusLabel = $focusState->status_label;
        $statusTone = $focusState->status_tone;

        // When day_clear has a future next_slot (e.g. Saturday showing Wed's next class), promote it cleanly like the Linear hero!
        if ($focusState->mode === 'day_clear' && $upcomingClass !== null && !$focusState->confirmation_text) {
            $rawRoom = trim((string) ($upcomingClass->schedule->room ?? ''));
            $room = ($rawRoom !== '' && strtoupper($rawRoom) !== 'TBA') ? $rawRoom : null;
            $instructor = $upcomingClass->subject->instructorUser?->name ?? $upcomingClass->subject->instructor;
            $code = $upcomingClass->subject->code;
            $title = $upcomingClass->subject->name;
            $statusLabel = 'Upcoming';
            $statusTone = 'upcoming';
        }

        return (object) [
            'mode' => $focusState->mode,
            'kicker' => $kicker,
            'code' => $code,
            'title' => $title,
            'status_label' => $alreadyClockedIn && $currentSlot !== null ? 'Checked In' : $statusLabel,
            'status_tone' => $statusTone,
            'is_checked_in' => $alreadyClockedIn && $currentSlot !== null,
            'date_time_label' => $dateTimeLabel,
            'room' => $room,
            'instructor' => $instructor,
            'duration_min' => $durationMin,
            'countdown_target_ms' => $countdownTargetMs,
            'countdown_prefix' => $countdownPrefix,
            'start_ms' => $focusState->start_ms,
            'end_ms' => $focusState->end_ms,
            'confirmation_text' => $focusState->confirmation_text,
            'confirmation_meta' => $focusState->confirmation_meta ?? null,
            'helper_text' => $focusState->helper_text,
            'additional_open_count' => $focusState->additional_open_count,
            'has_scheduled_target' => $targetSlot !== null,
            'day_clear_title' => $focusState->title,
        ];
    }

    /**
     * Build sorted timetable slots for today, attaching today's subject-level attendance record.
     *
     * @param Collection<int, \App\Models\Subject> $subjects
     * @param Collection<string, Attendance> $todayAttendances
     * @return Collection<int, object>
     */
    private function buildTodaySchedule(
        Collection $subjects,
        ?Holiday $todayHoliday,
        Collection $todayAttendances,
        Carbon $now,
        string $todayDate,
    ): Collection {
        $todaySchedule = collect();
        if ($todayHoliday) {
            return $todaySchedule;
        }

        $todayName = $now->format('l');
        foreach ($subjects as $subject) {
            $slots = $subject->schedules->where('day', $todayName);
            foreach ($slots as $schedule) {
                if (blank($schedule->start_time) || blank($schedule->end_time)) {
                    continue;
                }
                $start = Carbon::parse($todayDate . ' ' . $schedule->start_time);
                $end = Carbon::parse($todayDate . ' ' . $schedule->end_time);
                if ($end->lte($start)) {
                    continue;
                }

                $scheduleStatus = 'upcoming';
                if ($now->gt($end)) {
                    $scheduleStatus = 'ended';
                } elseif ($now->gte($start)) {
                    $scheduleStatus = 'ongoing';
                }

                $todaySchedule->push((object) [
                    'subject' => $subject,
                    'schedule' => $schedule,
                    'start_time' => $start,
                    'end_time' => $end,
                    'schedule_status' => $scheduleStatus,
                    'attendance' => $todayAttendances->get($subject->code),
                    'has_multiple_slots' => $slots->count() > 1,
                    'active_session' => null,
                ]);
            }
        }

        return $todaySchedule->sortBy('start_time')->values();
    }

    /**
     * Query currently open AttendanceSessions for the student's enrolled subjects.
     *
     * @param Collection<int, string> $subjectCodes
     * @param Collection<string, \App\Models\Subject> $subjectsByCode
     * @param Collection<string, Attendance> $todayAttendances
     * @param Collection<int, object> $todaySchedule
     * @return Collection<int, object>
     */
    private function loadActiveSessions(
        Collection $subjectCodes,
        Collection $subjectsByCode,
        Collection $todayAttendances,
        Collection $todaySchedule,
        Carbon $now,
    ): Collection {
        if ($subjectCodes->isEmpty()) {
            return collect();
        }

        return AttendanceSession::with(['subject.instructorUser', 'creator'])
            ->whereIn('subject_code', $subjectCodes->all())
            ->where('active', true)
            ->where('session_ends_at', '>', $now)
            ->orderBy('session_ends_at')
            ->get()
            ->map(function (AttendanceSession $session) use ($subjectsByCode, $todayAttendances, $todaySchedule) {
                $subject = $subjectsByCode->get($session->subject_code) ?? $session->subject;
                $attendance = $todayAttendances->get($session->subject_code);
                $matchingSlot = $todaySchedule->first(
                    fn (object $slot) => $slot->subject->code === $session->subject_code && $slot->schedule_status === 'ongoing'
                ) ?? $todaySchedule->first(
                    fn (object $slot) => $slot->subject->code === $session->subject_code
                );
                $room = trim((string) ($matchingSlot?->schedule?->room ?? ''));

                return (object) [
                    'session' => $session,
                    'subject' => $subject,
                    'subject_code' => $session->subject_code,
                    'subject_name' => $subject?->name ?? $session->subject_code,
                    'instructor' => $subject?->instructorUser?->name ?? $subject?->instructor ?? $session->creator?->name,
                    'room' => ($room !== '' && strtoupper($room) !== 'TBA') ? $room : null,
                    'slot' => $matchingSlot,
                    'ends_at' => $session->session_ends_at,
                    'attendance' => $attendance,
                    'checked_in' => in_array($attendance?->status, ['Present', 'Late'], true),
                ];
            })
            ->values();
    }

    /**
     * Find the earliest upcoming scheduled class in the next 7 days, skipping active holidays.
     *
     * @param Collection<int, \App\Models\Subject> $subjects
     * @param Collection<int, Holiday> $schoolHolidays
     */
    private function findNextUpcomingClass(Collection $subjects, Collection $schoolHolidays, Carbon $now): ?object
    {
        $upcomingClass = null;
        $upcomingLimit = $now->copy()->addDays(7);
        $upcomingHolidays = $schoolHolidays
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        for ($dayOffset = 0; $dayOffset <= 7; $dayOffset++) {
            $scheduleDate = $now->copy()->addDays($dayOffset);
            if ($upcomingHolidays->has($scheduleDate->toDateString())) {
                continue;
            }

            foreach ($subjects as $subject) {
                foreach ($subject->schedules->where('day', $scheduleDate->format('l')) as $schedule) {
                    if (blank($schedule->start_time) || blank($schedule->end_time)) {
                        continue;
                    }
                    $start = Carbon::parse($scheduleDate->toDateString() . ' ' . $schedule->start_time);
                    $end = Carbon::parse($scheduleDate->toDateString() . ' ' . $schedule->end_time);
                    if ($start->lte($now) || $start->gt($upcomingLimit) || $end->lte($start)) {
                        continue;
                    }
                    if ($upcomingClass === null || $start->lt($upcomingClass->start_time)) {
                        $upcomingClass = (object) [
                            'subject' => $subject,
                            'schedule' => $schedule,
                            'start_time' => $start,
                            'end_time' => $end,
                        ];
                    }
                }
            }

            if ($upcomingClass !== null) {
                break;
            }
        }

        return $upcomingClass;
    }

    /**
     * Surface active Student Assistant sessions only when the student has live duty today.
     *
     * @return Collection<int, AttendanceSession>
     */
    private function loadAssistantLiveSessions(User $student, Carbon $now): Collection
    {
        if (!$this->featureFlags->enabled('feature.student_assistant_qr')) {
            return collect();
        }

        $assignments = ClassStudentAssistant::with('subject')
            ->where('student_id', $student->id)
            ->whereNotNull('active_slot')
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where('expires_at', '>=', $now)
            ->get()
            ->filter(fn (ClassStudentAssistant $assignment) => $assignment->isActive());

        $assignedCodes = $assignments->pluck('subject.code')->filter()->unique()->values();
        if ($assignedCodes->isEmpty()) {
            return collect();
        }

        return AttendanceSession::with('subject')
            ->whereIn('subject_code', $assignedCodes->all())
            ->where('active', true)
            ->where('session_ends_at', '>', $now)
            ->orderByDesc('id')
            ->get()
            ->filter(fn (AttendanceSession $session) => $student->can('viewAssistantQr', $session))
            ->values();
    }

    /**
     * Compute real attendance standing and highlight any subject below the 80% caution threshold.
     *
     * @param Collection<string, \App\Models\Subject> $subjectsByCode
     * @return array{
     *     total: int,
     *     attended: int,
     *     present: int,
     *     late: int,
     *     excused: int,
     *     absent: int,
     *     rate: ?int,
     *     at_risk_subjects: array<int, array{code: string, name: string, rate: int, attended: int, total: int, unexcused_absences: int}>
     * }
     */
    private function computeAttendanceStanding(User $student, Collection $subjectsByCode, string $todayDate): array
    {
        $rows = Attendance::query()
            ->where('user_id', $student->id)
            ->whereDate('date', '<=', $todayDate)
            ->get(['subject_code', 'subject_name', 'status', 'excused']);

        $total = 0;
        $present = 0;
        $late = 0;
        $excused = 0;
        $absent = 0;
        $bySubject = [];

        foreach ($rows as $row) {
            $code = (string) ($row->subject_code ?? '');
            $isExcused = (bool) $row->excused || strcasecmp((string) $row->status, 'Excused') === 0;
            $status = ucfirst(strtolower(trim((string) $row->status)));

            if (!isset($bySubject[$code])) {
                $bySubject[$code] = [
                    'fallback_name' => $row->subject_name ?: $code,
                    'total' => 0,
                    'attended' => 0,
                    'unexcused_absences' => 0,
                ];
            } elseif (filled($row->subject_name)) {
                $bySubject[$code]['fallback_name'] = $row->subject_name;
            }

            $total++;
            $bySubject[$code]['total']++;

            if ($isExcused) {
                $excused++;
                $bySubject[$code]['attended']++;
            } elseif ($status === 'Present') {
                $present++;
                $bySubject[$code]['attended']++;
            } elseif ($status === 'Late') {
                $late++;
                $bySubject[$code]['attended']++;
            } else {
                $absent++;
                $bySubject[$code]['unexcused_absences']++;
            }
        }

        $atRiskSubjects = [];
        foreach ($bySubject as $code => $stats) {
            if ($stats['total'] < 2) {
                continue;
            }
            $subRate = (int) round(($stats['attended'] / $stats['total']) * 100);
            if ($subRate < 80) {
                $subject = $subjectsByCode->get($code);
                $atRiskSubjects[] = [
                    'code' => $code,
                    'name' => $subject?->name ?? $stats['fallback_name'],
                    'rate' => $subRate,
                    'attended' => $stats['attended'],
                    'total' => $stats['total'],
                    'unexcused_absences' => $stats['unexcused_absences'],
                ];
            }
        }

        usort($atRiskSubjects, static fn (array $a, array $b): int => $a['rate'] <=> $b['rate'] ?: $b['unexcused_absences'] <=> $a['unexcused_absences']);

        $attended = $present + $late + $excused;

        return [
            'total' => $total,
            'attended' => $attended,
            'present' => $present,
            'late' => $late,
            'excused' => $excused,
            'absent' => $absent,
            'rate' => $total > 0 ? (int) round(($attended / $total) * 100) : null,
            'at_risk_subjects' => array_slice($atRiskSubjects, 0, 3),
        ];
    }

    /**
     * Resolve the single primary situational state for the dashboard stage.
     *
     * @param Collection<int, object> $openUncheckedSessions
     * @param Collection<int, object> $todaySchedule
     * @param Collection<int, \App\Models\Subject> $subjects
     */
    private function resolveFocusState(
        Collection $openUncheckedSessions,
        ?object $currentSlot,
        bool $alreadyClockedIn,
        ?object $upcomingClass,
        Collection $todaySchedule,
        ?Holiday $todayHoliday,
        Collection $subjects,
        Carbon $now,
    ): object {
        // Priority 1: Live open attendance session where student has not checked in yet.
        if ($openUncheckedSessions->isNotEmpty()) {
            $primarySession = $openUncheckedSessions->first();
            return (object) [
                'mode' => 'open_session',
                'status_label' => 'Attendance open',
                'status_tone' => 'live',
                'title' => $primarySession->subject_name,
                'code' => $primarySession->subject_code,
                'time_range' => $primarySession->slot
                    ? $primarySession->slot->start_time->format('g:i A') . ' – ' . $primarySession->slot->end_time->format('g:i A')
                    : 'Closes at ' . $primarySession->ends_at->format('g:i A'),
                'session_closes_at' => $primarySession->ends_at,
                'room' => $primarySession->room,
                'instructor' => $primarySession->instructor,
                'start_ms' => $primarySession->slot?->start_time?->getTimestampMs(),
                'end_ms' => $primarySession->ends_at->getTimestampMs(),
                'additional_open_count' => max(0, $openUncheckedSessions->count() - 1),
                'show_primary_checkin' => true,
                'confirmation_text' => null,
                'helper_text' => 'Session closes at ' . $primarySession->ends_at->format('g:i A') . '. Scan the classroom QR or enter the 6-digit PIN.',
                'next_slot' => null,
            ];
        }

        // Priority 2: Scheduled class currently in progress.
        if ($currentSlot !== null) {
            $room = trim((string) ($currentSlot->schedule->room ?? ''));
            $cleanRoom = ($room !== '' && strtoupper($room) !== 'TBA') ? $room : null;
            $instructor = $currentSlot->subject->instructorUser?->name ?? $currentSlot->subject->instructor;
            $nextToday = $todaySchedule->first(
                fn (object $slot) => $slot->schedule_status === 'upcoming'
            );

            if ($alreadyClockedIn) {
                $statusWord = $currentSlot->attendance?->status ?? 'Present';
                $timeIn = $currentSlot->attendance?->time_in
                    ? Carbon::parse($currentSlot->attendance->time_in)->format('g:i A')
                    : null;

                return (object) [
                    'mode' => 'ongoing_checked_in',
                    'status_label' => 'Happening now',
                    'status_tone' => 'recorded',
                    'title' => $currentSlot->subject->name,
                    'code' => $currentSlot->subject->code,
                    'time_range' => $currentSlot->start_time->format('g:i A') . ' – ' . $currentSlot->end_time->format('g:i A'),
                    'session_closes_at' => null,
                    'room' => $cleanRoom,
                    'instructor' => $instructor,
                    'start_ms' => $currentSlot->start_time->getTimestampMs(),
                    'end_ms' => $currentSlot->end_time->getTimestampMs(),
                    'additional_open_count' => 0,
                    'show_primary_checkin' => false,
                    'confirmation_text' => 'Attendance recorded for ' . $currentSlot->subject->code . ' today.',
                    'confirmation_meta' => $timeIn ? $statusWord . ' · ' . $timeIn : $statusWord,
                    'helper_text' => null,
                    'next_slot' => $nextToday,
                ];
            }

            return (object) [
                'mode' => 'ongoing_pending',
                'status_label' => 'Happening now',
                'status_tone' => 'active',
                'title' => $currentSlot->subject->name,
                'code' => $currentSlot->subject->code,
                'time_range' => $currentSlot->start_time->format('g:i A') . ' – ' . $currentSlot->end_time->format('g:i A'),
                'session_closes_at' => null,
                'room' => $cleanRoom,
                'instructor' => $instructor,
                'start_ms' => $currentSlot->start_time->getTimestampMs(),
                'end_ms' => $currentSlot->end_time->getTimestampMs(),
                'additional_open_count' => 0,
                'show_primary_checkin' => true,
                'confirmation_text' => null,
                'helper_text' => 'Check in with the QR code or 6-digit PIN once your instructor opens attendance.',
                'next_slot' => $nextToday,
            ];
        }

        // Priority 3: Upcoming class later today.
        if ($upcomingClass !== null && $upcomingClass->start_time->isToday()) {
            $room = trim((string) ($upcomingClass->schedule->room ?? ''));
            $cleanRoom = ($room !== '' && strtoupper($room) !== 'TBA') ? $room : null;
            $instructor = $upcomingClass->subject->instructorUser?->name ?? $upcomingClass->subject->instructor;

            return (object) [
                'mode' => 'upcoming_today',
                'status_label' => 'Up next today',
                'status_tone' => 'upcoming',
                'title' => $upcomingClass->subject->name,
                'code' => $upcomingClass->subject->code,
                'time_range' => $upcomingClass->start_time->format('g:i A') . ' – ' . $upcomingClass->end_time->format('g:i A'),
                'session_closes_at' => null,
                'room' => $cleanRoom,
                'instructor' => $instructor,
                'start_ms' => $upcomingClass->start_time->getTimestampMs(),
                'end_ms' => $upcomingClass->end_time->getTimestampMs(),
                'additional_open_count' => 0,
                'show_primary_checkin' => false,
                'confirmation_text' => null,
                'helper_text' => null,
                'next_slot' => null,
            ];
        }

        // Priority 4: Clear / holiday / done for today / unenrolled compact state.
        if ($todayHoliday) {
            $title = $todayHoliday->name;
            $helper = 'No regular classes scheduled today.';
        } elseif ($todaySchedule->isNotEmpty()) {
            $title = 'Today’s scheduled classes have ended';
            $recordedCount = $todaySchedule->filter(fn (object $slot) => $slot->attendance !== null)->count();
            $helper = $recordedCount > 0
                ? $recordedCount . ' of ' . $todaySchedule->count() . ' scheduled ' . \Illuminate\Support\Str::plural('class', $todaySchedule->count()) . ' recorded today.'
                : 'No further classes scheduled for ' . $now->format('l') . '.';
        } elseif ($subjects->isEmpty()) {
            $title = 'Free schedule today';
            $helper = 'Your classes will appear after enrollment. Contact the registrar if your subjects are missing.';
        } else {
            $title = 'Free schedule today';
            $helper = $upcomingClass === null
                ? 'No upcoming class scheduled in the next seven days.'
                : 'No classes scheduled for ' . $now->format('l') . '.';
        }

        return (object) [
            'mode' => 'day_clear',
            'status_label' => $todayHoliday ? 'Holiday' : ($todaySchedule->isNotEmpty() ? 'Schedule complete' : 'Clear day'),
            'status_tone' => 'calm',
            'title' => $title,
            'code' => null,
            'time_range' => null,
            'session_closes_at' => null,
            'room' => null,
            'instructor' => null,
            'start_ms' => $upcomingClass?->start_time?->getTimestampMs(),
            'end_ms' => $upcomingClass?->end_time?->getTimestampMs(),
            'additional_open_count' => 0,
            'show_primary_checkin' => false,
            'confirmation_text' => null,
            'helper_text' => $helper,
            'next_slot' => $upcomingClass,
        ];
    }
}
