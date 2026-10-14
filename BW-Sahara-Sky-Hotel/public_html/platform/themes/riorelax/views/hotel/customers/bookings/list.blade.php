@extends(HotelHelper::viewPath('customers.master'))

@section('content')
    @php
        $hasPayments = is_plugin_active('payment');
        $bookings->loadMissing('payment');
        SeoHelper::setTitle(__('My Bookings'));
    @endphp

    <header class="sky-page-head">
        <p class="sky-eyebrow">{{ __('My account') }}</p>
        <h1>{{ __('My Bookings') }}</h1>
    </header>

    @if (count($bookings) > 0)
        <ul class="sky-booking-list">
            @foreach ($bookings as $booking)
                @php
                    $bookingRoom = $booking->room;
                    $room = $bookingRoom->room->exists ? $bookingRoom->room : null;
                    $roomName = $room?->name ?: $bookingRoom->room_name;
                    $image = $room ? $room->image : $bookingRoom->room_image;
                    $payment = $booking->payment;
                @endphp
                <li class="sky-card sky-booking-item">
                    <img src="{{ RvMedia::getImageUrl($image, 'thumb', false, RvMedia::getDefaultImage()) }}" alt="" width="120" height="90" loading="lazy">
                    <div class="sky-booking-body">
                        <h2 class="sky-booking-room">
                            @if ($room)
                                <a href="{{ $room->url }}">{{ $roomName }}</a>
                            @else
                                {{ $roomName }}
                            @endif
                        </h2>
                        <dl class="sky-facts">
                            <div><dt>{{ __('Check-In') }}</dt><dd>{{ $bookingRoom->start_date ? Theme::formatDate($bookingRoom->start_date) : '—' }}</dd></div>
                            <div><dt>{{ __('Check-Out') }}</dt><dd>{{ $bookingRoom->end_date ? Theme::formatDate($bookingRoom->end_date) : '—' }}</dd></div>
                            <div><dt>{{ __('Total') }}</dt><dd>{{ format_price($booking->amount) }}</dd></div>
                        </dl>
                        <dl class="sky-status-list">
                            <div>
                                <dt>{{ __('Booking status') }}</dt>
                                <dd><span class="sky-status sky-status-{{ riorelax_status_tone($booking->status?->getValue()) }}">{{ $booking->status?->label() }}</span></dd>
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
                    </div>
                    <a class="sky-button sky-button-quiet" href="{{ route('customer.bookings.show', $booking->transaction_id) }}">{{ __('View details') }}<span class="visually-hidden"> — {{ $roomName }}</span></a>
                </li>
            @endforeach
        </ul>

        {!! $bookings->links(Theme::getThemeNamespace('partials.pagination')) !!}
    @else
        <section class="sky-card sky-empty">
            <h2>{{ __('No bookings yet') }}</h2>
            <p>{{ __('When you book a room, its dates, booking status and payment status will appear here.') }}</p>
            <a class="sky-button" href="{{ riorelax_guest_site_url('booking') }}">{{ __('Book your stay') }}</a>
        </section>
    @endif
@stop
