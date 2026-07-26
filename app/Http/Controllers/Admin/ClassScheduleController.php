<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClassScheduleController extends Controller
{
    public function index()
    {
        $schedules = \App\Models\ClassSchedule::with(['subject', 'teacher', 'section'])->latest()->paginate(15);
        return view('admin.class_schedules.index', compact('schedules'));
    }
}
