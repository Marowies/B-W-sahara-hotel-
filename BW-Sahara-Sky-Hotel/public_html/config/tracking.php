<?php

return [
    // One owner: off, ga4 (direct), or gtm (GA4 must be configured inside GTM).
    'mode' => env('TRACKING_MODE', 'off'),
    'ga4_measurement_id' => env('GA4_MEASUREMENT_ID', ''),
    'gtm_container_id' => env('GTM_CONTAINER_ID', ''),
    'consent_cookie' => env('TRACKING_CONSENT_COOKIE', 'cookie_for_consent'),
];
