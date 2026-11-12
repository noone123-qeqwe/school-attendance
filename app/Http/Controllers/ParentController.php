<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Notification;
use App\Models\Warning;
use App\Models\ExcuseSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\ParentService;
use Exception;
use Illuminate\Support\Facades\Hash;
use App\Models\Subject;
use App\Support\ParentNavigation;

class ParentController extends Controller
{
    protected ParentService $parentService;

    public function __construct(ParentService $parentService)
    {
        $this->parentService = $parentService;
    }

    /**
     * Verify the parent has access to a specific child.
     */
    private function authorizeChild(User $child): void
    {
        $parent = Auth::user();
        if (!$parent->children()->where('student_id', $child->id)->exists()) {
            abort(403, 'You do not have access to this student\'s records.');
        }
        request()->session()->put(ParentNavigation::selectionKey($parent), $child->id);
    }

    /**
     * Let the shared child selector work as a normal GET form as well as with JavaScript.
     * The target is always looked up through the authenticated parent's own relationship.
     */
    private function childContextRedirect(Request $request, User $currentChild, string $destination)
    {
        $requestedChildId = $request->integer('child_id');
        if (!$requestedChildId || $requestedChildId === (int) $currentChild->id) {
            return null;
        }

        $requestedChild = Auth::user()->children()->whereKey($requestedChildId)->first();
        if (!$requestedChild) {
            return null;
        }

        $request->session()->put(ParentNavigation::selectionKey(Auth::user()), $requestedChild->id);

        return redirect()->route($destination, array_merge(
            ['child' => $requestedChild],
            $request->except(['child_id', 'page'])
        ));
    }

    /**
     * Enhanced parent dashboard with stats, charts, and warnings per child.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $children = $user->children()->with([
            'attendances' => function ($q) {
                $q->orderBy('date', 'desc')->take(10);
            },
            'attendances.subject',
        ])
        ->withCount([
            'attendances as total_attendances',
            'attendances as present_count' => fn($q) => $q->whereRaw('LOWER(status) = ?', ['present']),
            'attendances as late_count' => fn($q) => $q->whereRaw('LOWER(status) = ?', ['late']),
            'attendances as absent_count' => fn($q) => $q->whereRaw('LOWER(status) = ?', ['absent']),
        ])->get();

        // Fetch related data in bulk for all children to prevent N+1 queries
        $childIds = $children->pluck('id');

        $allStreakRecords = Attendance::whereIn('user_id', $childIds)
            ->whereDate('date', '>=', now()->subDays(60))
            ->select('user_id', 'date', 'status')
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('user_id');

        $allWarnings = Warning::whereIn('user_id', $childIds)
            ->with('subject')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('user_id');

        $allPendingExcuses = ExcuseSubmission::whereIn('user_id', $childIds)
            ->where('status', 'pending')
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id');

        $allApprovedExcuses = ExcuseSubmission::whereIn('user_id', $childIds)
            ->where('status', 'approved')
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id');

        $now = now('Asia/Manila');
        $todayDate = $now->toDateString();
        $currentDayName = $now->format('l');
        $currentTime = $now->format('H:i:s');

        // Calculate stats per child
        $childrenData = $children->map(function ($child) use ($allStreakRecords, $allWarnings, $allPendingExcuses, $allApprovedExcuses, $now, $todayDate, $currentDayName, $currentTime) {
            $present = (int) $child->present_count;
            $late = (int) $child->late_count;
            $absent = (int) $child->absent_count;

            // ── Historical missed classes calculation ──
            $historicalMisses = app(\App\Actions\Attendance\CalculateMissedAttendanceAction::class)->execute($child);
            $absent += $historicalMisses;
            $total = $present + $late + $absent;
            $rate = $total > 0 ? round((($present + $late) / $total) * 100) : 0;

            // Attendance streak
            $streakCount = 0;
            $streakRecords = collect($allStreakRecords->get($child->id, []))
                ->take(60)
                ->groupBy(fn($r) => \Carbon\Carbon::parse($r->date)->toDateString());

            foreach ($streakRecords as $dayRecords) {
                $allOnTime = $dayRecords->every(fn($r) => in_array(strtolower($r->status), ['present', 'late']));
                if ($allOnTime) {
                    $streakCount++;
                } else {
                    break;
                }
            }

            // Active warnings
            $warnings = collect($allWarnings->get($child->id, []))->take(5);

            // Pending excuses
            $pendingExcuses = $allPendingExcuses->get($child->id, 0);

            // Approved (excused) absences
            $excused = $allApprovedExcuses->get($child->id, 0);

            return (object) [
                'child'          => $child,
                'total'          => $total,
                'present'        => $present,
                'late'           => $late,
                'absent'         => $absent,
                'excused'        => $excused,
                'rate'           => $rate,
                'streak'         => $streakCount,
                'warnings'       => $warnings,
                'pendingExcuses' => $pendingExcuses,
            ];
        });

        $selectedChild = ParentNavigation::selectChild($children, $request);
        return view('parent.dashboard', compact('user', 'childrenData', 'children', 'selectedChild'));
    }

    /**
     * Show the link child form
     */
    public function linkChildForm()
    {
        $user = Auth::user();
        $linkedChildren = $user->children()->with(['attendances' => function ($q) {
            $q->orderBy('date', 'desc')->take(1);
        }])->get();

        return view('parent.link-child', compact('linkedChildren'));
    }

