@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    {{-- Statistics Overview --}}
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <x-core::icon name="ti ti-building" class="text-primary" style="font-size: 24px;" />
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">{{ trans('plugins/hotel::ical.total_rooms') }}</h6>
                            <h3 class="mb-0">{{ $rooms->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <x-core::icon name="ti ti-calendar-check" class="text-success" style="font-size: 24px;" />
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">{{ trans('plugins/hotel::ical.synced_calendars') }}</h6>
                            <h3 class="mb-0">{{ $rooms->sum(fn($r) => $r->calendars->count()) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <x-core::icon name="ti ti-link" class="text-info" style="font-size: 24px;" />
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">{{ trans('plugins/hotel::ical.rooms_with_calendars') }}</h6>
                            <h3 class="mb-0">{{ $rooms->filter(fn($r) => $r->calendars->isNotEmpty())->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-warning bg-opacity-10 rounded p-3">
                                <x-core::icon name="ti ti-clock" class="text-warning" style="font-size: 24px;" />
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">{{ trans('plugins/hotel::ical.last_sync_time') }}</h6>
                            @php
                                $lastSync = $rooms->flatMap(fn($r) => $r->calendars)->filter(fn($c) => $c->last_synced_at)->sortByDesc('last_synced_at')->first();
                            @endphp
                            <h3 class="mb-0 small">{{ $lastSync ? $lastSync->last_synced_at->diffForHumans() : trans('plugins/hotel::ical.never') }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="row">
        <div class="col-12">
            <div class="widget meta-boxes">
                <div class="widget-title justify-content-between">
                    <h4>
                        <x-core::icon name="ti ti-calendar-repeat" class="me-2" />
                        <span>{{ trans('plugins/hotel::ical.sync_calendars') }}</span>
                    </h4>
                    <div class="widget-action d-flex flex-wrap gap-2">
                        <a href="{{ route('ical.documentation') }}" class="btn btn-secondary">
                            <x-core::icon name="ti ti-book" class="me-1" /> {{ trans('plugins/hotel::ical.documentation') }}
                        </a>
                        <a href="{{ route('ical.logs.index') }}" class="btn btn-secondary">
                            <x-core::icon name="ti ti-history" class="me-1" /> {{ trans('plugins/hotel::ical.logs') }}
                        </a>
                        @if($rooms->filter(fn($r) => $r->calendars->isNotEmpty())->count() > 0)
                            <button class="btn btn-info sync-all-calendars">
                                <x-core::icon name="ti ti-refresh" class="me-1" /> {{ trans('plugins/hotel::ical.sync_all_calendars') }}
                            </button>
                        @endif
                    </div>
                </div>
                <div class="widget-body">
                    @if($rooms->isEmpty())
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <x-core::icon name="ti ti-building-off" style="font-size: 64px;" class="text-muted" />
                            </div>
                            <h5 class="text-muted">{{ trans('plugins/hotel::ical.no_rooms_found') }}</h5>
                            <p class="text-muted">{{ trans('plugins/hotel::ical.no_rooms_desc') }}</p>
                            <a href="{{ route('room.create') }}" class="btn btn-primary mt-3">
                                <x-core::icon name="ti ti-plus" class="me-1" /> {{ trans('plugins/hotel::room.create') }}
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30%;">
                                            <x-core::icon name="ti ti-building" class="me-1" />
                                            {{ trans('core/base::tables.name') }}
                                        </th>
                                        <th style="width: 15%;" class="text-center">
                                            <x-core::icon name="ti ti-calendar" class="me-1" />
                                            {{ trans('plugins/hotel::ical.calendars') }}
                                        </th>
                                        <th style="width: 15%;" class="text-center">
                                            <x-core::icon name="ti ti-circle-dot" class="me-1" />
                                            {{ trans('plugins/hotel::ical.status') }}
                                        </th>
                                        <th style="width: 20%;">
                                            <x-core::icon name="ti ti-clock" class="me-1" />
                                            {{ trans('plugins/hotel::ical.last_sync') }}
                                        </th>
                                        <th style="width: 20%;" class="text-end">{{ trans('core/base::tables.operations') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rooms as $room)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        @if($room->image)
                                                            <img src="{{ RvMedia::getImageUrl($room->image, 'thumb') }}" alt="{{ $room->name }}" class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                                        @else
                                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <x-core::icon name="ti ti-building" class="text-muted" />
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <strong class="d-block">{{ $room->name }}</strong>
                                                        @if($room->room_category)
                                                            <small class="text-muted">{{ $room->room_category->name }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($room->calendars->count() > 0)
                                                    <x-core::badge :label="$room->calendars->count()" color="info" />
                                                @else
                                                    <x-core::badge label="0" color="secondary" />
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($room->calendars->isNotEmpty())
                                                    @php
                                                        $lastSync = $room->calendars->max('last_synced_at');
                                                        $hasRecentSync = $lastSync && $lastSync->diffInHours(now()) < 24;
                                                    @endphp
                                                    @if($hasRecentSync)
                                                        <x-core::badge color="success">
                                                            <x-core::icon name="ti ti-check" class="me-1" />
                                                            {{ trans('plugins/hotel::ical.synced') }}
                                                        </x-core::badge>
                                                    @elseif($lastSync)
                                                        <x-core::badge color="warning">
                                                            <x-core::icon name="ti ti-clock" class="me-1" />
                                                            {{ trans('plugins/hotel::ical.needs_sync') }}
                                                        </x-core::badge>
                                                    @else
                                                        <x-core::badge color="secondary">
                                                            <x-core::icon name="ti ti-calendar-off" class="me-1" />
                                                            {{ trans('plugins/hotel::ical.not_synced') }}
                                                        </x-core::badge>
                                                    @endif
                                                @else
                                                    <x-core::badge color="light">
                                                        <x-core::icon name="ti ti-minus" class="me-1" />
                                                        {{ trans('plugins/hotel::ical.no_calendars') }}
                                                    </x-core::badge>
                                                @endif
                                            </td>
                                            <td>
                                                @if($room->calendars->isNotEmpty())
                                                    @php
                                                        $lastSync = $room->calendars->max('last_synced_at');
                                                    @endphp
                                                    @if($lastSync)
                                                        <span class="text-muted" data-bs-toggle="tooltip" title="{{ $lastSync->format('Y-m-d H:i:s') }}">
                                                            {{ $lastSync->diffForHumans() }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">{{ trans('plugins/hotel::ical.never') }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">{{ trans('plugins/hotel::ical.not_applicable') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex gap-1 justify-content-end">
                                                    <a href="{{ route('ical.edit', $room->id) }}"
                                                       class="btn btn-sm btn-icon btn-info"
                                                       data-bs-toggle="tooltip"
                                                       title="{{ trans('plugins/hotel::ical.manage_calendars') }}">
                                                        <x-core::icon name="ti ti-settings" />
                                                    </a>
                                                    <a href="{{ route('ical.download', $room->id) }}"
                                                       class="btn btn-sm btn-icon btn-primary"
                                                       data-bs-toggle="tooltip"
                                                       title="{{ trans('plugins/hotel::ical.download') }}">
                                                        <x-core::icon name="ti ti-download" />
                                                    </a>
                                                    @if($room->calendars->isNotEmpty())
                                                        <button class="btn btn-sm btn-icon btn-warning sync-calendar"
                                                                data-room-id="{{ $room->id }}"
                                                                data-bs-toggle="tooltip"
                                                                title="{{ trans('plugins/hotel::ical.sync') }}">
                                                            <x-core::icon name="ti ti-refresh" />
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('footer')
    <script>
        // Initialize tooltips
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
@endpush
