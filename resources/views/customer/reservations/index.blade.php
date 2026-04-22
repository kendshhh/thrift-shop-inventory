<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1 fw-bold"><i class="bi bi-bag me-2"></i>My Reservations</h5>
                <p class="text-muted mb-0 small">Track your reservation status, payment state, and pickup schedule.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#filterModal-customer-reservations" title="Open filters" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-custom"><i class="bi bi-grid me-1"></i>Browse More Items</a>
            </div>
        </div>
    </x-slot>

    @php
        $hasFilters = filled($filters['status'] ?? null)
            || filled($filters['payment_status'] ?? null)
            || filled($filters['reference'] ?? null);
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl">
            <div class="customer-stat-card customer-stat-sky">
                <span class="customer-stat-label">Total</span>
                <div class="customer-stat-value">{{ number_format((int) ($stats['total'] ?? 0)) }}</div>
                <span class="customer-stat-meta">All reservations</span>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="customer-stat-card customer-stat-amber">
                <span class="customer-stat-label">Pending</span>
                <div class="customer-stat-value">{{ number_format((int) ($stats['pending'] ?? 0)) }}</div>
                <span class="customer-stat-meta">Awaiting payment</span>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="customer-stat-card customer-stat-sky">
                <span class="customer-stat-label">Ready for Pickup</span>
                <div class="customer-stat-value">{{ number_format((int) ($stats['ready'] ?? 0)) }}</div>
                <span class="customer-stat-meta">Prepared and awaiting payment</span>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="customer-stat-card customer-stat-red">
                <span class="customer-stat-label">Overdue</span>
                <div class="customer-stat-value">{{ number_format((int) ($stats['overdue'] ?? 0)) }}</div>
                <span class="customer-stat-meta">Needs immediate action</span>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="customer-stat-card customer-stat-green">
                <span class="customer-stat-label">Completed</span>
                <div class="customer-stat-value">{{ number_format((int) ($stats['completed'] ?? 0)) }}</div>
                <span class="customer-stat-meta">Successfully claimed</span>
            </div>
        </div>
    </div>

    @if (($stats['expiring_soon'] ?? 0) > 0)
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="bi bi-alarm-fill fs-5 mt-1"></i>
            <div>
                You have <strong>{{ $stats['expiring_soon'] }}</strong> pending reservation{{ (int) $stats['expiring_soon'] > 1 ? 's' : '' }} expiring within 24 hours.
            </div>
        </div>
    @endif

    @include('partials.filter-modal', [
        'modalId' => 'filterModal-customer-reservations',
        'action' => route('customer.reservations.index'),
        'resetUrl' => route('customer.reservations.index'),
        'hasFilters' => $hasFilters,
        'fields' => [
            [
                'name' => 'reference',
                'id' => 'reference',
                'label' => 'Reference',
                'labelClass' => 'form-label fw-medium mb-1',
                'value' => $filters['reference'] ?? '',
                'placeholder' => 'Search by reference',
            ],
            [
                'name' => 'status',
                'id' => 'status',
                'type' => 'select',
                'label' => 'Status',
                'labelClass' => 'form-label fw-medium mb-1',
                'value' => $filters['status'] ?? '',
                'options' => collect([['value' => '', 'label' => 'All']])
                    ->merge(collect($statuses)->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]))
                    ->all(),
            ],
            [
                'name' => 'payment_status',
                'id' => 'payment_status',
                'type' => 'select',
                'label' => 'Payment',
                'labelClass' => 'form-label fw-medium mb-1',
                'value' => $filters['payment_status'] ?? '',
                'options' => collect([['value' => '', 'label' => 'All']])
                    ->merge(collect($paymentStatuses)->map(fn ($paymentStatus) => ['value' => $paymentStatus->value, 'label' => $paymentStatus->label()]))
                    ->all(),
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
                            <th>Items</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Pickup</th>
                            <th>Total</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservations as $reservation)
                            @php
                                $lineItems = $reservation->reservationItems;
                                $totalItemCount = (int) $lineItems->sum('quantity');
                                $visibleLineItems = $lineItems->take(3);
                                $hiddenLineItemCount = max(0, $lineItems->count() - $visibleLineItems->count());
                                $isExpiringSoon = $reservation->status->value === 'pending'
                                    && $reservation->expires_at
                                    && $reservation->expires_at->lte(now()->addDay())
                                    && $reservation->expires_at->gte(now());
                            @endphp

                            <tr>
                                <td class="fw-medium font-monospace">{{ $reservation->reference }}</td>
                                <td>
                                    <div class="small">
                                        <div class="text-muted mb-1">{{ $totalItemCount }} item{{ $totalItemCount > 1 ? 's' : '' }}</div>
                                        <ul class="list-unstyled mb-0">
                                            @foreach ($visibleLineItems as $lineItem)
                                                <li class="text-dark">
                                                    {{ $lineItem->item?->name ?? 'Archived Item' }}
                                                    <span class="text-muted">x{{ $lineItem->quantity }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if ($hiddenLineItemCount > 0)
                                            <div class="text-muted">+{{ $hiddenLineItemCount }} more</div>
                                        @endif
                                    </div>
                                </td>
                                <td><x-status-badge type="reservation" :value="$reservation->status->value" :label="$reservation->status->label()" /></td>
                                <td><x-status-badge type="payment" :value="$reservation->payment_status->value" :label="$reservation->payment_status->label()" /></td>
                                <td class="small">
                                    <div>{{ optional($reservation->pickup_date)->format('M d, Y') }}</div>
                                    <div class="text-muted">{{ ucfirst(str_replace('_', ' ', (string) $reservation->pickup_slot)) }}</div>
                                    @if ($reservation->status->value === 'ready_for_pickup')
                                        <div class="text-info-emphasis">Ready for pickup and payment</div>
                                    @endif
                                    @if ($isExpiringSoon)
                                        <div class="text-danger-emphasis">Expires {{ $reservation->expires_at->diffForHumans() }}</div>
                                    @endif
                                </td>
                                <td class="fw-medium">&#8369;{{ number_format((float) $reservation->total_amount, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('customer.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left-right"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-bag-x fs-2 d-block mb-2"></i>
                                    @if ($hasFilters)
                                        No reservations match your filters.
                                        <div class="mt-2">
                                            <a href="{{ route('customer.reservations.index') }}" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
                                        </div>
                                    @else
                                        You do not have any reservations yet. <a href="{{ route('items.index') }}">Browse items</a>.
                                    @endif
                                </td>
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
