<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_assistant_attendance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->string('suggested_status', 12);
            $table->string('review_status', 12)->default('pending');
            $table->string('reason', 250)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'student_id'], 'assistant_reports_unique_student_session');
            $table->index(['attendance_session_id', 'review_status'], 'assistant_reports_session_status');
            $table->index(['submitted_by', 'attendance_session_id'], 'assistant_reports_submitter_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_assistant_attendance_reports');
    }
};
