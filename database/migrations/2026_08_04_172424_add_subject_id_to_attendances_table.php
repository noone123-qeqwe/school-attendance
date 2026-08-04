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
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('cascade');
        });

        // Migrate data
        $attendances = DB::table('attendances')->get();
        foreach ($attendances as $attendance) {
            if ($attendance->subject_code) {
                $subject = DB::table('subjects')->where('code', $attendance->subject_code)->first();
                if ($subject) {
                    DB::table('attendances')->where('id', $attendance->id)->update(['subject_id' => $subject->id]);
                }
            }
        }

        Schema::table('attendances', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique(['user_id', 'subject_code', 'date']);
            
            // Note: Not dropping subject_code just yet to prevent breaking existing code during deployment,
            // we will drop it in a separate migration after all code is updated.
            
            // Add new unique constraint
            // We use subject_id, but it is nullable, so in SQLite or MySQL unique over nullable works differently.
            // In MySQL, multiple nulls are allowed in unique constraint, but since we are populating it, it's mostly non-null.
            $table->unique(['user_id', 'subject_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'subject_id', 'date']);
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
            $table->unique(['user_id', 'subject_code', 'date']);
        });
    }
};
