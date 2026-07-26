<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warnings')) {
            Schema::create('warnings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('subject_code');
                $table->string('type');
                $table->text('message')->nullable();
                $table->foreignId('sent_by')->nullable();
                $table->timestamps();

                // 🔥 THIS LINE FIXES DUPLICATES
                $table->unique(['user_id', 'subject_code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warnings');
    }
};