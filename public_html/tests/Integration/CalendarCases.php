<?php

use Botble\Hotel\Models\RoomCalendar;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Services\ICalService;

// Safety and lifecycle regressions for snapshot reconciliation.
transactionTest('Calendar: moving an event preserves its booking and block identities', function () use ($db): void {
    $room = calendarRoom();
    $service = new FixtureCalendarService();
    $service->fixture = calendarFixture();
    $service->syncExternalCalendars($room);
    $before = $db->table('ht_booking_rooms')->where('room_id', 910)->first();
    $service->fixture = calendarFixture('20261120', '20261122');
    $result = $service->syncExternalCalendars($room);
    $after = $db->table('ht_booking_rooms')->where('room_id', 910)->first();
    check($result['updated'] === 1 && $before->id === $after->id && $before->booking_id === $after->booking_id, 'Update replaced the event identity.');
});

transactionTest('Calendar: cancellation releases an existing block and reappearance blocks again', function () use ($db): void {
    $room = calendarRoom();
    $service = new FixtureCalendarService();
    $service->fixture = calendarFixture();
    $service->syncExternalCalendars($room);
    $bookingId = $db->table('ht_booking_rooms')->where('room_id', 910)->value('booking_id');
    $service->fixture = calendarFixture(status: 'CANCELLED');
    $result = $service->syncExternalCalendars($room);
    check($result['removed'] === 1 && $db->table('ht_booking_rooms')->where('room_id', 910)->count() === 0, 'Cancellation did not release inventory.');
    check($db->table('ht_bookings')->where('id', $bookingId)->value('status') === 'cancelled', 'Audit booking was not cancelled.');
    $service->fixture = calendarFixture();
    check($service->syncExternalCalendars($room)['created'] === 1, 'Reappearing event was not restored.');
});

foreach ([
    'failed fetch' => '',
    'HTML instead of calendar' => '<html>temporary error</html>',
    'truncated calendar' => "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nUID:partial",
    'missing UID' => str_replace("UID:synthetic-event@example.invalid\r\n", '', calendarFixture()),
    'invalid date rollover' => calendarFixture('20260230', '20260302'),
    'missing departure' => str_replace("DTEND;VALUE=DATE:20261112\r\n", '', calendarFixture()),
    'unsupported recurrence' => str_replace('STATUS:CONFIRMED', "RRULE:FREQ=DAILY\r\nSTATUS:CONFIRMED", calendarFixture()),
    'duplicate UID' => str_replace('END:VCALENDAR', "BEGIN:VEVENT\r\nUID:synthetic-event@example.invalid\r\nDTSTART;VALUE=DATE:20261120\r\nDTEND;VALUE=DATE:20261122\r\nEND:VEVENT\r\nEND:VCALENDAR", calendarFixture()),
] as $label => $fixture) {
    transactionTest('Calendar: ' . $label . ' preserves previous inventory and sync revision', function () use ($db, $fixture): void {
        $room = calendarRoom();
        $service = new FixtureCalendarService();
        $service->fixture = calendarFixture();
        $service->syncExternalCalendars($room);
        $version = $db->table('ht_room_calendars')->where('id', 910)->value('sync_version');
        $service->fixture = $fixture;
        $result = $service->syncExternalCalendars($room);
        check($result['failed'] === 1 && $result['success'] === 0, 'Invalid source was accepted.');
        check($db->table('ht_booking_rooms')->where('room_id', 910)->count() === 1, 'Failure released previous inventory.');
        check($db->table('ht_room_calendars')->where('id', 910)->value('sync_version') === $version, 'Failed snapshot advanced sync revision.');
    });
}

transactionTest('Calendar: one invalid event prevents partial application of the entire snapshot', function () use ($db): void {
    $room = calendarRoom();
    $service = new FixtureCalendarService();
    $service->fixture = str_replace('END:VCALENDAR', "BEGIN:VEVENT\r\nUID:invalid-second\r\nDTSTART;VALUE=DATE:20261112\r\nDTEND;VALUE=DATE:20261110\r\nEND:VEVENT\r\nEND:VCALENDAR", calendarFixture());
    $result = $service->syncExternalCalendars($room);
    check($result['failed'] === 1 && $db->table('ht_booking_rooms')->where('room_id', 910)->count() === 0, 'Snapshot was applied partially.');
});

