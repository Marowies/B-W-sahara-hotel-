<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ht_room_calendars', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->index();
            $table->string('name')->nullable();
            $table->text('url');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ht_room_calendars');
    }
};
