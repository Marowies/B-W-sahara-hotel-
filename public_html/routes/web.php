<?php

use Botble\Theme\Supports\RobotsTxt;
use Illuminate\Support\Facades\Route;

Route::get('robots.txt', fn () => response(
    RobotsTxt::render(RobotsTxt::content(), route('public.sitemap')),
    200,
    ['Content-Type' => 'text/plain; charset=UTF-8']
))->name('public.robots');

