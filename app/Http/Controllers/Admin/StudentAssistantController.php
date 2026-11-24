<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManageStudentAssistantRequest;
use App\Models\ClassStudentAssistant;
use App\Models\Subject;
use App\Models\User;
use App\Services\StudentAssistantAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentAssistantController extends Controller
{
    public function index(Subject $subject): View
    {
        $subject->load('instructorUser');
        $assignments = $subject->studentAssistantAssignments()
            ->with(['student', 'assigningAdmin'])
            ->whereNotNull('active_slot')
            ->whereNull('revoked_at')
            ->where('expires_at', '>=', now())
            ->orderBy('active_slot')
            ->get();
        $occupied = $assignments->pluck('student_id');
        $eligibleAssistantStudents = $subject->getAllStudents()
            ->filter(fn (User $student) => $student->isActive() && !$occupied->contains($student->id))
            ->sortBy('name');
        $instructor = $subject->instructorUser;
        $canAppoint = $instructor?->isTeacher() && $instructor->isActive();

        return view('admin.subjects.student-assistants', compact('subject', 'assignments', 'eligibleAssistantStudents', 'canAppoint'));
    }

    public function store(ManageStudentAssistantRequest $request, Subject $subject, StudentAssistantAssignmentService $service): RedirectResponse
    {
        $service->assign($subject, $request->user(), $request->validated());

        return redirect()->route('admin.subjects.assistants.index', $subject)->with('success', 'Student Assistant assigned.');
    }

    public function replace(ManageStudentAssistantRequest $request, Subject $subject, ClassStudentAssistant $assignment, StudentAssistantAssignmentService $service): RedirectResponse
    {
        $service->assign($subject, $request->user(), $request->validated(), $assignment);

        return redirect()->route('admin.subjects.assistants.index', $subject)->with('success', 'Student Assistant replaced.');
    }

    public function updatePermissions(ManageStudentAssistantRequest $request, Subject $subject, ClassStudentAssistant $assignment, StudentAssistantAssignmentService $service): RedirectResponse
    {
        $service->updatePermissions($subject, $request->user(), $assignment, $request->boolean('can_submit_attendance'));

        return redirect()->route('admin.subjects.assistants.index', $subject)->with('success', 'Student Assistant permissions updated.');
    }

    public function destroy(ManageStudentAssistantRequest $request, Subject $subject, ClassStudentAssistant $assignment, StudentAssistantAssignmentService $service): RedirectResponse
    {
        $service->revoke($subject, $request->user(), $assignment);

        return redirect()->route('admin.subjects.assistants.index', $subject)->with('success', 'Student Assistant removed.');
    }
}
