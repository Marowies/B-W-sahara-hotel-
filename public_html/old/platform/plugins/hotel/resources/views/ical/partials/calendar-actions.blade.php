<button type="button" class="btn btn-sm btn-warning sync-calendar" data-room-id="{{ $room->id }}">
    <x-core::icon name="ti ti-refresh" class="me-1" />
    {{ trans('plugins/hotel::ical.sync_all') }}
</button>
