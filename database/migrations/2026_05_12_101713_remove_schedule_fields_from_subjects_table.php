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
        Schema::table('subjects', function (Blueprint $table) {
            // Only drop columns that exist
            $existingColumns = Schema::getColumnListing('subjects');
            $columnsToDrop = [];
            
            if (in_array('days', $existingColumns)) {
                $columnsToDrop[] = 'days';
            }
            if (in_array('start_time', $existingColumns)) {
                $columnsToDrop[] = 'start_time';
            }
            if (in_array('end_time', $existingColumns)) {
                $columnsToDrop[] = 'end_time';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
        });
    }
};
