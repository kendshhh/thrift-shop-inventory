<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\AdminReservationNotification;
use App\Notifications\ReservationActivityNotification;
use App\Notifications\ReservationRequestUpdatedNotification;
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
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(ReservationStatus::values())],
            'payment_status' => ['nullable', Rule::in(PaymentStatus::values())],
            'sort' => ['nullable', Rule::in(['latest', 'pickup_asc', 'pickup_desc', 'amount_desc', 'amount_asc'])],
        ]);

        $query = Reservation::query()->with(['user', 'reservationItems.item']);

        if (!empty($validated['search'])) {
            $search = trim((string) $validated['search']);
            $query->where(function ($builder) use ($search): void {
                $builder->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('reservationItems.item', function ($itemQuery) use ($search): void {
                        $itemQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($validated['status'])) {
            $query->where('status', (string) $validated['status']);
        }

        if (!empty($validated['payment_status'])) {
            $query->where('payment_status', (string) $validated['payment_status']);
        }

        match ($validated['sort'] ?? 'latest') {
            'pickup_asc' => $query->orderBy('pickup_date')->orderBy('created_at'),
            'pickup_desc' => $query->orderByDesc('pickup_date')->orderByDesc('created_at'),
            'amount_desc' => $query->orderByDesc('total_amount'),
            'amount_asc' => $query->orderBy('total_amount'),
            default => $query->latest(),
        };

        $newReservationNotifications = $request->user()
            ->unreadNotifications()
            ->where('type', AdminReservationNotification::class)
            ->get()
            ->filter(static fn ($notification) => ($notification->data['event_type'] ?? null) === 'new_reservation');

        $newReservationIds = $newReservationNotifications
            ->pluck('data.reservation_id')
            ->filter()
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return view('admin.reservations.index', [
            'reservations' => $query->paginate(15)->withQueryString(),
            'statuses' => ReservationStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'filters' => $request->only(['search', 'status', 'payment_status', 'sort']),
            'newReservationIds' => $newReservationIds,
        ]);
    }

    public function show(Request $request, Reservation $reservation): View
    {
        $unreadNewReservationNotifications = $request->user()
            ->unreadNotifications()
            ->where('type', AdminReservationNotification::class)
            ->where('data->event_type', 'new_reservation')
            ->where('data->reservation_id', $reservation->id)
            ->get();

        $wasNewReservation = $unreadNewReservationNotifications->isNotEmpty();

        if ($wasNewReservation) {
            $unreadNewReservationNotifications->each->markAsRead();
        }

        return view('admin.reservations.show', [
            'reservation' => $reservation->load(['user', 'reservationItems.item']),
            'statuses' => ReservationStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'wasNewReservation' => $wasNewReservation,
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
        $newPaymentStatus = PaymentStatus::from($validated['payment_status']);

        if (!$reservation->status->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => 'Invalid status transition from '.$reservation->status->label().' to '.$newStatus->label().'.',
            ])->withInput();
        }

        if ($newStatus === ReservationStatus::READY_FOR_PICKUP && $newPaymentStatus !== PaymentStatus::PENDING) {
            return back()->withErrors([
                'payment_status' => 'Ready for pickup reservations must stay pending until payment is collected in person.',
            ])->withInput();
        }

        if ($newPaymentStatus === PaymentStatus::COMPLETED && $newStatus !== ReservationStatus::COMPLETED) {
            return back()->withErrors([
                'payment_status' => 'Payment can only be marked as completed when the reservation is also completed.',
            ])->withInput();
        }

        if ($newStatus === ReservationStatus::COMPLETED && $newPaymentStatus !== PaymentStatus::COMPLETED) {
            return back()->withErrors([
                'payment_status' => 'Completed reservations must have payment marked as completed.',
            ])->withInput();
        }

        $wasExpired = $reservation->status === ReservationStatus::EXPIRED;

        $reservation->status = $newStatus;
        $reservation->payment_status = $newPaymentStatus;
        $reservation->notes = $validated['notes'] ?? $reservation->notes;

        if ($reservation->payment_status === PaymentStatus::COMPLETED && $reservation->paid_at === null) {
            $reservation->paid_at = now();
        } elseif ($reservation->payment_status !== PaymentStatus::COMPLETED) {
            $reservation->paid_at = null;
        }


        $completedNow = false;
        if ($newStatus === ReservationStatus::COMPLETED && $reservation->completed_at === null) {
            $reservation->completed_at = now();
            $completedNow = true;
        }

        $reservation->save();

        // Release reserved items if just completed or expired
        if ((!$wasExpired && $newStatus === ReservationStatus::EXPIRED) || $completedNow) {
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

        $this->notifyOtherAdmins(
            $reservation,
            'status_changed',
            $request->user()?->name,
            $request->user()?->id
        );

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
            && !$reservation->canCustomerRequestCancellation()
        ) {
            return back()->withErrors([
                'customer_request' => 'This reservation can no longer be cancelled from its current status.',
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

        $reservation->refresh();
        $reservation->loadMissing('user');

        if ($reservation->user !== null) {
            $reservation->user->notifications()
                ->where('type', ReservationActivityNotification::class)
                ->where('data->reservation_id', $reservation->id)
                ->where('data->request_status', 'pending')
                ->update([
                    'read_at' => now(),
                    'created_at' => now()->subMinute(),
                    'updated_at' => now(),
                ]);

            $reservation->user->notify(new ReservationRequestUpdatedNotification($reservation));
        }

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

    private function notifyOtherAdmins(Reservation $reservation, string $eventType, ?string $actorName = null, ?int $ignoreUserId = null): void
    {
        User::role('admin')
            ->when($ignoreUserId !== null, static function ($query) use ($ignoreUserId) {
                $query->whereKeyNot($ignoreUserId);
            })
            ->get()
            ->each(static function (User $admin) use ($reservation, $eventType, $actorName): void {
                $admin->notify(new AdminReservationNotification($reservation, $eventType, $actorName));
            });
    }
}
