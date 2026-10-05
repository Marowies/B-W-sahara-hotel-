<?php

namespace Botble\Hotel\Services;

use Botble\Hotel\Enums\BookingStatusEnum;
use Botble\Hotel\Models\Booking;
use Botble\Hotel\Models\BookingRoom;
use Botble\Hotel\Models\ICalSyncLog;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Models\RoomCalendar;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ICalService
{
    protected const ICAL_DATE_FORMAT = 'Ymd\THis\Z';

    protected const ICAL_DATE_FORMAT_DAY = 'Ymd';

    public function generateICalContent(Room $room): string
    {
        $bookingRooms = BookingRoom::query()
            ->where('room_id', $room->id)
            ->whereHas('booking', function ($query): void {
                $query->where('status', '!=', BookingStatusEnum::CANCELLED);
            })
            ->with('booking')
            ->get();

        $content = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Botble//Hotel Booking//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($bookingRooms as $bookingRoom) {
            $booking = $bookingRoom->booking;
            $startDate = Carbon::parse($bookingRoom->start_date);
            $endDate = Carbon::parse($bookingRoom->end_date);

            $content[] = 'BEGIN:VEVENT';
            $content[] = 'UID:' . hash_hmac('sha256', $room->getKey() . ':' . $bookingRoom->getKey(), (string) config('app.key')) . '@hotel';
            $content[] = 'DTSTART;VALUE=DATE:' . $startDate->format(self::ICAL_DATE_FORMAT_DAY);
            $content[] = 'DTEND;VALUE=DATE:' . $endDate->format(self::ICAL_DATE_FORMAT_DAY);
            $content[] = 'SUMMARY:Reserved';
            $content[] = 'DESCRIPTION:Unavailable';
            $content[] = 'CREATED:' . Carbon::parse($booking->created_at)->utc()->format(self::ICAL_DATE_FORMAT);
            $content[] = 'LAST-MODIFIED:' . Carbon::parse($booking->updated_at)->utc()->format(self::ICAL_DATE_FORMAT);
            $content[] = 'STATUS:CONFIRMED';
            $content[] = 'END:VEVENT';
        }

        $content[] = 'END:VCALENDAR';

        return implode("\r\n", $content);
    }

    public function parseICalContent(string $content): Collection
    {
        $content = preg_replace('/\r?\n[ \t]/', '', trim($content));
        $lines = preg_split('/\r\n|\n|\r/', $content);
        $events = collect();
        $inCalendar = false;
        $closed = false;
        $current = null;
        foreach ($lines as $line) {
            if ($line === 'BEGIN:VCALENDAR') {
                if ($inCalendar || $closed) {
                    throw new \InvalidArgumentException('Invalid calendar envelope.');
                }
                $inCalendar = true;
            } elseif ($line === 'END:VCALENDAR') {
                if (! $inCalendar || $current !== null) {
                    throw new \InvalidArgumentException('Incomplete calendar event.');
                }
                $inCalendar = false;
                $closed = true;
            } elseif (! $inCalendar) {
                if (trim($line) !== '') {
                    throw new \InvalidArgumentException('Content outside calendar envelope.');
                }
            } elseif ($line === 'BEGIN:VEVENT') {
                if ($current !== null) {
                    throw new \InvalidArgumentException('Nested calendar event.');
                }
                $current = [];
            } elseif ($line === 'END:VEVENT') {
                if ($current === null) {
                    throw new \InvalidArgumentException('Unexpected event end.');
                }
                $events->push($current);
                $current = null;
            } elseif ($current !== null) {
                $parts = explode(':', $line, 2);
                if (count($parts) !== 2) {
                    throw new \InvalidArgumentException('Invalid event property.');
                }
                $key = strtoupper(explode(';', $parts[0], 2)[0]);
                if (in_array($key, ['RRULE', 'RDATE', 'EXDATE', 'RECURRENCE-ID', 'DURATION'])) {
                    throw new \InvalidArgumentException('Unsupported recurring or duration-based event.');
                }
                if (isset($current[$key]) && in_array($key, ['UID', 'DTSTART', 'DTEND', 'STATUS'])) {
                    throw new \InvalidArgumentException('Duplicate event property.');
                }
                $current[$key] = $parts[1];
                if (preg_match('/(?:^|;)TZID=([^;]+)/i', $parts[0], $match)) {
                    $current[$key . '_TZID'] = trim($match[1], '"');
                }
            }
        }
        if (! $closed || $inCalendar || $current !== null) {
            throw new \InvalidArgumentException('Incomplete calendar snapshot.');
        }

        return $events;
    }

    public function syncExternalCalendars(Room $room): array
    {
        $results = ['success' => 0, 'failed' => 0, 'events' => 0, 'errors' => [],
            'conflicts' => 0, 'created' => 0, 'updated' => 0, 'removed' => 0];

        foreach ($room->calendars as $source) {
            try {
                // Fetch outside the inventory lock. Reject stale downloads using a persisted revision.
                $calendar = RoomCalendar::query()->where('room_id', $room->id)->findOrFail($source->id);
                $content = $this->fetchCalendarContent($calendar->url);
                if (! $content) {
                    throw new \RuntimeException('Failed to fetch calendar content.');
                }
                $events = $this->parseICalContent($content);
                $results['events'] += $events->count();
                $snapshot = [];
                foreach ($events as $event) {
                    if (trim($event['UID'] ?? '') === '') {
                        throw new \InvalidArgumentException('Calendar event has no UID.');
                    }
                    $key = hash('sha256', $event['UID']);
                    if (array_key_exists($key, $snapshot)) {
                        throw new \InvalidArgumentException('Duplicate UID in calendar snapshot.');
                    }
                    if (strtoupper($event['STATUS'] ?? '') === 'CANCELLED') {
                        $snapshot[$key] = null;
                        continue;
                    }
                    if (! isset($event['DTSTART'], $event['DTEND'])) {
                        throw new \InvalidArgumentException('Event requires start and exclusive end dates.');
                    }
                    $start = $this->parseICalDate($event['DTSTART'], $event['DTSTART_TZID'] ?? null);
                    $end = $this->parseICalDate($event['DTEND'], $event['DTEND_TZID'] ?? null);
                    \Botble\Hotel\DataTransferObjects\StayDates::from($start, $end);
                    $snapshot[$key] = [$start, $end];
                }

                $counts = DB::transaction(function () use ($room, $calendar, $snapshot): array {
                    // Same first lock and order as checkout; read inventory only after taking it.
                    $lockedRoom = Room::query()->lockForUpdate()->findOrFail($room->id);
                    $lockedCalendar = RoomCalendar::query()->lockForUpdate()->findOrFail($calendar->id);
                    if ($lockedCalendar->room_id != $lockedRoom->id
                        || $lockedCalendar->url !== $calendar->url
                        || (int) $lockedCalendar->sync_version !== (int) $calendar->sync_version) {
                        throw new \RuntimeException('Calendar changed during download; retry synchronization.');
                    }
                    $existing = BookingRoom::query()->where('room_id', $room->id)
                        ->where('ical_calendar_id', $calendar->id)->get()->keyBy('ical_uid_hash');

                    // Validate the whole final snapshot, including moves/swaps and removals,
                    // before any mutation. Other sources and local bookings still consume capacity.
                    $inventory = $lockedRoom->activeBookingRooms
                        ->filter(fn ($row) => $row->ical_calendar_id != $calendar->id)->values();
                    $lockedRoom->setRelation('activeBookingRooms', $inventory);
                    foreach ($snapshot as $key => $dates) {
                        if ($dates === null) {
                            continue;
                        }
                        [$start, $end] = $dates;
                        if (! $lockedRoom->isAvailableAt(['start_date' => $start, 'end_date' => $end, 'rooms' => 1])) {
                            throw new CalendarInventoryConflict('Calendar snapshot exceeds room inventory.');
                        }
                        $inventory->push(new BookingRoom([
                            'start_date' => $start->toDateString(), 'end_date' => $end->toDateString(),
                            'number_of_rooms' => 1,
                        ]));
                    }
                    $counts = ['created' => 0, 'updated' => 0, 'removed' => 0];
                    foreach ($existing as $key => $row) {
                        if (! isset($snapshot[$key])) {
                            $this->removeImportedBlock($row);
                            $counts['removed']++;
                        }
                    }
                    foreach ($snapshot as $key => $dates) {
                        if ($dates === null) {
                            continue;
                        }
                        [$start, $end] = $dates;
                        $row = $existing->get($key);
                        if ($row) {
                            $row->start_date = $start->toDateString();
                            $row->end_date = $end->toDateString();
                            $row->save();
                            // Restore a previously cancelled imported booking when it reappears.
                            $booking = $row->booking;
                            $booking->status = BookingStatusEnum::COMPLETED;
                            $booking->save();
                            $counts['updated']++;
                        } else {
                            $this->createBlockedDate($lockedRoom, $start, $end, $lockedCalendar, $key);
                            $counts['created']++;
                        }
                    }
                    $lockedCalendar->last_synced_at = Carbon::now();
                    $lockedCalendar->sync_version = (int) $lockedCalendar->sync_version + 1;
                    $lockedCalendar->save();
                    $this->logSync($room->id, $calendar->id, 'success', 'Calendar snapshot reconciled', $counts);

                    return $counts;
                });
                foreach ($counts as $key => $count) {
                    $results[$key] += $count;
                }
                $results['success']++;
            } catch (Exception $e) {
                $conflict = $e instanceof CalendarInventoryConflict;
                $results['conflicts'] += (int) $conflict;
                $results['failed']++;
                $results['errors'][] = 'Error syncing calendar ' . $source->name . ': ' . $e->getMessage();
                $this->logSync($room->id, $source->id, $conflict ? 'warning' : 'error', $e->getMessage());
                Log::error('iCal sync error: ' . $e->getMessage(), ['exception' => $e]);
            }
        }

        return $results;
    }

    protected function fetchCalendarContent(string $url): ?string
    {
        try {
            return app(SafeCalendarFetcher::class)->fetch($url);
        } catch (Exception $e) {
            Log::error('Failed to fetch iCal content: ' . $e->getMessage(), ['exception' => $e]);

            return null;
        }
    }

    protected function parseICalDate(string $date, ?string $timezone = null): Carbon
    {
        $format = match (true) {
            (bool) preg_match('/\A\d{8}\z/', $date) => 'Ymd',
            (bool) preg_match('/\A\d{8}T\d{6}Z\z/', $date) => self::ICAL_DATE_FORMAT,
            (bool) preg_match('/\A\d{8}T\d{6}\z/', $date) => 'Ymd\THis',
            default => throw new \InvalidArgumentException('Invalid iCal date format.'),
        };
        $hotelTimezone = config('app.timezone', 'UTC');
        $zone = str_ends_with($date, 'Z') ? 'UTC' : ($timezone ?? $hotelTimezone);
        $parsed = Carbon::createFromFormat('!' . $format, $date, $zone);
        if (! $parsed || $parsed->format($format) !== $date) {
            throw new \InvalidArgumentException('Invalid iCal date value.');
        }

        return ($format === 'Ymd' ? $parsed : $parsed->setTimezone($hotelTimezone))->startOfDay();
    }

    // Called only within a room-locked, capacity-validated snapshot transaction.
    protected function createBlockedDate(Room $room, Carbon $startDate, Carbon $endDate, RoomCalendar $calendar, string $uidHash): void
    {
        $booking = new Booking();
        $booking->status = BookingStatusEnum::COMPLETED;
        $booking->booking_number = 'ICAL-' . Str::random(12);
        $booking->amount = 0;
        $booking->sub_total = 0;
        $booking->tax_amount = 0;
        $booking->currency_id = $room->currency_id;
        $booking->save();
        BookingRoom::query()->create([
            'room_id' => $room->id, 'room_name' => $room->name,
            'room_image' => Arr::first($room->images), 'booking_id' => $booking->getKey(),
            'price' => 0, 'currency_id' => $room->currency_id, 'number_of_rooms' => 1,
            'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString(),
            'ical_calendar_id' => $calendar->id, 'ical_uid_hash' => $uidHash,
        ]);
    }

    protected function removeImportedBlock(BookingRoom $row): void
    {
        $booking = $row->booking;
        $row->delete();
        // Keep an audit booking but release its inventory; never delete a local guest booking.
        if (str_starts_with((string) $booking->booking_number, 'ICAL-')
            && ! BookingRoom::query()->where('booking_id', $booking->id)->exists()) {
            $booking->status = BookingStatusEnum::CANCELLED;
            $booking->save();
        }
    }

    public function updateCalendar(RoomCalendar $calendar, string $name, string $url): void
    {
        DB::transaction(function () use ($calendar, $name, $url): void {
            Room::query()->lockForUpdate()->findOrFail($calendar->room_id);
            $locked = RoomCalendar::query()->lockForUpdate()->findOrFail($calendar->id);
            $locked->name = $name;
            $locked->url = $url;
            $locked->sync_version = (int) $locked->sync_version + 1;
            $locked->save();
        });
    }

    public function deleteCalendar(RoomCalendar $calendar): void
    {
        DB::transaction(function () use ($calendar): void {
            Room::query()->lockForUpdate()->findOrFail($calendar->room_id);
            $locked = RoomCalendar::query()->lockForUpdate()->findOrFail($calendar->id);
            foreach (BookingRoom::query()->where('room_id', $locked->room_id)
                ->where('ical_calendar_id', $locked->id)->get() as $row) {
                $this->removeImportedBlock($row);
            }
            $locked->delete();
        });
    }

    protected function logSync(int $roomId, ?int $calendarId, string $status, string $message, array $data = []): void
    {
        ICalSyncLog::query()->create([
            'room_id' => $roomId, 'calendar_id' => $calendarId, 'status' => $status,
            'message' => $message, 'data' => $data,
        ]);
    }
}
