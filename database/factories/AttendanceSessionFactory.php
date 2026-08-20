<?php

namespace Database\Factories;

use App\Models\AttendanceSession;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AttendanceSessionFactory extends Factory
{
    protected $model = AttendanceSession::class;

    public function definition()
    {
        return [
            'subject_code' => function () {
                return Subject::factory()->create()->code;
            },
            'created_by' => function () {
                return User::factory()->create(['role' => 'teacher'])->id;
            },
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes(5),
            'session_ends_at' => now()->addMinutes(20),
            'active' => true,
            'classroom_lat' => 14.599512,
            'classroom_lng' => 120.984222,
            'webauthn_challenge' => null,
        ];
    }
}
