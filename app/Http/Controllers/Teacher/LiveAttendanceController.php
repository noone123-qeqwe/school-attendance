<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\AttendanceSession;
use App\Models\Attendance;
use Carbon\Carbon;

class LiveAttendanceController extends Controller
{
    /**
     * Display the live attendance screen for an active session.
     */
    public function show(Subject $subject)
    {
        // Get the active session if one exists
        $session = AttendanceSession::where('subject_code', $subject->code)
            ->where('status', 'active')
            ->first();

        if (!$session) {
            return redirect()->route('teacher.dashboard')->with('error', 'No active attendance session found for this subject.');
        }

        $subject->load('enrolledStudents');

        // Get current attendance records for this session
        $attendances = Attendance::where('session_id', $session->id)->get()->keyBy('user_id');

        return view('teacher.qr-session', compact('subject', 'session', 'attendances'));
    }

    /**
     * Manual override: Mark a student as present, late, or absent.
     */
    public function override(Request $request, Subject $subject, AttendanceSession $session)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'status'     => 'required|in:present,late,absent'
        ]);

        // Ensure student is enrolled
        if (!$subject->enrolledStudents()->where('users.id', $request->student_id)->exists()) {
            return back()->with('error', 'Student is not enrolled in this subject.');
        }

        // Update or create the attendance record
        Attendance::updateOrCreate(
            [
                'user_id' => $request->student_id,
                'session_id' => $session->id,
                'subject_code' => $subject->code,
                'date' => $session->created_at->toDateString(),
            ],
            [
                'status' => $request->status,
                'time_in' => in_array($request->status, ['present', 'late']) ? now()->toTimeString() : null,
                'method' => 'manual', // indicates teacher override
            ]
        );

        return back()->with('success', 'Attendance status overridden manually.');
    }
}
