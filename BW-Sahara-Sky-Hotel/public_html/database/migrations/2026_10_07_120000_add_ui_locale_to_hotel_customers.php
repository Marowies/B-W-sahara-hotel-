<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ht_customers', function (Blueprint $table): void {
            $table->string('ui_locale', 5)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ht_customers', function (Blueprint $table): void {
            $table->dropColumn('ui_locale');
        });
    }
};
