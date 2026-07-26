<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->decimal('classroom_lat', 10, 7)->nullable()->after('active');
            $table->decimal('classroom_lng', 10, 7)->nullable()->after('classroom_lat');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropColumn(['classroom_lat', 'classroom_lng']);
        });
    }
};
