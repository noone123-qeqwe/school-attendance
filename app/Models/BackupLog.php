<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    protected $fillable = [
        'filename',
        'path',
        'size'
    ];

    protected $appends = ['status'];

    public function getStatusAttribute()
    {
        return 'completed';
    }
}
