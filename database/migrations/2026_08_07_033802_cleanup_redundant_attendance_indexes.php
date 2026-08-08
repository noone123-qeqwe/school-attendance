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
        $indexesFound = Schema::getIndexes('attendances');
        $indexNames = array_column($indexesFound, 'name');

        Schema::table('attendances', function (Blueprint $table) use ($indexNames) {
            if (in_array('attendances_unique_record', $indexNames)) {
                $table->dropUnique('attendances_unique_record');
            }

            if (in_array('attendance_unique_student_subject_date', $indexNames)) {
                $table->dropUnique('attendance_unique_student_subject_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['user_id', 'subject_code', 'date'], 'attendances_unique_record');
            $table->unique(['user_id', 'subject_code', 'date'], 'attendance_unique_student_subject_date');
        });
    }
};
