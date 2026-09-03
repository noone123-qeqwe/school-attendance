<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class Attendance extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'subject_id',
        'subject_code',
        'status',
        'excused',
        'excuse_note',
        'time_in',
        'time_out',
        'date',
        'latitude',
        'longitude',
        'gps_accuracy',
        'method',
        'device_id',
        'academic_year_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'excused' => 'boolean',
    ];

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = $value ? ucfirst(strtolower(trim($value))) : 'Absent';
    }

    public function getStatusAttribute($value)
    {
        return $value ? ucfirst(strtolower(trim($value))) : 'Absent';
    }

    protected static function booted()
    {
        static::saved(function ($attendance) {
            if ($attendance->status === 'Absent' && $attendance->wasChanged('status')) {
                \App\Models\Notification::create([
                    'user_id' => $attendance->user_id,
                    'type' => 'absence_alert',
                    'subject_code' => $attendance->subject_code,
                    'message' => "{$attendance->user->name} was marked Absent in {$attendance->subject_code} on {$attendance->date->format('M d, Y')}.",
                    'is_read' => false
                ]);
                
                $parents = $attendance->user->parents;
                if ($parents) {
                    foreach ($parents as $parent) {
                        $prefs = collect($parent->notification_preferences ?? ['in_app' => true, 'email' => true]);
                        
                        if ($prefs->get('in_app')) {
                            \App\Models\Notification::create([
                                'user_id' => $parent->id,
                                'type' => 'absence_alert',
                                'subject_code' => $attendance->subject_code,
                                'message' => "{$attendance->user->name} was marked Absent in {$attendance->subject_code} on {$attendance->date->format('M d, Y')}.",
                                'is_read' => false
                            ]);
                        }
                        
                        // We would trigger Email/SMS here based on $prefs->get('email')
                    }
                }
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function setSubjectCodeAttribute($value)
    {
        $this->attributes['subject_code'] = $value;
        if ($value && !isset($this->attributes['subject_id'])) {
            $subject = \App\Models\Subject::where('code', $value)->first();
            if ($subject) {
                $this->attributes['subject_id'] = $subject->id;
            }
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function excuseSubmission(): HasOne
    {
        return $this->hasOne(ExcuseSubmission::class);
    }

    public function correction(): HasOne
    {
        return $this->hasOne(AttendanceCorrection::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}