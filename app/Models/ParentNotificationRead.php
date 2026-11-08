<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentNotificationRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['parent_id', 'notification_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];
}
