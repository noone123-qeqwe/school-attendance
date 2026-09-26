<?php

namespace App\Services;

use App\Models\AttendanceSession;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class QrSessionService
{
    /**
     * Start a new QR attendance session.
     */
    public function startSession($teacherId, $subjectCode, $lat = null, $lng = null, $radiusMeters = null, $gracePeriodMinutes = null)
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

            // If start time is far in future or class ended, ensure a viable 30-minute attendance window
            if ($sessionEnd->lt($now->copy()->addMinutes(10)) || $now->lt($startTime->copy()->subMinutes(15))) {
                $sessionEnd = $now->copy()->addMinutes(30);
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
            'subject_code'          => $subjectCode,
            'created_by'            => $teacherId,
            'token'                 => AttendanceSession::generateToken($subjectCode),
            'session_code'          => $sessionCode,
            'previous_session_code' => null,
            'previous_token'        => null,
            'expires_at'            => $now->copy()->addSeconds(15)->min($sessionEnd),
            'session_ends_at'       => $sessionEnd,
            'active'                => true,
            'classroom_lat'         => $lat,
            'classroom_lng'         => $lng,
            'radius_meters'         => $radiusMeters ?? (int) \App\Models\Setting::get('gps_radius', 50),
            'grace_period_minutes'  => $gracePeriodMinutes ?? (int) \App\Models\Setting::get('presence_grace_minutes', 5),
        ]);
    }
    
    /**
     * Refresh the QR token and attendance code for an active session (15-second rotation).
     */
    public function refreshToken(AttendanceSession $session)
    {
        return DB::transaction(function () use ($session) {
        $session = AttendanceSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
        if ($session->qrTokens()->exists()) {
            throw new \Exception('Signed QR rotation is active for this session.');
        }
        $session->markInactiveIfExpired();

        if (!$session->isSessionActive()) {
            throw new \Exception('Session expired.');
        }
        
        $oldToken = $session->token;
        $oldCode  = $session->session_code;
        $now      = now('Asia/Manila');

        if ($oldToken) {
            // Keep rotated token in cache with rotation timestamp for network delay validation
            \Illuminate\Support\Facades\Cache::put("session_prev_token_{$oldToken}", [
                'session_id' => $session->id,
                'rotated_at' => $now->timestamp,
            ], 60);
        }

        if ($oldCode) {
            // Keep rotated session code in cache with rotation timestamp for network delay validation
            \Illuminate\Support\Facades\Cache::put("session_prev_code_{$oldCode}", [
                'session_id' => $session->id,
                'rotated_at' => $now->timestamp,
            ], 60);
        }

        $newCode = AttendanceSession::generateSessionCode();
        while (
            $newCode === $oldCode || 
            AttendanceSession::where('session_code', $newCode)->where('active', true)->where('id', '!=', $session->id)->exists()
        ) {
            $newCode = AttendanceSession::generateSessionCode();
        }

        $session->update([
            'token'                 => AttendanceSession::generateToken($session->subject_code),
            'previous_token'        => $oldToken,
            'session_code'          => $newCode,
            'previous_session_code' => $oldCode,
            'expires_at'            => $now->copy()->addSeconds(15)->min($session->session_ends_at),
        ]);
        
        return $session;
        });
    }
}
