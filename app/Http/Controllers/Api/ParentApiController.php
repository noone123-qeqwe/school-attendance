<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Warning;
use App\Models\ExcuseSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentApiController extends Controller
{
    private function authorizeChild(User $child): void
    {
        $parent = Auth::user();
        if (!$parent->children()->where('student_id', $child->id)->exists()) {
            abort(403, 'You do not have access to this student\'s records.');
        }
    }

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $children = $user->children()->with([
            'attendances' => function ($q) {
                $q->orderBy('date', 'desc')->take(10);
            },
            'attendances.subject',
        ])->get();

        $childrenData = $children->map(function ($child) {
            $allAttendance = Attendance::where('user_id', $child->id)->get();
            $present = $allAttendance->where('status', 'Present')->count();
            $late = $allAttendance->where('status', 'Late')->count();
            $absent = $allAttendance->where('status', 'Absent')->count();

            // Historical missed classes calculation
            $now = now('Asia/Manila');
            $todayDate = $now->toDateString();
            $currentTime = $now->format('H:i:s');

            if ($child->year_level && $child->semester) {
                $childSubjects = \App\Models\Subject::where('year_level', $child->year_level)
                    ->where('semester', $child->semester)
                    ->where(function ($q) use ($child) {
                        $q->whereNull('course')->orWhere('course', '')->orWhere('course', $child->course);
                    })
                    ->where(function ($q) use ($child) {
                        $q->whereNull('section')->orWhere('section', '')->orWhere('section', $child->section);
                    })
                    ->with('schedules')
                    ->get();

                $earliestRecord = Attendance::where('user_id', $child->id)->min('date');
                $academicYear = \App\Models\AcademicYear::where('is_current', true)->first();
                $semesterStart = $academicYear ? $academicYear->start_date : null;

                $startDate = $now->copy()->subDays(90);
                if ($semesterStart && \Carbon\Carbon::parse($semesterStart)->lt($now)) {
                    $startDate = \Carbon\Carbon::parse($semesterStart);
                }
                if ($earliestRecord) {
                    $earliest = \Carbon\Carbon::parse($earliestRecord);
                    if ($earliest->lt($startDate)) {
                        $startDate = $earliest;
                    }
                }

                $holidays = \App\Models\Holiday::active()
                    ->whereDate('date', '>=', $startDate->toDateString())
                    ->whereDate('date', '<=', $todayDate)
                    ->pluck('date')
                    ->map(fn($d) => $d instanceof \DateTimeInterface ? $d->format('Y-m-d') : \Carbon\Carbon::parse($d)->format('Y-m-d'))
                    ->toArray();

                foreach ($childSubjects as $subj) {
                    $scheduledDays = $subj->schedules->pluck('day')->unique()->values();
                    if ($scheduledDays->isEmpty()) continue;

                    $expectedSessions = 0;
                    $cursor = $startDate->copy();
                    while ($cursor->lte($now)) {
                        $cursorDayName = $cursor->format('l');
                        $cursorDateStr = $cursor->toDateString();

                        if (!in_array($cursorDateStr, $holidays) && $cursorDayName !== 'Sunday') {
                            if ($scheduledDays->contains($cursorDayName)) {
                                if ($cursorDateStr === $todayDate) {
                                    $todaySchedules = $subj->schedules->where('day', $cursorDayName);
                                    foreach ($todaySchedules as $sched) {
                                        if ($sched->end_time < $currentTime) {
                                            $expectedSessions++;
                                        }
                                    }
                                } else {
                                    $expectedSessions += $subj->schedules->where('day', $cursorDayName)->count();
                                }
                            }
                        }
                        $cursor->addDay();
                    }

                    $actualRecords = Attendance::where('user_id', $child->id)
                        ->where('subject_code', $subj->code)
                        ->whereDate('date', '>=', $startDate->toDateString())
                        ->whereDate('date', '<=', $todayDate)
                        ->count();

                    $absent += max(0, $expectedSessions - $actualRecords);
                }
            }

            $total = $present + $late + $absent;
            $rate = $total > 0 ? round((($present + $late) / $total) * 100) : 0;

            // Attendance streak
            $streakCount = 0;
            $streakRecords = Attendance::where('user_id', $child->id)
                ->orderBy('date', 'desc')
                ->get()
                ->groupBy(fn($r) => $r->date->toDateString());

            foreach ($streakRecords as $dayRecords) {
                $allOnTime = $dayRecords->every(fn($r) => in_array($r->status, ['Present', 'Late']));
                if ($allOnTime) {
                    $streakCount++;
                } else {
                    break;
                }
            }

            // 30-day trend data
            $trendData = Attendance::selectRaw("DATE(date) as day, status, COUNT(*) as total")
                ->where('user_id', $child->id)
                ->whereBetween('date', [now()->subDays(30)->toDateString(), now()->toDateString()])
                ->groupBy('day', 'status')
                ->get()
                ->groupBy('day');

            $trendLabels = [];
            $trendPresent = [];
            $trendAbsent = [];
            for ($i = 29; $i >= 0; $i--) {
                $day = now()->subDays($i);
                $dayKey = $day->toDateString();
                $trendLabels[] = $day->format('M d');
                $dayData = $trendData->get($dayKey, collect());
                $presentCount = ($dayData->firstWhere('status', 'Present')->total ?? 0) +
                                ($dayData->firstWhere('status', 'Late')->total ?? 0);
                $trendPresent[] = $presentCount;
                $trendAbsent[] = $dayData->firstWhere('status', 'Absent')->total ?? 0;
            }

            $warnings = Warning::where('user_id', $child->id)
                ->with('subject')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $pendingExcuses = ExcuseSubmission::where('user_id', $child->id)
                ->where('status', 'pending')
                ->count();

            return [
                'child' => $child,
                'stats' => [
                    'total' => $total,
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent,
                    'rate' => $rate,
                    'streak' => $streakCount,
                ],
                'trends' => [
                    'labels' => $trendLabels,
                    'present' => $trendPresent,
                    'absent' => $trendAbsent,
                ],
                'warnings' => $warnings,
                'pending_excuses' => $pendingExcuses,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $childrenData,
        ]);
    }

    public function childDetail(Request $request, User $child)
    {
        $this->authorizeChild($child);

        $query = Attendance::with('subject')
            ->where('user_id', $child->id)
            ->orderBy('date', 'desc');

        if ($request->filled('subject')) {
            $query->where('subject_code', $request->subject);
        }
        if ($request->filled('status')) {
            if ($request->status === 'Excused') {
                $query->where('excused', true);
            } else {
                $query->where('status', $request->status)->where('excused', false);
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $records = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $records
        ]);
    }
}
