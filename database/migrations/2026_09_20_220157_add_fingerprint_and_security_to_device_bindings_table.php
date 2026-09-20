<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('device_bindings', function (Blueprint $table) {
            if (!Schema::hasColumn('device_bindings', 'hardware_fingerprint')) {
                $table->string('hardware_fingerprint', 64)->nullable()->index()->after('device_hash');
            }
            if (!Schema::hasColumn('device_bindings', 'device_uuid')) {
                $table->string('device_uuid', 64)->nullable()->index()->after('hardware_fingerprint');
            }
            if (!Schema::hasColumn('device_bindings', 'change_count')) {
                $table->unsignedInteger('change_count')->default(0)->after('ip_address');
            }
            if (!Schema::hasColumn('device_bindings', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('change_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_bindings', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('device_bindings', 'hardware_fingerprint')) $columns[] = 'hardware_fingerprint';
            if (Schema::hasColumn('device_bindings', 'device_uuid')) $columns[] = 'device_uuid';
            if (Schema::hasColumn('device_bindings', 'change_count')) $columns[] = 'change_count';
            if (Schema::hasColumn('device_bindings', 'is_locked')) $columns[] = 'is_locked';
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
