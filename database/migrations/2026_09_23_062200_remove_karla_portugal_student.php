<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;

return new class extends Migration
{
    /**
     * Remove Karla Jean Villamor Portugal (student_number 2312215)
     * and her parent Rowena Portugal from the system.
     */
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        // Find student
        $student = User::withTrashed()
            ->where('student_number', '2312215')
            ->where('role', 'student')
            ->first();

        // Find parent
        $parent = User::withTrashed()
            ->where('email', 'rowena.portugal@gmail.com')
            ->where('role', 'parent')
            ->first();

        // Remove parent-student link
        if ($student && $parent) {
            DB::table('parent_student')
                ->where('parent_id', $parent->id)
                ->where('student_id', $student->id)
                ->delete();
        }

        // Soft-delete the student
        if ($student) {
            // Remove attendance records
            DB::table('attendances')->where('user_id', $student->id)->delete();

            $student->forceDelete();
        }

        // Soft-delete the parent (only linked to Karla)
        if ($parent) {
            // Check if linked to any other students
            $otherLinks = DB::table('parent_student')
                ->where('parent_id', $parent->id)
                ->count();

            if ($otherLinks === 0) {
                $parent->forceDelete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-reversible: student data has been removed
    }
};