transactionTest('Calendar: capacity conflict rolls back the source and preserves its previous dates', function () use ($db): void {
    $room = calendarRoom();
    $db->table('ht_rooms')->where('id', 910)->update(['number_of_rooms' => 1]);
    $service = new FixtureCalendarService();
    $service->fixture = calendarFixture();
    $service->syncExternalCalendars($room);
    $db->table('ht_bookings')->insert(['id' => 810, 'booking_number' => 'LOCAL-810', 'status' => 'confirmed']);
    $db->table('ht_booking_rooms')->insert(['room_id' => 910, 'booking_id' => 810, 'number_of_rooms' => 1, 'start_date' => '2026-11-20', 'end_date' => '2026-11-22']);
    $service->fixture = calendarFixture('20261120', '20261122');
    $result = $service->syncExternalCalendars($room);
    check($result['conflicts'] === 1 && $result['failed'] === 1, 'Capacity conflict was not reported.');
    check($db->table('ht_booking_rooms')->where('ical_calendar_id', 910)->value('start_date') === '2026-11-10', 'Conflict mutated the previous source block.');
});

transactionTest('Calendar: simultaneous date swaps are validated against the final snapshot', function () use ($db): void {
    $room = calendarRoom();
    $db->table('ht_rooms')->where('id', 910)->update(['number_of_rooms' => 1]);
    $service = new FixtureCalendarService();
    $second = str_replace('synthetic-event@example.invalid', 'second@example.invalid', calendarFixture('20261120', '20261122'));
    $service->fixture = str_replace('END:VCALENDAR', '', calendarFixture()) . str_replace("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n", '', $second);
    check($service->syncExternalCalendars($room)['created'] === 2, 'Initial nonoverlapping events failed.');
    $a = calendarFixture('20261120', '20261122');
    $b = str_replace('synthetic-event@example.invalid', 'second@example.invalid', calendarFixture());
    $service->fixture = str_replace('END:VCALENDAR', '', $a) . str_replace("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n", '', $b);
    check($service->syncExternalCalendars($room)['updated'] === 2, 'Valid date swap was falsely rejected.');
    check($db->table('ht_booking_rooms')->where('ical_uid_hash', hash('sha256', 'synthetic-event@example.invalid'))->value('start_date') === '2026-11-20', 'First UID did not move.');
});

transactionTest('Calendar: deleting a source releases only its own blocks', function () use ($db): void {
    $room = calendarRoom();
    $db->table('ht_room_calendars')->insert(['id' => 911, 'room_id' => 910, 'name' => 'Independent source', 'url' => 'https://calendar.example.invalid/second.ics']);
    $room->load('calendars');
    $service = new FixtureCalendarService();
    $service->fixture = calendarFixture();
    $service->syncExternalCalendars($room);
    $service->deleteCalendar(RoomCalendar::query()->findOrFail(910));
    check($db->table('ht_booking_rooms')->where('room_id', 910)->count() === 1, 'Delete touched another source.');
    check($db->table('ht_booking_rooms')->where('room_id', 910)->value('ical_calendar_id') === 911, 'Wrong source remains.');
});

transactionTest('Calendar: revision change during download rejects a stale snapshot', function () use ($db): void {
    $room = calendarRoom();
    $service = new class extends ICalService {
        protected function fetchCalendarContent(string $url): ?string {
            RoomCalendar::query()->where('id', 910)->increment('sync_version');
            return calendarFixture();
        }
    };
    check($service->syncExternalCalendars($room)['failed'] === 1, 'Stale download overwrote a newer calendar revision.');
    check($db->table('ht_booking_rooms')->where('room_id', 910)->count() === 0, 'Stale snapshot wrote inventory.');
});

test('Calendar: database failure rolls back booking and block writes', function () use ($db): void {
    $room = calendarRoom();
    $trigger = $db->getDriverName() === 'mysql'
        ? "CREATE TRIGGER fail_ical_block BEFORE INSERT ON ht_booking_rooms FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'synthetic write failure'"
        : "CREATE TRIGGER fail_ical_block BEFORE INSERT ON ht_booking_rooms BEGIN SELECT RAISE(FAIL, 'synthetic write failure'); END";
    $db->statement($trigger);
    $before = $db->table('ht_bookings')->count();
    try {
        $service = new FixtureCalendarService();
        $service->fixture = calendarFixture();
        check($service->syncExternalCalendars($room)['failed'] === 1, 'Write failure not reported.');
        check($db->table('ht_bookings')->count() === $before && $db->table('ht_booking_rooms')->where('room_id', 910)->count() === 0, 'Partial write persisted.');
    } finally {
        $db->statement('DROP TRIGGER fail_ical_block');
        $db->table('ht_room_calendars')->where('id', 910)->delete();
        $db->table('ht_rooms')->where('id', 910)->delete();
    }
});

