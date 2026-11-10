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

        return view('student.classes', compact('subjects', 'user'));
    }
}
