<?php

namespace Botble\Theme\Supports;

class WebsiteTracking
{
    public static function options(array $config): ?array
    {
        $mode = $config['mode'] ?? 'off';
        $id = trim((string) ($config[$mode === 'gtm' ? 'gtm_container_id' : 'ga4_measurement_id'] ?? ''));
        $pattern = $mode === 'gtm' ? '/^GTM-[A-Z0-9]+$/' : '/^G-[A-Z0-9]+$/';

        if (! in_array($mode, ['ga4', 'gtm'], true) || ! preg_match($pattern, $id)) {
            return null;
        }

        return ['mode' => $mode, 'id' => $id, 'cookie' => (string) ($config['consent_cookie'] ?? 'cookie_for_consent')];
    }

    public static function render(): string
    {
        $options = self::options(config('tracking', []));
        if (! $options) {
            return '';
        }

        // Never emit legacy CMS tracking code alongside the environment-owned loader.
        foreach (['gtm_container_id', 'google_tag_manager_id', 'google_analytics', 'google_tag_manager_code', 'custom_tracking_header_js', 'custom_tracking_body_html'] as $key) {
            if (setting($key)) {
                return '';
            }
        }
        // General CMS HTML/JS can also contain manually pasted Google tags.
        foreach (['custom_header_js', 'custom_body_js', 'custom_footer_js', 'custom_header_html', 'custom_body_html', 'custom_footer_html'] as $key) {
            if (preg_match('/googletagmanager|google-analytics|\\bgtag\\s*\\(|\\bdataLayer\\b/i', (string) setting($key))) {
                return '';
            }
        }

        $trackingScript = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/website-tracking/managed.js');

        return view('packages/theme::partials.website-tracking.managed', compact('options', 'trackingScript'))->render();
    }

    public static function roomEvent(string $event, int|string $roomId, ?string $checkoutAttempt = null): string
    {
        if (! in_array($event, ['view_item', 'begin_checkout'], true) || ! ctype_digit((string) $roomId) || ! self::options(config('tracking', []))) {
            return '';
        }

        $data = ['event' => $event, 'ecommerce' => ['items' => [['item_id' => 'room-' . $roomId, 'item_category' => 'Hotel room']]]];
        if ($event === 'begin_checkout' && $checkoutAttempt) {
            // A local deduplication key only; never send the session token to Google.
            $data['attempt'] = hash('sha256', 'checkout:' . $checkoutAttempt);
        }

        return '<script type="application/json" class="hotel-tracking-event">'
            . json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR)
            . '</script>';
    }
}
