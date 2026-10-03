<div class="alert alert-info mb-4" role="alert">
    <div class="d-flex align-items-start">
        <div class="flex-shrink-0 me-3">
            <x-core::icon name="ti ti-info-circle" style="font-size: 24px;" />
        </div>
        <div class="flex-grow-1">
            <h5 class="alert-heading mb-2">
                <x-core::icon name="ti ti-calendar-plus" class="me-1" />
                {{ trans('plugins/hotel::ical.add_external_calendar_title') }}
            </h5>
            <p class="mb-2">{{ trans('plugins/hotel::ical.add_external_calendar_desc') }}</p>
            <hr class="my-2">
            <div class="small">
                <strong>{{ trans('plugins/hotel::ical.supported_platforms') }}:</strong>
                <div class="mt-2">
                    <span class="badge bg-blue text-blue-fg me-1">Booking.com</span>
                    <span class="badge bg-azure text-azure-fg me-1">Airbnb</span>
                    <span class="badge bg-indigo text-indigo-fg me-1">Expedia</span>
                    <span class="badge bg-purple text-purple-fg me-1">VRBO</span>
                    <span class="badge bg-cyan text-cyan-fg">{{ trans('plugins/hotel::ical.and_more') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-success bg-opacity-10 rounded p-2">
                            <x-core::icon name="ti ti-circle-check" class="text-success" style="font-size: 20px;" />
                        </div>
                    </div>
                    <h6 class="mb-0">{{ trans('plugins/hotel::ical.benefits_title') }}</h6>
                </div>
                <ul class="small mb-0 ps-3">
                    <li>{{ trans('plugins/hotel::ical.benefit_1') }}</li>
                    <li>{{ trans('plugins/hotel::ical.benefit_2') }}</li>
                    <li>{{ trans('plugins/hotel::ical.benefit_3') }}</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-warning bg-opacity-10 rounded p-2">
                            <x-core::icon name="ti ti-bulb" class="text-warning" style="font-size: 20px;" />
                        </div>
                    </div>
                    <h6 class="mb-0">{{ trans('plugins/hotel::ical.quick_tip') }}</h6>
                </div>
                <p class="small mb-2">{{ trans('plugins/hotel::ical.tip_desc') }}</p>
                <a href="{{ route('ical.documentation') }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                    <x-core::icon name="ti ti-book" class="me-1" />
                    {{ trans('plugins/hotel::ical.view_guide') }}
                </a>
            </div>
        </div>
    </div>
</div>
