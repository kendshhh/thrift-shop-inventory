<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1 fw-bold"><i class="bi bi-grid me-2"></i>Browse Items</h5>
                <p class="text-muted mb-0 small">Use filters to find thrift pieces that match your style and budget.</p>
            </div>
            <a href="{{ route('customer.reservations.index') }}" class="btn btn-sm btn-outline-custom"><i class="bi bi-bag-check me-1"></i>My Reservations</a>
        </div>
    </x-slot>

    @php
        $hasFilters = filled($filters['search'] ?? null)
            || filled($filters['category_id'] ?? null)
            || filled($filters['condition'] ?? null)
            || filled($filters['sort'] ?? null);
    @endphp

    <div class="card customer-filter-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('items.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="search" class="form-label fw-medium mb-1">Search</label>
                    <input id="search" name="search" class="form-control" placeholder="Item name or description" value="{{ $filters['search'] ?? '' }}">
                </div>

                <div class="col-6 col-lg-2">
                    <label for="category_id" class="form-label fw-medium mb-1">Category</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label for="condition" class="form-label fw-medium mb-1">Condition</label>
                    <select id="condition" name="condition" class="form-select">
                        <option value="">All</option>
                        @foreach ($conditions as $condition)
                            <option value="{{ $condition->value }}" @selected(($filters['condition'] ?? '') === $condition->value)>{{ $condition->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label for="sort" class="form-label fw-medium mb-1">Sort</label>
                    <select id="sort" name="sort" class="form-select">
                        <option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>Newest</option>
                        <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Price: Low to High</option>
                        <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Price: High to Low</option>
                    </select>
                </div>

                <div class="col-6 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel me-1"></i>Apply</button>
                    @if ($hasFilters)
                        <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0 small">
            Showing <strong>{{ $items->count() }}</strong> of <strong>{{ $items->total() }}</strong> available item{{ $items->total() === 1 ? '' : 's' }}.
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
                                <span class="badge bg-white border text-dark">{{ $item->condition->label() }}</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-2">{{ $item->name }}</h6>
                            <p class="text-muted small mb-3">{{ \Illuminate\Support\Str::limit($item->description ?: 'No description provided.', 95) }}</p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-bold text-success">&#8369;{{ number_format((float) $item->price, 2) }}</span>
                                <span class="text-muted small">{{ $item->availableQuantity() }} left</span>
                            </div>

                            <div class="btn btn-sm btn-primary w-100">View Details</div>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
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
