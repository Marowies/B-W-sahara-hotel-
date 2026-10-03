<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ht_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id');
            $table->foreignId('payment_id')->nullable()->index();
            $table->morphs('reference');
            $table->string('code')->unique();
            $table->decimal('sub_total', 15)->unsigned();
            $table->decimal('tax_amount', 15)->default(0)->unsigned();
            $table->decimal('discount_amount', 15)->default(0)->unsigned();
            $table->decimal('amount', 15)->unsigned();
            $table->string('status')->index()->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ht_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id');
            $table->string('name');
            $table->string('description', 400)->nullable();
            $table->integer('qty')->unsigned();
            $table->decimal('sub_total', 15)->unsigned();
            $table->decimal('tax_amount', 15)->default(0)->unsigned();
            $table->decimal('discount_amount', 15)->default(0)->unsigned();
            $table->decimal('amount', 15);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ht_invoices');
        Schema::dropIfExists('ht_invoice_items');
    }
};
