@extends(HotelHelper::viewPath('customers.master'))

@section('content')
    @php
        SeoHelper::setTitle(__('Booking details'));
    @endphp

    <header class="sky-page-head">
        <a class="sky-back-link" href="{{ route('customer.bookings') }}">{{ __('Back to my bookings') }}</a>
        <h1>{{ __('Booking details') }}</h1>
        @if ($booking->booking_number)
            <p class="sky-lead">{{ __('Booking number') }}: <span dir="ltr">{{ $booking->booking_number }}</span></p>
        @endif
    </header>

    <div class="sky-card sky-booking-info">
        {{-- Shared with the admin panel; booking and payment status, totals and invoices come from here. --}}
        @include('plugins/hotel::booking-info', ['route' => 'customer.generate-invoice'])
    </div>
@endsection
