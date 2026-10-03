<?php

namespace Botble\Hotel\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Hotel\Models\ICalSyncLog;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Tables\ICalSyncLogTable;
use Illuminate\Http\Request;

class ICalSyncLogController extends BaseController
{
    public function __construct()
    {
        $this
            ->breadcrumb()
            ->add(trans('plugins/hotel::booking.bookings'), route('booking.index'))
            ->add(trans('plugins/hotel::ical.name'), route('ical.index'))
            ->add(trans('plugins/hotel::ical.logs'), route('ical.logs.index'));
    }

    public function index(ICalSyncLogTable $table)
    {
        $this->pageTitle(trans('plugins/hotel::ical.logs'));

        return $table->renderTable();
    }

    public function show(int $id, BaseHttpResponse $response)
    {
        $log = ICalSyncLog::query()->findOrFail($id);
        $room = Room::query()->find($log->room_id);

        $this->pageTitle(trans('plugins/hotel::ical.log_details', ['id' => $id]));

        return view('plugins/hotel::ical.log-details', compact('log', 'room'));
    }

    public function destroy(int $id, BaseHttpResponse $response)
    {
        $log = ICalSyncLog::query()->findOrFail($id);
        $log->delete();

        return $response
            ->setMessage(trans('core/base::notices.delete_success_message'));
    }

    public function clearLogs(Request $request, BaseHttpResponse $response)
    {
        if (! $request->ajax()) {
            return $response
                ->setNextUrl(route('ical.logs.index'))
                ->setError()
                ->setMessage(trans('plugins/hotel::ical.error_occurred'));
        }

        ICalSyncLog::query()->truncate();

        return $response
            ->setMessage(trans('plugins/hotel::ical.logs_cleared'));
    }
}
