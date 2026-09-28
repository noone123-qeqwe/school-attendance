<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('peer_face_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('verifier_subject_ref', 128)->unique();
            $table->string('model_version', 64);
            $table->timestamp('consented_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('peer_vouch_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('verification_id')->unique();
            $table->foreignId('session_id')->constrained('attendance_sessions')->cascadeOnDelete();
            $table->foreignId('subject_student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voucher_student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->string('decision_mac', 64)->nullable();
            $table->string('nonce_hash', 64);
            $table->string('challenge', 32);
            $table->string('status', 24)->index();
            $table->string('failure_reason', 64)->nullable();
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['voucher_student_id', 'status', 'created_at'], 'peer_host_failures_idx');
            $table->index(['subject_student_id', 'status', 'created_at'], 'peer_subject_failures_idx');
            $table->index(['session_id', 'voucher_student_id', 'status'], 'peer_host_quota_idx');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('verification_channel', 32)->nullable()->index();
            $table->boolean('is_provisional')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', fn (Blueprint $table) => $table->dropColumn(['verification_channel', 'is_provisional']));
        Schema::dropIfExists('peer_vouch_requests');
        Schema::dropIfExists('peer_face_enrollments');
    }
};
