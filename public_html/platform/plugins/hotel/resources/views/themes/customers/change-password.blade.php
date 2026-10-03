@extends(HotelHelper::viewPath('customers.master'))

@section('content')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h1 class="customer-page-title text-center">{{ trans('plugins/hotel::customer.change_password') }}</h1>
        </div>
        <div class="panel-body custom-edit-account-form">
            {!! Form::open(['route' => 'customer.post.change-password', 'method' => 'post']) !!}

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group mb-20 @if ($errors->has('old_password')) has-error @endif">
                        <label for="old_password" class="input-group-prepend mb-10 mt-20">{{ trans('plugins/hotel::customer.old_password') }}: </label>
                        <input id="old_password" type="password" class="form-control" name="old_password" placeholder="{{ trans('plugins/hotel::customer.current_password') }}" />
                        {!! Form::error('old_password', $errors) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group mb-20 @if ($errors->has('password')) has-error @endif">
                        <label for="password" class="input-group-prepend mb-10 mt-20">{{ trans('plugins/hotel::customer.new_password') }}: </label>
                        <input id="password" type="password" class="form-control" name="password" placeholder="{{ trans('plugins/hotel::customer.new_password') }}" />
                        {!! Form::error('password', $errors) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group mb-20 @if ($errors->has('password_confirmation')) has-error @endif">
                        <label for="password_confirmation" class="input-group-prepend mb-10 mt-20">{{ trans('plugins/hotel::customer.password_confirmation') }}: </label>
                        <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" placeholder="{{ trans('plugins/hotel::customer.password_confirmation') }}" />
                        {!! Form::error('password_confirmation', $errors) !!}
                    </div>
                </div>
            </div>
            <div class="form-group col s12 mt-20">
                <button type="submit" class="btn btn-primary btn-sm">{{ trans('plugins/hotel::customer.change_password') }}</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
@stop
