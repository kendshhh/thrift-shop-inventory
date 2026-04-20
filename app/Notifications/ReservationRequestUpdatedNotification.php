<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReservationRequestUpdatedNotification extends Notification
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
        $requestType = ucfirst((string) ($this->reservation->customer_request_type ?? 'reservation'));
        $requestStatus = (string) ($this->reservation->customer_request_status ?? 'updated');
        $pickupDate = optional($this->reservation->pickup_date)->format('M d, Y') ?? 'N/A';
        $pickupSlot = $this->reservation->pickup_slot !== null
            ? ucfirst(str_replace('_', ' ', (string) $this->reservation->pickup_slot))
            : 'N/A';

        return [
            'title' => $requestType.' request '.ucfirst($requestStatus),
            'message' => $this->message($requestType, $requestStatus),
            'reservation_id' => $this->reservation->id,
            'reference' => $this->reservation->reference,
            'status' => $this->reservation->status->value,
            'status_label' => $this->reservation->status->label(),
            'payment_status' => $this->reservation->payment_status->value,
            'payment_status_label' => $this->reservation->payment_status->label(),
            'pickup_date' => $pickupDate,
            'pickup_slot' => $pickupSlot,
            'notes' => $this->reservation->customer_request_admin_note,
            'request_type' => $this->reservation->customer_request_type,
            'request_status' => $requestStatus,
            'action_url' => route('customer.reservations.show', $this->reservation),
            'action_label' => 'View Reservation',
        ];
    }

    private function message(string $requestType, string $requestStatus): string
    {
        if ($requestStatus === 'approved' && $this->reservation->customer_request_type === 'reschedule') {
            $pickupDate = optional($this->reservation->pickup_date)->format('M d, Y') ?? 'N/A';
            $pickupSlot = $this->reservation->pickup_slot !== null
                ? ucfirst(str_replace('_', ' ', (string) $this->reservation->pickup_slot))
                : 'N/A';

            return 'Your '.$requestType.' request for '.$this->reservation->reference.' was approved. Your new pickup schedule is '.$pickupDate.' ('.$pickupSlot.').';
        }

        if ($requestStatus === 'approved' && $this->reservation->customer_request_type === 'cancellation') {
            return 'Your '.$requestType.' request for '.$this->reservation->reference.' was approved. The reservation has been closed.';
        }

        return 'Your '.$requestType.' request for '.$this->reservation->reference.' was '.$requestStatus.'.';
    }
}