<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1 fw-bold"><i class="bi bi-bell me-2"></i>Admin Notifications</h5>
                <p class="text-muted mb-0 small">Track new reservations and customer requests that need shop attention.</p>
            </div>
            <div class="action-row">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#filterModal-admin-notifications" title="Open filters" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="{{ route('admin.reservations.index') }}" class="btn btn-sm btn-outline-custom"><i class="bi bi-calendar-check me-1"></i>Reservations</a>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Mark All Read</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $hasFilters = filled($filters['search'] ?? null)
            || filled($filters['read_state'] ?? null)
            || filled($filters['event_type'] ?? null)
            || (($filters['sort'] ?? 'latest') !== 'latest');
    @endphp

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            {{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @include('partials.filter-modal', [
        'modalId' => 'filterModal-admin-notifications',
        'action' => route('admin.notifications.index'),
        'resetUrl' => route('admin.notifications.index'),
        'hasFilters' => $hasFilters,
        'fields' => [
            [
                'name' => 'search',
                'id' => 'admin-notification-search',
                'label' => 'Search',
                'value' => $filters['search'] ?? '',
                'placeholder' => 'Title, message, reference, customer, or item',
            ],
            [
                'name' => 'read_state',
                'id' => 'admin-notification-state',
                'type' => 'select',
                'label' => 'State',
                'value' => $filters['read_state'] ?? '',
                'options' => [
                    ['value' => '', 'label' => 'All'],
                    ['value' => 'unread', 'label' => 'Unread'],
                    ['value' => 'read', 'label' => 'Read'],
                ],
            ],
            [
                'name' => 'event_type',
                'id' => 'admin-notification-event',
                'type' => 'select',
                'label' => 'Type',
                'value' => $filters['event_type'] ?? '',
                'options' => [
                    ['value' => '', 'label' => 'All'],
                    ['value' => 'new_reservation', 'label' => 'New Reservation'],
                    ['value' => 'customer_request_submitted', 'label' => 'Customer Request'],
                    ['value' => 'status_changed', 'label' => 'Status Change'],
                    ['value' => 'low_stock', 'label' => 'Low Stock'],
                    ['value' => 'overdue_reservation', 'label' => 'Overdue'],
                ],
            ],
            [
                'name' => 'sort',
                'id' => 'admin-notification-sort',
                'type' => 'select',
                'label' => 'Sort',
                'value' => $filters['sort'] ?? 'latest',
                'options' => [
                    ['value' => 'latest', 'label' => 'Newest'],
                    ['value' => 'oldest', 'label' => 'Oldest'],
                ],
            ],
        ],
    ])

    <div class="card glass-card surface-section customer-surface">
        <div class="card-body p-0">
            @forelse ($notifications as $notification)
                @php
                    $payload = $notification->data;
                    $isUnread = $notification->read_at === null;
                @endphp

                <div class="list-row-card {{ $isUnread ? 'is-unread' : '' }}">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <h6 class="mb-0 fw-semibold">{{ $payload['title'] ?? 'Reservation alert' }}</h6>
                                <x-status-badge type="notification-state" :value="$isUnread ? 'unread' : 'read'" :label="$isUnread ? 'Unread' : 'Read'" />
                                <x-status-badge
                                    type="notification-event"
                                    :value="$payload['event_type'] ?? 'generic'"
                                    :label="match ($payload['event_type'] ?? null) {
                                        'new_reservation' => 'New Reservation',
                                        'low_stock' => 'Low Stock',
                                        'overdue_reservation' => 'Overdue',
                                        'customer_request_submitted' => 'Customer Request',
                                        'status_changed' => 'Status Change',
                                        default => 'Update',
                                    }"
                                />
                            </div>

                            <p class="text-muted mb-2">{{ $payload['message'] ?? 'A reservation update requires review.' }}</p>

                            <div class="small text-muted d-flex flex-wrap gap-3">
                                <span><strong>Reference:</strong> {{ $payload['reference'] ?? 'N/A' }}</span>
                                <span><strong>Customer:</strong> {{ $payload['customer_name'] ?? 'N/A' }}</span>
                                <span><strong>Pickup:</strong> {{ $payload['pickup_date'] ?? 'N/A' }} ({{ $payload['pickup_slot'] ?? 'N/A' }})</span>
                                <span>
                                    <strong>Status:</strong>
                                    <x-status-badge class="ms-1" type="reservation" :value="$payload['status'] ?? null" :label="$payload['status_label'] ?? ucfirst((string) ($payload['status'] ?? 'updated'))" />
                                </span>
                            </div>

                            @if (!empty($payload['customer_request_type']))
                                <div class="small text-muted mt-2"><strong>Request:</strong> {{ ucfirst(str_replace('_', ' ', (string) $payload['customer_request_type'])) }}</div>
                            @endif

                            <div class="small text-muted mt-2">{{ $notification->created_at?->diffForHumans() }}</div>
                        </div>

                        <div class="action-row action-row-end">
                            @if ($isUnread)
                                <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill">Mark as Read</button>
                                </form>
                            @endif

                            <a href="{{ $payload['action_url'] ?? route('admin.reservations.index') }}" class="btn btn-sm btn-primary rounded-pill">
                                {{ $payload['action_label'] ?? 'Review Reservation' }}
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5 px-4">
                    <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
                    No admin notifications yet.
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="card-footer bg-transparent border-0 px-3 pb-3 pt-0">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-app-layout>