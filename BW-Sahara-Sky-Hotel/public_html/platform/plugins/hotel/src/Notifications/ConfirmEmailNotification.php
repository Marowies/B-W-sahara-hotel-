<?php

namespace Botble\Hotel\Notifications;

use Botble\Base\Facades\EmailHandler;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class ConfirmEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $expirationMinutes = (int) setting('hotel_verification_expire_minutes', 60);

        if (! $expirationMinutes) {
            $expirationMinutes = (int) config('plugins.hotel.general.verification_expire_minutes', 60);
        }

        $emailHandler = EmailHandler::setModule(HOTEL_MODULE_SCREEN_NAME)
            ->setType('plugins')
            ->setTemplate('confirm-email')
            ->addTemplateSettings(HOTEL_MODULE_SCREEN_NAME, config('plugins.hotel.email', []))
            ->setVariableValues([
                'verify_link' => URL::temporarySignedRoute(
                    'customer.confirm',
                    Carbon::now()->addMinutes($expirationMinutes),
                    ['user' => $notifiable->id]
                ),
                'customer_name' => $notifiable->first_name . ' ' . $notifiable->last_name,
            ]);

        return (new MailMessage())
            ->view(['html' => new HtmlString($emailHandler->getContent())])
            ->subject($emailHandler->getSubject());
    }
}
