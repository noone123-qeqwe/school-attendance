<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\StudentScheduleService;

class ScheduleController extends Controller
{
    public function index(StudentScheduleService $scheduleService)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $subjects = $scheduleService->subjectsFor($user);
        $subjectAttendanceMap = \App\Models\Attendance::where('user_id', $user->id)
            ->orderByDesc('date')
            ->orderByDesc('time_in')
            ->get()
            ->groupBy('subject_code')
            ->map(function ($rows) {
                return [
                    'count' => $rows->whereIn('status', ['Present', 'Late'])->count() + $rows->where('excused', true)->whereNotIn('status', ['Present', 'Late'])->count(),
                    'total' => $rows->count(),
                    'recent' => $rows->take(6)->map(fn ($r) => [
                        'date' => \Carbon\Carbon::parse($r->date)->format('D, M j, Y'),
                        'time' => $r->time_in ? \Carbon\Carbon::parse($r->time_in)->format('g:i A') : '—',
                        'status' => ($r->excused || strcasecmp((string) $r->status, 'Excused') === 0) ? 'Excused' : (string) $r->status,
                        'method' => (string) ($r->method ?: 'Verified'),
                    ])->values()->all(),
                ];
            })
            ->all();

        return view('student.classes', compact('subjects', 'user', 'subjectAttendanceMap'));
    }
}
