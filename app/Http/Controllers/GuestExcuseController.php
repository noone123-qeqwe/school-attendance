<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Attendance;
use App\Models\ExcuseSubmission;
use App\Models\Notification;
use App\Models\Subject;

class GuestExcuseController extends Controller
{
    public function showForm(Attendance $attendance)
    {
        $attendance->loadMissing(['user.parents', 'subject.instructorUser']);

        // Check if an excuse already exists
        $existing = ExcuseSubmission::where('attendance_id', $attendance->id)->first();
        if ($existing) {
            return view('parent.guest-excuse-result', [
                'status' => 'info',
                'attendance' => $attendance,
                'excuse' => $existing,
                'message' => 'An excuse letter has already been submitted for this attendance record.'
            ]);
        }

        return view('parent.guest-excuse', compact('attendance'));
    }

    public function storeExcuse(Request $request, Attendance $attendance)
    {
        $attendance->loadMissing(['user.parents', 'subject.instructorUser']);

        // Check if an excuse already exists
        $existing = ExcuseSubmission::where('attendance_id', $attendance->id)->first();
        if ($existing) {
            return view('parent.guest-excuse-result', [
                'status' => 'info',
                'attendance' => $attendance,
                'excuse' => $existing,
                'message' => 'An excuse letter has already been submitted for this attendance record.'
            ]);
        }

        $request->validate([
            'reason' => 'required|string|min:5|max:1000',
            'reason_category' => 'nullable|string|max:100',
            'parent_name' => 'nullable|string|max:100',
            'parent_phone' => 'nullable|string|max:30',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120', // Max 5MB
        ]);

        $filePath = null;

        if ($request->hasFile('attachment')) {
            if (config('filesystems.default') === 'cloudinary') {
                $filePath = $request->file('attachment')->storeOnCloudinary('excuses')->getSecurePath();
            } else {
                $filePath = $request->file('attachment')->store('excuses', 'public');
            }
        }

        $category = $request->input('reason_category');
        $rawReason = trim($request->input('reason'));
        $parentName = trim((string) $request->input('parent_name'));
        $parentPhone = trim((string) $request->input('parent_phone'));

        $fullReason = $category ? "[{$category}] " . Str::limit($rawReason, 200) : Str::limit($rawReason, 250);

        $descParts = [];
        if (!empty($parentName)) {
            $descParts[] = "Submitted by Parent/Guardian: {$parentName}" . (!empty($parentPhone) ? " (Contact: {$parentPhone})" : "");
        }
        if (!empty($category)) {
            $descParts[] = "Category: {$category}";
        }
        $descParts[] = "Explanation:\n" . $rawReason;
        $description = implode("\n\n", $descParts);

        $excuse = ExcuseSubmission::create([
            'attendance_id' => $attendance->id,
            'user_id' => $attendance->user_id,
            'reason' => $fullReason,
            'description' => $description,
            'attachments' => $filePath ? [$filePath] : null,
            'status' => 'pending',
        ]);

        // Notify the Instructor for this subject
        $subject = $attendance->subject ?? Subject::where('code', $attendance->subject_code)->first();
        $instructorId = $subject?->instructor_id;
        $studentName = $attendance->user?->name ?? 'Student';
        $formattedDate = $attendance->date ? $attendance->date->format('M d, Y') : 'Session';

        if ($instructorId) {
            try {
                Notification::create([
                    'user_id' => $instructorId,
                    'sent_by' => $attendance->user_id,
                    'type' => 'excuse_submitted',
                    'subject_code' => $attendance->subject_code,
                    'message' => "Parent submitted an excuse letter for {$studentName} in {$attendance->subject_code} ({$formattedDate}).",
                    'is_read' => false,
                ]);

                app(\App\Services\WebPushService::class)->sendToUser(
                    $instructorId,
                    '📄 New Excuse Letter Received',
                    "Parent submitted an excuse for {$studentName} in {$attendance->subject_code}.",
                    [
                        'url' => route('teacher.excuse.reviews'),
                        'tag' => 'excuse-review-' . $excuse->id,
                    ]
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed notifying instructor of parent excuse: ' . $e->getMessage());
            }
        }

        // Notify linked parent user(s) if applicable
        if ($attendance->user && $attendance->user->parents && $attendance->user->parents->isNotEmpty()) {
            foreach ($attendance->user->parents as $parent) {
                try {
                    Notification::create([
                        'user_id' => $parent->id,
                        'sent_by' => null,
                        'type' => 'excuse_submitted',
                        'subject_code' => $attendance->subject_code,
                        'message' => "An excuse letter for {$studentName} in {$attendance->subject_code} was submitted and is pending teacher review.",
                        'is_read' => false,
                    ]);
                } catch (\Throwable $e) {
                    // Ignore notification error
                }
            }
        }

        return view('parent.guest-excuse-result', [
            'status' => 'success',
            'attendance' => $attendance,
            'excuse' => $excuse,
            'message' => 'Your excuse letter has been successfully submitted and is pending teacher review.'
        ]);
    }
}

