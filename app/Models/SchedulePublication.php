<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchedulePublication extends Model
{
    protected $fillable = [
        'academic_year_id',
        'published_by',
        'schedule_hash',
        'recipient_hashes',
        'published_at',
    ];

    protected $casts = [
        'recipient_hashes' => 'array',
        'published_at' => 'datetime',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
