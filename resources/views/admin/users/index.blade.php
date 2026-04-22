<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>User Management</h5>
            <div class="action-row action-row-end">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#filterModal-users" title="Open filters" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-funnel"></i>
                </button>
            </div>
        </div>
    </x-slot>

    @php
        $hasFilters = filled($filters['search'] ?? null)
            || filled($filters['role'] ?? null)
            || filled($filters['state'] ?? null)
            || (($filters['sort'] ?? 'latest') !== 'latest');
    @endphp

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            {{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @include('partials.filter-modal', [
        'modalId' => 'filterModal-users',
        'action' => route('admin.users.index'),
        'resetUrl' => route('admin.users.index'),
        'hasFilters' => $hasFilters,
        'fields' => [
            [
                'name' => 'search',
                'id' => 'user-search',
                'label' => 'Search',
                'value' => $filters['search'] ?? '',
                'placeholder' => 'Name, email, or phone',
            ],
            [
                'name' => 'role',
                'id' => 'user-role',
                'type' => 'select',
                'label' => 'Role',
                'value' => $filters['role'] ?? '',
                'options' => [
                    ['value' => '', 'label' => 'All'],
                    ['value' => 'admin', 'label' => 'Admin'],
                    ['value' => 'customer', 'label' => 'Customer'],
                ],
            ],
            [
                'name' => 'state',
                'id' => 'user-state',
                'type' => 'select',
                'label' => 'State',
                'value' => $filters['state'] ?? '',
                'options' => [
                    ['value' => '', 'label' => 'All'],
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'suspended', 'label' => 'Suspended'],
                ],
            ],
            [
                'name' => 'sort',
                'id' => 'user-sort',
                'type' => 'select',
                'label' => 'Sort',
                'value' => $filters['sort'] ?? 'latest',
                'options' => [
                    ['value' => 'latest', 'label' => 'Newest'],
                    ['value' => 'name_asc', 'label' => 'Name: A to Z'],
                    ['value' => 'name_desc', 'label' => 'Name: Z to A'],
                    ['value' => 'email_asc', 'label' => 'Email: A to Z'],
                    ['value' => 'email_desc', 'label' => 'Email: Z to A'],
                ],
            ],
        ],
    ])

    <div class="card glass-card surface-section table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="fw-medium">{{ $user->name }}</td>
                                <td class="text-muted small">{{ $user->email }}</td>
                                <td>
                                    <x-status-badge
                                        type="user-role"
                                        :value="$user->roles->pluck('name')->first()"
                                        :label="$user->roles->pluck('name')->implode(', ') ?: 'none'"
                                    />
                                </td>
                                <td>
                                    <x-status-badge
                                        type="user-state"
                                        :value="$user->is_active ? 'active' : 'suspended'"
                                        :label="$user->is_active ? 'Active' : 'Suspended'"
                                    />
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-custom rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            {{ $user->is_active ? 'Active' : 'Suspended' }}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end mt-2 border-0 shadow-lg glass-dropdown-menu">
                                            <li>
                                                <form method="POST" action="{{ route('admin.users.update', $user) }}" data-confirm-action data-confirm-message='Type "confirm" to update this user account status.'>
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="1">
                                                    <button type="submit" class="dropdown-item text-start w-100">
                                                        <span class="text-success">Active</span>
                                                        @if ($user->is_active)
                                                            <i class="bi bi-check2 ms-1"></i>
                                                        @endif
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('admin.users.update', $user) }}" data-confirm-action data-confirm-message='Type "confirm" to update this user account status.'>
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="0">
                                                    <button type="submit" class="dropdown-item text-start w-100">
                                                        <span class="text-danger">Suspended</span>
                                                        @if (!$user->is_active)
                                                            <i class="bi bi-check2 ms-1"></i>
                                                        @endif
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="card-footer bg-transparent border-0 px-3 pb-3 pt-0">{{ $users->links() }}</div>
        @endif
    </div>
</x-app-layout>
