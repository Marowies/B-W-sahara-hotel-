<?php

namespace Botble\Hotel\Forms\Settings;

use Botble\Base\Forms\FieldOptions\CheckboxFieldOption;
use Botble\Base\Forms\FieldOptions\LabelFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\LabelField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Http\Requests\Settings\GeneralSettingRequest;
use Botble\Setting\Forms\SettingForm;

class GeneralSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setSectionTitle(trans('plugins/hotel::settings.general.title'))
            ->setSectionDescription(trans('plugins/hotel::settings.general.description'))
            ->setValidatorClass(GeneralSettingRequest::class)
            ->add(
                'hotel_enable_booking',
                OnOffCheckboxField::class,
                CheckboxFieldOption::make()
                    ->value($enabled = HotelHelper::isBookingEnabled())
                    ->label(trans('plugins/hotel::settings.general.enable_booking'))
                    ->helperText(trans('plugins/hotel::settings.general.enable_booking_helper'))
            )
            ->addOpenCollapsible('hotel_enable_booking', '1', $enabled)
            ->tap(function (GeneralSettingForm $form) {
                $minimumNumberOfGuests = HotelHelper::getMinimumNumberOfGuests();
                $maximumNumberOfGuests = HotelHelper::getMaximumNumberOfGuests();

                return $form
                    ->add(
                        'hotel_booking_enabled_food_order',
                        OnOffCheckboxField::class,
                        OnOffFieldOption::make()
                            ->value(HotelHelper::isEnableFoodOrder())
                            ->label(trans('plugins/hotel::settings.general.enable_food_order'))
                            ->helperText(trans('plugins/hotel::settings.general.enable_food_order_helper'))
                    )
                    ->add(
                        'hotel_minimum_number_of_guests',
                        NumberField::class,
                        NumberFieldOption::make()
                            ->attributes([
                                'min' => 1,
                                'max' => old('hotel_maximum_number_of_guests', $maximumNumberOfGuests),
                            ])
                            ->value($minimumNumberOfGuests)
                            ->label(trans('plugins/hotel::settings.general.minimum_number_of_guests'))
                            ->helperText(trans('plugins/hotel::settings.general.minimum_number_of_guests_helper'))
                    )
                    ->add(
                        'hotel_maximum_number_of_guests',
                        NumberField::class,
                        NumberFieldOption::make()
                            ->attributes([
                                'min' => old('hotel_minimum_number_of_guests', $minimumNumberOfGuests),
                            ])
                            ->value($maximumNumberOfGuests)
                            ->label(trans('plugins/hotel::settings.general.maximum_number_of_guests'))
                            ->helperText(trans('plugins/hotel::settings.general.maximum_number_of_guests_helper'))
                    );
            })
            ->addCloseCollapsible('hotel_enable_booking', '1')
            ->add(
                'booking_number_format_section',
                LabelField::class,
                LabelFieldOption::make()
                    ->wrapperAttributes(['class' => 'mb-0'])
                    ->label(trans('plugins/hotel::settings.general.booking_number_format.title'))
            )
            ->add('booking_number_format_description', 'html', [
                'html' => sprintf(
                    '<p class="text-muted small">%s</p>',
                    trans('plugins/hotel::settings.general.booking_number_format.description', ['format' => sprintf(
                        '<span class="sample-booking-number-prefix">%s</span>%s' .
                        '<span class="sample-booking-number-suffix">%s</span>',
                        setting('hotel_booking_number_prefix') ? setting('hotel_booking_number_prefix') . '-' : '',
                        config('plugins.hotel.hotel.default_number_start_number'),
                        setting('hotel_booking_number_suffix') ? '-' . setting('hotel_booking_number_suffix') : '',
                    )])
                ),
            ])
            ->addOpenFieldset('booking_number_format_section', ['class' => 'form-fieldset d-flex gap-3'])
            ->add(
                'hotel_booking_number_prefix',
                TextField::class,
                TextFieldOption::make()
                    ->wrapperAttributes(['class' => 'position-relative w-full'])
                    ->label(trans('plugins/hotel::settings.general.booking_number_format.start_with'))
                    ->placeholder(trans('plugins/hotel::settings.general.booking_number_format.start_with_placeholder'))
                    ->value(setting('hotel_booking_number_prefix'))
            )
            ->add(
                'hotel_booking_number_suffix',
                TextField::class,
                TextFieldOption::make()
                    ->wrapperAttributes(['class' => 'position-relative w-full'])
                    ->label(trans('plugins/hotel::settings.general.booking_number_format.end_with'))
                    ->placeholder(trans('plugins/hotel::settings.general.booking_number_format.end_with_placeholder'))
                    ->value(setting('hotel_booking_number_suffix'))
            )
            ->addCloseFieldset('booking_number_format_section')
            ->add(
                'hotel_booking_date_format',
                SelectField::class,
                SelectFieldOption::make()
                    ->choices(HotelHelper::getBookingDateFormatOptions())
                    ->selected(setting('hotel_booking_date_format'))
                    ->label(trans('plugins/hotel::settings.general.booking_date_format'))
                    ->helperText(trans('plugins/hotel::settings.general.booking_date_format_helper'))
            )
            ->add(
                'verify_customer_email',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->value($verifyEmail = HotelHelper::isEnableEmailVerification())
                    ->label(trans('plugins/hotel::settings.general.verify_customer_email'))
                    ->helperText(trans('plugins/hotel::settings.general.verify_customer_email_helper'))
            )
            ->addOpenCollapsible('hotel_verify_customer_email', '1', $verifyEmail)
            ->add(
                'hotel_verification_expire_minutes',
                NumberField::class,
                NumberFieldOption::make()
                    ->attributes([
                        'min' => 1,
                        'max' => 10080,
                        'step' => 1,
                    ])
                    ->value(setting('hotel_verification_expire_minutes', 60))
                    ->label(trans('plugins/hotel::settings.general.verification_expire_minutes'))
                    ->helperText(trans('plugins/hotel::settings.general.verification_expire_minutes_helper'))
            )
            ->addCloseCollapsible('hotel_verify_customer_email', '1');
    }
}
