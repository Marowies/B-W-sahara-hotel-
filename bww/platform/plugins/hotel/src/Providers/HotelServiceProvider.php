<?php

namespace Botble\Hotel\Providers;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\DashboardMenu;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Facades\PanelSectionManager;
use Botble\Base\Supports\DashboardMenu as DashboardMenuSupport;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Hotel\Commands\SyncCalendarsCommand;
use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Forms\Fronts\Auth\ForgotPasswordForm;
use Botble\Hotel\Forms\Fronts\Auth\LoginForm;
use Botble\Hotel\Forms\Fronts\Auth\RegisterForm;
use Botble\Hotel\Forms\Fronts\Auth\ResetPasswordForm;
use Botble\Hotel\Http\Middleware\RedirectIfCustomer;
use Botble\Hotel\Http\Middleware\RedirectIfNotCustomer;
use Botble\Hotel\Http\Requests\Fronts\Auth\ForgotPasswordRequest;
use Botble\Hotel\Http\Requests\Fronts\Auth\LoginRequest;
use Botble\Hotel\Http\Requests\Fronts\Auth\RegisterRequest;
use Botble\Hotel\Http\Requests\Fronts\Auth\ResetPasswordRequest;
use Botble\Hotel\Models\Amenity;
use Botble\Hotel\Models\Booking;
use Botble\Hotel\Models\BookingAddress;
use Botble\Hotel\Models\BookingRoom;
use Botble\Hotel\Models\Currency;
use Botble\Hotel\Models\Customer;
use Botble\Hotel\Models\Feature;
use Botble\Hotel\Models\Food;
use Botble\Hotel\Models\FoodType;
use Botble\Hotel\Models\Place;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Models\RoomCategory;
use Botble\Hotel\Models\RoomDate;
use Botble\Hotel\Models\Service;
use Botble\Hotel\Models\Tax;
use Botble\Hotel\PanelSections\SettingHotelPanelSection;
use Botble\Hotel\Repositories\Eloquent\AmenityRepository;
use Botble\Hotel\Repositories\Eloquent\BookingAddressRepository;
use Botble\Hotel\Repositories\Eloquent\BookingRepository;
use Botble\Hotel\Repositories\Eloquent\BookingRoomRepository;
use Botble\Hotel\Repositories\Eloquent\CurrencyRepository;
use Botble\Hotel\Repositories\Eloquent\CustomerRepository;
use Botble\Hotel\Repositories\Eloquent\FeatureRepository;
use Botble\Hotel\Repositories\Eloquent\FoodRepository;
use Botble\Hotel\Repositories\Eloquent\FoodTypeRepository;
use Botble\Hotel\Repositories\Eloquent\PlaceRepository;
use Botble\Hotel\Repositories\Eloquent\RoomCategoryRepository;
use Botble\Hotel\Repositories\Eloquent\RoomDateRepository;
use Botble\Hotel\Repositories\Eloquent\RoomRepository;
use Botble\Hotel\Repositories\Eloquent\ServiceRepository;
use Botble\Hotel\Repositories\Eloquent\TaxRepository;
use Botble\Hotel\Repositories\Interfaces\AmenityInterface;
use Botble\Hotel\Repositories\Interfaces\BookingAddressInterface;
use Botble\Hotel\Repositories\Interfaces\BookingInterface;
use Botble\Hotel\Repositories\Interfaces\BookingRoomInterface;
use Botble\Hotel\Repositories\Interfaces\CurrencyInterface;
use Botble\Hotel\Repositories\Interfaces\CustomerInterface;
use Botble\Hotel\Repositories\Interfaces\FeatureInterface;
use Botble\Hotel\Repositories\Interfaces\FoodInterface;
use Botble\Hotel\Repositories\Interfaces\FoodTypeInterface;
use Botble\Hotel\Repositories\Interfaces\PlaceInterface;
use Botble\Hotel\Repositories\Interfaces\RoomCategoryInterface;
use Botble\Hotel\Repositories\Interfaces\RoomDateInterface;
use Botble\Hotel\Repositories\Interfaces\RoomInterface;
use Botble\Hotel\Repositories\Interfaces\ServiceInterface;
use Botble\Hotel\Repositories\Interfaces\TaxInterface;
use Botble\LanguageAdvanced\Supports\LanguageAdvancedManager;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Slug\Facades\SlugHelper;
use Botble\SocialLogin\Facades\SocialService;
use Botble\Theme\Facades\SiteMapManager;
use Botble\Theme\FormFrontManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class HotelServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register(): void
    {
        config([
            'auth.guards.customer' => [
                'driver' => 'session',
                'provider' => 'customers',
            ],
            'auth.providers.customers' => [
                'driver' => 'eloquent',
                'model' => Customer::class,
            ],
            'auth.passwords.customers' => [
                'provider' => 'customers',
                'table' => 'ht_customer_password_resets',
                'expire' => 60,
            ],
        ]);
        /**
         * @var Router $router
         */
        $router = $this->app['router'];

        $router->aliasMiddleware('customer', RedirectIfNotCustomer::class);
        $router->aliasMiddleware('customer.guest', RedirectIfCustomer::class);

        $aliasLoader = AliasLoader::getInstance();

        if (! class_exists('HotelHelper')) {
            $aliasLoader->alias('HotelHelper', HotelHelper::class);
        }

        $this->app->bind(CurrencyInterface::class, function () {
            return new CurrencyRepository(new Currency());
        });

        $this->app->bind(RoomInterface::class, function () {
            return new RoomRepository(new Room());
        });

        $this->app->bind(RoomDateInterface::class, function () {
            return new RoomDateRepository(new RoomDate());
        });

        $this->app->bind(AmenityInterface::class, function () {
            return new AmenityRepository(new Amenity());
        });

        $this->app->bind(FoodInterface::class, function () {
            return new FoodRepository(new Food());
        });

        $this->app->bind(FoodTypeInterface::class, function () {
            return new FoodTypeRepository(new FoodType());
        });

        $this->app->bind(BookingInterface::class, function () {
            return new BookingRepository(new Booking());
        });

        $this->app->bind(BookingAddressInterface::class, function () {
            return new BookingAddressRepository(new BookingAddress());
        });

        $this->app->bind(BookingRoomInterface::class, function () {
            return new BookingRoomRepository(new BookingRoom());
        });

        $this->app->bind(CustomerInterface::class, function () {
            return new CustomerRepository(new Customer());
        });

        $this->app->bind(RoomCategoryInterface::class, function () {
            return new RoomCategoryRepository(new RoomCategory());
        });

        $this->app->bind(FeatureInterface::class, function () {
            return new FeatureRepository(new Feature());
        });

        $this->app->bind(ServiceInterface::class, function () {
            return new ServiceRepository(new Service());
        });

        $this->app->bind(PlaceInterface::class, function () {
            return new PlaceRepository(new Place());
        });

        $this->app->bind(TaxInterface::class, function () {
            return new TaxRepository(new Tax());
        });
    }

    public function boot(): void
    {
        $this->setNamespace('plugins/hotel')
            ->loadAndPublishConfigurations(['permissions', 'hotel', 'email'])
            ->loadMigrations()
            ->loadHelpers()
            ->loadAndPublishTranslations()
            ->loadAndPublishViews()
            ->loadRoutes()
            ->publishAssets();

        SlugHelper::registering(function (): void {
            SlugHelper::registerModule(Room::class, fn () => trans('plugins/hotel::room.rooms'));
            SlugHelper::setPrefix(Room::class, 'rooms');

            SlugHelper::registerModule(RoomCategory::class, fn () => trans('plugins/hotel::room.categories'));
            SlugHelper::setPrefix(RoomCategory::class, 'room-categories');

            SlugHelper::registerModule(Place::class, fn () => trans('plugins/hotel::place.places'));
            SlugHelper::setPrefix(Place::class, 'places');

            SlugHelper::registerModule(Service::class, fn () => trans('plugins/hotel::service.services'));
            SlugHelper::setPrefix(Service::class, 'services');

            SlugHelper::registerModule(Food::class, fn () => trans('plugins/hotel::food.name'));
            SlugHelper::setPrefix(Food::class, 'foods');
        });

        PanelSectionManager::default()->beforeRendering(function (): void {
            PanelSectionManager::register(SettingHotelPanelSection::class);
        });

        DashboardMenu::default()->beforeRetrieving(function (): void {
            DashboardMenu::make()
                ->registerItem([
                    'id' => 'cms-plugins-hotel',
                    'priority' => 1,
                    'name' => 'plugins/hotel::hotel.name',
                    'icon' => 'ti ti-building-skyscraper',
                    'route' => 'room.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-room',
                    'priority' => 0,
                    'parent_id' => 'cms-plugins-hotel',
                    'name' => 'plugins/hotel::room.name',
                    'icon' => 'ti ti-bed',
                    'route' => 'room.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-room-category',
                    'priority' => 1,
                    'parent_id' => 'cms-plugins-hotel',
                    'name' => 'plugins/hotel::room-category.name',
                    'icon' => 'ti ti-tags',
                    'route' => 'room-category.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-amenities',
                    'priority' => 2,
                    'parent_id' => 'cms-plugins-hotel',
                    'name' => 'plugins/hotel::amenity.name',
                    'icon' => 'ti ti-tools',
                    'route' => 'amenity.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-food',
                    'priority' => 3,
                    'parent_id' => 'cms-plugins-hotel',
                    'name' => 'plugins/hotel::food.name',
                    'icon' => 'ti ti-tools-kitchen',
                    'route' => 'food.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-food-type',
                    'priority' => 4,
                    'parent_id' => 'cms-plugins-hotel',
                    'name' => 'plugins/hotel::food-type.name',
                    'icon' => 'ti ti-tools-kitchen-2',
                    'route' => 'food-type.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-feature',
                    'priority' => 5,
                    'parent_id' => 'cms-plugins-hotel',
                    'name' => 'plugins/hotel::feature.menu',
                    'icon' => 'ti ti-star',
                    'route' => 'feature.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-service',
                    'priority' => 6,
                    'parent_id' => 'cms-plugins-hotel',
                    'name' => 'plugins/hotel::service.name',
                    'icon' => 'ti ti-bell-ringing',
                    'route' => 'service.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-place',
                    'priority' => 7,
                    'parent_id' => 'cms-plugins-hotel',
                    'name' => 'plugins/hotel::place.name',
                    'icon' => 'ti ti-map-pin',
                    'route' => 'place.index',
                ])
                ->when(
                    HotelHelper::isReviewEnabled(),
                    function (DashboardMenuSupport $menu) {
                        return $menu->registerItem([
                            'id' => 'cms-plugins-hotel-review',
                            'priority' => 8,
                            'parent_id' => 'cms-plugins-hotel',
                            'name' => 'plugins/hotel::review.name',
                            'icon' => 'ti ti-message-star',
                            'route' => 'review.index',
                        ]);
                    }
                )
                ->registerItem([
                    'id' => 'cms-plugins-customer',
                    'priority' => 9,
                    'parent_id' => 'cms-plugins-hotel',
                    'name' => 'plugins/hotel::customer.name',
                    'icon' => 'ti ti-users',
                    'route' => 'customer.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-tax',
                    'priority' => 10,
                    'parent_id' => 'cms-plugins-hotel',
                    'name' => 'plugins/hotel::tax.name',
                    'icon' => 'ti ti-receipt-tax',
                    'route' => 'tax.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-hotel-coupons',
                    'priority' => 11,
                    'parent_id' => 'cms-plugins-hotel',
                    'name' => 'plugins/hotel::coupon.name',
                    'icon' => 'ti ti-discount-2',
                    'route' => 'coupons.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-booking',
                    'priority' => 1,
                    'name' => 'plugins/hotel::booking.bookings',
                    'icon' => 'ti ti-calendar-event',
                    'route' => 'booking.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-booking-reports',
                    'priority' => 1,
                    'parent_id' => 'cms-plugins-booking',
                    'name' => 'plugins/hotel::booking.reports',
                    'icon' => 'ti ti-report',
                    'route' => 'booking.reports.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-booking-calendar',
                    'priority' => 2,
                    'parent_id' => 'cms-plugins-booking',
                    'name' => 'plugins/hotel::booking.calendar',
                    'icon' => 'ti ti-calendar',
                    'route' => 'booking.calendar.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-ical-sync',
                    'priority' => 3,
                    'parent_id' => 'cms-plugins-booking',
                    'name' => 'plugins/hotel::ical.name',
                    'icon' => 'ti ti-refresh',
                    'route' => 'ical.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-booking-list',
                    'priority' => 5,
                    'parent_id' => 'cms-plugins-booking',
                    'name' => 'plugins/hotel::booking.bookings',
                    'icon' => 'ti ti-list',
                    'route' => 'booking.index',
                ])
                ->registerItem([
                    'id' => 'cms-plugins-invoice',
                    'priority' => 6,
                    'parent_id' => 'cms-plugins-booking',
                    'name' => 'plugins/hotel::invoice.name',
                    'icon' => 'ti ti-receipt',
                    'route' => 'invoices.index',
                ]);
        });

        $this->app['events']->listen(RouteMatched::class, function (): void {
            EmailHandler::addTemplateSettings(HOTEL_MODULE_SCREEN_NAME, config('plugins.hotel.email'));

            Assets::addScripts(['booking-create'])
                ->addStylesDirectly('vendor/core/plugins/hotel/css/hotel.css');
        });

        if (defined('LANGUAGE_MODULE_SCREEN_NAME') && defined('LANGUAGE_ADVANCED_MODULE_SCREEN_NAME')) {
            LanguageAdvancedManager::registerModule(Room::class, [
                'name',
                'description',
                'content',
            ]);

            LanguageAdvancedManager::registerModule(RoomCategory::class, [
                'name',
                'description',
            ]);

            LanguageAdvancedManager::registerModule(Amenity::class, [
                'name',
                'description',
            ]);

            LanguageAdvancedManager::registerModule(Food::class, [
                'name',
                'description',
                'content',
            ]);

            LanguageAdvancedManager::registerModule(FoodType::class, [
                'name',
            ]);

            LanguageAdvancedManager::registerModule(Feature::class, [
                'name',
                'description',
            ]);

            LanguageAdvancedManager::registerModule(Service::class, [
                'name',
                'description',
                'content',
            ]);

            LanguageAdvancedManager::registerModule(Place::class, [
                'name',
                'distance',
                'description',
                'content',
            ]);
        }

        SiteMapManager::registerKey(['rooms']);

        $this->app->register(EventServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncCalendarsCommand::class,
            ]);

            $this->app->afterResolving(Schedule::class, function (Schedule $schedule): void {
                $schedule->command('hotel:sync-calendars --notify')
                    ->hourly()
                    ->withoutOverlapping()
                    ->appendOutputTo(storage_path('logs/ical-sync.log'));
            });
        }

        $this->app->booted(function (): void {
            $this->app->register(HookServiceProvider::class);

            SeoHelper::registerModule([
                Room::class,
                RoomCategory::class,
                Service::class,
                Place::class,
            ]);

            if (
                defined('SOCIAL_LOGIN_MODULE_SCREEN_NAME') &&
                Route::has('customer.login') &&
                Route::has('public.index')
            ) {
                SocialService::registerModule([
                    'guard' => 'customer',
                    'model' => Customer::class,
                    'login_url' => route('customer.login'),
                    'redirect_url' => route('public.index'),
                ]);
            }

            add_filter('social_login_before_saving_account', function ($data, $oAuth, $providerData) {
                if (Arr::get($providerData, 'model') == Customer::class && Arr::get($providerData, 'guard') == 'customer') {
                    $firstName = implode(' ', explode(' ', $oAuth->getName(), -1));
                    Arr::forget($data, 'name');
                    $data = array_merge($data, [
                        'first_name' => $firstName,
                        'last_name' => trim(str_replace($firstName, '', $oAuth->getName())),
                    ]);
                }

                return $data;
            }, 49, 3);
        });

        FormFrontManager::register(LoginForm::class, LoginRequest::class);
        FormFrontManager::register(RegisterForm::class, RegisterRequest::class);
        FormFrontManager::register(ForgotPasswordForm::class, ForgotPasswordRequest::class);
        FormFrontManager::register(ResetPasswordForm::class, ResetPasswordRequest::class);
    }
}
