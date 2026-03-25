<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\PickupSlot;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Notifications\ReservationCreatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReservationController extends Controller
{
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
        $validated = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'pickup_slot' => ['required', Rule::in(PickupSlot::values())],
            'notes' => ['nullable', 'string'],
        ]);

        $reservation = DB::transaction(function () use ($request, $validated): Reservation {
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

        $request->user()?->notify(new ReservationCreatedNotification($reservation));

        return redirect()
            ->route('customer.reservations.show', $reservation)
            ->with('status', 'Reservation submitted. Please pay in person within 48 hours.');
    }

    public function show(Request $request, Reservation $reservation): View
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        return view('customer.reservations.show', [
            'reservation' => $reservation->load(['reservationItems.item', 'user']),
        ]);
    }

    public function requestCancellation(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        if (!$this->canCreateCustomerRequest($reservation)) {
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
            'request_reason' => ['required', 'string', 'max:1000'],
        ]);

        $reservation->update([
            'customer_request_type' => 'cancellation',
            'customer_request_status' => 'pending',
            'customer_request_reason' => trim($validated['request_reason']),
            'customer_requested_pickup_date' => null,
            'customer_requested_pickup_slot' => null,
            'customer_requested_at' => now(),
            'customer_request_handled_at' => null,
            'customer_request_admin_note' => null,
        ]);

        return redirect()
            ->route('customer.reservations.show', $reservation)
            ->with('status', 'Cancellation request submitted. We will review it shortly.');
    }

    public function requestReschedule(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);

        if (!$this->canCreateCustomerRequest($reservation)) {
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
            'requested_pickup_date' => ['required', 'date', 'after_or_equal:today'],
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

        return redirect()
            ->route('customer.reservations.show', $reservation)
            ->with('status', 'Pickup reschedule request submitted. We will review it shortly.');
    }

    private function canCreateCustomerRequest(Reservation $reservation): bool
    {
        return in_array($reservation->status, [
            ReservationStatus::PENDING,
            ReservationStatus::OVERDUE,
        ], true);
    }
}
