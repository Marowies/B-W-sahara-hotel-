<?php

namespace Botble\Hotel\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Hotel\Enums\ServicePriceTypeEnum;
use Botble\Hotel\Events\BookingCreated;
use Botble\Hotel\Events\BookingStatusChanged;
use Botble\Hotel\Events\BookingUpdated;
use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Forms\BookingCreateForm;
use Botble\Hotel\Forms\BookingForm;
use Botble\Hotel\Http\Requests\CreateBookingRequest;
use Botble\Hotel\Http\Requests\UpdateBookingRequest;
use Botble\Hotel\Models\Booking;
use Botble\Hotel\Models\BookingAddress;
use Botble\Hotel\Models\BookingRoom;
use Botble\Hotel\Models\Customer;
use Botble\Hotel\Models\Food;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Models\Service;
use Botble\Hotel\Tables\BookingTable;
use Botble\Media\Facades\RvMedia;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Services\Gateways\BankTransferPaymentService;
use Botble\Payment\Services\Gateways\CodPaymentService;
use Botble\Payment\Supports\PaymentHelper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BookingController extends BaseController
{
    public function __construct()
    {
        $this
            ->breadcrumb()
            ->add(trans('plugins/hotel::booking.bookings'), route('booking.index'));
    }

    public function index(BookingTable $table)
    {
        $this->pageTitle(trans('plugins/hotel::booking.bookings'));

        return $table->renderTable();
    }

    public function edit(Booking $booking)
    {
        $this->pageTitle(trans('core/base::forms.edit_item', ['name' => $booking->room->room_name]));

        return BookingForm::createFromModel($booking)->renderForm();
    }

    public function update(Booking $booking, UpdateBookingRequest $request)
    {
        $status = $booking->status;

        BookingForm::createFromModel($booking)
            ->setRequest($request)
            ->save();

        BookingUpdated::dispatch($booking);

        if ($booking->status != $status) {
            BookingStatusChanged::dispatch($status, $booking);
        }

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('booking.index'))
            ->withUpdatedSuccessMessage();
    }

    public function destroy(Booking $booking)
    {
        return DeleteResourceAction::make($booking);
    }

    public function create()
    {
        $this->pageTitle(trans('plugins/hotel::booking.create'));

        Assets::addScriptsDirectly('vendor/core/plugins/hotel/js/booking-create.js');
        Assets::addScriptsDirectly('vendor/core/plugins/hotel/js/booking-room-search.js');
        Assets::addScriptsDirectly('vendor/core/plugins/hotel/js/customer-autocomplete.js');
        Assets::addStylesDirectly('vendor/core/plugins/hotel/css/hotel.css');

        return BookingCreateForm::create()->renderForm();
    }

    public function searchRooms(Request $request, BaseHttpResponse $response)
    {
        $startDate = HotelHelper::dateFromRequest($request->input('start_date'));
        $endDate = HotelHelper::dateFromRequest($request->input('end_date'));

        if (! $startDate || ! $endDate) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/hotel::booking.invalid_dates'));
        }

        $availableRooms = Room::query()
            ->wherePublished()
            ->get();

        $dateFormat = HotelHelper::getDateFormat();
        $condition = [
            'start_date' => $startDate->format($dateFormat),
            'end_date' => $endDate->format($dateFormat),
            'adults' => HotelHelper::getMinimumNumberOfGuests(),
            'children' => 0,
            'rooms' => 1,
        ];

        $rooms = [];

        foreach ($availableRooms as $room) {
            /**
             * @var Room $room
             */
            if ($room->isAvailableAt($condition)) {
                $room->total_price = $room->getRoomTotalPrice($startDate->format($dateFormat), $endDate->format($dateFormat), 1);
                $room->price_html = format_price($room->total_price);
                $room->image = RvMedia::getImageUrl($room->image ?: Arr::first($room->images), 'medium', false, RvMedia::getDefaultImage());
                $rooms[] = $room;
            }
        }

        // Render the room cards view
        $html = '';
        if (count($rooms) > 0) {
            $html = view('plugins/hotel::booking-room-cards', compact('rooms'))->render();
        }

        return $response->setData([
            'html' => $html,
            'rooms' => $rooms,
        ]);
    }

    public function searchCustomers(Request $request, BaseHttpResponse $response)
    {
        $keyword = $request->input('q');

        if (empty($keyword)) {
            return $response->setData([
                'html' => view('plugins/hotel::customer-search-results', ['customers' => []])->render(),
                'customers' => [],
            ]);
        }

        $customers = Customer::query()
            ->where(function ($query) use ($keyword): void {
                $query->where('first_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('last_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%")
                    ->orWhere('phone', 'LIKE', "%{$keyword}%");
            })
            ->limit(10)
            ->get();

        $html = view('plugins/hotel::customer-search-results', compact('customers'))->render();

        return $response->setData([
            'html' => $html,
            'customers' => $customers,
        ]);
    }

    public function getCustomer(Request $request, BaseHttpResponse $response)
    {
        $customerId = $request->input('id');

        if (empty($customerId)) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/hotel::customer.customer_not_found'));
        }

        $customer = Customer::query()->find($customerId);

        if (! $customer) {
            return $response
                ->setError()
                ->setMessage(trans('plugins/hotel::customer.customer_not_found'));
        }

        $html = view('plugins/hotel::customer-info', compact('customer'))->render();

        return $response->setData([
            'html' => $html,
            'customer' => $customer,
        ]);
    }

    public function createCustomer(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:60', 'min:2'],
            'last_name' => ['required', 'string', 'max:60', 'min:2'],
            'email' => ['required', 'max:60', 'min:6', 'email', 'unique:ht_customers'],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'zip' => ['nullable', 'string'],
        ]);

        try {
            $customer = new Customer();
            $customer->fill([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'address' => $request->input('address'),
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'country' => $request->input('country'),
                'zip' => $request->input('zip'),
                'password' => Hash::make(Str::random(8)),
            ]);
            $customer->save();

            $html = view('plugins/hotel::customer-info', compact('customer'))->render();

            return $response->setData([
                'html' => $html,
                'customer' => $customer,
                'message' => trans('core/base::notices.create_success_message'),
            ]);
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function store(CreateBookingRequest $request, BaseHttpResponse $response)
    {
        $customerId = $request->input('customer_id');
        if (! $customerId || $customerId == '0') {
            $customer = Customer::query()->create([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
            ]);
            $customerId = $customer->id;
        }

        /**
         * @var Room $room
         */
        $room = Room::query()->findOrFail($request->input('room_id'));
        $startDate = HotelHelper::dateFromRequest($request->input('start_date'));
        $endDate = HotelHelper::dateFromRequest($request->input('end_date'));
        $numberOfRooms = $request->input('rooms', 1);

        $room->total_price = $room->getRoomTotalPrice($startDate, $endDate, $numberOfRooms);

        $serviceIds = $request->input('services', []);
        if (is_string($serviceIds)) {
            $serviceIds = json_decode($serviceIds, true) ?: [];
        } elseif (is_numeric($serviceIds)) {
            // Convert a single service ID to array
            $serviceIds = [$serviceIds];
        } elseif (! is_array($serviceIds)) {
            $serviceIds = [];
        }

        $foodIds = HotelHelper::isEnableFoodOrder() ? $request->input('foods', []) : [];
        if (is_string($foodIds)) {
            $foodIds = json_decode($foodIds, true) ?: [];
        } elseif (is_numeric($foodIds)) {
            // Convert single food ID to array
            $foodIds = [$foodIds];
        } elseif (! is_array($foodIds)) {
            $foodIds = [];
        }

        $booking = new Booking();
        $booking->fill([
            'status' => $request->input('status'),
            'customer_id' => $customerId,
            'requests' => $request->input('requests'),
            'arrival_time' => $request->input('arrival_time'),
            'number_of_guests' => $request->input('number_of_guests', HotelHelper::getMinimumNumberOfGuests()),
            'number_of_children' => $request->input('number_of_children', 0),
            'transaction_id' => Str::upper(Str::random(32)),
            'booking_number' => Booking::generateUniqueBookingNumber(),
        ]);

        $nights = $startDate->diffInDays($endDate);
        $amount = $room->total_price;

        if ($serviceIds && is_array($serviceIds)) {
            $services = Service::query()->whereIn('id', $serviceIds)->get();
            foreach ($services as $service) {
                if ($service->price_type == ServicePriceTypeEnum::PER_DAY) {
                    $amount += $service->price * $nights;
                } else {
                    $amount += $service->price;
                }
            }
        }

        if ($foodIds && is_array($foodIds)) {
            $foods = Food::query()->whereIn('id', $foodIds)->get();
            foreach ($foods as $food) {
                $amount += $food->price;
            }
        }

        $taxAmount = 0;
        if ($room->tax) {
            $taxAmount = $room->tax->percentage * $amount / 100;
        }

        $booking->amount = $amount + $taxAmount;
        $booking->sub_total = $amount;
        $booking->tax_amount = $taxAmount;
        $booking->currency_id = $room->currency_id;
        $booking->save();

        if ($serviceIds && is_array($serviceIds)) {
            $booking->services()->attach($serviceIds);
        }

        if ($foodIds && is_array($foodIds)) {
            $booking->foods()->attach($foodIds);
        }

        BookingRoom::query()->create([
            'room_id' => $room->getKey(),
            'room_name' => $room->name,
            'room_image' => Arr::first($room->images),
            'booking_id' => $booking->getKey(),
            'price' => $room->total_price,
            'currency_id' => $room->currency_id,
            'number_of_rooms' => $numberOfRooms,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        $bookingAddress = new BookingAddress();
        $bookingAddress->fill([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'country' => $request->input('country'),
            'zip' => $request->input('zip'),
            'address' => $request->input('address'),
            'booking_id' => $booking->getKey(),
        ]);
        $bookingAddress->save();

        // Create payment record
        if (is_plugin_active('payment')) {
            $paymentData = [
                'amount' => $booking->amount,
                'currency' => get_application_currency()->title,
                'charge_id' => $request->input('transaction_id') ?: Str::upper(Str::random(20)),
                'order_id' => $booking->id,
                'customer_id' => $customerId,
                'customer_type' => Customer::class,
                'payment_channel' => $request->input('payment_method'),
                'status' => $request->input('payment_status', PaymentStatusEnum::PENDING),
            ];

            $payment = null;

            switch ($request->input('payment_method')) {
                case PaymentMethodEnum::COD:
                    $codPaymentService = app(CodPaymentService::class);
                    $codPaymentService->execute($paymentData);

                    break;

                case PaymentMethodEnum::BANK_TRANSFER:
                    $bankTransferPaymentService = app(BankTransferPaymentService::class);
                    $bankTransferPaymentService->execute($paymentData);

                    break;

                default:
                    $payment = PaymentHelper::storeLocalPayment($paymentData);

                    break;
            }

            // Get the payment record and associate it with the booking
            if (! $payment) {
                $payment = Payment::query()
                    ->where('charge_id', $paymentData['charge_id'])
                    ->where('order_id', $booking->id)
                    ->first();
            }

            if ($payment) {
                $booking->payment_id = $payment->id;
                $booking->save();
            }
        }

        BookingCreated::dispatch($booking);

        return $response
            ->setPreviousUrl(route('booking.index'))
            ->setNextUrl(route('booking.edit', $booking->id))
            ->withCreatedSuccessMessage();
    }
}
