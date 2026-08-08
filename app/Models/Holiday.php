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
        return $query->whereDate('date', $date);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    // Helper methods
    public static function isHoliday($date)
    {
        return self::active()->forDate($date)->exists() || 
               \App\Models\Event::where('type', 'holiday')->where('status', '!=', 'cancelled')->whereDate('date', $date)->exists();
    }

    public static function getHoliday($date)
    {
        $holiday = self::active()->forDate($date)->first();
        if ($holiday) return $holiday;

        $event = \App\Models\Event::where('type', 'holiday')->where('status', '!=', 'cancelled')->whereDate('date', $date)->first();
        if ($event) {
            $h = new static([
                'name' => $event->name,
                'description' => $event->description,
                'type' => 'school',
            ]);
            $h->date = $event->date;
            return $h;
        }
        
        return null;
    }

    public static function getUpcoming($startDate, $endDate)
    {
        $holidays = self::active()
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->get();
            
        $events = \App\Models\Event::where('type', 'holiday')
            ->where('status', '!=', 'cancelled')
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->get()
            ->map(function($e) {
                $h = new static([
                    'name' => $e->name,
                    'description' => $e->description,
                    'type' => 'school',
                ]);
                $h->date = $e->date;
                $h->created_at = $e->created_at;
                return $h;
            });
            
        return $holidays->concat($events)->sortBy('date')->values();
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
