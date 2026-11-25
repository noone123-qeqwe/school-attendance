<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webauthn_credentials') && !Schema::hasColumn('webauthn_credentials', 'transports')) {
            Schema::table('webauthn_credentials', function (Blueprint $table) {
                $table->json('transports')->nullable()->after('biometric_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('webauthn_credentials') && Schema::hasColumn('webauthn_credentials', 'transports')) {
            Schema::table('webauthn_credentials', function (Blueprint $table) {
                $table->dropColumn('transports');
            });
        }
    }
};
