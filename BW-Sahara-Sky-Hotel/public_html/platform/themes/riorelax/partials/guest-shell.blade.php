{{-- Keep the CMS forms, assets and customer authorization; only replace the guest presentation. --}}
@php
    $guestLocale = riorelax_guest_locale() ?: 'en';
    $guestCss = public_path('themes/riorelax/css/guest-account.css');
@endphp
<link rel="stylesheet" href="{{ asset('themes/riorelax/css/guest-account.css') }}?v={{ is_file($guestCss) ? filemtime($guestCss) : '1' }}">
<div @class(['sky-guest', 'sky-guest-account' => request()->routeIs('customer.*')]) lang="{{ $guestLocale }}" dir="{{ $guestLocale === 'ar' ? 'rtl' : 'ltr' }}">
    <a class="sky-skip" href="#main">{{ __('Skip to content') }}</a>
    {{-- Same brand, links and controls as the public site header. --}}
    <header class="sky-guest-header sky-site-header">
        <a class="sky-site-brand" href="{{ riorelax_guest_site_url('home') }}">
            <img src="{{ asset('themes/riorelax/images/site-logo.webp') }}" alt="" width="120" height="54">
            <span>B&amp;W SAHARA SKY<small>THE DESERT COLLECTION</small></span>
        </a>
        <nav class="sky-site-nav" aria-label="{{ __('Main navigation') }}">
            <a href="{{ riorelax_guest_site_url('home') }}">{{ __('Home') }}</a>
            <a href="{{ riorelax_guest_site_url('rooms') }}">{{ __('Rooms') }}</a>
            <a href="{{ riorelax_guest_site_url('experiences') }}">{{ __('Experiences') }}</a>
            <a href="{{ riorelax_guest_site_url('gallery') }}">{{ __('Gallery') }}</a>
            <a href="{{ riorelax_guest_site_url('about') }}">{{ __('Our story') }}</a>
            <a href="{{ riorelax_guest_site_url('contact') }}">{{ __('Contact') }}</a>
        </nav>
        <div class="sky-site-tools">
            {!! Theme::partial('currency-switcher') !!}
            <details class="sky-lang">
                <summary aria-label="{{ __('Language') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/></svg>
                    <span>{{ ['en' => 'EN', 'ar' => 'AR', 'zh' => '中文'][$guestLocale] ?? 'EN' }}</span>
                    <svg class="sky-lang-chev" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </summary>
                <nav class="sky-lang-list" aria-label="{{ __('Language') }}">
                    @foreach (['en' => ['English', 'EN'], 'ar' => ['العربية', 'AR'], 'zh' => ['中文', '中文']] as $code => [$name, $short])
                        <a href="{{ riorelax_guest_switch_url($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" data-guest-locale="{{ $code }}" @if ($code === $guestLocale) aria-current="true" @endif><span>{{ $name }}</span><small>{{ $short }}</small></a>
                    @endforeach
                </nav>
            </details>
            @if (auth('customer')->check())
                <a class="sky-site-account" href="{{ route('customer.overview') }}" aria-label="{{ __('My account') }}" @if (request()->routeIs('customer.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-5 4-7 8-7s7 2 8 7"/></svg>
                </a>
            @else
                <a class="sky-site-account" href="{{ riorelax_guest_site_url('login') }}" aria-label="{{ __('Sign in') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-5 4-7 8-7s7 2 8 7"/></svg>
                </a>
            @endif
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
// Reaching the authenticated guest page completes the guarded sign-in return.
try { sessionStorage.removeItem('hotel-account-return'); } catch (_) {}
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
