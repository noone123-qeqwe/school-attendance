<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Keep this migration ID as a no-op. Parent accounts and student links
        // must be created through an authorized workflow.
    }

    public function down(): void
    {
        // No schema or data changes are performed by this migration.
    }
};
