<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Otp extends Model
{
    protected $fillable = ['user_id', 'code', 'purpose', 'used', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime', 'used' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    /**
     * Generate a fresh 6-digit OTP for a user, invalidating any previous ones.
     */
    public static function generate(int $userId, string $purpose): self
    {
        // Invalidate old OTPs for this user + purpose
        static::where('user_id', $userId)
              ->where('purpose', $purpose)
              ->update(['used' => true]);

        return static::create([
            'user_id'    => $userId,
            'code'       => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'purpose'    => $purpose,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);
    }
}
