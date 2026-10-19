<?php

// Boots the real application for local browser/fixture checks and refuses anything but a disposable
// SQLite file whose name contains "fixture", outside production and staging.
// Usage (from public_html): php tests/Fixtures/<script>.php
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$connection = config('database.default');
$database = (string) config("database.connections.$connection.database");
if ($connection !== 'sqlite' || ! str_contains(strtolower(basename($database)), 'fixture') || app()->environment(['production', 'staging'])) {
    fwrite(STDERR, "Refusing: fixture scripts only run against a local *fixture*.sqlite database outside production/staging.\n");
    exit(2);
}

return $app;
