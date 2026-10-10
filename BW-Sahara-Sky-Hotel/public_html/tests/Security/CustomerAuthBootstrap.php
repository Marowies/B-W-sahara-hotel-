<?php

Illuminate\Support\Facades\Schema::swap($db->getSchemaBuilder());
$db->getSchemaBuilder()->table('ht_customers', function ($table): void {
    $table->string('password')->nullable();
    $table->timestamp('confirmed_at')->nullable();
    $table->timestamps();
});
$db->table('ht_customers')->insert(['id' => 43, 'first_name' => 'Synthetic', 'last_name' => 'Auth', 'email' => 'synthetic@example.invalid']);
foreach (['2026_10_07_170000_create_hotel_login_codes_table.php', '2026_10_08_090000_create_hotel_auth_tokens_table.php', '2026_10_08_110000_harden_customer_auth_concurrency.php'] as $migration) {
    (require $source . '/database/migrations/' . $migration)->up();
}

// Exercise production logic while replacing only delivery of external notifications.
$app->bind(App\Services\HotelTokenService::class, fn () => new class extends App\Services\HotelTokenService {
    protected function notifyReuse(array $alert): void { $GLOBALS['authAlerts'][] = $alert; }
});
