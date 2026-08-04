<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('substitute_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('substitute_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->date('date');
            $table->timestamps();

            // A substitute can only cover a subject once per day
            $table->unique(['substitute_id', 'subject_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('substitute_teachers');
    }
};
