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
        // First, remove duplicates keeping the latest attendance record
        // This is necessary because adding a unique index will fail if duplicates exist.
        $duplicates = DB::select("
            SELECT user_id, subject_code, date, COUNT(*)
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
            
            // Skip the first one (latest), delete the rest
            $idsToDelete = $records->slice(1)->pluck('id');
            DB::table('attendances')->whereIn('id', $idsToDelete)->delete();
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['user_id', 'subject_code', 'date'], 'attendance_unique_student_subject_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendance_unique_student_subject_date');
        });
    }
};
