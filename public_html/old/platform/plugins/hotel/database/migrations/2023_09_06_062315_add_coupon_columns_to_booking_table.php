<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('ht_bookings', function (Blueprint $table): void {
            $table->after('sub_total', function () use ($table): void {
                $table->decimal('coupon_amount', 15)->default(0)->unsigned();
                $table->string('coupon_code', 20)->nullable();
            });
        });
    }

    public function down(): void
    {
        Schema::table('ht_bookings', function (Blueprint $table): void {
            $table->dropColumn(['coupon_amount', 'coupon_code']);
        });
    }
};
