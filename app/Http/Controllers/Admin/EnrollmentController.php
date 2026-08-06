<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\User;
use App\Models\Enrollment;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of students enrolled in a subject.
     */
    public function index(Subject $subject)
    {
        $subject->load('enrolledStudents');
        
        // Get all active students not enrolled in this subject (for adding new ones)
        $availableStudents = User::where('role', 'student')
            ->whereNotIn('id', $subject->enrolledStudents->pluck('id'))
            ->orderBy('name')
            ->get();

        return view('admin.enrollments.index', compact('subject', 'availableStudents'));
    }

    /**
     * Store a newly created enrollment in storage.
     */
    public function store(Request $request, Subject $subject)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        // Ensure the user is actually a student
        $student = User::findOrFail($request->student_id);
        if ($student->role !== 'student') {
            return back()->with('error', 'Only students can be enrolled in a subject.');
        }

        // Attach safely without duplicates
        $subject->enrolledStudents()->syncWithoutDetaching([$request->student_id]);

        return back()->with('success', "Student {$student->name} successfully enrolled in {$subject->code}.");
    }

    /**
     * Remove the specified enrollment from storage.
     */
    public function destroy(Subject $subject, User $student)
    {
        $subject->enrolledStudents()->detach($student->id);

        return back()->with('success', "Student {$student->name} removed from {$subject->code}.");
    }
}
