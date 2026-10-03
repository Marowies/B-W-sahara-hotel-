<?php

namespace Botble\Hotel\Forms;

use Botble\Base\Facades\Assets;
use Botble\Base\Forms\FieldOptions\DatePickerFieldOption;
use Botble\Base\Forms\FieldOptions\EmailFieldOption;
use Botble\Base\Forms\FieldOptions\NameFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\StatusFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\DatePickerField;
use Botble\Base\Forms\Fields\EmailField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\FormAbstract;
use Botble\Hotel\Enums\BookingStatusEnum;
use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Http\Requests\CreateBookingRequest;
use Botble\Hotel\Models\Booking;
use Botble\Hotel\Models\Food;
use Botble\Hotel\Models\Service;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Carbon\Carbon;

class BookingCreateForm extends FormAbstract
{
    public function setup(): void
    {
        Assets::addScripts(['booking-create']);
        Assets::addScriptsDirectly('vendor/core/plugins/hotel/js/booking-room-search.js');
        Assets::addScriptsDirectly('vendor/core/plugins/hotel/js/customer-autocomplete.js');

        $today = Carbon::now();
        $tomorrow = Carbon::now()->addDay();
        $selectedStartDate = $today->format(HotelHelper::getDateFormat());
        $selectedEndDate = $tomorrow->format(HotelHelper::getDateFormat());

        $services = Service::query()
            ->select(['id', 'name', 'price', 'price_type'])
            ->where('status', 'published')
            ->get();

        $foods = [];
        if (HotelHelper::isEnableFoodOrder()) {
            $foods = Food::query()
                ->select(['id', 'name', 'price', 'currency_id'])
                ->where('status', 'published')
                ->get();
        }

        $this
            ->model(Booking::class)
            ->setValidatorClass(CreateBookingRequest::class)
            ->withCustomFields()
            ->columns()
            ->add(
                'start_date',
                DatePickerField::class,
                DatePickerFieldOption::make()
                ->label(trans('plugins/hotel::booking.start_date'))
                ->required()
                ->addAttribute('data-date-format', HotelHelper::getDateFormat())
                ->addAttribute('id', 'booking_start_date')
                ->value($selectedStartDate)
                ->colspan(1)
            )
            ->add(
                'end_date',
                DatePickerField::class,
                DatePickerFieldOption::make()
                ->label(trans('plugins/hotel::booking.end_date'))
                ->required()
                ->addAttribute('data-date-format', HotelHelper::getDateFormat())
                ->addAttribute('id', 'booking_end_date')
                ->value($selectedEndDate)
                ->colspan(1)
            )
            ->add('search_rooms', 'html', [
                'html' => '<div class="form-group col-md-12">
                    <button type="button" id="search_rooms_button" class="btn btn-info">
                        <i class="fa fa-search"></i> ' . trans('plugins/hotel::booking.search_rooms') . '
                    </button>
                </div>
                <div id="room_cards_container" class="col-md-12"></div>',
                'colspan' => 2,
            ])
            ->add('booking_form_container', 'html', [
                'html' => '<div id="booking_form_container" class="d-none">
                    <div class="form-group col-md-12">
                        <hr>
                        <h4>' . trans('plugins/hotel::booking.selected_room') . ': <span id="selected_room_name"></span></h4>
                    </div>
                </div>',
                'colspan' => 2,
            ])
            ->add('room_id', 'hidden', [
                'value' => '',
                'attr' => [
                    'id' => 'room_id',
                ],
            ])
            ->add('booking_details_container', 'html', [
                'html' => '<div id="booking_details_container" class="d-none">',
                'colspan' => 2,
            ])
            ->add('customer_search_container', 'html', [
                'html' => '
                <div class="form-group mb-3">
                    <label for="customer_search">' . trans('plugins/hotel::booking.customer') . '</label>
                    <input type="text" id="customer_search" class="form-control" placeholder="' . trans('plugins/hotel::booking.search_customer') . '">
                    <div id="customer_search_results" class="dropdown-menu w-100" style="display: none;"></div>
                    <button class="btn btn-sm btn-info mt-2" type="button" id="btn_create_new_customer">
                        ' . trans('plugins/hotel::booking.create_new_customer') . '
                    </button>
                    <div id="selected_customer_info" class="mt-2" style="display: none;"></div>
                </div>
                ' . view('plugins/hotel::customer-create-modal')->render(),
                'colspan' => 2,
            ])
            ->add(
                'customer_id',
                'hidden',
                [
                    'value' => 0,
                    'attr' => [
                        'id' => 'customer_id',
                    ],
                ]
            )
            ->add('open_columns_wrapper', 'html', [
                'html' => '<div class="row row-cols-lg-2">',
            ])
            ->add(
                'rooms',
                NumberField::class,
                NumberFieldOption::make()
                ->label(trans('plugins/hotel::booking.number_of_rooms'))
                ->min(1)
                ->defaultValue(1)
                ->helperText(trans('plugins/hotel::booking.number_of_rooms_helper'))
                ->colspan(1)
            )
            ->add(
                'number_of_guests',
                NumberField::class,
                NumberFieldOption::make()
                ->label(trans('plugins/hotel::booking.number_of_guests'))
                ->min(HotelHelper::getMinimumNumberOfGuests())
                ->max(HotelHelper::getMaximumNumberOfGuests())
                ->defaultValue(HotelHelper::getMinimumNumberOfGuests())
                ->helperText(trans('plugins/hotel::booking.number_of_guests_helper'))
                ->colspan(1)
            )
            ->add(
                'number_of_children',
                NumberField::class,
                NumberFieldOption::make()
                ->label(trans('plugins/hotel::booking.number_of_children'))
                ->min(0)
                ->defaultValue(0)
                ->helperText(trans('plugins/hotel::booking.number_of_children_helper'))
                ->colspan(1)
            )
            ->add(
                'first_name',
                TextField::class,
                NameFieldOption::make()
                ->label(trans('plugins/hotel::booking.first_name'))
                ->required()
                ->placeholder(trans('plugins/hotel::booking.first_name_placeholder'))
                ->colspan(1)
            )
            ->add(
                'last_name',
                TextField::class,
                NameFieldOption::make()
                ->label(trans('plugins/hotel::booking.last_name'))
                ->required()
                ->placeholder(trans('plugins/hotel::booking.last_name_placeholder'))
                ->colspan(1)
            )
            ->add(
                'email',
                EmailField::class,
                EmailFieldOption::make()
                ->required()
                ->placeholder(trans('plugins/hotel::booking.email_placeholder'))
                ->colspan(1)
            )
            ->add(
                'phone',
                TextField::class,
                TextFieldOption::make()
                ->label(trans('plugins/hotel::booking.phone'))
                ->required()
                ->placeholder(trans('plugins/hotel::booking.phone_placeholder'))
                ->colspan(1)
            )
            ->add(
                'country',
                TextField::class,
                TextFieldOption::make()
                ->label(trans('plugins/hotel::booking.country'))
                ->placeholder(trans('plugins/hotel::booking.country_placeholder'))
                ->colspan(1)
            )
            ->add(
                'state',
                TextField::class,
                TextFieldOption::make()
                ->label(trans('plugins/hotel::booking.state'))
                ->placeholder(trans('plugins/hotel::booking.state_placeholder'))
                ->colspan(1)
            )
            ->add(
                'city',
                TextField::class,
                TextFieldOption::make()
                ->label(trans('plugins/hotel::booking.city'))
                ->placeholder(trans('plugins/hotel::booking.city_placeholder'))
                ->colspan(1)
            )
            ->add(
                'address',
                TextField::class,
                TextFieldOption::make()
                ->label(trans('plugins/hotel::booking.address'))
                ->value('')
                ->placeholder(trans('plugins/hotel::booking.address_placeholder'))
                ->colspan(1)
            )
            ->add(
                'zip',
                TextField::class,
                TextFieldOption::make()
                ->label(trans('plugins/hotel::booking.zip'))
                ->placeholder(trans('plugins/hotel::booking.zip_placeholder'))
                ->colspan(1)
            )
            ->add(
                'arrival_time',
                SelectField::class,
                SelectFieldOption::make()
                ->label(trans('plugins/hotel::booking.arrival_time'))
                ->helperText(trans('plugins/hotel::booking.arrival_time_helper'))
                ->choices([
                    trans('plugins/hotel::booking.i_do_not_know') => trans('plugins/hotel::booking.i_do_not_know'),
                    '12:00 - 1:00 ' . trans('plugins/hotel::booking.am') => '12:00 - 1:00 ' . trans('plugins/hotel::booking.am'),
                    '1:00 - 2:00 ' . trans('plugins/hotel::booking.am') => '1:00 - 2:00 ' . trans('plugins/hotel::booking.am'),
                    '2:00 - 3:00 ' . trans('plugins/hotel::booking.am') => '2:00 - 3:00 ' . trans('plugins/hotel::booking.am'),
                    '3:00 - 4:00 ' . trans('plugins/hotel::booking.am') => '3:00 - 4:00 ' . trans('plugins/hotel::booking.am'),
                    '4:00 - 5:00 ' . trans('plugins/hotel::booking.am') => '4:00 - 5:00 ' . trans('plugins/hotel::booking.am'),
                    '5:00 - 6:00 ' . trans('plugins/hotel::booking.am') => '5:00 - 6:00 ' . trans('plugins/hotel::booking.am'),
                    '6:00 - 7:00 ' . trans('plugins/hotel::booking.am') => '6:00 - 7:00 ' . trans('plugins/hotel::booking.am'),
                    '7:00 - 8:00 ' . trans('plugins/hotel::booking.am') => '7:00 - 8:00 ' . trans('plugins/hotel::booking.am'),
                    '8:00 - 9:00 ' . trans('plugins/hotel::booking.am') => '8:00 - 9:00 ' . trans('plugins/hotel::booking.am'),
                    '9:00 - 10:00 ' . trans('plugins/hotel::booking.am') => '9:00 - 10:00 ' . trans('plugins/hotel::booking.am'),
                    '10:00 - 11:00 ' . trans('plugins/hotel::booking.am') => '10:00 - 11:00 ' . trans('plugins/hotel::booking.am'),
                    '11:00 - 12:00 ' . trans('plugins/hotel::booking.am') => '11:00 - 12:00 ' . trans('plugins/hotel::booking.am'),
                    '12:00 - 1:00 ' . trans('plugins/hotel::booking.pm') => '12:00 - 1:00 ' . trans('plugins/hotel::booking.pm'),
                    '1:00 - 2:00 ' . trans('plugins/hotel::booking.pm') => '1:00 - 2:00 ' . trans('plugins/hotel::booking.pm'),
                    '2:00 - 3:00 ' . trans('plugins/hotel::booking.pm') => '2:00 - 3:00 ' . trans('plugins/hotel::booking.pm'),
                    '3:00 - 4:00 ' . trans('plugins/hotel::booking.pm') => '3:00 - 4:00 ' . trans('plugins/hotel::booking.pm'),
                    '4:00 - 5:00 ' . trans('plugins/hotel::booking.pm') => '4:00 - 5:00 ' . trans('plugins/hotel::booking.pm'),
                    '5:00 - 6:00 ' . trans('plugins/hotel::booking.pm') => '5:00 - 6:00 ' . trans('plugins/hotel::booking.pm'),
                    '6:00 - 7:00 ' . trans('plugins/hotel::booking.pm') => '6:00 - 7:00 ' . trans('plugins/hotel::booking.pm'),
                    '7:00 - 8:00 ' . trans('plugins/hotel::booking.pm') => '7:00 - 8:00 ' . trans('plugins/hotel::booking.pm'),
                    '8:00 - 9:00 ' . trans('plugins/hotel::booking.pm') => '8:00 - 9:00 ' . trans('plugins/hotel::booking.pm'),
                    '9:00 - 10:00 ' . trans('plugins/hotel::booking.pm') => '9:00 - 10:00 ' . trans('plugins/hotel::booking.pm'),
                    '10:00 - 11:00 ' . trans('plugins/hotel::booking.pm') => '10:00 - 11:00 ' . trans('plugins/hotel::booking.pm'),
                    '11:00 - 12:00 ' . trans('plugins/hotel::booking.pm') => '11:00 - 12:00 ' . trans('plugins/hotel::booking.pm'),
                ])
                ->defaultValue(trans('plugins/hotel::booking.i_do_not_know'))
                ->colspan(1)
            )
            ->add(
                'requests',
                TextareaField::class,
                TextareaFieldOption::make()
                ->label(trans('plugins/hotel::booking.requests'))
                ->rows(3)
                ->placeholder(trans('plugins/hotel::booking.requests_placeholder'))
                ->colspan(2)
            )
            ->add('booking_details_end', 'html', [
                'html' => '</div></div>',
            ]);

        if ($services->count()) {
            $servicesArray = $services->mapWithKeys(function ($service) {
                return [$service->id => $service];
            })->toArray();

            $this->addMetaBoxes([
                'services_box' => [
                    'title' => trans('plugins/hotel::booking.services'),
                    'content' => view('plugins/hotel::booking-services', [
                        'services' => $servicesArray,
                        'selectedServices' => [],
                    ])->render(),
                ],
            ]);
        }

        if (count($foods)) {
            $foodsArray = $foods->mapWithKeys(function ($food) {
                return [$food->id => $food];
            })->toArray();

            $this->addMetaBoxes([
                'foods_box' => [
                    'title' => trans('plugins/hotel::booking.foods'),
                    'content' => view('plugins/hotel::booking-foods', [
                        'foods' => $foodsArray,
                        'selectedFoods' => [],
                    ])->render(),
                ],
            ]);
        }

        $this
            ->add(
                'payment_method',
                SelectField::class,
                SelectFieldOption::make()
                ->label(trans('plugins/hotel::booking.payment_method'))
                ->choices(PaymentMethodEnum::labels())
                ->helperText(trans('plugins/hotel::booking.payment_method_helper'))
                ->colspan(1)
            )
            ->add(
                'payment_status',
                SelectField::class,
                SelectFieldOption::make()
                ->label(trans('plugins/payment::payment.status'))
                ->choices(PaymentStatusEnum::labels())
                ->helperText(trans('plugins/hotel::booking.payment_status_helper'))
                ->colspan(1)
            )
            ->add(
                'transaction_id',
                TextField::class,
                TextFieldOption::make()
                ->label(trans('plugins/hotel::booking.transaction_id'))
                ->helperText(trans('plugins/hotel::booking.transaction_id_helper'))
                ->placeholder(trans('plugins/hotel::booking.transaction_id'))
                ->colspan(2)
            )
            ->add(
                'status',
                SelectField::class,
                StatusFieldOption::make()
                ->choices(BookingStatusEnum::labels())
                ->helperText(trans('plugins/hotel::booking.status_helper'))
                ->colspan(2)
            )
            ->setBreakFieldPoint('status');
    }
}
