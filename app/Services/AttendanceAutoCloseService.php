<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\User;
use App\Models\Warning;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceAutoCloseService
{
    /**
     * Close expired attendance sessions and mark absent students
     */
    public function closeExpiredSessions()
    {
        $now = now('Asia/Manila');
        
        // Find all sessions that have ended but are still marked active
        $expiredSessions = AttendanceSession::where('active', true)
            ->where('session_ends_at', '<', $now)
            ->get();
        
        $closedCount = 0;
        
        foreach ($expiredSessions as $session) {
            Log::info('Auto-closing expired session', [
                'session_id' => $session->id,
                'subject_code' => $session->subject_code,
                'session_ended_at' => $session->session_ends_at->toDateTimeString(),
            ]);
            
            // Mark session as inactive
            $session->update(['active' => false]);
            
            // Mark absent students for this session
            $this->markAbsentStudentsForSession($session);
            $closedCount++;
        }
        
        return $closedCount;
    }
    
    /**
     * Mark students as absent if they didn't clock in during the session
     */
    private function markAbsentStudentsForSession(AttendanceSession $session)
    {
        $subject = $session->subject;
        if (!$subject) {
            Log::warning('Cannot mark absent - subject not found', ['subject_code' => $session->subject_code]);
            return;
        }
        
        $today = $session->session_ends_at->toDateString();
        
        // Get all students who should attend this class
        $enrolledStudents = $subject->getAllStudents();
        
        $absentCount = 0;
        
        foreach ($enrolledStudents as $student) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($student, $session, $today, &$absentCount) {
                // Check if student already has attendance record for this session
                $existingAttendance = Attendance::where('user_id', $student->id)
                    ->where('subject_code', $session->subject_code)
                    ->whereDate('date', $today)
                    ->exists();
                
                if (!$existingAttendance) {
                    // Mark as absent
                    Attendance::create([
                        'user_id' => $student->id,
                        'subject_code' => $session->subject_code,
                        'date' => $today,
                        'status' => 'Absent',
                        'time_in' => null,
                        'latitude' => null,
                        'longitude' => null,
                    ]);
                    
                    $absentCount++;
                    
                    Log::info('Auto-marked student absent', [
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'subject_code' => $session->subject_code,
                        'date' => $today,
                    ]);
                    
                    // Check for consecutive absences and send OSAS warning if needed
                    $this->checkConsecutiveAbsences($student->id, $session->subject_code);
                }
            });
        }
        
        Log::info('Auto-absent marking completed', [
            'session_id' => $session->id,
            'subject_code' => $session->subject_code,
            'total_enrolled' => $enrolledStudents->count(),
            'marked_absent' => $absentCount,
        ]);
    }
    
    /**
     * Check if a student has 3 consecutive absences and send OSAS warning
     */
    private function checkConsecutiveAbsences($studentId, $subjectCode)
    {
        // Get the last 3 attendance records for this student in this subject
        $recentAttendance = Attendance::where('user_id', $studentId)
            ->where('subject_code', $subjectCode)
            ->orderBy('date', 'desc')
            ->take(3)
            ->get();

        // Check if all 3 are absent and consecutive
        if ($recentAttendance->count() >= 3 && 
            $recentAttendance->every(fn($record) => $record->status === 'Absent')) {
            
            // Check if dates are consecutive (allowing for weekends/holidays)
            $dates = $recentAttendance->pluck('date')->map(function($date) {
                return $date instanceof Carbon ? $date : Carbon::parse($date);
            })->sort()->values();
            
            $isConsecutive = true;
            for ($i = 1; $i < $dates->count(); $i++) {
                $daysDiff = $dates[$i]->diffInDays($dates[$i-1]);
                // Allow up to 7 days difference (accounting for weekends)
                if ($daysDiff > 7) {
                    $isConsecutive = false;
                    break;
                }
            }

            if ($isConsecutive) {
                // Check if OSAS warning already sent
                $osasWarningExists = Warning::where('user_id', $studentId)
                    ->where('subject_code', $subjectCode)
                    ->where('type', 'warning_consecutive_3')
                    ->exists();

                if (!$osasWarningExists) {
                    $this->sendAutomaticOsasWarning($studentId, $subjectCode);
                }
            }
        }
    }
    
    /**
     * Send automatic OSAS warning for 3 consecutive absences
     */
    private function sendAutomaticOsasWarning($studentId, $subjectCode)
    {
        $student = User::find($studentId);
        $subject = Subject::where('code', $subjectCode)->first();
        $subjectName = $subject ? $subject->name : $subjectCode;

        $message = "🚨 URGENT: You have been absent for 3 CONSECUTIVE sessions in {$subjectName}. YOU HAVE TO GO TO THE OSAS TO GET THE READMISSION TO ENTER MY CLASS.";

        // Save warning record (sent by system - use admin ID or null)
        $adminUser = User::where('role', 'admin')->first();
        
        Warning::create([
            'user_id' => $studentId,
            'subject_code' => $subjectCode,
            'type' => 'warning_consecutive_3',
            'message' => $message,
            'sent_by' => $adminUser ? $adminUser->id : null,
        ]);

        // Create notification
        Notification::create([
            'user_id' => $studentId,
            'sent_by' => $adminUser ? $adminUser->id : null,
            'type' => 'warning_consecutive_3',
            'subject_code' => $subjectCode,
            'message' => $message
        ]);

        try {
            broadcast(new \App\Events\NotificationSent(
                userId: $studentId,
                message: $message,
                type: 'warning_consecutive_3'
            ));
        } catch (\Exception $e) {
            // Broadcasting failed, continue silently
            Log::warning('Failed to broadcast OSAS warning notification', [
                'error' => $e->getMessage(),
                'student_id' => $studentId,
                'subject_code' => $subjectCode,
            ]);
        }
        
        Log::info('Auto-sent OSAS warning', [
            'student_id' => $studentId,
            'student_name' => $student->name,
            'subject_code' => $subjectCode,
            'subject_name' => $subjectName,
        ]);
    }
}