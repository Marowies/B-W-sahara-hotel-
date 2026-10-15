@php
    Theme::set('pageTitle', __('My account'));
    $accountCustomer = auth('customer')->user();
    $accountRoute = Route::currentRouteName();
    $accountLinks = [
        ['customer.overview', __('Overview'), ['customer.overview']],
        ['customer.bookings', __('My Bookings'), ['customer.bookings', 'customer.bookings.show']],
        ['customer.edit-account', __('Profile'), ['customer.edit-account']],
    ];
    if (HotelHelper::isReviewEnabled()) {
        $accountLinks[] = ['customer.reviews', __('My Reviews'), ['customer.reviews']];
    }
    $accountLinks[] = ['customer.change-password', __('Change password'), ['customer.change-password']];
@endphp

<div @class(['customer-page sky-account', 'sky-account-overview' => $accountRoute === 'customer.overview'])>
    {{-- On small screens the tabs come first, then the page itself, then the profile card. --}}
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

    @if ($accountRoute !== 'customer.overview')
    <aside class="sky-account-profile profile-sidebar" aria-label="{{ __('Profile') }}">
        <p class="sky-account-profile-name">{{ $accountCustomer->name }}</p>
        @if ($accountCustomer->email)
            <p class="sky-account-profile-email" dir="ltr">{{ $accountCustomer->email }}</p>
        @endif
    </aside>
    @endif

</div>
