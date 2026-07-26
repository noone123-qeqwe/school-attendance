<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = \App\Models\Course::with('department')->latest()->paginate(10);
        $departments = \App\Models\Department::all();
        return view('admin.courses.index', compact('courses', 'departments'));
    }
}
