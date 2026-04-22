<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                <div>
                    <h5 class="mb-0 fw-bold">Item Details</h5>
                    <p class="mb-0 text-muted small">Review details and reserve while stock is available.</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('cart.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-cart me-1"></i>View Cart</a>
                <a href="{{ route('customer.reservations.index') }}" class="btn btn-sm btn-outline-custom"><i class="bi bi-bag-check me-1"></i>My Reservations</a>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            {{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @error('item_id') <div class="alert alert-danger mb-4">{{ $message }}</div> @enderror

    @php
        $availableQuantity = $item->availableQuantity();
        $isAvailable = $item->isAvailableForPurchase();
        $isReservedOut = $item->isReservedOut();
        $isReservationLocked = $reservationLock['is_locked'] ?? false;
        $pendingReservationCount = $reservationLock['pending_count'] ?? 0;
        $reservationLimit = $reservationLock['limit'] ?? 2;
    @endphp

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="item-detail-image-wrap mb-3">
                        @if ($item->imageUrl())
                            <div class="image-frame image-frame-detail">
                                <div class="image-frame-backdrop" style="background-image: url('{{ $item->imageUrl() }}');"></div>
                                <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" class="item-detail-image image-frame-foreground" data-lightbox-image tabindex="0">
                            </div>
                        @else
                            <div class="item-detail-image-placeholder">
                                <i class="bi bi-image fs-1 d-block mb-2"></i>
                                No thumbnail uploaded
                            </div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                        <h4 class="fw-bold mb-1">{{ $item->name }}</h4>
                        @if ($isAvailable)
                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">In Stock</span>
                        @elseif ($isReservedOut)
                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle">Reserved by Another Customer</span>
                        @elseif ($item->hasScheduledRestock())
                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle">Restocking Soon</span>
                        @else
                            <span class="badge rounded-pill bg-secondary">Out of Stock</span>
                        @endif
                    </div>
                    <div class="mb-3">
                        <span class="badge text-bg-light border">{{ $item->category?->name ?? 'Uncategorized' }}</span>
                        <span class="badge bg-white text-dark border ms-1">{{ $item->condition->label() }}</span>
                    </div>
                    <div class="fs-4 fw-bold text-success mb-3">&#8369;{{ number_format((float) $item->price, 2) }}</div>
                    <div class="mb-3 text-muted small">
                        @if ($isAvailable)
                            <span><i class="bi bi-check-circle-fill text-success me-1"></i>{{ $availableQuantity }} available</span>
                        @elseif ($isReservedOut)
                            <span class="countdown-chip" data-countdown-to="{{ $item->nextReservationAvailabilityAt()?->toIso8601String() }}">
                                Available in <span data-countdown-label>Loading...</span>
                            </span>
                            <div class="mt-2">Reserved temporarily by another customer. If payment is not completed before expiry, this item will be available again.</div>
                        @elseif ($item->hasScheduledRestock())
                            <span class="countdown-chip" data-countdown-to="{{ $item->restock_at?->toIso8601String() }}">
                                Restocks in <span data-countdown-label>Loading...</span>
                            </span>
                            <div class="mt-2">Expected on {{ $item->restock_at?->format('M d, Y g:i A') }}</div>
                        @else
                            <span><i class="bi bi-x-circle text-secondary me-1"></i>Currently unavailable</span>
                        @endif
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="text-muted small">Seller Name</div>
                            <div class="fw-medium">{{ $item->seller_name }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Contact Number</div>
                            <div class="fw-medium">{{ $item->seller_contact_number }}</div>
                        </div>
                    </div>
                    <p class="text-muted mb-4">{{ $item->description ?: 'No description provided.' }}</p>

                    <div class="customer-policy-note">
                        <h6 class="fw-semibold mb-2">Reservation Notes</h6>
                        <ul class="mb-0 small text-muted ps-3">
                            <li>Reservations stay active for 24 hours while awaiting payment.</li>
                            <li>You may extend a pending reservation one time for another 24 hours.</li>
                            <li>Customers with {{ $reservationLimit }} active reservations are temporarily locked from making another one.</li>
                            <li>Payment is processed in person at pickup.</li>
                            <li>Stock is reserved immediately after you submit.</li>
                        </ul>
                    </div>

                    @if (data_get($branding, 'payment_details'))
                        <div class="mt-4">
                            @include('partials.payment-details', [
                                'paymentDetails' => data_get($branding, 'payment_details', []),
                                'title' => 'Accepted Payment Details',
                                'subtitle' => 'Use these details as reference before pickup. Full payment instructions also appear inside your reservation page.',
                                'compact' => true,
                                'emptyMessage' => null,
                            ])
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @auth
            @if (auth()->user()->hasRole('customer'))
                <div class="col-lg-6">
                    <div class="card" id="reserve-card">
                        <div class="card-header fw-semibold"><i class="bi bi-bag-plus me-1"></i>Reserve This Item</div>
                        <div class="card-body">
                            @if ($isAvailable)
                                @if ($isReservationLocked)
                                    <div class="alert alert-warning mb-3">
                                        Your reservation button is locked right now because you already have <strong>{{ $pendingReservationCount }}</strong> active reservation{{ $pendingReservationCount > 1 ? 's' : '' }}.
                                        Extend, complete, or wait for one to expire before reserving another item.
                                    </div>
                                    <a href="{{ route('customer.reservations.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-bag-check me-1"></i>Manage My Reservations</a>
                                @else
                                    <form method="POST" action="{{ route('customer.reservations.store') }}" data-confirm-action data-confirm-message='Type "confirm" to create this reservation.'>
                                        @csrf
                                        <input type="hidden" name="item_id" value="{{ $item->id }}">

                                        @php
                                            $pickupSlots = \App\Enums\PickupSlot::cases();
                                            $selectedPickupSlot = old('pickup_slot', $pickupSlots[0]->value ?? null);
                                            $selectedPickupSlotLabel = collect($pickupSlots)->firstWhere('value', $selectedPickupSlot)?->label() ?? 'Select slot';
                                        @endphp

                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Quantity</label>
                                            <input
                                                name="quantity"
                                                type="number"
                                                min="1"
                                                max="{{ $availableQuantity }}"
                                                value="{{ old('quantity', 1) }}"
                                                class="form-control @error('quantity') is-invalid @enderror"
                                                required
                                            >
                                            <div class="form-text">Maximum reservable quantity: {{ $availableQuantity }}.</div>
                                            @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Pickup Date</label>
                                            <input name="pickup_date" type="text" data-flatpickr-date data-min-date="{{ now()->addDay()->toDateString() }}" value="{{ old('pickup_date') }}" class="form-control form-control-modern @error('pickup_date') is-invalid @enderror" required>
                                            @error('pickup_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Pickup Slot</label>
                                            <div class="dropdown" data-option-dropdown>
                                                <input type="hidden" name="pickup_slot" value="{{ $selectedPickupSlot }}" data-option-input required>
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

                                        <div class="mb-4">
                                            <label class="form-label fw-medium">Notes <span class="text-muted fw-normal small">(optional)</span></label>
                                            <textarea name="notes" rows="3" class="form-control" placeholder="Special handling notes or pickup reminders">{{ old('notes') }}</textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-bag-check me-1"></i>Reserve Item</button>
                                    </form>

                                    <hr>

                                    <div>
                                        <h6 class="fw-semibold">Save This Item to Cart</h6>
                                        <p class="small text-muted mb-3">Use the cart when you want to combine multiple items into a single reservation checkout.</p>

                                        <form method="POST" action="{{ route('cart.add') }}" data-confirm-action data-confirm-message='Type "confirm" to add this item to your cart.'>
                                            @csrf
                                            <input type="hidden" name="item_id" value="{{ $item->id }}">

                                            <div class="mb-3">
                                                <label class="form-label fw-medium">Cart Quantity</label>
                                                <input
                                                    name="quantity"
                                                    type="number"
                                                    min="1"
                                                    max="{{ $availableQuantity }}"
                                                    value="{{ old('quantity', 1) }}"
                                                    class="form-control @error('quantity') is-invalid @enderror"
                                                    required
                                                >
                                                <div class="form-text">Maximum addable quantity right now: {{ $availableQuantity }}.</div>
                                                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>

                                            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-cart-plus me-1"></i>Add to Cart</button>
                                        </form>
                                    </div>
                                @endif
                            @elseif ($isReservationLocked)
                                <div class="alert alert-warning mb-3">
                                    Your reservation button is locked right now because you already have <strong>{{ $pendingReservationCount }}</strong> active reservation{{ $pendingReservationCount > 1 ? 's' : '' }}.
                                    Extend, complete, or wait for one to expire before reserving another item.
                                </div>
                                <a href="{{ route('customer.reservations.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-bag-check me-1"></i>Manage My Reservations</a>
                            @else
                                <div class="alert alert-secondary mb-3">
                                    @if ($isReservedOut)
                                        This item is temporarily reserved by another customer. If payment is not completed before {{ $item->nextReservationAvailabilityAt()?->format('M d, Y g:i A') }}, it may become available again.
                                    @elseif ($item->hasScheduledRestock())
                                        This item is unavailable right now, but it is scheduled to return on {{ $item->restock_at?->format('M d, Y g:i A') }}.
                                    @else
                                        This item is currently unavailable.
                                    @endif
                                </div>
                                <a href="{{ route('items.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-search me-1"></i>Browse Similar Items</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="col-lg-6">
                <div class="card" id="reserve-card">
                    <div class="card-header fw-semibold"><i class="bi bi-bag-plus me-1"></i>Reserve Now</div>
                    <div class="card-body">
                        @if ($isAvailable)
                            @if (request()->boolean('reserve'))
                                <div class="alert alert-info mb-3">
                                    Sign in to reserve this item. After signing in, you’ll be brought back here to continue.
                                </div>
                            @else
                                <div class="alert alert-light border mb-3">
                                    Sign in to reserve this item in a few clicks.
                                </div>
                            @endif

                            <div class="d-grid gap-2">
                                <a href="{{ route('items.reserve-now', $item) }}" class="btn btn-primary">
                                    <i class="bi bi-bag-check me-1"></i>Reserve Now
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn btn-outline-custom">Create an Account</a>
                                @endif
                            </div>
                        @else
                            <div class="alert alert-secondary mb-3">
                                @if ($isReservedOut)
                                    This item is temporarily reserved by another customer. Please check back once it becomes available again.
                                @elseif ($item->hasScheduledRestock())
                                    This item is unavailable right now, but it is scheduled to return on {{ $item->restock_at?->format('M d, Y g:i A') }}.
                                @else
                                    This item is currently unavailable.
                                @endif
                            </div>
                            <a href="{{ route('items.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-search me-1"></i>Browse Similar Items</a>
                        @endif
                    </div>
                </div>
            </div>
        @endauth
    </div>
</x-app-layout>
