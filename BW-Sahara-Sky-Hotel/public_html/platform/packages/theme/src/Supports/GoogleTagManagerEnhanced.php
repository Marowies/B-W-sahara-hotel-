<?php

namespace Botble\Theme\Supports;

class GoogleTagManagerEnhanced
{
    public static function renderGoogleTagManagerScript(): string
    {
        return WebsiteTracking::render();
    }

    public static function renderGoogleTagManagerNoscript(): string
    {
        // A noscript iframe would contact Google without the JavaScript consent gate.
        return '';
    }
}
