<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1 fw-bold"><i class="bi bi-grid-1x2-fill me-2"></i>Reservations Live Overview</h5>
                <p class="small text-muted mb-0">Top: item/system state. Bottom: user-level active holds.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.reservations.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-table me-1"></i>Full List
                </a>
                <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-outline-custom rounded-pill">
                    <i class="bi bi-bell me-1"></i>Admin Notifications
                </a>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            {{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            {{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card glass-card surface-section mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.reservations.overview') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="overview-search" class="form-label form-label-modern mb-1">Search</label>
                    <input
                        id="overview-search"
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        class="form-control form-control-modern"
                        placeholder="Item, customer, email, or reference"
                    >
                </div>
                <div class="col-6 col-lg-3">
                    <label for="overview-state" class="form-label form-label-modern mb-1">State</label>
                    <select id="overview-state" name="state" class="form-select form-control-modern">
                        <option value="all" @selected(($filters['state'] ?? 'all') === 'all')>All Reserved</option>
                        <option value="reserved" @selected(($filters['state'] ?? 'all') === 'reserved')>Reserved</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label form-label-modern mb-1">Urgency</label>
                    <div class="form-control form-control-modern d-flex align-items-center gap-2 py-2">
                        <input
                            id="overview-expiring"
                            type="checkbox"
                            name="expiring_soon"
                            value="1"
                            class="form-check-input mt-0"
                            @checked($filters['expiring_soon'] ?? false)
                        >
                        <label for="overview-expiring" class="small fw-semibold mb-0">Expiring Soon</label>
                    </div>
                </div>
                <div class="col-12 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill flex-fill">Apply</button>
                    <a href="{{ route('admin.reservations.overview') }}" class="btn btn-outline-secondary rounded-pill flex-fill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="card glass-card surface-section">
                <div class="card-body py-3">
                    <div class="small text-muted mb-1">Reserved</div>
                    <div class="fs-4 fw-bold text-warning">{{ $overviewCounts['reserved'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card glass-card surface-section">
                <div class="card-body py-3">
                    <div class="small text-muted mb-1">Expiring Soon</div>
                    <div class="fs-4 fw-bold text-danger">{{ $overviewCounts['expiring_soon'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card glass-card surface-section table-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Item Reservation Overview</strong>
            <span class="small text-muted">Reserved items only</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 overview-table">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Reserved By</th>
                            <th>Timer</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($itemRows as $row)
                            @php
                                $item = $row['item'];
                                $reservationRow = $row['reservation_row'];
                            @endphp
                            <tr class="overview-state-{{ $row['state'] }} {{ $row['is_expiring_soon'] ? 'overview-reserved-urgent' : '' }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($item->imageUrl())
                                            <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" class="inventory-thumb-sm" data-lightbox-image tabindex="0">
                                        @else
                                            <span class="inventory-thumb-fallback"><i class="bi bi-image"></i></span>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $item->name }}</div>
                                            <div class="small text-muted">Qty {{ $item->quantity }} | Free {{ $item->availableQuantity() }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-semibold">&#8369;{{ number_format((float) $item->price, 2) }}</td>
                            <td>
                                    @if ($row['state'] === 'available')
                                        <span class="overview-pill is-available">Available</span>
                                    @elseif ($row['state'] === 'reserved')
                                        <span class="overview-pill is-reserved">Reserved</span>
                                    @else
                                        <span class="overview-pill is-sold">Sold</span>
                                    @endif
                                    @if (!empty($row['source_label']))
                                        <div class="small text-muted mt-1">{{ $row['source_label'] }}</div>
                                    @endif
                                    @if (!empty($row['reservation_row']['cancel_pending']))
                                        <div class="mt-1"><span class="badge text-bg-warning"><i class="bi bi-x-circle me-1"></i>Cancel Requested</span></div>
                                    @endif
                                </td>
                                <td>{{ $row['reserved_by'] ?? 'N/A' }}</td>
                                <td>
                                    @if ($row['deadline'])
                                        <span class="countdown-chip {{ $row['is_expiring_soon'] ? 'is-urgent' : '' }}" data-countdown-to="{{ $row['deadline']->toIso8601String() }}" data-countdown-mode="live-second">
                                            <i class="bi bi-hourglass-split me-1"></i><span data-countdown-label>Loading...</span>
                                        </span>
                                    @else
                                        <span class="small text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($reservationRow)
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-custom rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end mt-2 border-0 shadow-lg glass-dropdown-menu">
                                                <li>
                                                    <form method="POST" action="{{ route('admin.reservations.remove-item', [$reservationRow['reservation_id'], $reservationRow['reservation_item_id']]) }}" data-confirm-action data-confirm-message='Type "confirm" to release this reserved item.'>
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="dropdown-item text-start w-100">Release</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" action="{{ route('admin.reservations.extend', $reservationRow['reservation_id']) }}" data-confirm-action data-confirm-message='Type "confirm" to extend this reservation timer.'>
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="dropdown-item text-start w-100">Extend Time</button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('admin.reservations.mark-sold', $reservationRow['reservation_id']) }}" data-confirm-action data-confirm-message='Type "confirm" to mark this reservation as sold.'>
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="dropdown-item text-success text-start w-100">Mark as Sold</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    @else
                                        <span class="small text-muted">No direct action</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No items matched your filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card glass-card surface-section">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Active Reservations by User</strong>
            <span class="small text-muted">Who is currently holding inventory</span>
        </div>
        <div class="card-body">
            @forelse ($userReservationGroups as $group)
                <div class="overview-user-group mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-2">
                        <div>
                            <div class="fw-semibold">{{ $group['user']?->name ?? 'Deleted User' }}</div>
                            <div class="small text-muted">{{ $group['user']?->email ?? 'No email' }}</div>
                            <div class="small text-muted">ID: {{ $group['user']?->id ?? 'N/A' }}</div>
                        </div>
                        <div class="text-end">
                            @php
                                $groupDeadline = $group['rows']->pluck('deadline')->filter()->sortBy(fn($deadline) => $deadline->getTimestamp())->first();
                            @endphp
                            <div class="small text-muted mb-1">Total Reservation Time Left</div>
                            @if ($groupDeadline)
                                <span class="countdown-chip" data-countdown-to="{{ $groupDeadline->toIso8601String() }}" data-countdown-mode="live-second">
                                    <i class="bi bi-stopwatch me-1"></i><span data-countdown-label>Loading...</span>
                                </span>
                            @else
                                <span class="small text-muted">N/A</span>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Reference</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Line Timer</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group['rows'] as $row)
                                    <tr>
                                        <td>{{ $row['item']?->name ?? 'Archived Item' }}</td>
                                        <td class="font-monospace">{{ $row['reservation']->reference }}</td>
                                        <td>&#8369;{{ number_format((float) $row['reservation_item']->line_total, 2) }}</td>
                                        <td>
                                            @if ($row['cancel_pending'])
                                                <span class="badge text-bg-warning"><i class="bi bi-x-circle me-1"></i>Cancel Requested</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($row['deadline'])
                                                <span class="countdown-chip" data-countdown-to="{{ $row['deadline']->toIso8601String() }}" data-countdown-mode="live-second">
                                                    <span data-countdown-label>Loading...</span>
                                                </span>
                                            @else
                                                <span class="small text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-custom rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end mt-2 border-0 shadow-lg glass-dropdown-menu">
                                                    <li>
                                                    <form method="POST" action="{{ route('admin.reservations.remove-item', [$row['reservation']->id, $row['reservation_item']->id]) }}" data-confirm-action data-confirm-message='Type "confirm" to remove this item from the reservation.'>
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="dropdown-item text-danger text-start w-100">Remove Item</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="fw-semibold">Subtotal: &#8369;{{ number_format((float) $group['subtotal'], 2) }}</div>
                        @if ($group['user'])
                            <form method="POST" action="{{ route('admin.reservations.cancel-all-for-user', $group['user']) }}" data-confirm-action data-confirm-message='Type "confirm" to cancel all active reservations for this user.'>
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Cancel All Reservations</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">No active reservation groups matched your filters.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
