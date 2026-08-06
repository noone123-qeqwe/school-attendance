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

        if (!$todaySchedule) {
            throw new \Exception('This subject does not have a class scheduled for today (' . $todayName . '). Please check the subject schedule.');
        }

        $todayDate = $now->toDateString();
        $startTime = Carbon::parse($todayDate . ' ' . $todaySchedule->start_time);
        $endTime = Carbon::parse($todayDate . ' ' . $todaySchedule->end_time);
        $sessionOpenTime = $startTime->copy()->subMinutes(5);
        $sessionEnd = $startTime->copy()->addMinutes(20);

        if ($now->lt($sessionOpenTime)) {
            $waitTime = $sessionOpenTime->diffForHumans($now, true);
            throw new \Exception("⏰ Too early! QR session opens 5 minutes before class starts.\n\nClass time: {$startTime->format('h:i A')} - {$endTime->format('h:i A')}\nWait time: {$waitTime}");
        }

        if ($now->gt($sessionEnd)) {
            throw new \Exception("The 20-minute attendance window has closed at {$sessionEnd->format('h:i A')}. Cannot start new attendance session.");
        }

        // End any active sessions for this subject
        AttendanceSession::where('subject_code', $subjectCode)
            ->where('active', true)
            ->update(['active' => false]);
            
        return AttendanceSession::create([
            'subject_code'    => $subjectCode,
            'created_by'      => $teacherId,
            'token'           => AttendanceSession::generateToken($subjectCode),
            'expires_at'      => $now->copy()->addSeconds(20)->min($sessionEnd),
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
            'expires_at' => now('Asia/Manila')->addSeconds(20)->min($session->session_ends_at),
        ]);
        
        return $session;
    }
}
