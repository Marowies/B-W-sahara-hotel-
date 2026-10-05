<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$connection = require $argv[1];
if (($connection['host'] ?? null) !== '127.0.0.1' || ! str_starts_with($connection['database'] ?? '', 'hotel_test_')) {
    throw new RuntimeException('Only a synthetic loopback hotel_test_ database is allowed.');
}
$capsule = new Illuminate\Database\Capsule\Manager();$capsule->addConnection($connection);$db = $capsule->getConnection();
// This requires a disposable schema prepared by the integration runner; no live CMS schema is modified.
for ($id = 1001; $id <= 1100; $id++) {
    $db->table('ht_rooms')->insert(['id' => $id, 'name' => 'Synthetic load room ' . $id, 'images' => '[]', 'status' => 'published',
        'price' => 100, 'number_of_rooms' => $id === 1001 ? 200000 : 5, 'max_adults' => 2, 'max_children' => 1,
        'currency_id' => 1, 'tax_id' => 1, 'created_at' => '2026-10-05', 'room_category_id' => 1, 'number_of_beds' => 1, 'size' => 30]);
}
echo "100 synthetic room rows prepared.\n";
