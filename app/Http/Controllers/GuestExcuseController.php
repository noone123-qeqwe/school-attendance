<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\ExcuseSubmission;

class GuestExcuseController extends Controller
{
    public function showForm(Attendance $attendance)
    {
        // Check if an excuse already exists
        if (ExcuseSubmission::where('attendance_id', $attendance->id)->exists()) {
            return view('parent.guest-excuse-result', [
                'status' => 'info',
                'message' => 'An excuse has already been submitted for this attendance record.'
            ]);
        }

        return view('parent.guest-excuse', compact('attendance'));
    }

    public function storeExcuse(Request $request, Attendance $attendance)
    {
        // Check if an excuse already exists
        if (ExcuseSubmission::where('attendance_id', $attendance->id)->exists()) {
            return view('parent.guest-excuse-result', [
                'status' => 'info',
                'message' => 'An excuse has already been submitted.'
            ]);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120', // Max 5MB
        ]);

        $filePath = null;

        if ($request->hasFile('attachment')) {
            // Using existing cloudinary or local storage setup
            if (config('filesystems.default') === 'cloudinary') {
                $filePath = $request->file('attachment')->storeOnCloudinary('excuses')->getSecurePath();
            } else {
                $filePath = $request->file('attachment')->store('excuses', 'public');
            }
        }

        ExcuseSubmission::create([
            'attendance_id' => $attendance->id,
            'user_id' => $attendance->user_id,
            'reason' => $request->reason,
            'attachments' => $filePath ? [$filePath] : null,
            'status' => 'pending',
        ]);

        // Automatically set the attendance record to excused status pending teacher review
        // In many systems 'excused' is toggled upon approval. We'll leave it as false until approved.

        return view('parent.guest-excuse-result', [
            'status' => 'success',
            'message' => 'Your excuse letter has been successfully submitted and is pending teacher review.'
        ]);
    }
}
