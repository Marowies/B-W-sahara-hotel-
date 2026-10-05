<?php

use Illuminate\Support\Facades\Route;
use Botble\Base\Facades\AdminHelper;

Route::group(['namespace' => 'Botble\BotbleNulled\Http\Controllers'], function () {
    AdminHelper::registerRoutes(function () {
        Route::group(['prefix' => 'botble_nulleds', 'as' => 'botble_nulled.'], function () {
            Route::resource('', 'BotbleNulledController')->parameters(['' => 'botble_nulled']);
        });
    });
});

// License operations must not be exposed through unauthenticated public routes.
