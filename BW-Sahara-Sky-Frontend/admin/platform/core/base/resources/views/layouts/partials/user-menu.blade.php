<x-core::dropdown
    wrapper-class="nav-item"
    :has-arrow="true"
    position="end"
>
    <x-slot:trigger>
        <a
            href="{{ Auth::guard()->user()->url }}"
            class="p-0 nav-link d-flex lh-1 text-reset"
            data-bs-toggle="dropdown"
            aria-label="{{ trans('core/base::forms.open_user_menu') }}"
        >
            @php($bwUser = Auth::guard()->user())
            @if ($bwUser->avatar && $bwUser->avatar->url)
                <span
                    class="crop-image-original avatar avatar-sm"
                    style="background-image: url({{ $bwUser->avatar_url }})"
                ></span>
            @else
                {{-- No uploaded photo: show the first letter of the login username --}}
                <span class="avatar avatar-sm bw-avatar-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($bwUser->username ?: $bwUser->name), 0, 1)) }}</span>
            @endif
            <div class="d-none d-xl-block ps-2">
                <div>{{ Auth::guard()->user()->name }}</div>
                <div class="mt-1 small text-muted">{{ Auth::guard()->user()->email }}</div>
            </div>
        </a>
    </x-slot:trigger>

    <x-core::dropdown.item
        :href="Auth::guard()->user()->url"
        :label="trans('core/base::layouts.profile')"
        icon="ti ti-user"
    />

    <x-core::dropdown.item
        :href="route('access.logout')"
        :label="trans('core/base::layouts.logout')"
        icon="ti ti-logout"
    />
</x-core::dropdown>
