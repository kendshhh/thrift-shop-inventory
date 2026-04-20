<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminReservationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Reservation $reservation,
        private readonly string $eventType,
        private readonly ?string $actorName = null
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

        return [
            'event_type' => $this->eventType,
            'title' => $this->title(),
            'message' => $this->message(),
            'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference,
            'customer_name' => $this->reservation->user?->name ?? 'Customer',
            'status' => $this->reservation->status->value,
            'status_label' => $this->reservation->status->label(),
            'payment_status' => $this->reservation->payment_status->value,
            'payment_status_label' => $this->reservation->payment_status->label(),
            'pickup_date' => $pickupDate,
            'pickup_slot' => $pickupSlot,
            'customer_request_type' => $this->reservation->customer_request_type,
            'customer_request_status' => $this->reservation->customer_request_status,
            'notes' => $this->reservation->notes,
            'action_url' => route('admin.reservations.show', $this->reservation),
            'action_label' => 'Review Reservation',
        ];
    }

    private function title(): string
    {
        return match ($this->eventType) {
            'new_reservation' => 'New reservation received',
            'customer_request_submitted' => 'Customer request submitted',
            'status_changed' => 'Reservation updated',
            default => 'Reservation alert',
        };
    }

    private function message(): string
    {
        return match ($this->eventType) {
            'new_reservation' => ($this->reservation->user?->name ?? 'A customer').' placed reservation '.$this->reservation->reference.'.',
            'customer_request_submitted' => ($this->reservation->user?->name ?? 'A customer').' submitted a '.($this->reservation->customer_request_type ?? 'reservation').' request for '.$this->reservation->reference.'.',
            'status_changed' => trim(($this->actorName !== null ? $this->actorName.' updated ' : '').'reservation '.$this->reservation->reference.' to '.$this->reservation->status->label().'.'),
            default => 'Reservation '.$this->reservation->reference.' has a new admin update.',
        };
    }
}