transactionTest('Calendar: legacy unmapped blocks are preserved rather than guessed or deleted', function () use ($db): void {
    $room = calendarRoom();
    $db->table('ht_bookings')->insert(['id' => 820, 'booking_number' => 'ICAL-LEGACY', 'status' => 'completed']);
    $db->table('ht_booking_rooms')->insert(['room_id' => 910, 'booking_id' => 820, 'number_of_rooms' => 1, 'start_date' => '2026-11-10', 'end_date' => '2026-11-12']);
    $service = new FixtureCalendarService();
    $service->fixture = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR";
    $service->syncExternalCalendars($room);
    check($db->table('ht_booking_rooms')->where('booking_id', 820)->exists(), 'Legacy inventory was silently discarded.');
});

transactionTest('Calendar: failed synchronization is returned as an error to the admin UI', function () use ($db): void {
    calendarRoom();
    $service = new FixtureCalendarService();
    $service->fixture = '<html>failure</html>';
    $reflection = new ReflectionClass(Botble\Hotel\Http\Controllers\ICalController::class);
    $controller = $reflection->newInstanceWithoutConstructor();
    $reflection->getProperty('iCalService')->setValue($controller, $service);
    $request = Botble\Hotel\Http\Requests\SyncCalendarRequest::create('/ical/sync', 'POST', ['room_id' => 910]);
    $response = $controller->sync($request, new Botble\Base\Http\Responses\BaseHttpResponse());
    check($response->toArray()['error'] === true, 'Failure was reported as a successful synchronization.');
});

test('Calendar: checkout waits for calendar inventory lock and cannot oversell', function () use ($db, $argv): void {
    check($db->getDriverName() === 'mysql', 'Real InnoDB required.');
    $db->table('ht_rooms')->insert(['id' => 940, 'name' => 'Calendar-first race', 'images' => '[]', 'status' => 'published', 'price' => 100, 'number_of_rooms' => 1, 'max_adults' => 2, 'max_children' => 1, 'currency_id' => 1]);
    $db->table('ht_room_calendars')->insert(['id' => 940, 'room_id' => 940, 'name' => 'Calendar-first source', 'url' => 'https://calendar.example.invalid/race.ics']);
    $marker = sys_get_temp_dir() . '/hotel-test-calendar-first-' . bin2hex(random_bytes(8));
    $processes = [];
    $start = microtime(true) + 1;
    try {
        foreach (['ical-hold', 'checkout-wait'] as $offset => $kind) {
            $command = [PHP_BINARY, '-c', php_ini_loaded_file(), __DIR__ . '/run.php', $argv[1], $argv[2], 'worker', (string) ($offset + 7), (string) $start, $kind, '940', $marker];
            $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            check(is_resource($process), 'Could not start reverse race worker.');
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        $outcomes = [];
        foreach ($processes as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            check(proc_close($process) === 0, 'Worker failed: ' . $error);
            $outcomes[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR)['result'];
        }
        $quantity = (int) $db->table('ht_booking_rooms')->where('room_id', 940)->sum('number_of_rooms');
        $GLOBALS['integrationEvidence']['calendar_first_race'] = ['outcomes' => $outcomes, 'capacity' => 1, 'rooms_booked' => $quantity];
        check($outcomes === ['imported', 'unavailable'] && $quantity === 1, 'Calendar-first race oversold inventory.');
    } finally {
        if (is_file($marker)) { unlink($marker); }
    }
});

test('Calendar: deployment migration rolls back and reapplies cleanly', function () use ($db, $identityMigration): void {
    $identityMigration->down();
    check(! $db->getSchemaBuilder()->hasColumn('ht_booking_rooms', 'ical_uid_hash'), 'Migration rollback did not remove identity field.');
    $identityMigration->up();
    check($db->getSchemaBuilder()->hasColumn('ht_booking_rooms', 'ical_uid_hash') && $db->getSchemaBuilder()->hasColumn('ht_room_calendars', 'sync_version'), 'Migration reapply failed.');
});
