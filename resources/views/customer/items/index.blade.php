<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1 fw-bold"><i class="bi bi-grid me-2"></i>Browse Items</h5>
                <p class="text-muted mb-0 small">Use filters to find thrift pieces that match your style and budget.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#filterModal-customer-items" title="Open filters" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="{{ route('customer.reservations.index') }}" class="btn btn-sm btn-outline-custom"><i class="bi bi-bag-check me-1"></i>My Reservations</a>
            </div>
        </div>
    </x-slot>

    @php
        $hasFilters = filled($filters['search'] ?? null)
            || filled($filters['category_id'] ?? null)
            || filled($filters['condition'] ?? null)
            || filled($filters['sort'] ?? null);
    @endphp

    @include('partials.filter-modal', [
        'modalId' => 'filterModal-customer-items',
        'action' => route('items.index'),
        'resetUrl' => route('items.index'),
        'hasFilters' => $hasFilters,
        'fields' => [
            [
                'name' => 'search',
                'id' => 'search',
                'label' => 'Search',
                'labelClass' => 'form-label fw-medium mb-1',
                'value' => $filters['search'] ?? '',
                'placeholder' => 'Item name or description',
            ],
            [
                'name' => 'category_id',
                'id' => 'category_id',
                'type' => 'select',
                'label' => 'Category',
                'labelClass' => 'form-label fw-medium mb-1',
                'value' => $filters['category_id'] ?? '',
                'options' => collect([['value' => '', 'label' => 'All']])
                    ->merge($categories->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->name]))
                    ->all(),
            ],
            [
                'name' => 'condition',
                'id' => 'condition',
                'type' => 'select',
                'label' => 'Condition',
                'labelClass' => 'form-label fw-medium mb-1',
                'value' => $filters['condition'] ?? '',
                'options' => collect([['value' => '', 'label' => 'All']])
                    ->merge(collect($conditions)->map(fn ($condition) => ['value' => $condition->value, 'label' => $condition->label()]))
                    ->all(),
            ],
            [
                'name' => 'sort',
                'id' => 'sort',
                'type' => 'select',
                'label' => 'Sort',
                'labelClass' => 'form-label fw-medium mb-1',
                'value' => $filters['sort'] ?? 'latest',
                'options' => [
                    ['value' => 'latest', 'label' => 'Newest'],
                    ['value' => 'price_asc', 'label' => 'Price: Low to High'],
                    ['value' => 'price_desc', 'label' => 'Price: High to Low'],
                ],
            ],
        ],
    ])

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0 small">
            Showing <strong>{{ $items->count() }}</strong> of <strong>{{ $items->total() }}</strong> browseable item{{ $items->total() === 1 ? '' : 's' }}.
        </p>
        @if ($hasFilters)
            <span class="badge bg-light text-dark border">Filtered Results</span>
        @endif
    </div>

    <div class="row g-3">
        @forelse ($items as $item)
            <div class="col-sm-6 col-xl-4">
                <a href="{{ route('items.show', $item) }}" class="text-decoration-none text-reset h-100 d-block">
                    <div class="card h-100 item-card-modern">
                        <div class="card-body">
                            <div class="item-thumb-wrap mb-3">
                                @if ($item->imageUrl())
                                    <div class="image-frame">
                                        <div class="image-frame-backdrop" style="background-image: url('{{ $item->imageUrl() }}');"></div>
                                        <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" class="item-thumb-image image-frame-foreground" data-lightbox-image tabindex="0">
                                    </div>
                                @else
                                    <div class="item-thumb-placeholder"><i class="bi bi-image"></i></div>
                                @endif
                            </div>

                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <span class="badge text-bg-light border">{{ $item->category?->name ?? 'Uncategorized' }}</span>
                                @if ($item->isReservedOut())
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Reserved</span>
                                @elseif ($item->hasScheduledRestock())
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Restocking Soon</span>
                                @else
                                    <span class="badge bg-white border text-dark">{{ $item->condition->label() }}</span>
                                @endif
                            </div>
                            <h6 class="fw-bold text-dark mb-2">{{ $item->name }}</h6>
                            <p class="text-muted small mb-3">{{ \Illuminate\Support\Str::limit($item->description ?: 'No description provided.', 95) }}</p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-bold text-success">&#8369;{{ number_format((float) $item->price, 2) }}</span>
                                @if ($item->isAvailableForPurchase())
                                    <span class="text-muted small">{{ $item->availableQuantity() }} left</span>
                                @elseif ($item->isReservedOut())
                                    <span class="countdown-chip" data-countdown-to="{{ $item->nextReservationAvailabilityAt()?->toIso8601String() }}">
                                        Available in <span data-countdown-label>Loading...</span>
                                    </span>
                                @elseif ($item->hasScheduledRestock())
                                    <span class="countdown-chip" data-countdown-to="{{ $item->restock_at?->toIso8601String() }}">
                                        Restocks in <span data-countdown-label>Loading...</span>
                                    </span>
                                @else
                                    <span class="text-muted small">Currently unavailable</span>
                                @endif
                            </div>

                            @if ($item->isReservedOut())
                                <p class="text-muted small mb-3">Reserved by another customer. It becomes available again if payment is not made before the timer ends.</p>
                            @endif

                            <div class="btn btn-sm {{ $item->isAvailableForPurchase() ? 'btn-primary' : 'btn-outline-secondary' }} w-100">
                                {{ $item->isAvailableForPurchase() ? 'View Details' : 'View Availability' }}
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="card empty-state-card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-search fs-2 d-block mb-2 text-muted"></i>
                        <h6 class="fw-semibold mb-2">No items matched your filters.</h6>
                        <p class="text-muted mb-3">Try a different category, condition, or keyword.</p>
                        <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">Clear Filters</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if ($items->hasPages())
        <div class="mt-4">{{ $items->links() }}</div>
    @endif
</x-app-layout>
