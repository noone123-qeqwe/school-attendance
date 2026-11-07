<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Keep this migration ID as a no-op. Account provisioning, passwords,
        // and roster data must never be synchronized by migration.
    }

    public function down(): void
    {
        // No schema or data changes are performed by this migration.
    }
};
