<?php

namespace App\Http\Controllers;

use App\Helpers\DeviceHelper;
use Illuminate\Support\Facades\Auth;
use App\Models\Subject;
use App\Models\Attendance;
use Carbon\Carbon;

class MobileController extends Controller
{
    /**
     * Mobile home dashboard
     */
    public function home()
    {
        $user = Auth::user();
        $now = now();
        $todayDate = $now->toDateString();

        // Greeting based on time
        $hour = $now->hour;
        $greeting = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');

        // Get today's attendance status
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $todayDate)
            ->whereIn('status', ['Present', 'Late'])
            ->first();

        $todayStatus = 'pending';
        $todayStatusText = 'Not Checked In';
        $todayStatusSubtext = 'Tap Scan QR to check in';
        $checkInTime = null;

        if ($todayAttendance) {
            $todayStatus = 'present';
            $todayStatusText = $todayAttendance->status;
            $checkInTime = Carbon::parse($todayAttendance->time_in)->format('g:i A');
            $todayStatusSubtext = '';
        } else {
            // Check if any class has ended today
            $anyClassEnded = Subject::where('year_level', $user->year_level)
                ->where('semester', $user->semester)
                ->whereHas('schedules', function($query) use ($now) {
                    $query->where('day', $now->format('l'))
                          ->whereTime('end_time', '<', $now->format('H:i:s'));
                })
                ->exists();

            if ($anyClassEnded) {
                $todayStatus = 'absent';
                $todayStatusText = 'Absent';
                $todayStatusSubtext = 'No attendance recorded today';
            }
        }

        // Week attendance stats
        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();
        
        $weekAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->get();

        $weekPresent = $weekAttendances->whereIn('status', ['Present', 'Late'])->count();
        $weekTotal = $weekAttendances->count();
        $attendanceRate = $weekTotal > 0 ? round(($weekPresent / $weekTotal) * 100) : 0;

        // Upcoming classes today
        $upcomingClasses = [];
        $currentDayName = $now->format('l');
        
        $todaySubjects = Subject::where('year_level', $user->year_level)
            ->where('semester', $user->semester)
            ->whereHas('schedules', function($query) use ($currentDayName) {
                $query->where('day', $currentDayName);
            })
            ->with(['schedules' => function($query) use ($currentDayName) {
                $query->where('day', $currentDayName);
            }])
            ->get();

        foreach ($todaySubjects as $subject) {
            $schedule = $subject->schedules->first();
            if ($schedule) {
                $startTime = Carbon::parse($schedule->start_time);
                if ($startTime->greaterThan($now)) {
                    $upcomingClasses[] = [
                        'time' => $startTime->format('g:i A'),
                        'name' => $subject->name ?? $subject->code,
                        'room' => $schedule->room ?? 'TBA',
                        'teacher' => $subject->instructor->name ?? 'TBA',
                    ];
                }
            }
        }

        // Recent activity
        $recentAttendances = Attendance::with('subject')
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->orderBy('time_in', 'desc')
            ->take(10)
            ->get();

        $recentActivity = [];
        foreach ($recentAttendances as $att) {
            $date = Carbon::parse($att->date);
            $dateLabel = $date->isToday() ? 'Today' : ($date->isYesterday() ? 'Yesterday' : $date->format('M j'));
            
            if (!isset($recentActivity[$dateLabel])) {
                $recentActivity[$dateLabel] = [];
            }

            $icon = match($att->status) {
                'Present' => 'check-circle-fill',
                'Late' => 'clock-fill',
                'Absent' => 'x-circle-fill',
                default => 'circle'
            };

            $status = match($att->status) {
                'Present' => 'success',
                'Late' => 'warning',
                'Absent' => 'error',
                default => 'pending'
            };

            $recentActivity[$dateLabel][] = [
                'title' => ucfirst($att->status),
                'subtitle' => ($att->subject->name ?? $att->subject_code) . ' - ' . ($att->subject->schedules->first()->room ?? 'Room TBA'),
                'time' => $att->time_in ? Carbon::parse($att->time_in)->format('g:i A') : '',
                'icon' => $icon,
                'status' => $status,
            ];
        }

        return view('mobile.home', compact(
            'greeting',
            'todayStatus',
            'todayStatusText',
            'todayStatusSubtext',
            'checkInTime',
            'weekPresent',
            'weekTotal',
            'attendanceRate',
            'upcomingClasses',
            'recentActivity'
        ));
    }

    /**
     * Mobile attendance page
     */
    public function attendance()
    {
        // Placeholder for now
        return view('mobile.attendance');
    }

    /**
     * Mobile QR scanner
     */
    public function scan()
    {
        // Placeholder for now
        return view('mobile.scan');
    }

    /**
     * Mobile attendance history
     */
    public function history()
    {
        // Placeholder for now
        return view('mobile.history');
    }

    /**
     * Mobile profile
     */
    public function profile()
    {
        // Placeholder for now
        return view('mobile.profile');
    }

    // Teacher-specific mobile pages
    public function classes()
    {
        return view('mobile.classes');
    }

    public function students()
    {
        return view('mobile.students');
    }

    // Parent-specific mobile pages
    public function children()
    {
        return view('mobile.children');
    }

    public function reports()
    {
        return view('mobile.reports');
    }

    // Admin-specific mobile pages
    public function dashboard()
    {
        return view('mobile.dashboard');
    }

    public function settings()
    {
        return view('mobile.settings');
    }
}
