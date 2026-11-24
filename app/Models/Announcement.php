<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'target_audience',
        'target_id',
        'author_id',
        'scheduled_for',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Scope: only announcements that are published (scheduled_for is null or in the past).
     */
    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('scheduled_for')
              ->orWhere('scheduled_for', '<=', now());
        });
    }

    /**
     * Scope: announcements visible to a given user across roles and enrollments.
     */
    public function scopeVisibleTo($query, $user)
    {
        if (!$user instanceof User) {
            return $query->where('target_audience', 'All');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        $courseNames = [];
        $sectionNames = [];
        $departmentNames = [];
        $subjectIds = [];
        $roleAudiences = ['All'];

        if ($user->isStudent()) {
            $roleAudiences[] = 'Student';
            if (!empty($user->course)) $courseNames[] = (string) $user->course;
            if (!empty($user->section)) $sectionNames[] = (string) $user->section;
            if (!empty($user->department)) $departmentNames[] = (string) $user->department;
            $subjectIds = $user->getAllSubjects()->pluck('id')->map(fn ($id) => (int) $id)->all();
        } elseif ($user->isParent()) {
            $roleAudiences[] = 'Parent';
            foreach ($user->children()->get() as $child) {
                if (!empty($child->course)) $courseNames[] = (string) $child->course;
                if (!empty($child->section)) $sectionNames[] = (string) $child->section;
                if (!empty($child->department)) $departmentNames[] = (string) $child->department;
                foreach ($child->getAllSubjects() as $subj) {
                    $subjectIds[] = (int) $subj->id;
                }
            }
        } elseif ($user->isTeacher()) {
            $roleAudiences[] = 'Teacher';
            if (!empty($user->department)) $departmentNames[] = (string) $user->department;
            $subjectIds = $user->teachingSubjects()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $courseIds = $this->resolveCourseTargetIds($courseNames);
        $sectionIds = $this->resolveSectionTargetIds($sectionNames);
        $departmentIds = $this->resolveDepartmentTargetIds($departmentNames);
        $subjectIds = array_values(array_unique(array_filter($subjectIds)));

        return $query->where(function ($q) use ($roleAudiences, $courseIds, $sectionIds, $departmentIds, $subjectIds) {
            $q->whereIn('target_audience', $roleAudiences);

            if ($courseIds !== []) {
                $q->orWhere(function ($q2) use ($courseIds) {
                    $q2->where('target_audience', 'Course')->whereIn('target_id', $courseIds);
                });
            }
            if ($sectionIds !== []) {
                $q->orWhere(function ($q2) use ($sectionIds) {
                    $q2->where('target_audience', 'Section')->whereIn('target_id', $sectionIds);
                });
            }
            if ($departmentIds !== []) {
                $q->orWhere(function ($q2) use ($departmentIds) {
                    $q2->where('target_audience', 'Department')->whereIn('target_id', $departmentIds);
                });
            }
            if ($subjectIds !== []) {
                $q->orWhere(function ($q2) use ($subjectIds) {
                    $q2->where('target_audience', 'Subject')->whereIn('target_id', $subjectIds);
                });
            }
        });
    }

    /** @param string[] $names @return int[] */
    private function resolveCourseTargetIds(array $names): array
    {
        $clean = array_values(array_unique(array_filter(array_map('trim', $names))));
        if ($clean === []) {
            return [];
        }

        return Course::whereIn('code', $clean)
            ->orWhereIn('name', $clean)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @param string[] $names @return int[] */
    private function resolveSectionTargetIds(array $names): array
    {
        $clean = array_values(array_unique(array_filter(array_map('trim', $names))));
        if ($clean === []) {
            return [];
        }

        return Section::whereIn('name', $clean)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @param string[] $names @return int[] */
    private function resolveDepartmentTargetIds(array $names): array
    {
        $clean = array_values(array_unique(array_filter(array_map('trim', $names))));
        if ($clean === []) {
            return [];
        }

        return Department::whereIn('code', $clean)
            ->orWhereIn('name', $clean)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
