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
        if (Schema::hasTable('attendance_sessions') && !Schema::hasColumn('attendance_sessions', 'previous_session_code')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->string('previous_session_code', 16)->nullable()->after('session_code')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('attendance_sessions') && Schema::hasColumn('attendance_sessions', 'previous_session_code')) {
            Schema::table('attendance_sessions', function (Blueprint $table) {
                $table->dropColumn('previous_session_code');
            });
        }
    }
};
