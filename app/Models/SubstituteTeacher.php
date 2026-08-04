<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubstituteTeacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'substitute_id',
        'subject_id',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function substitute()
    {
        return $this->belongsTo(User::class, 'substitute_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
