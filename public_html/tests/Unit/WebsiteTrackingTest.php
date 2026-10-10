<?php

namespace Tests\Unit;

use Botble\Theme\Supports\WebsiteTracking;
use PHPUnit\Framework\TestCase;

class WebsiteTrackingTest extends TestCase
{
    public function test_empty_or_invalid_configuration_cannot_enable_tracking(): void
    {
        foreach ([[], ['mode' => 'off'], ['mode' => 'gtm'], ['mode' => 'ga4'], ['mode' => 'custom', 'ga4_measurement_id' => 'G-ABC'], ['mode' => 'ga4', 'ga4_measurement_id' => "G-ABC'</script>"]] as $config) {
            self::assertNull(WebsiteTracking::options($config));
        }
    }

    public function test_only_the_selected_owner_is_used(): void
    {
        // Synthetic values are test inputs only and are never sent to Google.
        $config = ['mode' => 'gtm', 'gtm_container_id' => 'GTM-TEST', 'ga4_measurement_id' => 'G-TEST'];
        self::assertSame('GTM-TEST', WebsiteTracking::options($config)['id']);
        $config['mode'] = 'ga4';
        self::assertSame('G-TEST', WebsiteTracking::options($config)['id']);
    }
}
