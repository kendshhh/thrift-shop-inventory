<x-guest-layout>
    <x-auth-session-status class="mb-3" :status="session('status')" />

    @php
        $isAdmin = isset($role) && $role === 'admin';
        $roleLabel = $isAdmin ? 'Admin' : 'Customer';
    @endphp

    <div class="auth-glass">
        <div class="text-center auth-copy-stack">
            <span class="status-badge status-badge-ready rounded-pill px-3 py-2 mb-3 d-inline-flex align-items-center">
                <i class="bi {{ $isAdmin ? 'bi-shield-lock' : 'bi-bag-heart' }} me-2"></i>{{ $roleLabel }} Sign In
            </span>
            <h4 class="mb-2 fw-bold">Welcome back</h4>
            <p class="text-muted mb-0">
                {{ $isAdmin
                    ? 'Use the default admin account to access inventory, reservations, and store controls.'
                    : 'Sign in to continue browsing, reserving, and tracking your orders.' }}
            </p>
        </div>

        @if ($isAdmin)
            <div class="auth-inline-note small mb-3">
                Admin sign-in is only for admin credentials. Customer accounts must use the customer sign-in option.
            </div>
        @endif

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

            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label small" for="remember">
                        {{ __('Remember me') }}
                    </label>
                </div>
                @if (Route::has('password.request'))
                    <a class="text-muted small" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <div class="d-grid mb-3">
                <x-primary-button>{{ __('Sign in') }}</x-primary-button>
            </div>

            <div class="text-center">
                @if ($isAdmin)
                    <span class="text-muted small">Admin access uses the default system admin account.</span>
                @else
                    <a class="text-muted small" href="{{ route('register', ['role' => 'customer']) }}">{{ __('Don\'t have an account?') }}</a>
                @endif
            </div>

            @if ($isAdmin && app()->environment(['local', 'testing']))
                <div class="auth-inline-credentials small text-muted">
                    Default admin: <code>{{ env('DEFAULT_ADMIN_EMAIL', 'admin@thriftshop.local') }}</code> / <code>{{ env('DEFAULT_ADMIN_PASSWORD', '123') }}</code>
                </div>
            @endif
        </form>
    </div>
</x-guest-layout>
