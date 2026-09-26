<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignStudentAssistantRequest;
use App\Http\Requests\RevokeStudentAssistantRequest;
use App\Models\ClassStudentAssistant;
use App\Models\Subject;
use App\Services\StudentAssistantAssignmentService;

class StudentAssistantAssignmentController extends Controller
{
    public function store(AssignStudentAssistantRequest $request, string $subjectCode, StudentAssistantAssignmentService $assignments)
    {
        $subject = Subject::where('code', $subjectCode)->where('instructor_id', $request->user()->id)->firstOrFail();
        $assignments->assign($subject, $request->user(), $request->validated());

        return back()->with('success', 'Student Assistant assigned.');
    }

    public function replace(AssignStudentAssistantRequest $request, string $subjectCode, ClassStudentAssistant $assignment, StudentAssistantAssignmentService $assignments)
    {
        $subject = Subject::where('code', $subjectCode)->where('instructor_id', $request->user()->id)->firstOrFail();
        $assignments->assign($subject, $request->user(), $request->validated(), $assignment);

        return back()->with('success', 'Student Assistant changed.');
    }

    public function destroy(RevokeStudentAssistantRequest $request, string $subjectCode, ClassStudentAssistant $assignment, StudentAssistantAssignmentService $assignments)
    {
        $subject = Subject::where('code', $subjectCode)->where('instructor_id', $request->user()->id)->firstOrFail();
        $assignments->revoke($subject, $request->user(), $assignment);

        return back()->with('success', 'Student Assistant removed.');
    }
}
