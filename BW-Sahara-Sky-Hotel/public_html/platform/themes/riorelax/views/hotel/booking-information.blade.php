@php
    // The default layout wraps booking routes in the same guest shell as the customer account.
    Theme::set('pageTitle', __('Booking information'));
    SeoHelper::setTitle(__('Booking information'));
@endphp

<section class="booking-information-page">
    <header class="sky-page-head">
        <p class="sky-eyebrow">{{ __('Booking form') }}</p>
        <h1>{{ __('Your booking information') }}</h1>
        @if (auth('customer')->check())
            <p class="sky-lead"><a class="sky-text-link" href="{{ route('customer.bookings') }}">{{ __('My Bookings') }}</a></p>
        @endif
    </header>
    <div class="sky-card sky-booking-info booking-information">
        @include('plugins/hotel::booking-info', ['booking' => $booking, 'route' => 'customer.generate-invoice'])
    </div>
</section>
