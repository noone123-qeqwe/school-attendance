<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectMaterial extends Model
{
    protected $fillable = [
        'subject_id',
        'title',
        'description',
        'file_path',
        'file_type',
        'original_filename',
        'file_size',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
