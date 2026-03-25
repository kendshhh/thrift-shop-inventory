<x-app-layout>
    <x-slot name="header">
        <h5 class="mb-0 fw-bold"><i class="bi bi-speedometer2 me-2"></i>{{ __('Dashboard') }}</h5>
    </x-slot>

    <div class="card glass-card border-0">
        <div class="card-body p-4 p-lg-5">
            <p class="text-muted mb-0 fs-5">{{ __("You're logged in!") }}</p>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-semibold mb-1">Browse Inventory</h6>
                        <p class="text-muted small mb-0">See what is available now.</p>
                    </div>
                    <a href="{{ route('items.index') }}" class="btn btn-outline-custom btn-sm">Open</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-semibold mb-1">Manage Profile</h6>
                        <p class="text-muted small mb-0">Update account details and password.</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-custom btn-sm">Open</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
