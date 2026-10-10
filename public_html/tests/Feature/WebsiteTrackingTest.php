<?php

namespace Tests\Feature;

use Botble\Setting\Facades\Setting;
use Botble\Theme\Supports\GoogleTagManagerEnhanced;
use Botble\Theme\Supports\WebsiteTracking;
use Tests\TestCase;

class WebsiteTrackingTest extends TestCase
{
    private function configureTracking(array $settings = []): void
    {
        config(['tracking.mode' => 'gtm', 'tracking.gtm_container_id' => 'GTM-UNIT']);
        Setting::swap(new class($settings) {
            public function __construct(private array $values) {}
            public function get($key, $default = null) { return $this->values[$key] ?? $default; }
        });
    }

    public function test_the_existing_renderer_uses_the_managed_loader_without_a_noscript_bypass(): void
    {
        $this->configureTracking();
        $html = GoogleTagManagerEnhanced::renderGoogleTagManagerScript();
        self::assertStringContainsString('hotel-tracking-config', $html);
        self::assertStringContainsString('analytics_storage', $html);
        self::assertStringNotContainsString('<script async', $html);
        self::assertSame('', GoogleTagManagerEnhanced::renderGoogleTagManagerNoscript());
    }

    public function test_old_cms_ids_custom_tracking_and_pasted_google_code_block_a_second_loader(): void
    {
        foreach (['gtm_container_id' => 'GTM-OLD', 'google_analytics' => 'G-OLD', 'custom_tracking_header_js' => '<script></script>', 'custom_footer_html' => '<script src="https://www.googletagmanager.com/gtm.js"></script>'] as $key => $value) {
            $this->configureTracking([$key => $value]);
            self::assertSame('', WebsiteTracking::render());
        }
    }

    public function test_event_markers_allow_only_room_funnel_events_and_hide_the_checkout_token(): void
    {
        $this->configureTracking();
        $marker = WebsiteTracking::roomEvent('begin_checkout', 7, 'private-booking-token');
        self::assertStringNotContainsString('private-booking-token', $marker);
        self::assertStringContainsString(hash('sha256', 'checkout:private-booking-token'), $marker);
        self::assertStringContainsString('room-7', $marker);
        self::assertSame('', WebsiteTracking::roomEvent('purchase', 7));
        self::assertSame('', WebsiteTracking::roomEvent('view_item', '</script>'));
        config(['tracking.mode' => 'off']);
        self::assertSame('', WebsiteTracking::roomEvent('view_item', 7));
        self::assertSame('', WebsiteTracking::render());
    }
}
