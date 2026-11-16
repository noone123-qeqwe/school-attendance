<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'semester',
        'start_date',
        'end_date',
        'is_current'
    ];

    protected $casts = [
        'semester' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean'
    ];

    public function getSemesterLabelAttribute(): string
    {
        return match ((int) $this->semester) {
            1 => '1st Semester',
            2 => '2nd Semester',
            3 => 'Summer Term',
            default => "Semester {$this->semester}",
        };
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
