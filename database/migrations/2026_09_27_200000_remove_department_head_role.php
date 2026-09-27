<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Decommissions the department_head role and migrates any remaining users to the teacher role.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'department_head')
            ->update(['role' => 'teacher']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Role cannot be automatically re-inferred on rollback.
    }
};
