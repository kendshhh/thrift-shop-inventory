<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-1 fw-bold"><i class="bi bi-speedometer2 me-2"></i>{{ data_get($branding, 'brand_name') }} Admin</h5>
                <p class="text-muted small mb-0">Command center for inventory and reservations.</p>
            </div>
            <span class="badge rounded-pill text-bg-light border">Live overview</span>
        </div>
    </x-slot>

    <section class="admin-nextgen-hero glass-card p-4 p-lg-5 mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="admin-chip mb-3 d-inline-flex align-items-center gap-2">
                    <i class="bi bi-stars"></i>
                    {{ data_get($branding, 'brand_name') }} Dashboard
                </span>
                <h2 class="display-6 fw-bold mb-3">Manage operations with clarity, speed, and control.</h2>
                <p class="text-muted mb-0">Monitor stock health, reservation flow, and account activity in one streamlined workspace.</p>
            </div>
            <div class="col-lg-4">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-primary">
                        <i class="bi bi-boxes me-2"></i>Manage Inventory
                    </a>
                    <a href="{{ route('admin.reservations.index') }}" class="btn btn-outline-custom">
                        <i class="bi bi-calendar-check me-2"></i>Review Reservations
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
                            <div class="quick-link-card h-100">
                                <span class="quick-link-icon"><i class="bi bi-graph-up-arrow"></i></span>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">Performance</h6>
                                    <p class="small text-muted mb-0">Keep response times and workflow quality high.</p>
                                </div>
                            </div>
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
