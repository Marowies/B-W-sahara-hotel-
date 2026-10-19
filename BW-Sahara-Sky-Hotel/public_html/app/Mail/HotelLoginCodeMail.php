<?php

namespace App\Mail;

use App\Support\HotelMailCopy;

class HotelLoginCodeMail extends HotelAccountMail
{
    public function __construct(private string $code, private int $minutes, ?string $locale = null)
    {
        parent::__construct($locale);
    }

    public function build(): static
    {
        $copy = HotelMailCopy::get($this->locale);
        $replace = [':code' => $this->code, ':minutes' => (string) $this->minutes];
        $data = $this->branding($copy['code_title'], strtr($copy['code_preheader'], $replace)) + ['code' => $this->code, 'minutes' => $this->minutes];

        return $this->subject(strtr($copy['code_subject'], $replace))
            ->view('emails.login-code', $data)
            ->text('emails.login-code-text', $data);
    }
}
