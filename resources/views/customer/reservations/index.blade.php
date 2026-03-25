<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1 fw-bold"><i class="bi bi-bag me-2"></i>My Reservations</h5>
                <p class="text-muted mb-0 small">Track your reservation status, payment state, and pickup schedule.</p>
            </div>
            <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-custom"><i class="bi bi-grid me-1"></i>Browse More Items</a>
        </div>
    </x-slot>

    @php
        $statusBadgeClasses = [
            'pending' => 'bg-warning text-dark',
            'completed' => 'bg-success',
            'overdue' => 'bg-danger',
            'expired' => 'bg-secondary',
        ];

        $paymentBadgeClasses = [
            'pending' => 'bg-warning text-dark',
            'completed' => 'bg-success',
            'overdue' => 'bg-danger',
        ];

        $hasFilters = filled($filters['status'] ?? null)
            || filled($filters['payment_status'] ?? null)
            || filled($filters['reference'] ?? null);
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="customer-stat-card customer-stat-sky">
                <span class="customer-stat-label">Total</span>
                <div class="customer-stat-value">{{ number_format((int) ($stats['total'] ?? 0)) }}</div>
                <span class="customer-stat-meta">All reservations</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="customer-stat-card customer-stat-amber">
                <span class="customer-stat-label">Pending</span>
                <div class="customer-stat-value">{{ number_format((int) ($stats['pending'] ?? 0)) }}</div>
                <span class="customer-stat-meta">Awaiting payment</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="customer-stat-card customer-stat-red">
                <span class="customer-stat-label">Overdue</span>
                <div class="customer-stat-value">{{ number_format((int) ($stats['overdue'] ?? 0)) }}</div>
                <span class="customer-stat-meta">Needs immediate action</span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
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

    <div class="card customer-filter-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('customer.reservations.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="reference" class="form-label fw-medium mb-1">Reference</label>
                    <input id="reference" name="reference" class="form-control" placeholder="Search by reference" value="{{ $filters['reference'] ?? '' }}">
                </div>

                <div class="col-6 col-lg-3">
                    <label for="status" class="form-label fw-medium mb-1">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-lg-3">
                    <label for="payment_status" class="form-label fw-medium mb-1">Payment</label>
                    <select id="payment_status" name="payment_status" class="form-select">
                        <option value="">All</option>
                        @foreach ($paymentStatuses as $paymentStatus)
                            <option value="{{ $paymentStatus->value }}" @selected(($filters['payment_status'] ?? '') === $paymentStatus->value)>{{ $paymentStatus->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel me-1"></i>Apply</button>
                    @if ($hasFilters)
                        <a href="{{ route('customer.reservations.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
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
                                $statusClass = $statusBadgeClasses[$reservation->status->value] ?? 'bg-secondary';
                                $paymentClass = $paymentBadgeClasses[$reservation->payment_status->value] ?? 'bg-secondary';
                                $isExpiringSoon = $reservation->status->value === 'pending'
                                    && $reservation->expires_at
                                    && $reservation->expires_at->lte(now()->addDay())
                                    && $reservation->expires_at->gte(now());
                            @endphp

                            <tr>
                                <td class="fw-medium font-monospace">{{ $reservation->reference }}</td>
                                <td class="small text-muted">{{ $reservation->reservationItems->sum('quantity') }} item{{ $reservation->reservationItems->sum('quantity') > 1 ? 's' : '' }}</td>
                                <td><span class="badge rounded-pill {{ $statusClass }}">{{ $reservation->status->label() }}</span></td>
                                <td><span class="badge rounded-pill {{ $paymentClass }}">{{ $reservation->payment_status->label() }}</span></td>
                                <td class="small">
                                    <div>{{ optional($reservation->pickup_date)->format('M d, Y') }}</div>
                                    <div class="text-muted">{{ ucfirst(str_replace('_', ' ', (string) $reservation->pickup_slot)) }}</div>
                                    @if ($isExpiringSoon)
                                        <div class="text-danger-emphasis">Expires {{ $reservation->expires_at->diffForHumans() }}</div>
                                    @endif
                                </td>
                                <td class="fw-medium">&#8369;{{ number_format((float) $reservation->total_amount, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('customer.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
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
            <div class="card-footer bg-white">{{ $reservations->links() }}</div>
        @endif
    </div>
</x-app-layout>
