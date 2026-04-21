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

    <section class="admin-nextgen-hero glass-card surface-section p-4 p-lg-5 mb-4">
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
                    <a href="{{ route('admin.inventory.create') }}" class="btn btn-success rounded-pill">
                        <i class="bi bi-plus-circle me-2"></i>Add New Item
                    </a>
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-primary rounded-pill">
                        <i class="bi bi-boxes me-2"></i>View Inventory
                    </a>
                    <a href="{{ route('admin.reservations.index') }}" class="btn btn-outline-custom rounded-pill">
                        <i class="bi bi-calendar-check me-2"></i>Check Reservations
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <article class="customer-stat-card customer-stat-sky admin-stat-card h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                    <span class="metric-title">Total Inventory</span>
                    <span class="metric-icon"><i class="bi bi-boxes"></i></span>
                </div>
                <div class="metric-value">{{ $totalInventoryItems }}</div>
                <p class="metric-caption mb-0"><i class="bi bi-boxes me-1"></i>All items in your catalog.</p>
            </article>
        </div>
        <div class="col-sm-6 col-xl-3">
            <article class="customer-stat-card customer-stat-green admin-stat-card h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                    <span class="metric-title">Active Reservations</span>
                    <span class="metric-icon"><i class="bi bi-calendar-check"></i></span>
                </div>
                <div class="metric-value">{{ $activeReservations }}</div>
                <p class="metric-caption mb-0"><i class="bi bi-journal-check me-1"></i>Orders currently in progress.</p>
            </article>
        </div>
        <div class="col-sm-6 col-xl-3">
            <article class="customer-stat-card customer-stat-red admin-stat-card h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                    <span class="metric-title">Overdue</span>
                    <span class="metric-icon"><i class="bi bi-exclamation-triangle"></i></span>
                </div>
                <div class="metric-value">{{ $overdueReservations }}</div>
                <p class="metric-caption mb-0"><i class="bi bi-alarm me-1"></i>Reservations needing attention.</p>
            </article>
        </div>
        <div class="col-sm-6 col-xl-3">
            <article class="customer-stat-card customer-stat-amber admin-stat-card h-100">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                    <span class="metric-title">Low Stock</span>
                    <span class="metric-icon"><i class="bi bi-bell"></i></span>
                </div>
                <div class="metric-value">{{ $lowStockItems }}</div>
                <p class="metric-caption mb-0"><i class="bi bi-bell me-1"></i>Items close to depletion.</p>
            </article>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card glass-card surface-section customer-surface h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
                    <h6 class="fw-bold mb-1">Payment Workflow</h6>
                    <p class="text-muted small mb-0">Monitor unpaid reservations and keep manual payment details current.</p>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="data-grid-card">
                                <div class="small text-muted text-uppercase fw-semibold mb-1">Awaiting Payment</div>
                                <div class="fs-3 fw-bold">{{ $awaitingPaymentReservations }}</div>
                                <div class="small text-muted">Active reservations still unpaid.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="data-grid-card">
                                <div class="small text-muted text-uppercase fw-semibold mb-1">Ready for Pickup</div>
                                <div class="fs-3 fw-bold">{{ $readyForPickupReservations }}</div>
                                <div class="small text-muted">Prepared orders waiting for in-person settlement.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="data-grid-card">
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
            <div class="card glass-card surface-section customer-surface h-100">
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
        <div class="col-12">
            <div class="card glass-card surface-section customer-surface h-100">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h6 class="fw-bold mb-1">Quick Actions</h6>
                            <p class="text-muted small mb-0">Move fast through your most important workflows without leaving the dashboard.</p>
                        </div>
                        <span class="admin-chip d-inline-flex align-items-center gap-2 mb-0">
                            <i class="bi bi-lightning-charge"></i>
                            5 shortcuts ready
                        </span>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-6 col-xl-4">
                            <a href="{{ route('admin.inventory.index') }}" class="quick-link-card text-decoration-none h-100">
                                <span class="quick-link-icon"><i class="bi bi-boxes"></i></span>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Inventory</h6>
                                    <p class="small text-muted mb-0">Manage items and stock quantities.</p>
                                </div>
                                <i class="bi bi-arrow-right short-arrow"></i>
                            </a>
                        </div>
                        <div class="col-md-6 col-xl-4">
                            <a href="{{ route('admin.reservations.index') }}" class="quick-link-card text-decoration-none h-100">
                                <span class="quick-link-icon"><i class="bi bi-calendar-check"></i></span>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Reservations</h6>
                                    <p class="small text-muted mb-0">Review, update, and resolve bookings.</p>
                                </div>
                                <i class="bi bi-arrow-right short-arrow"></i>
                            </a>
                        </div>
                        <div class="col-md-6 col-xl-4">
                            <a href="{{ route('admin.inventory.create') }}" class="quick-link-card text-decoration-none h-100">
                                <span class="quick-link-icon"><i class="bi bi-plus-circle"></i></span>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Add New Item</h6>
                                    <p class="small text-muted mb-0">Create a new inventory listing without leaving the dashboard.</p>
                                </div>
                                <i class="bi bi-arrow-right short-arrow"></i>
                            </a>
                        </div>
                        <div class="col-md-6 col-xl-4">
                            <a href="{{ route('admin.users.index') }}" class="quick-link-card text-decoration-none h-100">
                                <span class="quick-link-icon"><i class="bi bi-people"></i></span>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Users</h6>
                                    <p class="small text-muted mb-0">Manage roles and account access.</p>
                                </div>
                                <i class="bi bi-arrow-right short-arrow"></i>
                            </a>
                        </div>
                        <div class="col-md-6 col-xl-4">
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
    </div>
</x-app-layout>
