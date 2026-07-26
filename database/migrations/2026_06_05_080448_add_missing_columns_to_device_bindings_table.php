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
            if (!Schema::hasColumn('device_bindings', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('user_agent');
            }
            if (!Schema::hasColumn('device_bindings', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('user_agent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_bindings', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'last_seen_at']);
        });
    }
};
