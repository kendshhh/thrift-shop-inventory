<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RequiresConfirmedAction;
use App\Models\Cart;
use App\Models\Item;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\User;
use App\Notifications\AdminReservationNotification;
use App\Notifications\ReservationActivityNotification;
use App\Notifications\ReservationRequestUpdatedNotification;
use App\Notifications\ReservationStatusUpdatedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReservationManagementController extends Controller
{
    use RequiresConfirmedAction;

    public function overview(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', Rule::in(['all', 'reserved'])],
            'expiring_soon' => ['nullable', 'boolean'],
        ]);

        $activeReservations = Reservation::query()
            ->whereIn('status', ReservationStatus::activeValues())
            ->with(['user', 'reservationItems.item'])
            ->get();

        $activeCarts = Cart::query()
            ->where('expires_at', '>', now())
            ->with(['items.item'])
            ->get();

        $cartUsers = User::query()
            ->whereIn('id', $activeCarts->pluck('user_id')->unique()->filter()->all())
            ->get()
            ->keyBy('id');

        $soldReservationItems = ReservationItem::query()
            ->with(['reservation.user'])
            ->whereHas('reservation', function ($query): void {
                $query->where('status', ReservationStatus::COMPLETED->value);
            })
            ->get()
            ->groupBy('item_id');

        $reservationRowsByItem = $this->buildReservationRowsByItem($activeReservations);
        $cartRowsByItem = $this->buildCartRowsByItem($activeCarts, $cartUsers);

        $items = Item::query()
            ->latest('updated_at')
            ->get();

        $search = trim((string) ($validated['search'] ?? ''));
        $state = (string) ($validated['state'] ?? 'all');
        $expiringSoonOnly = (bool) ($validated['expiring_soon'] ?? false);

        $itemRows = $items
            ->map(function (Item $item) use ($reservationRowsByItem, $cartRowsByItem, $soldReservationItems): array {
                $reservationRows = $reservationRowsByItem->get($item->id, collect());
                $cartRows = $cartRowsByItem->get($item->id, collect());
                $soldRows = $soldReservationItems->get($item->id, collect());

                $primaryReservationRow = $reservationRows->sortBy('deadline_timestamp')->first();
                $primaryCartRow = $cartRows->sortBy('deadline_timestamp')->first();
                $latestSoldRow = $soldRows->sortByDesc(static fn (ReservationItem $lineItem) => (string) $lineItem->updated_at)->first();

                $deadline = $primaryReservationRow['deadline'] ?? $primaryCartRow['deadline'] ?? null;
                $isExpiringSoon = $deadline instanceof Carbon && $deadline->isFuture() && $deadline->diffInMinutes(now()) <= 60;

                $derivedState = 'available';
                if ($latestSoldRow instanceof ReservationItem) {
                    $derivedState = 'sold';
                }
                if ($primaryReservationRow !== null) {
                    $derivedState = 'reserved';
                } elseif ($primaryCartRow !== null) {
                    $derivedState = 'cart_hold';
                }

                $reservedBy = null;
                $sourceLabel = null;
                if ($primaryReservationRow !== null) {
                    $reservedBy = $primaryReservationRow['user_name'];
                    $sourceLabel = 'Reservation '.$primaryReservationRow['reservation_reference'];
                } elseif ($primaryCartRow !== null) {
                    $reservedBy = $primaryCartRow['user_name'];
                    $sourceLabel = 'Cart Hold';
                } elseif ($latestSoldRow instanceof ReservationItem) {
                    $reservedBy = $latestSoldRow->reservation?->user?->name;
                    $sourceLabel = 'Completed Reservation';
                }

                return [
                    'item' => $item,
                    'state' => $derivedState,
                    'reserved_by' => $reservedBy,
                    'source_label' => $sourceLabel,
                    'deadline' => $deadline,
                    'is_expiring_soon' => $isExpiringSoon,
                    'reservation_row' => $primaryReservationRow,
                    'cart_row' => $primaryCartRow,
                    'search_blob' => strtolower(implode(' ', array_filter([
                        $item->name,
                        $reservedBy,
                        $primaryReservationRow['reservation_reference'] ?? null,
                        $primaryCartRow['user_name'] ?? null,
                    ]))),
                ];
            })
            ->filter(function (array $row) use ($state, $search, $expiringSoonOnly): bool {
                // Show both reserved and cart_hold states
                if (!in_array($row['state'], ['reserved', 'cart_hold'], true)) {
                    return false;
                }

                if ($state !== 'all' && $row['state'] !== $state) {
                    return false;
                }

                if ($expiringSoonOnly && !$row['is_expiring_soon']) {
                    return false;
                }

                if ($search !== '' && !str_contains($row['search_blob'], strtolower($search))) {
                    return false;
                }

                return true;
            })
            ->values();

        $userReservationGroups = $this->buildUserReservationGroups($activeReservations, $search);

        return view('admin.reservations.overview', [
            'itemRows' => $itemRows,
            'userReservationGroups' => $userReservationGroups,
            'filters' => [
                'search' => $search,
                'state' => $state,
                'expiring_soon' => $expiringSoonOnly,
            ],
            'overviewCounts' => [
                'reserved' => $itemRows->where('state', 'reserved')->count(),
                'expiring_soon' => $itemRows->where('is_expiring_soon', true)->count(),
            ],
        ]);
    }

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
        $this->requireConfirmedAction($request);

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

        // Release reserved items if just expired
        if (!$wasExpired && $newStatus === ReservationStatus::EXPIRED) {
            $this->releaseReservedItems($reservation);
        }
        // Permanently deduct quantity if just completed
        if ($completedNow) {
            $this->deductCompletedItems($reservation);
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
        $this->requireConfirmedAction($request);

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

        // Only redirect to self-service anchor if the approval came from the self-service request form
        if ($validated['action'] === 'approve' && $reservation->customer_request_type === 'cancellation') {
            // Check referer or add a hidden input to the form to indicate source
            if ($request->input('from_self_service') === '1') {
                return redirect()
                    ->route('admin.reservations.show', [$reservation, '#self-service-requests'])
                    ->with('status', 'Customer request has been approved.');
            }
        }
        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('status', 'Customer request has been '.$reservation->customer_request_status.'.');
    }

    public function extend(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        if (!$reservation->isExtendable()) {
            return back()->withErrors([
                'reservation' => 'This reservation can no longer be extended.',
            ]);
        }

        $reservation->forceFill([
            'expires_at' => $reservation->expires_at?->copy()->addHours(Reservation::EXTENSION_HOURS),
            'extended_at' => now(),
        ])->save();

        return back()->with('status', 'Reservation timer extended by 24 hours.');
    }

    public function markSold(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        if (!$reservation->status->canTransitionTo(ReservationStatus::COMPLETED)) {
            return back()->withErrors([
                'reservation' => 'This reservation cannot be marked as sold from its current status.',
            ]);
        }

        $reservation->forceFill([
            'status' => ReservationStatus::COMPLETED,
            'payment_status' => PaymentStatus::COMPLETED,
            'paid_at' => $reservation->paid_at ?? now(),
            'completed_at' => $reservation->completed_at ?? now(),
        ])->save();

        $this->releaseReservedItems($reservation);

        return back()->with('status', 'Reservation marked as sold and stock released.');
    }

    public function handleItemCancelRequest(Request $request, Reservation $reservation, ReservationItem $reservationItem): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        if ($reservationItem->reservation_id !== $reservation->id) {
            return back()->withErrors(['reservation_item' => 'Item does not belong to this reservation.']);
        }

        $action = $request->input('action');

        if ($action === 'approve') {
            DB::transaction(function () use ($reservation, $reservationItem): void {
                $reservationItem->loadMissing('item');

                if ($reservationItem->item !== null) {
                    $reservationItem->item->decrement('reserved_quantity', min($reservationItem->item->reserved_quantity, $reservationItem->quantity));
                }

                $reservationItem->delete();

                $remainingTotal = (float) $reservation->reservationItems()->sum('line_total');

                if ($remainingTotal <= 0.0) {
                    $reservation->forceFill([
                        'status' => ReservationStatus::EXPIRED,
                        'payment_status' => PaymentStatus::OVERDUE,
                    ])->save();
                } else {
                    $reservation->update(['total_amount' => $remainingTotal]);
                }
            });
            // Notify customer with specific item cancel notification
            $reservation->loadMissing('user');
            if ($reservation->user) {
                $reservation->user->notify(new \App\Notifications\ReservationItemCancelRequestNotification(
                    $reservation,
                    $reservationItem,
                    'approved'
                ));
            }
            return back()->with('status', 'Item cancellation approved and removed from reservation.');
        }

        if ($action === 'decline') {
            $reservationItem->update(['cancel_pending' => false]);
            // Notify customer with specific item cancel notification
            $reservation->loadMissing('user');
            if ($reservation->user) {
                $reservation->user->notify(new \App\Notifications\ReservationItemCancelRequestNotification(
                    $reservation,
                    $reservationItem,
                    'declined'
                ));
            }
            return back()->with('status', 'Item cancellation request declined.');
        }

        return back();
    }

    public function removeItem(Request $request, Reservation $reservation, ReservationItem $reservationItem): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        if ($reservationItem->reservation_id !== $reservation->id) {
            return back()->withErrors([
                'reservation_item' => 'The selected reservation item does not belong to this reservation.',
            ]);
        }

        DB::transaction(function () use ($reservation, $reservationItem): void {
            $reservationItem->loadMissing('item');

            if ($reservationItem->item !== null) {
                $reservationItem->item->decrement('reserved_quantity', min($reservationItem->item->reserved_quantity, $reservationItem->quantity));
            }

            $reservationItem->delete();

            $remainingItemsCount = $reservation->reservationItems()->count();
            $reservation->loadMissing('user');
            if ($remainingItemsCount === 0 && $reservation->status !== ReservationStatus::EXPIRED && $reservation->status !== ReservationStatus::COMPLETED) {
                $reservation->forceFill([
                    'status' => ReservationStatus::EXPIRED,
                    'payment_status' => PaymentStatus::OVERDUE,
                ])->save();
                // Notify user if reservation is now cancelled
                if ($reservation->user !== null) {
                    $reservation->user->notify(new \App\Notifications\ReservationStatusUpdatedNotification(
                        $reservation,
                        $reservation->status->label(),
                        $reservation->payment_status->label()
                    ));
                }
            } else {
                // Notify user that an item was removed (reservation still active)
                if ($reservation->user !== null) {
                    $reservation->user->notify(new \App\Notifications\ReservationStatusUpdatedNotification(
                        $reservation,
                        $reservation->status->label(),
                        $reservation->payment_status->label()
                    ));
                }
            }
        });

        return back()->with('status', 'Reserved item released and user notified.');
    }

    public function cancelAllForUser(Request $request, User $user): RedirectResponse
    {
        $this->requireConfirmedAction($request);

        $activeReservations = Reservation::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ReservationStatus::activeValues())
            ->with('reservationItems.item')
            ->get();

        if ($activeReservations->isEmpty()) {
            return back()->with('status', 'No active reservations found for this user.');
        }


        DB::transaction(function () use ($activeReservations): void {
            foreach ($activeReservations as $reservation) {
                $this->releaseReservedItems($reservation);

                $reservation->forceFill([
                    'status' => ReservationStatus::EXPIRED,
                    'payment_status' => PaymentStatus::OVERDUE,
                ])->save();
                // Notify user if reservation is now cancelled
                $reservation->loadMissing('user');
                if ($reservation->user !== null) {
                    $reservation->user->notify(new \App\Notifications\ReservationStatusUpdatedNotification(
                        $reservation,
                        $reservation->status->label(),
                        $reservation->payment_status->label()
                    ));
                }
            }
        });

        return back()->with('status', 'All active reservations for this user were cancelled.');
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

    private function deductCompletedItems(Reservation $reservation): void
    {
        $reservation->loadMissing('reservationItems.item');

        foreach ($reservation->reservationItems as $lineItem) {
            if ($lineItem->item !== null) {
                $item = $lineItem->item;
                $deductQty = min($item->reserved_quantity, $lineItem->quantity);

                $item->decrement('reserved_quantity', $deductQty);
                $item->decrement('quantity', $lineItem->quantity);
            }
        }
    }

    private function buildReservationRowsByItem(Collection $reservations): Collection
    {
        return $reservations
            ->flatMap(function (Reservation $reservation): Collection {
                $deadline = $this->reservationDeadline($reservation);

                return $reservation->reservationItems->map(function (ReservationItem $lineItem) use ($reservation, $deadline): array {
                    return [
                        'item_id' => $lineItem->item_id,
                        'reservation_id' => $reservation->id,
                        'reservation_item_id' => $lineItem->id,
                        'reservation_reference' => $reservation->reference,
                        'reservation_status' => $reservation->status->value,
                        'user_id' => $reservation->user?->id,
                        'user_name' => $reservation->user?->name ?? 'Deleted User',
                        'quantity' => $lineItem->quantity,
                        'deadline' => $deadline,
                        'deadline_timestamp' => $deadline?->getTimestamp() ?? PHP_INT_MAX,
                        'cancel_pending' => (bool) $lineItem->cancel_pending,
                    ];
                });
            })
            ->groupBy('item_id');
    }

    private function buildCartRowsByItem(Collection $activeCarts, Collection $cartUsers): Collection
    {
        return $activeCarts
            ->flatMap(function (Cart $cart) use ($cartUsers): Collection {
                $userName = $cartUsers->get($cart->user_id)?->name ?? 'Customer #'.$cart->user_id;

                return $cart->items->map(function ($lineItem) use ($cart, $userName): array {
                    return [
                        'item_id' => $lineItem->item_id,
                        'cart_id' => $cart->id,
                        'user_id' => $cart->user_id,
                        'user_name' => $userName,
                        'quantity' => $lineItem->quantity,
                        'deadline' => $cart->expires_at,
                        'deadline_timestamp' => $cart->expires_at?->getTimestamp() ?? PHP_INT_MAX,
                    ];
                });
            })
            ->groupBy('item_id');
    }

    private function buildUserReservationGroups(Collection $reservations, string $search): Collection
    {
        return $reservations
            ->groupBy('user_id')
            ->map(function (Collection $userReservations): array {
                /** @var Reservation $primaryReservation */
                $primaryReservation = $userReservations->first();

                $rows = $userReservations
                    ->flatMap(function (Reservation $reservation): Collection {
                        $deadline = $this->reservationDeadline($reservation);

                        return $reservation->reservationItems->map(function (ReservationItem $lineItem) use ($reservation, $deadline): array {
                            return [
                                'reservation' => $reservation,
                                'reservation_item' => $lineItem,
                                'item' => $lineItem->item,
                                'deadline' => $deadline,
                                'cancel_pending' => (bool) $lineItem->cancel_pending,
                            ];
                        });
                    })
                    ->values();

                $totalSecondsLeft = $rows
                    ->map(static function (array $row): int {
                        $deadline = $row['deadline'];
                        if (!$deadline instanceof Carbon || !$deadline->isFuture()) {
                            return 0;
                        }

                        return now()->diffInSeconds($deadline);
                    })
                    ->sum();

                return [
                    'user' => $primaryReservation->user,
                    'rows' => $rows,
                    'total_seconds_left' => $totalSecondsLeft,
                    'subtotal' => (float) $rows->sum(static fn (array $row) => (float) ($row['reservation_item']->line_total ?? 0)),
                ];
            })
            ->filter(function (array $group) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                $blob = strtolower(implode(' ', array_filter([
                    $group['user']?->name,
                    $group['user']?->email,
                    $group['rows']->pluck('item.name')->implode(' '),
                    $group['rows']->pluck('reservation.reference')->implode(' '),
                ])));

                return str_contains($blob, strtolower($search));
            })
            ->values();
    }

    private function reservationDeadline(Reservation $reservation): ?Carbon
    {
        if ($reservation->status === ReservationStatus::PENDING && $reservation->expires_at instanceof Carbon) {
            return $reservation->expires_at;
        }

        if (
            in_array($reservation->status, [ReservationStatus::READY_FOR_PICKUP, ReservationStatus::OVERDUE], true)
            && $reservation->pickup_date instanceof Carbon
        ) {
            return $reservation->pickup_date->copy()->endOfDay();
        }

        return null;
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
