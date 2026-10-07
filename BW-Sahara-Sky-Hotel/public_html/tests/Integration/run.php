<?php

// php tests/Integration/run.php /path/to/vendor/autoload.php /path/to/private-local-db-config.php
// The named database must be empty and disposable. No real hotel data is used.
define('HOTEL_INTEGRATION_TESTS', true);
if (($argv[3] ?? null) === 'calendar-fixes') {
    define('HOTEL_CALENDAR_FIX_TESTS', true);
}
if (($argv[3] ?? null) === 'worker') {
    define('HOTEL_INTEGRATION_WORKER', true);
}
require dirname(__DIR__) . '/Security/run.php';
