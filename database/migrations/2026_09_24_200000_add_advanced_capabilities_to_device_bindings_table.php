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
            if (!Schema::hasColumn('device_bindings', 'client_metadata')) {
                $table->json('client_metadata')->nullable()->after('user_agent');
            }
            if (!Schema::hasColumn('device_bindings', 'trust_score')) {
                $table->unsignedTinyInteger('trust_score')->nullable()->after('change_count');
            }
            if (!Schema::hasColumn('device_bindings', 'locked_reason')) {
                $table->string('locked_reason', 128)->nullable()->after('is_locked');
            }
            if (!Schema::hasColumn('device_bindings', 'last_verified_at')) {
                $table->timestamp('last_verified_at')->nullable()->after('last_seen_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_bindings', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('device_bindings', 'client_metadata')) $cols[] = 'client_metadata';
            if (Schema::hasColumn('device_bindings', 'trust_score')) $cols[] = 'trust_score';
            if (Schema::hasColumn('device_bindings', 'locked_reason')) $cols[] = 'locked_reason';
            if (Schema::hasColumn('device_bindings', 'last_verified_at')) $cols[] = 'last_verified_at';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
