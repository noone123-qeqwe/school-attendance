<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotent migration: ensure deleted_at exists on users, subjects, and
     * attendances tables. The original migration (2026_07_20_141909) may have
     * failed or been skipped on production, leaving these columns missing.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('subjects') && !Schema::hasColumn('subjects', 'deleted_at')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('attendances') && !Schema::hasColumn('attendances', 'deleted_at')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop if this migration actually added them
        // (no-op if original migration already had them)
    }
};
