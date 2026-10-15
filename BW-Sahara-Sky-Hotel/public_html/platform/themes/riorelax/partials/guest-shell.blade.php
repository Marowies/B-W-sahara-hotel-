{{-- Keep the CMS forms, assets and customer authorization; only replace the guest presentation. --}}
@php
    $guestLocale = riorelax_guest_locale() ?: 'en';
    $guestLanguages = ['en' => 'EN', 'ar' => 'العربية', 'zh' => '中文'];
    $guestCss = public_path('themes/riorelax/css/guest-account.css');
@endphp
<link rel="stylesheet" href="{{ asset('themes/riorelax/css/guest-account.css') }}?v={{ is_file($guestCss) ? filemtime($guestCss) : '1' }}">
<div @class(['sky-guest', 'sky-guest-account' => request()->routeIs('customer.*')]) lang="{{ $guestLocale }}" dir="{{ $guestLocale === 'ar' ? 'rtl' : 'ltr' }}">
    <a class="sky-skip" href="#main">{{ __('Skip to content') }}</a>
    <header class="sky-guest-header">
        <a class="sky-guest-brand" href="{{ riorelax_guest_site_url('home') }}">B&amp;W SAHARA SKY</a>
        <nav class="sky-guest-nav" aria-label="{{ __('Main navigation') }}">
            <a href="{{ riorelax_guest_site_url('rooms') }}">{{ __('Rooms') }}</a>
            <a href="{{ riorelax_guest_site_url('experiences') }}">{{ __('Experiences') }}</a>
            <a href="{{ riorelax_guest_site_url('contact') }}">{{ __('Contact') }}</a>
            @if (auth('customer')->check())
                <a href="{{ route('customer.overview') }}" @if (request()->routeIs('customer.*')) aria-current="page" @endif>{{ __('My account') }}</a>
            @else
                <a href="{{ riorelax_guest_site_url('login') }}">{{ __('Sign in') }}</a>
            @endif
        </nav>
        <div class="sky-guest-tools">
            <nav class="sky-language" aria-label="{{ __('Language') }}">
                @foreach ($guestLanguages as $code => $label)
                    <a href="{{ riorelax_guest_switch_url($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" data-guest-locale="{{ $code }}" @if ($code === $guestLocale) aria-current="true" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            {!! Theme::partial('currency-switcher') !!}
        </div>
    </header>
    <main id="main" class="sky-guest-main" tabindex="-1">{!! Theme::content() !!}</main>
    <footer class="sky-guest-footer">
        <p>B&amp;W Sahara Sky Hotel</p>
        <a href="{{ riorelax_guest_site_url('privacy') }}">{{ __('Privacy') }}</a>
        <a href="{{ riorelax_guest_site_url('terms') }}">{{ __('Terms & conditions') }}</a>
        <a href="{{ riorelax_guest_site_url('booking') }}">{{ __('Book your stay') }}</a>
    </footer>
</div>
<script defer src="{{ asset('hotel-consent.js') }}?v={{ is_file(public_path('hotel-consent.js')) ? filemtime(public_path('hotel-consent.js')) : '1' }}"></script>
<script>
// The language choice is shared with the public site through the same cookie and saved preference.
document.addEventListener('click', async function (event) {
    const link = event.target.closest('a[data-guest-locale]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey) return;
    event.preventDefault();
    const code = link.dataset.guestLocale;
    document.cookie = 'hotel_locale=' + code + '; Path=/; Max-Age=31536000; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
    try {
        const session = await (await fetch('/api/hotel/session', {credentials:'same-origin', cache:'no-store', signal:AbortSignal.timeout(3000)})).json();
        if (session.authenticated) {
            await fetch('/api/hotel/locale', {method:'POST', credentials:'same-origin', signal:AbortSignal.timeout(3000), headers:{'Accept':'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN':session.csrf_token}, body:JSON.stringify({ui_locale:code})});
        }
    } catch (_) {}
    location.assign(link.href);
});
// The customer guard and OTP token family must both be closed on sign out.
document.addEventListener('click', async function (event) {
    const link = event.target.closest('a[href]');
    if (!link || link.href !== @json(route('customer.logout'))) return;
    event.preventDefault();
    if (link.dataset.busy) return;
    link.dataset.busy = '1';
    try {
        const session = await fetch('/api/hotel/session', {credentials:'same-origin', cache:'no-store'});
        if (!session.ok) throw new Error('session');
        const data = await session.json();
        const result = await fetch('/api/hotel/auth/logout', {method:'POST', credentials:'same-origin', headers:{'Accept':'application/json', 'X-CSRF-TOKEN':data.csrf_token}});
        if (!result.ok) throw new Error('logout');
        location.assign(@json(riorelax_guest_site_url('login')));
    } catch (_) {
        delete link.dataset.busy;
        let status = document.getElementById('sky-logout-status');
        if (!status) { status = document.createElement('p'); status.id = 'sky-logout-status'; status.setAttribute('role','alert'); link.after(status); }
        status.textContent = @json(__('Unable to sign out. Please try again.'));
    }
});
</script>
