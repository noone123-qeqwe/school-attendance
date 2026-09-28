<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_bindings') && Schema::hasColumn('device_bindings', 'device_uuid')) {
            // Older releases copied the bearer key here. device_hash already
            // contains the HMAC needed for recognition.
            DB::table('device_bindings')->whereNotNull('device_uuid')->update(['device_uuid' => null]);
        }
    }

    public function down(): void
    {
        // Raw keys cannot and must not be reconstructed.
    }
};
