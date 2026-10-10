<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ht_rooms', function (Blueprint $table): void {
            $table->index(['status', 'order', 'id'], 'ht_rooms_catalogue_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('ht_rooms', function (Blueprint $table): void {
            $table->dropIndex('ht_rooms_catalogue_order_index');
        });
    }
};
