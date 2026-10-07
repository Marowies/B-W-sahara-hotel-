@if($calendars->isEmpty())
    <div class="text-center py-4">
        <div class="mb-3">
            <x-core::icon name="ti ti-calendar-off" style="font-size: 48px;" class="text-muted" />
        </div>
        <p class="text-muted mb-0">{{ trans('plugins/hotel::ical.no_external_calendars') }}</p>
        <small class="text-muted">{{ trans('plugins/hotel::ical.add_calendar_desc') }}</small>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 25%;">
                        <x-core::icon name="ti ti-tag" class="me-1" />
                        {{ trans('core/base::tables.name') }}
                    </th>
                    <th style="width: 45%;">
                        <x-core::icon name="ti ti-link" class="me-1" />
                        {{ trans('plugins/hotel::ical.calendar_url') }}
                    </th>
                    <th style="width: 20%;">
                        <x-core::icon name="ti ti-clock" class="me-1" />
                        {{ trans('plugins/hotel::ical.last_sync') }}
                    </th>
                    <th style="width: 10%;" class="text-end">{{ trans('core/base::tables.operations') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($calendars as $calendar)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <x-core::icon name="ti ti-calendar" class="text-primary me-2" />
                                <strong>{{ $calendar->name }}</strong>
                            </div>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm" value="{{ Str::limit($calendar->url, 60) }}" readonly title="{{ $calendar->url }}">
                                <x-core::button
                                    class="btn-outline-secondary btn-sm"
                                    data-bb-toggle="clipboard"
                                    data-clipboard-text="{{ $calendar->url }}"
                                    data-clipboard-message="{{ trans('core/base::base.copied') }}"
                                    title="{{ trans('core/base::base.copy') }}"
                                >
                                    <x-core::icon name="ti ti-clipboard" data-clipboard-icon="true" />
                                    <x-core::icon name="ti ti-check" data-clipboard-success-icon="true" class="text-success d-none" />
                                </x-core::button>
                            </div>
                        </td>
                        <td>
                            @if($calendar->last_synced_at)
                                <div class="d-flex align-items-center">
                                    <x-core::badge
                                        :color="$calendar->last_synced_at->diffInHours(now()) < 24 ? 'success' : 'warning'"
                                        class="me-1"
                                    >
                                        <x-core::icon name="ti ti-clock" />
                                    </x-core::badge>
                                    <span class="text-muted small" data-bs-toggle="tooltip" title="{{ $calendar->last_synced_at->format('Y-m-d H:i:s') }}">
                                        {{ $calendar->last_synced_at->diffForHumans() }}
                                    </span>
                                </div>
                            @else
                                <x-core::badge color="secondary">
                                    {{ trans('plugins/hotel::ical.never') }}
                                </x-core::badge>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="#"
                               class="btn btn-sm btn-icon btn-danger deleteDialog"
                               data-section="{{ route('ical.destroy', $calendar->id) }}"
                               data-bs-toggle="tooltip"
                               title="{{ trans('core/base::tables.delete') }}">
                                <x-core::icon name="ti ti-trash" />
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-core::modal.action
        type="danger"
        class="modal-confirm-delete"
        :title="trans('core/base::tables.confirm_delete')"
        :description="trans('core/base::tables.confirm_delete_msg')"
        :submit-button-label="trans('core/base::tables.delete')"
        :submit-button-attrs="['class' => 'delete-crud-entry']"
    />
@endif
