<?php

namespace App\Http\Controllers;

use App\Models\StudentAssistantAttendanceReport;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentAssistantAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherAssistantAttendanceController extends Controller
{
    public function index(Request $request, string $subjectCode)
    {
        $subject = $this->ownedClass($request->user(), $subjectCode);
        $status = $request->query('status', 'pending');
        if (!in_array($status, StudentAssistantAttendanceReport::REVIEW_STATUSES, true)) {
            $status = 'pending';
        }

        $reports = StudentAssistantAttendanceReport::with(['student', 'submitter', 'reviewer', 'session'])
            ->whereHas('session', fn ($query) => $query->where('subject_code', $subject->code))
            ->where('review_status', $status)
            ->latest()->orderByDesc('id')->paginate(20)->withQueryString();

        return view('teacher.classroom.assistant-attendance', compact('subject', 'status', 'reports'));
    }

    public function update(Request $request, string $subjectCode, StudentAssistantAttendanceReport $report, StudentAssistantAttendanceService $reports)
    {
        $subject = $this->ownedClass($request->user(), $subjectCode);
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'dismiss'])]]);
        $reports->review($subject, $report, $request->user(), $data['decision']);

        return redirect()->route('teacher.classroom.assistant-attendance.index', $subject->code)
            ->with('success', $data['decision'] === 'approve'
                ? 'Submission approved and attendance recorded.'
                : 'Submission dismissed.');
    }

    private function ownedClass(User $teacher, string $subjectCode): Subject
    {
        abort_unless($teacher->isTeacher() && $teacher->isActive(), 403);

        return Subject::where('code', $subjectCode)->where('instructor_id', $teacher->id)->firstOrFail();
    }
}
