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
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_student_number_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('student_number')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('student_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_number')->nullable(false)->change();
        });
    }
};