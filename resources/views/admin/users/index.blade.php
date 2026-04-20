<x-app-layout>
    <x-slot name="header">
        <h5 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>User Management</h5>
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

    @include('partials.filter-bar', [
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
                'colClass' => 'col-12 col-lg-4',
            ],
            [
                'name' => 'role',
                'id' => 'user-role',
                'type' => 'select',
                'label' => 'Role',
                'value' => $filters['role'] ?? '',
                'colClass' => 'col-6 col-lg-2',
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
                'colClass' => 'col-6 col-lg-2',
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
                'colClass' => 'col-6 col-lg-3',
                'options' => [
                    ['value' => 'latest', 'label' => 'Newest'],
                    ['value' => 'name_asc', 'label' => 'Name: A to Z'],
                    ['value' => 'name_desc', 'label' => 'Name: Z to A'],
                    ['value' => 'email_asc', 'label' => 'Email: A to Z'],
                    ['value' => 'email_desc', 'label' => 'Email: Z to A'],
                ],
            ],
        ],
        'actionsColClass' => 'col-12 col-lg-1 d-flex gap-2',
    ])

    <div class="card">
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
                                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="action-row action-row-end">
                                        @csrf
                                        @method('PATCH')
                                        <select name="is_active" class="form-select form-select-sm" style="width:auto">
                                            <option value="1" @selected($user->is_active)>Active</option>
                                            <option value="0" @selected(!$user->is_active)>Suspended</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                    </form>
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
            <div class="card-footer bg-white">{{ $users->links() }}</div>
        @endif
    </div>
</x-app-layout>
