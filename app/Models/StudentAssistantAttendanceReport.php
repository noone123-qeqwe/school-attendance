<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAssistantAttendanceReport extends Model
{
    public const SUGGESTED_STATUSES = ['present', 'late'];

    public const REVIEW_STATUSES = ['pending', 'approved', 'dismissed'];

    protected $fillable = [
        'attendance_session_id', 'student_id', 'submitted_by', 'reviewed_by', 'attendance_id',
        'suggested_status', 'review_status', 'reason', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    /** @return BelongsTo<AttendanceSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
