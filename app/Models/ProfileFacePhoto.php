<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileFacePhoto extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = ['user_id', 'image_ciphertext', 'mime_type', 'source_path'];

    protected $hidden = ['image_ciphertext'];
}
