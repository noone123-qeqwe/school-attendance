<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('subjects', 'instructor_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        // Backfill data
        // We look up the users table where user name or employee_id matches the instructor string
        DB::statement("
            UPDATE subjects
            JOIN users ON subjects.instructor = users.name OR subjects.instructor = users.employee_id
            SET subjects.instructor_id = users.id
            WHERE subjects.instructor IS NOT NULL AND subjects.instructor_id IS NULL
        ");

        $unresolved = DB::table('subjects')
            ->whereNotNull('instructor')
            ->whereNull('instructor_id')
            ->count();
            
        if ($unresolved > 0) {
            Log::warning("Found {$unresolved} subjects with unresolved instructor names.");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe forward migration: we don't drop the instructor_id or foreign key 
        // in down() to prevent accidental data loss in production.
    }
};
