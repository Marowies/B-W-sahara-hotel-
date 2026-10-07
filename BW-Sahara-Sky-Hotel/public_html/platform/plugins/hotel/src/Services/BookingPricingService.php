<?php

namespace Botble\Hotel\Services;

use Botble\Hotel\Enums\ServicePriceTypeEnum;
use Botble\Hotel\Facades\HotelHelper;
use Botble\Hotel\Models\Food;
use Botble\Hotel\Models\Room;
use Botble\Hotel\Models\Service;
use Illuminate\Support\Arr;

class BookingPricingService
{
    public function calculate(Room $room, array $servicesIds = [], $nights = 1, int $numberOfRooms = 1, array $foods = []): array
    {
        $amount = $room->total_price;

        $serviceAmount = 0;
        $selectedServices = [];

        if ($servicesIds) {
            $services = Service::query()
                ->wherePublished()
                ->whereIn('id', $servicesIds)
                ->get();

            foreach ($services as $service) {
                if ($service->price_type == ServicePriceTypeEnum::PER_DAY) {
                    $serviceAmount += $service->price * $nights;
                } else {
                    $serviceAmount += $service->price;
                }
            }

            $serviceAmount *= $numberOfRooms;

            $amount += $serviceAmount;

            $selectedServices = $services->pluck('id')->values()->all();
        }

        $foodAmount = 0;
        $foodsSelected = [];

        if ($foods) {
            $foods = Food::query()
                ->wherePublished()
                ->whereIn('id', $foods)
                ->get();

            foreach ($foods as $food) {
                $foodAmount += $food->price;
            }

            $amount += $foodAmount;

            $foodsSelected = $foods->pluck('id')->values()->all();
        }

        $sessionData = HotelHelper::getCheckoutData();

        $sessionData['service_amount'] = $serviceAmount;
        $sessionData['selected_services'] = $selectedServices;

        $sessionData['food_amount'] = $foodAmount;
        $sessionData['selected_foods'] = $foodsSelected;

        $couponCode = Arr::get($sessionData, 'coupon_code');

        $discountAmount = 0;

        if ($couponCode) {
            $couponService = new CouponService();

            $coupon = $couponService->getCouponByCode($couponCode);

            if ($coupon !== null) {
                $discountAmount = $couponService->getDiscountAmount(
                    $coupon->type->getValue(),
                    $coupon->value,
                    $amount
                );
            }

            if ($coupon === null) {
                $couponCode = null;
            }
        }

        $discountAmount = min(max(0, $discountAmount), max(0, $amount));
        $sessionData['coupon_amount'] = $discountAmount;
        $sessionData['coupon_code'] = $couponCode;

        HotelHelper::saveCheckoutData($sessionData);

        return [
            $amount,
            $discountAmount,
        ];
    }
}
