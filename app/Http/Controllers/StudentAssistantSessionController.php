<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\ClassStudentAssistant;
use Illuminate\Http\Request;

class StudentAssistantSessionController extends Controller
{
    public function index(Request $request)
    {
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

        return view('student-assistant.index', compact('assignments', 'sessions'));
    }

    public function show(Request $request, AttendanceSession $session)
    {
        $this->authorize('viewAssistantQr', $session);

        return view('student-assistant.session', compact('session'));
    }
}
