<?php

namespace App\Mail;

use App\Support\HotelMailCopy;
use Illuminate\Mail\Mailable;

/** Shared branding for the hotel's account emails: header, language, direction, footer. */
abstract class HotelAccountMail extends Mailable
{
    public function __construct(?string $locale = null)
    {
        $this->locale = HotelMailCopy::locale($locale);
    }

    /** @return array<string,mixed> */
    protected function branding(string $title, string $preheader): array
    {
        $site = rtrim((string) config('app.url'), '/') ?: 'https://staging.bwsaharaskyhotel.com';

        return [
            'locale' => $this->locale, 'dir' => HotelMailCopy::dir($this->locale), 'copy' => HotelMailCopy::get($this->locale),
            'title' => $title, 'preheader' => $preheader, 'siteUrl' => $site,
            'logo' => $site . '/themes/riorelax/images/email-logo.png',
        ];
    }
}
