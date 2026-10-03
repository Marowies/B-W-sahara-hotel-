<?php

namespace Botble\Hotel\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ICalSyncFailureEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(protected array $data)
    {
    }

    public function build(): self
    {
        return $this
            ->subject(trans('plugins/hotel::ical.email.subject'))
            ->view('plugins/hotel::emails.ical-sync-failure', ['data' => $this->data]);
    }
}
