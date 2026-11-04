<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Warning;
use App\Models\ExcuseSubmission;
use App\Http\Requests\Api\ParentAttendanceIndexRequest;
use App\Http\Resources\Api\ParentAttendanceResource;
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
            $absent += app(\App\Actions\Attendance\CalculateMissedAttendanceAction::class)->execute($child);

            $total = $present + $late + $absent;
            $rate = $total > 0 ? round((($present + $late) / $total) * 100) : 0;

            // Attendance streak
            $streakCount = 0;
            $streakRecords = $allAttendance
                ->sortByDesc('date')
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

    public function childDetail(ParentAttendanceIndexRequest $request, User $child)
    {
        $this->authorizeChild($child);
        $filters = $request->validated();

        $query = Attendance::query()
            ->select([
                'id',
                'subject_id',
                'subject_code',
                'subject_name',
                'status',
                'excused',
                'time_in',
                'time_out',
                'date',
                'method',
            ])
            ->with('subject:id,code,name')
            ->where('user_id', $child->id)
            ->orderByDesc('date')
            ->orderByDesc('id');

        if (isset($filters['subject'])) {
            $query->where('subject_code', $filters['subject']);
        }
        if (isset($filters['status'])) {
            if ($filters['status'] === 'Excused') {
                $query->where('excused', true);
            } else {
                $query->where('status', $filters['status'])->where('excused', false);
            }
        }
        if (isset($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        $records = $query->paginate($filters['per_page'] ?? 20);
        $records->through(fn (Attendance $attendance) => (new ParentAttendanceResource($attendance))->toArray($request));

        return response()->json([
            'status' => 'success',
            'data' => $records
        ], 200, [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
