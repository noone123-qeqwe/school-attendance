<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Mark attendance for a student.
     * Prevents duplicate scans for the same subject and date.
     */
    public function markAttendance($userId, $subjectCode, $status = 'Present')
    {
        return DB::transaction(function () use ($userId, $subjectCode, $status) {
            $today = Carbon::today()->toDateString();
            
            // Check if already marked today
            $existing = Attendance::where('user_id', $userId)
                ->where('subject_code', $subjectCode)
                ->whereDate('date', $today)
                ->first();
                
            if ($existing) {
                return $existing;
            }
            
            return Attendance::create([
                'user_id' => $userId,
                'subject_code' => $subjectCode,
                'date' => $today,
                'status' => $status
            ]);
        });
    }
}
