<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationStatusUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly Reservation $reservation,
        private readonly string $previousStatusLabel,
        private readonly string $previousPaymentStatusLabel
    ) {}

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

        $mail = (new MailMessage)
            ->subject('Reservation Updated: '.$this->reservation->reference)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('An administrator has updated your reservation.')
            ->line('Reference: '.$this->reservation->reference)
            ->line('Reservation Status: '.$this->previousStatusLabel.' -> '.$this->reservation->status->label())
            ->line('Payment Status: '.$this->previousPaymentStatusLabel.' -> '.$this->reservation->payment_status->label())
            ->line('Pickup Date: '.$pickupDate.' ('.$this->reservation->pickup_slot.')')
            ->action('View Reservation', route('customer.reservations.show', $this->reservation));

        if (!empty($this->reservation->notes)) {
            $mail->line('Admin Notes: '.$this->reservation->notes);
        } else {
            $mail->line('Thank you for using our application.');
        }

        return $mail;
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
            'previous_status' => $this->previousStatusLabel,
            'status' => $this->reservation->status->value,
            'previous_payment_status' => $this->previousPaymentStatusLabel,
            'payment_status' => $this->reservation->payment_status->value,
        ];
    }
}
