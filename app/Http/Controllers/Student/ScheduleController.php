<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Subject;

class ScheduleController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get student's subjects based on year_level and semester
        $subjects = Subject::with('schedules.teacher', 'schedules.section')
            ->where('year_level', $user->year_level)
            ->where('semester', $user->semester)
            ->get();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        $weeklySchedule = [];
        foreach ($days as $day) {
            $weeklySchedule[$day] = collect();
        }

        foreach ($subjects as $subject) {
            foreach ($subject->schedules as $schedule) {
                if (in_array($schedule->day_of_week, $days)) {
                    $schedule->subject = $subject; // Attach subject to schedule for easy access
                    $weeklySchedule[$schedule->day_of_week]->push($schedule);
                }
            }
        }

        // Sort schedules by start_time for each day
        foreach ($days as $day) {
            $weeklySchedule[$day] = $weeklySchedule[$day]->sortBy('start_time')->values();
        }

        return view('student.schedule', compact('weeklySchedule', 'days'));
    }
}
