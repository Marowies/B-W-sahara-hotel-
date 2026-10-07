<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stripe_checkout_bindings', function (Blueprint $table): void {
            $table->string('session_id', 255)->primary();
            $table->unsignedBigInteger('payment_id')->unique();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_checkout_bindings');
    }
};
