<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\AcademicYear;
use App\Models\SchedulePublication;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Schedule;
use App\Models\User;
use App\Rules\ValidScheduleSlot;
use App\Services\ClassScheduleLock;
use App\Services\SchedulePublicationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        $academicYear = AcademicYear::current();
        $publication = $academicYear
            ? SchedulePublication::query()->where('academic_year_id', $academicYear->id)->first()
            : null;
        $publicationStatus = $academicYear ? app(SchedulePublicationService::class)->status($academicYear) : null;
        $isSchedulePublished = $publication && $publicationStatus
            && hash_equals($publication->schedule_hash, $publicationStatus['hash']);

        return view('admin.class_schedules.index', compact(
            'schedules', 'subjects', 'sections', 'teachers', 'stats',
            'academicYear', 'publication', 'publicationStatus', 'isSchedulePublished',
        ));
    }

    public function publish(Request $request, SchedulePublicationService $publicationService)
    {
        $result = DB::transaction(function () use ($request, $publicationService) {
            ClassScheduleLock::acquire();
            $academicYear = AcademicYear::query()->current()->lockForUpdate()->first();
            if (!$academicYear) {
                throw ValidationException::withMessages([
                    'publish' => 'Set a current academic term before publishing schedules.',
                ]);
            }

            return $publicationService->publish($academicYear, (int) $request->user()->id);
        }, 3);

        if (!$result['published']) {
            return redirect()->route('admin.class-schedules.index')
                ->with('info', 'These schedules are already published. No duplicate notifications were sent.');
        }

        return redirect()->route('admin.class-schedules.index')->with(
            'success',
            "Schedules published. {$result['notified']} student and teacher notification(s) sent.",
        );
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            ClassScheduleLock::acquire();
            $subject = $this->validateScheduleRequest($request);

            $startTime = Carbon::parse($request->start_time)->format('H:i');
            $endTime = Carbon::parse($request->end_time)->format('H:i');
            $room = trim($request->room);
            $slot = $this->attendanceSlot((int) $request->subject_id, $request->day_of_week, $startTime, $endTime);

            ClassSchedule::create([
                'subject_id' => $request->subject_id,
                'teacher_id' => $request->teacher_id,
                'section_id' => $request->section_id,
                'day_of_week' => $request->day_of_week,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $room,
            ]);

            $this->assignUnownedSubject($subject, (int) $request->teacher_id);
            $this->syncAttendanceSchedule(null, $slot, $room);
        }, 3);

        return redirect()->route('admin.class-schedules.index')->with('success', 'Class schedule assigned successfully.');
    }

    public function update(Request $request, ClassSchedule $classSchedule)
    {
        DB::transaction(function () use ($request, $classSchedule) {
            ClassScheduleLock::acquire();
            $classSchedule = ClassSchedule::findOrFail($classSchedule->id);
            $subject = $this->validateScheduleRequest($request, $classSchedule);

            $startTime = Carbon::parse($request->start_time)->format('H:i');
            $endTime = Carbon::parse($request->end_time)->format('H:i');
            $oldSlot = $this->attendanceSlot(
                (int) $classSchedule->subject_id,
                $classSchedule->day_of_week,
                Carbon::parse($classSchedule->start_time)->format('H:i'),
                Carbon::parse($classSchedule->end_time)->format('H:i'),
            );
            $newSlot = $this->attendanceSlot((int) $request->subject_id, $request->day_of_week, $startTime, $endTime);
            $room = trim($request->room);

            $classSchedule->update([
                'subject_id' => $request->subject_id,
                'teacher_id' => $request->teacher_id,
                'section_id' => $request->section_id,
                'day_of_week' => $request->day_of_week,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $room,
            ]);

            $this->assignUnownedSubject($subject, (int) $request->teacher_id);
            $this->syncAttendanceSchedule($oldSlot, $newSlot, $room);
        }, 3);

        return redirect()->route('admin.class-schedules.index')->with('success', 'Class schedule updated successfully.');
    }

    private function validateScheduleRequest(Request $request, ?ClassSchedule $current = null): Subject
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => ['required', Rule::exists('users', 'id')->where('role', 'teacher')],
            'section_id' => 'required|exists:sections,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|after:start_time',
            'room' => [
                'required',
                'string',
                'max:50',
                new ValidScheduleSlot(
                    (string) $request->input('day_of_week'),
                    (string) $request->input('start_time'),
                    (string) $request->input('end_time'),
                    $request->filled('teacher_id') ? (int) $request->input('teacher_id') : null,
                    (string) $request->input('room'),
                    $current?->id,
                    $request->filled('section_id') ? (int) $request->input('section_id') : null,
                ),
            ],
        ]);

        $subject = Subject::findOrFail((int) $request->subject_id);
        $teacherId = (int) $request->teacher_id;
        if ($subject->instructor_id !== null && (int) $subject->instructor_id !== $teacherId) {
            throw ValidationException::withMessages(['teacher_id' => 'This subject is assigned to another instructor. Update the subject assignment first.']);
        }

        $differentTeacherAssigned = ClassSchedule::query()
            ->where('subject_id', $subject->id)
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->where('teacher_id', '!=', $teacherId)
            ->exists();
        if ($differentTeacherAssigned) {
            throw ValidationException::withMessages(['teacher_id' => 'All schedules for a subject must use the same instructor.']);
        }

        $section = Section::with('course')->findOrFail((int) $request->section_id);
        if ((int) $subject->year_level !== (int) $section->year_level
            || (filled($subject->course) && strcasecmp(trim((string) $subject->course), trim((string) $section->course?->code)) !== 0)
            || (filled($subject->section) && strcasecmp(trim((string) $subject->section), trim((string) $section->name)) !== 0)) {
            throw ValidationException::withMessages(['section_id' => 'The section must match the subject course, year level, and section.']);
        }

        return $subject;
    }

    public function destroy(ClassSchedule $classSchedule)
    {
        DB::transaction(function () use ($classSchedule) {
            ClassScheduleLock::acquire();
            $classSchedule = ClassSchedule::findOrFail($classSchedule->id);
            $slot = $this->attendanceSlot(
                (int) $classSchedule->subject_id,
                $classSchedule->day_of_week,
                Carbon::parse($classSchedule->start_time)->format('H:i'),
                Carbon::parse($classSchedule->end_time)->format('H:i'),
            );
            $classSchedule->delete();
            $this->removeAttendanceScheduleIfUnused($slot);
        }, 3);

        return redirect()->route('admin.class-schedules.index')->with('success', 'Class schedule deleted successfully.');
    }

    /** @return array{subject_id: int, day: string, start_time: string, end_time: string} */
    private function attendanceSlot(int $subjectId, string $day, string $startTime, string $endTime): array
    {
        return [
            'subject_id' => $subjectId,
            'day' => $day,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }

    private function assignUnownedSubject(Subject $subject, int $teacherId): void
    {
        if ($subject->instructor_id === null) {
            $teacher = User::findOrFail($teacherId);
            $subject->update(['instructor_id' => $teacherId, 'instructor' => $teacher->name]);
        }
    }

    /**
     * @param array{subject_id: int, day: string, start_time: string, end_time: string} $oldSlot
     * @param array{subject_id: int, day: string, start_time: string, end_time: string} $newSlot
     */
    private function syncAttendanceSchedule(?array $oldSlot, array $newSlot, string $room): void
    {
        if ($oldSlot !== null && $oldSlot !== $newSlot) {
            $this->removeAttendanceScheduleIfUnused($oldSlot);
        }

        Schedule::updateOrCreate($newSlot, ['room' => $room]);
    }

    /** @param array{subject_id: int, day: string, start_time: string, end_time: string} $slot */
    private function removeAttendanceScheduleIfUnused(array $slot): void
    {
        $stillAssigned = ClassSchedule::query()
            ->where('subject_id', $slot['subject_id'])
            ->where('day_of_week', $slot['day'])
            ->where('start_time', $slot['start_time'])
            ->where('end_time', $slot['end_time'])
            ->exists();

        if (!$stillAssigned) {
            Schedule::where($slot)->delete();
        }
    }
}
