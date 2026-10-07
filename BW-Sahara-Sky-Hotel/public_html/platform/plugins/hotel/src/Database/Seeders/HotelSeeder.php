<?php

namespace Botble\Hotel\Database\Seeders;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\MetaBox;
use Botble\Base\Supports\BaseSeeder;
use Botble\Hotel\Enums\BookingStatusEnum;
use Botble\Hotel\Enums\ServicePriceTypeEnum;
use Botble\Hotel\Facades\InvoiceHelper;
use Botble\Hotel\Models\Amenity;
use Botble\Hotel\Models\Booking;
use Botble\Hotel\Models\BookingAddress;
use Botble\Hotel\Models\BookingRoom;
use Botble\Hotel\Models\Currency;
use Botble\Hotel\Models\Customer;
use Botble\Hotel\Models\Feature;
use Botble\Hotel\Models\Food;
use Botble\Hotel\Models\FoodType;
use Botble\Hotel\Models\Invoice;
use Botble\Hotel\Models\InvoiceItem;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Models\RoomCategory;
use Botble\Hotel\Models\Service;
use Botble\Hotel\Models\Tax;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Slug\Facades\SlugHelper;
use Botble\Slug\Models\Slug;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HotelSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->seedRoomCategories();
        $this->seedAmenities();
        $this->seedFeatures();
        $this->seedTaxes();
        $this->seedCurrencies();
        $this->seedRooms();
        $this->seedFoodTypes();
        $this->seedFoods();
        $this->seedServices();
        $this->seedCustomers();
        $this->seedBookings();
    }

    protected function seedRoomCategories(): void
    {
        RoomCategory::query()->truncate();

        $categories = [
            [
                'name' => 'Luxury',
                'is_featured' => true,
            ],
            [
                'name' => 'Family',
                'is_featured' => true,
            ],
            [
                'name' => 'Business',
                'is_featured' => false,
            ],
            [
                'name' => 'Standard',
                'is_featured' => false,
            ],
        ];

        foreach ($categories as $category) {
            $category['status'] = BaseStatusEnum::PUBLISHED;
            RoomCategory::query()->create($category);
        }
    }

    protected function seedAmenities(): void
    {
        Amenity::query()->truncate();

        $amenities = [
            [
                'name' => 'Air conditioner',
                'icon' => 'fal fa-bath',
            ],
            [
                'name' => 'High speed WiFi',
                'icon' => 'fal fa-wifi',
            ],
            [
                'name' => 'Strong Locker',
                'icon' => 'fal fa-key',
            ],
            [
                'name' => 'Breakfast',
                'icon' => 'fal fa-cut',
            ],
            [
                'name' => 'Kitchen',
                'icon' => 'fal fa-guitar',
            ],
            [
                'name' => 'Smart Security',
                'icon' => 'fal fa-lock',
            ],
            [
                'name' => 'Cleaning',
                'icon' => 'fal fa-broom',
            ],
            [
                'name' => 'Shower',
                'icon' => 'fal fa-shower',
            ],
            [
                'name' => '24/7 Online Support',
                'icon' => 'fal fa-headphones-alt',
            ],
        ];

        $faker = $this->fake();

        foreach ($amenities as $amenity) {
            $amenity['status'] = BaseStatusEnum::PUBLISHED;
            $newAmenity = Amenity::query()->create($amenity);

            MetaBox::saveMetaBoxData($newAmenity, 'icon_image', sprintf('amenities/icon-%s.png', rand(1, 6)));
            MetaBox::saveMetaBoxData($newAmenity, 'description', $faker->text(120));
        }
    }

    protected function seedFeatures(): void
    {
        Feature::query()->truncate();

        $features = [
            [
                'name' => 'Have High Rating',
                'icon' => 'flaticon-rating',
                'is_featured' => true,
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            ],
            [
                'name' => 'Quiet Hours',
                'icon' => 'flaticon-clock',
                'is_featured' => true,
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            ],
            [
                'name' => 'Best Locations',
                'icon' => 'flaticon-location-pin',
                'is_featured' => true,
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            ],
            [
                'name' => 'Free Cancellation',
                'icon' => 'flaticon-clock-1',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            ],
        ];

        foreach ($features as $feature) {
            $feature['status'] = BaseStatusEnum::PUBLISHED;
            Feature::query()->create($feature);
        }
    }

    protected function seedTaxes(): void
    {
        Tax::query()->truncate();

        Tax::query()->create([
            'title' => 'VAT',
            'percentage' => 10,
            'priority' => 1,
            'status' => BaseStatusEnum::PUBLISHED,
        ]);

        Tax::query()->create([
            'title' => 'None',
            'percentage' => 0,
            'priority' => 2,
            'status' => BaseStatusEnum::PUBLISHED,
        ]);
    }

    protected function seedCurrencies(): void
    {
        Currency::query()->truncate();

        $currencies = [
            [
                'title' => 'USD',
                'symbol' => '$',
                'is_prefix_symbol' => true,
                'order' => 0,
                'decimals' => 2,
                'is_default' => 1,
                'exchange_rate' => 1,
            ],
            [
                'title' => 'EUR',
                'symbol' => '€',
                'is_prefix_symbol' => false,
                'order' => 1,
                'decimals' => 2,
                'is_default' => 0,
                'exchange_rate' => 0.91,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::query()->create($currency);
        }
    }

    protected function seedRooms(): void
    {
        Room::query()->truncate();
        DB::table('ht_rooms_amenities')->truncate();

        $rooms = [
            [
                'name' => 'Luxury Hall Of Fame',
                'room_category_id' => 1,
                'images' => json_encode([
                    'rooms/01.jpg',
                    'rooms/02.jpg',
                    'rooms/03.jpg',
                ]),
                'price' => 150,
                'number_of_rooms' => 5,
                'number_of_beds' => 2,
                'max_adults' => 4,
                'max_children' => 2,
                'size' => 150,
                'description' => 'Our spacious room offers a cozy ambiance, modern amenities, and stunning city views.',
            ],
            [
                'name' => 'Family Suite',
                'room_category_id' => 2,
                'images' => json_encode([
                    'rooms/02.jpg',
                    'rooms/03.jpg',
                    'rooms/04.jpg',
                ]),
                'price' => 180,
                'number_of_rooms' => 3,
                'number_of_beds' => 3,
                'max_adults' => 6,
                'max_children' => 3,
                'size' => 200,
                'description' => 'Perfect for families, our suite provides ample space and comfort for everyone.',
            ],
            [
                'name' => 'Business Room',
                'room_category_id' => 3,
                'images' => json_encode([
                    'rooms/03.jpg',
                    'rooms/04.jpg',
                    'rooms/05.jpg',
                ]),
                'price' => 120,
                'number_of_rooms' => 8,
                'number_of_beds' => 1,
                'max_adults' => 2,
                'max_children' => 1,
                'size' => 100,
                'description' => 'Designed for business travelers with a work desk and high-speed internet.',
            ],
        ];

        $amenities = Amenity::query()->pluck('id')->all();
        $faker = $this->fake();

        foreach ($rooms as $room) {
            $room['tax_id'] = 1;
            $room['is_featured'] = rand(0, 1);
            $room['content'] = 'Detailed content about the room and its features.';
            $room['status'] = BaseStatusEnum::PUBLISHED;
            $room = Room::query()->create($room);

            if ($countAmenities = count($amenities)) {
                $amenitiesRandom = [];
                foreach ($faker->randomElements(range(1, $countAmenities), rand(3, $countAmenities)) as $i) {
                    $amenitiesRandom[] = $i;
                }

                $room->amenities()->sync($amenitiesRandom);
            }

            Slug::query()->create([
                'reference_type' => Room::class,
                'reference_id' => $room->id,
                'key' => Str::slug($room->name),
                'prefix' => SlugHelper::getPrefix(Room::class),
            ]);
        }
    }

    protected function seedFoodTypes(): void
    {
        FoodType::query()->truncate();

        $foodTypes = [
            [
                'name' => 'Breakfast',
                'icon' => 'flaticon-coffee',
            ],
            [
                'name' => 'Lunch',
                'icon' => 'flaticon-pizza',
            ],
            [
                'name' => 'Dinner',
                'icon' => 'flaticon-boiled',
            ],
            [
                'name' => 'Dessert',
                'icon' => 'flaticon-cake',
            ],
            [
                'name' => 'Drinks',
                'icon' => 'flaticon-cocktail',
            ],
        ];

        foreach ($foodTypes as $foodType) {
            $foodType['status'] = BaseStatusEnum::PUBLISHED;
            FoodType::query()->create($foodType);
        }
    }

    protected function seedFoods(): void
    {
        Food::query()->truncate();

        $foods = [
            [
                'name' => 'Continental Breakfast',
                'food_type_id' => 1,
                'image' => 'foods/01.jpg',
                'price' => 15,
                'description' => 'A light breakfast that typically consists of pastries, coffee, tea, and fruit.',
            ],
            [
                'name' => 'English Breakfast',
                'food_type_id' => 1,
                'image' => 'foods/02.jpg',
                'price' => 25,
                'description' => 'A hearty breakfast with eggs, bacon, sausages, grilled tomatoes, and toast.',
            ],
            [
                'name' => 'Caesar Salad',
                'food_type_id' => 2,
                'image' => 'foods/03.jpg',
                'price' => 12,
                'description' => 'Fresh romaine lettuce with Caesar dressing, croutons, and parmesan cheese.',
            ],
            [
                'name' => 'Grilled Salmon',
                'food_type_id' => 3,
                'image' => 'foods/04.jpg',
                'price' => 30,
                'description' => 'Freshly grilled salmon served with seasonal vegetables and lemon butter sauce.',
            ],
            [
                'name' => 'Chocolate Cake',
                'food_type_id' => 4,
                'image' => 'foods/05.jpg',
                'price' => 8,
                'description' => 'Rich chocolate cake with a velvety ganache topping.',
            ],
            [
                'name' => 'Signature Cocktail',
                'food_type_id' => 5,
                'image' => 'foods/06.jpg',
                'price' => 12,
                'description' => 'Our signature blend of premium spirits and fresh ingredients.',
            ],
        ];

        $faker = $this->fake();
        $currency = Currency::query()->where('is_default', 1)->first();

        foreach ($foods as $food) {
            $food['currency_id'] = $currency->id;
            $food['status'] = BaseStatusEnum::PUBLISHED;
            Food::query()->create($food);
        }
    }

    protected function seedServices(): void
    {
        Service::query()->truncate();

        $services = [
            [
                'name' => 'Airport Pickup',
                'image' => 'services/01.jpg',
                'price' => 25,
                'price_type' => ServicePriceTypeEnum::ONCE,
                'description' => 'Convenient transportation from the airport to our hotel.',
            ],
            [
                'name' => 'Spa Treatment',
                'image' => 'services/02.jpg',
                'price' => 80,
                'price_type' => ServicePriceTypeEnum::ONCE,
                'description' => 'Relaxing spa treatments to rejuvenate your body and mind.',
            ],
            [
                'name' => 'Laundry Service',
                'image' => 'services/03.jpg',
                'price' => 15,
                'price_type' => ServicePriceTypeEnum::PER_DAY,
                'description' => 'Professional laundry service for your convenience.',
            ],
            [
                'name' => 'Room Cleaning',
                'image' => 'services/04.jpg',
                'price' => 10,
                'price_type' => ServicePriceTypeEnum::PER_DAY,
                'description' => 'Daily room cleaning service to maintain a fresh environment.',
            ],
            [
                'name' => 'Fitness Center Access',
                'image' => 'services/05.jpg',
                'price' => 5,
                'price_type' => ServicePriceTypeEnum::PER_DAY,
                'description' => 'Access to our state-of-the-art fitness center.',
            ],
        ];

        foreach ($services as $service) {
            $service['status'] = BaseStatusEnum::PUBLISHED;
            $service['content'] = 'Detailed information about this service and what it includes.';
            $service = Service::query()->create($service);

            Slug::query()->create([
                'reference_type' => Service::class,
                'reference_id' => $service->id,
                'key' => Str::slug($service->name),
                'prefix' => SlugHelper::getPrefix(Service::class),
            ]);
        }
    }

    protected function seedCustomers(): void
    {
        Customer::query()->truncate();

        $faker = $this->fake();

        $customers = [];

        for ($i = 0; $i < 5; $i++) {
            $customers[] = [
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'email' => $faker->unique()->safeEmail(),
                'phone' => $faker->phoneNumber(),
                'password' => bcrypt('password'),
                'avatar' => null,
                'dob' => $faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
                'address' => $faker->address(),
                'zip' => $faker->postcode(),
                'city' => $faker->city(),
                'state' => $faker->state(),
                'country' => $faker->countryCode(),
            ];
        }

        // Add a test customer with known credentials
        $customers[] = [
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@example.com',
            'phone' => '123-456-7890',
            'password' => bcrypt('password'),
            'avatar' => null,
            'dob' => '1990-01-01',
            'address' => '123 Test Street',
            'zip' => '12345',
            'city' => 'Test City',
            'state' => 'Test State',
            'country' => 'US',
        ];

        foreach ($customers as $customer) {
            Customer::query()->create($customer);
        }
    }

    protected function seedBookings(): void
    {
        Booking::query()->truncate();
        BookingAddress::query()->truncate();
        BookingRoom::query()->truncate();
        DB::table('ht_booking_services')->truncate();
        Payment::query()->truncate();
        Invoice::query()->truncate();
        InvoiceItem::query()->truncate();

        $rooms = Room::query()->get();
        $customers = Customer::query()->get();
        $services = Service::query()->get();
        $faker = $this->fake();

        // Create bookings for the past 30 days and future 30 days
        $period = CarbonPeriod::between(
            Carbon::now()->subDays(30),
            Carbon::now()->addDays(30)
        );

        $bookingNumber = 10001;

        foreach ($period as $date) {
            // Skip some days randomly
            if (rand(0, 2) > 0) {
                continue;
            }

            $customer = $customers->random();
            $room = $rooms->random();

            // Create booking address
            $bookingAddress = new BookingAddress([
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'city' => $customer->city,
                'state' => $customer->state,
                'country' => $customer->country,
                'zip' => $customer->zip,
            ]);

            // Calculate random stay duration (1-7 days)
            $stayDuration = rand(1, 7);
            $startDate = $date->format('Y-m-d');
            $endDate = $date->copy()->addDays($stayDuration)->format('Y-m-d');

            // Create booking room
            $numberOfRooms = rand(1, 3);
            $roomPrice = $room->price;
            $amount = $roomPrice * $numberOfRooms * $stayDuration;

            $bookingRoom = new BookingRoom([
                'room_id' => $room->id,
                'room_name' => $room->name,
                'room_image' => Arr::first($room->images),
                'price' => $roomPrice,
                'number_of_rooms' => $numberOfRooms,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            // Create booking
            $booking = Booking::query()->create([
                'customer_id' => $customer->id,
                'amount' => $amount,
                'sub_total' => $amount,
                'tax_amount' => $amount * 0.1, // 10% tax
                'coupon_amount' => 0,
                'currency_id' => 1, // USD
                'requests' => $faker->optional(0.7)->sentence(),
                'arrival_time' => $faker->randomElement(['12:00 AM', '01:00 PM', '02:00 PM', '03:00 PM']),
                'number_of_guests' => $numberOfRooms * rand(1, 3),
                'number_of_children' => rand(0, 2),
                'transaction_id' => Str::random(10),
                'status' => $faker->randomElement(BookingStatusEnum::values()),
                'booking_number' => 'BK-' . $bookingNumber++,
            ]);

            // Associate booking address with booking
            $bookingAddress->booking_id = $booking->id;
            $bookingAddress->save();

            // Associate booking room with booking
            $bookingRoom->booking_id = $booking->id;
            $bookingRoom->save();

            // Add random services to booking (0-3 services)
            $randomServices = $services->random(rand(0, 3));
            foreach ($randomServices as $service) {
                DB::table('ht_booking_services')->insert([
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                ]);
            }

            $paymentStatus = $booking->status == BookingStatusEnum::COMPLETED
                ? PaymentStatusEnum::COMPLETED
                : $faker->randomElement([
                    PaymentStatusEnum::PENDING,
                    PaymentStatusEnum::COMPLETED,
                    PaymentStatusEnum::FAILED,
                ]);

            $payment = Payment::query()->create([
                'amount' => $booking->amount,
                'currency' => 'USD',
                'user_id' => 0,
                'charge_id' => Str::random(10),
                'payment_channel' => $faker->randomElement(PaymentMethodEnum::values()),
                'payment_type' => 'direct',
                'status' => $paymentStatus,
                'order_id' => $booking->id,
            ]);

            $booking->payment_id = $payment->id;
            $booking->save();

            InvoiceHelper::store($booking);
        }
    }
}
