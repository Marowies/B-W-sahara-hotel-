<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('ht_booking_rooms', function (Blueprint $table): void {
            $table->unsignedBigInteger('ical_calendar_id')->nullable()->index();
            $table->char('ical_uid_hash', 64)->nullable();
            $table->unique(['ical_calendar_id', 'ical_uid_hash'], 'ht_booking_rooms_ical_identity');
        });
        Schema::table('ht_room_calendars', function (Blueprint $table): void {
            $table->unsignedBigInteger('sync_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('ht_booking_rooms', function (Blueprint $table): void {
            $table->dropUnique('ht_booking_rooms_ical_identity');
            $table->dropIndex(['ical_calendar_id']);
            $table->dropColumn(['ical_calendar_id', 'ical_uid_hash']);
        });
        Schema::table('ht_room_calendars', function (Blueprint $table): void {
            $table->dropColumn('sync_version');
        });
    }
};
