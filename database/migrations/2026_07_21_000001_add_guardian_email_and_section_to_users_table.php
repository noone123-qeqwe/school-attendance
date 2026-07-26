<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'section')) {
                $table->string('section', 20)->nullable()->after('semester');
            }
            if (!Schema::hasColumn('users', 'guardian_email')) {
                $table->string('guardian_email')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(array_filter(['section', 'guardian_email'], function ($col) {
                return Schema::hasColumn('users', $col);
            }));
        });
    }
};
