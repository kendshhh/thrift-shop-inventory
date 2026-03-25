<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Notifications\ReservationStatusUpdatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReservationManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Reservation::query()->with(['user', 'reservationItems.item'])->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', (string) $request->input('payment_status'));
        }

        return view('admin.reservations.index', [
            'reservations' => $query->paginate(15)->withQueryString(),
            'statuses' => ReservationStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'filters' => $request->only(['status', 'payment_status']),
        ]);
    }

    public function show(Reservation $reservation): View
    {
        return view('admin.reservations.show', [
            'reservation' => $reservation->load(['user', 'reservationItems.item']),
            'statuses' => ReservationStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
        ]);
    }

    public function updateStatus(Request $request, Reservation $reservation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(ReservationStatus::values())],
            'payment_status' => ['required', Rule::in(PaymentStatus::values())],
            'notes' => ['nullable', 'string'],
        ]);

        $previousStatusLabel = $reservation->status->label();
        $previousPaymentStatusLabel = $reservation->payment_status->label();

        $newStatus = ReservationStatus::from($validated['status']);

        if (!$reservation->status->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => 'Invalid status transition from '.$reservation->status->label().' to '.$newStatus->label().'.',
            ]);
        }

        $wasExpired = $reservation->status === ReservationStatus::EXPIRED;

        $reservation->status = $newStatus;
        $reservation->payment_status = PaymentStatus::from($validated['payment_status']);
        $reservation->notes = $validated['notes'] ?? $reservation->notes;

        if ($reservation->payment_status === PaymentStatus::COMPLETED && $reservation->paid_at === null) {
            $reservation->paid_at = now();
        }

        if ($newStatus === ReservationStatus::COMPLETED && $reservation->completed_at === null) {
            $reservation->completed_at = now();
        }

        $reservation->save();

        if (!$wasExpired && $newStatus === ReservationStatus::EXPIRED) {
            $this->releaseReservedItems($reservation);
        }

        $reservation->loadMissing('user');

        if ($reservation->user !== null) {
            $reservation->user->notify(new ReservationStatusUpdatedNotification(
                $reservation,
                $previousStatusLabel,
                $previousPaymentStatusLabel
            ));
        }

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('status', 'Reservation status updated.');
    }

    public function updateCustomerRequest(Request $request, Reservation $reservation): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'decline'])],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($reservation->customer_request_status !== 'pending' || $reservation->customer_request_type === null) {
            return back()->withErrors([
                'customer_request' => 'There is no pending customer request to process.',
            ]);
        }

        if (
            $validated['action'] === 'approve'
            && $reservation->customer_request_type === 'reschedule'
            && ($reservation->customer_requested_pickup_date === null || $reservation->customer_requested_pickup_slot === null)
        ) {
            return back()->withErrors([
                'customer_request' => 'This reschedule request is missing the requested pickup details.',
            ]);
        }

        if (
            $validated['action'] === 'approve'
            && $reservation->customer_request_type === 'reschedule'
            && !in_array($reservation->status, [ReservationStatus::PENDING, ReservationStatus::OVERDUE], true)
        ) {
            return back()->withErrors([
                'customer_request' => 'This reservation can no longer be rescheduled from its current status.',
            ]);
        }

        if (
            $validated['action'] === 'approve'
            && $reservation->customer_request_type === 'cancellation'
            && !in_array($reservation->status, [ReservationStatus::PENDING, ReservationStatus::OVERDUE], true)
        ) {
            return back()->withErrors([
                'customer_request' => 'This reservation can no longer be cancelled automatically from its current status.',
            ]);
        }

        DB::transaction(function () use ($validated, $reservation): void {
            $shouldReleaseItems = false;

            if ($validated['action'] === 'approve') {
                if ($reservation->customer_request_type === 'reschedule') {
                    $reservation->pickup_date = $reservation->customer_requested_pickup_date;
                    $reservation->pickup_slot = $reservation->customer_requested_pickup_slot;
                }

                if ($reservation->customer_request_type === 'cancellation' && $reservation->status !== ReservationStatus::EXPIRED) {
                    $reservation->status = ReservationStatus::EXPIRED;
                    $shouldReleaseItems = true;
                }

                $reservation->customer_request_status = 'approved';
            } else {
                $reservation->customer_request_status = 'declined';
            }

            $reservation->customer_request_admin_note = $validated['admin_note'] !== null ? trim($validated['admin_note']) : null;
            $reservation->customer_request_handled_at = now();
            $reservation->save();

            if ($shouldReleaseItems) {
                $this->releaseReservedItems($reservation);
            }
        });

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('status', 'Customer request has been '.$reservation->customer_request_status.'.');
    }

    private function releaseReservedItems(Reservation $reservation): void
    {
        $reservation->loadMissing('reservationItems.item');

        foreach ($reservation->reservationItems as $lineItem) {
            if ($lineItem->item !== null) {
                $lineItem->item->decrement('reserved_quantity', min($lineItem->item->reserved_quantity, $lineItem->quantity));
            }
        }
    }
}
