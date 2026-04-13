<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-boxes me-2"></i>Inventory</h5>
            <a href="{{ route('admin.inventory.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Item</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Reserved</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($item->imageUrl())
                                            <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" class="inventory-thumb-sm" data-lightbox-image tabindex="0">
                                        @else
                                            <span class="inventory-thumb-fallback"><i class="bi bi-image"></i></span>
                                        @endif
                                        <span class="fw-medium">{{ $item->name }}</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary">{{ $item->category?->name ?? 'Uncategorized' }}</span></td>
                                <td>&#8369;{{ number_format((float) $item->price, 2) }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->reserved_quantity }}</td>
                                <td>
                                    <span class="badge bg-{{ $item->status->value === 'active' ? 'success' : 'secondary' }}">
                                        {{ $item->status->label() }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.inventory.show', $item) }}" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('admin.inventory.edit', $item) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No inventory items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($items->hasPages())
            <div class="card-footer bg-white">{{ $items->links() }}</div>
        @endif
    </div>
</x-app-layout>
