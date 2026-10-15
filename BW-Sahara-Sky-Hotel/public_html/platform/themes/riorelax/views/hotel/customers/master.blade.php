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

<div @class(['customer-page crop-avatar sky-account', 'sky-account-overview' => $accountRoute === 'customer.overview'])>
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
    <aside class="sky-account-profile profile-sidebar" aria-label="{{ __('Profile photo') }}">
        <form id="avatar-upload-form" enctype="multipart/form-data" action="javascript:void(0)" onsubmit="return false">
            <div class="avatar-upload-container">
                <div id="account-avatar">
                    <div class="profile-image custom-avatar-master">
                        <div class="avatar-view mt-card-avatar">
                            <img class="br2" src="{{ $accountCustomer->avatar_url }}" alt="{{ $accountCustomer->name }}" width="96" height="96" />
                        </div>
                    </div>
                </div>
                <div id="print-msg" class="text-danger hidden"></div>
            </div>
        </form>
        <p class="sky-account-profile-name">{{ $accountCustomer->name }}</p>
        @if ($accountCustomer->email)
            <p class="sky-account-profile-email" dir="ltr">{{ $accountCustomer->email }}</p>
        @endif
        <button type="button" class="sky-link-button avatar-view">{{ __('Change photo') }}</button>
    </aside>
    @endif

    <div class="modal fade" id="avatar-modal" tabindex="-1" role="dialog" aria-labelledby="avatar-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form class="avatar-form" method="post" action="{{ route('customer.avatar') }}" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h2 class="modal-title" id="avatar-modal-label">{{ __('Profile Image') }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="avatar-body">
                            <div class="avatar-upload">
                                <input class="avatar-src" name="avatar_src" type="hidden" />
                                <input class="avatar-data" name="avatar_data" type="hidden" />
                                @csrf
                                <label for="avatarInput">{{ __('New image') }}</label>
                                <input class="avatar-input" id="avatarInput" name="avatar_file" type="file" accept="image/*" />
                            </div>

                            <div class="loading" style="display: none;" tabindex="-1" role="img" aria-label="{{ __('Loading') }}"></div>

                            <div class="row">
                                <div class="col-md-9">
                                    <div class="avatar-wrapper"></div>
                                    <div class="error-message text-danger" style="display: none;"></div>
                                </div>
                                <div class="col-md-3 avatar-preview-wrapper">
                                    <div class="avatar-preview preview-lg"></div>
                                    <div class="avatar-preview preview-md"></div>
                                    <div class="avatar-preview preview-sm"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button class="btn btn-primary avatar-save" type="submit">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
