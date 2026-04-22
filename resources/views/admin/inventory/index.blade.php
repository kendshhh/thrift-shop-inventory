<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <h5 class="mb-0 fw-bold"><i class="bi bi-boxes me-2"></i>Inventory</h5>
                @if (($archivedCount ?? 0) > 0)
                    <x-status-badge type="inventory" value="archived" :label="($archivedCount ?? 0).' Archived'" />
                @endif
            </div>
            <div class="action-row action-row-end">
                <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm {{ ($filters['status'] ?? '') === '' ? 'btn-outline-dark' : 'btn-outline-secondary' }}">All Items</a>
                <a href="{{ route('admin.inventory.index', ['status' => 'archived']) }}" class="btn btn-sm {{ ($filters['status'] ?? '') === 'archived' ? 'btn-outline-dark' : 'btn-outline-secondary' }}"><i class="bi bi-archive me-1"></i>Archived</a>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#filterModal-inventory" title="Open filters" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="{{ route('admin.inventory.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Item</a>
            </div>
        </div>
    </x-slot>

        @php
            $hasFilters = filled($filters['search'] ?? null)
                || filled($filters['category_id'] ?? null)
                || filled($filters['condition'] ?? null)
                || filled($filters['status'] ?? null)
                || (($filters['sort'] ?? 'latest') !== 'latest');
        @endphp

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @include('partials.filter-modal', [
        'modalId' => 'filterModal-inventory',
        'action' => route('admin.inventory.index'),
        'resetUrl' => route('admin.inventory.index'),
        'hasFilters' => $hasFilters,
        'fields' => [
            [
                'name' => 'search',
                'id' => 'inventory-search',
                'label' => 'Search',
                'value' => $filters['search'] ?? '',
                'placeholder' => 'Name, description, or slug',
            ],
            [
                'name' => 'category_id',
                'id' => 'inventory-category',
                'type' => 'select',
                'label' => 'Category',
                'value' => $filters['category_id'] ?? '',
                'options' => collect([['value' => '', 'label' => 'All']])
                    ->merge($categories->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->name]))
                    ->all(),
            ],
            [
                'name' => 'condition',
                'id' => 'inventory-condition',
                'type' => 'select',
                'label' => 'Condition',
                'value' => $filters['condition'] ?? '',
                'options' => collect([['value' => '', 'label' => 'All']])
                    ->merge(collect($conditions)->map(fn ($condition) => ['value' => $condition->value, 'label' => $condition->label()]))
                    ->all(),
            ],
            [
                'name' => 'status',
                'id' => 'inventory-status',
                'type' => 'select',
                'label' => 'Status',
                'value' => $filters['status'] ?? '',
                'options' => collect([['value' => '', 'label' => 'All']])
                    ->merge(collect($statuses)->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]))
                    ->all(),
            ],
            [
                'name' => 'sort',
                'id' => 'inventory-sort',
                'type' => 'select',
                'label' => 'Sort',
                'value' => $filters['sort'] ?? 'latest',
                'options' => [
                    ['value' => 'latest', 'label' => 'Newest'],
                    ['value' => 'name_asc', 'label' => 'Name: A to Z'],
                    ['value' => 'name_desc', 'label' => 'Name: Z to A'],
                    ['value' => 'price_asc', 'label' => 'Price: Low to High'],
                    ['value' => 'price_desc', 'label' => 'Price: High to Low'],
                    ['value' => 'quantity_desc', 'label' => 'Qty: High to Low'],
                    ['value' => 'quantity_asc', 'label' => 'Qty: Low to High'],
                ],
            ],
        ],
    ])

    <div class="card glass-card surface-section">
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
                                        <div>
                                            <div class="fw-medium d-flex flex-wrap align-items-center gap-2">
                                                <span>{{ $item->name }}</span>
                                                @if ($item->trashed())
                                                    <x-status-badge type="inventory" value="archived" label="Archived Record" />
                                                @endif
                                            </div>
                                            @if ($item->hasScheduledRestock())
                                                <div class="countdown-chip mt-1" data-countdown-to="{{ $item->restock_at?->toIso8601String() }}">
                                                    Restocks in <span data-countdown-label>Loading...</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary">{{ $item->category?->name ?? 'Uncategorized' }}</span></td>
                                <td>&#8369;{{ number_format((float) $item->price, 2) }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->reserved_quantity }}</td>
                                <td>
                                    <x-status-badge type="inventory" :value="$item->status->value" :label="$item->status->label()" />
                                </td>
                                <td class="text-end">
                                    <div class="action-row action-row-end">
                                        <a href="{{ route('admin.inventory.show', $item) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('admin.inventory.edit', $item) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        @if ($item->status === \App\Enums\ItemStatus::ARCHIVED)
                                            <form method="POST" action="{{ route('admin.inventory.unarchive', $item) }}" data-confirm-action data-confirm-message='Type "confirm" to restore this inventory item.'>
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i></button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.inventory.destroy', $item) }}" data-confirm-action data-confirm-message='Type "confirm" to archive this inventory item.'>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-warning"><i class="bi bi-archive"></i></button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.inventory.force-destroy', $item) }}" data-confirm-action data-confirm-message='Type "confirm" to permanently delete this inventory item.'>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">{{ ($filters['status'] ?? '') === 'archived' ? 'No archived inventory items found.' : 'No inventory items found.' }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($items->hasPages())
            <div class="card-footer bg-transparent border-0 px-3 pb-3 pt-0">{{ $items->links() }}</div>
        @endif
    </div>
</x-app-layout>
