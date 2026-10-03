<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('botble__nulleds', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('status', 60)->default('published');
            $table->timestamps();
        });

        Schema::create('botble__nulleds_translations', function (Blueprint $table) {
            $table->string('lang_code');
            $table->foreignId('botble__nulleds_id');
            $table->string('name', 255)->nullable();

            $table->primary(['lang_code', 'botble__nulleds_id'], 'botble__nulleds_translations_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('botble__nulleds');
        Schema::dropIfExists('botble__nulleds_translations');
    }
};
