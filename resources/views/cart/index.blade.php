<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                <div>
                    <h5 class="mb-0 fw-bold">Your Cart</h5>
                    <p class="mb-0 text-muted small">Review your selected items before creating one reservation.</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-custom"><i class="bi bi-grid me-1"></i>Browse Items</a>
                <a href="{{ route('customer.reservations.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-bag-check me-1"></i>My Reservations</a>
            </div>
        </div>
    </x-slot>

    @php
        $cartItems = $cart?->items ?? collect();
        $itemCount = $cartItems->sum('quantity');
        $subtotal = $cartItems->sum(fn ($cartItem) => (float) $cartItem->item->price * $cartItem->quantity);
    @endphp

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            {{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card glass-card surface-section h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Cart Items</strong>
                    <span class="small text-muted">{{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }}</span>
                </div>
                <div class="card-body">
                    @if ($cart && $cartItems->isNotEmpty())
                        @if ($cart->expires_at)
                            <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
                                <i class="bi bi-clock-history fs-5 mt-1"></i>
                                <div>
                                    Your current cart session expires {{ $cart->expires_at->diffForHumans() }}. Stock is confirmed only when you submit checkout.
                                </div>
                            </div>
                        @endif

                        <div class="vstack gap-3">
                            @foreach ($cartItems as $cartItem)
                                <div class="border rounded-4 p-3">
                                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                        <div class="flex-grow-1">
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                <h6 class="mb-0 fw-semibold">{{ $cartItem->item->name }}</h6>
                                                <span class="badge text-bg-light border">{{ $cartItem->item->category?->name ?? 'Uncategorized' }}</span>
                                            </div>
                                            <p class="text-muted small mb-2">{{ \Illuminate\Support\Str::limit($cartItem->item->description ?: 'No description provided.', 120) }}</p>
                                            <div class="small text-muted d-flex flex-wrap gap-3">
                                                <span>Unit Price: <strong class="text-success">&#8369;{{ number_format((float) $cartItem->item->price, 2) }}</strong></span>
                                                <span>Quantity: <strong>{{ $cartItem->quantity }}</strong></span>
                                                <span>Line Total: <strong>&#8369;{{ number_format((float) $cartItem->item->price * $cartItem->quantity, 2) }}</strong></span>
                                            </div>

                                            <form action="{{ route('cart.update') }}" method="POST" class="row g-2 align-items-end mt-3">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="item_id" value="{{ $cartItem->item_id }}">

                                                <div class="col-sm-6 col-md-5 col-lg-4">
                                                    <label for="quantity-{{ $cartItem->item_id }}" class="form-label small text-muted mb-1">Update Quantity</label>
                                                    <div class="input-group input-group-sm">
                                                        <button type="submit" name="adjustment" value="decrement" class="btn btn-outline-secondary" aria-label="Decrease quantity for {{ $cartItem->item->name }}">
                                                            <i class="bi bi-dash-lg"></i>
                                                        </button>
                                                        <input
                                                            id="quantity-{{ $cartItem->item_id }}"
                                                            name="quantity"
                                                            type="number"
                                                            min="1"
                                                            max="{{ $cartItem->item->availableQuantity() }}"
                                                            value="{{ old('item_id') == $cartItem->item_id ? old('quantity', $cartItem->quantity) : $cartItem->quantity }}"
                                                            class="form-control form-control-sm text-center @if (old('item_id') == $cartItem->item_id && $errors->has('quantity')) is-invalid @endif"
                                                            required
                                                        >
                                                        <button type="submit" name="adjustment" value="increment" class="btn btn-outline-secondary" aria-label="Increase quantity for {{ $cartItem->item->name }}">
                                                            <i class="bi bi-plus-lg"></i>
                                                        </button>
                                                    </div>
                                                    @if (old('item_id') == $cartItem->item_id && $errors->has('quantity'))
                                                        <div class="invalid-feedback">{{ $errors->first('quantity') }}</div>
                                                    @endif
                                                </div>

                                                <div class="col-sm-auto">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Apply</button>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="d-flex flex-md-column gap-2 justify-content-between align-items-md-end">
                                            <a href="{{ route('items.show', $cartItem->item) }}" class="btn btn-sm btn-outline-secondary">View Item</a>
                                            <form action="{{ route('cart.remove') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="item_id" value="{{ $cartItem->item_id }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-cart-x fs-1 d-block mb-3 text-muted"></i>
                            <h6 class="fw-semibold mb-2">Your cart is empty.</h6>
                            <p class="text-muted mb-3">Add items first, then come back here to submit them as one reservation.</p>
                            <a href="{{ route('items.index') }}" class="btn btn-primary">Browse Items</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card glass-card surface-section h-100">
                <div class="card-header"><strong>Checkout Details</strong></div>
                <div class="card-body">
                    @if ($cart && $cartItems->isNotEmpty())
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Items</span>
                                <strong>{{ $itemCount }}</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Estimated Total</span>
                                <strong class="text-success fs-5">&#8369;{{ number_format($subtotal, 2) }}</strong>
                            </div>
                            <p class="small text-muted mb-0">Submitting checkout creates one reservation for all items in this cart. Inventory is revalidated when you submit.</p>
                        </div>

                        <form action="{{ route('cart.checkout') }}" method="POST" class="vstack gap-3">
                            @csrf

                            @php
                                $selectedPickupSlot = old('pickup_slot', $pickupSlots[0]->value ?? null);
                                $selectedPickupSlotLabel = collect($pickupSlots)->firstWhere('value', $selectedPickupSlot)?->label() ?? 'Select slot';
                            @endphp

                            <div>
                                <label for="pickup_date" class="form-label fw-medium">Pickup Date</label>
                                <input
                                    id="pickup_date"
                                    name="pickup_date"
                                    type="text"
                                    data-flatpickr-date
                                    data-min-date="{{ now()->addDay()->toDateString() }}"
                                    value="{{ old('pickup_date', now()->addDay()->toDateString()) }}"
                                    class="form-control form-control-modern @error('pickup_date') is-invalid @enderror"
                                    required
                                >
                                @error('pickup_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label for="pickup_slot" class="form-label fw-medium">Pickup Slot</label>
                                <div class="dropdown" data-option-dropdown>
                                    <input type="hidden" id="pickup_slot" name="pickup_slot" value="{{ $selectedPickupSlot }}" data-option-input required>
                                    <button class="btn btn-outline-custom w-100 d-flex justify-content-between align-items-center rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span data-option-label>{{ $selectedPickupSlotLabel }}</span>
                                    </button>
                                    <ul class="dropdown-menu w-100 mt-2 border-0 shadow-lg glass-dropdown-menu">
                                        @foreach ($pickupSlots as $slot)
                                            <li>
                                                <button type="button" class="dropdown-item" data-option-value="{{ $slot->value }}" data-option-text="{{ $slot->label() }}">{{ $slot->label() }}</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @error('pickup_slot') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label for="notes" class="form-label fw-medium">Notes <span class="text-muted fw-normal small">(optional)</span></label>
                                <textarea id="notes" name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Pickup reminders or special handling notes">{{ old('notes') }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-bag-check me-1"></i>Checkout Cart as Reservation
                            </button>
                            <a href="{{ route('customer.reservations.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                        </form>
                    @else
                        <div class="text-muted small">
                            Pickup schedule and checkout options will appear here once you add items to your cart.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>