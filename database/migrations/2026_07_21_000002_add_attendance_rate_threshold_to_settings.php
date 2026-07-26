<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        // Seed the attendance_rate_threshold default (75%) if not already present
        Setting::firstOrCreate(
            ['key' => 'attendance_rate_threshold'],
            ['value' => '75']
        );
    }

    public function down(): void
    {
        Setting::where('key', 'attendance_rate_threshold')->delete();
    }
};
