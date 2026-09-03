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
        if (!Schema::hasColumn('academic_years', 'semester')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->tinyInteger('semester')->default(1)->after('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('academic_years', 'semester')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->dropColumn('semester');
            });
        }
    }
};
