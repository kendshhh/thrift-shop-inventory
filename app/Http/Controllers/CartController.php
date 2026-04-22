<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Enums\PaymentStatus;
use App\Enums\PickupSlot;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\User;
use App\Notifications\AdminReservationNotification;
use App\Notifications\ReservationCreatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = Cart::currentForUser($request->user()->id)?->loadMissing('items.item');

        return view('cart.index', [
            'cart' => $cart,
            'pickupSlots' => PickupSlot::cases(),
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        /** @var Item $item */
        $item = Item::query()->findOrFail((int) $validated['item_id']);

        if (!$item->isAvailableForPurchase()) {
            return back()
                ->withErrors(['item_id' => 'This item is not available for cart checkout.'])
                ->withInput();
        }

        $cart = Cart::currentOrCreateForUser($request->user()->id);
        $existingCartItem = $cart->items()->where('item_id', $item->id)->first();
        $requestedQuantity = (int) $validated['quantity'];
        $newQuantity = ($existingCartItem?->quantity ?? 0) + $requestedQuantity;

        if ($newQuantity > $item->availableQuantity()) {
            return back()
                ->withErrors(['quantity' => 'Requested quantity exceeds what is currently available.'])
                ->withInput();
        }

        $cart->refreshExpiry();

        if ($existingCartItem instanceof CartItem) {
            $existingCartItem->forceFill([
                'quantity' => $newQuantity,
            ])->save();
        } else {
            $cart->items()->create([
                'item_id' => $item->id,
                'quantity' => $requestedQuantity,
            ]);
        }

        return redirect()->route('cart.index')->with('status', 'Item added to cart.');
    }

    public function remove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
        ]);

        $cart = Cart::currentForUser($request->user()->id);

        if ($cart) {
            $cart->items()->where('item_id', (int) $validated['item_id'])->delete();

            if (!$cart->items()->exists()) {
                $cart->delete();
            }
        }

        return redirect()->route('cart.index')->with('status', 'Item removed from cart.');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::currentForUser($request->user()->id);

        if ($cart === null) {
            return redirect()->route('cart.index')->withErrors([
                'cart' => 'Your cart is empty or expired.',
            ]);
        }

        /** @var CartItem|null $cartItem */
        $cartItem = $cart->items()->where('item_id', (int) $validated['item_id'])->first();

        if (!$cartItem instanceof CartItem) {
            return redirect()->route('cart.index')->withErrors([
                'cart' => 'The selected cart item could not be found.',
            ]);
        }

        /** @var Item $item */
        $item = Item::query()->findOrFail((int) $validated['item_id']);
        $quantity = (int) $validated['quantity'];

        if ($request->input('adjustment') === 'increment') {
            $quantity = $cartItem->quantity + 1;
        }

        if ($request->input('adjustment') === 'decrement') {
            $quantity = max(1, $cartItem->quantity - 1);
        }

        if (!$item->isAvailableForPurchase() || $quantity > $item->availableQuantity()) {
            return back()
                ->withErrors(['quantity' => 'Requested quantity exceeds what is currently available.'])
                ->withInput();
        }

        $cart->refreshExpiry();
        $cartItem->forceFill([
            'quantity' => $quantity,
        ])->save();

        return redirect()->route('cart.index')->with('status', 'Cart quantity updated.');
    }

    /**
     * @throws ValidationException
     */
    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pickup_date' => ['required', 'date', 'after:today'],
            'pickup_slot' => ['required', Rule::in(PickupSlot::values())],
            'notes' => ['nullable', 'string'],
        ]);

        $reservation = DB::transaction(function () use ($request, $validated): Reservation {
            $cart = Cart::currentForUser($request->user()->id);

            if ($cart === null || !$cart->items()->exists()) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty or expired.',
                ]);
            }

            $activeReservationCount = Reservation::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ReservationStatus::activeValues())
                ->lockForUpdate()
                ->count();

            if ($activeReservationCount >= Reservation::MAX_PENDING_RESERVATIONS_PER_USER) {
                throw ValidationException::withMessages([
                    'cart' => 'Reservation lock active. You already have 2 active reservations. Please complete, pick up, or wait for one to expire before checking out your cart.',
                ]);
            }

            $cartItems = $cart->items()
                ->orderBy('item_id')
                ->get();

            $items = Item::query()
                ->whereIn('id', $cartItems->pluck('item_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $totalAmount = 0.0;

            foreach ($cartItems as $cartItem) {
                $item = $items->get($cartItem->item_id);

                if (!$item instanceof Item || $item->status !== ItemStatus::ACTIVE || $item->availableQuantity() < $cartItem->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => 'Some items in your cart are no longer available in the requested quantity.',
                    ]);
                }

                $totalAmount += (float) $item->price * $cartItem->quantity;
            }

            $reservation = Reservation::query()->create([
                'user_id' => $request->user()->id,
                'status' => ReservationStatus::PENDING,
                'payment_status' => PaymentStatus::PENDING,
                'pickup_date' => $validated['pickup_date'],
                'pickup_slot' => $validated['pickup_slot'],
                'notes' => filled($validated['notes'] ?? null) ? trim((string) $validated['notes']) : null,
                'total_amount' => $totalAmount,
            ]);

            foreach ($cartItems as $cartItem) {
                /** @var Item $item */
                $item = $items->get($cartItem->item_id);

                ReservationItem::query()->create([
                    'reservation_id' => $reservation->id,
                    'item_id' => $item->id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $item->price,
                    'line_total' => (float) $item->price * $cartItem->quantity,
                ]);

                $item->increment('reserved_quantity', $cartItem->quantity);
            }

            $cart->items()->delete();
            $cart->delete();

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

    private function notifyAdmins(Reservation $reservation, string $eventType): void
    {
        try {
            User::role('admin')
                ->get()
                ->each(static function (User $admin) use ($reservation, $eventType): void {
                    $admin->notify(new AdminReservationNotification($reservation, $eventType));
                });
        } catch (RoleDoesNotExist $exception) {
            report($exception);
        }
    }
}
