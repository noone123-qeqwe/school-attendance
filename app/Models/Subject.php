<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'year_level',
        'semester',
        'course',
        'units',
        'instructor',
        'instructor_id',
        'section',
    ];

    public function instructorUser()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function materials()
    {
        return $this->hasMany(SubjectMaterial::class);
    }

    public function enrolledStudents()
    {
        return $this->belongsToMany(User::class, 'enrollments', 'subject_id', 'user_id')
                    ->where('role', 'student')
                    ->withTimestamps();
    }

    /**
     * Get all students for this subject (both explicitly enrolled and implicitly via year level / semester)
     */
    public function getAllStudents()
    {
        $explicit = $this->enrolledStudents()->get();
        
        $query = User::where('role', 'student')
            ->where('year_level', $this->year_level)
            ->where('semester', $this->semester);
            
        if (!empty($this->course)) {
            $query->where('course', $this->course);
        }
        
        if (!empty($this->section)) {
            $query->where('section', $this->section);
        }
            
        $implicit = $query->get();
            
        return $explicit->merge($implicit)->unique('id')->values();
    }

    // Accessor to get days as a string (for backward compatibility)
   public function getDaysAttribute(): string
{
    $dayOrder = [
        'Monday'    => 1,
        'Tuesday'   => 2,
        'Wednesday' => 3,
        'Thursday'  => 4,
        'Friday'    => 5,
        'Saturday'  => 6,
        'Sunday'    => 7,
    ];

    $letter = [
        'Monday'    => 'M',
        'Tuesday'   => 'T',
        'Wednesday' => 'W',
        'Thursday'  => 'TH',
        'Friday'    => 'F',
        'Saturday'  => 'S',
        'Sunday'    => 'SUN',
    ];

    if (!$this->relationLoaded('schedules')) {
        $this->load('schedules');
    }

    return $this->schedules
        ->pluck('day')
        ->unique()
        ->values()
        ->sortBy(fn(string $d) => $dayOrder[$d] ?? 99)
        ->values()
        ->map(fn(string $d) => $letter[$d] ?? '')
        ->filter()
        ->implode('');
}

    // Accessor for start_time (get the earliest start time)
    public function getStartTimeAttribute()
    {
        return $this->schedules->min('start_time');
    }

    // Accessor for end_time (get the latest end time)
    public function getEndTimeAttribute()
    {
        return $this->schedules->max('end_time');
    }
}

