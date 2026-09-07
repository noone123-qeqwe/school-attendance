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
        return redirect()->route('student.attendance.calendar');
    }

    /**
     * Mobile QR scanner
     */
    public function scan()
    {
        return redirect()->route('home', ['action' => 'scan']);
    }

    /**
     * Mobile attendance history
     */
    public function history()
    {
        return redirect()->route('home');
    }

    /**
     * Mobile profile
     */
    public function profile()
    {
        $user = Auth::user();
        return view('mobile.profile', compact('user'));
    }

    // Teacher-specific mobile pages
    public function classes()
    {
        return redirect()->route('teacher.classroom.index');
    }

    public function students()
    {
        return redirect()->route('teacher.students');
    }

    // Parent-specific mobile pages
    public function children()
    {
        return redirect()->route('parent.dashboard');
    }

    public function reports()
    {
        return redirect()->route('parent.dashboard');
    }

    // Admin-specific mobile pages
    public function dashboard()
    {
        return redirect()->route('admin.dashboard');
    }

    public function settings()
    {
        return redirect()->route('admin.settings');
    }
}
