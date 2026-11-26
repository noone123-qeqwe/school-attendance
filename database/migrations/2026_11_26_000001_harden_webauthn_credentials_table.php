<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('webauthn_credentials')) {
            return;
        }

        Schema::table('webauthn_credentials', function (Blueprint $table) {
            if (!Schema::hasColumn('webauthn_credentials', 'aaguid')) {
                $table->string('aaguid', 64)->nullable()->after('transports');
            }
            if (!Schema::hasColumn('webauthn_credentials', 'authenticator_attachment')) {
                $table->string('authenticator_attachment', 32)->nullable()->after('aaguid');
            }
            if (!Schema::hasColumn('webauthn_credentials', 'backed_up')) {
                $table->boolean('backed_up')->default(false)->after('authenticator_attachment');
            }
            if (!Schema::hasColumn('webauthn_credentials', 'user_agent_summary')) {
                $table->string('user_agent_summary', 160)->nullable()->after('backed_up');
            }
            if (!Schema::hasColumn('webauthn_credentials', 'consent_given_at')) {
                $table->timestamp('consent_given_at')->nullable()->after('user_agent_summary');
            }
            if (!Schema::hasColumn('webauthn_credentials', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('last_used_at');
            }
            if (!Schema::hasColumn('webauthn_credentials', 'revoked_reason')) {
                $table->string('revoked_reason', 120)->nullable()->after('revoked_at');
            }
        });

        try {
            Schema::table('webauthn_credentials', function (Blueprint $table) {
                $table->index(['user_id', 'revoked_at'], 'webauthn_user_revoked_idx');
            });
        } catch (\Throwable) {
            // Index may already exist on repeated migration runs.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('webauthn_credentials')) {
            return;
        }

        try {
            Schema::table('webauthn_credentials', function (Blueprint $table) {
                $table->dropIndex('webauthn_user_revoked_idx');
            });
        } catch (\Throwable) {
            // Ignore if index does not exist.
        }

        Schema::table('webauthn_credentials', function (Blueprint $table) {
            $columns = array_values(array_filter([
                'aaguid',
                'authenticator_attachment',
                'backed_up',
                'user_agent_summary',
                'consent_given_at',
                'revoked_at',
                'revoked_reason',
            ], fn (string $col) => Schema::hasColumn('webauthn_credentials', $col)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
