<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the unused push_subscriptions table after removing Web Push notifications.
     */
    public function up(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }

    /**
     * Restore the push_subscriptions table schema on rollback.
     */
    public function down(): void
    {
        if (Schema::hasTable('push_subscriptions')) {
            return;
        }

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->index();
            $table->text('public_key')->nullable();
            $table->text('auth_token')->nullable();
            $table->string('content_encoding', 32)->default('aes128gcm');
            $table->string('device_name')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }
};
