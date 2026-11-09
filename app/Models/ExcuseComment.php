<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcuseComment extends Model
{
    protected $fillable = ['excuse_submission_id', 'user_id', 'body'];

    public function excuseSubmission()
    {
        return $this->belongsTo(ExcuseSubmission::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
