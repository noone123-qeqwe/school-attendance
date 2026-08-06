<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Event type & status
            $table->enum('type', ['class', 'exam', 'meeting', 'school_event', 'holiday'])->default('school_event')->after('name');
            $table->enum('status', ['scheduled', 'rescheduled', 'cancelled'])->default('scheduled')->after('end_time');

            // Location (free-text, null for holidays)
            $table->string('location')->nullable()->after('status');

            // Ownership
            $table->foreignId('organizer_id')->nullable()->constrained('users')->nullOnDelete()->after('location');
            $table->unsignedBigInteger('class_id')->nullable()->after('organizer_id');

            // Reschedule audit fields
            $table->time('original_start')->nullable()->after('class_id');
            $table->time('original_end')->nullable()->after('original_start');
            $table->string('original_location')->nullable()->after('original_end');
            $table->foreignId('rescheduled_by')->nullable()->constrained('users')->nullOnDelete()->after('original_location');
            $table->timestamp('rescheduled_at')->nullable()->after('rescheduled_by');
            $table->text('reschedule_reason')->nullable()->after('rescheduled_at');

            // Soft deletes
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['organizer_id']);
            $table->dropForeign(['rescheduled_by']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'type', 'status', 'location', 'organizer_id', 'class_id',
                'original_start', 'original_end', 'original_location',
                'rescheduled_by', 'rescheduled_at', 'reschedule_reason',
            ]);
        });
    }
};
