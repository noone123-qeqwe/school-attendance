<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_attendee_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->enum('group_type', ['class_parents', 'class_students', 'class_instructors']);
            $table->unsignedBigInteger('class_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendee_groups');
    }
};
