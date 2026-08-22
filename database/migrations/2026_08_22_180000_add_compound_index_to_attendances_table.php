<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Safe deduplication before applying index to prevent migration failure if duplicates exist
        try {
            $duplicates = DB::select("
                SELECT user_id, subject_code, date, COUNT(*) as cnt
                FROM attendances
                GROUP BY user_id, subject_code, date
                HAVING COUNT(*) > 1
            ");

            foreach ($duplicates as $dup) {
                $records = DB::table('attendances')
                    ->where('user_id', $dup->user_id)
                    ->where('subject_code', $dup->subject_code)
                    ->where('date', $dup->date)
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                // Keep the first (newest), delete the rest
                $idsToDelete = $records->slice(1)->pluck('id');
                DB::table('attendances')->whereIn('id', $idsToDelete)->delete();
            }
        } catch (\Throwable $e) {
            // If table doesn't exist or database driver doesn't support sub-query, continue to schema definition
        }

        $indexesFound = Schema::getIndexes('attendances');
        $indexNames = array_column($indexesFound, 'name');

        Schema::table('attendances', function (Blueprint $table) use ($indexNames) {
            if (!in_array('idx_attendances_user_subject_date', $indexNames)) {
                $table->unique(['user_id', 'subject_code', 'date'], 'idx_attendances_user_subject_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexesFound = Schema::getIndexes('attendances');
        $indexNames = array_column($indexesFound, 'name');

        Schema::table('attendances', function (Blueprint $table) use ($indexNames) {
            if (in_array('idx_attendances_user_subject_date', $indexNames)) {
                $table->dropUnique('idx_attendances_user_subject_date');
            }
        });
    }
};
