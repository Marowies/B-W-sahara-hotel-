<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('payment_webhook_receipts', function (Blueprint $table): void {
            $table->string('provider', 32);
            $table->string('event_id', 255);
            $table->string('payment_identity', 255);
            $table->unsignedBigInteger('payment_id');
            $table->timestamp('processed_at');
            $table->primary(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_receipts');
    }
};
