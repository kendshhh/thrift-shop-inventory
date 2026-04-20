<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OverdueReservationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Reservation $reservation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event_type' => 'overdue_reservation',
            'title' => 'Overdue Reservation',
            'message' => 'Reservation ' . $this->reservation->reference . ' is overdue and needs attention.',
            'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference,
            'customer_name' => $this->reservation->user?->name ?? 'Customer',
            'status' => $this->reservation->status->value,
            'status_label' => $this->reservation->status->label(),
            'action_url' => route('admin.reservations.show', $this->reservation),
            'action_label' => 'Review Reservation',
        ];
    }
}
