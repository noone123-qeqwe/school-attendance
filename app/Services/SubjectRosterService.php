<?php

namespace App\Services;

use App\Models\Subject;
use Illuminate\Support\Collection;

final class SubjectRosterService
{
    /**
     * Resolve the same explicit and profile-based roster used by Subject::hasStudent().
     *
     * @param Collection<int, Subject> $subjects
     * @return array<int, int>
     */
    public static function studentIds(Collection $subjects): array
    {
        return $subjects
            ->flatMap(fn (Subject $subject) => $subject->getAllStudents()->pluck('id'))
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
