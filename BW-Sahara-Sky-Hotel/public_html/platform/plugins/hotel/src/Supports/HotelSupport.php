<?php

namespace Botble\Hotel\Supports;

use Botble\Hotel\Enums\ReviewStatusEnum;
use Botble\Hotel\DataTransferObjects\RoomSearchParams;
use Botble\Theme\Facades\Theme;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class HotelSupport
{
    public function isEnableEmailVerification(): bool
    {
        return (bool) $this->getSetting('verify_customer_email', 0);
    }

    public function getSettingPrefix(): ?string
    {
        return config('plugins.hotel.general.prefix');
    }

    public function isReviewEnabled(): bool
    {
        return (bool) setting('hotel_enable_review_room', 1);
    }

    public function isBookingEnabled(): bool
    {
        return (bool) setting('hotel_enable_booking', true);
    }

    public function getReviewExtraData(): array
    {
        if (! $this->isReviewEnabled()) {
            return [];
        }

        return [
            'withCount' => [
                'reviews' => function ($query): void {
                    $query->where('status', ReviewStatusEnum::APPROVED);
                },
            ],
            'withAvg' => ['reviews', 'star'],
        ];
    }

    public function getSetting(string $key, bool|int|string|null $default = ''): array|int|string|null
    {
        return setting($this->getSettingPrefix() . $key, $default);
    }

    public function loadCountriesStatesCitiesFromPluginLocation(): bool
    {
        if (! is_plugin_active('location')) {
            return false;
        }

        return (bool) $this->getSetting('load_countries_states_cities_from_location_plugin', 0);
    }

    public function viewPath(string $view): string
    {
        $themeView = Theme::getThemeNamespace() . '::views.hotel.' . $view;

        if (view()->exists($themeView)) {
            return $themeView;
        }

        return 'plugins/hotel::themes.' . $view;
    }

    public function getRoomFilters(Request|array $request): array
    {
        $data = $request instanceof Request ? $request->input() : $request;
        if (! array_key_exists('q', $data) && array_key_exists('keyword', $data)) {
            $data['q'] = $data['keyword'];
        }

        return RoomSearchParams::fromRequest($data)->toArray();
    }

    public function getRoomBookingParams(): array
    {
        $params = RoomSearchParams::fromRequest(request()->input());

        return [
            $params->startDate,
            $params->endDate,
            $params->adults,
            (int) $params->startDate->diffInDays($params->endDate),
            $params->children,
            $params->rooms,
        ];
    }

    public function getCheckoutData(?string $key = null): mixed
    {
        $checkoutToken = session('checkout_token');

        if (! $checkoutToken) {
            $checkoutToken = Str::upper(Str::random(32));
        }

        $sessionData = [];
        if (session()->has($checkoutToken)) {
            $sessionData = session($checkoutToken);
        }

        if ($key) {
            return $sessionData[$key] ?? null;
        }

        return $sessionData;
    }

    public function saveCheckoutData(array $data): void
    {
        $checkoutToken = session('checkout_token');

        $sessionData = $this->getCheckoutData();

        $data = array_merge($sessionData, $data);

        session()->put($checkoutToken, $data);
    }

    public function getDateFormat(): string
    {
        return (setting('hotel_booking_date_format') ?: config('plugins.hotel.hotel.date_format')) ?: 'd-m-Y';
    }

    public function getBookingFormDateFormat(): string
    {
        return ($this->getDateFormatDatepicker() ?: config('plugins.hotel.hotel.booking_form_date_format')) ?: 'dd-mm-yyyy';
    }

    public function dateFromRequest(string $date): Carbon|false
    {
        return Carbon::createFromFormat($this->getDateFormat(), $date);
    }

    public function getDateRangeInReport(Request $request): array
    {
        $startDate = Carbon::now()->subDays(29);
        $endDate = Carbon::now();

        if ($request->input('date_from')) {
            try {
                $startDate = Carbon::now()->createFromFormat('Y-m-d', $request->input('date_from'));
            } catch (Exception) {
                $startDate = Carbon::now()->subDays(29);
            }
        }

        if ($request->input('date_to')) {
            try {
                $endDate = Carbon::now()->createFromFormat('Y-m-d', $request->input('date_to'));
            } catch (Exception) {
                $endDate = Carbon::now();
            }
        }

        if ($endDate->gt(Carbon::now())) {
            $endDate = Carbon::now();
        }

        if ($startDate->gt($endDate)) {
            $startDate = Carbon::now()->subDays(29);
        }

        $predefinedRange = $request->input('predefined_range', trans('plugins/hotel::booking-reports.ranges.last_30_days'));

        return [$startDate, $endDate, $predefinedRange];
    }

    public function getMinimumNumberOfGuests(): int
    {
        return (int) setting('hotel_minimum_number_of_guests', 1);
    }

    public function getMaximumNumberOfGuests(): int
    {
        return (int) setting('hotel_maximum_number_of_guests', 10);
    }

    public function getBookingNumber(string|int $id): string
    {
        $prefix = setting('hotel_booking_number_prefix') ? setting('hotel_booking_number_prefix') . '-' : '';
        $suffix = setting('hotel_booking_number_suffix') ? '-' . setting('hotel_booking_number_suffix') : '';

        return sprintf(
            '#%s%d%s',
            $prefix,
            (int) config('plugins.hotel.hotel.default_number_start_number') + $id,
            $suffix
        );
    }

    public function getBookingDateFormatTemplates(): array
    {
        return [
            [
                'carbon' => 'd-m-Y',
                'datepicker' => 'dd-mm-yyyy',
            ],
            [
                'carbon' => 'm-d-Y',
                'datepicker' => 'mm-dd-yyyy',
            ],
            [
                'carbon' => 'Y-m-d',
                'datepicker' => 'yyyy-mm-dd',
            ],
            [
                'carbon' => 'd/m/Y',
                'datepicker' => 'dd/mm/yyyy',
            ],
            [
                'carbon' => 'm/d/Y',
                'datepicker' => 'mm/dd/yyyy',
            ],
            [
                'carbon' => 'Y/m/d',
                'datepicker' => 'yyyy/mm/dd',
            ],
        ];
    }

    public function getBookingDateFormatOptions(): array
    {
        $templates = $this->getBookingDateFormatTemplates();

        $options = [];

        $now = Carbon::now();

        $options[''] = trans('plugins/hotel::settings.general.default_system_date_format');

        foreach ($templates as $template) {
            $options[$template['carbon']] = $template['carbon'] . ' (' . $now->format($template['carbon']) . ')';
        }

        return $options;
    }

    public function getDateFormatDatepicker(): ?string
    {
        $dateFormat = $this->getDateFormat();
        $templates = $this->getBookingDateFormatTemplates();

        foreach ($templates as $template) {
            if ($template['carbon'] == $dateFormat) {
                return $template['datepicker'];
            }
        }

        return null;
    }

    public function isEnableFoodOrder(): bool
    {
        return (bool) $this->getSetting('hotel_booking_enabled_food_order', false);
    }
}
