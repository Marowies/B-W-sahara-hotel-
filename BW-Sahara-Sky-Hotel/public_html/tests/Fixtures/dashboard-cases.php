<?php

// Account overview figures from riorelax_customer_dashboard() on the seeded fixture customers,
// including the empty and cancelled-only states, and a bound on the queries it runs.
// Run after seed-account-fixture.php: php tests/Fixtures/dashboard-cases.php
use Botble\Hotel\Models\Customer;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/bootstrap.php';

$expected = [
    'a' => ['total' => 3, 'upcoming_count' => 1, 'shown' => 'FIXTURE-1001', 'upcoming' => true],
    'b' => ['total' => 0, 'upcoming_count' => 0, 'shown' => null, 'upcoming' => false],
    'c' => ['total' => 2, 'upcoming_count' => 0, 'shown' => 'FIXTURE-C-CANCELLED', 'upcoming' => false],
    'd' => ['total' => 4, 'upcoming_count' => 3, 'shown' => 'FIXTURE-D-TODAY', 'upcoming' => true],
    'e' => ['total' => 1, 'upcoming_count' => 0, 'shown' => 'FIXTURE-E-NOROOM', 'upcoming' => false],
];
$failures = 0;
foreach ($expected as $key => $want) {
    $customer = Customer::query()->where('email', "fixture.guest.$key@example.invalid")->firstOrFail();
    DB::flushQueryLog();
    DB::enableQueryLog();
    $result = riorelax_customer_dashboard($customer);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    $shown = $result['upcoming'] ?: $result['latest'];
    $checks = [
        'counts' => $result['total'] === $want['total'] && $result['upcoming_count'] === $want['upcoming_count'],
        'booking shown' => $shown?->booking_number === $want['shown'] && ($result['upcoming'] !== null) === $want['upcoming'],
        'only own bookings' => ! $shown || (int) $shown->customer_id === (int) $customer->getKey(),
        'bounded queries' => count($queries) <= 6 && collect($queries)->every(fn ($q) => ! preg_match('/^select \* from "ht_bookings"/i', $q['query']) || str_contains(strtolower($q['query']), 'limit 1')),
    ];
    foreach ($checks as $name => $ok) {
        $failures += $ok ? 0 : 1;
        printf("%s %s: %s\n", $ok ? 'PASS' : 'FAIL', $key, $name);
    }
}
exit($failures ? 1 : 0);
