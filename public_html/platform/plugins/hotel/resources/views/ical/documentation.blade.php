@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="widget meta-boxes">
                <div class="widget-title">
                    <h4>
                        <x-core::icon name="ti ti-book-2" class="me-2" />
                        <span>{{ trans('plugins/hotel::ical.documentation') }}</span>
                    </h4>
                </div>
                <div class="widget-body p-4">
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-start">
                            <x-core::icon name="ti ti-info-circle" class="me-2 mt-1" style="font-size: 20px;" />
                            <div>
                                <strong>{{ trans('plugins/hotel::ical.what_is_ical') }}</strong>
                                <p class="mb-0 mt-1">{{ trans('plugins/hotel::ical.what_is_ical_desc') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <x-core::icon name="ti ti-upload" class="me-2" />
                                {{ trans('plugins/hotel::ical.export_bookings') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">{{ trans('plugins/hotel::ical.export_bookings_desc') }}</p>

                            <div class="list-group list-group-flush">
                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-start">
                                        <span class="badge bg-blue text-blue-fg me-3 mt-1" style="font-size: 14px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">1</span>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ trans('plugins/hotel::ical.export_step1_title') }}</h6>
                                            <p class="text-muted mb-0">{{ trans('plugins/hotel::ical.export_step1_desc') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-start">
                                        <span class="badge bg-blue text-blue-fg me-3 mt-1" style="font-size: 14px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">2</span>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ trans('plugins/hotel::ical.export_step2_title') }}</h6>
                                            <p class="text-muted mb-0">{{ trans('plugins/hotel::ical.export_step2_desc') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="list-group-item px-0 border-bottom-0">
                                    <div class="d-flex align-items-start">
                                        <span class="badge bg-blue text-blue-fg me-3 mt-1" style="font-size: 14px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">3</span>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ trans('plugins/hotel::ical.export_step3_title') }}</h6>
                                            <p class="text-muted mb-0">{{ trans('plugins/hotel::ical.export_step3_desc') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <x-core::icon name="ti ti-download" class="me-2" />
                                {{ trans('plugins/hotel::ical.import_bookings') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">{{ trans('plugins/hotel::ical.import_bookings_desc') }}</p>

                            <div class="list-group list-group-flush">
                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-start">
                                        <span class="badge bg-green text-green-fg me-3 mt-1" style="font-size: 14px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">1</span>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ trans('plugins/hotel::ical.import_step1_title') }}</h6>
                                            <p class="text-muted mb-0">{{ trans('plugins/hotel::ical.import_step1_desc') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-start">
                                        <span class="badge bg-green text-green-fg me-3 mt-1" style="font-size: 14px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">2</span>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ trans('plugins/hotel::ical.import_step2_title') }}</h6>
                                            <p class="text-muted mb-0">{{ trans('plugins/hotel::ical.import_step2_desc') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="list-group-item px-0">
                                    <div class="d-flex align-items-start">
                                        <span class="badge bg-green text-green-fg me-3 mt-1" style="font-size: 14px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">3</span>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ trans('plugins/hotel::ical.import_step3_title') }}</h6>
                                            <p class="text-muted mb-0">{{ trans('plugins/hotel::ical.import_step3_desc') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="list-group-item px-0 border-bottom-0">
                                    <div class="d-flex align-items-start">
                                        <span class="badge bg-green text-green-fg me-3 mt-1" style="font-size: 14px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">4</span>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ trans('plugins/hotel::ical.import_step4_title') }}</h6>
                                            <p class="text-muted mb-0">{{ trans('plugins/hotel::ical.import_step4_desc') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <x-core::icon name="ti ti-history" class="me-2" />
                                {{ trans('plugins/hotel::ical.sync_logs') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-0">{{ trans('plugins/hotel::ical.sync_logs_desc') }}</p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <x-core::icon name="ti ti-help-circle" class="me-2" />
                                {{ trans('plugins/hotel::ical.troubleshooting') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">{{ trans('plugins/hotel::ical.troubleshooting_desc') }}</p>

                            <div class="accordion" id="faqAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                            <x-core::icon name="ti ti-calendar-off" class="me-2" />
                                            {{ trans('plugins/hotel::ical.faq1_title') }}
                                        </button>
                                    </h2>
                                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            {{ trans('plugins/hotel::ical.faq1_desc') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                            <x-core::icon name="ti ti-alert-triangle" class="me-2" />
                                            {{ trans('plugins/hotel::ical.faq2_title') }}
                                        </button>
                                    </h2>
                                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            {{ trans('plugins/hotel::ical.faq2_desc') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                            <x-core::icon name="ti ti-clock" class="me-2" />
                                            {{ trans('plugins/hotel::ical.faq3_title') }}
                                        </button>
                                    </h2>
                                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            {{ trans('plugins/hotel::ical.faq3_desc') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
