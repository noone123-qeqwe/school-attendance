<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AcademicYear;
use App\Models\AttendanceSession;
use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\User;
use App\Models\Subject;
use App\Models\Holiday;
use App\Models\Event;
use App\Models\Announcement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get all data required for the Admin Dashboard.
     */
    public function getAdminDashboardData(): array
    {
        $dailyAttendance = $this->dailyAttendance(7);

        // ── Active attendance sessions (batch-optimized, zero N+1) ──
        $todayStr = today()->toDateString();
        $activeSessions = AttendanceSession::where('active', true)
            ->where(function($q) {
                $q->whereNull('session_ends_at')->orWhere('session_ends_at', '>', now());
            })
            ->with(['creator', 'subject'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($activeSessions->isNotEmpty()) {
            $sessionSubjectCodes = $activeSessions->pluck('subject_code')->unique()->filter();
            $checkInCounts = Attendance::whereIn('subject_code', $sessionSubjectCodes)
                ->where('date', $todayStr)
                ->selectRaw('subject_code, count(*) as count')
                ->groupBy('subject_code')
                ->pluck('count', 'subject_code');

            $activeSessions->each(function ($session) use ($checkInCounts) {
                $session->checked_in_count = $checkInCounts->get($session->subject_code, 0);
                $session->qr_status = $session->isTokenValid() ? 'Active' : 'Expired';
            });
        }

        $activeSessionCount = $activeSessions->count();

        $expiredSessions = AttendanceSession::where('active', true)
            ->where('session_ends_at', '<', now())
            ->count();

        return compact('dailyAttendance', 'activeSessions', 'activeSessionCount', 'expiredSessions');
    }

    public function getAcademicOverviewData(): array
    {
        $directoryCounts = $this->academicDirectoryCounts();
        $term = AcademicYear::current();
        $sectionCount = Section::count();
        $scheduledClasses = ClassSchedule::query()
            ->whereHas('subject')
            ->whereHas('section')
            ->select('subject_id', 'section_id')
            ->distinct();
        $activeClassCount = DB::query()->fromSub($scheduledClasses, 'scheduled_classes')->count();

        $recentSchedules = ClassSchedule::query()
            ->whereHas('subject')
            ->whereHas('section')
            ->with(['subject:id,code,name', 'section:id,name'])
            ->latest('updated_at')->orderByDesc('id')->limit(5)->get();

        $dailyAttendance = collect($this->dailyAttendance(30));
        $recorded = $dailyAttendance->sum('recorded');
        $checkedIn = $dailyAttendance->sum('present') + $dailyAttendance->sum('late');
        $attendanceSummary = [
            'rate' => $recorded ? (int) round(100 * $checkedIn / $recorded) : null,
            'recorded' => $recorded,
            'absent' => $dailyAttendance->sum('absent'),
        ];

        return compact('directoryCounts', 'term', 'sectionCount', 'activeClassCount', 'recentSchedules', 'attendanceSummary');
    }

    private function academicDirectoryCounts(): array
    {
        return Cache::remember('admin_core_counts', 300, fn () => [
            'students' => User::where('role', 'student')->count(),
            'teachers' => User::where('role', 'teacher')->count(),
        ]);
    }

    private function dailyAttendance(int $days): array
    {
        $today = today()->toImmutable();
        $start = $today->subDays($days - 1);
        $rows = Attendance::query()
            ->whereBetween('date', [$start->toDateString(), $today->toDateString()])
            ->whereIn('status', ['Present', 'Late', 'Absent'])
            ->selectRaw('date, status, COUNT(*) as total')
            ->groupBy('date', 'status')
            ->toBase()->get()->groupBy('date');

        return array_map(static function (int $offset) use ($start, $rows) {
            $date = $start->addDays($offset);
            $counts = $rows->get($date->toDateString(), collect())->pluck('total', 'status');
            $present = (int) $counts->get('Present', 0);
            $late = (int) $counts->get('Late', 0);
            $absent = (int) $counts->get('Absent', 0);
            $recorded = $present + $late + $absent;
            $rate = $recorded ? (int) round(100 * ($present + $late) / $recorded) : null;

            return compact('date', 'present', 'late', 'absent', 'recorded', 'rate');
        }, range(0, $days - 1));
    }

    /**
     * Alias for getHolidayCalendarData or dashboard data retrieval.
     */
    public function getDashboardData(?int $calYear = null, ?int $calMonth = null): array
    {
        $year = $calYear ?? now()->year;
        $month = $calMonth ?? now()->month;
        return $this->getHolidayCalendarData($year, $month);
    }

    /**
     * Get Holiday and Event calendar map and upcoming events for any year/month (cached).
     */
    public function getHolidayCalendarData(int $calYear, int $calMonth): array
    {
        $todayStr = now()->toDateString();
        $cacheKey = "hcal_events_{$calYear}_{$calMonth}_{$todayStr}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($calYear, $calMonth, $todayStr) {
            $calStart = Carbon::create($calYear, $calMonth, 1);
            $rangeStart = $calStart->copy()->subMonth()->startOfMonth()->toDateString();
            $rangeEnd = $calStart->copy()->addMonth()->endOfMonth()->toDateString();

            $calendarHolidays = Holiday::active()
                ->whereBetween('date', [$rangeStart, $rangeEnd])
                ->orderBy('date')
                ->get()
                ->unique(fn($h) => $h->date->format('Y-m-d') . '_' . strtolower(trim($h->name)));

            $calendarEvents = Event::where('status', '!=', 'cancelled')
                ->whereBetween('date', [$rangeStart, $rangeEnd])
                ->orderBy('date')
                ->get();

            $calendarAnnouncements = Announcement::with('author')
                ->published()
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get();

            $hcalEventsMap = [];
            foreach ($calendarHolidays as $hol) {
                $dateKey = $hol->date->format('Y-m-d');
                $hcalEventsMap[$dateKey][] = [
                    'id'    => $hol->id,
                    'type'  => $hol->type,
                    'name'  => $hol->name,
                    'description' => $hol->description,
                    'date'  => $dateKey,
                    'date_formatted' => $hol->date->format('M j, Y'),
                    'type_label' => $hol->type_label,
                    'source' => 'holiday',
                ];
            }

            foreach ($calendarEvents as $evt) {
                $dateKey = $evt->date->format('Y-m-d');
                $hcalEventsMap[$dateKey][] = [
                    'id'    => $evt->id,
                    'type'  => $evt->type,
                    'name'  => $evt->name,
                    'description' => $evt->description,
                    'date'  => $dateKey,
                    'date_formatted' => $evt->date->format('M j, Y'),
                    'type_label' => ucfirst(str_replace('_', ' ', $evt->type)),
                    'source' => 'event',
                    'location' => $evt->location,
                ];
            }

            foreach ($calendarAnnouncements as $ann) {
                $dateKey = $ann->scheduled_for ? $ann->scheduled_for->format('Y-m-d') : $ann->created_at->format('Y-m-d');
                $hcalEventsMap[$dateKey][] = [
                    'id'    => $ann->id,
                    'type'  => 'announcement',
                    'name'  => $ann->title,
                    'description' => \Illuminate\Support\Str::limit($ann->content, 150),
                    'date'  => $dateKey,
                    'date_formatted' => Carbon::parse($dateKey)->format('M j, Y'),
                    'type_label' => 'Announcement',
                    'source' => 'announcement',
                    'author' => $ann->author->name ?? 'Admin',
                    'author_role' => $ann->author->role ?? 'admin',
                ];
            }

            // Build Upcoming Events list (>= today, deduplicated, sorted ascending)
            $hcalUpcoming = collect();

            // Upcoming Holidays
            $upcomingHolidays = Holiday::active()
                ->whereDate('date', '>=', $todayStr)
                ->orderBy('date')
                ->take(15)
                ->get();

            foreach ($upcomingHolidays as $hol) {
                $hcalUpcoming->push((object)[
                    'id' => $hol->id,
                    'type' => $hol->type,
                    'name' => $hol->name,
                    'description' => $hol->description,
                    'date' => $hol->date,
                    'date_formatted' => $hol->date->format('M j, Y'),
                    'type_label' => $hol->type_label,
                    'source' => 'holiday',
                ]);
            }

            // Upcoming Events
            $upcomingSchoolEvents = Event::where('status', '!=', 'cancelled')
                ->whereDate('date', '>=', $todayStr)
                ->orderBy('date')
                ->take(15)
                ->get();

            foreach ($upcomingSchoolEvents as $evt) {
                $hcalUpcoming->push((object)[
                    'id' => $evt->id,
                    'type' => $evt->type,
                    'name' => $evt->name,
                    'description' => $evt->description,
                    'date' => $evt->date,
                    'date_formatted' => $evt->date->format('M j, Y'),
                    'type_label' => ucfirst(str_replace('_', ' ', $evt->type)),
                    'source' => 'event',
                    'location' => $evt->location,
                ]);
            }

            // Recent Announcements
            $upcomingAnnouncements = Announcement::published()
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            foreach ($upcomingAnnouncements as $ann) {
                $annDate = $ann->scheduled_for ?? $ann->created_at;
                $hcalUpcoming->push((object)[
                    'id' => $ann->id,
                    'type' => 'announcement',
                    'name' => $ann->title,
                    'description' => \Illuminate\Support\Str::limit($ann->content, 150),
                    'date' => $annDate,
                    'date_formatted' => $annDate->format('M j, Y'),
                    'type_label' => 'Announcement',
                    'source' => 'announcement',
                ]);
            }

            // Deduplicate and sort by date ascending
            $hcalUpcoming = $hcalUpcoming
                ->unique(fn($item) => ($item->date instanceof \DateTimeInterface ? $item->date->format('Y-m-d') : Carbon::parse($item->date)->format('Y-m-d')) . '_' . strtolower(trim($item->name)))
                ->sortBy('date')
                ->values()
                ->take(10);

            return [
                'calYear' => $calYear,
                'calMonth' => $calMonth,
                'hcalEventsMap' => $hcalEventsMap,
                'hcalUpcoming' => $hcalUpcoming,
            ];
        });
    }

    /**
     * Get all data required for the Teacher Dashboard.
     */
    public function getTeacherDashboardData(Request $request, User $teacher)
    {
        $targetDateStr = $request->input('date', now()->toDateString());
        $targetDate = Carbon::parse($targetDateStr);

        $cacheKey = "teacher_dash_{$teacher->id}_{$targetDateStr}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($teacher, $targetDate) {
            // Get subjects taught by this teacher
            $teacherSubjects = Subject::where('instructor_id', $teacher->id)
                ->with('schedules')
                ->get();

            // Get today's classes for this teacher
            $targetDayName = $targetDate->format('l'); // Day name (Monday, Tuesday, etc.)
            $todayClasses = $teacherSubjects->filter(function($subject) use ($targetDayName) {
                return $subject->schedules->where('day', $targetDayName)->isNotEmpty();
            });

            $attendanceStats = \App\Models\Attendance::select('subject_code', 
                \Illuminate\Support\Facades\DB::raw('count(*) as total'),
                \Illuminate\Support\Facades\DB::raw("sum(case when status in ('Present', 'Late', 'Excused') then 1 else 0 end) as present_count")
            )
                ->whereIn('subject_code', $todayClasses->pluck('code'))
                ->whereDate('date', $targetDate)
                ->groupBy('subject_code')
                ->get()->keyBy('subject_code');

            // Check completion status and class health
            $todayClasses = $todayClasses->map(function($subject) use ($attendanceStats) {
                $stats = $attendanceStats->get($subject->code);
                $total = $stats ? $stats->total : 0;
                $present = $stats ? $stats->present_count : 0;
                
                $subject->has_attendance_today = $total > 0;
                $subject->attendance_count_today = $present;
                $subject->class_health = $total > 0 ? round(($present / $total) * 100) : 0;
                
                return $subject;
            });

            // Get attendance statistics for teacher's subjects only (DB aggregated count)
            $subjectCodes = $teacherSubjects->pluck('code');
            
            $totalStudents = Attendance::whereIn('subject_code', $subjectCodes)
                ->distinct('user_id')
                ->count('user_id');
            
            $todayStats = Attendance::whereIn('subject_code', $subjectCodes)
                ->whereDate('date', $targetDate)
                ->selectRaw("status, count(*) as count")
                ->groupBy('status')
                ->pluck('count', 'status');

            $totalPresent = ($todayStats->get('Present', 0) + $todayStats->get('Late', 0));
            $totalAbsent  = $todayStats->get('Absent', 0);
            $totalLate    = $todayStats->get('Late', 0);

            // Weekly attendance chart data (for teacher's subjects only) — single query
            $weeklyRaw = Attendance::selectRaw("DATE(date) as day, status, COUNT(*) as total")
                ->whereIn('subject_code', $subjectCodes)
                ->whereBetween('date', [$targetDate->copy()->subDays(6)->toDateString(), $targetDate->toDateString()])
                ->groupBy('day', 'status')
                ->get()
                ->groupBy('day');

            $weeklyLabels = [];
            $weeklyPresent = [];
            $weeklyLate = [];
            $weeklyAbsent = [];
            
            for ($i = 6; $i >= 0; $i--) {
                $day = $targetDate->copy()->subDays($i);
                $dayKey = $day->toDateString();
                $weeklyLabels[] = $day->format('D');
                $dayData = $weeklyRaw->get($dayKey, collect());
                $weeklyPresent[] = $dayData->firstWhere('status', 'Present')->total ?? 0;
                $weeklyLate[] = $dayData->firstWhere('status', 'Late')->total ?? 0;
                $weeklyAbsent[] = $dayData->firstWhere('status', 'Absent')->total ?? 0;
            }

            // Recent attendance records (for teacher's subjects only - constrained columns)
            $recentAttendance = Attendance::with([
                    'user:id,name,student_number',
                    'subject:id,code,name'
                ])
                ->whereIn('subject_code', $subjectCodes)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            // Pending excuse submissions count (for dashboard notification)
            $pendingExcuses = \App\Models\ExcuseSubmission::whereHas('attendance', function($q) use ($subjectCodes) {
                $q->whereIn('subject_code', $subjectCodes);
            })->where('status', 'pending')->count();

            // "At-Risk" Students logic (constrained columns)
            $atRiskStudents = collect();
            if ($subjectCodes->isNotEmpty()) {
                $studentStats = Attendance::select('user_id',
                    \Illuminate\Support\Facades\DB::raw('count(*) as total_sessions'),
                    \Illuminate\Support\Facades\DB::raw("sum(case when status in ('Present', 'Late', 'Excused') then 1 else 0 end) as present_sessions")
                )
                ->whereIn('subject_code', $subjectCodes)
                ->groupBy('user_id')
                ->get();

                $atRiskStudents = $studentStats->map(function($stat) {
                    $stat->rate = $stat->total_sessions > 0 ? round(($stat->present_sessions / $stat->total_sessions) * 100) : 100;
                    return $stat;
                })->filter(function($stat) {
                    return $stat->rate < 80;
                })->sortBy('rate')->take(5);

                if ($atRiskStudents->isNotEmpty()) {
                    $atRiskStudents->load('user:id,name,student_number,role,course,year_level,section');
                }
            }

            return compact(
                'targetDate', 'teacherSubjects', 'todayClasses',
                'totalStudents', 'totalPresent', 'totalAbsent', 'totalLate',
                'weeklyLabels', 'weeklyPresent', 'weeklyLate', 'weeklyAbsent',
                'recentAttendance', 'pendingExcuses', 'atRiskStudents'
            );
        });
    }
}
