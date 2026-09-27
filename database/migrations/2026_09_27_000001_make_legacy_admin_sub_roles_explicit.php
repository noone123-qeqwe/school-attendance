<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacyAdmins = DB::table('users')
            ->where('role', 'admin')
            ->whereNull('admin_sub_role')
            ->orderBy('id')
            ->pluck('id');

        if ($legacyAdmins->isEmpty()) {
            return;
        }

        // Preserve an administrator for installations that predate explicit
        // subroles. Other legacy accounts receive the least privileged admin role.
        $hasSuperAdmin = DB::table('users')
            ->where('role', 'admin')
            ->where('admin_sub_role', 'super_admin')
            ->where('is_active', true)
            ->exists();

        if (!$hasSuperAdmin) {
            $primaryAdmin = DB::table('users')->whereIn('id', $legacyAdmins->all())
                ->where('is_active', true)->orderBy('id')->value('id')
                ?? $legacyAdmins->first();
            DB::table('users')->where('id', $primaryAdmin)
                ->update(['admin_sub_role' => 'super_admin']);
            $legacyAdmins = $legacyAdmins->reject(fn ($id) => $id === $primaryAdmin);
        }

        DB::table('users')->whereIn('id', $legacyAdmins->all())
            ->update(['admin_sub_role' => 'data_entry']);
    }

    public function down(): void
    {
        // Do not erase administrator role assignments on rollback.
    }
};
