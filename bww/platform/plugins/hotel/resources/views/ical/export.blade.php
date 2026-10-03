<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <div class="flex-shrink-0 me-3">
                <div class="bg-primary bg-opacity-10 rounded p-3">
                    <x-core::icon name="ti ti-upload" class="text-primary" style="font-size: 24px;" />
                </div>
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-1">{{ trans('plugins/hotel::ical.export_calendar_title') }}</h5>
                <p class="text-muted mb-0 small">{{ trans('plugins/hotel::ical.export_description') }}</p>
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="fw-semibold mb-2">
                <x-core::icon name="ti ti-link" class="me-1" />
                {{ trans('plugins/hotel::ical.export_url') }}
            </label>
            <div class="input-group">
                <input type="text" class="form-control" value="{{ route('public.ical', $room->slug) }}" readonly>
                <x-core::button
                    class="btn-outline-secondary"
                    data-bb-toggle="clipboard"
                    data-clipboard-text="{{ route('public.ical', $room->slug) }}"
                    data-clipboard-message="{{ trans('core/base::base.copied') }}"
                >
                    <x-core::icon name="ti ti-clipboard" data-clipboard-icon="true" />
                    <x-core::icon name="ti ti-check" data-clipboard-success-icon="true" class="text-success d-none" />
                </x-core::button>
            </div>
            <span class="form-hint">
                {{ trans('plugins/hotel::ical.copy_url_instruction') }}
            </span>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('ical.download', $room->id) }}" class="btn btn-primary">
                <x-core::icon name="ti ti-download" class="me-1" /> {{ trans('plugins/hotel::ical.download_ical_file') }}
            </a>
            <a href="{{ route('ical.documentation') }}" class="btn btn-outline-secondary" target="_blank">
                <x-core::icon name="ti ti-help" class="me-1" /> {{ trans('plugins/hotel::ical.how_to_export') }}
            </a>
        </div>
    </div>
</div>
