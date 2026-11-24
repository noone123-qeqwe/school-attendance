<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManageStudentAssistantRequest;
use App\Models\ClassStudentAssistant;
use App\Models\Subject;
use App\Services\StudentAssistantAssignmentService;

class StudentAssistantAssignmentController extends Controller
{
    public function store(ManageStudentAssistantRequest $request, string $subjectCode, StudentAssistantAssignmentService $assignments)
    {
        $subject = Subject::where('code', $subjectCode)->where('instructor_id', $request->user()->id)->firstOrFail();
        $assignments->assign($subject, $request->user(), $request->validated());

        return back()->with('success', 'Student Assistant assigned.');
    }

    public function replace(ManageStudentAssistantRequest $request, string $subjectCode, ClassStudentAssistant $assignment, StudentAssistantAssignmentService $assignments)
    {
        $subject = Subject::where('code', $subjectCode)->where('instructor_id', $request->user()->id)->firstOrFail();
        $assignments->assign($subject, $request->user(), $request->validated(), $assignment);

        return back()->with('success', 'Student Assistant changed.');
    }

    public function updatePermissions(ManageStudentAssistantRequest $request, string $subjectCode, ClassStudentAssistant $assignment, StudentAssistantAssignmentService $assignments)
    {
        $subject = Subject::where('code', $subjectCode)->where('instructor_id', $request->user()->id)->firstOrFail();
        $assignments->updatePermissions($subject, $request->user(), $assignment, $request->boolean('can_submit_attendance'));

        return back()->with('success', 'Student Assistant permissions updated.');
    }

    public function destroy(ManageStudentAssistantRequest $request, string $subjectCode, ClassStudentAssistant $assignment, StudentAssistantAssignmentService $assignments)
    {
        $subject = Subject::where('code', $subjectCode)->where('instructor_id', $request->user()->id)->firstOrFail();
        $assignments->revoke($subject, $request->user(), $assignment);

        return back()->with('success', 'Student Assistant removed.');
    }
}
