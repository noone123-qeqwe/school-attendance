<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'date',
        'start_time',
        'end_time',
        'created_by',
        'type',
        'status',
        'location',
        'organizer_id',
        'class_id',
        'original_start',
        'original_end',
        'original_location',
        'rescheduled_by',
        'rescheduled_at',
        'reschedule_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'original_start' => 'datetime:H:i',
        'original_end' => 'datetime:H:i',
        'rescheduled_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'class_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }
    
    public function attendees()
    {
        return $this->belongsToMany(User::class, 'event_attendees', 'event_id', 'user_id')
                    ->withPivot('response', 'decline_reason')
                    ->withTimestamps();
    }
    
    public function attendeeGroups(): HasMany
    {
        return $this->hasMany(EventAttendeeGroup::class);
    }
    
    public function rescheduleLogs(): HasMany
    {
        return $this->hasMany(EventRescheduleLog::class);
    }
    
    public function scopeVisibleTo($query, $user)
    {
        return $query->where(function($q) use ($user) {
            if ($user->isAdmin()) {
                return;
            }
            
            $q->whereIn('type', ['school_event', 'holiday'])
              ->orWhere(function($subQ) use ($user) {
                  $subQ->where('type', 'meeting')
                       ->where(function($sq) use ($user) {
                           $sq->where('organizer_id', $user->id)
                              ->orWhereHas('attendees', function($aq) use ($user) {
                                  $aq->where('user_id', $user->id);
                              });
                       });
              })
              ->orWhere(function($subQ) use ($user) {
                  $subQ->whereIn('type', ['class', 'exam']);
                  
                  if ($user->isTeacher()) {
                      $subQ->whereIn('class_id', $user->subjects()->pluck('id'));
                  } elseif ($user->isStudent()) {
                      $subQ->whereIn('class_id', $user->enrolledSubjects()->pluck('subjects.id'));
                  }
              });
        });
    }

    public function scopeEditableBy($query, $user)
    {
        return $query->where(function($q) use ($user) {
            if ($user->isAdmin()) {
                return;
            }
            
            if ($user->isTeacher()) {
                $q->where(function($sq) use ($user) {
                    $sq->where('type', 'meeting')
                       ->where('organizer_id', $user->id);
                })
                ->orWhere(function($sq) use ($user) {
                    $sq->whereIn('type', ['class', 'exam'])
                       ->whereIn('class_id', $user->subjects()->pluck('id'));
                });
            }
        });
    }
}
