<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'date',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'date' => 'date',
        'excused' => 'boolean',
    ];

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
                        $parent->notify(new \App\Notifications\AbsenceAlert($attendance));
                    }
                }
            }
        });
        
        static::created(function ($attendance) {
            if ($attendance->status === 'Absent') {
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
                        $parent->notify(new \App\Notifications\AbsenceAlert($attendance));
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

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function setSubjectCodeAttribute($value)
    {
        $this->attributes['subject_code'] = $value;
        if ($value) {
            $subject = \App\Models\Subject::where('code', $value)->first();
            if ($subject) {
                $this->attributes['subject_id'] = $subject->id;
            }
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function excuseSubmission()
    {
        return $this->hasOne(ExcuseSubmission::class);
    }
}