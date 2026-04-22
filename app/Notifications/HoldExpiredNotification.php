<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class HoldExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct($itemName)
    {
        $this->itemName = $itemName;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The hold on the item "' . $this->itemName . '" has expired.')
                    ->action('Browse Items', url('/'))
                    ->line('Thank you for using our platform!');
    }
}