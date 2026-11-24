<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_student_assistants', function (Blueprint $table) {
            $table->foreignId('assigned_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('can_submit_attendance')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('class_student_assistants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_by_admin_id');
            $table->dropColumn('can_submit_attendance');
        });
    }
};
