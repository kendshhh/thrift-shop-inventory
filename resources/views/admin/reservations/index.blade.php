<x-app-layout>
    <x-slot name="header">
        <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-check me-2"></i>Reservation Management</h5>
    </x-slot>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Pickup</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservations as $reservation)
                            <tr>
                                <td class="fw-medium font-monospace">{{ $reservation->reference }}</td>
                                <td>{{ $reservation->user?->name ?? 'Deleted User' }}</td>
                                <td><span class="badge bg-secondary">{{ $reservation->status->label() }}</span></td>
                                <td><span class="badge bg-info text-dark">{{ $reservation->payment_status->label() }}</span></td>
                                <td class="small">{{ optional($reservation->pickup_date)->format('M d, Y') }} {{ $reservation->pickup_slot }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No reservations found.</td>
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
