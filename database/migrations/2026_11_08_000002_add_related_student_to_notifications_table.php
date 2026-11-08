<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('related_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['user_id', 'related_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['related_user_id']);
            $table->dropIndex(['user_id', 'related_user_id']);
            $table->dropColumn('related_user_id');
        });
    }
};
