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

    // Teachers don't have a student dashboard — send them to teacher panel
    if ($user->isTeacher()) {
        return redirect()->route('teacher.dashboard');
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

    // 5. Clock-in Check — only Present/Late counts as clocked in
    $alreadyClockedIn = false;
    if ($currentClass) {
        $existingRecord = Attendance::where('user_id', $user->id)
            ->where('subject_code', $currentClass->code)
            ->where('date', $todayDate)
            ->first();

        $alreadyClockedIn = $existingRecord && in_array($existingRecord->status, ['Present', 'Late']);
    }

    // 6. Subjects
    $subjects = clone $user->getAllSubjects();
    $subjects->load('schedules');

    // 7. Fetch Attendance History
    $records = Attendance::with('subject') 
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
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

            $existing = Attendance::where('user_id', $user->id)
                ->where('subject_code', $subject->code)
                ->where('date', $todayDate)
                ->first();

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


    // 9. Check for holidays
    $todayHoliday = Holiday::getHoliday($now->toDateString());
    $upcomingHolidays = Holiday::getUpcoming(
        $now->copy()->addDay()->toDateString(),
        $now->copy()->addDays(7)->toDateString()
    )->take(3);

    // 10. Fetch Active Warnings
    $activeWarnings = \App\Models\Warning::where('user_id', $user->id)
        ->where('created_at', '>=', $now->copy()->subDays(14))
        ->orderBy('created_at', 'desc')
        ->get();

    // 11. Fetch Announcements for Events Calendar
    $announcements = Announcement::with('author')
        ->published()
        ->orderBy('created_at', 'desc')
        ->take(10)
        ->get();

    // 12. Build events calendar data (announcements + holidays + custom events)
    $calendarEvents = collect();

    // Add announcements as events (upcoming/recent)
    foreach ($announcements as $ann) {
        $eventDate = $ann->scheduled_for ? $ann->scheduled_for->toDateString() : $ann->created_at->toDateString();
        if ($eventDate >= $todayDate) {
            $calendarEvents->push((object) [
                'type'  => 'announcement',
                'title' => $ann->title,
                'content' => \Illuminate\Support\Str::limit($ann->content, 120),
                'date'  => $eventDate,
                'author' => $ann->author->name ?? 'Admin',
                'author_role' => $ann->author->role ?? 'admin',
                'audience' => $ann->target_audience,
                'created_at' => $ann->created_at,
            ]);
        }
    }

    // Add holidays as events (upcoming next 60 days)
    $allHolidays = Holiday::getUpcoming(
        $todayDate,
        $now->copy()->addDays(60)->toDateString()
    );

    foreach ($allHolidays as $hol) {
        $calendarEvents->push((object) [
            'type'  => 'holiday',
            'title' => $hol->name,
            'content' => $hol->description ?? 'No classes',
            'date'  => $hol->date instanceof \DateTimeInterface ? $hol->date->toDateString() : Carbon::parse($hol->date)->toDateString(),
            'author' => null,
            'audience' => 'All',
            'created_at' => $hol->created_at,
        ]);
    }

    // Add school events visible to student
    $studentEvents = Event::visibleTo($user)
        ->where('status', '!=', 'cancelled')
        ->whereDate('date', '>=', $todayDate)
        ->whereDate('date', '<=', $now->copy()->addDays(60)->toDateString())
        ->orderBy('date')
        ->get();

    foreach ($studentEvents as $evt) {
        $calendarEvents->push((object) [
            'type'  => $evt->type,
            'title' => $evt->name,
            'content' => $evt->description ?? $evt->location ?? '',
            'date'  => $evt->date->toDateString(),
            'author' => null,
            'audience' => 'Students',
            'created_at' => $evt->created_at,
        ]);
    }

    // Deduplicate by date + title and sort ascending by date
    $calendarEvents = $calendarEvents
        ->unique(fn($evt) => $evt->date . '_' . strtolower(trim($evt->title)))
        ->sortBy('date')
        ->values();

    // Build events map for calendar dots
    $eventsMap = [];
    foreach ($calendarEvents as $evt) {
        $eventsMap[$evt->date][] = [
            'type'    => $evt->type,
            'title'   => $evt->title,
            'content' => $evt->content,
            'author'  => $evt->author,
        ];
    }

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
        'announcements',
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

        // Fetch all attendance records with subject relation
        $records = Attendance::with('subject')
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->get();

        return view('student.attendance-calendar', compact('records'));
    }

    public function settings()
    {
        return view('settings');
    }

    public function notifications()
    {
        $user = Auth::user();
        
        // Get notifications based on status filter
        $query = Notification::where('user_id', $user->id);
        
        if (request('status') === 'archived') {
            $query->archived();
        } else {
            $query->active();
        }
        
        $notifications = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('notifications', compact('notifications'));
    }

    public function markNotificationsRead()
    {
        \App\Models\Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function deleteNotification(\App\Models\Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->delete();
        return response()->json(['success' => true]);
    }

    public function archiveNotification(\App\Models\Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->archive();
        return response()->json(['success' => true]);
    }

    public function unarchiveNotification(\App\Models\Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->unarchive();
        return response()->json(['success' => true]);
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