<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReservationActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Reservation $reservation,
        private readonly string $title,
        private readonly string $message,
        private readonly array $extra = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $pickupDate = optional($this->reservation->pickup_date)->format('M d, Y') ?? 'N/A';
        $pickupSlot = $this->reservation->pickup_slot !== null
            ? ucfirst(str_replace('_', ' ', (string) $this->reservation->pickup_slot))
            : 'N/A';

        return array_merge([
            'title' => $this->title,
            'message' => $this->message,
            'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference,
            'status' => $this->reservation->status->value,
            'status_label' => $this->reservation->status->label(),
            'payment_status' => $this->reservation->payment_status->value,
            'payment_status_label' => $this->reservation->payment_status->label(),
            'pickup_date' => $pickupDate,
            'pickup_slot' => $pickupSlot,
            'notes' => $this->reservation->notes,
            'action_url' => route('customer.reservations.show', $this->reservation),
            'action_label' => 'View Reservation',
        ], $this->extra);
    }
}