<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'year_level',
        'semester',
        'course',
        'units',
        'instructor',
        'section',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
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

