<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeerFaceEnrollment extends Model
{
    protected $fillable = ['user_id', 'verifier_subject_ref', 'model_version', 'consented_at', 'revoked_at'];

    protected $casts = ['consented_at' => 'datetime', 'revoked_at' => 'datetime'];
}
