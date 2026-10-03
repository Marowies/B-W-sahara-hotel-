<?php

namespace Botble\Hotel\Commands;

use Botble\Hotel\Emails\ICalSyncFailureEmail;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Services\ICalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SyncCalendarsCommand extends Command
{
    protected $signature = 'hotel:sync-calendars {--room_id= : The ID of the room to sync} {--notify : Send email notifications for failures}';

    protected $description = 'Sync external calendars for rooms';

    public function handle(ICalService $iCalService)
    {
        $roomId = $this->option('room_id');

        if ($roomId) {
            $room = Room::query()->find($roomId);

            if (! $room) {
                $this->error("Room with ID {$roomId} not found.");

                return 1;
            }

            if ($room->calendars->isEmpty()) {
                $this->info("Room {$room->name} has no external calendars.");

                return 0;
            }

            $this->info("Syncing calendars for room: {$room->name}");
            $results = $iCalService->syncExternalCalendars($room);

            $this->displayResults($results);

            return 0;
        }

        $this->components->info('Syncing calendars for all rooms...');

        $rooms = Room::query()->with('calendars')->get();
        $totalResults = [
            'success' => 0,
            'failed' => 0,
            'events' => 0,
            'errors' => [],
        ];

        foreach ($rooms as $room) {
            if ($room->calendars->isEmpty()) {
                continue;
            }

            $this->info("Syncing calendars for room: {$room->name}");

            try {
                $results = $iCalService->syncExternalCalendars($room);

                $totalResults['success'] += $results['success'];
                $totalResults['failed'] += $results['failed'];
                $totalResults['events'] += $results['events'];
                $totalResults['errors'] = array_merge($totalResults['errors'], $results['errors']);
            } catch (\Exception $e) {
                $totalResults['failed']++;
                $totalResults['errors'][] = "Error syncing room {$room->name}: {$e->getMessage()}";
                Log::error("iCal sync error for room {$room->name}: {$e->getMessage()}", ['exception' => $e]);

                // Send email notification if --notify flag is set
                if ($this->option('notify')) {
                    $this->sendFailureNotification($room, $e->getMessage());
                }
            }
        }

        $this->displayResults($totalResults);

        return 0;
    }

    protected function displayResults(array $results): void
    {
        $this->components->info('Sync completed:');
        $this->components->info("- Successful calendars: {$results['success']}");
        $this->components->info("- Failed calendars: {$results['failed']}");
        $this->components->info("- Total events processed: {$results['events']}");

        if (! empty($results['errors'])) {
            $this->error('Errors:');
            foreach ($results['errors'] as $error) {
                $this->error("- {$error}");
            }
        }
    }

    /**
     * Send email notification for sync failures
     */
    protected function sendFailureNotification(Room $room, string $errorMessage): void
    {
        try {
            $adminEmail = setting('admin_email');

            if (! $adminEmail) {
                $this->warn('Admin email not configured. Skipping notification.');

                return;
            }

            $data = [
                'time' => now()->format('Y-m-d H:i:s'),
                'room_name' => $room->name,
                'error' => $errorMessage,
                'logs_url' => route('ical.logs.index'),
            ];

            Mail::to($adminEmail)->send(new ICalSyncFailureEmail($data));

            $this->info("Sent failure notification email to {$adminEmail}");
        } catch (\Exception $e) {
            $this->error("Failed to send notification email: {$e->getMessage()}");
            Log::error("Failed to send iCal sync failure notification: {$e->getMessage()}", ['exception' => $e]);
        }
    }
}
