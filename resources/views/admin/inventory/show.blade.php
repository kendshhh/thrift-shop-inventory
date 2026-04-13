<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
            <h5 class="mb-0 fw-bold">Inventory Item Details</h5>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            {{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $item->name }}</h5>
            <span class="badge fs-6 bg-{{ $item->status->value === 'active' ? 'success' : 'secondary' }}">{{ $item->status->label() }}</span>
        </div>
        <div class="card-body">
            <div class="row g-4 mb-4 align-items-start">
                <div class="col-lg-4">
                    @if ($item->imageUrl())
                        <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" class="inventory-detail-image" data-lightbox-image tabindex="0">
                    @else
                        <div class="inventory-detail-placeholder">
                            <i class="bi bi-image fs-2 d-block mb-2"></i>
                            No thumbnail uploaded
                        </div>
                    @endif
                </div>
                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Category</div>
                            <div class="fw-medium">{{ $item->category?->name ?? 'Uncategorized' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Price</div>
                            <div class="fw-medium">&#8369;{{ number_format((float) $item->price, 2) }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Total Quantity</div>
                            <div class="fw-medium">{{ $item->quantity }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Reserved</div>
                            <div class="fw-medium">{{ $item->reserved_quantity }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Condition</div>
                            <div class="fw-medium">{{ $item->condition->label() }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Available</div>
                            <div class="fw-medium text-success">{{ $item->availableQuantity() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($item->description)
                <div class="mb-4">
                    <div class="text-muted small mb-1">Description</div>
                    <p class="mb-0">{{ $item->description }}</p>
                </div>
            @endif

            <div class="d-flex gap-2">
                <a href="{{ route('admin.inventory.edit', $item) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
                <form method="POST" action="{{ route('admin.inventory.destroy', $item) }}" onsubmit="return confirm('Archive this item?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"><i class="bi bi-archive me-1"></i>Archive</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
