<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ht_booking_foods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->index();
            $table->foreignId('food_id')->index();
            $table->unsignedInteger('quantity')->default(1);

            $table->unique(['booking_id', 'food_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ht_booking_foods');
    }
};
