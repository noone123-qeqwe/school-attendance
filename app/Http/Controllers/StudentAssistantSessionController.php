<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\ClassStudentAssistant;
use App\Models\StudentAssistantAttendanceReport;
use App\Services\StudentAssistantAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentAssistantSessionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isStudent() && $request->user()->isActive(), 403);

        $assignments = ClassStudentAssistant::with('subject.schedules')
            ->where('student_id', $request->user()->id)
            ->whereNotNull('active_slot')->whereNull('revoked_at')
            ->where('starts_at', '<=', now())->where('expires_at', '>=', now())
            ->get()
            ->filter(fn ($assignment) => $assignment->isActive());

        $sessions = AttendanceSession::with('subject.schedules')
            ->whereIn('subject_code', $assignments->pluck('subject.code')->filter())
            ->where('active', true)->where('session_ends_at', '>', now())
            ->whereDate('created_at', now('Asia/Manila')->toDateString())
            ->orderByDesc('id')->get()
            ->filter(fn ($session) => $request->user()->can('viewAssistantQr', $session));

        $attendanceReports = StudentAssistantAttendanceReport::with(['student', 'session.subject'])
            ->where('submitted_by', $request->user()->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->latest()->limit(20)->get();

        return view('student-assistant.index', compact('assignments', 'sessions', 'attendanceReports'));
    }

    public function show(Request $request, AttendanceSession $session, StudentAssistantAttendanceService $reports)
    {
        $this->authorize('viewAssistantQr', $session);

        $assistant = $request->user();
        $canSubmitAttendance = $assistant->can('submitAssistantAttendance', $session);
        $attendanceReportStudents = $canSubmitAttendance ? $reports->eligibleStudents($session, $assistant) : collect();
        $attendanceReports = StudentAssistantAttendanceReport::with('student')
            ->where('attendance_session_id', $session->id)
            ->where('submitted_by', $assistant->id)
            ->latest()->get();

        return view('student-assistant.session', compact(
            'session', 'canSubmitAttendance', 'attendanceReportStudents', 'attendanceReports'
        ));
    }

    public function submitAttendance(Request $request, AttendanceSession $session, StudentAssistantAttendanceService $reports)
    {
        $this->authorize('submitAssistantAttendance', $session);

        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'suggested_status' => ['required', Rule::in(StudentAssistantAttendanceReport::SUGGESTED_STATUSES)],
            'reason' => ['nullable', 'string', 'max:250'],
        ]);
        $reports->submit($session, $request->user(), $data);

        return redirect()->to(route('student-assistant.sessions.show', $session) . '#attendance-reports')
            ->with('success', 'Attendance submitted for teacher review.');
    }
}
