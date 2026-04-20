<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-1">Everdarling Admin</h5>
                <p class="text-muted small mb-0">A simple overview of your pieces and reservations.</p>
            </div>
            <span class="badge rounded-pill text-bg-light border">Shop overview</span>
        </div>
    </x-slot>

    <section class="admin-nextgen-hero glass-card p-4 p-lg-5 mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="admin-chip mb-3 d-inline-flex align-items-center gap-2">
                    <i class="bi bi-stars"></i>
                    Everdarling Admin
                </span>
                <h2 class="display-6 fw-bold mb-3">A little space to keep everything in place.</h2>
                <p class="text-muted mb-0">Track your pieces, manage reservations, and stay on top of your shop—all in one simple, easy flow.</p>
            </div>
            <div class="col-lg-4">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.inventory.create') }}" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i>Add New Item
                    </a>
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-primary">
                        <i class="bi bi-boxes me-2"></i>View Inventory
                    </a>
                    <a href="{{ route('admin.reservations.index') }}" class="btn btn-outline-custom">
                        <i class="bi bi-calendar-check me-2"></i>Check Reservations
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <article class="metric-card metric-sky h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="metric-title">Total Inventory</span>
                    <span class="metric-icon"><i class="bi bi-boxes"></i></span>
                </div>
                <div class="metric-value">{{ $totalInventoryItems }}</div>
                <p class="metric-caption mb-0">All items in your catalog.</p>
            </article>
        </div>
        <div class="col-sm-6 col-xl-3">
            <article class="metric-card metric-green h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="metric-title">Active Reservations</span>
                    <span class="metric-icon"><i class="bi bi-calendar-check"></i></span>
                </div>
                <div class="metric-value">{{ $activeReservations }}</div>
                <p class="metric-caption mb-0">Orders currently in progress.</p>
            </article>
        </div>
        <div class="col-sm-6 col-xl-3">
            <article class="metric-card metric-red h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="metric-title">Overdue</span>
                    <span class="metric-icon"><i class="bi bi-exclamation-triangle"></i></span>
                </div>
                <div class="metric-value">{{ $overdueReservations }}</div>
                <p class="metric-caption mb-0">Reservations needing attention.</p>
            </article>
        </div>
        <div class="col-sm-6 col-xl-3">
            <article class="metric-card metric-amber h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="metric-title">Low Stock</span>
                    <span class="metric-icon"><i class="bi bi-bell"></i></span>
                </div>
                <div class="metric-value">{{ $lowStockItems }}</div>
                <p class="metric-caption mb-0">Items close to depletion.</p>
            </article>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
                    <h6 class="fw-bold mb-1">Payment Workflow</h6>
                    <p class="text-muted small mb-0">Monitor unpaid reservations and keep manual payment details current.</p>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="small text-muted text-uppercase fw-semibold mb-1">Awaiting Payment</div>
                                <div class="fs-3 fw-bold">{{ $awaitingPaymentReservations }}</div>
                                <div class="small text-muted">Active reservations still unpaid.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="small text-muted text-uppercase fw-semibold mb-1">Ready for Pickup</div>
                                <div class="fs-3 fw-bold">{{ $readyForPickupReservations }}</div>
                                <div class="small text-muted">Prepared orders waiting for in-person settlement.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="small text-muted text-uppercase fw-semibold mb-1">Completed Payments</div>
                                <div class="fs-3 fw-bold">{{ $completedPayments }}</div>
                                <div class="small text-muted">Reservations already closed out.</div>
                            </div>
                        </div>
                    </div>

                    @if ($paymentDetailCount === 0)
                        <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3 mb-0" role="alert">
                            <div>
                                <div class="fw-semibold">Payment details are not configured yet.</div>
                                <div class="small">Customers cannot see your manual payment instructions until at least one payment detail is added.</div>
                            </div>
                            <a href="{{ route('admin.payments.edit') }}" class="btn btn-sm btn-warning text-nowrap">
                                <i class="bi bi-qr-code me-1"></i>Set Up Payments
                            </a>
                        </div>
                    @else
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <div class="fw-semibold">{{ $paymentDetailCount }} payment detail {{ $paymentDetailCount === 1 ? 'entry' : 'entries' }} published</div>
                                <div class="small text-muted">Customers can review these details before pickup and on their reservation page.</div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('admin.payments.edit') }}" class="btn btn-outline-custom btn-sm">
                                    <i class="bi bi-qr-code me-1"></i>Manage Payments
                                </a>
                                <a href="{{ route('admin.reservations.index', ['payment_status' => 'pending']) }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-credit-card me-1"></i>Review Unpaid Reservations
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
                    <h6 class="fw-bold mb-1">Payment Actions</h6>
                    <p class="text-muted small mb-0">Shortcuts for the parts of the flow admins touch most.</p>
                </div>
                <div class="card-body pt-3">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.payments.edit') }}" class="quick-link-card text-decoration-none">
                            <span class="quick-link-icon"><i class="bi bi-qr-code"></i></span>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Payment Settings</h6>
                                <p class="small text-muted mb-0">Add bank details and QR codes customers can reference.</p>
                            </div>
                            <i class="bi bi-arrow-right short-arrow"></i>
                        </a>
                        <a href="{{ route('admin.reservations.index', ['payment_status' => 'pending']) }}" class="quick-link-card text-decoration-none">
                            <span class="quick-link-icon"><i class="bi bi-hourglass-split"></i></span>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Awaiting Payment</h6>
                                <p class="small text-muted mb-0">Jump straight to reservations that still need payment collection.</p>
                            </div>
                            <i class="bi bi-arrow-right short-arrow"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
                    <h6 class="fw-bold mb-1">Quick Actions</h6>
                    <p class="text-muted small mb-0">Move fast through your most important workflows.</p>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="{{ route('admin.inventory.index') }}" class="quick-link-card text-decoration-none h-100">
                                <span class="quick-link-icon"><i class="bi bi-boxes"></i></span>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Inventory</h6>
                                    <p class="small text-muted mb-0">Manage items and stock quantities.</p>
                                </div>
                                <i class="bi bi-arrow-right short-arrow"></i>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('admin.reservations.index') }}" class="quick-link-card text-decoration-none h-100">
                                <span class="quick-link-icon"><i class="bi bi-calendar-check"></i></span>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Reservations</h6>
                                    <p class="small text-muted mb-0">Review, update, and resolve bookings.</p>
                                </div>
                                <i class="bi bi-arrow-right short-arrow"></i>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('admin.inventory.create') }}" class="quick-link-card text-decoration-none h-100">
                                <span class="quick-link-icon"><i class="bi bi-plus-circle"></i></span>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Add New Item</h6>
                                    <p class="small text-muted mb-0">Create a new inventory listing without leaving the dashboard.</p>
                                </div>
                                <i class="bi bi-arrow-right short-arrow"></i>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('admin.users.index') }}" class="quick-link-card text-decoration-none h-100">
                                <span class="quick-link-icon"><i class="bi bi-people"></i></span>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Users</h6>
                                    <p class="small text-muted mb-0">Manage roles and account access.</p>
                                </div>
                                <i class="bi bi-arrow-right short-arrow"></i>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('admin.notifications.index') }}" class="quick-link-card text-decoration-none h-100">
                                <span class="quick-link-icon"><i class="bi bi-bell"></i></span>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Admin Alerts</h6>
                                    <p class="small text-muted mb-0">Review new reservations and customer request updates.</p>
                                </div>
                                <i class="bi bi-arrow-right short-arrow"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
                    <h6 class="fw-bold mb-1">Operational Snapshot</h6>
                    <p class="text-muted small mb-0">Real-time counts from current system state.</p>
                </div>
                <div class="card-body pt-3">
                    <ul class="admin-checklist list-unstyled mb-0">
                        <li>
                            <span class="label">Inventory Items</span>
                            <span class="value">{{ $totalInventoryItems }}</span>
                        </li>
                        <li>
                            <span class="label">Active Reservations</span>
                            <span class="value">{{ $activeReservations }}</span>
                        </li>
                        <li>
                            <span class="label">Overdue Cases</span>
                            <span class="value text-danger">{{ $overdueReservations }}</span>
                        </li>
                        <li>
                            <span class="label">Low Stock Alerts</span>
                            <span class="value text-warning-emphasis">{{ $lowStockItems }}</span>
                        </li>
                    </ul>
                    </div>
            </div>
        </div>
    </div>
</x-app-layout>
