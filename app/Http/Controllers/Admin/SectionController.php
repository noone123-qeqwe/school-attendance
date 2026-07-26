<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        $sections = \App\Models\Section::with('course.department')->latest()->paginate(10);
        $courses = \App\Models\Course::all();
        return view('admin.sections.index', compact('sections', 'courses'));
    }
}
