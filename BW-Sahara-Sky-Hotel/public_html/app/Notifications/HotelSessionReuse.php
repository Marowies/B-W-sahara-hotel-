<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class HotelSessionReuse extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public array $backoff = [60, 300, 900, 1800];

    public function __construct(public string $ip, public string $device, public string $occurredAt)
    {
        $this->afterCommit();
        $this->onQueue('security');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): \App\Mail\HotelSecurityAlertMail
    {
        // The hotel's branded template, in the language the customer last used on the site.
        return (new \App\Mail\HotelSecurityAlertMail($this->ip, $this->device, $notifiable->ui_locale ?? null))->to($notifiable->email);
    }
}
