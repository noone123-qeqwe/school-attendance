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
   Schema::create('attendances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('subject_code');
    $table->date('date');
    $table->time('time_in')->nullable();
    $table->string('status'); // Present, Late, Absent
    $table->timestamps();

    $table->unique(['user_id', 'subject_code', 'date']); // 🔥 NO DUPLICATES EVER
});

}
};