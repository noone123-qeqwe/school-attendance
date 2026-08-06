<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrection;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceCorrectionController extends Controller
{
    /**
     * Submit a correction request (Student)
     */
    public function store(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'reason' => 'required|string|max:500',
        ]);

        $attendance = Attendance::findOrFail($request->attendance_id);

        if ($attendance->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Prevent duplicate pending requests
        $existing = AttendanceCorrection::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'A correction request is already pending for this attendance record.'], 422);
        }

        $correction = AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'student_id' => Auth::id(),
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Correction request submitted successfully.']);
    }

    /**
     * Review a correction request (Teacher)
     */
    public function update(Request $request, AttendanceCorrection $correction)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        $teacher = Auth::user();
        $attendance = $correction->attendance;
        $subject = $attendance->subject;

        if ($subject->instructor_id !== $teacher->id && !$teacher->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        if ($correction->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request has already been processed.'], 422);
        }

        if ($request->action === 'approve') {
            $correction->update([
                'status' => 'approved',
                'teacher_notes' => $request->notes,
            ]);

            // Assuming correction sets status to 'Present' if they were 'Absent' or 'Late' incorrectly
            $oldStatus = $attendance->status;
            $attendance->update(['status' => 'Present']);
            
            activity()
                ->performedOn($attendance)
                ->causedBy($teacher)
                ->withProperties(['reason' => 'Correction Approved: ' . $correction->reason, 'old_status' => $oldStatus, 'new_status' => 'Present'])
                ->log("Attendance corrected to Present. Teacher notes: {$request->notes}");

        } else {
            $correction->update([
                'status' => 'rejected',
                'teacher_notes' => $request->notes,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Correction request ' . $request->action . 'd.']);
    }
}
