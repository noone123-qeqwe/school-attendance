<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Subject;
use App\Models\Holiday;
use App\Models\Event;
use App\Models\Announcement;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsService
{
    /**
     * Get all data required for the Admin Dashboard.
     */
    public function getAdminDashboardData(Request $request)
    {
        // ── Core counts ──
        $coreCounts = \Illuminate\Support\Facades\Cache::remember('admin_core_counts', 300, function () {
            return [
                'students' => User::where('role', 'student')->count(),
                'teachers' => User::where('role', 'teacher')->count(),
                'subjects' => Subject::count(),
                'parents'  => User::where('role', 'parent')->count(),
                'departments' => \App\Models\Department::count(),
                'courses'  => \App\Models\Course::count(),
                'sections' => \App\Models\Section::count(),
            ];
        });

        $totalStudents = $coreCounts['students'];
        $totalTeachers = $coreCounts['teachers'];
        $totalSubjects = $coreCounts['subjects'];
        $totalParents  = $coreCounts['parents'];
        $totalDepartments = $coreCounts['departments'];
        $totalCourses  = $coreCounts['courses'];
        $totalSections = $coreCounts['sections'];

        // ── Today's stats ──
        $todayStats = Attendance::selectRaw("status, COUNT(*) as total")
            ->whereDate('date', today())
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalPresent  = $todayStats->get('Present', 0);
        $totalLate     = $todayStats->get('Late', 0);
        $totalAbsent   = $todayStats->get('Absent', 0);
        $totalToday    = $totalPresent + $totalLate + $totalAbsent;
        $attendanceRate = $totalToday > 0 ? round((($totalPresent + $totalLate) / $totalToday) * 100) : 0;

        // ── Yesterday's stats (for trend comparison) ──
        $yesterdayStats = Attendance::selectRaw("status, COUNT(*) as total")
            ->whereDate('date', today()->subDay())
            ->groupBy('status')
            ->pluck('total', 'status');

        $yesterdayPresent = $yesterdayStats->get('Present', 0);
        $yesterdayLate    = $yesterdayStats->get('Late', 0);
        $yesterdayAbsent  = $yesterdayStats->get('Absent', 0);
        $yesterdayTotal   = $yesterdayPresent + $yesterdayLate + $yesterdayAbsent;
        $yesterdayRate    = $yesterdayTotal > 0 ? round((($yesterdayPresent + $yesterdayLate) / $yesterdayTotal) * 100) : 0;

        // ── Active attendance sessions ──
        $activeSessions = \App\Models\AttendanceSession::where('active', true)
            ->with(['creator', 'subject.schedules'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($session) {
                $session->markInactiveIfExpired();
                $checkedIn = Attendance::where('subject_code', $session->subject_code)
                    ->whereDate('date', today())
                    ->count();
                $session->checked_in_count = $checkedIn;
                $session->qr_status = $session->isTokenValid() ? 'Active' : 'Expired';
                $session->session_status = $session->isSessionActive() ? 'Active' : ($session->active ? 'Waiting' : 'Finished');
                return $session;
            })->filter(fn($s) => $s->active);

        $activeSessionCount = $activeSessions->count();

        // ── Classes completed / pending today ──
        $todayDayName = now()->format('l');
        $scheduledToday = \App\Models\Schedule::where('day', $todayDayName)->count();
        $sessionsToday = \App\Models\AttendanceSession::whereDate('created_at', today())->count();
        $classesCompleted = min($sessionsToday, $scheduledToday);
        $classesPending = max(0, $scheduledToday - $classesCompleted);

        // ── Weekly chart data (last 7 days) ──
        $weeklyRaw = Attendance::selectRaw("DATE(date) as day, status, COUNT(*) as total")
            ->whereBetween('date', [Carbon::today()->subDays(6)->toDateString(), Carbon::today()->toDateString()])
            ->groupBy('day', 'status')
            ->get()
            ->groupBy('day');

        $weeklyLabels  = [];
        $weeklyPresent = [];
        $weeklyLate    = [];
        $weeklyAbsent  = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dayKey = $day->toDateString();
            $weeklyLabels[]  = $day->format('D');
            $dayData = $weeklyRaw->get($dayKey, collect());
            $weeklyPresent[] = $dayData->firstWhere('status', 'Present')->total ?? 0;
            $weeklyLate[]    = $dayData->firstWhere('status', 'Late')->total ?? 0;
            $weeklyAbsent[]  = $dayData->firstWhere('status', 'Absent')->total ?? 0;
        }

        // ── Teacher activity monitor ──
        $teachers = User::where('role', 'teacher')->orderBy('name')->get();
        $teacherIds = $teachers->pluck('id');
        $todaySessions = \App\Models\AttendanceSession::whereIn('created_by', $teacherIds)
            ->whereDate('created_at', today())
            ->with('subject')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('created_by');

        $teacherActivity = $teachers->map(function ($teacher) use ($todaySessions) {
            $session = $todaySessions->get($teacher->id)?->first();

            $teacher->current_subject = $session?->subject?->name ?? '—';
            $teacher->current_subject_code = $session?->subject_code ?? null;
            if ($session && $session->active && $session->isSessionActive()) {
                $teacher->attendance_status = 'Attendance Open';
            } elseif ($session && !$session->active) {
                $teacher->attendance_status = 'Attendance Closed';
            } else {
                $teacher->attendance_status = 'No Session';
            }
            $teacher->last_activity = $session?->updated_at?->diffForHumans() ?? '—';
            $teacher->session_started = $session?->created_at?->format('h:i A') ?? '—';
            return $teacher;
        });

        // ── At-risk students ──
        $studentStats = Attendance::select('user_id',
            \Illuminate\Support\Facades\DB::raw('count(*) as total_sessions'),
            \Illuminate\Support\Facades\DB::raw('sum(case when status in ("Present", "Late", "Excused") then 1 else 0 end) as present_sessions'),
            \Illuminate\Support\Facades\DB::raw('max(date) as last_attendance')
        )
        ->groupBy('user_id')
        ->with('user')
        ->get();

        $atRiskStudents = $studentStats->map(function ($stat) {
            $rate = $stat->total_sessions > 0 ? round(($stat->present_sessions / $stat->total_sessions) * 100) : 100;
            if ($rate < 80 && $stat->user && $stat->user->role === 'student') {
                $student = $stat->user;
                $student->attendance_rate = $rate;
                $student->last_attendance = $stat->last_attendance ? \Carbon\Carbon::parse($stat->last_attendance)->format('M d, Y') : '—';
                $student->risk_level = match(true) {
                    $rate >= 70 => 'Watch',
                    $rate >= 50 => 'Warning',
                    default => 'Critical',
                };
                return $student;
            }
            return null;
        })->filter()->sortBy('attendance_rate')->take(15);

        // ── Class performance ──
        $classPerformance = Attendance::selectRaw("subject_code, status, COUNT(*) as total")
            ->groupBy('subject_code', 'status')
            ->get()
            ->groupBy('subject_code')
            ->map(function ($group, $code) {
                $present = $group->where('status', 'Present')->sum('total') + $group->where('status', 'Late')->sum('total');
                $total = $group->sum('total');
                $absent = $group->where('status', 'Absent')->sum('total');
                $subject = Subject::where('code', $code)->first();
                return (object)[
                    'code' => $code,
                    'name' => $subject?->name ?? $code,
                    'present' => $present,
                    'absent' => $absent,
                    'total' => $total,
                    'rate' => $total > 0 ? round(($present / $total) * 100) : 0,
                ];
            })->values();

        $topClasses = $classPerformance->sortByDesc('rate')->take(5)->values();
        $bottomClasses = $classPerformance->sortBy('rate')->take(5)->values();

        // ── Recent activity feed ──
        $recentActivity = collect();
        try {
            $recentActivity = \Spatie\Activitylog\Models\Activity::with('causer')
                ->latest()
                ->take(15)
                ->get();
        } catch (\Exception $e) {}

        // ── System alerts ──
        $pendingExcuses = \App\Models\ExcuseSubmission::where('status', 'pending')->count();
        $expiredSessions = \App\Models\AttendanceSession::where('active', true)
            ->where('session_ends_at', '<', now())
            ->count();
        $lowAttendanceStudents = $atRiskStudents->where('risk_level', 'Critical')->count();

        $systemAlerts = collect();
        if ($expiredSessions > 0) {
            $systemAlerts->push((object)['severity' => 'critical', 'icon' => 'bi-exclamation-octagon-fill', 'message' => "{$expiredSessions} QR session(s) have expired but are still marked active.", 'action' => null]);
        }
        if ($lowAttendanceStudents > 0) {
            $systemAlerts->push((object)['severity' => 'warning', 'icon' => 'bi-exclamation-triangle-fill', 'message' => "{$lowAttendanceStudents} student(s) have critically low attendance (below 50%).", 'action' => '#at-risk-section']);
        }
        if ($pendingExcuses > 0) {
            $systemAlerts->push((object)['severity' => 'warning', 'icon' => 'bi-file-earmark-text', 'message' => "{$pendingExcuses} excuse submission(s) awaiting review.", 'action' => route('admin.excuses')]);
        }
        if ($totalStudents === 0) {
            $systemAlerts->push((object)['severity' => 'info', 'icon' => 'bi-info-circle-fill', 'message' => "No students registered yet. Add students to get started.", 'action' => route('admin.student.create')]);
        }

        // ── Recently added students ──
        $recentStudents = User::where('role', 'student')
            ->latest()
            ->take(5)
            ->get();

        // ── Holiday Calendar Data ──
        $calYear = (int) $request->get('hcal_year', now()->year);
        $calMonth = (int) $request->get('hcal_month', now()->month);
        $hcalData = $this->getHolidayCalendarData($calYear, $calMonth);
        $hcalEventsMap = $hcalData['hcalEventsMap'];
        $hcalUpcoming = $hcalData['hcalUpcoming'];

        return compact(
            'totalStudents', 'totalTeachers', 'totalSubjects', 'totalParents',
            'totalDepartments', 'totalCourses', 'totalSections',
            'totalPresent', 'totalLate', 'totalAbsent', 'attendanceRate', 'totalToday',
            'yesterdayPresent', 'yesterdayLate', 'yesterdayAbsent', 'yesterdayRate', 'yesterdayTotal',
            'activeSessions', 'activeSessionCount', 'classesCompleted', 'classesPending',
            'weeklyLabels', 'weeklyPresent', 'weeklyLate', 'weeklyAbsent',
            'teacherActivity', 'atRiskStudents',
            'topClasses', 'bottomClasses',
            'recentActivity', 'systemAlerts', 'pendingExcuses',
            'recentStudents',
            'calYear', 'calMonth', 'hcalEventsMap', 'hcalUpcoming'
        );
    }

    /**
     * Get Holiday and Event calendar map and upcoming events for any year/month.
     */
    public function getHolidayCalendarData(int $calYear, int $calMonth): array
    {
        $calStart = Carbon::create($calYear, $calMonth, 1);
        $rangeStart = $calStart->copy()->subMonth()->startOfMonth()->toDateString();
        $rangeEnd = $calStart->copy()->addMonth()->endOfMonth()->toDateString();
        $todayStr = now()->toDateString();

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
    }

    /**
     * Get all data required for the Teacher Dashboard.
     */
    public function getTeacherDashboardData(Request $request, User $teacher)
    {
        $targetDateStr = $request->input('date', now()->toDateString());
        $targetDate = Carbon::parse($targetDateStr);

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
            \Illuminate\Support\Facades\DB::raw('sum(case when status in ("Present", "Late", "Excused") then 1 else 0 end) as present_count')
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

        // Get attendance statistics for teacher's subjects only
        $subjectCodes = $teacherSubjects->pluck('code');
        
        // Get students who have attendance in teacher's subjects
        $studentIds = Attendance::whereIn('subject_code', $subjectCodes)
            ->distinct()
            ->pluck('user_id');
        $totalStudents = $studentIds->count();
        
        $todayAttendance = Attendance::whereIn('subject_code', $subjectCodes)
            ->whereDate('date', $targetDate)
            ->get();

        $totalPresent = $todayAttendance->whereIn('status', ['Present', 'Late'])->count();
        $totalAbsent = $todayAttendance->where('status', 'Absent')->count();
        $totalLate = $todayAttendance->where('status', 'Late')->count();

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

        // Recent attendance records (for teacher's subjects only)
        $recentAttendance = Attendance::with(['user', 'subject'])
            ->whereIn('subject_code', $subjectCodes)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Pending excuse submissions count (for dashboard notification)
        $pendingExcuses = \App\Models\ExcuseSubmission::whereHas('attendance', function($q) use ($subjectCodes) {
            $q->whereIn('subject_code', $subjectCodes);
        })->where('status', 'pending')->count();

        // "At-Risk" Students logic
        $atRiskStudents = collect();
        if ($subjectCodes->isNotEmpty()) {
            $studentStats = Attendance::select('user_id',
                \Illuminate\Support\Facades\DB::raw('count(*) as total_sessions'),
                \Illuminate\Support\Facades\DB::raw('sum(case when status in ("Present", "Late", "Excused") then 1 else 0 end) as present_sessions')
            )
            ->whereIn('subject_code', $subjectCodes)
            ->groupBy('user_id')
            ->with('user')
            ->get();

            $atRiskStudents = $studentStats->map(function($stat) {
                $stat->rate = $stat->total_sessions > 0 ? round(($stat->present_sessions / $stat->total_sessions) * 100) : 100;
                return $stat;
            })->filter(function($stat) {
                return $stat->rate < 80;
            })->sortBy('rate')->take(5);
        }

        return compact(
            'targetDate', 'teacherSubjects', 'todayClasses',
            'totalStudents', 'totalPresent', 'totalAbsent', 'totalLate',
            'weeklyLabels', 'weeklyPresent', 'weeklyLate', 'weeklyAbsent',
            'recentAttendance', 'pendingExcuses', 'atRiskStudents'
        );
    }
}
