<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Services\StudentScheduleService;

class ClassController extends Controller
{
    public function index(StudentScheduleService $scheduleService)
    {
        $user = Auth::user();

        $subjects = $scheduleService->subjectsFor($user);

        return view('student.classes', compact('subjects', 'user'));
    }
}
