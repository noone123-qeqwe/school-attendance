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
            'attendances as present_count' => fn($q) => $q->where('status', 'Present'),
            'attendances as late_count' => fn($q) => $q->where('status', 'Late'),
            'attendances as absent_count' => fn($q) => $q->where('status', 'Absent'),
        ])->get();

        // Calculate stats per child
        $childrenData = $children->map(function ($child) {
            $total = $child->total_attendances;
            $present = $child->present_count;
            $late = $child->late_count;
            $absent = $child->absent_count;
            $rate = $total > 0 ? round((($present + $late) / $total) * 100) : 0;

            // Attendance streak
            $streakCount = 0;
            $streakRecords = Attendance::where('user_id', $child->id)
                ->select('date', 'status')
                ->orderBy('date', 'desc')
                ->take(60)
                ->get()
                ->groupBy(fn($r) => $r->date->toDateString());

            foreach ($streakRecords as $dayRecords) {
                $allOnTime = $dayRecords->every(fn($r) => in_array($r->status, ['Present', 'Late']));
                if ($allOnTime) {
                    $streakCount++;
                } else {
                    break;
                }
            }

            // 30-day trend data
            $trendData = Attendance::selectRaw("DATE(date) as day, status, COUNT(*) as total")
                ->where('user_id', $child->id)
                ->whereBetween('date', [now()->subDays(30)->toDateString(), now()->toDateString()])
                ->groupBy('day', 'status')
                ->get()
                ->groupBy('day');

            $trendLabels = [];
            $trendPresent = [];
            $trendAbsent = [];
            for ($i = 29; $i >= 0; $i--) {
                $day = now()->subDays($i);
                $dayKey = $day->toDateString();
                $trendLabels[] = $day->format('M d');
                $dayData = $trendData->get($dayKey, collect());
                $presentCount = ($dayData->firstWhere('status', 'Present')->total ?? 0) +
                                ($dayData->firstWhere('status', 'Late')->total ?? 0);
                $trendPresent[] = $presentCount;
                $trendAbsent[] = $dayData->firstWhere('status', 'Absent')->total ?? 0;
            }

            // Active warnings
            $warnings = Warning::where('user_id', $child->id)
                ->with('subject')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Pending excuses
            $pendingExcuses = ExcuseSubmission::where('user_id', $child->id)
                ->where('status', 'pending')
                ->count();

            return (object) [
                'child' => $child,
                'total' => $total,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'rate' => $rate,
                'streak' => $streakCount,
                'trendLabels' => $trendLabels,
                'trendPresent' => $trendPresent,
                'trendAbsent' => $trendAbsent,
                'warnings' => $warnings,
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
            ->selectRaw('SUM(CASE WHEN status = "Present" THEN 1 ELSE 0 END) as present')
            ->selectRaw('SUM(CASE WHEN status = "Late" THEN 1 ELSE 0 END) as late')
            ->selectRaw('SUM(CASE WHEN status = "Absent" THEN 1 ELSE 0 END) as absent')
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

    public function calendar()
    {
        $parent = Auth::user();
        $children = $parent->children()->get();
        return view('parent.calendar', compact('children'));
    }

    public function getCalendarData(Request $request)
    {
        $parent = Auth::user();
        $childIds = $parent->children()->pluck('users.id');
        
        $start = $request->start;
        $end = $request->end;

        $attendances = Attendance::with('subject', 'user')
            ->whereIn('user_id', $childIds)
            ->when($start, function($q) use ($start) {
                return $q->whereDate('date', '>=', $start);
            })
            ->when($end, function($q) use ($end) {
                return $q->whereDate('date', '<=', $end);
            })
            ->get();

        $events = [];
        foreach ($attendances as $att) {
            $color = '#10b981'; // Present
            if ($att->status === 'Late') $color = '#f59e0b';
            if ($att->status === 'Absent') $color = '#ef4444';
            if ($att->excused) $color = '#6366f1';

            $events[] = [
                'title' => $att->user->name . ' - ' . ($att->subject->name ?? $att->subject_code),
                'start' => $att->date,
                'color' => $color,
                'extendedProps' => [
                    'status' => $att->status,
                    'excused' => $att->excused
                ]
            ];
        }

        // Include Holidays
        $holidays = \App\Models\Holiday::active()
            ->when($start, function($q) use ($start) {
                return $q->whereDate('date', '>=', $start);
            })
            ->when($end, function($q) use ($end) {
                return $q->whereDate('date', '<=', $end);
            })
            ->get();

        foreach ($holidays as $hol) {
            $events[] = [
                'title' => 'Holiday: ' . $hol->name,
                'start' => $hol->date->toDateString(),
                'color' => '#8b5cf6',
                'allDay' => true
            ];
        }

        return response()->json($events);
    }
}
