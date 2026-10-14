@extends(HotelHelper::viewPath('customers.master'))

@section('content')
    @php
        SeoHelper::setTitle(__('Change password'));
    @endphp

    <header class="sky-page-head">
        <p class="sky-eyebrow">{{ __('My account') }}</p>
        <h1>{{ __('Change password') }}</h1>
    </header>

    <div class="sky-card sky-form-card custom-edit-account-form">
            {!! Form::open(['route' => 'customer.post.change-password', 'method' => 'post']) !!}

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group mb-20">
                        <label for="old_password" class="form-label">{{ __('Old Password') }}</label>
                        <input id="old_password" type="password" class="form-control @if ($errors->has('old_password')) is-invalid @endif" name="old_password" placeholder="{{ __('Current Password') }}" />
                        {!! Form::error('old_password', $errors) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group mb-20">
                        <label for="password" class="form-label">{{ __('New Password') }}</label>
                        <input id="password" type="password" class="form-control @if ($errors->has('old_password')) is-invalid @endif" name="password" placeholder="{{ __('New Password') }}" />
                        {!! Form::error('password', $errors) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group mb-20">
                        <label for="password_confirmation" class="form-label">{{ __('Password Confirmation') }}</label>
                        <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" placeholder="{{ __('Password Confirmation') }}" />
                    </div>
                </div>
            </div>

            <div class="sky-actions">
                <button type="submit" class="btn btn-primary">{{ __('Change password') }}</button>
            </div>

            {!! Form::close() !!}
    </div>
@stop
