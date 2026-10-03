<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ht_ical_sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->index();
            $table->foreignId('calendar_id')->nullable()->index();
            $table->string('status'); // success, warning, error
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ht_ical_sync_logs');
    }
};
