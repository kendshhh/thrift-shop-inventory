@php
    $user = Auth::user();
    $brandName = data_get($branding, 'brand_name', config('app.name', 'Everdarling'));
    $logoUrl = data_get($branding, 'logo_url');
    $brandLogo = $logoUrl ?: asset('images/everdarling_logo.png');
    $customerCartCount = $user && !$user->isAdmin()
        ? \App\Models\Cart::currentForUser($user->id)?->totalQuantity() ?? 0
        : 0;
    $unreadNotificationCount = $user && !$user->isAdmin()
        ? $user->unreadNotifications()->count()
        : 0;
    $adminUnreadNotifications = $user && $user->isAdmin()
        ? $user->unreadNotifications()->where('type', \App\Notifications\AdminReservationNotification::class)->get()
        : collect();
    $adminUnreadNotificationCount = $adminUnreadNotifications->count();
    $newReservationAlertCount = $adminUnreadNotifications
        ->filter(function ($notification) {
            return ($notification->data['event_type'] ?? null) === 'new_reservation';
        })
        ->count();
    $routeName = request()->route()?->getName();
    $authIntent = match ($routeName) {
        'register' => 'register',
        'login', 'password.request', 'password.reset' => 'login',
        'auth.role-selection' => 'login',
        default => null,
    };
@endphp

<nav class="navbar navbar-expand-lg navbar-light fixed-top navbar-modern">
    <div class="container app-container">
        <a class="navbar-brand fw-bold fs-4 d-inline-flex align-items-center" href="{{ $user ? route('dashboard') : url('/') }}">
            <img src="{{ $brandLogo }}" alt="{{ $brandName }} logo" class="navbar-brand-logo">
            <!-- Brand text replaced with logo -->
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse nav-glass-collapse" id="mainNav">
            @if ($user)
                @if ($user->isAdmin())
                    <ul class="navbar-nav me-auto align-items-lg-center">
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('admin.dashboard') ? ' active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('admin.inventory.*') ? ' active' : '' }}" href="{{ route('admin.inventory.index') }}">Inventory</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('admin.categories.*') ? ' active' : '' }}" href="{{ route('admin.categories.index') }}">Categories</a></li>
                        <li class="nav-item">
                            <a class="nav-link nav-link-with-badge mx-lg-2{{ request()->routeIs('admin.reservations.*') ? ' active' : '' }}" href="{{ route('admin.reservations.overview') }}">
                                <span>Reservations</span>
                                @if ($newReservationAlertCount > 0)
                                    <span class="badge rounded-pill bg-danger nav-link-badge">{{ $newReservationAlertCount > 99 ? '99+' : $newReservationAlertCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('admin.users.*') ? ' active' : '' }}" href="{{ route('admin.users.index') }}">Users</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('admin.branding.*') ? ' active' : '' }}" href="{{ route('admin.branding.edit') }}">Branding</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('admin.payments.*') ? ' active' : '' }}" href="{{ route('admin.payments.edit') }}">Payments</a></li>
                    </ul>
                @else
                    <ul class="navbar-nav me-auto align-items-lg-center">
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('customer.home') ? ' active' : '' }}" href="{{ route('customer.home') }}">Home</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('items.*') ? ' active' : '' }}" href="{{ route('items.index') }}">Browse</a></li>
                        <li class="nav-item"><a class="nav-link mx-lg-2{{ request()->routeIs('customer.reservations.*') ? ' active' : '' }}" href="{{ route('customer.reservations.index') }}">My Reservations</a></li>
                    </ul>
                @endif

                <ul class="navbar-nav ms-auto align-items-lg-center mt-3 mt-lg-0">
                    @if (isset($user) && $user && $user->isAdmin())
                        <li class="nav-item me-lg-2">
                            <a class="nav-link navbar-surface-pill position-relative px-3 py-2{{ request()->routeIs('admin.notifications.*') ? ' active' : '' }}" href="{{ route('admin.notifications.index') }}">
                                <i class="bi bi-bell"></i>
                                @if ($adminUnreadNotificationCount > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        {{ $adminUnreadNotificationCount > 99 ? '99+' : $adminUnreadNotificationCount }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    @else
                        <li class="nav-item me-lg-2">
                            <a class="nav-link navbar-surface-pill position-relative px-3 py-2{{ request()->routeIs('cart.*') ? ' active' : '' }}" href="{{ route('cart.index') }}">
                                <i class="bi bi-cart me-1"></i>Cart
                                @if ($customerCartCount > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        {{ $customerCartCount > 99 ? '99+' : $customerCartCount }}
                                    </span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item me-lg-2">
                            <a class="nav-link navbar-surface-pill position-relative px-3 py-2{{ request()->routeIs('customer.notifications.*') ? ' active' : '' }}" href="{{ route('customer.notifications.index') }}">
                                <i class="bi bi-bell me-1"></i>Notifications
                                @if ($unreadNotificationCount > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endif
                    <li class="nav-item dropdown">
                        <a class="nav-link navbar-surface-pill dropdown-toggle px-3 py-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>{{ $user->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end mt-2 border-0 shadow-lg glass-dropdown-menu">
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
                    <li class="nav-item">
                        <div class="auth-nav-switch">
                            <a
                                class="auth-nav-link {{ $authIntent === 'login' ? 'is-active' : '' }}"
                                href="{{ route('auth.role-selection', ['intent' => 'login']) }}"
                                @if ($authIntent === 'login') aria-current="page" @endif
                            >Sign In</a>
                            @if (Route::has('register'))
                                <a
                                    class="auth-nav-link {{ $authIntent === 'register' ? 'is-active' : '' }}"
                                    href="{{ route('register') }}"
                                    @if ($authIntent === 'register') aria-current="page" @endif
                                >Register</a>
                            @endif
                        </div>
                    </li>
                </ul>
            @endif
        </div>
    </div>
</nav>
