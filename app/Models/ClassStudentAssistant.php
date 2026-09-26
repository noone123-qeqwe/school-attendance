<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassStudentAssistant extends Model
{
    protected $fillable = [
        'subject_id', 'student_id', 'assigned_by_teacher_id',
        'active_slot', 'starts_at', 'expires_at', 'revoked_at',
    ];

    protected $casts = [
        'active_slot' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function assigningTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_teacher_id');
    }

    public function isActive(): bool
    {
        return $this->active_slot !== null
            && $this->revoked_at === null
            && $this->starts_at <= now()
            && $this->expires_at >= now()
            && $this->student?->isStudent()
            && $this->student->isActive();
    }

    public function getStatusAttribute(): string
    {
        if ($this->revoked_at !== null) {
            return 'Revoked';
        }

        if ($this->expires_at < now()) {
            return 'Expired';
        }

        return $this->starts_at > now() ? 'Scheduled' : 'Active';
    }
}
