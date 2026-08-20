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
        'date' => 'date:Y-m-d',
        'is_active' => 'boolean'
    ];

    /**
     * Always store date strictly as Y-m-d string.
     */
    public function setDateAttribute($value)
    {
        $this->attributes['date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

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
        $dateStr = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');
        return $query->whereDate('date', $dateStr);
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    // Helper methods
    public static function isHoliday($date)
    {
        $dateStr = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');
        return self::active()->forDate($dateStr)->exists() || 
               \App\Models\Event::where('type', 'holiday')->where('status', '!=', 'cancelled')->whereDate('date', $dateStr)->exists();
    }

    public static function getHoliday($date)
    {
        $dateStr = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');
        $holiday = self::active()->forDate($dateStr)->first();
        if ($holiday) return $holiday;

        $event = \App\Models\Event::where('type', 'holiday')->where('status', '!=', 'cancelled')->whereDate('date', $dateStr)->first();
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
        $startStr = $startDate instanceof \DateTimeInterface ? $startDate->format('Y-m-d') : Carbon::parse($startDate)->format('Y-m-d');
        $endStr = $endDate instanceof \DateTimeInterface ? $endDate->format('Y-m-d') : Carbon::parse($endDate)->format('Y-m-d');

        $holidays = self::active()
            ->whereDate('date', '>=', $startStr)
            ->whereDate('date', '<=', $endStr)
            ->orderBy('date')
            ->get();
            
        $events = \App\Models\Event::where('type', 'holiday')
            ->where('status', '!=', 'cancelled')
            ->whereDate('date', '>=', $startStr)
            ->whereDate('date', '<=', $endStr)
            ->orderBy('date')
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
            
        return $holidays->concat($events)
            ->unique(function ($item) {
                $d = $item->date instanceof \DateTimeInterface ? $item->date->format('Y-m-d') : Carbon::parse($item->date)->format('Y-m-d');
                return $d . '_' . strtolower(trim($item->name));
            })
            ->sortBy('date')
            ->values();
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
