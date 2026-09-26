<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_student_assistants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by_teacher_id')->constrained('users')->restrictOnDelete();
            // A nullable slot preserves historical rows while the unique index
            // prevents more than two occupied slots for a class.
            $table->unsignedTinyInteger('active_slot')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('expires_at');
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['subject_id', 'student_id']);
            $table->unique(['subject_id', 'active_slot']);
            $table->index(['student_id', 'revoked_at', 'starts_at', 'expires_at'], 'assistant_access_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_student_assistants');
    }
};
