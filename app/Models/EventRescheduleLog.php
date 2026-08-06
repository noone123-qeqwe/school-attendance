<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRescheduleLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'changed_by',
        'old_start',
        'old_end',
        'new_start',
        'new_end',
        'old_location',
        'new_location',
        'reason'
    ];

    protected $casts = [
        'old_start' => 'datetime:H:i',
        'old_end' => 'datetime:H:i',
        'new_start' => 'datetime:H:i',
        'new_end' => 'datetime:H:i',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
