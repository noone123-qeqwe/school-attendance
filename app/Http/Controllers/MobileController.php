<?php

namespace App\Http\Controllers;

use App\Helpers\DeviceHelper;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\ExcuseSubmission;
use App\Models\Warning;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileController extends Controller
{
    /**
     * Mobile home dashboard
     */
    public function home()
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->isTeacher()) {
            return redirect()->route('teacher.dashboard');
        }
        if ($user->isParent()) {
            return redirect()->route('parent.dashboard');
        }

        $now = now();
        $todayDate = $now->toDateString();
        $allSubjects = app(\App\Services\StudentScheduleService::class)->subjectsFor($user);
        $subjectsById = $allSubjects->keyBy('id');

        $todaySubjects = $allSubjects
            ->map(function ($subject) use ($now) {
                $todaySchedules = $subject->schedules->where('day', $now->format('l'))->values();
                if ($todaySchedules->isEmpty()) {
                    return null;
                }

                $todaySubject = clone $subject;
                $todaySubject->setRelation('schedules', $todaySchedules);
                return $todaySubject;
            })
            ->filter()
            ->values();

        // Greeting based on time
        $hour = $now->hour;
        $greeting = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');

        // Today's attendance map (single query)
        $todayAttendances = Attendance::where('user_id', $user->id)
            ->whereDate('date', $todayDate)
            ->get()
            ->keyBy('subject_code');

        $todayAttendance = $todayAttendances
            ->filter(fn ($att) => in_array($att->status, ['Present', 'Late'], true))
            ->sortByDesc('checked_in_at')
            ->first();

        $todayStatus = 'pending';
        $todayStatusText = 'Not Checked In';
        $todayStatusSubtext = 'Tap Scan QR to check in';
        $checkInTime = null;

        $userCreated = $user->created_at ? Carbon::parse($user->created_at)->timezone('Asia/Manila') : null;
        $todaySchedule = collect();
        /** @var array<int, array{sort_time: string, time: string, name: string, room: string, teacher: string}> $upcomingClasses */
        $upcomingClasses = [];

        foreach ($todaySubjects as $subject) {
            foreach ($subject->schedules as $schedule) {
                $classStart = Carbon::parse($todayDate . ' ' . $schedule->start_time);
                $classEnd   = Carbon::parse($todayDate . ' ' . $schedule->end_time);
                $existing   = $todayAttendances->get($subject->code);

                $joinedAt = $userCreated;
                if (!$subject->matchesStudentProfile($user)) {
                    $pivotCreatedAt = $subjectsById->get($subject->id)?->pivot?->getAttribute('created_at');
                    if ($pivotCreatedAt) {
                        $enrolledAt = Carbon::parse($pivotCreatedAt)->timezone('Asia/Manila');
                        if (!$joinedAt || $enrolledAt->greaterThan($joinedAt)) {
                            $joinedAt = $enrolledAt;
                        }
                    }
                }
                $joinedAfterSessionStarted = $joinedAt && $joinedAt->greaterThanOrEqualTo($classStart);

                $status = 'upcoming';
                if ($existing && in_array($existing->status, ['Present', 'Late'], true)) {
                    $status = 'completed';
                } elseif ($existing && $existing->status === 'Absent') {
                    $status = 'missed';
                } elseif ($joinedAfterSessionStarted) {
                    $status = 'past';
                } elseif ($now->greaterThan($classEnd)) {
                    $status = 'missed';
                } elseif ($now->greaterThanOrEqualTo($classStart) && $now->lessThanOrEqualTo($classEnd)) {
                    $status = 'ongoing';
                }

                $todaySchedule->push((object) [
                    'subject'    => $subject,
                    'schedule'   => $schedule,
                    'start_time' => $classStart,
                    'end_time'   => $classEnd,
                    'status'     => $status,
                    'attendance' => $existing,
                ]);

                if ($classStart->greaterThan($now)) {
                    $upcomingClasses[] = [
                        'sort_time' => $classStart->format('H:i:s'),
                        'time' => $classStart->format('g:i A'),
                        'name' => $subject->name ?? $subject->code,
                        'room' => $schedule->room ?? 'TBA',
                        'teacher' => $subject->instructorUser->name ?? 'TBA',
                    ];
                }
            }
        }
        $todaySchedule = $todaySchedule->sortBy('start_time')->values();
        usort($upcomingClasses, fn ($a, $b) => strcmp($a['sort_time'], $b['sort_time']));

        if ($todayAttendance) {
            $todayStatus = 'present';
            $todayStatusText = $todayAttendance->status;
            $checkInTime = $todayAttendance->time_in
                ? Carbon::parse($todayAttendance->time_in)->format('g:i A') : null;
            $todayStatusSubtext = $checkInTime ? '' : 'Checked in today';
        } elseif ($todaySchedule->contains(fn ($item) => $item->status === 'missed')) {
            $todayStatus = 'absent';
            $todayStatusText = 'Absent';
            $todayStatusSubtext = 'No attendance recorded today';
        }

        // Aggregated attendance summary + streak + 7-day pulse
        $attendanceSummaryRows = Attendance::where('user_id', $user->id)
            ->selectRaw('subject_code, status, COUNT(*) as record_count, SUM(CASE WHEN excused THEN 1 ELSE 0 END) as excused_count')
            ->groupBy('subject_code', 'status')
            ->get();

        $totalPresent = 0;
        $totalLate = 0;
        $totalAbsent = 0;
        $subjectAttendanceStats = collect();

        foreach ($attendanceSummaryRows as $row) {
            $count = (int) $row->record_count;
            if ($row->status === 'Present') $totalPresent += $count;
            if ($row->status === 'Late') $totalLate += $count;
            if ($row->status === 'Absent') $totalAbsent += $count;

            $code = (string) ($row->subject_code ?? '');
            $bucket = $subjectAttendanceStats->get($code) ?? (object) [
                'present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0,
            ];
            if ($row->status === 'Present') $bucket->present += $count;
            if ($row->status === 'Late') $bucket->late += $count;
            if ($row->status === 'Absent') $bucket->absent += $count;
            $bucket->excused += (int) $row->excused_count;
            $subjectAttendanceStats->put($code, $bucket);
        }

        $historicalMissesPerSubject = app(\App\Actions\Attendance\CalculateMissedAttendanceAction::class)
            ->executePerSubject($user, $allSubjects);
        $totalAbsent += array_sum($historicalMissesPerSubject);
        $totalAll = $totalPresent + $totalLate + $totalAbsent;
        $overallRate = $totalAll > 0 ? round((($totalPresent + $totalLate) / $totalAll) * 100) : null;

        $subjectStats = collect();
        foreach ($allSubjects as $subjectModel) {
            $b = $subjectAttendanceStats->get($subjectModel->code);
            $p = (int) ($b->present ?? 0);
            $l = (int) ($b->late ?? 0);
            $a = (int) ($b->absent ?? 0) + (int) ($historicalMissesPerSubject[$subjectModel->code] ?? 0);
            $e = (int) ($b->excused ?? 0);
            $tot = $p + $l + $a;
            $eff = $tot - $e;
            $subjectStats->push((object) [
                'code'    => $subjectModel->code,
                'name'    => $subjectModel->name ?? $subjectModel->code,
                'present' => $p,
                'late'    => $l,
                'absent'  => $a,
                'excused' => $e,
                'total'   => $tot,
                'rate'    => $eff > 0 ? round((($p + $l) / $eff) * 100) : 0,
            ]);
        }
        $subjectStats = $subjectStats->sortBy('rate')->values();

        // Week attendance stats & 7-day pulse + streak (single query for last 60 days)
        $streakRecords = Attendance::where('user_id', $user->id)
            ->where('date', '>=', $now->copy()->subDays(60)->toDateString())
            ->select('date', 'status')
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy(fn ($r) => $r->date->toDateString());

        $streakCount = 0;
        foreach ($streakRecords as $dayRows) {
            if ($dayRows->every(fn ($r) => in_array($r->status, ['Present', 'Late'], true))) {
                $streakCount++;
            } else {
                break;
            }
        }

        $weeklyPulse = collect();
        for ($dayOffset = 6; $dayOffset >= 0; $dayOffset--) {
            $pulseDate = $now->copy()->subDays($dayOffset);
            $pulseKey = $pulseDate->toDateString();
            $dayRows = $streakRecords->get($pulseKey, collect());
            $pulseStatus = 'empty';
            if ($dayRows->isNotEmpty()) {
                if ($dayRows->contains(fn ($r) => $r->status === 'Absent')) {
                    $pulseStatus = 'absent';
                } elseif ($dayRows->contains(fn ($r) => $r->status === 'Late')) {
                    $pulseStatus = 'late';
                } else {
                    $pulseStatus = 'present';
                }
            }
            $weeklyPulse->push((object) [
                'date_key'   => $pulseKey,
                'day_num'    => $pulseDate->day,
                'day_short'  => $pulseDate->format('D'),
                'day_letter' => substr($pulseDate->format('D'), 0, 1),
                'label'      => $pulseDate->format('M j'),
                'is_today'   => $dayOffset === 0,
                'status'     => $pulseStatus,
                'count'      => $dayRows->count(),
            ]);
        }

        $weekStart = $now->copy()->startOfWeek()->toDateString();
        $weekEnd = $now->copy()->endOfWeek()->toDateString();
        $weekAttendances = $streakRecords
            ->filter(fn ($_, $dateKey) => $dateKey >= $weekStart && $dateKey <= $weekEnd)
            ->flatten();

        $weekPresent = $weekAttendances->whereIn('status', ['Present', 'Late'])->count();
        $weekTotal = $weekAttendances->count();
        $attendanceRate = $weekTotal > 0 ? round(($weekPresent / $weekTotal) * 100) : 0;

        $activeWarnings = Warning::where('user_id', $user->id)
            ->where('created_at', '>=', $now->copy()->subDays(14))
            ->orderBy('created_at', 'desc')
            ->get();

        $announcements = Announcement::with('author')
            ->published()
            ->visibleTo($user)
            ->latest()
            ->take(3)
            ->get();

        $pendingExcusesCount = ExcuseSubmission::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // Recent activity
        $recentAttendances = Attendance::with('subject.schedules')
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->orderBy('time_in', 'desc')
            ->take(6)
            ->get();

        $recentActivity = [];
        foreach ($recentAttendances as $att) {
            $date = Carbon::parse($att->date);
            $dateLabel = $date->isToday() ? 'Today' : ($date->isYesterday() ? 'Yesterday' : $date->format('M j'));

            if (!isset($recentActivity[$dateLabel])) {
                $recentActivity[$dateLabel] = [];
            }

            $icon = match ($att->status) {
                'Present' => 'check-circle-fill',
                'Late' => 'clock-fill',
                'Absent' => 'x-circle-fill',
                default => 'circle'
            };

            $status = match ($att->status) {
                'Present' => 'success',
                'Late' => 'warning',
                'Absent' => 'error',
                default => 'pending'
            };

            $recentActivity[$dateLabel][] = [
                'title' => ucfirst($att->status),
                'subtitle' => ($att->subject->name ?? $att->subject_code) . ' · ' . ($att->subject->schedules->first()->room ?? 'Room TBA'),
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
            'overallRate',
            'totalPresent',
            'totalLate',
            'totalAbsent',
            'streakCount',
            'weeklyPulse',
            'todaySchedule',
            'subjectStats',
            'activeWarnings',
            'announcements',
            'pendingExcusesCount',
            'upcomingClasses',
            'recentActivity'
        ));
    }

    /**
     * Mobile attendance page
     */
    public function attendance()
    {
        return redirect()->route('student.calendar');
    }

    /**
     * Mobile QR scanner
     */
    public function scan()
    {
        return view('mobile.scan');
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
        return Auth::user()->isSuperAdmin()
            ? redirect()->route('admin.settings')
            : redirect()->route('admin.dashboard');
    }
}
