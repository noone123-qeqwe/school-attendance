<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::with('course.department')->withCount('schedules')->latest()->paginate(10);
        $courses = Course::orderBy('code')->get();
        return view('admin.sections.index', compact('sections', 'courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'year_level' => 'required|integer|between:1,5',
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sections')->where(function ($query) use ($request) {
                    return $query->where('course_id', $request->course_id)
                        ->where('year_level', $request->year_level);
                }),
            ],
        ]);

        Section::create([
            'course_id' => $request->course_id,
            'year_level' => $request->year_level,
            'name' => trim($request->name),
        ]);

        return redirect()->route('admin.sections.index')->with('success', 'Section created successfully.');
    }

    public function update(Request $request, Section $section)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'year_level' => 'required|integer|between:1,5',
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sections')->where(function ($query) use ($request) {
                    return $query->where('course_id', $request->course_id)
                        ->where('year_level', $request->year_level);
                })->ignore($section->id),
            ],
        ]);

        $section->update([
            'course_id' => $request->course_id,
            'year_level' => $request->year_level,
            'name' => trim($request->name),
        ]);

        return redirect()->route('admin.sections.index')->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section)
    {
        $section->delete();
        return redirect()->route('admin.sections.index')->with('success', 'Section deleted successfully.');
    }
}
