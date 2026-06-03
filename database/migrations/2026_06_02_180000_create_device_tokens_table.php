<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * device_tokens — one row per Expo push token a logged-in customer has
 * registered. We upsert on (user_id, token) so re-installing the app or
 * re-registering after each login is a no-op.
 *
 * Additive: not used by the existing Vue SPA or any existing API path —
 * only by the new POST /api/device-token endpoint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('token'); // Expo push tokens are ~30–40 chars but allow room
            $table->string('platform', 16); // 'ios' | 'android' | 'web'
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'token']);
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
