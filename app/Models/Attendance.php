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
            if ($attendance->status === 'Absent' && $attendance->wasChanged('status') && !$attendance->excused) {
                try {
                    $student = $attendance->user;
                    if (!$student) {
                        return;
                    }

                    $formattedDate = $attendance->date ? $attendance->date->format('M d, Y') : 'Session';
                    $signedUrl = \Illuminate\Support\Facades\URL::signedRoute('guest.excuse', ['attendance' => $attendance->id]);

                    \App\Models\Notification::create([
                        'user_id' => $attendance->user_id,
                        'type' => 'absence_alert',
                        'subject_code' => $attendance->subject_code,
                        'message' => "{$student->name} was marked Absent in {$attendance->subject_code} on {$formattedDate}.",
                        'is_read' => false
                    ]);
                    
                    $parents = $student->parents;
                    if ($parents && $parents->isNotEmpty()) {
                        foreach ($parents as $parent) {
                            \App\Models\Notification::create([
                                'user_id' => $parent->id,
                                'type' => 'absence_alert',
                                'subject_code' => $attendance->subject_code,
                                'message' => "{$student->name} was marked Absent in {$attendance->subject_code} on {$formattedDate}.",
                                'is_read' => false
                            ]);

                            try {
                                $parent->notify(new \App\Notifications\AbsenceAlert($attendance, $signedUrl));
                            } catch (\Throwable $notifEx) {
                                \Illuminate\Support\Facades\Log::warning('Parent AbsenceAlert dispatch warning: ' . $notifEx->getMessage());
                            }
                        }
                    } elseif (!empty($student->guardian_email)) {
                        try {
                            \Illuminate\Support\Facades\Notification::route('mail', $student->guardian_email)
                                ->notify(new \App\Notifications\AbsenceAlert($attendance, $signedUrl));
                        } catch (\Throwable $notifEx) {
                            \Illuminate\Support\Facades\Log::warning('Guardian email AbsenceAlert warning: ' . $notifEx->getMessage());
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Attendance saved notification warning: ' . $e->getMessage());
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

    /**
     * Safely update or create an attendance record, handling soft-deleted rows.
     */
    public static function updateOrCreateRecord(array $attributes, array $values = []): self
    {
        $instance = static::withTrashed()->where($attributes)->first();

        if ($instance) {
            if ($instance->trashed()) {
                $instance->restore();
            }
            $instance->fill($values);
            $instance->save();

            return $instance;
        }

        try {
            return static::create(array_merge($attributes, $values));
        } catch (\Illuminate\Database\QueryException $e) {
            $instance = static::withTrashed()->where($attributes)->first();
            if ($instance) {
                if ($instance->trashed()) {
                    $instance->restore();
                }
                $instance->fill($values);
                $instance->save();

                return $instance;
            }
            throw $e;
        }
    }
}