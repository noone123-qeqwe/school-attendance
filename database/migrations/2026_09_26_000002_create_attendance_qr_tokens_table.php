<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            // Nullable unique key: invalidated tokens release their slot.
            $table->foreignId('active_session_id')->nullable()->constrained('attendance_sessions')->cascadeOnDelete();
            $table->string('nonce', 64);
            $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $table->string('generator_type', 20);
            $table->string('rotation_type', 20);
            $table->dateTime('issued_at');
            $table->dateTime('expires_at');
            $table->dateTime('invalidated_at')->nullable();
            $table->timestamps();

            $table->unique('active_session_id');
            $table->index(['attendance_session_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_qr_tokens');
    }
};
