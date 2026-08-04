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


class HomeController extends Controller
{
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
    $subjects = Subject::with('schedules')
        ->where('year_level', $user->year_level)
        ->where('semester', $user->semester)
        ->get();

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

    $totalRecords = array_sum($stats);
    $presentRecords = $totalPresent + $totalLate;

    $attendanceRate = $totalRecords > 0
        ? round(($presentRecords / $totalRecords) * 100)
        : 0;

    // 8b. Detailed stats for dashboard donut chart
    // (Already captured above)

    // 8c. Attendance streak (consecutive days present/late, not absent)
    $streakCount = 0;
    $streakRecords = Attendance::where('user_id', $user->id)
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


    // 9. Check for holidays
    $todayHoliday = Holiday::getHoliday($now->toDateString());
    $upcomingHolidays = Holiday::active()
        ->where('date', '>', $now->toDateString())
        ->where('date', '<=', $now->copy()->addDays(7)->toDateString())
        ->orderBy('date')
        ->take(3)
        ->get();

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

    // 12. Build events calendar data (announcements + holidays)
    $calendarEvents = collect();

    // Add announcements as events
    foreach ($announcements as $ann) {
        $eventDate = $ann->scheduled_for ? $ann->scheduled_for->toDateString() : $ann->created_at->toDateString();
        $calendarEvents->push((object) [
            'type'  => 'announcement',
            'title' => $ann->title,
            'content' => \Illuminate\Support\Str::limit($ann->content, 120),
            'date'  => $eventDate,
            'author' => $ann->author->name ?? 'Admin',
            'audience' => $ann->target_audience,
            'created_at' => $ann->created_at,
        ]);
    }

    // Add holidays as events
    $allHolidays = Holiday::active()
        ->where('date', '>=', $now->copy()->subDays(30)->toDateString())
        ->where('date', '<=', $now->copy()->addDays(60)->toDateString())
        ->orderBy('date')
        ->get();

    foreach ($allHolidays as $hol) {
        $calendarEvents->push((object) [
            'type'  => 'holiday',
            'title' => $hol->name,
            'content' => $hol->description ?? 'No classes',
            'date'  => $hol->date->toDateString(),
            'author' => null,
            'audience' => 'All',
            'created_at' => $hol->created_at,
        ]);
    }

    $calendarEvents = $calendarEvents->sortByDesc('date')->values();

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
        'eventsMap'
    ));
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

    // ─────────────────────────────────────────
    // EXCUSE SUBMISSIONS
    // ─────────────────────────────────────────
    public function excuses()
    {
        $user = Auth::user();
        
        // Get absent attendance records that can be excused
        $absentRecords = Attendance::with(['subject', 'excuseSubmission'])
            ->where('user_id', $user->id)
            ->where('status', 'Absent')
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

        return view('excuses.create', compact('attendance'));
    }

    public function storeExcuse(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'reason' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120', // 5MB max
        ]);

        $attendance = Attendance::findOrFail($request->attendance_id);
        
        // Verify ownership and status
        if ($attendance->user_id !== Auth::id() || $attendance->status !== 'Absent') {
            abort(403);
        }

        // Check if excuse already submitted
        if ($attendance->excuseSubmission) {
            return redirect()->route('excuses')->with('error', 'Excuse already submitted for this absence.');
        }

        $attachmentPaths = [];
        
        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('excuse_attachments', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $excuseSubmission = ExcuseSubmission::create([
            'user_id' => Auth::id(),
            'attendance_id' => $attendance->id,
            'reason' => $request->reason,
            'description' => $request->description,
            'attachments' => $attachmentPaths,
            'status' => 'pending'
        ]);

        // Find the teacher for this subject and broadcast the event
        $subject = \App\Models\Subject::where('code', $attendance->subject_code)->first();
        if ($subject && $subject->instructor_id) {
            // Create notification for teacher
            \App\Models\Notification::create([
                'user_id' => $subject->instructor_id,
                'message' => "New excuse letter submitted by " . Auth::user()->name . " for " . $attendance->subject_code . " (" . \Carbon\Carbon::parse($attendance->date)->format('M j, Y') . ")",
                'type' => 'custom',
                'is_read' => false
            ]);

            event(new \App\Events\ExcuseSubmitted(
                $excuseSubmission,
                Auth::user()->name,
                $attendance->subject_code,
                $subject->instructor_id
            ));
        }

        return redirect()->route('excuses')->with('success', 'Excuse submitted successfully! It will be reviewed by the teacher.');
    }
}