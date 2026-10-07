<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingEngineSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $url = 'https://be.aiosell.com/book/acc8e772e0';

        $settings = [
            // External booking URL — used by all blade templates
            'theme-riorelax-external_booking_url'       => $url,
            'theme-riorelax-ar-external_booking_url'    => $url,
            'theme-riorelax-zh_CN-external_booking_url' => $url,

            // Header "Book Now" button
            'theme-riorelax-header_button_url'          => $url,
            'theme-riorelax-ar-header_button_url'       => $url,
            'theme-riorelax-zh_CN-header_button_url'    => $url,
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key'   => $key],
                ['value' => $value]
            );
        }

        $this->command->info('✅ Booking engine settings seeded successfully.');
    }
}
