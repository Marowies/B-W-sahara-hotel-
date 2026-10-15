@extends(HotelHelper::viewPath('customers.master'))

@section('content')
    @php
        $customer = auth('customer')->user();
        $dashboard = riorelax_customer_dashboard($customer);
        $upcoming = $dashboard['upcoming'];
        $featured = $upcoming ?: $dashboard['latest'];
        $hasPayments = is_plugin_active('payment');
        $displayName = trim((string) $customer->first_name) ?: $customer->name;
        $initial = mb_strtoupper(mb_substr(trim((string) $customer->name) ?: (string) $customer->email, 0, 1));
        $profileFields = array_filter([
            __('Email') => $customer->email,
            __('Phone') => $customer->phone,
            __('Country') => $customer->country,
            __('City') => $customer->city,
        ], fn ($value) => filled($value));
        SeoHelper::setTitle(__('My account'));
    @endphp

    <header class="sky-hero">
        <div class="sky-hero-body">
            {{-- Google sends a generated teal letter when an account has no photo, so the initial is drawn in the site colours instead. --}}
            <span class="sky-hero-avatar" aria-hidden="true">{{ $initial }}</span>
            <div>
                <p class="sky-eyebrow">{{ __('My account') }}</p>
                <h1>{{ __('Welcome back, :name', ['name' => $displayName]) }}</h1>
                @if ($customer->created_at)
                    <p class="sky-hero-meta">{{ __('Member since') }} {{ $customer->created_at->translatedFormat('F Y') }}</p>
                @endif
            </div>
        </div>
        <dl class="sky-hero-stats">
            <div><dt>{{ __('Upcoming stays') }}</dt><dd>{{ number_format($dashboard['upcoming_count']) }}</dd></div>
            <div><dt>{{ __('All bookings') }}</dt><dd>{{ number_format($dashboard['total']) }}</dd></div>
        </dl>
    </header>

    <div class="sky-overview-grid">
        <section class="sky-card sky-stay-panel" aria-labelledby="sky-booking-heading">
            @if ($featured)
                @php
                    $bookingRoom = $featured->room;
                    $roomName = $bookingRoom->room->exists ? $bookingRoom->room->name : $bookingRoom->room_name;
                    $payment = $featured->payment;
                    $nights = $bookingRoom->start_date && $bookingRoom->end_date
                        ? (int) \Carbon\Carbon::parse($bookingRoom->start_date)->diffInDays(\Carbon\Carbon::parse($bookingRoom->end_date))
                        : null;
                @endphp
                <p class="sky-panel-label">{{ $upcoming ? __('Your next stay') : __('Your latest booking') }}</p>
                <h2 id="sky-booking-heading" class="sky-booking-room">{{ $roomName }}</h2>
                <div class="sky-stay-dates">
                    <div>
                        <span>{{ __('Check-In') }}</span>
                        <strong>{{ $bookingRoom->start_date ? Theme::formatDate($bookingRoom->start_date) : '—' }}</strong>
                    </div>
                    <div>
                        <span>{{ __('Check-Out') }}</span>
                        <strong>{{ $bookingRoom->end_date ? Theme::formatDate($bookingRoom->end_date) : '—' }}</strong>
                    </div>
                    @if ($nights)
                        <div>
                            <span>{{ __('Nights') }}</span>
                            <strong>{{ $nights }}</strong>
                        </div>
                    @endif
                </div>
                <dl class="sky-status-list">
                    <div><dt>{{ __('Booking number') }}</dt><dd dir="ltr">{{ $featured->booking_number ?: '—' }}</dd></div>
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
                <p class="sky-empty-note">{{ __('When you book a room, its dates, booking status and payment status will appear here.') }}</p>
                <div class="sky-actions">
                    <a class="sky-button" href="{{ riorelax_guest_site_url('booking') }}">{{ __('Book your stay') }}</a>
                    <a class="sky-text-link" href="{{ riorelax_guest_site_url('rooms') }}">{{ __('Browse rooms') }}</a>
                </div>
            @endif
        </section>

        <section class="sky-card sky-profile-panel" aria-labelledby="sky-profile-heading">
            <p class="sky-panel-label" id="sky-profile-heading">{{ __('Profile') }}</p>
            <p class="sky-profile-fullname">{{ $customer->name }}</p>
            @if ($profileFields)
                <dl class="sky-facts sky-facts-stacked">
                    @foreach ($profileFields as $label => $value)
                        <div><dt>{{ $label }}</dt><dd @if ($label === __('Email') || $label === __('Phone')) dir="ltr" @endif>{{ $value }}</dd></div>
                    @endforeach
                </dl>
            @endif
            <div class="sky-actions">
                <a class="sky-button sky-button-quiet" href="{{ route('customer.edit-account') }}">{{ __('Edit profile') }}</a>
            </div>
        </section>
    </div>

    <nav class="sky-quick" aria-label="{{ __('Quick actions') }}">
        <a class="sky-quick-item" href="{{ route('customer.bookings') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/></svg>
            <span><strong>{{ __('My Bookings') }}</strong><small>{{ __('See every stay, past and upcoming') }}</small></span>
        </a>
        <a class="sky-quick-item" href="{{ route('customer.edit-account') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="8.5" r="4"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/></svg>
            <span><strong>{{ __('Edit profile') }}</strong><small>{{ __('Update your name, phone and country') }}</small></span>
        </a>
        <a class="sky-quick-item" href="{{ route('customer.change-password') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5"/></svg>
            <span><strong>{{ __('Change password') }}</strong><small>{{ __('Keep your account secure') }}</small></span>
        </a>
        <a class="sky-quick-item" href="{{ riorelax_guest_site_url('contact') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v8a2.5 2.5 0 0 1-2.5 2.5H10l-4.5 3.5V17A2.5 2.5 0 0 1 4 14.5z"/></svg>
            <span><strong>{{ __('Contact the hotel') }}</strong><small>{{ __('Talk to our team about your stay') }}</small></span>
        </a>
    </nav>
@endsection
