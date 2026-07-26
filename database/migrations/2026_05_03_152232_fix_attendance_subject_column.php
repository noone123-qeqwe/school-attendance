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

        if (Schema::hasColumn('subjects', 'days')) {
            $table->dropColumn('days');
        }

        if (Schema::hasColumn('subjects', 'start_time')) {
            $table->dropColumn('start_time');
        }

        if (Schema::hasColumn('subjects', 'end_time')) {
            $table->dropColumn('end_time');
        }
    });
}
};
