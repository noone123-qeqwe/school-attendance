<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClassScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = ClassSchedule::with(['subject', 'teacher', 'section.course'])->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('room', 'like', "%{$search}%")
                  ->orWhereHas('subject', function ($sq) use ($search) {
                      $sq->where('code', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('teacher', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('section', function ($secq) use ($search) {
                      $secq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('day')) {
            $query->where('day_of_week', $request->day);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        $schedules = $query->paginate(15)->withQueryString();
        $subjects = Subject::orderBy('code')->get();
        $sections = Section::with('course')->orderBy('name')->get();
        $teachers = User::where('role', 'teacher')->orderBy('name')->get();

        $stats = [
            'total' => ClassSchedule::count(),
            'subjects' => ClassSchedule::distinct('subject_id')->count('subject_id'),
            'sections' => ClassSchedule::distinct('section_id')->count('section_id'),
            'rooms' => ClassSchedule::distinct('room')->whereNotNull('room')->count('room'),
        ];

        return view('admin.class_schedules.index', compact('schedules', 'subjects', 'sections', 'teachers', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'nullable|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'room' => 'required|string|max:50',
        ]);

        $startTime = Carbon::parse($request->start_time)->format('H:i');
        $endTime = Carbon::parse($request->end_time)->format('H:i');

        // Check for room conflict on the same day and overlapping time
        $roomConflict = ClassSchedule::where('room', trim($request->room))
            ->where('day_of_week', $request->day_of_week)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($sub) use ($startTime, $endTime) {
                    $sub->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })
            ->first();

        if ($roomConflict) {
            $existingSub = $roomConflict->subject->code ?? 'another subject';
            return back()->withInput()->with('error', "Room conflict: '{$request->room}' is already scheduled for {$existingSub} on {$request->day_of_week} ({$roomConflict->start_time} - {$roomConflict->end_time}).");
        }

        ClassSchedule::create([
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'section_id' => $request->section_id,
            'day_of_week' => $request->day_of_week,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room' => trim($request->room),
        ]);

        // Sync with Subject's instructor and section if specified
        if ($request->filled('teacher_id')) {
            Subject::where('id', $request->subject_id)->update(['instructor_id' => $request->teacher_id]);
        }

        // Sync with Attendance Schedule table so student scanning works
        Schedule::updateOrCreate(
            [
                'subject_id' => $request->subject_id,
                'day' => $request->day_of_week,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ],
            [
                'room' => trim($request->room),
            ]
        );

        return redirect()->route('admin.class-schedules.index')->with('success', 'Class schedule assigned successfully.');
    }

    public function update(Request $request, ClassSchedule $classSchedule)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'nullable|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'room' => 'required|string|max:50',
        ]);

        $startTime = Carbon::parse($request->start_time)->format('H:i');
        $endTime = Carbon::parse($request->end_time)->format('H:i');

        // Check for room conflict on the same day and overlapping time excluding self
        $roomConflict = ClassSchedule::where('id', '!=', $classSchedule->id)
            ->where('room', trim($request->room))
            ->where('day_of_week', $request->day_of_week)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($sub) use ($startTime, $endTime) {
                    $sub->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })
            ->first();

        if ($roomConflict) {
            $existingSub = $roomConflict->subject->code ?? 'another subject';
            return back()->withInput()->with('error', "Room conflict: '{$request->room}' is already scheduled for {$existingSub} on {$request->day_of_week} ({$roomConflict->start_time} - {$roomConflict->end_time}).");
        }

        $classSchedule->update([
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'section_id' => $request->section_id,
            'day_of_week' => $request->day_of_week,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room' => trim($request->room),
        ]);

        if ($request->filled('teacher_id')) {
            Subject::where('id', $request->subject_id)->update(['instructor_id' => $request->teacher_id]);
        }

        Schedule::updateOrCreate(
            [
                'subject_id' => $request->subject_id,
                'day' => $request->day_of_week,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ],
            [
                'room' => trim($request->room),
            ]
        );

        return redirect()->route('admin.class-schedules.index')->with('success', 'Class schedule updated successfully.');
    }

    public function destroy(ClassSchedule $classSchedule)
    {
        // Also remove matching Schedule entry if exists
        Schedule::where('subject_id', $classSchedule->subject_id)
            ->where('day', $classSchedule->day_of_week)
            ->where('start_time', $classSchedule->start_time)
            ->delete();

        $classSchedule->delete();
        return redirect()->route('admin.class-schedules.index')->with('success', 'Class schedule deleted successfully.');
    }
}
