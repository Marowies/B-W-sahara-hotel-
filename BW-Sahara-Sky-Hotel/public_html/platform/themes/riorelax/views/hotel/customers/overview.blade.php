@extends(HotelHelper::viewPath('customers.master'))

@section('content')
    @php
        $customer = auth('customer')->user();
        $dashboard = riorelax_customer_dashboard($customer);
        $upcoming = $dashboard['upcoming'];
        $featured = $upcoming ?: $dashboard['latest'];
        $hasPayments = is_plugin_active('payment');
        $displayName = trim((string) $customer->first_name) ?: $customer->name;
        $profileFields = array_filter([
            __('Email') => $customer->email,
            __('Phone') => $customer->phone,
            __('Country') => $customer->country,
            __('City') => $customer->city,
        ], fn ($value) => filled($value));
        SeoHelper::setTitle(__('My account'));
    @endphp

    <header class="sky-page-head">
        <p class="sky-eyebrow">{{ __('My account') }}</p>
        <h1>{{ __('Welcome back, :name', ['name' => $displayName]) }}</h1>
        <p class="sky-lead">{{ __('Your bookings and profile details with B&W Sahara Sky Hotel.') }}</p>
    </header>

    <dl class="sky-stats">
        <div>
            <dt>{{ __('Upcoming stays') }}</dt>
            <dd>{{ number_format($dashboard['upcoming_count']) }}</dd>
        </div>
        <div>
            <dt>{{ __('All bookings') }}</dt>
            <dd>{{ number_format($dashboard['total']) }}</dd>
        </div>
    </dl>

    <div class="sky-dashboard-grid">
        <section class="sky-card sky-card-booking" aria-labelledby="sky-booking-heading">
            @if ($featured)
                @php
                    $bookingRoom = $featured->room;
                    $roomName = $bookingRoom->room->exists ? $bookingRoom->room->name : $bookingRoom->room_name;
                    $payment = $featured->payment;
                @endphp
                <h2 id="sky-booking-heading">{{ $upcoming ? __('Your next stay') : __('Your latest booking') }}</h2>
                <p class="sky-booking-room">{{ $roomName }}</p>
                <dl class="sky-facts">
                    <div><dt>{{ __('Check-In') }}</dt><dd>{{ $bookingRoom->start_date ? Theme::formatDate($bookingRoom->start_date) : '—' }}</dd></div>
                    <div><dt>{{ __('Check-Out') }}</dt><dd>{{ $bookingRoom->end_date ? Theme::formatDate($bookingRoom->end_date) : '—' }}</dd></div>
                    <div><dt>{{ __('Booking number') }}</dt><dd dir="ltr">{{ $featured->booking_number ?: '—' }}</dd></div>
                </dl>
                <dl class="sky-status-list">
                    <div>
                        <dt>{{ __('Booking status') }}</dt>
                        <dd><span class="sky-status sky-status-{{ riorelax_status_tone($featured->status?->getValue()) }}">{{ $featured->status?->label() }}</span></dd>
                    </div>
                    @if ($hasPayments)
                        <div>
                            <dt>{{ __('Payment status') }}</dt>
                            <dd>
                                @if ($payment->id && $payment->status)
                                    <span class="sky-status sky-status-{{ riorelax_status_tone($payment->status->getValue()) }}">{{ $payment->status->label() }}</span>
                                @else
                                    <span class="sky-status sky-status-muted">{{ __('No payment recorded') }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
                <div class="sky-actions">
                    <a class="sky-button" href="{{ route('customer.bookings.show', $featured->transaction_id) }}">{{ __('View booking details') }}</a>
                    <a class="sky-text-link" href="{{ route('customer.bookings') }}">{{ __('All bookings') }}</a>
                </div>
            @else
                <h2 id="sky-booking-heading">{{ __('No bookings yet') }}</h2>
                <p>{{ __('When you book a room, its dates, booking status and payment status will appear here.') }}</p>
                <div class="sky-actions">
                    <a class="sky-button" href="{{ riorelax_guest_site_url('booking') }}">{{ __('Book your stay') }}</a>
                    <a class="sky-text-link" href="{{ riorelax_guest_site_url('rooms') }}">{{ __('Browse rooms') }}</a>
                </div>
            @endif
        </section>

        <section class="sky-card" aria-labelledby="sky-profile-heading">
            <h2 id="sky-profile-heading">{{ __('Profile') }}</h2>
            <p class="sky-booking-room">{{ $customer->name }}</p>
            @if ($profileFields)
                <dl class="sky-facts sky-facts-stacked">
                    @foreach ($profileFields as $label => $value)
                        <div><dt>{{ $label }}</dt><dd @if ($label === __('Email') || $label === __('Phone')) dir="ltr" @endif>{{ $value }}</dd></div>
                    @endforeach
                </dl>
            @endif
            <div class="sky-actions">
                <a class="sky-button sky-button-quiet" href="{{ route('customer.edit-account') }}">{{ __('Edit profile') }}</a>
                <a class="sky-text-link sky-account-signout" href="{{ route('customer.logout') }}">{{ __('Sign out') }}</a>
            </div>
        </section>
    </div>
@endsection
