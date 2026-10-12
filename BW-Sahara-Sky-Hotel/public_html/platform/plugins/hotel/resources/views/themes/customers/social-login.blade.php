<div class="login-options">
    <div class="login-options-title"><p>{{ trans('plugins/hotel::customer.or') }}</p></div>
    <ul class="social-login-basic">
        @foreach (SocialService::getProviderKeysEnabled() as $provider)
            <li>
                <a
                    href="{{ route('auth.social', ['provider' => $provider, 'guard' => 'customer']) }}"
                    class="social-login {{ $provider }}-login"
                >
                    @php($iconName = $provider === 'linkedin-openid' ? 'linkedin' : $provider)
                    <img src="{{ asset('vendor/core/plugins/social-login/images/icons/logo-' . $iconName . '.svg') }}" alt="" />
                    <span>{{ $provider === 'google' ? trans('plugins/hotel::customer.continue_with_google') : trans('plugins/social-login::social-login.sign_in_with', ['provider' => trans('plugins/social-login::social-login.socials.' . $provider)]) }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
