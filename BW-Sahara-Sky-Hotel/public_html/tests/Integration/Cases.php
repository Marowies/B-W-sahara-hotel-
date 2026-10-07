<?php

use Botble\Hotel\Models\Room;
use Botble\Hotel\Models\RoomCalendar;
use Botble\Hotel\Services\ICalService;
use Illuminate\Http\Request;

$db->statement('CREATE TABLE ht_room_calendars (id INTEGER PRIMARY KEY, room_id INTEGER, name TEXT, url TEXT, last_synced_at TEXT, created_at TEXT, updated_at TEXT)');
$db->statement('CREATE TABLE ht_ical_sync_logs (id INTEGER PRIMARY KEY ' . ($db->getDriverName() === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ', room_id INTEGER, calendar_id INTEGER, status TEXT, message TEXT, data TEXT, created_at TEXT, updated_at TEXT)');
// Exercise the actual deployment migration against the isolated test schema.
Illuminate\Support\Facades\Schema::swap($db->getSchemaBuilder());
$identityMigration = require dirname(__DIR__, 2) . '/platform/plugins/hotel/database/migrations/2026_10_05_000001_track_calendar_event_identity.php';
$identityMigration->up();
Illuminate\Support\Facades\Log::swap(new class { public function error(...$args): void {} });

class FixtureCalendarService extends ICalService
{
    public string $fixture = '';
    protected function fetchCalendarContent(string $url): ?string { return $this->fixture; }
}

function calendarFixture(string $start = '20261110', string $end = '20261112', string $status = 'CONFIRMED'): string
{
    return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:synthetic-event@example.invalid\r\nDTSTART;VALUE=DATE:$start\r\nDTEND;VALUE=DATE:$end\r\nSTATUS:$status\r\nEND:VEVENT\r\nEND:VCALENDAR";
}

function calendarRoom(): Room
{
    global $db;
    $db->table('ht_rooms')->insert(['id' => 910, 'name' => 'Synthetic calendar room', 'images' => '[]', 'status' => 'published', 'price' => 100, 'number_of_rooms' => 2, 'max_adults' => 2, 'max_children' => 1, 'currency_id' => 1]);
    $db->table('ht_room_calendars')->insert(['id' => 910, 'room_id' => 910, 'name' => 'Synthetic calendar', 'url' => 'https://calendar.example.invalid/feed.ics']);

    return Room::query()->with('calendars')->findOrFail(910);
}

transactionTest('Integration: calendar import blocks exactly two nights and repeats without duplicates', function () use ($db): void {
    $room = calendarRoom();
    $service = new FixtureCalendarService();
    $service->fixture = calendarFixture();
    $first = $service->syncExternalCalendars($room);
    $second = $service->syncExternalCalendars($room);
    $row = $db->table('ht_booking_rooms')->where('room_id', 910)->first();
    check($first['created'] === 1 && $second['created'] === 0, 'Repeat import duplicates an unchanged event.');
    check($row->start_date === '2026-11-10' && $row->end_date === '2026-11-12', 'Exclusive dates changed during import.');
    check($db->table('ht_room_calendars')->where('id', 910)->value('last_synced_at') !== null, 'Sync timestamp missing.');
});

transactionTest('Integration: changed calendar UID moves the original block instead of leaving stale dates', function () use ($db): void {
    $room = calendarRoom();
    $service = new FixtureCalendarService();
    $service->fixture = calendarFixture();
    $service->syncExternalCalendars($room);
    $service->fixture = calendarFixture('20261120', '20261122');
    $service->syncExternalCalendars($room);
    $rows = $db->table('ht_booking_rooms')->where('room_id', 910)->get();
    check($rows->count() === 1 && $rows->first()->start_date === '2026-11-20', 'UID update leaves a stale block.');
});

transactionTest('Integration: deleted calendar event releases its imported inventory', function () use ($db): void {
    $room = calendarRoom();
    $service = new FixtureCalendarService();
    $service->fixture = calendarFixture();
    $service->syncExternalCalendars($room);
    $service->fixture = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR";
    $service->syncExternalCalendars($room);
    check($db->table('ht_booking_rooms')->where('room_id', 910)->count() === 0, 'Deleted event still consumes inventory.');
});

transactionTest('Integration: cancelled calendar event is not imported as an active booking', function () use ($db): void {
    $room = calendarRoom();
    $service = new FixtureCalendarService();
    $service->fixture = calendarFixture(status: 'CANCELLED');
    $service->syncExternalCalendars($room);
    check($db->table('ht_booking_rooms')->where('room_id', 910)->count() === 0, 'Cancelled event blocks inventory.');
});

transactionTest('Integration: reversed calendar dates are rejected without saving a booking', function () use ($db): void {
    $room = calendarRoom();
    $service = new FixtureCalendarService();
    $service->fixture = calendarFixture('20261112', '20261110');
    $service->syncExternalCalendars($room);
    check($db->table('ht_booking_rooms')->where('room_id', 910)->count() === 0, 'Invalid calendar range was saved.');
});

transactionTest('Integration: identical dates from independent calendars remain independent inventory blocks', function () use ($db): void {
    $room = calendarRoom();
    $db->table('ht_room_calendars')->insert(['id' => 911, 'room_id' => 910, 'name' => 'Second synthetic source', 'url' => 'https://calendar.example.invalid/second.ics']);
    $room->load('calendars');
    $service = new FixtureCalendarService();
    $service->fixture = calendarFixture();
    $service->syncExternalCalendars($room);
    check($db->table('ht_booking_rooms')->where('room_id', 910)->count() === 2, 'Independent feeds incorrectly merged.');
});

test('Integration: four concurrent checkouts compete for one room on InnoDB', function () use ($db, $argv): void {
    check($db->getDriverName() === 'mysql', 'Real InnoDB database required; SQLite cannot verify this test.');
    $db->table('ht_rooms')->insert(['id' => 900, 'name' => 'Synthetic concurrent room', 'images' => '[]', 'status' => 'published', 'price' => 100, 'number_of_rooms' => 1, 'max_adults' => 2, 'max_children' => 1, 'currency_id' => 1]);
    $start = microtime(true) + 2;
    $processes = [];
    foreach (range(1, 4) as $worker) {
        $command = [PHP_BINARY, '-c', php_ini_loaded_file(), __DIR__ . '/run.php', $argv[1], $argv[2], 'worker', (string) $worker, (string) $start];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        check(is_resource($process), 'Could not start worker.');
        fclose($pipes[0]);
        $processes[] = [$process, $pipes];
    }
    $outcomes = [];
    foreach ($processes as [$process, $pipes]) {
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $exit = proc_close($process);
        check($exit === 0, 'Worker failed: ' . $error);
        $outcomes[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR)['result'];
    }
    $quantity = $db->table('ht_booking_rooms')->where('room_id', 900)->sum('number_of_rooms');
    $GLOBALS['integrationEvidence']['concurrency'] = ['workers' => 4, 'outcomes' => $outcomes, 'rooms_booked' => (int) $quantity, 'server' => $db->selectOne('SELECT VERSION() AS version')->version];
    check(count(array_filter($outcomes, fn ($result) => $result === 'booked')) === 1 && (int) $quantity === 1, 'Concurrent checkout oversells inventory.');
});

test('Integration: concurrent calendar import and checkout cannot oversell one room', function () use ($db, $argv): void {
    check($db->getDriverName() === 'mysql', 'Real InnoDB required.');
    $db->table('ht_rooms')->insert(['id' => 920, 'name' => 'Synthetic cross-writer room', 'images' => '[]', 'status' => 'published', 'price' => 100, 'number_of_rooms' => 1, 'max_adults' => 2, 'max_children' => 1, 'currency_id' => 1]);
    $db->table('ht_room_calendars')->insert(['id' => 920, 'room_id' => 920, 'name' => 'Synthetic race feed', 'url' => 'https://calendar.example.invalid/race.ics']);
    $marker = sys_get_temp_dir() . '/hotel-test-barrier-' . bin2hex(random_bytes(8));
    $processes = [];
    $start = microtime(true) + 1;
    try {
        foreach (['checkout-hold', 'ical'] as $offset => $kind) {
            $command = [PHP_BINARY, '-c', php_ini_loaded_file(), __DIR__ . '/run.php', $argv[1], $argv[2], 'worker', (string) ($offset + 5), (string) $start, $kind, '920', $marker];
            $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            check(is_resource($process), 'Could not start cross-writer process.');
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        $outcomes = [];
        foreach ($processes as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            check(proc_close($process) === 0, 'Cross-writer process failed: ' . $error);
            $outcomes[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR)['result'];
        }
        $quantity = (int) $db->table('ht_booking_rooms')->where('room_id', 920)->sum('number_of_rooms');
        $GLOBALS['integrationEvidence']['cross_writer_race'] = ['outcomes' => $outcomes, 'capacity' => 1, 'rooms_booked' => $quantity, 'barrier' => 'calendar writer starts after checkout inventory snapshot'];
        check($quantity <= 1, 'Calendar import bypasses the checkout room lock and oversells inventory.');
    } finally {
        if (is_file($marker)) { unlink($marker); }
    }
});

require __DIR__ . '/CalendarCases.php';
require __DIR__ . '/PaymentCases.php';
