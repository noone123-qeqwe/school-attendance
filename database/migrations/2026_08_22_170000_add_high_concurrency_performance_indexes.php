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
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->index(['active', 'session_ends_at'], 'idx_sessions_active_ends_at');
            $table->index(['created_by', 'created_at'], 'idx_sessions_creator_created_at');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read', 'archived_at'], 'idx_notifications_user_read_archived');
        });

        Schema::table('warnings', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_warnings_user_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_active_ends_at');
            $table->dropIndex('idx_sessions_creator_created_at');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_read_archived');
        });

        Schema::table('warnings', function (Blueprint $table) {
            $table->dropIndex('idx_warnings_user_created_at');
        });
    }
};
