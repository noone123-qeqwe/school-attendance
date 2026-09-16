<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Allow multiple users to register platform authenticators on shared devices
     * by scoping uniqueness of credential_id to (user_id, credential_id).
     */
    public function up(): void
    {
        if (Schema::hasTable('webauthn_credentials')) {
            $driver = DB::connection()->getDriverName();
            $hasOldUnique = false;
            $hasCompositeUnique = false;

            if ($driver === 'sqlite') {
                try {
                    $indexList = DB::select("PRAGMA index_list('webauthn_credentials')");
                    foreach ($indexList as $idx) {
                        $idxName = is_object($idx) ? ($idx->name ?? '') : ($idx['name'] ?? '');
                        if (str_contains($idxName, 'credential_id_unique')) $hasOldUnique = true;
                        if (str_contains($idxName, 'webauthn_user_credential_unique')) $hasCompositeUnique = true;
                    }
                } catch (\Throwable $e) {}
            } else {
                try {
                    $indexes = DB::select("SHOW INDEXES FROM webauthn_credentials WHERE Key_name = 'webauthn_credentials_credential_id_unique'");
                    $hasOldUnique = !empty($indexes);
                    $compositeIndexes = DB::select("SHOW INDEXES FROM webauthn_credentials WHERE Key_name = 'webauthn_user_credential_unique'");
                    $hasCompositeUnique = !empty($compositeIndexes);
                } catch (\Throwable $e) {}
            }

            if ($hasOldUnique) {
                try {
                    Schema::table('webauthn_credentials', function (Blueprint $table) {
                        $table->dropUnique('webauthn_credentials_credential_id_unique');
                    });
                } catch (\Throwable $e) {}
            }

            if (!$hasCompositeUnique) {
                try {
                    Schema::table('webauthn_credentials', function (Blueprint $table) {
                        $table->unique(['user_id', 'credential_id'], 'webauthn_user_credential_unique');
                    });
                } catch (\Throwable $e) {}
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('webauthn_credentials')) {
            $driver = DB::connection()->getDriverName();
            $hasComposite = false;
            $hasOld = false;

            if ($driver === 'sqlite') {
                try {
                    $indexList = DB::select("PRAGMA index_list('webauthn_credentials')");
                    foreach ($indexList as $idx) {
                        $idxName = is_object($idx) ? ($idx->name ?? '') : ($idx['name'] ?? '');
                        if (str_contains($idxName, 'webauthn_user_credential_unique')) $hasComposite = true;
                        if (str_contains($idxName, 'credential_id_unique')) $hasOld = true;
                    }
                } catch (\Throwable $e) {}
            } else {
                try {
                    $compositeIndexes = DB::select("SHOW INDEXES FROM webauthn_credentials WHERE Key_name = 'webauthn_user_credential_unique'");
                    $hasComposite = !empty($compositeIndexes);
                    $indexes = DB::select("SHOW INDEXES FROM webauthn_credentials WHERE Key_name = 'webauthn_credentials_credential_id_unique'");
                    $hasOld = !empty($indexes);
                } catch (\Throwable $e) {}
            }

            if ($hasComposite) {
                try {
                    Schema::table('webauthn_credentials', function (Blueprint $table) {
                        $table->dropUnique('webauthn_user_credential_unique');
                    });
                } catch (\Throwable $e) {}
            }

            if (!$hasOld) {
                try {
                    Schema::table('webauthn_credentials', function (Blueprint $table) {
                        $table->unique('credential_id', 'webauthn_credentials_credential_id_unique');
                    });
                } catch (\Throwable $e) {}
            }
        }
    }
};
