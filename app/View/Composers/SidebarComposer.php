<?php

namespace App\View\Composers;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\ExcuseSubmission;
use App\Models\Notification;
use App\Models\Subject;
use App\Services\FeatureFlagService;
use App\Services\StudentScheduleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Provides pre-computed, per-request memoized navigation state for sidebars
 * and the student portal shell without embedding Eloquent queries inside Blade templates.
 */
class SidebarComposer
{
    public function compose(View $view): void
    {
        $viewName = $view->name();

        if (
            in_array($viewName, [
                'layouts.student-shell',
                'layouts.sidebars.student',
                'layouts.sidebars.settings',
                'home',
                'student.classes',
                'student.schedule',
                'student.calendar',
                'excuses.index',
                'excuses.create',
                'excuses.create_general',
                'notifications',
                'settings',
                'student.profile',
                'mobile.scan',
                'mobile.peer-snap',
            ], true)
            || str_starts_with($viewName, 'student-assistant.')
        ) {
            $shell = $this->resolveStudentShellData();
            $view->with([
                'hasActiveAssistantAssignment' => $shell['hasActiveAssistantAssignment'],
                'studentShellData' => $shell,
            ]);
            return;
        }

        if (in_array($viewName, ['layouts.sidebars.teacher', 'partials.teacher-navigation'], true)) {
            $view->with($this->resolveTeacherPendingCounts());
        }
    }

    /**
     * @return array{pendingExcusesCount: int, pendingCorrectionsCount: int}
     */
    private function resolveTeacherPendingCounts(): array
    {
        $default = ['pendingExcusesCount' => 0, 'pendingCorrectionsCount' => 0];
        $teacher = Auth::user();
        if (!$teacher || !$teacher->isTeacher()) {
            return $default;
        }

        $request = request();
        $attrKey = "sidebar_teacher_counts_{$teacher->id}";
        if ($request->attributes->has($attrKey)) {
            return $request->attributes->get($attrKey);
        }

        $resolver = function () use ($teacher, $default): array {
            try {
                $subjectCodes = Subject::query()
                    ->where('instructor_id', $teacher->id)
                    ->orWhere('instructor', $teacher->name)
                    ->pluck('code');

                if ($subjectCodes->isEmpty()) {
                    return $default;
                }

                return [
                    'pendingExcusesCount' => ExcuseSubmission::query()
                        ->where('status', 'pending')
                        ->where('user_id', '!=', $teacher->id)
                        ->whereHas('attendance', fn ($q) => $q->whereIn('subject_code', $subjectCodes))
                        ->count(),
                    'pendingCorrectionsCount' => AttendanceCorrection::query()
                        ->where('status', 'pending')
                        ->whereHas('attendance', fn ($q) => $q->whereIn('subject_code', $subjectCodes))
                        ->count(),
                ];
            } catch (\Throwable) {
                return $default;
            }
        };

        $counts = app()->runningUnitTests()
            ? $resolver()
            : Cache::remember("sidebar:teacher_counts:{$teacher->id}", now()->addSeconds(20), $resolver);

        $request->attributes->set($attrKey, $counts);

        return $counts;
    }

    /**
     * @return array{
     *     hasActiveAssistantAssignment: bool,
     *     classesCount: int,
     *     recordsCount: int,
     *     excusesCount: int,
     *     pendingExcusesCount: int,
     *     unreadNotificationsCount: int,
     *     initials: string,
     *     shortName: string,
     *     subjectsQuickList: list<array{code: string, name: string, room: string, instructor: string}>
     * }
     */
    private function resolveStudentShellData(): array
    {
        $default = [
            'hasActiveAssistantAssignment' => false,
            'classesCount' => 0,
            'recordsCount' => 0,
            'excusesCount' => 0,
            'pendingExcusesCount' => 0,
            'unreadNotificationsCount' => 0,
            'initials' => 'ST',
            'shortName' => 'Student',
            'subjectsQuickList' => [],
        ];

        $student = Auth::user();
        if (!$student?->isStudent() || !$student->isActive()) {
            return $default;
        }

        $request = request();
        $attrKey = "student_shell_data_{$student->id}";
        if ($request->attributes->has($attrKey)) {
            return $request->attributes->get($attrKey);
        }

        $nameParts = preg_split('/\s+/', trim((string) $student->name)) ?: [];
        $firstInitial = mb_strtoupper(mb_substr($nameParts[0] ?? 'S', 0, 1));
        $lastInitial = count($nameParts) > 1
            ? mb_strtoupper(mb_substr($nameParts[count($nameParts) - 1], 0, 1))
            : mb_strtoupper(mb_substr($nameParts[0] ?? 'T', 1, 1));
        $initials = trim($firstInitial . $lastInitial) ?: 'ST';

        if (count($nameParts) >= 3) {
            $shortName = $nameParts[0] . ' ' . $nameParts[1] . ' ' . mb_strtoupper(mb_substr($nameParts[count($nameParts) - 1], 0, 1)) . '.';
        } elseif (count($nameParts) === 2) {
            $shortName = $nameParts[0] . ' ' . mb_strtoupper(mb_substr($nameParts[1], 0, 1)) . '.';
        } else {
            $shortName = (string) ($student->name ?: 'Student');
        }

        $hasAssistant = false;
        if (app(FeatureFlagService::class)->enabled('feature.student_assistant_qr')) {
            try {
                $hasAssistant = $student->studentAssistantAssignments()
                    ->whereNotNull('active_slot')
                    ->whereNull('revoked_at')
                    ->where('starts_at', '<=', now())
                    ->where('expires_at', '>=', now())
                    ->exists();
            } catch (\Throwable) {
                $hasAssistant = false;
            }
        }

        try {
            $subjects = app(StudentScheduleService::class)->subjectsFor($student);
            $subjectsQuickList = $subjects->map(function (Subject $subject): array {
                $firstSlot = $subject->schedules->first();
                $room = trim((string) ($firstSlot?->room ?? ''));
                return [
                    'code' => (string) $subject->code,
                    'name' => (string) $subject->name,
                    'room' => ($room !== '' && strtoupper($room) !== 'TBA') ? $room : 'Room TBA',
                    'instructor' => (string) ($subject->instructorUser?->name ?? $subject->instructor ?? 'Instructor TBA'),
                ];
            })->values()->all();

            $recordsCount = Attendance::where('user_id', $student->id)->count();
            $excusesCount = ExcuseSubmission::where('user_id', $student->id)->count();
            $pendingExcusesCount = ExcuseSubmission::where('user_id', $student->id)->where('status', 'pending')->count();
            $unreadNotificationsCount = Notification::where('user_id', $student->id)
                ->active()
                ->where('is_read', false)
                ->count();

            $data = [
                'hasActiveAssistantAssignment' => $hasAssistant,
                'classesCount' => $subjects->count(),
                'recordsCount' => $recordsCount,
                'excusesCount' => $excusesCount,
                'pendingExcusesCount' => $pendingExcusesCount,
                'unreadNotificationsCount' => $unreadNotificationsCount,
                'initials' => $initials,
                'shortName' => $shortName,
                'subjectsQuickList' => $subjectsQuickList,
            ];
        } catch (\Throwable) {
            $data = array_merge($default, [
                'hasActiveAssistantAssignment' => $hasAssistant,
                'initials' => $initials,
                'shortName' => $shortName,
            ]);
        }

        $request->attributes->set($attrKey, $data);

        return $data;
    }
}
