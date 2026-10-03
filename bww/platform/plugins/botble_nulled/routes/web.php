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

// routes for post licences requests
Route::group(['namespace' => 'Botble\BotbleNulled\Http\Controllers'], function () {

    //http://localhost/api/activate_license for post_activate_license
    Route::post('api/activate_license', 'BotbleNulledController@post_activate_license')->name('post_activate_license');

    //http://localhost/api/verify_license for post_verify_license
    Route::post('api/verify_license', 'BotbleNulledController@post_verify_license')->name('post_verify_license');

    //http://localhost/api/deactivate_license for post_deactivate_license
    Route::post('api/deactivate_license', 'BotbleNulledController@post_deactivate_license')->name('post_deactivate_license');

    //http://localhost/api/check_license for post_check_license 
    Route::post('api/check_license', 'BotbleNulledController@post_check_license')->name('post_check_license');

    //http://localhost/api/check_connection_ext for check_connection_ext
    Route::post('api/check_connection_ext', 'BotbleNulledController@check_connection_ext')->name('check_connection_ext');

});