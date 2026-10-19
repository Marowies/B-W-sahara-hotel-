<?php

return [
    // Active only when the frontend build (public/route-map.json) is deployed; set false to show the CMS theme again.
    'enabled' => env('FRONTEND_GATE_ENABLED', true),
];
