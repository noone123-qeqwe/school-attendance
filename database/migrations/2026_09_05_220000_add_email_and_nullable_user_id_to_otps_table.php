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
        Schema::table('otps', function (Blueprint $table) {
            if (!Schema::hasColumn('otps', 'email')) {
                $table->string('email')->nullable()->after('user_id')->index();
            }
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->index(['email', 'purpose']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropIndex(['email', 'purpose']);
            if (Schema::hasColumn('otps', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
