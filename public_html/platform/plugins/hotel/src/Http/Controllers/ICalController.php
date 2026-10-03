<?php

namespace Botble\Hotel\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Hotel\Forms\RoomCalendarForm;
use Botble\Hotel\Http\Requests\SyncCalendarRequest;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Models\RoomCalendar;
use Botble\Hotel\Services\ICalService;
use Botble\Slug\Facades\SlugHelper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ICalController extends BaseController
{
    public function __construct(protected ICalService $iCalService)
    {
        $this
            ->breadcrumb()
            ->add(trans('plugins/hotel::booking.bookings'), route('booking.index'))
            ->add(trans('plugins/hotel::ical.name'), route('ical.index'));
    }

    public function index()
    {
        $this->pageTitle(trans('plugins/hotel::ical.name'));

        Assets::addScriptsDirectly('vendor/core/plugins/hotel/js/ical-sync.js');

        $rooms = Room::query()
            ->with('calendars')
            ->get();

        return view('plugins/hotel::ical.index', compact('rooms'));
    }

    public function edit(int $id)
    {
        $room = Room::query()->findOrFail($id);

        $this->pageTitle(trans('plugins/hotel::ical.edit', ['name' => $room->name]));

        Assets::addScriptsDirectly('vendor/core/plugins/hotel/js/ical-sync.js');

        return RoomCalendarForm::createFromModel($room)->renderForm();
    }

    public function store(Request $request, BaseHttpResponse $response)
    {
        $roomId = $request->input('room_id');
        $room = Room::query()->findOrFail($roomId);

        $name = $request->input('name');
        $url = $request->input('url');

        $calendar = new RoomCalendar();
        $calendar->room_id = $room->id;
        $calendar->name = $name;
        $calendar->url = $url;
        $calendar->save();

        return $response
            ->setPreviousUrl(route('ical.edit', $roomId))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function update(int $id, Request $request, BaseHttpResponse $response)
    {
        $calendar = RoomCalendar::query()->findOrFail($id);

        $calendar->name = $request->input('name');
        $calendar->url = $request->input('url');
        $calendar->save();

        return $response
            ->setPreviousUrl(route('ical.edit', $calendar->room_id))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(int $id, BaseHttpResponse $response)
    {
        $calendar = RoomCalendar::query()->findOrFail($id);
        $calendar->delete();

        return $response
            ->setMessage(trans('core/base::notices.delete_success_message'));
    }

    public function sync(SyncCalendarRequest $request, BaseHttpResponse $response)
    {
        $roomId = $request->input('room_id');
        $room = Room::query()->findOrFail($roomId);

        try {
            $results = $this->iCalService->syncExternalCalendars($room);

            return $response
                ->setData($results)
                ->setMessage(trans('plugins/hotel::ical.sync_success', [
                    'success' => $results['success'],
                    'failed' => $results['failed'],
                ]));
        } catch (Exception $e) {
            Log::error('iCal sync error: ' . $e->getMessage(), ['exception' => $e]);

            return $response
                ->setError()
                ->setMessage($e->getMessage());
        }
    }

    public function syncAll(BaseHttpResponse $response)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'events' => 0,
            'errors' => [],
        ];

        $rooms = Room::query()->with('calendars')->get();

        foreach ($rooms as $room) {
            if ($room->calendars->isEmpty()) {
                continue;
            }

            try {
                $roomResults = $this->iCalService->syncExternalCalendars($room);

                $results['success'] += $roomResults['success'];
                $results['failed'] += $roomResults['failed'];
                $results['events'] += $roomResults['events'];
                $results['errors'] = array_merge($results['errors'], $roomResults['errors']);
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = 'Error syncing room ' . $room->name . ': ' . $e->getMessage();
                Log::error('iCal sync error: ' . $e->getMessage(), ['exception' => $e]);
            }
        }

        return $response
            ->setData($results)
            ->setMessage(trans('plugins/hotel::ical.sync_all_success', [
                'success' => $results['success'],
                'failed' => $results['failed'],
            ]));
    }

    public function download(int $id)
    {
        $room = Room::query()->findOrFail($id);
        $content = $this->iCalService->generateICalContent($room);

        return response($content)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . Str::slug($room->name) . '.ics"');
    }

    public function exportIcal(string $slug)
    {
        // Get the slug from the database
        $slugModel = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Room::class));

        abort_unless($slugModel, 404);

        $room = Room::query()->findOrFail($slugModel->reference_id);
        $content = $this->iCalService->generateICalContent($room);

        return response($content)
            ->header('Content-Type', 'text/calendar; charset=utf-8');
    }

    public function documentation()
    {
        $this->pageTitle(trans('plugins/hotel::ical.documentation'));

        return view('plugins/hotel::ical.documentation');
    }
}
