<?php

namespace App\Http\Controllers;

use App\Events\AttendanceSessionChanged;
use App\Http\Requests\ExtendAttendanceSessionRequest;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class AttendanceSessionControlController extends Controller
{
    public function timeline(Request $request, AttendanceSession $session)
    {
        $teacher = $request->user();
        abort_unless($teacher && $teacher->isTeacher() && $teacher->isActive()
            && (int) $session->subject?->instructor_id === (int) $teacher->id, 403);

        $events = Activity::with('causer')->where('subject_type', AttendanceSession::class)
            ->where('subject_id', $session->id)
            ->whereIn('log_name', ['attendance-qr', 'attendance-session'])
            ->latest('id')->limit(50)->get()->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'action' => $activity->description,
                'actor' => $activity->causer?->name,
                'reason' => $activity->properties->get('reason'),
                'at' => $activity->created_at->toIso8601String(),
            ]);

        return response()->json(['events' => $events, 'updated_at' => now()->toIso8601String()]);
    }

    public function extend(ExtendAttendanceSessionRequest $request, AttendanceSession $session)
    {
        $session = DB::transaction(function () use ($session, $request) {
            $locked = AttendanceSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if (!$locked->isSessionActive()) {
                throw ValidationException::withMessages(['session' => 'Attendance session has ended.']);
            }

            $locked->update([
                'session_ends_at' => $locked->session_ends_at->copy()->addMinutes((int) $request->integer('minutes')),
            ]);
            return $locked;
        });

        try {
            AttendanceSessionChanged::dispatch(
                $session->id, $request->user()->id, $session->subject_code,
                'extended', $session->session_ends_at->timestamp
            );
        } catch (\Throwable $exception) {
            Log::warning('Attendance session extension broadcast failed', [
                'session_id' => $session->id, 'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'session_end' => $session->session_ends_at->timestamp,
            'message' => 'Attendance session extended.',
        ]);
    }
}
