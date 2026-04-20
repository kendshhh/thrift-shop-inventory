<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1 fw-bold"><i class="bi bi-bell me-2"></i>Notifications</h5>
                <p class="text-muted mb-0 small">Track updates from the shop team about your reservations.</p>
            </div>
            <a href="{{ route('customer.reservations.index') }}" class="btn btn-sm btn-outline-custom"><i class="bi bi-bag-check me-1"></i>My Reservations</a>
        </div>
    </x-slot>

    @php
        $hasFilters = filled($filters['search'] ?? null)
            || filled($filters['read_state'] ?? null);
    @endphp

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            {{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @include('partials.filter-bar', [
        'action' => route('customer.notifications.index'),
        'resetUrl' => route('customer.notifications.index'),
        'hasFilters' => $hasFilters,
        'cardClass' => 'card customer-filter-card mb-4',
        'fields' => [
            [
                'name' => 'search',
                'id' => 'customer-notification-search',
                'label' => 'Search',
                'labelClass' => 'form-label fw-medium mb-1',
                'value' => $filters['search'] ?? '',
                'placeholder' => 'Title, message, reference, or status',
                'colClass' => 'col-12 col-lg-8',
            ],
            [
                'name' => 'read_state',
                'id' => 'customer-notification-state',
                'type' => 'select',
                'label' => 'State',
                'labelClass' => 'form-label fw-medium mb-1',
                'value' => $filters['read_state'] ?? '',
                'colClass' => 'col-6 col-lg-2',
                'options' => [
                    ['value' => '', 'label' => 'All'],
                    ['value' => 'unread', 'label' => 'Unread'],
                    ['value' => 'read', 'label' => 'Read'],
                ],
            ],
        ],
    ])

    <div class="card customer-surface">
        <div class="card-body p-0">
            @forelse ($notifications as $notification)
                @php
                    $payload = $notification->data;
                    $isUnread = $notification->read_at === null;
                @endphp

                <div class="border-bottom p-4 {{ $isUnread ? 'bg-light-subtle' : '' }}">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <h6 class="mb-0 fw-semibold">{{ $payload['title'] ?? 'Reservation update' }}</h6>
                                <x-status-badge type="notification-state" :value="$isUnread ? 'unread' : 'read'" :label="$isUnread ? 'Unread' : 'Read'" />
                            </div>

                            <p class="text-muted mb-2">{{ $payload['message'] ?? 'Your reservation has been updated.' }}</p>

                            <div class="small text-muted d-flex flex-wrap gap-3">
                                <span><strong>Reference:</strong> {{ $payload['reference'] ?? 'N/A' }}</span>
                                <span><strong>Pickup:</strong> {{ $payload['pickup_date'] ?? 'N/A' }} ({{ $payload['pickup_slot'] ?? 'N/A' }})</span>
                                <span>
                                    <strong>Status:</strong>
                                    <x-status-badge class="ms-1" type="reservation" :value="$payload['status'] ?? null" :label="$payload['status_label'] ?? ucfirst((string) ($payload['status'] ?? 'updated'))" />
                                </span>
                            </div>

                            @if (!empty($payload['notes']))
                                <div class="small text-muted mt-2"><strong>Shop note:</strong> {{ $payload['notes'] }}</div>
                            @endif

                            <div class="small text-muted mt-2">{{ $notification->created_at?->diffForHumans() }}</div>
                        </div>

                        <div class="action-row action-row-end">
                            @if ($isUnread)
                                <form method="POST" action="{{ route('customer.notifications.read', $notification->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Mark as Read</button>
                                </form>
                            @endif

                            <a href="{{ $payload['action_url'] ?? route('customer.reservations.index') }}" class="btn btn-sm btn-primary">
                                {{ $payload['action_label'] ?? 'View Reservation' }}
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5 px-4">
                    <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
                    No notifications yet.
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="card-footer bg-white">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-app-layout>