<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeatingChart extends Model
{
    use HasFactory;
    
    protected $fillable = ['subject_code', 'grid_data', 'rows', 'cols'];
    
    protected $casts = [
        'grid_data' => 'array',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_code', 'code');
    }
}
