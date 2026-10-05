<?php

// Loopback-only service laboratory; never install this router in the public document root.
if (($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1' || ! getenv('HOTEL_PERF_DB_CONFIG')) {
    http_response_code(403);exit;
}
define('HOTEL_INTEGRATION_TESTS', true);
define('HOTEL_INTEGRATION_WORKER', true);
$argv = [__FILE__, dirname(__DIR__, 2) . '/vendor/autoload.php', getenv('HOTEL_PERF_DB_CONFIG'), 'worker', '0', '0', 'performance'];
require dirname(__DIR__) . '/Security/run.php';
