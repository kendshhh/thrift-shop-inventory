<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationCreatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly Reservation $reservation)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $pickupDate = optional($this->reservation->pickup_date)->format('M d, Y') ?? 'N/A';
        $expiresAt = optional($this->reservation->expires_at)->format('M d, Y h:i A') ?? 'N/A';

        return (new MailMessage)
            ->subject('Reservation Confirmed: '.$this->reservation->reference)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your reservation request has been successfully submitted.')
            ->line('Reference: '.$this->reservation->reference)
            ->line('Pickup: '.$pickupDate.' ('.$this->reservation->pickup_slot.')')
            ->line('Reservation Expires: '.$expiresAt)
            ->line('Total Reserved Amount: P'.number_format((float) $this->reservation->total_amount, 2))
            ->line('Please complete payment in person at the thrift shop before the reservation deadline.')
            ->action('View Reservation', route('customer.reservations.show', $this->reservation))
            ->line('Thank you for supporting sustainable shopping!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference,
            'status' => $this->reservation->status->value,
            'payment_status' => $this->reservation->payment_status->value,
        ];
    }
}
