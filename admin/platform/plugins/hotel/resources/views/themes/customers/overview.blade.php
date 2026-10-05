@extends(HotelHelper::viewPath('customers.master'))

@section('content')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h1 class="text-center">{{ trans('plugins/hotel::customer.account_information') }}</h1>
        </div>

        <div class="mt-30">
            <div class="row">
                <div class="col-md-6">
                    @if (auth('customer')->user()->name)
                        <p>
                            <strong>
                                {{ trans('plugins/hotel::customer.name') }}
                            </strong>:
                            <i>{{ auth('customer')->user()->name }}</i>
                        </p>
                    @endif

                    @if (auth('customer')->user()->email)
                        <p>
                            <strong>
                                {{ trans('plugins/hotel::customer.form.email') }}
                            </strong>:
                            <i>{{ auth('customer')->user()->email }}</i>
                        </p>
                    @endif

                    @if (auth('customer')->user()->country)
                        <p>
                            <strong>
                                {{ trans('plugins/hotel::customer.form.country') }}
                            </strong>:
                            <i>{{ auth('customer')->user()->country }}</i>
                        </p>
                    @endif

                    @if (auth('customer')->user()->city)
                        <p>
                            <strong>
                                {{ trans('plugins/hotel::customer.form.city') }}
                            </strong>:
                            <i>{{ auth('customer')->user()->city }}</i>
                        </p>
                    @endif

                    @if (auth('customer')->user()->zip)
                        <p>
                            <strong>
                                {{ trans('plugins/hotel::customer.form.zip') }}
                            </strong>:
                            <i>{{ auth('customer')->user()->zip }}</i>
                        </p>
                    @endif
                </div>
                <div class="col-md-6">
                    @if (auth('customer')->user()->dob)
                        <p>
                            <strong>
                                {{ trans('plugins/hotel::customer.date_of_birth') }}
                            </strong>:
                            <i>{{ auth('customer')->user()->dob }}</i>
                        </p>
                    @endif

                    @if (auth('customer')->user()->phone)
                        <p>
                            <strong>
                                {{ trans('plugins/hotel::customer.form.phone') }}
                            </strong>:
                            <i>{{ auth('customer')->user()->phone }}</i>
                        </p>
                    @endif

                    @if (auth('customer')->user()->state)
                        <p>
                            <strong>
                                {{ trans('plugins/hotel::customer.form.state') }}
                            </strong>:
                            <i>{{ auth('customer')->user()->state }}</i>
                        </p>
                    @endif

                    @if (auth('customer')->user()->address)
                        <p>
                            <strong>
                                {{ trans('plugins/hotel::customer.form.address') }}
                            </strong>:
                            <i>{{ auth('customer')->user()->address }}</i>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
