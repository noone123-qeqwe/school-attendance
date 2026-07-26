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
        Schema::table('warnings', function (Blueprint $table) {
            if (!Schema::hasColumn('warnings', 'sent_by')) {
                $table->unsignedBigInteger('sent_by')->nullable()->after('message');
            }
            if (!Schema::hasColumn('warnings', 'type')) {
                $table->string('type')->after('subject_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warnings', function (Blueprint $table) {
            if (Schema::hasColumn('warnings', 'sent_by')) {
                $table->dropColumn('sent_by');
            }
            if (Schema::hasColumn('warnings', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
