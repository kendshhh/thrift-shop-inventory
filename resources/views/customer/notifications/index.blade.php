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

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            {{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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
                                @if ($isUnread)
                                    <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle">Unread</span>
                                @else
                                    <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">Read</span>
                                @endif
                            </div>

                            <p class="text-muted mb-2">{{ $payload['message'] ?? 'Your reservation has been updated.' }}</p>

                            <div class="small text-muted d-flex flex-wrap gap-3">
                                <span><strong>Reference:</strong> {{ $payload['reference'] ?? 'N/A' }}</span>
                                <span><strong>Pickup:</strong> {{ $payload['pickup_date'] ?? 'N/A' }} ({{ $payload['pickup_slot'] ?? 'N/A' }})</span>
                                <span><strong>Status:</strong> {{ $payload['status_label'] ?? ucfirst((string) ($payload['status'] ?? 'updated')) }}</span>
                            </div>

                            @if (!empty($payload['notes']))
                                <div class="small text-muted mt-2"><strong>Shop note:</strong> {{ $payload['notes'] }}</div>
                            @endif

                            <div class="small text-muted mt-2">{{ $notification->created_at?->diffForHumans() }}</div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
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