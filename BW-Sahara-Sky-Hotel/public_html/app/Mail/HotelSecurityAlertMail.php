<?php

namespace App\Mail;

use App\Support\HotelMailCopy;

class HotelSecurityAlertMail extends HotelAccountMail
{
    public function __construct(private string $ip, private string $device, ?string $locale = null)
    {
        parent::__construct($locale);
    }

    public function build(): static
    {
        $copy = HotelMailCopy::get($this->locale);
        $data = $this->branding($copy['alert_title'], $copy['alert_preheader'])
            + ['ip' => $this->ip, 'device' => mb_substr($this->device, 0, 160), 'time' => now()->utc()->format('Y-m-d H:i')];

        return $this->subject($copy['alert_subject'])
            ->view('emails.security-alert', $data)
            ->text('emails.security-alert-text', $data);
    }
}
