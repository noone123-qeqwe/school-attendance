<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendeeGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'group_type',
        'class_id'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'class_id');
    }
}
