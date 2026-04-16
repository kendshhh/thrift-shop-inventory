<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1 fw-bold"><i class="bi bi-house-heart me-2"></i>Customer Home</h5>
                <p class="text-muted mb-0 small">Discover available items and keep track of your pickup schedule.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-custom" href="{{ route('items.index') }}"><i class="bi bi-search me-1"></i>Browse Items</a>
                <a class="btn btn-sm btn-primary" href="{{ route('customer.reservations.index') }}"><i class="bi bi-bag-check me-1"></i>My Reservations</a>
            </div>
        </div>
    </x-slot>

    @php
        $statusBadgeClasses = [
            'pending' => 'bg-warning text-dark',
            'completed' => 'bg-success',
            'overdue' => 'bg-danger',
            'expired' => 'bg-secondary',
        ];
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="customer-stat-card customer-stat-sky">
                <span class="customer-stat-label">Total Reservations</span>
                <div class="customer-stat-value">{{ number_format((int) ($customerStats['total_reservations'] ?? 0)) }}</div>
                <span class="customer-stat-meta"><i class="bi bi-journal-check me-1"></i>All-time records</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="customer-stat-card customer-stat-amber">
                <span class="customer-stat-label">Pending</span>
                <div class="customer-stat-value">{{ number_format((int) ($customerStats['pending_reservations'] ?? 0)) }}</div>
                <span class="customer-stat-meta"><i class="bi bi-hourglass-split me-1"></i>Awaiting in-person payment</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="customer-stat-card customer-stat-red">
                <span class="customer-stat-label">Expiring in 24h</span>
                <div class="customer-stat-value">{{ number_format((int) ($customerStats['expiring_soon'] ?? 0)) }}</div>
                <span class="customer-stat-meta"><i class="bi bi-alarm me-1"></i>Pay before reservation expires</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="customer-stat-card customer-stat-green">
                <span class="customer-stat-label">Upcoming Pickups</span>
                <div class="customer-stat-value">{{ number_format((int) ($customerStats['upcoming_pickups'] ?? 0)) }}</div>
                <span class="customer-stat-meta"><i class="bi bi-calendar-event me-1"></i>Scheduled for pickup</span>
            </div>
        </div>
    </div>

    @if (($customerStats['expiring_soon'] ?? 0) > 0)
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
            <div>
                <strong>{{ $customerStats['expiring_soon'] }}</strong> reservation{{ (int) $customerStats['expiring_soon'] > 1 ? 's are' : ' is' }} expiring within 24 hours.
                Please visit the shop to settle payment and secure your items.
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card customer-surface h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-stars me-1"></i>Featured Items</strong>
                    <a class="btn btn-sm btn-outline-custom" href="{{ route('items.index') }}">Browse All <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @forelse ($featuredItems as $item)
                            <div class="col-sm-6">
                                <a href="{{ route('items.show', $item) }}" class="text-decoration-none text-reset">
                                    <div class="card h-100 item-card-modern">
                                        <div class="card-body">
                                            <div class="item-thumb-wrap mb-3">
                                                @if ($item->imageUrl())
                                                    <div class="image-frame">
                                                        <div class="image-frame-backdrop" style="background-image: url('{{ $item->imageUrl() }}');"></div>
                                                        <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" class="item-thumb-image image-frame-foreground" data-lightbox-image tabindex="0">
                                                    </div>
                                                @else
                                                    <div class="item-thumb-placeholder"><i class="bi bi-image"></i></div>
                                                @endif
                                            </div>

                                            <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                                <span class="badge text-bg-light border">{{ $item->category?->name ?? 'Uncategorized' }}</span>
                                                @if ($item->isReservedOut())
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Reserved</span>
                                                @elseif ($item->hasScheduledRestock())
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Restocking Soon</span>
                                                @else
                                                    <span class="badge bg-white border text-dark">{{ $item->condition->label() }}</span>
                                                @endif
                                            </div>
                                            <h6 class="fw-bold text-dark mb-2">{{ $item->name }}</h6>
                                            <p class="text-muted small mb-3">{{ \Illuminate\Support\Str::limit($item->description ?: 'No description available yet.', 90) }}</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-success fw-bold">&#8369;{{ number_format((float) $item->price, 2) }}</span>
                                                @if ($item->isAvailableForPurchase())
                                                    <span class="text-muted small"><i class="bi bi-check-circle me-1"></i>{{ $item->availableQuantity() }} available</span>
                                                @elseif ($item->isReservedOut())
                                                    <span class="countdown-chip" data-countdown-to="{{ $item->nextReservationAvailabilityAt()?->toIso8601String() }}">
                                                        Available in <span data-countdown-label>Loading...</span>
                                                    </span>
                                                @elseif ($item->hasScheduledRestock())
                                                    <span class="countdown-chip" data-countdown-to="{{ $item->restock_at?->toIso8601String() }}">
                                                        Restocks in <span data-countdown-label>Loading...</span>
                                                    </span>
                                                @else
                                                    <span class="text-muted small">Currently unavailable</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @empty
                            <div class="col">
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-box-seam fs-3 d-block mb-2"></i>
                                    No featured items yet.
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card customer-surface mt-4">
                <div class="card-header"><strong><i class="bi bi-tags me-1"></i>Browse by Category</strong></div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @forelse ($categories as $category)
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('items.index', ['category_id' => $category->id]) }}">{{ $category->name }}</a>
                        @empty
                            <p class="text-muted mb-0">No categories available.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card customer-surface h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-calendar2-check me-1"></i>Upcoming Pickups</strong>
                    <a href="{{ route('customer.reservations.index') }}" class="small text-decoration-none">View all</a>
                </div>
                <div class="card-body">
                    @forelse ($upcomingReservations as $reservation)
                        @php
                            $statusClass = $statusBadgeClasses[$reservation->status->value] ?? 'bg-secondary';
                            $isExpiringSoon = $reservation->status->value === 'pending'
                                && $reservation->expires_at
                                && $reservation->expires_at->lte(now()->addDay());
                        @endphp

                        <a href="{{ route('customer.reservations.show', $reservation) }}" class="customer-upcoming-item text-decoration-none text-reset">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                <span class="fw-semibold font-monospace small">{{ $reservation->reference }}</span>
                                <span class="badge rounded-pill {{ $statusClass }}">{{ $reservation->status->label() }}</span>
                            </div>
                            <div class="small text-muted mb-2">
                                <i class="bi bi-calendar-event me-1"></i>{{ optional($reservation->pickup_date)->format('M d, Y') }}
                                <span class="ms-1">{{ ucfirst(str_replace('_', ' ', (string) $reservation->pickup_slot)) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">{{ $reservation->reservationItems->sum('quantity') }} item{{ $reservation->reservationItems->sum('quantity') > 1 ? 's' : '' }}</span>
                                <span class="fw-semibold text-success small">&#8369;{{ number_format((float) $reservation->total_amount, 2) }}</span>
                            </div>

                            @if ($isExpiringSoon)
                                <div class="text-danger-emphasis small mt-2"><i class="bi bi-alarm me-1"></i>Expires {{ $reservation->expires_at->diffForHumans() }}</div>
                            @endif
                        </a>
                        @empty
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
                                No upcoming pickups yet.
                                <div class="mt-2">
                                    <a class="btn btn-sm btn-outline-custom" href="{{ route('items.index') }}">Reserve your first item</a>
                                </div>
                            </div>
                        @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
