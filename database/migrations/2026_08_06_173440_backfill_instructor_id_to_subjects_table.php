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

        // Backfill data using chunking for DB agnosticism (supports SQLite and MySQL)
        DB::table('subjects')
            ->whereNotNull('instructor')
            ->whereNull('instructor_id')
            ->orderBy('id')
            ->chunk(100, function ($subjects) {
                foreach ($subjects as $subject) {
                    $user = DB::table('users')
                        ->where('name', $subject->instructor)
                        ->orWhere('employee_id', $subject->instructor)
                        ->first();
                        
                    if ($user) {
                        DB::table('subjects')
                            ->where('id', $subject->id)
                            ->update(['instructor_id' => $user->id]);
                    }
                }
            });

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
