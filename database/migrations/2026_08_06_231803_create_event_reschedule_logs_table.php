<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_reschedule_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();

            // What changed
            $table->time('old_start')->nullable();
            $table->time('old_end')->nullable();
            $table->time('new_start')->nullable();
            $table->time('new_end')->nullable();
            $table->string('old_location')->nullable();
            $table->string('new_location')->nullable();
            $table->text('reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_reschedule_logs');
    }
};
