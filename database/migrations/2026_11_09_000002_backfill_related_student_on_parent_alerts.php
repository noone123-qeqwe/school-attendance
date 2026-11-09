<?php

use App\Services\LegacyParentAlertBackfillService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(LegacyParentAlertBackfillService::class)->run();
    }

    public function down(): void
    {
        // Historical associations are retained because they may have been added
        // by normal application writes after this migration ran.
    }
};
