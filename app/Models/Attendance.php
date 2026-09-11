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
        'subject_name',
        'class',
        'session_id',
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
        'checked_in_at',
        'last_location_check_at',
        'last_latitude',
        'last_longitude',
        'last_accuracy',
        'last_distance_meters',
        'outside_since',
        'consecutive_outside_count',
        'escaped_at',
        'monitoring_status',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'excused' => 'boolean',
        'checked_in_at' => 'datetime',
        'last_location_check_at' => 'datetime',
        'outside_since' => 'datetime',
        'escaped_at' => 'datetime',
        'consecutive_outside_count' => 'integer',
        'last_distance_meters' => 'float',
        'last_accuracy' => 'float',
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
        return $this->belongsTo(Subject::class, 'subject_id')->withTrashed();
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }

    public function setSubjectCodeAttribute($value)
    {
        $this->attributes['subject_code'] = $value;
        if ($value && !isset($this->attributes['subject_id'])) {
            $subject = \App\Models\Subject::where('code', $value)->first();
            if ($subject) {
                $this->attributes['subject_id'] = $subject->id;
                if (!isset($this->attributes['subject_name'])) {
                    $this->attributes['subject_name'] = $subject->name;
                }
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
     * Safely update or create an attendance record, handling soft-deleted rows and composite keys.
     */
    public static function updateOrCreateRecord(array $attributes, array $values = []): self
    {
        $allData = array_merge($attributes, $values);
        $userId = $allData['user_id'] ?? ($attributes['user_id'] ?? null);
        $date = isset($allData['date']) ? \Carbon\Carbon::parse($allData['date'])->format('Y-m-d') : null;
        $subjectId = $allData['subject_id'] ?? ($attributes['subject_id'] ?? null);
        $subjectCode = $allData['subject_code'] ?? ($attributes['subject_code'] ?? null);

        // Auto-resolve subject_id and subject_name if missing
        if (!$subjectId && $subjectCode) {
            $subject = \App\Models\Subject::where('code', $subjectCode)->first();
            if ($subject) {
                $subjectId = $subject->id;
                $allData['subject_id'] = $subject->id;
                if (empty($allData['subject_name'])) {
                    $allData['subject_name'] = $subject->name;
                }
            }
        } elseif ($subjectId && empty($allData['subject_name'])) {
            $subject = \App\Models\Subject::find($subjectId);
            if ($subject) {
                $allData['subject_name'] = $subject->name;
                if (empty($allData['subject_code'])) {
                    $allData['subject_code'] = $subject->code;
                }
            }
        }

        // 1. Primary lookup: user_id + date + (subject_id or subject_code)
        $findExisting = function () use ($attributes, $userId, $date, $subjectId, $subjectCode) {
            $query = static::withTrashed();
            if ($userId && $date) {
                $query->where('user_id', $userId)->where('date', $date);
                $query->where(function ($q) use ($subjectId, $subjectCode) {
                    if ($subjectId && $subjectCode) {
                        $q->where('subject_id', $subjectId)
                          ->orWhere('subject_code', $subjectCode);
                    } elseif ($subjectId) {
                        $q->where('subject_id', $subjectId);
                    } elseif ($subjectCode) {
                        $q->where('subject_code', $subjectCode);
                    }
                });
                return $query->first();
            }
            return static::withTrashed()->where($attributes)->first();
        };

        $instance = $findExisting();

        if ($instance) {
            if ($instance->trashed()) {
                $instance->restore();
            }
            $instance->fill($allData);
            $instance->save();

            return $instance;
        }

        try {
            return static::create($allData);
        } catch (\Illuminate\Database\QueryException $e) {
            // Check for unique key constraint collision (code 23000 or 23505)
            $instance = $findExisting();
            if ($instance) {
                if ($instance->trashed()) {
                    $instance->restore();
                }
                $instance->fill($allData);
                $instance->save();

                return $instance;
            }
            throw $e;
        }
    }
}