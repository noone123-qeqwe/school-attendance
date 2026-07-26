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
        if (Schema::hasTable('excuse_submissions') && !Schema::hasColumn('excuse_submissions', 'admin_notes')) {
            Schema::table('excuse_submissions', function (Blueprint $table) {
                $table->text('admin_notes')->nullable();
            });
        }
        
        if (Schema::hasTable('excuse_submissions') && !Schema::hasColumn('excuse_submissions', 'reviewed_at')) {
            Schema::table('excuse_submissions', function (Blueprint $table) {
                $table->timestamp('reviewed_at')->nullable();
            });
        }
        
        if (Schema::hasTable('excuse_submissions') && !Schema::hasColumn('excuse_submissions', 'reviewed_by')) {
            Schema::table('excuse_submissions', function (Blueprint $table) {
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('excuse_submissions', 'admin_notes')) {
            Schema::table('excuse_submissions', function (Blueprint $table) {
                $table->dropColumn('admin_notes');
            });
        }
        
        if (Schema::hasColumn('excuse_submissions', 'reviewed_at')) {
            Schema::table('excuse_submissions', function (Blueprint $table) {
                $table->dropColumn('reviewed_at');
            });
        }
        
        if (Schema::hasColumn('excuse_submissions', 'reviewed_by')) {
            Schema::table('excuse_submissions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('reviewed_by');
            });
        }
    }
};
