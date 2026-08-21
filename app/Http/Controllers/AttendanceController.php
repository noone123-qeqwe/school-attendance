<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Setting;
use App\Events\AttendanceClockedIn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\Controller;


class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        
        // Get all records with subject and excuse submission relationships, newest first
        $records = Attendance::with(['subject', 'excuseSubmission'])
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->orderBy('time_in', 'desc')
            ->get();


        return view('attendance.records', compact('records'));
    }

    

   public function store(Request $request)
{
   $request->validate([
    'subject_code' => 'required|string',
    'latitude' => 'required|numeric',
    'longitude' => 'required|numeric',
    'accuracy' => 'nullable|numeric',
]);
    $user = auth()->user();
    $now = now(); 
    $todayDate = $now->toDateString();

    $subject = Subject::with('schedules')->where('code', $request->subject_code)->first();

    if (!$subject) {
        return redirect()->back()->with('error', 'Subject not found.');
    }

    $isEnrolled = $user->getAllSubjects()->contains('id', $subject->id);

    if (!$isEnrolled) {
        return redirect()->back()->with('error', 'You are not enrolled in this subject.');
    }

    // SCHOOL LOCATION — Read dynamically from admin settings
    $schoolLat    = (float) \App\Models\Setting::get('gps_lat', 12.316);
    $schoolLng    = (float) \App\Models\Setting::get('gps_lng', 123.673);
    $radiusMeters = (int) \App\Models\Setting::get('gps_radius', 100);

    // GPS VALIDATION — use is_null() so 0.0 is accepted
    if (is_null($request->latitude) || is_null($request->longitude)) {
        return redirect()->back()->with('error', 'GPS location is required for clock-in.');
    }

    // CALCULATE DISTANCE
    $distance = $this->distance(
        $request->latitude,
        $request->longitude,
        $schoolLat,
        $schoolLng
    );

    if ($distance > $radiusMeters) {
        return redirect()->back()->with('error', "You are {$distance}m away from the classroom. You must be within {$radiusMeters}m to clock in.");
    }

    if ($request->filled('accuracy') && $request->accuracy > 100) {
        return redirect()->back()->with('error', "GPS signal too weak (Accuracy: {$request->accuracy}m). Please ensure high-trust connection or try WebAuthn flow.");
    }

    // 1. DAY VALIDATION
$todayFull = $now->format('l'); // e.g. "Monday", "Thursday"

$scheduledDays = $subject->schedules->pluck('day'); // ["Monday", "Wednesday", "Friday"]

if (!$scheduledDays->contains($todayFull)) {
    return redirect()->back()->with('error', "This class does not meet on {$todayFull}.");
}

    // 2. TIME & LOCKOUT VALIDATION
    $todaySchedule = $subject->schedules->where('day', $todayFull)->first();
    
    if (!$todaySchedule) {
        return redirect()->back()->with('error', 'No schedule found for this subject today.');
    }
    
    $startTime = \Carbon\Carbon::parse($todayDate . ' ' . $todaySchedule->start_time);
    $endTime = \Carbon\Carbon::parse($todayDate . ' ' . $todaySchedule->end_time);
    
    // Lockout Logic: 20 minutes after class starts
    $lockoutTime = $startTime->copy()->addMinutes(20);

    // Guard Clause: Too Early
    if ($now->lt($startTime->copy()->subMinutes(5))) {
        return redirect()->back()->with('error', 'Too early! You can only clock in 5 minutes before class.');
    }

    // Guard Clause: Class Ended (check BEFORE lockout — more specific message)
    if ($now->gt($endTime)) {
        return redirect()->back()->with('error', 'This class session has already ended.');
    }

    // Guard Clause: 20-Minute Lockout
    if ($now->gt($lockoutTime)) {
        return redirect()->back()->with('error', 'Attendance Closed. You cannot clock in after the 20-minute grace period.');
    }

    // 3. DETERMINE STATUS (Present vs Late)
    $lateThreshold = $startTime->copy()->addMinutes(15);
    $status = $now->lte($lateThreshold) ? 'Present' : 'Late';

    // 4. SAVE RECORD
    $attendance = Attendance::updateOrCreate(
    [
        'user_id' => $user->id,
        'subject_code' => $request->subject_code,
        'date' => $todayDate
    ],
    [
        // If attendance is marked Present/Late, it cannot also be "excused" (excused applies only to Absent).
        'status' => $status,
        'excused' => false,
        'excuse_note' => null,
        'time_in' => $now->format('H:i:s'),
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
        'gps_accuracy' => $request->accuracy,
        'method' => 'manual_gps',
    ]
);

event(new \App\Events\AttendanceMarked($attendance));

    // 5. BROADCAST real-time events
    try {
        // Broadcast to admin dashboard
        broadcast(new AttendanceClockedIn(
            studentName: $user->name,
            subjectName: $subject->name,
            status:      $status,
            time:        $now->format('h:i A')
        ))->toOthers();

        // Broadcast to teacher if instructor exists
        if ($subject->instructor_id) {
            // Get updated stats for this teacher's subjects
            $teacherSubjects = \App\Models\Subject::where('instructor_id', $subject->instructor_id)->pluck('code');
            $todayAttendance = \App\Models\Attendance::where('date', $todayDate)
                ->whereIn('subject_code', $teacherSubjects);
            
            $stats = [
                'total_present' => $todayAttendance->clone()->where('status', 'Present')->count(),
                'total_late' => $todayAttendance->clone()->where('status', 'Late')->count(),
                'total_absent' => $todayAttendance->clone()->where('status', 'Absent')->count(),
                'total_students' => $todayAttendance->clone()->count()
            ];

            broadcast(new \App\Events\TeacherAttendanceUpdated(
                teacherId: $subject->instructor_id,
                studentName: $user->name,
                subjectCode: $request->subject_code,
                status: $status,
                type: 'clock_in',
                stats: $stats
            ))->toOthers();
        }

        // Broadcast to parents
        if ($user->parents) {
            foreach ($user->parents as $parent) {
                broadcast(new \App\Events\LiveNotification(
                    userId: $parent->id,
                    title: 'Attendance Update',
                    message: "{$user->name} was marked as {$status} in {$subject->name}.",
                    type: $status === 'Present' ? 'success' : ($status === 'Late' ? 'warning' : 'error')
                ))->toOthers();
            }
        }

        // Real-Time Web Push Alerts
        try {
            $pushTitle = $status === 'Present' ? "✓ Checked in: {$subject->name}" : ($status === 'Late' ? "⚠️ Marked Late: {$subject->name}" : "❌ Attendance Alert: {$subject->name}");
            $pushBody = "{$user->name} was marked as {$status} on " . $now->format('h:i A');

            // Push to linked parents
            app(\App\Services\WebPushService::class)->sendToParentsOfStudent(
                $user,
                $pushTitle,
                $pushBody,
                ['url' => route('parent.attendance.records', ['child' => $user->id])]
            );

            // Push to student if marked Late
            if ($status === 'Late') {
                app(\App\Services\WebPushService::class)->sendToUser(
                    $user,
                    "⚠️ Marked Late in {$subject->name}",
                    "You clocked in at {$now->format('h:i A')}, after the grace threshold.",
                    ['url' => route('attendance.records')]
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Attendance WebPush dispatch error: ' . $e->getMessage());
        }
    } catch (\Exception $e) {
        // Broadcasting not available — skip, app continues normally
    }

    return redirect()->route('home')->with('success', "Clock-in successful! Status: $status");
}
private function distance($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371000;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) *
         cos(deg2rad($lat2)) *
         sin($dLon/2) *
         sin($dLon/2);

    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $earthRadius * $c;

}

}