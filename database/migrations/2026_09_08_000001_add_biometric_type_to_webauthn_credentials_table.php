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
        if (Schema::hasTable('webauthn_credentials') && !Schema::hasColumn('webauthn_credentials', 'biometric_type')) {
            Schema::table('webauthn_credentials', function (Blueprint $table) {
                $table->string('biometric_type')->default('fingerprint')->after('device_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('webauthn_credentials') && Schema::hasColumn('webauthn_credentials', 'biometric_type')) {
            Schema::table('webauthn_credentials', function (Blueprint $table) {
                $table->dropColumn('biometric_type');
            });
        }
    }
};
