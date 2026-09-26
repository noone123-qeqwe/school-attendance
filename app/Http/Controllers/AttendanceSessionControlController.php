<?php

namespace App\Http\Controllers;

use App\Events\AttendanceSessionChanged;
use App\Http\Requests\ExtendAttendanceSessionRequest;
use App\Models\AttendanceSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AttendanceSessionControlController extends Controller
{
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
