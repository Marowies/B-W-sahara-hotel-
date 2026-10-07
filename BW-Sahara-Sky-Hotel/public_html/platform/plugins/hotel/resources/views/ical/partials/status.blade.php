@if($item->status === 'success')
    <x-core::badge
        :label="trans('plugins/hotel::ical.status_success')"
        color="success"
        icon="ti ti-check"
    />
@elseif($item->status === 'warning')
    <x-core::badge
        :label="trans('plugins/hotel::ical.status_warning')"
        color="warning"
        icon="ti ti-alert-triangle"
    />
@elseif($item->status === 'error')
    <x-core::badge
        :label="trans('plugins/hotel::ical.status_error')"
        color="danger"
        icon="ti ti-alert-circle"
    />
@else
    <x-core::badge
        :label="$item->status"
        color="secondary"
        icon="ti ti-info-circle"
    />
@endif
