@php
    Theme::set('pageTitle',  __('Login'));
@endphp

<section class="about-area about-p pt-120 pb-120 p-relative fix">
    <div class="container">
        <div class="row flex-row-reverse justify-content-center align-items-center">
            @if($backgroundImage = theme_option('authentication_login_background_image'))
                <div class="col-lg-6 col-md-6">
                    <div class="booking-img">
                        <img src="{{ RvMedia::getImageURL($backgroundImage) }}" alt="{{ __('Login') }}" />
                    </div>
                </div>
            @endif

            <div class="col-xxl-5 col-xl-12 col-lg-12">
                <div class="about-content s-about-content wow fadeInRight animated" data-animation="fadeInRight" data-delay=".4s" style="visibility: visible; animation-name: fadeInRight;">
                    <div class="about-title second-title pb-25">
                        <h1>{{ __('Welcome back') }}</h1>
                    </div>
                    <div class="form-border-box">
                        {!! $form
                            ->formClass('auth-form')
                            ->contentOnly()
                            ->renderForm()
                        !!}
                    </div>
                </div>
                <div class="col-lg-12 mt-20">
                    <span class="color-grey-500 d-inline-block align-middle font-sm">
                        {{ __('Don’t have an account?') }}
                    </span>
                    <a class="d-inline-block align-middle color-success ms-1 custom-register-label" href="{{ route('customer.register') }}">{{ __('Sign up now') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
