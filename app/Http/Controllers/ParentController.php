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
    }

    /**
     * Enhanced parent dashboard with stats, charts, and warnings per child.
     */
    public function dashboard()
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
            $total = (int) $child->total_attendances;
            $present = (int) $child->present_count;
            $late = (int) $child->late_count;
            $absent = (int) $child->absent_count;

            // Check if there are scheduled classes today that have ended with no attendance record
            $dynamicMisses = 0;
            if ($child->year_level && $child->semester) {
                $endedSubjectsToday = \App\Models\Subject::where('year_level', $child->year_level)
                    ->where('semester', $child->semester)
                    ->whereHas('schedules', function ($query) use ($currentDayName, $currentTime) {
                        $query->where('day', $currentDayName)->where('end_time', '<', $currentTime);
                    })
                    ->get();

                foreach ($endedSubjectsToday as $subj) {
                    $hasAttendance = Attendance::where('user_id', $child->id)
                        ->where('subject_code', $subj->code)
                        ->whereDate('date', $todayDate)
                        ->exists();
                    if (!$hasAttendance) {
                        $dynamicMisses++;
                    }
                }
            }

            $absent += $dynamicMisses;
            $total += $dynamicMisses;
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

        return view('parent.dashboard', compact('user', 'childrenData'));
    }

    /**
     * Show the link child form
     */
    public function linkChildForm()
    {
        return view('parent.link-child');
    }

    /**
     * Send OTP to the student for linking
     */
    public function sendLinkOtp(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string|size:7',
        ]);

        try {
            $this->parentService->initiateLink(Auth::user(), $request->student_number);
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Parent link failed: " . $e->getMessage());
        }
        return response()->json(['success' => true, 'message' => 'If the student ID is valid, an OTP has been sent to their email.']);
    }

    /**
     * Verify OTP and link
     */
    public function verifyLinkOtp(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string|size:7',
            'otp' => 'required|string|size:6',
        ]);

        try {
            $this->parentService->verifyAndLink(Auth::user(), $request->student_number, $request->otp);
            return response()->json(['success' => true, 'message' => 'Successfully linked to student!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invalid student ID or OTP.'], 400);
        }
    }

    /**
     * Full attendance detail for a specific child.
     */
    public function childDetail(Request $request, User $child)
    {
        $this->authorizeChild($child);

        $query = Attendance::with('subject')
            ->where('user_id', $child->id)
            ->orderBy('date', 'desc');

        if ($request->filled('subject')) {
            $query->where('subject_code', $request->subject);
        }
        if ($request->filled('status')) {
            if ($request->status === 'Excused') {
                $query->where('excused', true);
            } else {
                $query->where('status', $request->status)->where('excused', false);
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $records = $query->paginate(20)->withQueryString();

        // Stats
        $stats = Attendance::where('user_id', $child->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = \'Present\' THEN 1 ELSE 0 END) as present')
            ->selectRaw('SUM(CASE WHEN status = \'Late\' THEN 1 ELSE 0 END) as late')
            ->selectRaw('SUM(CASE WHEN status = \'Absent\' THEN 1 ELSE 0 END) as absent')
            ->first();

        $total = $stats->total ?? 0;
        $present = $stats->present ?? 0;
        $late = $stats->late ?? 0;
        $absent = $stats->absent ?? 0;
        $rate = $total > 0 ? round((($present + $late) / $total) * 100) : 0;

        // Subjects for filter
        $subjects = Attendance::where('user_id', $child->id)
            ->distinct()
            ->pluck('subject_code')
            ->map(function ($code) {
                $subject = \App\Models\Subject::where('code', $code)->first();
                return (object) ['code' => $code, 'name' => $subject->name ?? $code];
            });

        return view('parent.child-detail', compact(
            'child', 'records', 'total', 'present', 'late', 'absent', 'rate', 'subjects'
        ));
    }

    /**
     * View warnings for a specific child.
     */
    public function childWarnings(User $child)
    {
        $this->authorizeChild($child);

        $warnings = Warning::where('user_id', $child->id)
            ->with('subject')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('parent.child-warnings', compact('child', 'warnings'));
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
            $path = $request->file('document')->store('excuse_documents', 'public');
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
            $path = $request->file('document')->store('excuse_documents', 'public');
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
     * View notifications for all children.
     */
    public function notifications(Request $request)
    {
        $parent = Auth::user();
        $childIds = $parent->children()->pluck('users.id');

        $query = Notification::with(['user', 'sender', 'subject'])
            ->whereIn('user_id', $childIds)
            ->orderBy('created_at', 'desc');

        if ($request->filled('child_id')) {
            $query->where('user_id', $request->child_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate(20)->withQueryString();
        $children = $parent->children()->get();

        return view('parent.notifications', compact('notifications', 'children'));
    }

    /**
     * Mark all notifications as read for children.
     */
    public function markNotificationsRead()
    {
        $parent = Auth::user();
        $childIds = $parent->children()->pluck('users.id');

        Notification::whereIn('user_id', $childIds)
            ->where('is_read', false)
            ->update(['is_read' => true]);

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

        $total = $attendances->count();
        $present = $attendances->where('status', 'Present')->count();
        $late = $attendances->where('status', 'Late')->count();
        $absent = $attendances->where('status', 'Absent')->count();
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
        $childId = $request->query('child_id');
        $selectedChild = $childId ? $children->firstWhere('id', $childId) : $children->first();
        if (!$selectedChild && $children->isNotEmpty()) {
            $selectedChild = $children->first();
        }
        return view('parent.calendar', compact('children', 'selectedChild'));
    }

    public function data(Request $request, \App\Services\CalendarService $calendarService)
    {
        $childId = $request->query('child_id');
        return response()->json(
            $calendarService->getEventsForUser(
                Auth::user(),
                $request->query('start'),
                $request->query('end'),
                $childId ? (int) $childId : null
            )
        );
    }

    public function schedule(Request $request)
    {
        $parent = Auth::user();
        $children = $parent->children()->get();
        
        if ($children->isEmpty()) {
            return view('parent.schedule', ['children' => $children, 'selectedChild' => null, 'weeklySchedule' => [], 'days' => []]);
        }

        $childId = $request->query('child_id');
        $selectedChild = $childId ? $children->firstWhere('id', $childId) : $children->first();

        // Fallback to first child if invalid ID
        if (!$selectedChild) {
            $selectedChild = $children->first();
        }

        $subjects = $selectedChild->getAllSubjects();
        $subjects->load(['schedules', 'instructorUser']);

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
        
        if ($children->isEmpty()) {
            return view('parent.attendance-calendar', ['children' => $children, 'selectedChild' => null, 'records' => collect()]);
        }

        $childId = $request->query('child_id');
        $selectedChild = $childId ? $children->firstWhere('id', $childId) : $children->first();

        // Fallback to first child if invalid ID
        if (!$selectedChild) {
            $selectedChild = $children->first();
        }

        // Fetch all attendance records with subject relation for the selected child
        $records = Attendance::with('subject')
            ->where('user_id', $selectedChild->id)
            ->orderBy('date', 'desc')
            ->get();

        return view('parent.attendance-calendar', compact('children', 'selectedChild', 'records'));
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
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];

        if ($request->filled('current_password') || $request->filled('password')) {
            $rules['current_password'] = 'required|current_password';
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $request->validate($rules);

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
