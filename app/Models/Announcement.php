<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function author()
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
     * Scope: announcements visible to a given student user.
     */
    public function scopeVisibleTo($query, $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('target_audience', 'All')
              ->orWhere(function ($q2) use ($user) {
                  $q2->where('target_audience', 'Course')
                     ->where('target_id', $user->course_id ?? 0);
              })
              ->orWhere(function ($q2) use ($user) {
                  $q2->where('target_audience', 'Section')
                     ->where('target_id', $user->section_id ?? 0);
              })
              ->orWhere(function ($q2) use ($user) {
                  $q2->where('target_audience', 'Department')
                     ->where('target_id', $user->department_id ?? 0);
              });
        });
    }
}
