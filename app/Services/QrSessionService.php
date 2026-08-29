<?php

namespace App\Services;

use App\Models\AttendanceSession;
use Carbon\Carbon;
use Illuminate\Support\Str;

class QrSessionService
{
    /**
     * Start a new QR attendance session.
     */
    public function startSession($teacherId, $subjectCode, $lat = null, $lng = null)
    {
        $subject = \App\Models\Subject::with('schedules')->where('code', $subjectCode)->first();
        if (!$subject) {
            throw new \Exception('Subject not found.');
        }

        $now = now('Asia/Manila');
        $todayName = $now->format('l');

        $todaySchedule = $subject->schedules->first(function ($schedule) use ($todayName) {
            return strcasecmp(trim($schedule->day ?? ''), $todayName) === 0;
        });

        $todayDate = $now->toDateString();

        if (!$todaySchedule) {
            // Allow ad-hoc / makeup class attendance session (20 minutes duration)
            $sessionEnd = $now->copy()->addMinutes(20);
        } else {
            $startTime = Carbon::parse($todayDate . ' ' . $todaySchedule->start_time);
            $endTime = Carbon::parse($todayDate . ' ' . $todaySchedule->end_time);
            $sessionEnd = $endTime;

            // If start time is far in future or class ended, ensure a viable 20-minute attendance window
            if ($sessionEnd->lt($now->copy()->addMinutes(5)) || $now->lt($startTime->copy()->subMinutes(15))) {
                $sessionEnd = $now->copy()->addMinutes(20);
            }
        }

        // End any active sessions for this subject
        AttendanceSession::where('subject_code', $subjectCode)
            ->where('active', true)
            ->update(['active' => false]);

        $sessionCode = AttendanceSession::generateSessionCode();
        while (AttendanceSession::where('session_code', $sessionCode)->where('active', true)->exists()) {
            $sessionCode = AttendanceSession::generateSessionCode();
        }
            
        return AttendanceSession::create([
            'subject_code'    => $subjectCode,
            'created_by'      => $teacherId,
            'token'           => AttendanceSession::generateToken($subjectCode),
            'session_code'    => $sessionCode,
            'expires_at'      => $now->copy()->addSeconds(60)->min($sessionEnd),
            'session_ends_at' => $sessionEnd,
            'active'          => true,
            'classroom_lat'   => $lat,
            'classroom_lng'   => $lng,
        ]);
    }
    
    /**
     * Refresh the QR token for an active session.
     */
    public function refreshToken(AttendanceSession $session)
    {
        $session->markInactiveIfExpired();

        if (!$session->isSessionActive()) {
            throw new \Exception('Session expired.');
        }
        
        $session->update([
            'token'      => AttendanceSession::generateToken($session->subject_code),
            'expires_at' => now('Asia/Manila')->addSeconds(60)->min($session->session_ends_at),
        ]);
        
        return $session;
    }
}
