<x-guest-layout>
    <x-auth-session-status class="mb-3" :status="session('status')" />

    @php
        $isAdmin = $role === 'admin';
    @endphp

    <div class="text-center mb-4">
        <span class="status-badge {{ $isAdmin ? 'status-badge-admin' : 'status-badge-ready' }} rounded-pill px-3 py-2 mb-3 d-inline-flex align-items-center">
            <i class="bi {{ $isAdmin ? 'bi-shield-lock' : 'bi-bag-heart' }} me-2"></i>{{ $isAdmin ? 'Admin access' : 'Customer access' }}
        </span>
        <h4 class="mb-2 fw-bold">{{ $isAdmin ? 'Administrator sign in' : 'Sign in to your account' }}</h4>
        <p class="text-muted mb-0">
            {{ $isAdmin
                ? 'Use your issued administrator credentials to access inventory and operations.'
                : 'Continue to browse finds, manage reservations, and track your activity.' }}
        </p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <input type="hidden" name="role" value="{{ $role }}">

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <div class="mb-3">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <div class="mb-3 form-check">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
            <label for="remember_me" class="form-check-label">{{ __('Remember me') }}</label>
        </div>
        <div class="d-grid mb-3">
            <x-primary-button class="justify-content-center">{{ __('Log in') }}</x-primary-button>
        </div>
        <div class="text-center">
            @if (Route::has('password.request'))
                <a class="text-muted small" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
            @endif
            <span class="text-muted small mx-2">&bull;</span>
            @if ($isAdmin)
                <a class="text-muted small" href="{{ route('auth.role-selection', ['intent' => 'login']) }}">{{ __('Choose a different role') }}</a>
            @elseif (Route::has('register'))
                <a class="text-muted small" href="{{ route('register') }}">{{ __('Create an account') }}</a>
            @endif
        </div>
    </form>

    @if (app()->environment(['local', 'testing']))
        <hr class="mt-4">
        <div class="small text-muted">
            <div class="fw-semibold mb-1">Default accounts</div>
            @if ($isAdmin)
                <div>Admin: <code>{{ env('DEFAULT_ADMIN_EMAIL', 'admin@thriftshop.local') }}</code> / <code>{{ env('DEFAULT_ADMIN_PASSWORD', '123') }}</code></div>
            @else
                <div>Customer: <code>{{ env('DEFAULT_CUSTOMER_EMAIL', 'customer@thriftshop.local') }}</code> / <code>{{ env('DEFAULT_CUSTOMER_PASSWORD', '123') }}</code></div>
            @endif
        </div>
    @endif
</x-guest-layout>
