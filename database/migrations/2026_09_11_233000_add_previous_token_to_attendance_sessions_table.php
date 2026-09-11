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
        if (Schema::hasTable('attendance_sessions') && !Schema::hasColumn('attendance_sessions', 'previous_token')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->string('previous_token', 64)->nullable()->after('token')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('attendance_sessions') && Schema::hasColumn('attendance_sessions', 'previous_token')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->dropColumn('previous_token');
            });
        }
    }
};
