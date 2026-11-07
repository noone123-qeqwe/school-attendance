<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Retire the old cleanup behavior. Never delete production accounts or
        // attendance data from a hard-coded migration.
    }

    public function down(): void
    {
        // No schema or data changes are performed by this migration.
    }
};
