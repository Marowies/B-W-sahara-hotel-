<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // One row per refresh token. A login creates a family; every rotation adds a row to that family.
        Schema::create('hotel_auth_tokens', function (Blueprint $table): void {
            $table->id();
            $table->char('family_id', 36)->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->char('refresh_hash', 64)->unique();
            $table->string('status', 10)->default('active')->index(); // active | revoked
            $table->string('revoke_reason', 20)->nullable();          // rotated | logout | reuse_detected | expired
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('family_expires_at')->index();          // absolute 30-day limit, inherited by every rotation
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_auth_tokens');
    }
};
