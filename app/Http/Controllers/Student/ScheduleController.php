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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $subjects = $user->getAllSubjects();
        $subjects->load(['schedules', 'instructorUser']);

        return view('student.classes', compact('subjects', 'user'));
    }
}
