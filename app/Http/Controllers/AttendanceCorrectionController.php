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
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'A correction request is already pending for this attendance record.'], 422);
            }
            return back()->with('error', 'A correction request is already pending for this attendance record.');
        }

        $correction = AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'student_id' => Auth::id(),
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Correction request submitted successfully.']);
        }

        return back()->with('success', 'Correction request submitted successfully. Your teacher or admin will review it.');
    }

    /**
     * Review a correction request (Teacher)
     */
    public function update(Request $request, AttendanceCorrection $correction)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'target_status' => 'nullable|in:Present,Late,Excused',
            'notes' => 'nullable|string|max:500',
        ]);

        $teacher = Auth::user();
        $attendance = $correction->attendance;
        $subject = $attendance->subject;

        if ($subject && $subject->instructor_id !== $teacher->id && !$teacher->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        if ($correction->status !== 'pending') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Request has already been processed.'], 422);
            }
            return back()->with('error', 'Request has already been processed.');
        }

        if ($request->action === 'approve') {
            $correction->update([
                'status' => 'approved',
                'teacher_notes' => $request->notes,
            ]);

            $targetStatus = $request->input('target_status', 'Present');
            $oldStatus = $attendance->status . ($attendance->excused ? ' (Excused)' : '');
            $isExcused = $targetStatus === 'Excused';
            $actualStatus = $isExcused ? 'Absent' : $targetStatus;

            $attendance->update([
                'status' => $actualStatus,
                'excused' => $isExcused,
                'excuse_note' => $correction->reason,
            ]);
            
            activity()
                ->performedOn($attendance)
                ->causedBy($teacher)
                ->withProperties(['reason' => 'Correction Approved: ' . $correction->reason, 'old_status' => $oldStatus, 'new_status' => $targetStatus])
                ->log("Attendance corrected to {$targetStatus}. Reviewer notes: {$request->notes}");

        } else {
            $correction->update([
                'status' => 'rejected',
                'teacher_notes' => $request->notes,
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Correction request ' . $request->action . 'd.']);
        }

        return back()->with('success', 'Correction request ' . $request->action . 'd.');
    }
}
