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
        Schema::create('seating_charts', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code')->index();
            $table->json('grid_data')->nullable(); // array of rows and cols mapped to user_ids
            $table->integer('rows')->default(5);
            $table->integer('cols')->default(6);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seating_charts');
    }
};
