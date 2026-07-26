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
        // 1. Add soft deletes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 2. Add soft deletes to subjects table
        Schema::table('subjects', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 3. Add soft deletes and indexes to attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->softDeletes();
            
            // Add composite indexes for common queries
            $table->index(['user_id', 'date']);
            $table->index(['subject_code', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'date']);
            $table->dropIndex(['subject_code', 'date']);
            $table->dropSoftDeletes();
        });
    }
};
