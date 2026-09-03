<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AttendanceCorrection extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'attendance_id',
        'student_id',
        'reason',
        'status',
        'teacher_notes',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'teacher_notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
