@php
    Theme::set('pageTitle', SeoHelper::getTitle());
@endphp

<section class="about-area about-p pt-60 pb-60 p-relative fix">
    <div class="container">
        <div class="row flex-row-reverse justify-content-center align-items-center">
            @if($backgroundImage = theme_option('authentication_forgot_password_background_image'))
                <div class="col-lg-6 col-md-6">
                    <div class="booking-img">
                        <img src="{{ RvMedia::getImageURL($backgroundImage) }}" alt="{{ __('Forgot password') }}" />
                    </div>
                </div>
            @endif
            <div class="col-md-6 col-lg-6">
                <h1>{{ __('Forgot password') }}</h1>
                <div class="panel panel-default">
                    <div class="panel-body">
                        @if (session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif
                        <p>{{ __('Enter the email address associated with your account and we’ll send you a link to reset your password') }}</p>
                            {!! $form
                                ->formClass('auth-form form-horizontal')
                                ->contentOnly()
                                ->renderForm()
                            !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
