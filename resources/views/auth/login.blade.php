<x-guest-layout>
    <x-auth-session-status class="mb-3" :status="session('status')" />

    @php
        $isAdmin = isset($role) && $role === 'admin';
    @endphp

    <div class="auth-glass">
        <div class="text-center mb-4">
            <span class="status-badge status-badge-ready rounded-pill px-3 py-2 mb-3 d-inline-flex align-items-center">
                <i class="bi bi-bag-heart me-2"></i>Sign in
            </span>
            <h4 class="mb-2 fw-bold">Welcome back</h4>
            <p class="text-muted mb-0">Sign in to your account to continue.</p>
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
                <a class="text-muted small" href="{{ route('register', ['role' => 'customer']) }}">{{ __('Don\'t have an account?') }}</a>
            </div>
        </form>
    </div>

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
