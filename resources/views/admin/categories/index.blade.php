<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-tags me-2"></i>Categories</h5>
            <div class="action-row action-row-end">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#filterModal-categories" title="Open filters" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="{{ route('admin.categories.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Category</a>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $hasFilters = filled($filters['search'] ?? null)
            || filled($filters['is_active'] ?? null)
            || (($filters['sort'] ?? 'latest') !== 'latest');
    @endphp

    @include('partials.filter-modal', [
        'modalId' => 'filterModal-categories',
        'action' => route('admin.categories.index'),
        'resetUrl' => route('admin.categories.index'),
        'hasFilters' => $hasFilters,
        'fields' => [
            [
                'name' => 'search',
                'id' => 'category-search',
                'label' => 'Search',
                'value' => $filters['search'] ?? '',
                'placeholder' => 'Search by name, slug, or description',
            ],
            [
                'name' => 'is_active',
                'id' => 'category-status',
                'type' => 'select',
                'label' => 'Status',
                'value' => $filters['is_active'] ?? '',
                'options' => [
                    ['value' => '', 'label' => 'All'],
                    ['value' => '1', 'label' => 'Active'],
                    ['value' => '0', 'label' => 'Inactive'],
                ],
            ],
            [
                'name' => 'sort',
                'id' => 'category-sort',
                'type' => 'select',
                'label' => 'Sort',
                'value' => $filters['sort'] ?? 'latest',
                'options' => [
                    ['value' => 'latest', 'label' => 'Newest'],
                    ['value' => 'name_asc', 'label' => 'Name: A to Z'],
                    ['value' => 'name_desc', 'label' => 'Name: Z to A'],
                    ['value' => 'items_desc', 'label' => 'Most Items'],
                    ['value' => 'items_asc', 'label' => 'Fewest Items'],
                ],
            ],
        ],
    ])

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th class="text-center">Items</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td class="fw-medium">{{ $category->name }}</td>
                                <td class="text-muted small font-monospace">{{ $category->slug }}</td>
                                <td class="text-center">{{ $category->items_count }}</td>
                                <td>
                                    <x-status-badge type="user-state" :value="$category->is_active ? 'active' : 'suspended'" :label="$category->is_active ? 'Active' : 'Inactive'" />
                                </td>
                                <td class="small text-muted">{{ $category->updated_at->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="d-inline" data-confirm-action data-confirm-message='Archive this category?' data-confirm-variant='danger' data-confirm-label='Archive'>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-archive"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No categories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($categories->hasPages())
            <div class="card-footer bg-white">{{ $categories->links() }}</div>
        @endif
    </div>
</x-app-layout>
