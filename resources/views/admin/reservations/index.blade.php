<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-check me-2"></i>Reservation Management</h5>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#filterModal-reservations" title="Open filters" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="{{ route('admin.reservations.overview') }}" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-activity me-1"></i>Live Overview</a>
                <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-outline-custom"><i class="bi bi-bell me-1"></i>Admin Notifications</a>
            </div>
        </div>
    </x-slot>

    @php
        $hasFilters = filled($filters['search'] ?? null)
            || filled($filters['status'] ?? null)
            || filled($filters['payment_status'] ?? null)
            || (($filters['sort'] ?? 'latest') !== 'latest');
    @endphp

    @include('partials.filter-modal', [
        'modalId' => 'filterModal-reservations',
        'action' => route('admin.reservations.index'),
        'resetUrl' => route('admin.reservations.index'),
        'hasFilters' => $hasFilters,
        'fields' => [
            [
                'name' => 'search',
                'id' => 'reservation-search',
                'label' => 'Search',
                'value' => $filters['search'] ?? '',
                'placeholder' => 'Reference, customer, email, or item name',
            ],
            [
                'name' => 'status',
                'id' => 'reservation-status',
                'type' => 'select',
                'label' => 'Status',
                'value' => $filters['status'] ?? '',
                'options' => collect([['value' => '', 'label' => 'All']])
                    ->merge(collect($statuses)->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]))
                    ->all(),
            ],
            [
                'name' => 'payment_status',
                'id' => 'reservation-payment-status',
                'type' => 'select',
                'label' => 'Payment',
                'value' => $filters['payment_status'] ?? '',
                'options' => collect([['value' => '', 'label' => 'All']])
                    ->merge(collect($paymentStatuses)->map(fn ($paymentStatus) => ['value' => $paymentStatus->value, 'label' => $paymentStatus->label()]))
                    ->all(),
            ],
            [
                'name' => 'sort',
                'id' => 'reservation-sort',
                'type' => 'select',
                'label' => 'Sort',
                'value' => $filters['sort'] ?? 'latest',
                'options' => [
                    ['value' => 'latest', 'label' => 'Newest'],
                    ['value' => 'pickup_asc', 'label' => 'Pickup: Earliest First'],
                    ['value' => 'pickup_desc', 'label' => 'Pickup: Latest First'],
                    ['value' => 'amount_desc', 'label' => 'Amount: High to Low'],
                    ['value' => 'amount_asc', 'label' => 'Amount: Low to High'],
                ],
            ],
        ],
    ])

    <div class="card glass-card surface-section table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Pickup</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservations as $reservation)
                            @php
                                $previewItem = $reservation->reservationItems->first()?->item;
                                $totalItemCount = $reservation->reservationItems->sum('quantity');
                                $hasPendingItemCancel = $reservation->reservationItems->contains('cancel_pending', true);
                                $pendingCancelCount = $reservation->reservationItems->where('cancel_pending', true)->count();
                            @endphp
                            <tr>
                                <td class="fw-medium font-monospace">
                                    {{ $reservation->reference }}
                                    @if (in_array($reservation->id, $newReservationIds ?? [], true))
                                        <x-status-badge class="ms-2" type="notification-event" value="new_reservation" label="New" />
                                    @endif
                                    @if ($hasPendingItemCancel)
                                        <span class="badge text-bg-warning ms-1" title="{{ $pendingCancelCount }} item cancellation request{{ $pendingCancelCount > 1 ? 's' : '' }} pending"><i class="bi bi-x-circle me-1"></i>{{ $pendingCancelCount }} Cancel Request{{ $pendingCancelCount > 1 ? 's' : '' }}</span>
                                    @endif
                                </td>
                                <td>{{ $reservation->user?->name ?? 'Deleted User' }}</td>
                                <td>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        @foreach ($reservation->reservationItems as $lineItem)
                                            <div class="d-flex align-items-center gap-1 mb-1">
                                                @if ($lineItem->item?->imageUrl())
                                                    <img src="{{ $lineItem->item->imageUrl() }}" alt="{{ $lineItem->item->name }}" class="inventory-thumb-xs" style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px;">
                                                @else
                                                    <span class="inventory-thumb-fallback" style="width: 24px; height: 24px;"><i class="bi bi-image"></i></span>
                                                @endif
                                                <span class="small fw-medium text-dark">{{ $lineItem->item?->name ?? 'Archived Item' }}</span>
                                                <span class="text-muted small">&times;{{ $lineItem->quantity }}</span>
                                            </div>
                                        @endforeach
                                        <div class="text-muted small">{{ $totalItemCount }} item{{ $totalItemCount > 1 ? 's' : '' }}</div>
                                    </div>
                                </td>
                                <td><x-status-badge type="reservation" :value="$reservation->status->value" :label="$reservation->status->label()" /></td>
                                <td><x-status-badge type="payment" :value="$reservation->payment_status->value" :label="$reservation->payment_status->label()" /></td>
                                <td class="small">{{ optional($reservation->pickup_date)->format('M d, Y') }} {{ $reservation->pickup_slot }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left-right"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No reservations found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($reservations->hasPages())
            <div class="card-footer bg-transparent border-0 px-3 pb-3 pt-0">{{ $reservations->links() }}</div>
        @endif
    </div>
</x-app-layout>
