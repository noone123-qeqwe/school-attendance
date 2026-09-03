<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('department')->withCount('sections')->latest()->paginate(10);
        $departments = Department::orderBy('name')->get();
        return view('admin.courses.index', compact('courses', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'code' => 'required|string|max:50|unique:courses,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        Course::create([
            'department_id' => $request->department_id,
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'description' => $request->description,
        ]);

        return redirect()->route('admin.courses.index')->with('success', 'Course created successfully.');
    }

    public function update(Request $request, Course $course)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'code' => 'required|string|max:50|unique:courses,code,' . $course->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $course->update([
            'department_id' => $request->department_id,
            'code' => strtoupper(trim($request->code)),
            'name' => trim($request->name),
            'description' => $request->description,
        ]);

        return redirect()->route('admin.courses.index')->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return redirect()->route('admin.courses.index')->with('success', 'Course deleted successfully.');
    }
}
