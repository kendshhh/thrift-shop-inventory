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
            <a href="{{ route('customer.reservations.index') }}" class="btn btn-sm btn-outline-custom"><i class="bi bi-bag-check me-1"></i>My Reservations</a>
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
                        @elseif ($item->hasScheduledRestock())
                            <span class="countdown-chip" data-countdown-to="{{ $item->restock_at?->toIso8601String() }}">
                                Restocks in <span data-countdown-label>Loading...</span>
                            </span>
                            <div class="mt-2">Expected on {{ $item->restock_at?->format('M d, Y g:i A') }}</div>
                        @else
                            <span><i class="bi bi-x-circle text-secondary me-1"></i>Currently unavailable</span>
                        @endif
                    </div>
                    <p class="text-muted mb-4">{{ $item->description ?: 'No description provided.' }}</p>

                    <div class="customer-policy-note">
                        <h6 class="fw-semibold mb-2">Reservation Notes</h6>
                        <ul class="mb-0 small text-muted ps-3">
                            <li>Reservations stay active for 48 hours while awaiting payment.</li>
                            <li>Payment is processed in person at pickup.</li>
                            <li>Stock is reserved immediately after you submit.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        @auth
            @if (auth()->user()->hasRole('customer'))
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header fw-semibold"><i class="bi bi-bag-plus me-1"></i>Reserve This Item</div>
                        <div class="card-body">
                            @if ($isAvailable)
                                <form method="POST" action="{{ route('customer.reservations.store') }}">
                                    @csrf
                                    <input type="hidden" name="item_id" value="{{ $item->id }}">

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
                                        <input name="pickup_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('pickup_date') }}" class="form-control @error('pickup_date') is-invalid @enderror" required>
                                        @error('pickup_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-medium">Pickup Slot</label>
                                        <select name="pickup_slot" class="form-select @error('pickup_slot') is-invalid @enderror" required>
                                            @foreach (\App\Enums\PickupSlot::cases() as $slot)
                                                <option value="{{ $slot->value }}" @selected(old('pickup_slot') === $slot->value)>{{ $slot->label() }}</option>
                                            @endforeach
                                        </select>
                                        @error('pickup_slot') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-medium">Notes <span class="text-muted fw-normal small">(optional)</span></label>
                                        <textarea name="notes" rows="3" class="form-control" placeholder="Special handling notes or pickup reminders">{{ old('notes') }}</textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-bag-check me-1"></i>Reserve Item</button>
                                </form>
                            @else
                                <div class="alert alert-secondary mb-3">
                                    @if ($item->hasScheduledRestock())
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
        @endauth
    </div>
</x-app-layout>
