<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
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
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $pickupDate = optional($this->reservation->pickup_date)->format('M d, Y') ?? 'N/A';
        $pickupSlot = $this->reservation->pickup_slot !== null
            ? ucfirst(str_replace('_', ' ', (string) $this->reservation->pickup_slot))
            : 'N/A';

        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference,
            'previous_status' => $this->previousStatusLabel,
            'status_label' => $this->reservation->status->label(),
            'status' => $this->reservation->status->value,
            'previous_payment_status' => $this->previousPaymentStatusLabel,
            'payment_status_label' => $this->reservation->payment_status->label(),
            'payment_status' => $this->reservation->payment_status->value,
            'pickup_date' => $pickupDate,
            'pickup_slot' => $pickupSlot,
            'notes' => $this->reservation->notes,
            'is_ready_for_pickup' => $this->reservation->isReadyForPickup(),
            'action_url' => route('customer.reservations.show', $this->reservation),
            'action_label' => 'View Reservation',
        ];
    }

    private function title(): string
    {
        if ($this->reservation->isReadyForPickup()) {
            return 'Reservation ready for pickup';
        }

        return 'Reservation updated';
    }

    private function message(): string
    {
        if ($this->reservation->isReadyForPickup()) {
            return 'Your reservation '.$this->reservation->reference.' is ready for pickup. Please visit the shop and settle payment during your scheduled pickup.';
        }

        return 'Your reservation '.$this->reservation->reference.' was updated from '.$this->previousStatusLabel.' to '.$this->reservation->status->label().'.';
    }
}
