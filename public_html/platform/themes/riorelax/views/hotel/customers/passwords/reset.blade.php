@php
    Theme::set('pageTitle', SeoHelper::getTitle());
@endphp

<section class="about-area about-p pt-60 pb-60 p-relative fix">
    <div class="container">
        <div class="row flex-row-reverse justify-content-center align-items-center">
            @if($backgroundImage = theme_option('authentication_reset_password_background_image'))
                <div class="col-lg-6 col-md-6">
                    <div class="booking-img">
                        <img src="{{ RvMedia::getImageURL($backgroundImage) }}" alt="{{ __('Reset password') }}" />
                    </div>
                </div>
            @endif
            <div class="col-md-6 col-lg-6">
                <h1>{{ __('Reset password') }}</h1>
                <div class="panel panel-default">
                    <div class="panel-body">
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
