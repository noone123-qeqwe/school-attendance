<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
        'content_encoding',
        'device_name',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for users by role.
     */
    public function scopeForRole($query, $role)
    {
        return $query->whereHas('user', function ($q) use ($role) {
            $q->where('role', $role);
        });
    }

    /**
     * Automatically hash the endpoint before saving.
     */
    protected static function booted()
    {
        static::saving(function ($subscription) {
            if (empty($subscription->endpoint_hash) && !empty($subscription->endpoint)) {
                $subscription->endpoint_hash = hash('sha256', $subscription->endpoint);
            }
        });
    }
}
