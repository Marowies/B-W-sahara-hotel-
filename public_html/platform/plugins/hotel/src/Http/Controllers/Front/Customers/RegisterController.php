<?php

namespace Botble\Hotel\Http\Controllers\Front\Customers;

use Botble\ACL\Traits\RegistersUsers;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Forms\Fronts\Auth\RegisterForm;
use Botble\Hotel\Http\Requests\Fronts\Auth\RegisterRequest;
use Botble\Hotel\Models\Customer;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

class RegisterController extends BaseController
{
    use RegistersUsers;

    protected string $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('customer.guest');
    }

    public function showRegistrationForm()
    {
        SeoHelper::setTitle(trans('plugins/hotel::customer.register'));

        Theme::breadcrumb()->add(trans('plugins/hotel::customer.register'), route('customer.register'));

        if (! session()->has('url.intended') &&
            ! in_array(url()->previous(), [route('customer.login'), route('customer.register')])
        ) {
            session(['url.intended' => url()->previous()]);
        }

        return Theme::scope(
            'hotel.customers.register',
            ['form' => RegisterForm::create()],
            'plugins/hotel::themes.customers.register'
        )->render();
    }

    public function register(RegisterRequest $request)
    {
        do_action('customer_register_validation', $request);

        /**
         * @var Customer $customer
         */
        $customer = $this->create($request->input());

        event(new Registered($customer));

        if (HotelHelper::isEnableEmailVerification()) {
            $this->registered($request, $customer);

            $message = trans('plugins/hotel::customer.verification_email_sent');

            return $this
                ->httpResponse()
                ->setNextUrl(route('customer.login'))
                ->with(['auth_warning_message' => $message])
                ->setMessage($message);
        }

        $customer->confirmed_at = Carbon::now();
        $customer->save();

        $this->guard()->login($customer);

        return $this
            ->httpResponse()
            ->setNextUrl($this->redirectPath())->setMessage(trans('plugins/hotel::customer.registered_successfully'));
    }

    protected function create(array $data)
    {
        return Customer::query()->forceCreate([
            'first_name' => BaseHelper::clean($data['first_name']),
            'last_name' => BaseHelper::clean($data['last_name']),
            'email' => BaseHelper::clean($data['email']),
            'password' => Hash::make($data['password']),
        ]);
    }

    protected function guard()
    {
        return auth('customer');
    }

    public function confirm(int|string $id, Request $request)
    {
        abort_unless(URL::hasValidSignature($request), 404);

        $customer = Customer::query()->findOrFail($id);

        $customer->confirmed_at = Carbon::now();
        $customer->save();

        $this->guard()->login($customer);

        return $this
            ->httpResponse()
            ->setNextUrl(route('customer.overview'))
            ->setMessage(trans('plugins/hotel::customer.email_confirmed_successfully'));
    }

    public function resendConfirmation(Request $request)
    {
        /**
         * @var Customer $customer
         */
        $customer =Customer::query()->where('email', $request->input('email'))->first();

        if (! $customer) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/hotel::customer.customer_not_found'));
        }

        $customer->sendEmailVerificationNotification();

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/hotel::customer.confirmation_email_resent'));
    }
}
