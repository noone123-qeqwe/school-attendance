<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Subject;
use App\Models\Attendance;
use App\Models\Notification;
use App\Models\ExcuseSubmission;
use App\Models\Holiday;
use App\Models\Announcement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class HomeController extends Controller
{
    public function showPasswordChangeForm()
    {
        return view('auth.force-change-password');
    }

    public function submitPasswordChange(Request $request)
    {
        $request->validate([
            'password' => ['required', strong_password_rule(), 'confirmed'],
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();
        $user->tokens()->delete();

        // Redirect to the appropriate dashboard based on role
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')->with('success', 'Password updated successfully. Welcome!');
        } elseif ($user->isTeacher()) {
            return redirect()->route('teacher.dashboard')->with('success', 'Password updated successfully. Welcome!');
        } elseif ($user->isParent()) {
            return redirect()->route('parent.dashboard')->with('success', 'Password updated successfully. Welcome!');
        }
        return redirect()->route('home')->with('success', 'Password updated successfully. Welcome!');
    }
   public function index()
{
    $user = Auth::user();

    // Admins don't have a student dashboard — send them to admin panel
    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    // Teachers don't have a student dashboard — send them to teacher panel
    if ($user->isTeacher()) {
        return redirect()->route('teacher.dashboard');
    }

    // Parents don't have a student dashboard — send them to parent portal
    if ($user->isParent()) {
        return redirect()->route('parent.dashboard');
    }

    // Ensure we're dealing with a student
    if (!$user->isStudent()) {
        Auth::logout();
        request()->session()->flush();
        return redirect()->route('login')->with('error', 'Invalid user role.');
    }

    $now = now(); 
    $todayDate = $now->toDateString();
    $currentTime = $now->format('H:i:s');

    // 1. Day Mapping
    $dayMap = [
        'Monday'    => 'M',
        'Tuesday'   => 'T',
        'Wednesday' => 'W',
        'Thursday'  => 'TH',
        'Friday'    => 'F',
        'Saturday'  => 'S',
    ];
    $currentDayLetter = $dayMap[$now->format('l')] ?? null;
    $currentDayName   = $now->format('l');
    // Load a student's subjects and schedules once, then derive today's classes
    // from that collection instead of repeating subject and schedule queries.
    $subjects = app(\App\Services\StudentScheduleService::class)->subjectsFor($user);
    $subjectsById = $subjects->keyBy('id');

    $todaySubjects = collect();
    if ($currentDayLetter) {
        $todaySubjects = $subjects->map(function ($subject) use ($user, $currentDayName) {
            $hasExplicitEnrollment = $subject->relationLoaded('pivot')
                && $subject->getRelation('pivot') !== null;
            $matchesStudentProfile = $subject->matchesStudentProfile($user)
                && (!empty($user->section) || empty($subject->section));

            if (!$hasExplicitEnrollment && !$matchesStudentProfile) {
                return null;
            }

            $daySchedules = $subject->schedules->where('day', $currentDayName)->values();
            if ($daySchedules->isEmpty()) {
                return null;
            }

            $todaySubject = clone $subject;
            $todaySubject->setRelation('schedules', $daySchedules);
            return $todaySubject;
        })->filter()->values();
    }

    // Reuse today's eager-loaded schedules to locate the active class instead of
    // issuing a second subject/schedule query for the same student.
    $currentClass = null;
    foreach ($todaySubjects as $subject) {
        $activeSchedule = $subject->schedules->first(function ($schedule) use ($currentTime) {
            return Carbon::parse($schedule->start_time)->format('H:i:s') <= $currentTime
                && Carbon::parse($schedule->end_time)->format('H:i:s') >= $currentTime;
        });

        if (!$activeSchedule) {
            continue;
        }

        $currentClass = clone $subject;
        $currentClass->setRelation('schedules', collect([$activeSchedule]));
        break;
    }

    // 4. Attendance Status (15-min Grace Period)
    $attendanceStatus = 'Present';
    if ($currentClass && $currentClass->schedules->isNotEmpty()) {
        $currentSchedule = $currentClass->schedules->first();
        $startTime = Carbon::parse($currentSchedule->start_time);
        if ($now->diffInMinutes($startTime, false) < -15) {
            $attendanceStatus = 'Late';
        }
    }

    // Pre-fetch today's attendance records for this user in a single query (avoids N+1 DB roundtrips)
    $todayAttendances = Attendance::where('user_id', $user->id)
        ->where('date', $todayDate)
        ->get()
        ->keyBy('subject_code');

    // 5. Clock-in Check — only Present/Late counts as clocked in
    $alreadyClockedIn = false;
    if ($currentClass) {
        $existingRecord = $todayAttendances->get($currentClass->code);
        $alreadyClockedIn = $existingRecord && in_array($existingRecord->status, ['Present', 'Late']);
    }

    // Calendar records and school events are useful detail, but they are not
    // needed to render the first dashboard screen. They are loaded through a
    // protected endpoint when opened so an authenticated
    // student reaches the dashboard without waiting for extra relationships.
    // Aggregate once in the database, then build the small per-subject summary
    // in PHP instead of loading every historical row for this dashboard.
    $attendanceSummaryRows = Attendance::where('user_id', $user->id)
        ->selectRaw('subject_code, status, COUNT(*) as record_count, SUM(CASE WHEN excused THEN 1 ELSE 0 END) as excused_count')
        ->groupBy('subject_code', 'status')
        ->get();

    $stats = [];
    $subjectAttendanceStats = collect();
    foreach ($attendanceSummaryRows as $summaryRow) {
        $recordCount = (int) $summaryRow->record_count;
        $stats[$summaryRow->status] = ($stats[$summaryRow->status] ?? 0) + $recordCount;

        $subjectCode = (string) ($summaryRow->subject_code ?? '');
        $subjectStatsRow = $subjectAttendanceStats->get($subjectCode) ?? (object) [
            'present_count' => 0,
            'late_count' => 0,
            'absent_count' => 0,
            'excused_count' => 0,
        ];

        if ($summaryRow->status === 'Present') $subjectStatsRow->present_count += $recordCount;
        if ($summaryRow->status === 'Late') $subjectStatsRow->late_count += $recordCount;
        if ($summaryRow->status === 'Absent') $subjectStatsRow->absent_count += $recordCount;
        $subjectStatsRow->excused_count += (int) $summaryRow->excused_count;
        $subjectAttendanceStats->put($subjectCode, $subjectStatsRow);
    }

    // 8. Stats Calculation — Option B (overall rate)
    $attendanceRecordCount = array_sum($stats);

    $totalPresent = $stats['Present'] ?? 0;
    $totalLate    = $stats['Late'] ?? 0;
    $totalAbsent  = $stats['Absent'] ?? 0;

    // Build today's schedule before calculating attendance totals.
    $todaySchedule = collect();
    $userCreated = $user->created_at ? Carbon::parse($user->created_at)->timezone('Asia/Manila') : null;
    if ($todaySubjects->isNotEmpty()) {
        foreach ($todaySubjects as $subject) {
            $sched = $subject->schedules->first();
            if (!$sched) continue;

            $classStart = Carbon::parse($todayDate . ' ' . $sched->start_time);
            $classEnd   = Carbon::parse($todayDate . ' ' . $sched->end_time);

            $existing = $todayAttendances->get($subject->code);

            $joinedAt = $userCreated;
            if (!$subject->matchesStudentProfile($user)) {
                $pivotCreatedAt = $subjectsById->get($subject->id)?->pivot?->created_at;
                if ($pivotCreatedAt) {
                    $enrolledAt = Carbon::parse($pivotCreatedAt)->timezone('Asia/Manila');
                    if (!$joinedAt || $enrolledAt->greaterThan($joinedAt)) {
                        $joinedAt = $enrolledAt;
                    }
                }
            }
            $joinedAfterSessionStarted = $joinedAt && $joinedAt->greaterThanOrEqualTo($classStart);

            $status = 'upcoming';
            if ($existing && in_array($existing->status, ['Present', 'Late'])) {
                $status = 'completed';
            } elseif ($existing && $existing->status === 'Absent') {
                $status = 'missed';
            } elseif ($joinedAfterSessionStarted) {
                // Class started before account registration or explicit enrollment.
                $status = 'past';
            } elseif ($now->greaterThan($classEnd)) {
                $status = 'missed';
            } elseif ($now->greaterThanOrEqualTo($classStart) && $now->lessThanOrEqualTo($classEnd)) {
                $status = 'ongoing';
            }

            $todaySchedule->push((object)[
                'subject'    => $subject,
                'schedule'   => $sched,
                'start_time' => $classStart,
                'end_time'   => $classEnd,
                'status'     => $status,
                'attendance' => $existing,
            ]);
        }
        $todaySchedule = $todaySchedule->sortBy('start_time')->values();
    }

    // ── Historical missed classes calculation ──
    $historicalMissesPerSubject = app(\App\Actions\Attendance\CalculateMissedAttendanceAction::class)->executePerSubject($user, $subjects);
    $historicalMissesTotal = array_sum($historicalMissesPerSubject);
    $totalAbsent += $historicalMissesTotal;
    $totalRecords = $totalPresent + $totalLate + $totalAbsent;
    $presentRecords = $totalPresent + $totalLate;

    $attendanceRate = $totalRecords > 0
        ? round(($presentRecords / $totalRecords) * 100)
        : null;

    // 8b. Detailed stats for dashboard donut chart
    // (Already captured above)

    // 8c. Attendance streak (consecutive days present/late, not absent - optimized query)
    $streakCount = 0;
    $streakRecords = Attendance::where('user_id', $user->id)
        ->where('date', '>=', now()->subDays(60)->toDateString())
        ->select('date', 'status')
        ->orderBy('date', 'desc')
        ->get()
        ->groupBy(fn($r) => $r->date->toDateString());

    foreach ($streakRecords as $date => $dayRecords) {
        $allOnTime = $dayRecords->every(fn($r) => in_array($r->status, ['Present', 'Late']));
        if ($allOnTime) {
            $streakCount++;
        } else {
            break;
        }
    }

    // 8d. Today's schedule with status (upcoming, ongoing, completed, missed)
    // (Already built above to calculate dynamic misses)


    // 9. Fetch active warnings. These are actionable and remain part of the
    // initial response.
    $activeWarnings = \App\Models\Warning::where('user_id', $user->id)
        ->where('created_at', '>=', $now->copy()->subDays(14))
        ->orderBy('created_at', 'desc')
        ->get();

    // 10. Per-subject attendance breakdown
    $subjectStats = collect();
    foreach ($subjects as $subjectModel) {
        $subjectRecords = $subjectAttendanceStats->get($subjectModel->code);

        $present = (int) ($subjectRecords->present_count ?? 0);
        $late = (int) ($subjectRecords->late_count ?? 0);
        $absent = (int) ($subjectRecords->absent_count ?? 0);
        $excused = (int) ($subjectRecords->excused_count ?? 0);
        
        // Add historical missed classes (unrecorded absences from past days)
        $historicalMiss = $historicalMissesPerSubject[$subjectModel->code] ?? 0;
        $absent += $historicalMiss;

        $total = $present + $late + $absent;

        $effectiveTotal = $total - $excused;
        $rate = $effectiveTotal > 0 ? round((($present + $late) / $effectiveTotal) * 100) : 0;

        $subjectStats->push((object)[
            'code' => $subjectModel->code,
            'name' => $subjectModel->name ?? $subjectModel->code,
            'total' => $total,
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'excused' => $excused,
            'rate' => $rate,
        ]);
    }
    $subjectStats = $subjectStats->sortBy('rate')->values();

    return view('home', compact(
        'currentClass',
        'attendanceStatus',
        'alreadyClockedIn',
        'subjects',
        'attendanceRecordCount',
        'attendanceRate',
        'todaySubjects',
        'activeWarnings',
        'totalPresent',
        'totalLate',
        'totalAbsent',
        'streakCount',
        'todaySchedule',
        'subjectStats'
    ));
}

    /**
     * Return the detailed month data used by the dashboard's calendar dialog.
     *
     * Keeping this separate from index() lets the visible dashboard render
     * before optional calendar relationships and events have finished loading.
     */
    public function dashboardCalendarData(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->isStudent(), 403);

        $now = now();
        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'between:2000,2100'],
            'month' => ['sometimes', 'integer', 'between:1,12'],
        ]);
        $year = (int) ($validated['year'] ?? $now->year);
        $month = (int) ($validated['month'] ?? $now->month);

        $calendarMonth = Carbon::create($year, $month, 1);
        $records = Attendance::with(['subject.schedules', 'subject.instructorUser', 'excuseSubmission'])
            ->where('user_id', $user->id)
            ->whereBetween('date', [
                $calendarMonth->copy()->startOfMonth()->toDateString(),
                $calendarMonth->copy()->endOfMonth()->toDateString(),
            ])
            ->orderBy('date', 'desc')
            ->orderBy('time_in', 'desc')
            ->get();

        [, $eventsMap] = $this->getStudentCalendarEventsMap($user, $year, $month, true);

        return response()->json([
            'attendance' => $this->formatDashboardCalendarRecords($records),
            'events' => $eventsMap,
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    /**
     * Format the small calendar-dialog payload without requiring the dashboard
     * Blade view to eagerly serialize every attendance relationship.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function formatDashboardCalendarRecords(\Illuminate\Support\Collection $records): array
    {
        $calendar = [];

        foreach ($records as $record) {
            $date = Carbon::parse($record->date);
            $dayName = $date->format('l');
            $schedule = $record->subject?->schedules?->firstWhere('day', $dayName)
                ?? $record->subject?->schedules?->first();

            $scheduleStart = $schedule ? Carbon::parse($schedule->start_time)->format('g:i A') : null;
            $scheduleEnd = $schedule ? Carbon::parse($schedule->end_time)->format('g:i A') : null;
            $clockIn = $record->time_in
                ? Carbon::parse($record->time_in)->format('g:i A')
                : ($record->checked_in_at ? $record->checked_in_at->format('g:i A') : null);
            $clockOut = $record->time_out ? Carbon::parse($record->time_out)->format('g:i A') : null;
            $status = $record->status ? ucfirst(strtolower($record->status)) : 'Absent';
            $dateKey = $date->toDateString();

            $calendar[$dateKey][] = [
                'id' => $record->id,
                'subject' => $record->subject?->name ?? $record->subject_name ?? $record->subject_code ?? 'Class',
                'code' => $record->subject_code ?? $record->subject?->code ?? '',
                'instructor' => $record->subject?->instructorUser?->name
                    ?? $record->subject?->instructor
                    ?? 'Instructor TBA',
                'schedule' => ($scheduleStart && $scheduleEnd) ? "{$scheduleStart} – {$scheduleEnd}" : 'Schedule TBA',
                'schedule_start' => $scheduleStart,
                'schedule_end' => $scheduleEnd,
                'status' => $status,
                'status_lower' => strtolower($status),
                'time_in' => $clockIn,
                'clock_in' => $clockIn ?? 'No clock-in',
                'time_out' => $clockOut,
                'remarks' => $record->excuse_note
                    ?? $record->excuseSubmission?->reason
                    ?? ($record->excused ? 'Excused Absence' : null),
            ];
        }

        return $calendar;
    }

    /**
     * Dedicated Attendance Calendar page.
     */
    public function attendanceCalendar()
    {
        $user = Auth::user();

        if (!$user->isStudent()) {
            return redirect()->route('home');
        }

        // The school calendar already includes the student's attendance feed.
        // Keep this legacy URL working for bookmarks while using one calendar page.
        return redirect()->route('student.calendar');
    }

    /**
     * Build unified calendar events, exams, and holidays for a student.
     */
    private function getStudentCalendarEventsMap(User $user, $calYear = null, $calMonth = null, bool $monthOnly = false): array
    {
        $now = now();
        $year = (int) ($calYear ?? request('cal_year', $now->year));
        $month = (int) ($calMonth ?? request('cal_month', $now->month));

        $viewMonthStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $viewMonthEnd   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $rangeStart = $monthOnly ? $viewMonthStart : min($viewMonthStart, $now->copy()->subDays(60)->toDateString());
        $rangeEnd   = $monthOnly ? $viewMonthEnd : max($viewMonthEnd, $now->copy()->addDays(90)->toDateString());

        // 1. Fetch active holidays in range
        $holidays = Holiday::active()
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->orderBy('date')
            ->get();

        // 2. Fetch student-visible events (exams, school events, etc.) with subject relation
        $studentEvents = Event::visibleTo($user)
            ->with('subject')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $calendarEvents = collect();

        foreach ($holidays as $hol) {
            $dateStr = $hol->date instanceof \DateTimeInterface 
                ? $hol->date->format('Y-m-d') 
                : Carbon::parse($hol->date)->format('Y-m-d');

            $calendarEvents->push((object)[
                'id'          => 'hol_' . $hol->id,
                'type'        => 'holiday',
                'title'       => $hol->name,
                'description' => $hol->description ?? 'No classes',
                'date'        => $dateStr,
                'time'        => null,
                'location'    => null,
                'subject'     => null,
            ]);
        }

        foreach ($studentEvents as $evt) {
            $dateStr = $evt->date instanceof \DateTimeInterface 
                ? $evt->date->format('Y-m-d') 
                : Carbon::parse($evt->date)->format('Y-m-d');

            $type = match($evt->type) {
                'exam' => 'exam',
                'holiday' => 'holiday',
                default => 'event',
            };

            $timeStr = null;
            if ($evt->start_time && $evt->end_time) {
                $startFormatted = Carbon::parse($evt->start_time)->format('g:i A');
                $endFormatted   = Carbon::parse($evt->end_time)->format('g:i A');
                $timeStr = "{$startFormatted} – {$endFormatted}";
            } elseif ($evt->start_time) {
                $timeStr = Carbon::parse($evt->start_time)->format('g:i A');
            }

            $subjectName = $evt->subject ? ($evt->subject->name ?? $evt->subject->code) : null;

            $calendarEvents->push((object)[
                'id'          => 'evt_' . $evt->id,
                'type'        => $type,
                'title'       => $evt->name,
                'description' => $evt->description ?? '',
                'date'        => $dateStr,
                'time'        => $timeStr,
                'location'    => $evt->location ?? null,
                'subject'     => $subjectName,
            ]);
        }

        // Deduplicate identical items on same date
        $calendarEvents = $calendarEvents
            ->unique(fn($evt) => $evt->date . '_' . $evt->type . '_' . strtolower(trim($evt->title)))
            ->sortBy('date')
            ->values();

        $eventsMap = [];
        foreach ($calendarEvents as $evt) {
            $eventsMap[$evt->date][] = [
                'id'          => $evt->id,
                'type'        => $evt->type,
                'title'       => $evt->title,
                'description' => $evt->description,
                'date'        => $evt->date,
                'time'        => $evt->time,
                'location'    => $evt->location,
                'subject'     => $evt->subject,
            ];
        }

        return [$calendarEvents, $eventsMap];
    }

    public function settings()
    {
        $user = Auth::user();
        $linkedParents = ($user && $user->isStudent()) ? $user->parents()->get() : collect();
        $hasCameraFaceEnrollment = $user && $user->isStudent()
            ? \App\Models\ProfileFacePhoto::where('user_id', $user->id)->exists()
            : false;
        return view('settings', compact('linkedParents', 'hasCameraFaceEnrollment'));
    }

    public function faceRegistration(Request $request, \App\Services\FeatureFlagService $features)
    {
        return view('student.biometrics.face', [
            'hasCameraFaceEnrollment' => \App\Models\ProfileFacePhoto::where('user_id', $request->user()->id)->exists(),
            'faceEnrollmentAvailable' => $features->enabled('feature.face_verification'),
        ]);
    }

    /**
     * Generate an instant 6-digit link code for the authenticated student
     */
    public function generateParentLinkCode(\App\Services\ParentService $parentService)
    {
        $user = Auth::user();
        if (!$user || !$user->isStudent()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        try {
            $data = $parentService->generateStudentLinkCode($user);
            return response()->json(array_merge(['success' => true], $data));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Students are not permitted to unlink guardians.
     */
    public function unlinkParent(Request $request, \App\Services\ParentService $parentService)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Students are not authorized to revoke guardian connections. Please contact your school administrator or guardian.',
            ], 403);
        }
        return back()->with('error', 'Students are not authorized to revoke guardian connections. Please contact your school administrator or guardian.');
    }

    public function notifications()
    {
        $user = Auth::user();
        
        $query = Notification::with(['sender', 'subject'])->where('user_id', $user->id);
        
        $status = request('status');
        if ($status === 'archived') {
            $query->archived();
        } elseif ($status === 'unread') {
            $query->active()->where('is_read', false);
        } else {
            $query->active();
        }

        if (request()->filled('type')) {
            $type = request('type');
            if ($type === 'warning') {
                $query->where('type', 'like', '%warning%');
            } elseif ($type === 'absence') {
                $query->where('type', 'absence');
            } elseif ($type === 'system') {
                $query->where('type', 'system_update');
            } else {
                $query->where('type', $type);
            }
        }

        if (request()->filled('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('subject_code', 'like', "%{$search}%")
                  ->orWhereHas('subject', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $counts = [
            'active' => Notification::where('user_id', $user->id)->active()->count(),
            'unread' => Notification::where('user_id', $user->id)->active()->where('is_read', false)->count(),
            'archived' => Notification::where('user_id', $user->id)->archived()->count(),
            'warnings' => Notification::where('user_id', $user->id)->active()->where('type', 'like', '%warning%')->count(),
            'absences' => Notification::where('user_id', $user->id)->active()->where('type', 'absence')->count(),
        ];

        $notifications = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('notifications', compact('notifications', 'counts'));
    }

    public function markNotificationsRead()
    {
        \App\Models\Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0
            ]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    public function markSingleNotificationRead(\App\Models\Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        if (request()->wantsJson() || request()->ajax()) {
            $unreadCount = \App\Models\Notification::where('user_id', Auth::id())
                ->active()
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount
            ]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function deleteNotification(\App\Models\Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->delete();

        if (request()->wantsJson() || request()->ajax()) {
            $unreadCount = \App\Models\Notification::where('user_id', Auth::id())
                ->active()
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount
            ]);
        }

        return back()->with('success', 'Notification deleted.');
    }

    public function archiveNotification(\App\Models\Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->archive();

        if (request()->wantsJson() || request()->ajax()) {
            $unreadCount = \App\Models\Notification::where('user_id', Auth::id())
                ->active()
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount
            ]);
        }

        return back()->with('success', 'Notification archived.');
    }

    public function unarchiveNotification(\App\Models\Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->unarchive();

        if (request()->wantsJson() || request()->ajax()) {
            $unreadCount = \App\Models\Notification::where('user_id', Auth::id())
                ->active()
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount
            ]);
        }

        return back()->with('success', 'Notification unarchived.');
    }

    public function pollNotifications(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['unread_count' => 0, 'notifications' => []]);
        }

        if ($user->isAdmin() && !$request->session()->get('admin_2fa_verified', false)) {
            return response()->json(['message' => 'Admin verification is required.'], 403);
        }

        if ($user->isParent()) {
            $childIds = $user->children()->pluck('users.id');
            $recipientIds = $childIds->all();
            $recipientIds[] = $user->id;
            $unreadCount = \App\Models\Notification::where('user_id', $user->id)->active()->where('is_read', false)->count()
                + \App\Models\Notification::whereIn('user_id', $childIds)->active()->where('is_read', false)
                    ->whereNotExists(function ($query) use ($user) {
                        $query->selectRaw('1')->from('parent_notification_reads')
                            ->whereColumn('parent_notification_reads.notification_id', 'notifications.id')
                            ->where('parent_notification_reads.parent_id', $user->id);
                    })->count();
            $notifications = \App\Models\Notification::with(['user', 'sender', 'subject', 'parentReads' => fn ($q) => $q->where('parent_id', $user->id)])
                ->whereIn('user_id', $recipientIds)
                ->active()
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get();
        } else {
            $unreadCount = \App\Models\Notification::where('user_id', $user->id)->active()->where('is_read', false)->count();
            $notifications = \App\Models\Notification::with(['sender', 'subject'])
                ->where('user_id', $user->id)
                ->active()
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get();
        }

        $formatted = $notifications->map(function ($notif) use ($user) {
            return [
                'id' => $notif->id,
                'type' => $notif->type,
                'message' => $notif->message,
                'is_read' => $user->isParent() && (int) $notif->user_id !== (int) $user->id
                    ? $notif->parentReads->isNotEmpty()
                    : (bool) $notif->is_read,
                'created_at_human' => $notif->created_at->diffForHumans(),
                'is_today' => $notif->created_at->isToday(),
                'sender_name' => $notif->sender?->name,
                'subject_name' => $notif->subject?->name ?? $notif->subject_code,
            ];
        });

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $formatted,
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s]+$/',
        ]);

        $user->phone = $request->phone;
        $user->save();

        return redirect()->route('settings')->with('success', 'Phone number updated successfully!');
    }

    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        
        $prefs = $request->input('prefs', []);
        
        // Ensure values are boolean
        $formattedPrefs = [
            'in_app' => !empty($prefs['in_app']),
            'email'  => !empty($prefs['email']),
        ];

        $user->notification_preferences = $formattedPrefs;
        $user->save();

        return redirect()->route('settings')->with('success', 'Notification preferences saved successfully!');
    }

    // ─────────────────────────────────────────
    // EXCUSE SUBMISSIONS
    // ─────────────────────────────────────────
    public function excuses()
    {
        $user = Auth::user();
        
        // Get absent attendance records that can be excused
        $absentRecords = Attendance::with(['subject'])
            ->where('user_id', $user->id)
            ->where('status', 'Absent')
            ->doesntHave('excuseSubmission')
            ->orderBy('date', 'desc')
            ->get();

        // Get all excuse submissions
        $excuseSubmissions = ExcuseSubmission::with(['attendance.subject'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('excuses.index', compact('absentRecords', 'excuseSubmissions'));
    }

    public function createExcuse(Attendance $attendance)
    {
        // Verify this attendance belongs to the current user and is absent
        if ($attendance->user_id !== Auth::id() || $attendance->status !== 'Absent') {
            abort(403);
        }

        // Check if excuse already submitted
        if ($attendance->excuseSubmission) {
            return redirect()->route('excuses')->with('error', 'Excuse already submitted for this absence.');
        }

        $user = Auth::user();
        $subjects = $user->getAllSubjects();
        
        // Find any other absent records on the same date that don't have excuse submissions yet
        $sameDayAttendances = Attendance::where('user_id', $user->id)
            ->whereDate('date', $attendance->date)
            ->where('status', 'Absent')
            ->whereDoesntHave('excuseSubmission')
            ->get()
            ->keyBy('subject_code');

        return view('excuses.create', compact('attendance', 'subjects', 'sameDayAttendances'));
    }

    public function createGeneralExcuse()
    {
        $user = Auth::user();
        $subjects = $user->getAllSubjects();
        return view('excuses.create_general', compact('subjects'));
    }

    public function storeGeneralExcuse(Request $request)
    {
        $request->validate([
            'subject_code' => 'nullable|string',
            'subject_codes' => 'nullable|array',
            'subject_codes.*' => 'string|max:50',
            'date' => 'required|date|before_or_equal:today',
            'reason' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        $user = Auth::user();
        $enrolledSubjects = $user->getAllSubjects();
        $allEnrolledCodes = $enrolledSubjects->pluck('code')->toArray();

        if (empty($allEnrolledCodes)) {
            return redirect()->back()->with('error', 'You are not enrolled in any subjects.')->withInput();
        }

        $selectedCodes = [];

        // Check if multiple subject_codes array was sent
        if ($request->has('subject_codes') && is_array($request->subject_codes) && !empty($request->subject_codes)) {
            if (in_array('all_subjects', $request->subject_codes)) {
                $selectedCodes = $allEnrolledCodes;
            } else {
                $selectedCodes = array_values(array_intersect($request->subject_codes, $allEnrolledCodes));
            }
        } elseif ($request->filled('subject_code')) {
            if ($request->subject_code === 'all_subjects') {
                $selectedCodes = $allEnrolledCodes;
            } elseif (in_array($request->subject_code, $allEnrolledCodes)) {
                $selectedCodes = [$request->subject_code];
            }
        }

        if (empty($selectedCodes)) {
            return redirect()->back()->with('error', 'Please select at least one subject to submit your excuse letter for.')->withInput();
        }

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = app(\App\Services\ExcuseAttachmentStorage::class)->store($file);
                $attachmentPaths[] = $path;
            }
        }

        $submittedCount = 0;
        foreach ($selectedCodes as $code) {
            $subModel = \App\Models\Subject::where('code', $code)->first();
            // Excuse submissions must refer to an existing official absence.
            // Students cannot create attendance rows through this form.
            $attendance = Attendance::where([
                'user_id' => $user->id,
                'subject_code' => $code,
                'date' => $request->date,
                'status' => 'Absent',
            ])->whereDoesntHave('excuseSubmission')->first();

            if (!$attendance) {
                continue;
            }

            $excuseSubmission = ExcuseSubmission::create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'reason' => $request->reason,
                'description' => $request->description,
                'attachments' => $attachmentPaths,
                'status' => 'pending'
            ]);

            $submittedCount++;

            // Notify Teacher
            if ($subModel && $subModel->instructor_id) {
                \App\Models\Notification::create([
                    'user_id' => $subModel->instructor_id,
                    'title' => 'New Excuse Letter',
                    'message' => "{$user->name} submitted an excuse letter for {$subModel->name} ({$code}) on " . \Carbon\Carbon::parse($request->date)->format('M d, Y'),
                    'type' => 'custom',
                    'link' => route('teacher.excuse.reviews')
                ]);

                event(new \App\Events\ExcuseSubmitted(
                    $excuseSubmission,
                    $user->name,
                    $code,
                    $subModel->instructor_id
                ));
            }
        }

        if ($submittedCount === 0) {
            return redirect()->route('excuses')->with('error', 'No unexcused absence record was found for the selected subjects and date.');
        }

        $msg = $submittedCount > 1 
            ? "Excuse letters submitted successfully for {$submittedCount} subjects! They will be reviewed by your instructors."
            : "Excuse letter submitted successfully! It will be reviewed by your instructor.";

        return redirect()->route('excuses')->with('success', $msg);
    }

    public function storeExcuse(Request $request)
    {
        $request->validate([
            'attendance_id' => 'nullable|exists:attendances,id',
            'attendance_ids' => 'nullable|array',
            'attendance_ids.*' => 'exists:attendances,id',
            'subject_codes' => 'nullable|array',
            'subject_codes.*' => 'string|max:50',
            'date' => 'nullable|date|before_or_equal:today',
            'reason' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        $user = Auth::user();
        $targetAttendances = collect();

        // 1. If explicit attendance_ids provided
        if ($request->has('attendance_ids') && is_array($request->attendance_ids)) {
            $records = Attendance::whereIn('id', $request->attendance_ids)
                ->where('user_id', $user->id)
                ->where('status', 'Absent')
                ->whereDate('date', '<=', today()->toDateString())
                ->whereDoesntHave('excuseSubmission')
                ->get();
            $targetAttendances = $targetAttendances->merge($records);
        }

        // 2. If single attendance_id provided
        if ($request->filled('attendance_id')) {
            $single = Attendance::where('id', $request->attendance_id)
                ->where('user_id', $user->id)
                ->where('status', 'Absent')
                ->whereDate('date', '<=', today()->toDateString())
                ->whereDoesntHave('excuseSubmission')
                ->first();
            if ($single) {
                $targetAttendances->push($single);
            }
        }

        // 3. If additional subject_codes provided for a date, only use existing
        // official absence records. A student cannot manufacture attendance here.
        if ($request->has('subject_codes') && is_array($request->subject_codes)) {
            $refDate = $request->input('date') ?? ($targetAttendances->first() ? $targetAttendances->first()->date : now()->toDateString());
            $enrolledCodes = $user->getAllSubjects()->pluck('code')->toArray();
            
            foreach ($request->subject_codes as $code) {
                if (!in_array($code, $enrolledCodes)) continue;
                $att = Attendance::where([
                    'user_id' => $user->id,
                    'subject_code' => $code,
                    'date' => $refDate,
                    'status' => 'Absent',
                ])->whereDoesntHave('excuseSubmission')->first();

                if ($att) {
                    $targetAttendances->push($att);
                }
            }
        }

        $targetAttendances = $targetAttendances->unique('id');

        if ($targetAttendances->isEmpty()) {
            return redirect()->route('excuses')->with('error', 'No eligible absence records found or excuse already submitted.');
        }

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = app(\App\Services\ExcuseAttachmentStorage::class)->store($file);
                $attachmentPaths[] = $path;
            }
        }

        $count = 0;
        foreach ($targetAttendances as $attendance) {
            $excuseSubmission = ExcuseSubmission::create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'reason' => $request->reason,
                'description' => $request->description,
                'attachments' => $attachmentPaths,
                'status' => 'pending'
            ]);

            $count++;

            // Find teacher and notify
            $subject = \App\Models\Subject::where('code', $attendance->subject_code)->first();
            if ($subject && $subject->instructor_id) {
                \App\Models\Notification::create([
                    'user_id' => $subject->instructor_id,
                    'title' => 'New Excuse Letter',
                    'message' => "New excuse letter submitted by {$user->name} for {$attendance->subject_code} (" . \Carbon\Carbon::parse($attendance->date)->format('M j, Y') . ")",
                    'type' => 'custom',
                    'is_read' => false,
                    'link' => route('teacher.excuse.reviews')
                ]);

                event(new \App\Events\ExcuseSubmitted(
                    $excuseSubmission,
                    $user->name,
                    $attendance->subject_code,
                    $subject->instructor_id
                ));
            }
        }

        $msg = $count > 1 
            ? "Excuse letters submitted successfully for {$count} subjects! They will be reviewed by your instructors."
            : "Excuse letter submitted successfully! It will be reviewed by your instructor.";

        return redirect()->route('excuses')->with('success', $msg);
    }

    /**
     * Student calendar view.
     */
    public function calendar()
    {
        $year = request('year', now()->year);
        $month = request('month', now()->month);
        
        return view('student.calendar', compact('year', 'month'));
    }

    public function calendarData(Request $request, \App\Services\CalendarService $calendarService)
    {
        return response()->json(
            $calendarService->getEventsForUser(
                Auth::user(),
                $request->query('start'),
                $request->query('end')
            )
        );
    }

    /**
     * Search invitees for the attendee picker (Student).
     */
    public function searchInvitees(Request $request)
    {
        $q = $request->query('q');

        $query = User::query();

        if ($q) {
            $query->where(function($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $query->whereIn('role', ['teacher', 'student']);

        $results = $query->select('id', 'name', 'email', 'role', 'profile_image')
                         ->paginate(10);

        return response()->json($results);
    }

    /**
     * Create a meeting event (Student).
     */
    public function storeMeeting(Request $request)
    {
        $this->authorize('create', [Event::class, 'meeting']);

        $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'location' => 'nullable|string|max:255',
            'attendee_ids' => 'nullable|array',
            'attendee_ids.*' => 'exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $event = Event::create([
                'name' => $request->name,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'type' => 'meeting',
                'status' => 'scheduled',
                'location' => $request->location,
                'organizer_id' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            $userIdsToInvite = [];

            if ($request->has('attendee_ids')) {
                foreach ($request->attendee_ids as $id) {
                    $userIdsToInvite[] = $id;
                }
            }

            $userIdsToInvite = array_unique($userIdsToInvite);
            
            $attendeeData = [];
            foreach ($userIdsToInvite as $uid) {
                $attendeeData[$uid] = ['response' => 'pending'];
            }
            
            $event->attendees()->syncWithoutDetaching($attendeeData);

            DB::commit();

            return response()->json(['success' => true, 'event' => $event]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
