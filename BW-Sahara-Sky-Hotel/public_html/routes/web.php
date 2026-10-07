<?php

use App\Http\Controllers\HotelCustomerAuthController;
use App\Http\Controllers\HotelFrontendController;
use App\Http\Middleware\RequireHotelCustomer;
use Illuminate\Support\Facades\Route;

Route::prefix('api/hotel')->middleware('throttle:60,1')->group(function (): void {
    Route::get('rooms', [HotelFrontendController::class, 'rooms']);
    Route::get('availability', [HotelFrontendController::class, 'availability']);
    Route::get('session', [HotelFrontendController::class, 'session'])->middleware(RequireHotelCustomer::class . ':optional');
    Route::post('locale', [HotelFrontendController::class, 'locale'])->middleware(['throttle:10,1', RequireHotelCustomer::class . ':optional']);
    Route::post('contact', [HotelFrontendController::class, 'contact'])->middleware('throttle:5,1');
    Route::post('auth/otp/request', [HotelCustomerAuthController::class, 'requestCode'])->middleware('throttle:5,1,otp-request');
    Route::post('auth/otp/verify', [HotelCustomerAuthController::class, 'verifyCode'])->middleware('throttle:10,1,otp-verify');
    Route::post('auth/refresh', [HotelCustomerAuthController::class, 'refresh'])->middleware('throttle:30,1,otp-refresh');
    Route::post('auth/logout', [HotelCustomerAuthController::class, 'logout']);
    Route::get('checkout/eligibility', [HotelCustomerAuthController::class, 'checkoutEligibility'])->middleware(RequireHotelCustomer::class);
});
