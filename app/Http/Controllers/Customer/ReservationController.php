<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\PickupSlot;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RequiresConfirmedAction;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\User;
use App\Notifications\AdminReservationNotification;
use App\Notifications\ReservationActivityNotification;
use App\Notifications\ReservationCreatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReservationController extends Controller
{
    use RequiresConfirmedAction;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(ReservationStatus::values())],
            'payment_status' => ['nullable', Rule::in(PaymentStatus::values())],
            'reference' => ['nullable', 'string', 'max:50'],
        ]);

        $query = $request->user()
            ->reservations()
            ->with('reservationItems.item')
            ->latest();

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['payment_status'])) {
            $query->where('payment_status', $validated['payment_status']);
        }

        if (!empty($validated['reference'])) {
            $query->where('reference', 'like', '%'.trim($validated['reference']).'%');
        }

        $statusCounts = $request->user()
            ->reservations()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('customer.reservations.index', [
            'reservations' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['status', 'payment_status', 'reference']),
            'statuses' => ReservationStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'stats' => [
                'total' => (int) $statusCounts->sum(),
                'pending' => (int) ($statusCounts[ReservationStatus::PENDING->value] ?? 0),
                'ready' => (int) ($statusCounts[ReservationStatus::READY_FOR_PICKUP->value] ?? 0),
                'overdue' => (int) ($statusCounts[ReservationStatus::OVERDUE->value] ?? 0),
                'completed' => (int) ($statusCounts[ReservationStatus::COMPLETED->value] ?? 0),
                'expiring_soon' => $request->user()
                    ->reservations()
                    ->where('status', ReservationStatus::PENDING->value)
                    ->whereBetween('expires_at', [now(), now()->addDay()])
                    ->count(),
            ],
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        $validated = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'pickup_date' => ['required', 'date', 'after:today'],
            'pickup_slot' => ['required', Rule::in(PickupSlot::values())],
            'notes' => ['nullable', 'string'],
        ]);

        $reservation = DB::transaction(function () use ($request, $validated): Reservation {
            $activeReservationCount = Reservation::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ReservationStatus::activeValues())
                ->lockForUpdate()
                ->count();

            if ($activeReservationCount >= Reservation::MAX_PENDING_RESERVATIONS_PER_USER) {
                throw ValidationException::withMessages([
                    'item_id' => 'Reservation lock active. You already have 2 active reservations. Please complete, pick up, or wait for one to expire before reserving again.',
                ]);
            }

            /** @var Item $item */
            $item = Item::query()
                ->lockForUpdate()
                ->findOrFail((int) $validated['item_id']);

            if ($item->status !== ItemStatus::ACTIVE) {
                throw ValidationException::withMessages([
                    'item_id' => 'This item is not available for reservation.',
                ]);
            }

            if ($item->availableQuantity() < (int) $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Requested quantity is no longer available.',
                ]);
            }

            $reservation = Reservation::query()->create([
                'user_id' => $request->user()->id,
                'status' => ReservationStatus::PENDING,
                'payment_status' => PaymentStatus::PENDING,
                'pickup_date' => $validated['pickup_date'],
                'pickup_slot' => $validated['pickup_slot'],
                'notes' => $validated['notes'] ?? null,
                'total_amount' => (float) $item->price * (int) $validated['quantity'],
            ]);

            ReservationItem::query()->create([
                'reservation_id' => $reservation->id,
                'item_id' => $item->id,
                'quantity' => (int) $validated['quantity'],
                'unit_price' => $item->price,
                'line_total' => (float) $item->price * (int) $validated['quantity'],
            ]);

            $item->increment('reserved_quantity', (int) $validated['quantity']);

            return $reservation;
        });

        try {
            $request->user()?->notify(new ReservationCreatedNotification($reservation));
        } catch (\Throwable $exception) {
            report($exception);
        }

        $this->notifyAdmins($reservation->loadMissing('user'), 'new_reservation');

        return redirect()
            ->route('customer.reservations.show', $reservation)
            ->with('status', 'Reservation submitted. Please pay in person within 24 hours.');
    }

    public function show(Request $request, Reservation $reservation): View
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        return view('customer.reservations.show', [
            'reservation' => $reservation->load(['reservationItems.item', 'user']),
        ]);
    }

    public function extend(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);
        $this->requireConfirmedAction($request);

        if (!$reservation->isExtendable()) {
            return back()->withErrors([
                'customer_request' => 'This reservation can no longer be extended.',
            ]);
        }

        $reservation->forceFill([
            'expires_at' => $reservation->expires_at->copy()->addHours(Reservation::EXTENSION_HOURS),
            'extended_at' => now(),
        ])->save();

        $request->user()?->notify(new ReservationActivityNotification(
            $reservation,
            'Reservation extended',
            'Your reservation '.$reservation->reference.' was extended for 24 more hours.'
        ));

        return redirect()
            ->route('customer.reservations.show', $reservation)
            ->with('status', 'Reservation extended for 24 more hours.');
    }

    public function requestCancellation(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);
        $this->requireConfirmedAction($request);

        if (!$reservation->canCustomerRequestCancellation()) {
            return back()->withErrors([
                'customer_request' => 'Cancellation requests are available for active reservations that are not yet completed.',
            ]);
        }

        if ($reservation->customer_request_status === 'pending') {
            return back()->withErrors([
                'customer_request' => 'A request is already pending for this reservation.',
            ]);
        }

        $validated = $request->validate([
            'request_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $reservation->update([
            'customer_request_type' => 'cancellation',
            'customer_request_status' => 'pending',
            'customer_request_reason' => filled($validated['request_reason'] ?? null)
                ? trim((string) $validated['request_reason'])
                : 'Customer requested cancellation via self-service.',
            'customer_requested_pickup_date' => null,
            'customer_requested_pickup_slot' => null,
            'customer_requested_at' => now(),
            'customer_request_handled_at' => null,
            'customer_request_admin_note' => null,
        ]);

        $request->user()?->notify(new ReservationActivityNotification(
            $reservation,
            'Cancellation request submitted',
            'Your cancellation request for '.$reservation->reference.' was submitted and is waiting for admin review.',
            [
                'request_type' => 'cancellation',
                'request_status' => 'pending',
            ]
        ));

        $this->notifyAdmins($reservation->loadMissing('user'), 'customer_request_submitted');

        return redirect()
            ->route('customer.reservations.show', $reservation)
            ->with('status', 'Cancellation request submitted. We will review it shortly.');
    }

    public function requestReschedule(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);
        $this->requireConfirmedAction($request);

        if (!$reservation->canCustomerRequestReschedule()) {
            return back()->withErrors([
                'customer_request' => 'Only pending or overdue reservations can be updated through self-service.',
            ]);
        }

        if ($reservation->customer_request_status === 'pending') {
            return back()->withErrors([
                'customer_request' => 'A request is already pending for this reservation.',
            ]);
        }

        $validated = $request->validate([
            'requested_pickup_date' => ['required', 'date', 'after:today'],
            'requested_pickup_slot' => ['required', Rule::in(PickupSlot::values())],
            'request_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (
            optional($reservation->pickup_date)->toDateString() === $validated['requested_pickup_date']
            && $reservation->pickup_slot === $validated['requested_pickup_slot']
        ) {
            return back()->withErrors([
                'requested_pickup_date' => 'Please choose a different pickup schedule from the current one.',
            ]);
        }

        $reservation->update([
            'customer_request_type' => 'reschedule',
            'customer_request_status' => 'pending',
            'customer_request_reason' => $validated['request_reason'] !== null ? trim($validated['request_reason']) : null,
            'customer_requested_pickup_date' => $validated['requested_pickup_date'],
            'customer_requested_pickup_slot' => $validated['requested_pickup_slot'],
            'customer_requested_at' => now(),
            'customer_request_handled_at' => null,
            'customer_request_admin_note' => null,
        ]);

        $request->user()?->notify(new ReservationActivityNotification(
            $reservation,
            'Reschedule request submitted',
            'Your reschedule request for '.$reservation->reference.' was submitted and is waiting for admin review.',
            [
                'request_type' => 'reschedule',
                'request_status' => 'pending',
            ]
        ));

        $this->notifyAdmins($reservation->loadMissing('user'), 'customer_request_submitted');

        return redirect()
            ->route('customer.reservations.show', $reservation)
            ->with('status', 'Pickup reschedule request submitted. We will review it shortly.');
    }

    /**
     * Cancel a single item from a reservation (per-line-item cancel)
     */
    public function cancelItem(Request $request, Reservation $reservation, ReservationItem $reservationItem): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);
        abort_unless($reservationItem->reservation_id === $reservation->id, 404);
        $this->requireConfirmedAction($request);

        // Only allow cancel if reservation is active and item is not already pending cancel
        if (!$reservation->canCustomerRequestCancellation()) {
            return back()->withErrors(['customer_request' => 'You cannot cancel items for this reservation.']);
        }

        if ($reservationItem->cancel_pending) {
            return back()->withErrors(['customer_request' => 'A cancellation request for this item is already pending.']);
        }

        $reservationItem->update(['cancel_pending' => true]);

        // Optionally, notify admins here if not already done elsewhere
        // $this->notifyAdmins($reservation, 'customer_request_submitted');

        // Redirect to self-service section with status
        return redirect()
            ->route('customer.reservations.show', [$reservation, '#self-service-requests'])
            ->with('status', 'Cancellation request submitted for this item. Awaiting admin approval.');
    }

    private function notifyAdmins(Reservation $reservation, string $eventType): void
    {
        User::admins()
            ->get()
            ->each(static function (User $admin) use ($reservation, $eventType): void {
                $admin->notify(new AdminReservationNotification($reservation, $eventType));
            });
    }
}
