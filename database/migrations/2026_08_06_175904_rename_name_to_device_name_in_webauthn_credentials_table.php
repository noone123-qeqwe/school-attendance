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
        Schema::table('webauthn_credentials', function (Blueprint $table) {
            // Check if 'name' exists to avoid errors on some environments
            if (Schema::hasColumn('webauthn_credentials', 'name')) {
                $table->renameColumn('name', 'device_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webauthn_credentials', function (Blueprint $table) {
            if (Schema::hasColumn('webauthn_credentials', 'device_name')) {
                $table->renameColumn('device_name', 'name');
            }
        });
    }
};
