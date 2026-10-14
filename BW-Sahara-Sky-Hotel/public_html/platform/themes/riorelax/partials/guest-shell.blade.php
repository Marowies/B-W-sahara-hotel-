{{-- Keep the CMS forms, assets and customer authorization; only replace the guest presentation. --}}
<style>
    body { background: #f6f1e8 !important; color: #39291e; }
    .sky-guest { --primary-color: #60452f; --primary-color-hover: #493321; --secondary-color: #60452f; --btn-text-color-hover: #fff; font-family: Arial, sans-serif; color: #39291e; }
    .sky-guest a { color: #60452f; }
    .sky-guest-header { padding: 24px clamp(20px,5vw,80px); background: #60452f; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 24px; }
    .sky-guest-header a { color: #f6f1e8; text-decoration: none; }
    .sky-guest-header .dropdown-menu a { color: #60452f; }
    .sky-guest-brand { font-family: Georgia,serif; font-size: 24px; letter-spacing: .04em; }
    .sky-guest-header nav { display: flex; gap: 22px; flex-wrap: wrap; }
    .sky-guest-main { max-width: 1280px; margin: auto; padding: 48px 20px; min-height: 60vh; }
    .sky-guest-main h1,.sky-guest-main h2,.sky-guest-main h3 { font-family: Georgia,serif; color: #60452f; }
    .sky-guest-main .customer-page { background: transparent; padding: 0; }
    .sky-guest-main .customer-body,.sky-guest-main .body-border { border: 0; }
    .sky-guest-main .profile-sidebar,.sky-guest-main .profile-content { background: #fffaf3; padding: 24px; border: 1px solid #dfd1bf; border-radius: 18px; }
    .sky-guest-main .list-group-item { background: transparent; border-color: #dfd1bf; padding: 14px; }
    .sky-guest-main .list-group-item a.active { color: #60452f; font-weight: bold; }
    .sky-guest-main .btn-primary,.sky-guest-main button[type=submit] { background: #60452f; border-color: #60452f; color: #fffaf3; border-radius: 10px; }
    .sky-guest-main .form-control,.sky-guest-main .form-select { border-color: #cbb99e; border-radius: 8px; }
    .sky-guest-main img { max-width: 100%; }
    .sky-guest-main .table-responsive { overflow-x: auto; }
    .sky-guest-footer { border-top: 1px solid #dfd1bf; padding: 30px 20px; text-align: center; }
    .sky-guest-footer a { display: inline-block; margin: 8px 14px; }
    @media(max-width:767px) { .sky-guest-main .profile-sidebar { margin-bottom: 20px; } .sky-guest-header nav { gap: 14px; } .sky-guest-main { padding-top: 28px; } }
</style>
<div class="sky-guest">
    <header class="sky-guest-header">
        <a class="sky-guest-brand" href="{{ url('/') }}">B&amp;W SAHARA SKY</a>
        <nav aria-label="{{ __('Main navigation') }}">
            <a href="{{ url('/') }}">{{ __('Home') }}</a>
            <a href="{{ url('/rooms/') }}">{{ __('Rooms') }}</a>
            <a href="{{ url('/contact-us/') }}">{{ __('Contact') }}</a>
            @if (auth('customer')->check())
                <a href="{{ route('customer.overview') }}">{{ __('My account') }}</a>
            @else
                <a href="{{ url('/login/') }}">{{ __('Sign in') }}</a>
            @endif
            {!! Theme::partial('language-switcher') !!}
            {!! Theme::partial('currency-switcher') !!}
        </nav>
    </header>
    <main id="main" class="sky-guest-main">{!! Theme::content() !!}</main>
    <footer class="sky-guest-footer">
        <p>B&amp;W Sahara Sky Hotel</p>
        <a href="{{ url('/privacy/') }}">{{ __('Privacy') }}</a>
        <a href="{{ url('/term-and-conditions/') }}">{{ __('Terms & conditions') }}</a>
        <a href="{{ url('/booking/') }}">{{ __('Book your stay') }}</a>
    </footer>
</div>
<script defer src="{{ asset('hotel-consent.js') }}?v={{ is_file(public_path('hotel-consent.js')) ? filemtime(public_path('hotel-consent.js')) : '1' }}"></script>
<script>
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
        location.assign('/login/');
    } catch (_) {
        delete link.dataset.busy;
        let status = document.getElementById('sky-logout-status');
        if (!status) { status = document.createElement('p'); status.id = 'sky-logout-status'; status.setAttribute('role','alert'); link.after(status); }
        status.textContent = @json(__('Unable to sign out. Please try again.'));
    }
});
</script>
