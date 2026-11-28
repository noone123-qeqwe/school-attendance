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
    $subjects = app(\App\Services\StudentScheduleService::class)->subjectsFor($user);
    $schoolHolidays = Holiday::getUpcoming($todayDate, $now->copy()->addDays(7)->toDateString());
    $todayHoliday = $schoolHolidays->first(fn ($holiday) => $holiday->date->isSameDay($now));
    $todayAttendances = Attendance::where('user_id', $user->id)
        ->whereDate('date', $todayDate)->get()->keyBy('subject_code');

    // Timetable slots and subject/day attendance are separate concepts.
    $todaySchedule = collect();
    if (!$todayHoliday) {
        foreach ($subjects as $subject) {
            $slots = $subject->schedules->where('day', $now->format('l'));
            foreach ($slots as $schedule) {
                if (blank($schedule->start_time) || blank($schedule->end_time)) {
                    continue;
                }
                $start = Carbon::parse($todayDate.' '.$schedule->start_time);
                $end = Carbon::parse($todayDate.' '.$schedule->end_time);
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
                ]);
            }
        }
    }
    $todaySchedule = $todaySchedule->sortBy('start_time')->values();
    $currentSlot = $todaySchedule->firstWhere('schedule_status', 'ongoing');
    $currentClass = $currentSlot?->subject;
    $alreadyClockedIn = $currentSlot && in_array($currentSlot->attendance?->status, ['Present', 'Late'], true);


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

    $recentAttendance = Attendance::with('subject')
        ->where('user_id', $user->id)
        ->whereDate('date', '<=', $todayDate)
        ->orderByDesc('date')
        ->orderByDesc('time_in')
        ->orderByDesc('id')
        ->limit(5)
        ->get();

    $unresolvedAbsencesQuery = Attendance::with('subject')
        ->where('user_id', $user->id)
        ->where('status', 'Absent')
        ->where('excused', false)
        ->whereDate('date', '<=', $todayDate)
        ->whereDoesntHave('excuseSubmission', function ($query) use ($user) {
            $query->where('user_id', $user->id)->whereIn('status', ['pending', 'approved']);
        });
    $unresolvedAbsencesCount = (clone $unresolvedAbsencesQuery)->count();
    $unresolvedAbsences = $unresolvedAbsencesQuery
        ->orderByDesc('date')
        ->orderByDesc('id')
        ->limit(3)
        ->get();

    $recentExcuseRequests = ExcuseSubmission::with('attendance.subject')
        ->where('user_id', $user->id)
        ->whereHas('attendance', function ($query) use ($user, $todayDate) {
            $query->where('user_id', $user->id)
                ->whereDate('date', '<=', $todayDate);
        })
        ->orderByDesc('updated_at')
        ->orderByDesc('id')
        ->limit(3)
        ->get();

    $activeWarnings = \App\Models\Warning::where('user_id', $user->id)
        ->where('created_at', '>=', $now->copy()->subDays(14))
        ->orderBy('created_at', 'desc')
        ->get();

    $announcements = Announcement::with('author')
        ->published()
        ->visibleTo($user)
        ->latest()
        ->take(5)
        ->get();

    return view('home', compact(
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
        'announcements'
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
                'subject' => $record->subject->name ?? $record->subject_name ?? $record->subject_code ?? 'Class',
                'code' => $record->subject_code ?? $record->subject->code ?? '',
                'instructor' => $record->subject->instructorUser->name
                    ?? $record->subject->instructor
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
                    ?? $record->excuseSubmission->reason
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
            $dateStr = $hol->date->format('Y-m-d');

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
            $dateStr = $evt->date->format('Y-m-d');

            $type = match($evt->type) {
                'exam' => 'exam',
                'holiday' => 'holiday',
                default => 'event',
            };

            $startFormatted = $evt->start_time->format('g:i A');
            $endFormatted   = $evt->end_time->format('g:i A');
            $timeStr = "{$startFormatted} – {$endFormatted}";

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
            'hasFaceConsent' => app(\App\Services\FaceAttendanceConsent::class)->approved($request->user()),
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

        $formatted = $notifications->toBase()->map(function ($notif) use ($user) {
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
                'subject_name' => $notif->subject->name ?? $notif->subject_code,
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
    public function calendar(Request $request)
    {
        $year = filter_var($request->query('year'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1900, 'max_range' => 2200]]) ?: now()->year;
        $month = filter_var($request->query('month'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]) ?: now()->month;
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:Present,Late,Absent,Escaped,Excused'],
            'record_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $history = Attendance::where('user_id', Auth::id());
        $counts = (clone $history)->selectRaw('status, excused, COUNT(*) AS record_count')
            ->groupBy('status', 'excused')->get();
        $totals = ['all' => $counts->sum('record_count')];
        foreach ($counts as $count) {
            $status = $count->excused ? 'Excused' : $count->status;
            $totals[$status] = ($totals[$status] ?? 0) + $count->record_count;
        }

        if ($status = $filters['status'] ?? null) {
            if ($status === 'Excused') {
                $history->where(fn ($query) => $query->where('excused', true)
                    ->orWhereRaw('LOWER(status) = ?', ['excused']));
            } else {
                $history->whereRaw('LOWER(status) = ?', [strtolower($status)])->where('excused', false);
            }
        }
        if ($date = $filters['record_date'] ?? null) {
            $history->where('date', $date);
        }
        if (($filters['q'] ?? '') !== '') {
            $search = '%'.$filters['q'].'%';
            $history->where(fn ($query) => $query->whereLike('subject_code', $search)
                ->orWhereLike('subject_name', $search)
                ->orWhereHas('subject', fn ($subject) => $subject->whereLike('name', $search)
                    ->orWhereLike('instructor', $search)
                    ->orWhereHas('instructorUser', fn ($teacher) => $teacher->whereLike('name', $search))));
        }

        $records = $history->with(['subject.schedules', 'subject.instructorUser', 'excuseSubmission', 'correction'])
            ->orderByDesc('date')->orderByDesc('time_in')->orderByDesc('id')
            ->paginate(10)->withQueryString()->fragment('attendance-records');

        $monthStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $monthEnd = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        $initialCalendarEvents = app(\App\Services\CalendarService::class)->getEventsForUser(
            Auth::user(),
            $monthStart,
            $monthEnd
        );

        return view('student.calendar', compact('year', 'month', 'records', 'totals', 'filters', 'initialCalendarEvents'));
    }

    public function calendarData(Request $request, \App\Services\CalendarService $calendarService)
    {
        return response()->json(
            $calendarService->getEventsForUser(
                Auth::user(),
                $request->query('start'),
                $request->query('end')
            )
        )->header('Cache-Control', 'private, no-store');
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
