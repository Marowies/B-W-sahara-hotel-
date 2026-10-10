<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())->subject('Security alert — B&W Sahara Sky')
            ->line('An old sign-in token was reused. We ended the affected session. Other sign-in sessions were not changed.')
            ->line('Time UTC: ' . $this->occurredAt)
            ->line('IP: ' . $this->ip)->line('Device: ' . $this->device)
            ->line('Sign in again with an email code. If this was not you, contact the hotel.');
    }
}
