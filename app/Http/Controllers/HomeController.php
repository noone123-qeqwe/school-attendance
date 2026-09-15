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
            'password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

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

    // Teachers / Dept Heads don't have a student dashboard — send them to teacher panel
    if ($user->isTeacher() || $user->isDepartmentHead()) {
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

    // 2. Today's subjects (auto-absent marking is handled by scheduled command: attendance:mark-absent)
    $todaySubjects = collect();

    if ($currentDayLetter) {
        $todaySubjects = Subject::where('year_level', $user->year_level)
            ->where('semester', $user->semester)
            ->where(function ($q) use ($user) {
                $q->whereNull('course')->orWhere('course', '')->orWhere('course', $user->course);
            })
            ->where(function ($q) use ($user) {
                $q->whereNull('section')->orWhere('section', '')->orWhere('section', $user->section);
            })
            ->whereHas('schedules', function ($query) use ($currentDayName) {
                $query->where('day', $currentDayName);
            })
            ->with(['schedules' => function ($query) use ($currentDayName) {
                $query->where('day', $currentDayName);
            }])
            ->get();
    }

    // 3. Fetch Active Class for "Today's Clock In"
    $currentClass = null;
    if ($currentDayLetter) {
        $currentClass = Subject::where('year_level', $user->year_level)
            ->where('semester', $user->semester)
            ->where(function ($q) use ($user) {
                $q->whereNull('course')->orWhere('course', '')->orWhere('course', $user->course);
            })
            ->where(function ($q) use ($user) {
                $q->whereNull('section')->orWhere('section', '')->orWhere('section', $user->section);
            })
            ->whereHas('schedules', function ($query) use ($currentTime, $currentDayName) {
                $query->whereTime('start_time', '<=', $currentTime)
                      ->whereTime('end_time', '>=', $currentTime)
                      ->where('day', $currentDayName);
            })
            ->with(['schedules' => function ($query) use ($currentTime, $currentDayName) {
                $query->whereTime('start_time', '<=', $currentTime)
                      ->whereTime('end_time', '>=', $currentTime)
                      ->where('day', $currentDayName);
            }])
            ->first();
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

    // 6. Subjects
    $subjects = clone $user->getAllSubjects();
    $subjects->load('schedules');

    // 7. Fetch Attendance History
    $records = Attendance::with(['subject.schedules', 'subject.instructorUser', 'excuseSubmission', 'correction']) 
        ->where('user_id', $user->id)
        ->orderBy('date', 'desc')
        ->orderBy('time_in', 'desc')
        ->get();

    // 8. Stats Calculation — Option B (overall rate)
    $weeklyClasses = Attendance::where('user_id', $user->id)
        ->whereBetween('date', [
            Carbon::now()->startOfWeek()->toDateString(),
            Carbon::now()->endOfWeek()->toDateString()
        ])->count();

    $stats = Attendance::where('user_id', $user->id)
        ->selectRaw('status, count(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();

    $totalPresent = $stats['Present'] ?? 0;
    $totalLate    = $stats['Late'] ?? 0;
    $totalAbsent  = $stats['Absent'] ?? 0;

    // Determine dynamically missed classes today that have no database record yet
    $dynamicMissesTotal = 0;
    
    // First, build today's schedule if it isn't built yet
    $todaySchedule = collect();
    if ($todaySubjects->isNotEmpty()) {
        foreach ($todaySubjects as $subject) {
            $sched = $subject->schedules->first();
            if (!$sched) continue;

            $classStart = Carbon::parse($todayDate . ' ' . $sched->start_time);
            $classEnd   = Carbon::parse($todayDate . ' ' . $sched->end_time);

            $existing = $todayAttendances->get($subject->code);

            $status = 'upcoming';
            if ($existing && in_array($existing->status, ['Present', 'Late'])) {
                $status = 'completed';
            } elseif ($now->greaterThan($classEnd)) {
                $status = $existing && $existing->status === 'Absent' ? 'missed' : 'missed';
                if (!$existing) {
                    $dynamicMissesTotal++;
                }
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
        : 0; // If they have 0 total classes, attendance is 0%

    // 8b. Detailed stats for dashboard donut chart
    // (Already captured above)

    // 8c. Attendance streak (consecutive days present/late, not absent - optimized query)
    $streakCount = 0;
    $streakRecords = Attendance::where('user_id', $user->id)
        ->whereDate('date', '>=', now()->subDays(60))
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


    // 9. Check for holidays (cached for 30 minutes to eliminate WAN roundtrips)
    $todayHoliday = \Illuminate\Support\Facades\Cache::remember("holiday_{$todayDate}", 1800, function() use ($now) {
        return Holiday::getHoliday($now->toDateString());
    });
    $upcomingHolidays = \Illuminate\Support\Facades\Cache::remember("upcoming_holidays_{$todayDate}", 1800, function() use ($now) {
        return Holiday::getUpcoming(
            $now->copy()->addDay()->toDateString(),
            $now->copy()->addDays(7)->toDateString()
        )->take(3);
    });

    // 10. Fetch Active Warnings
    $activeWarnings = \App\Models\Warning::where('user_id', $user->id)
        ->where('created_at', '>=', $now->copy()->subDays(14))
        ->orderBy('created_at', 'desc')
        ->get();

    // 11. Build events calendar data (holidays + custom events + exams)
    [$calendarEvents, $eventsMap] = $this->getStudentCalendarEventsMap($user);


    // 13. Per-Subject Attendance Breakdown
    $subjectStats = collect();
    $grouped = $records->groupBy('subject_code');
    
    foreach ($subjects as $subjectModel) {
        $subjectRecords = $grouped->get($subjectModel->code, collect());
        
        $present = $subjectRecords->where('status', 'Present')->count();
        $late = $subjectRecords->where('status', 'Late')->count();
        $absent = $subjectRecords->where('status', 'Absent')->count();
        $excused = $subjectRecords->where('excused', true)->count();
        
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
        'records',
        'attendanceRate',
        'weeklyClasses',
        'todaySubjects',
        'todayHoliday',
        'upcomingHolidays',
        'activeWarnings',
        'totalPresent',
        'totalLate',
        'totalAbsent',
        'streakCount',
        'todaySchedule',
        'calendarEvents',
        'eventsMap',
        'subjectStats'
    ));
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

        // Fetch all attendance records with subject, schedules, instructor, and excuse relation
        $records = Attendance::with(['subject.schedules', 'subject.instructorUser', 'excuseSubmission', 'correction'])
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->orderBy('time_in', 'desc')
            ->get();

        [$calendarEvents, $eventsMap] = $this->getStudentCalendarEventsMap($user);

        return view('student.attendance-calendar', compact('records', 'calendarEvents', 'eventsMap'));
    }

    /**
     * Build unified calendar events, exams, and holidays for a student.
     */
    private function getStudentCalendarEventsMap(User $user, $calYear = null, $calMonth = null): array
    {
        $now = now();
        $year = (int) ($calYear ?? request('cal_year', $now->year));
        $month = (int) ($calMonth ?? request('cal_month', $now->month));

        $viewMonthStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $viewMonthEnd   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $rangeStart = min($viewMonthStart, $now->copy()->subDays(60)->toDateString());
        $rangeEnd   = max($viewMonthEnd, $now->copy()->addDays(90)->toDateString());

        // 1. Fetch active holidays in range
        $holidays = Holiday::active()
            ->whereDate('date', '>=', $rangeStart)
            ->whereDate('date', '<=', $rangeEnd)
            ->orderBy('date')
            ->get();

        // 2. Fetch student-visible events (exams, school events, etc.) with subject relation
        $studentEvents = Event::visibleTo($user)
            ->with('subject')
            ->where('status', '!=', 'cancelled')
            ->whereDate('date', '>=', $rangeStart)
            ->whereDate('date', '<=', $rangeEnd)
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
        return view('settings');
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

    public function pollNotifications()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['unread_count' => 0, 'notifications' => []]);
        }

        if ($user->isParent()) {
            $childIds = $user->children()->pluck('users.id');
            $unreadCount = \App\Models\Notification::whereIn('user_id', $childIds)->where('is_read', false)->count();
            $notifications = \App\Models\Notification::with(['user', 'sender', 'subject'])
                ->whereIn('user_id', $childIds)
                ->active()
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get();
        } else {
            $unreadCount = \App\Models\Notification::where('user_id', $user->id)->where('is_read', false)->count();
            $notifications = \App\Models\Notification::with(['sender', 'subject'])
                ->where('user_id', $user->id)
                ->active()
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get();
        }

        $formatted = $notifications->map(function($notif) {
            return [
                'id' => $notif->id,
                'type' => $notif->type,
                'message' => $notif->message,
                'is_read' => (bool)$notif->is_read,
                'created_at_human' => $notif->created_at->diffForHumans(),
                'is_today' => $notif->created_at->isToday(),
                'sender_name' => $notif->sender?->name,
                'subject_name' => $notif->subject?->name ?? $notif->subject_code,
            ];
        });

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $formatted,
        ]);
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
            'date' => 'required|date',
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
                $path = $file->store('excuse_attachments', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $submittedCount = 0;
        foreach ($selectedCodes as $code) {
            $subModel = \App\Models\Subject::where('code', $code)->first();
            // Get or create the attendance record
            $attendance = Attendance::firstOrCreate([
                'user_id' => $user->id,
                'subject_code' => $code,
                'date' => $request->date,
            ], [
                'subject_id' => $subModel?->id,
                'status' => 'Absent',
                'time_in' => null,
                'excused' => false
            ]);

            // If it already has an excuse, skip
            if ($attendance->excuseSubmission) {
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
            return redirect()->route('excuses')->with('error', 'Excuse letters have already been submitted for all selected subjects on this date.');
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
            'date' => 'nullable|date',
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
                ->whereDoesntHave('excuseSubmission')
                ->get();
            $targetAttendances = $targetAttendances->merge($records);
        }

        // 2. If single attendance_id provided
        if ($request->filled('attendance_id')) {
            $single = Attendance::where('id', $request->attendance_id)
                ->where('user_id', $user->id)
                ->where('status', 'Absent')
                ->whereDoesntHave('excuseSubmission')
                ->first();
            if ($single) {
                $targetAttendances->push($single);
            }
        }

        // 3. If additional subject_codes provided for a date
        if ($request->has('subject_codes') && is_array($request->subject_codes)) {
            $refDate = $request->input('date') ?? ($targetAttendances->first() ? $targetAttendances->first()->date : now()->toDateString());
            $enrolledCodes = $user->getAllSubjects()->pluck('code')->toArray();
            
            foreach ($request->subject_codes as $code) {
                if (!in_array($code, $enrolledCodes)) continue;
                $subModel = \App\Models\Subject::where('code', $code)->first();
                
                $att = Attendance::firstOrCreate([
                    'user_id' => $user->id,
                    'subject_code' => $code,
                    'date' => $refDate,
                ], [
                    'subject_id' => $subModel?->id,
                    'status' => 'Absent',
                    'time_in' => null,
                    'excused' => false
                ]);

                if (!$att->excuseSubmission) {
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
                $path = $file->store('excuse_attachments', 'public');
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