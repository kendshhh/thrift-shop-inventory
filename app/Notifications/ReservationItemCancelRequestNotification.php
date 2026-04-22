<?php

namespace App\Notifications;

use App\Models\Reservation;
use App\Models\ReservationItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReservationItemCancelRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Reservation $reservation,
        private readonly ReservationItem $reservationItem,
        private readonly string $action // 'approved' or 'declined'
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

        $itemName = $this->reservationItem->item?->name ?? 'Archived Item';
        $actionLabel = $this->action === 'approved' ? 'approved' : 'declined';

        return [
            'title' => "Item Cancellation $actionLabel",
            'message' => "Your cancellation request for item '$itemName' in reservation {$this->reservation->reference} was $actionLabel by the admin.",
            'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference,
            'item_name' => $itemName,
            'action' => $actionLabel,
            'status_label' => $this->reservation->status->label(),
            'status' => $this->reservation->status->value,
            'payment_status_label' => $this->reservation->payment_status->label(),
            'payment_status' => $this->reservation->payment_status->value,
            'pickup_date' => $pickupDate,
            'pickup_slot' => $pickupSlot,
            'notes' => $this->reservation->notes,
            'action_url' => route('customer.reservations.show', $this->reservation),
            'action_label' => 'View Reservation',
        ];
    }
}
