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
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('gps_accuracy', 8, 2)->nullable()->after('longitude');
            $table->string('method')->default('manual_gps')->after('gps_accuracy')->comment('manual_gps, qr, webauthn, override');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['gps_accuracy', 'method']);
        });
    }
};
