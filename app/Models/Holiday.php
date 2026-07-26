<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'name',
        'description',
        'type',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    // Helper methods
    public static function isHoliday($date)
    {
        return self::active()->forDate($date)->exists();
    }

    public static function getHoliday($date)
    {
        return self::active()->forDate($date)->first();
    }

    public function getTypeColorAttribute()
    {
        return match($this->type) {
            'national' => '#dc2626', // Red
            'local' => '#d97706',     // Orange
            'school' => '#7c2d12',    // Brown
            'no_class' => '#6366f1',  // Indigo
            default => '#6b7280'      // Gray
        };
    }

    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'national' => 'National Holiday',
            'local' => 'Local Holiday',
            'school' => 'School Holiday',
            'no_class' => 'No Classes',
            default => 'Holiday'
        };
    }
}
