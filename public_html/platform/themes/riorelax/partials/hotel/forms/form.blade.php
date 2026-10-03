@php
    if (App::getLocale() !== 'en') {
        Theme::asset()
            ->container('footer')
            ->usePath(false)
            ->add('bootstrap-datepicker-locale', sprintf('//cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/locales/bootstrap-datepicker.%s.min.js', App::getLocale()), ['datepicker-js']);
    }
@endphp

@if (is_plugin_active('hotel'))
    @php
        $minimumNumberOfGuests = HotelHelper::getMinimumNumberOfGuests();
        $maximumNumberOfGuests = HotelHelper::getMaximumNumberOfGuests();
        $startDate = request()->query('start_date', Carbon\Carbon::now()->format(HotelHelper::getDateFormat()));
        $endDate = request()->query('end_date', Carbon\Carbon::now()->addDay()->format(HotelHelper::getDateFormat()));
        $adults = request()->query('adults', $minimumNumberOfGuests);
        $externalBookingUrl = theme_option('external_booking_url');
    @endphp

    @switch($style)

        {{-- ============================================================
             STYLE 2: Hero pill / capsule — always show full form UI
             but swap the submit button for an external link when needed
             ============================================================ --}}
        @case(2)
            <form
                action="{{ (!$externalBookingUrl && $availableForBooking) ? route('public.booking') : route('public.rooms') }}"
                method="{{ (!$externalBookingUrl && $availableForBooking) ? 'POST' : 'GET' }}"
                class="contact-form mt-30 form-booking"
            >
                @if (!$externalBookingUrl && $availableForBooking)
                    @csrf
                    <input type="hidden" name="room_id" value="{{ $room->id }}">
                @endif

                <div class="row align-items-end custom-pill-row">
                    @if (! empty($title))
                        <div class="col-lg-12">
                            <div class="section-title center-align mb-30">
                                <h2>{!! BaseHelper::clean($title) !!}</h2>
                            </div>
                        </div>
                    @endif

                    {{-- Check In --}}
                    <div class="col-lg-3 col-md-6 mb-30">
                        <div class="contact-field p-relative c-name custom-input-pill">
                            <label for="availability-form-start-date">{{ __('Check In Date') }}</label>
                            <div class="input-wrapper">
                                <input
                                    id="availability-form-start-date"
                                    autocomplete="off"
                                    type="text"
                                    class="departure-date date-picker"
                                    data-date-format="{{ HotelHelper::getBookingFormDateFormat() }}"
                                    placeholder="{{ Carbon\Carbon::now()->format(HotelHelper::getDateFormat()) }}"
                                    data-locale="{{ App::getLocale() }}"
                                    value="{{ BaseHelper::stringify($availableForBooking ? old('start_date', $startDate) : $startDate) }}"
                                    name="start_date"
                                >
                                <i class="fal fa-calendar-alt input-icon"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Check Out --}}
                    <div class="col-lg-3 col-md-6 mb-30">
                        <div class="contact-field p-relative c-name custom-input-pill">
                            <label for="availability-form-end-date">{{ __('Check Out Date') }}</label>
                            <div class="input-wrapper">
                                <input
                                    type="text"
                                    id="availability-form-end-date"
                                    autocomplete="off"
                                    class="arrival-date date-picker"
                                    data-date-format="{{ HotelHelper::getBookingFormDateFormat() }}"
                                    placeholder="{{ Carbon\Carbon::now()->addDay()->format(HotelHelper::getDateFormat()) }}"
                                    data-locale="{{ App::getLocale() }}"
                                    value="{{ BaseHelper::clean($availableForBooking ? old('end_date', $endDate) : $endDate) }}"
                                    name="end_date"
                                >
                                <i class="fal fa-calendar-alt input-icon"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Guests & Rooms --}}
                    <div class="col-lg-4 col-md-6 mb-30">
                        <div class="contact-field p-relative c-name form-guests-and-rooms-wrapper custom-input-pill">
                            <label for="adults">{{ __('Guests and Rooms') }}</label>
                            <div class="input-wrapper">
                                <button data-bb-toggle="toggle-guests-and-rooms" class="text-truncate text-start" type="button" data-target="#toggle-guests-and-rooms">
                                    <span data-bb-toggle="filter-adults-count" class="me-1">1</span> {{ __('Adult(s)') }},
                                    <span data-bb-toggle="filter-children-count" class="ms-1 me-1">0</span> {{ __('Child(ren)') }},
                                    <span data-bb-toggle="filter-rooms-count" class="me-1 ms-1">1</span> {{ __('Room(s)') }}
                                </button>
                            </div>

                            <div class="custom-dropdown dropdown-menu p-3" id="toggle-guests-and-rooms">
                                <div class="inputs-filed">
                                    <label for="adults">{{ __('Adults') }}</label>
                                    <div class="input-quantity">
                                        <button type="button" class="main-btn btn" data-bb-toggle="decrement-room">-</button>
                                        <input type="number" id="adults" name="adults" readonly value="1" min="{{ HotelHelper::getMinimumNumberOfGuests() }}" max="{{ HotelHelper::getMaximumNumberOfGuests() }}">
                                        <button type="button" class="main-btn btn" data-bb-toggle="increment-room">+</button>
                                    </div>
                                </div>
                                <div class="inputs-filed mt-30">
                                    <label for="children">{{ __('Children') }}</label>
                                    <div class="input-quantity">
                                        <button type="button" class="main-btn btn" data-bb-toggle="decrement-room">-</button>
                                        <input type="number" id="children" name="children" readonly value="0" min="0" max="{{ HotelHelper::getMaximumNumberOfGuests() }}">
                                        <button type="button" class="main-btn btn" data-bb-toggle="increment-room">+</button>
                                    </div>
                                </div>
                                <div class="inputs-filed mt-30">
                                    <label for="rooms">{{ __('Rooms') }}</label>
                                    <div class="input-quantity">
                                        <button type="button" class="main-btn btn" data-bb-toggle="decrement-room">-</button>
                                        <input type="number" id="rooms" name="rooms" readonly value="1" min="1" max="{{ 10 }}">
                                        <button type="button" class="main-btn btn" data-bb-toggle="increment-room">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Search / Book button --}}
                    <div class="col-lg-2 col-md-6 mb-30">
                        <div class="slider-btn text-end text-lg-center">
                            @if ($externalBookingUrl)
                                <a href="{{ $externalBookingUrl }}" target="_blank" class="btn ss-btn round-search-btn" data-animation="fadeInRight" data-delay=".8s">
                                    <i class="fal fa-search"></i>
                                </a>
                            @else
                                <button type="submit" class="btn ss-btn round-search-btn" data-animation="fadeInRight" data-delay=".8s">
                                    <i class="fal fa-search"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
            @break

        {{-- ============================================================
             STYLE 1 (default): Room sidebar — no form, direct link or full form
             ============================================================ --}}
        @default
            @if ($externalBookingUrl)
                {{-- External mode: direct link only, no internal form --}}
                <div class="row booking-area">
                    <div class="col-lg-12">
                        <div class="slider-btn mt-15">
                            <a href="{{ $externalBookingUrl }}" target="_blank" class="btn ss-btn" data-animation="fadeInRight" data-delay=".8s">
                                <span>{{ $availableForBooking ? __('Book Now') : __('Check Availability') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            @else
                {{-- Internal mode: full sidebar form --}}
                <form action="{{ $availableForBooking ? route('public.booking') : route('public.rooms') }}" method="{{ $availableForBooking ? 'POST' : 'GET' }}" class="contact-form mt-30 form-booking">
                    @if ($availableForBooking)
                        @csrf
                        <input type="hidden" name="room_id" value="{{ $room->id }}">
                    @endif

                    <div class="row booking-area">
                        @if (! empty($title))
                            <div class="col-lg-12">
                                <div class="section-title center-align mb-30">
                                    <h2>{!! BaseHelper::clean($title) !!}</h2>
                                </div>
                            </div>
                        @endif
                        <div class="col-lg-12">
                            <div class="contact-field p-relative c-name mb-20">
                                <label for="room-detail-booking-form-start-date"><i class="fal fa-badge-check"></i>{{ __('Check In Date') }}</label>
                                <input
                                    type="text"
                                    id="room-detail-booking-form-start-date"
                                    class="departure-date date-picker"
                                    autocomplete="off"
                                    data-date-format="{{ HotelHelper::getBookingFormDateFormat() }}"
                                    placeholder="{{ Carbon\Carbon::now()->format(HotelHelper::getDateFormat()) }}"
                                    data-locale="{{ App::getLocale() }}"
                                    value="{{ BaseHelper::stringify($startDate ?: old('start_date', $startDate)) }}"
                                    name="start_date"
                                >
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="contact-field p-relative c-subject mb-20">
                                <label for="room-detail-booking-form-end-date"><i class="fal fa-times-octagon"></i>{{ __('Check Out Date') }}</label>
                                <input
                                    type="text"
                                    id="room-detail-booking-form-end-date"
                                    class="arrival-date date-picker"
                                    autocomplete="off"
                                    data-date-format="{{ HotelHelper::getBookingFormDateFormat() }}"
                                    placeholder="{{ Carbon\Carbon::now()->addDay()->format(HotelHelper::getDateFormat()) }}"
                                    data-locale="{{ App::getLocale() }}"
                                    value="{{ BaseHelper::stringify($endDate ?: old('end_date', $endDate)) }}"
                                    name="end_date"
                                >
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="contact-field p-relative c-subject input-group input-group-two left-icon mb-20">
                                <label for="adults"><i class="fal fa-users"></i>{{ __('Adults') }}</label>
                                <div class="input-quantity">
                                    <button type="button" class="main-btn btn" data-bb-toggle="decrement-room">-</button>
                                    <input type="number" id="adults" name="adults" readonly value="{{ BaseHelper::stringify(request()->integer('adults', 1)) }}" min="{{ HotelHelper::getMinimumNumberOfGuests() }}" max="{{ HotelHelper::getMaximumNumberOfGuests() }}">
                                    <button type="button" class="main-btn btn" data-bb-toggle="increment-room">+</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="contact-field p-relative c-subject input-group input-group-two left-icon mb-20">
                                <label for="children"><i class="fal fa-child"></i>{{ __('Children') }}</label>
                                <div class="input-quantity">
                                    <button type="button" class="main-btn btn" data-bb-toggle="decrement-room">-</button>
                                    <input type="number" id="children" name="children" readonly value="{{ BaseHelper::stringify(request()->integer('children')) ?: 0 }}" min="0" max="{{ HotelHelper::getMaximumNumberOfGuests() }}">
                                    <button type="button" class="main-btn btn" data-bb-toggle="increment-room">+</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="contact-field p-relative c-subject input-group input-group-two left-icon mb-20">
                                <label for="rooms"><i class="fal fa-hotel"></i>{{ __('Rooms') }}</label>
                                <div class="input-quantity">
                                    <button type="button" class="main-btn btn" data-bb-toggle="decrement-room">-</button>
                                    <input type="number" id="rooms" name="rooms" readonly value="{{ BaseHelper::stringify(request()->integer('rooms', 1)) }}" min="1" max="{{ 10 }}">
                                    <button type="button" class="main-btn btn" data-bb-toggle="increment-room">+</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="slider-btn mt-15">
                                <button type="submit" class="btn ss-btn" data-animation="fadeInRight" data-delay=".8s">
                                    <span>{{ $availableForBooking ? __('Book Now') : __('Check Availability') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            @endif

    @endswitch

@endif
