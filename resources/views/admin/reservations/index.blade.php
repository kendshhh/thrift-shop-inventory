<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-check me-2"></i>Reservation Management</h5>
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-outline-custom"><i class="bi bi-bell me-1"></i>Admin Notifications</a>
        </div>
    </x-slot>

    @php
        $hasFilters = filled($filters['search'] ?? null)
            || filled($filters['status'] ?? null)
            || filled($filters['payment_status'] ?? null)
            || (($filters['sort'] ?? 'latest') !== 'latest');
    @endphp

    @include('partials.filter-bar', [
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
                'colClass' => 'col-12 col-lg-4',
            ],
            [
                'name' => 'status',
                'id' => 'reservation-status',
                'type' => 'select',
                'label' => 'Status',
                'value' => $filters['status'] ?? '',
                'colClass' => 'col-6 col-lg-2',
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
                'colClass' => 'col-6 col-lg-2',
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
                'colClass' => 'col-6 col-lg-3',
                'options' => [
                    ['value' => 'latest', 'label' => 'Newest'],
                    ['value' => 'pickup_asc', 'label' => 'Pickup: Earliest First'],
                    ['value' => 'pickup_desc', 'label' => 'Pickup: Latest First'],
                    ['value' => 'amount_desc', 'label' => 'Amount: High to Low'],
                    ['value' => 'amount_asc', 'label' => 'Amount: Low to High'],
                ],
            ],
        ],
        'actionsColClass' => 'col-12 col-lg-1 d-flex gap-2',
    ])

    <div class="card">
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
                            @endphp
                            <tr>
                                <td class="fw-medium font-monospace">
                                    {{ $reservation->reference }}
                                    @if (in_array($reservation->id, $newReservationIds ?? [], true))
                                        <x-status-badge class="ms-2" type="notification-event" value="new_reservation" label="New" />
                                    @endif
                                </td>
                                <td>{{ $reservation->user?->name ?? 'Deleted User' }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($previewItem?->imageUrl())
                                            <img src="{{ $previewItem->imageUrl() }}" alt="{{ $previewItem->name }}" class="inventory-thumb-sm" data-lightbox-image tabindex="0">
                                        @else
                                            <span class="inventory-thumb-fallback"><i class="bi bi-image"></i></span>
                                        @endif
                                        <div class="small">
                                            <div class="fw-medium text-dark">{{ $previewItem?->name ?? 'Archived Item' }}</div>
                                            <div class="text-muted">{{ $totalItemCount }} item{{ $totalItemCount > 1 ? 's' : '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><x-status-badge type="reservation" :value="$reservation->status->value" :label="$reservation->status->label()" /></td>
                                <td><x-status-badge type="payment" :value="$reservation->payment_status->value" :label="$reservation->payment_status->label()" /></td>
                                <td class="small">{{ optional($reservation->pickup_date)->format('M d, Y') }} {{ $reservation->pickup_slot }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
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
            <div class="card-footer bg-white">{{ $reservations->links() }}</div>
        @endif
    </div>
</x-app-layout>
