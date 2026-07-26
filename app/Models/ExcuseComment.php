<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExcuseComment extends Model
{
    protected $fillable = ['excuse_submission_id', 'user_id', 'body'];

    public function excuseSubmission()
    {
        return $this->belongsTo(ExcuseSubmission::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
