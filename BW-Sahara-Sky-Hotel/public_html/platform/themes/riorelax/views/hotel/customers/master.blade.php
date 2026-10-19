@php
    Theme::set('pageTitle', __('My account'));
    $accountRoute = Route::currentRouteName();
    $accountLinks = [
        ['customer.overview', __('Overview'), ['customer.overview']],
        ['customer.bookings', __('My Bookings'), ['customer.bookings', 'customer.bookings.show']],
        ['customer.edit-account', __('Profile'), ['customer.edit-account']],
    ];
    if (HotelHelper::isReviewEnabled()) {
        $accountLinks[] = ['customer.reviews', __('My Reviews'), ['customer.reviews']];
    }
    // No "Change password": guests sign in with an emailed code, so there is no password to change.
@endphp

<div @class(['customer-page sky-account', 'sky-account-overview' => $accountRoute === 'customer.overview'])>
    <nav class="sky-account-nav" aria-label="{{ __('Account navigation') }}">
        <ul>
            @foreach ($accountLinks as [$name, $label, $active])
                <li>
                    <a href="{{ route($name) }}" @class(['active' => in_array($accountRoute, $active, true)]) @if (in_array($accountRoute, $active, true)) aria-current="page" @endif>{{ $label }}</a>
                </li>
            @endforeach
            <li>
                <a href="{{ route('customer.logout') }}" class="sky-account-signout">{{ __('Sign out') }}</a>
            </li>
        </ul>
    </nav>

    <section class="sky-account-content profile-content">
        @yield('content')
    </section>
</div>
