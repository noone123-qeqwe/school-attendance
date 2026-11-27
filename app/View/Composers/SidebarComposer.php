<?php

namespace App\View\Composers;

use App\Models\AttendanceCorrection;
use App\Models\ExcuseSubmission;
use App\Models\Subject;
use App\Services\FeatureFlagService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Provides pre-computed, per-request memoized navigation state for sidebars
 * without embedding Eloquent queries inside Blade templates.
 */
class SidebarComposer
{
    public function compose(View $view): void
    {
        $viewName = $view->name();

        if (in_array($viewName, ['layouts.sidebars.student', 'home', 'student.classes'], true)) {
            $view->with('hasActiveAssistantAssignment', $this->resolveStudentAssistantStatus());
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

    private function resolveStudentAssistantStatus(): bool
    {
        $student = Auth::user();
        if (!$student?->isStudent() || !$student->isActive()) {
            return false;
        }

        $request = request();
        $attrKey = "sidebar_student_assistant_{$student->id}";
        if ($request->attributes->has($attrKey)) {
            return (bool) $request->attributes->get($attrKey);
        }

        if (!app(FeatureFlagService::class)->enabled('feature.student_assistant_qr')) {
            $request->attributes->set($attrKey, false);

            return false;
        }

        $resolver = function () use ($student): bool {
            try {
                return $student->studentAssistantAssignments()
                    ->whereNotNull('active_slot')
                    ->whereNull('revoked_at')
                    ->where('starts_at', '<=', now())
                    ->where('expires_at', '>=', now())
                    ->exists();
            } catch (\Throwable) {
                return false;
            }
        };

        $active = app()->runningUnitTests()
            ? $resolver()
            : Cache::remember("sidebar:student_assistant:{$student->id}", now()->addSeconds(20), $resolver);

        $request->attributes->set($attrKey, $active);

        return $active;
    }
}