    /**
     * Send OTP to the student for linking
     */
    public function sendLinkOtp(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string|max:50',
        ]);

        try {
            $this->parentService->initiateLink(Auth::user(), $request->student_number);
            return response()->json(['success' => true, 'message' => 'An OTP has been sent to the student\'s school email.']);
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Parent link failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Verify OTP and link
     */
    public function verifyLinkOtp(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string|max:50',
            'otp' => 'required|string|size:6',
        ]);

        try {
            $student = $this->parentService->verifyAndLink(Auth::user(), $request->student_number, $request->otp);
            return response()->json([
                'success' => true,
                'message' => "Successfully linked to {$student->name}!",
                'student' => [
                    'id'             => $student->id,
                    'name'           => $student->name,
                    'student_number' => $student->student_number,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Verify instant 6-digit student link code and link child
     */
    public function linkWithCode(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string|max:50',
            'code' => 'required|string|size:6',
        ]);

        try {
            $student = $this->parentService->verifyAndLinkWithStudentCode(
                Auth::user(), $request->student_number, $request->code
            );
            return response()->json([
                'success' => true,
                'message' => "Successfully linked to {$student->name}!",
                'student' => [
                    'id'             => $student->id,
                    'name'           => $student->name,
                    'student_number' => $student->student_number,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Unlink a student from this parent account
     */
    public function unlinkChild(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        $child = User::findOrFail($request->student_id);

        try {
            $this->parentService->unlink(Auth::user(), Auth::user(), $child);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully disconnected {$child->name}.",
                ]);
            }
            return back()->with('success', "Successfully disconnected {$child->name}.");
        } catch (Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Full attendance detail for a specific child.
     */
    public function childDetail(Request $request, User $child)
    {
        $this->authorizeChild($child);
        $requestedChild = $request->integer('child_id');
        if ($requestedChild && $requestedChild !== (int) $child->id) {
            $child = Auth::user()->children()->whereKey($requestedChild)->first() ?? $child;
        }

        return redirect()->to(route('parent.calendar', [
            'child_id' => $child->id,
            ...$request->only(['year', 'month', 'subject', 'status', 'date_from', 'date_to', 'page']),
        ]).'#attendance-records');
    }

    private function calendarRecords(Request $request, User $child): array
    {
        $filters = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:Present,Late,Absent,Escaped,Excused'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $query = Attendance::with('subject')
            ->where('user_id', $child->id)
            ->orderByDesc('date')->orderByDesc('time_in')->orderByDesc('id');

        if (isset($filters['subject'])) {
            $query->where('subject_code', $filters['subject']);
        }
        if ($status = $filters['status'] ?? null) {
            if ($status === 'Excused') {
                $query->where(fn ($query) => $query->where('excused', true)
                    ->orWhereRaw('LOWER(status) = ?', ['excused']));
            } else {
                $query->whereRaw('LOWER(status) = ?', [strtolower($status)])->where('excused', false);
            }
        }
        if (isset($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        $records = $query->paginate(20)->withQueryString()->appends(['child_id' => $child->id])
            ->fragment('attendance-records');

        // Stats
        $stats = Attendance::where('user_id', $child->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = \'Present\' THEN 1 ELSE 0 END) as present')
            ->selectRaw('SUM(CASE WHEN status = \'Late\' THEN 1 ELSE 0 END) as late')
            ->selectRaw('SUM(CASE WHEN status = \'Absent\' THEN 1 ELSE 0 END) as absent')
            ->first();

        $present = $stats->present ?? 0;
        $late = $stats->late ?? 0;
        $absent = $stats->absent ?? 0;

        // Historical missed classes calculation
        $absent += app(\App\Actions\Attendance\CalculateMissedAttendanceAction::class)->execute($child);

        $total = $present + $late + $absent;
        $rate = $total > 0 ? round((($present + $late) / $total) * 100) : 0;

        // Subjects for filter
        $codes = Attendance::where('user_id', $child->id)->whereNotNull('subject_code')
            ->distinct()->orderBy('subject_code')->pluck('subject_code');
        $names = Subject::withTrashed()->whereIn('code', $codes)->pluck('name', 'code');
        $subjects = $codes->map(fn ($code) => (object) ['code' => $code, 'name' => $names->get($code, $code)]);

        return compact('records', 'total', 'present', 'late', 'absent', 'rate', 'subjects', 'filters');
    }

    /**
     * View warnings for a specific child.
     */
    public function childWarnings(Request $request, User $child)
    {
        $this->authorizeChild($child);
        if ($redirect = $this->childContextRedirect($request, $child, 'parent.child.warnings')) {
            return $redirect;
        }

        $warnings = Warning::where('user_id', $child->id)
            ->with('subject')
            ->orderBy('created_at', 'desc')
            ->get();

        $children = Auth::user()->children()->orderBy('name')->get();
        return view('parent.child-warnings', compact('child', 'children', 'warnings'));
    }

    /**
     * Show excuse submission form for a child's attendance record.
     */
    public function submitExcuse(User $child, Attendance $attendance)
    {
        $this->authorizeChild($child);

        // Ensure attendance belongs to the child
        if ($attendance->user_id !== $child->id) {
            abort(403, 'This attendance record does not belong to your child.');
        }

        $attendance->load('subject');

        return view('parent.excuse-form', compact('child', 'attendance'));
    }

    /**
     * Store an excuse submission from a parent.
     */
    public function storeExcuse(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'reason' => 'required|string|max:1000',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $attendance = Attendance::findOrFail($request->attendance_id);
        $parent = Auth::user();

        // Verify parent has access to this student
        if (!$parent->children()->where('student_id', $attendance->user_id)->exists()) {
            abort(403, 'Unauthorized.');
        }

        if (!in_array($attendance->status, ['Absent', 'Late'], true)
            || ($attendance->date && Carbon::parse($attendance->date)->isAfter(today()))) {
            return back()->with('error', 'Excuses can only be submitted for recorded absences or late arrivals.');
        }

        // Check if excuse already exists
        $existingExcuse = ExcuseSubmission::where('attendance_id', $attendance->id)->first();
        if ($existingExcuse) {
            return back()->with('error', 'An excuse has already been submitted for this attendance record.');
        }

        $data = [
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'reason' => $request->reason,
            'status' => 'pending',
        ];

        if ($request->hasFile('document')) {
            $path = app(\App\Services\ExcuseAttachmentStorage::class)->store($request->file('document'));
            $data['attachments'] = [$path];
        }

        ExcuseSubmission::create($data);

        return redirect()->route('parent.excuses')->with('success', 'Excuse submitted successfully. It will be reviewed by the teacher.');
    }

    /**
     * Show general excuse form where parent can select child and attendance record.
     */
    public function createGeneralExcuse()
    {
        $parent = Auth::user();
        // Load children and their un-excused absent/late attendances
        $children = $parent->children()->with(['attendances' => function ($q) {
            $q->whereIn('status', ['Absent', 'Late'])
              ->doesntHave('excuseSubmission')
              ->with('subject')
              ->orderBy('date', 'desc');
        }])->get();

        return view('parent.excuse-form-general', compact('children'));
    }

    /**
     * Store a general excuse submission.
     */
    public function storeGeneralExcuse(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'reason' => 'required|string|max:1000',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $attendance = Attendance::findOrFail($request->attendance_id);
        $parent = Auth::user();

        // Verify parent has access to this student
        if (!$parent->children()->where('student_id', $attendance->user_id)->exists()) {
            abort(403, 'Unauthorized.');
        }

        if (!in_array($attendance->status, ['Absent', 'Late'], true)
            || ($attendance->date && Carbon::parse($attendance->date)->isAfter(today()))) {
            return back()->with('error', 'Excuses can only be submitted for recorded absences or late arrivals.')->withInput();
        }

        // Check if excuse already exists
        $existingExcuse = ExcuseSubmission::where('attendance_id', $attendance->id)->first();
        if ($existingExcuse) {
            return back()->with('error', 'An excuse has already been submitted for this attendance record.')->withInput();
        }

        $data = [
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'reason' => $request->reason,
            'status' => 'pending',
        ];

        if ($request->hasFile('document')) {
            $path = app(\App\Services\ExcuseAttachmentStorage::class)->store($request->file('document'));
            $data['attachments'] = [$path];
        }

        ExcuseSubmission::create($data);

        return redirect()->route('parent.excuses')->with('success', 'Excuse submitted successfully. It will be reviewed by the teacher.');
    }

    /**
     * View all excuse submissions for parent's children.
     */
    public function excuses(Request $request)
    {
        $parent = Auth::user();
        $childIds = $parent->children()->pluck('users.id');

        $query = ExcuseSubmission::with(['user', 'attendance.subject', 'reviewer'])
            ->whereIn('user_id', $childIds)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('child_id')) {
            $query->where('user_id', $request->child_id);
        }

        $excuses = $query->paginate(20)->withQueryString();
        $children = $parent->children()->get();

        return view('parent.excuses', compact('excuses', 'children'));
    }

    public function showExcuse(ExcuseSubmission $excuseSubmission)
    {
        $parent = Auth::user();
        $childIds = $parent->children()->pluck('users.id');

        if (!$childIds->contains($excuseSubmission->user_id)) {
            abort(403, 'Unauthorized access to this excuse submission.');
        }

        $excuseSubmission->load(['user', 'attendance.subject', 'reviewer', 'comments.user']);

        return view('parent.excuse-show', compact('excuseSubmission'));
    }

    public function storeExcuseComment(Request $request, ExcuseSubmission $excuseSubmission)
    {
        $parent = Auth::user();
        $childIds = $parent->children()->pluck('users.id');

        if (!$childIds->contains($excuseSubmission->user_id)) {
            abort(403, 'Unauthorized access to this excuse submission.');
        }

        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $excuseSubmission->comments()->create([
            'user_id' => $parent->id,
            'body' => $request->body,
        ]);

        return back()->with('success', 'Comment added.');
    }

    /**
     * View notifications addressed to the parent or their linked children.
     */
    public function notifications(Request $request)
    {
        $parent = Auth::user();
        $childIds = $parent->children()->pluck('users.id');
        $recipientIds = $childIds->all();
        $recipientIds[] = $parent->id;

        $query = Notification::with(['user', 'relatedStudent', 'sender', 'subject', 'parentReads' => fn ($q) => $q->where('parent_id', $parent->id)])
            ->whereIn('user_id', $recipientIds)
            ->orderBy('created_at', 'desc');

        if ($request->filled('child_id')) {
            $childId = (int) $request->input('child_id');
            if (!$childIds->contains($childId)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($childId, $parent) {
                    $q->where('user_id', $childId)
                        ->orWhere(function ($parentAlert) use ($childId, $parent) {
                            $parentAlert->where('user_id', $parent->id)
                                ->where('related_user_id', $childId);
                        });
                });
            }
        }
        if ($request->query('type') === 'absence') {
            $query->whereIn('type', ['absence', 'absence_alert']);
        } elseif ($request->query('type') === 'warning') {
            $query->where('type', 'like', 'warning%');
        } elseif ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate(20)->withQueryString();
        $notifications->getCollection()->each(function (Notification $notification) use ($parent) {
            if ((int) $notification->user_id !== (int) $parent->id) {
                $notification->setAttribute('is_read', $notification->parentReads->isNotEmpty());
            }
        });
        $children = $parent->children()->get();

        return view('parent.notifications', compact('notifications', 'children'));
    }

    /**
     * Mark notifications for the parent and their linked children as read.
     */
    public function markNotificationsRead()
    {
        $parent = Auth::user();
        Notification::where('user_id', $parent->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $childNotificationIds = Notification::whereIn('user_id', $parent->children()->pluck('users.id'))
            ->where('is_read', false)
            ->pluck('id');
        if ($childNotificationIds->isNotEmpty()) {
            $now = now();
            \App\Models\ParentNotificationRead::upsert(
                $childNotificationIds->map(fn ($id) => ['parent_id' => $parent->id, 'notification_id' => $id, 'read_at' => $now])->all(),
                ['parent_id', 'notification_id'],
                ['read_at'],
            );
        }

        return response()->json(['success' => true]);
    }

    /**
     * Download attendance report PDF for a child
     */
    public function downloadReport(User $child)
    {
        $this->authorizeChild($child);

        $attendances = Attendance::with('subject')
            ->where('user_id', $child->id)
            ->orderBy('date', 'desc')
            ->get();
            
        $warnings = Warning::with('subject')
            ->where('user_id', $child->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $present = $attendances->where('status', 'Present')->count();
        $late = $attendances->where('status', 'Late')->count();
        $absent = $attendances->where('status', 'Absent')->count();

        // Historical missed classes calculation
        $absent += app(\App\Actions\Attendance\CalculateMissedAttendanceAction::class)->execute($child);

        $total = $present + $late + $absent;
        $rate = $total > 0 ? round((($present + $late) / $total) * 100) : 0;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('parent.report-pdf', compact(
            'child', 'attendances', 'warnings', 'total', 'present', 'late', 'absent', 'rate'
        ));

        return $pdf->download('Attendance_Report_' . $child->student_number . '.pdf');
    }

    public function calendar(Request $request)
    {
        $parent = Auth::user();
        $children = $parent->children()->get();
        $selectedChild = ParentNavigation::selectChild($children, $request);
        $year = filter_var($request->query('year'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1900, 'max_range' => 2200]]) ?: now()->year;
        $month = filter_var($request->query('month'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]) ?: now()->month;
        $attendance = $selectedChild ? $this->calendarRecords($request, $selectedChild) : [];

        return view('parent.calendar', compact('children', 'selectedChild', 'year', 'month') + $attendance);
    }

    public function data(Request $request, \App\Services\CalendarService $calendarService)
    {
        $childId = $request->validate(['child_id' => ['nullable', 'integer', 'min:1']])['child_id'] ?? null;
        return response()->json(
            $calendarService->getEventsForUser(
                Auth::user(),
                $request->query('start'),
                $request->query('end'),
                $childId !== null ? (int) $childId : null
            )
        )->header('Cache-Control', 'private, no-store');
    }

    public function schedule(Request $request)
    {
        $parent = Auth::user();
        $children = $parent->children()->get();
        
        if ($children->isEmpty()) {
            return view('parent.schedule', ['children' => $children, 'selectedChild' => null, 'weeklySchedule' => [], 'days' => []]);
        }

        $selectedChild = ParentNavigation::selectChild($children, $request);

        $subjects = app(\App\Services\StudentScheduleService::class)->subjectsFor($selectedChild);

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        $weeklySchedule = [];
        foreach ($days as $day) {
            $weeklySchedule[$day] = collect();
        }

        foreach ($subjects as $subject) {
            foreach ($subject->schedules as $schedule) {
                if (in_array($schedule->day, $days)) {
                    $schedule->subject = $subject; // Attach subject to schedule for easy access
                    $weeklySchedule[$schedule->day]->push($schedule);
                }
            }
        }

        // Sort schedules by start_time for each day
        foreach ($days as $day) {
            $weeklySchedule[$day] = $weeklySchedule[$day]->sortBy('start_time')->values();
        }

        return view('parent.schedule', compact('children', 'selectedChild', 'weeklySchedule', 'days'));
    }

    public function attendanceCalendar(Request $request)
    {
        $parent = Auth::user();
        $children = $parent->children()->get();
        $selectedChild = ParentNavigation::selectChild($children, $request);
        // The guardian calendar already merges school dates with the selected
        // child's attendance. Preserve the old URL while directing to that view.
        return redirect()->route('parent.calendar', $selectedChild ? ['child_id' => $selectedChild->id] : []);
    }

    public function profile()
    {
        $user = Auth::user();
        return view('parent.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif,heic,heif|max:10240',
        ];

        if ($request->filled('current_password') || $request->filled('password')) {
            $rules['current_password'] = 'required|current_password';
            $rules['password'] = ['required', strong_password_rule(), 'confirmed'];
        }

        $request->validate($rules, [
            'profile_image.image' => 'The selected file must be a valid image.',
            'profile_image.mimes' => 'Allowed formats: JPG, JPEG, PNG, WEBP, GIF, HEIC.',
            'profile_image.max' => 'The profile image size must not exceed 10 MB.',
        ]);

        $user->name = $request->name;
        $user->phone = $request->phone;

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image && !str_starts_with($user->profile_image, 'http')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_image);
            }
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $user->profile_image = $path;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
            $user->must_change_password = false;
            $user->tokens()->delete();
        }

        // Update notification preferences
        $preferences = $user->notification_preferences ?? [];
        $preferences['email_notifications'] = $request->has('email_notifications');
        $preferences['push_notifications'] = $request->has('push_notifications');
        $preferences['email'] = $request->has('email_notifications');
        $preferences['in_app'] = $request->has('push_notifications');
        $user->notification_preferences = $preferences;

        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }
}
