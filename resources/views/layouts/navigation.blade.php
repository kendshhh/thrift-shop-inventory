@php
    $user = Auth::user();
    $brandName = data_get($branding, 'brand_name', config('app.name', 'Thrift Shop'));
    $logoUrl = data_get($branding, 'logo_url');
@endphp

<nav class="navbar navbar-expand-lg navbar-light fixed-top navbar-modern">
    <div class="container app-container">
        <a class="navbar-brand fw-bold fs-4 d-inline-flex align-items-center" href="{{ $user ? route('dashboard') : url('/') }}">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $brandName }} logo" class="navbar-brand-logo">
            @endif
            <span>{{ $brandName }}</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            @if ($user)
                @if ($user->isAdmin())
                    <ul class="navbar-nav me-auto align-items-lg-center">
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('admin.dashboard') ? ' active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('admin.inventory.*') ? ' active' : '' }}" href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('admin.categories.*') ? ' active' : '' }}" href="{{ route('admin.categories.index') }}">Categories</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('admin.reservations.*') ? ' active' : '' }}" href="{{ route('admin.reservations.index') }}">Reservations</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('admin.users.*') ? ' active' : '' }}" href="{{ route('admin.users.index') }}">Users</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('admin.branding.*') ? ' active' : '' }}" href="{{ route('admin.branding.edit') }}">Branding</a></li>
                    </ul>
                @else
                    <ul class="navbar-nav me-auto align-items-lg-center">
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('customer.home') ? ' active' : '' }}" href="{{ route('customer.home') }}">Home</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('items.*') ? ' active' : '' }}" href="{{ route('items.index') }}">Browse</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('customer.reservations.*') ? ' active' : '' }}" href="{{ route('customer.reservations.index') }}">My Reservations</a></li>
                    </ul>
                @endif

                <ul class="navbar-nav ms-auto align-items-lg-center mt-3 mt-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle rounded-pill px-3 py-2 bg-white border shadow-sm" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>{{ $user->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end mt-2 border-0 shadow-lg">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Log Out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            @else
                <ul class="navbar-nav ms-auto align-items-lg-center mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link me-lg-2" href="{{ route('login') }}">Sign In</a></li>
                    @if (Route::has('register'))
                        <li class="nav-item"><a class="btn btn-primary btn-sm px-3" href="{{ route('register') }}">Register</a></li>
                    @endif
                </ul>
            @endif
        </div>
    </div>
</nav>
