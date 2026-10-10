<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hotel_login_locks', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->timestamp('updated_at')->index();
        });
        Schema::table('hotel_auth_tokens', function (Blueprint $table): void {
            $table->text('refresh_ciphertext')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hotel_auth_tokens', fn (Blueprint $table) => $table->dropColumn('refresh_ciphertext'));
        Schema::dropIfExists('hotel_login_locks');
    }
};
