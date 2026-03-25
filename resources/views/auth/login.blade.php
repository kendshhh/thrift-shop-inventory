<x-guest-layout>
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <h4 class="mb-4 fw-bold text-center">Sign in to your account</h4>

    <form method="POST" action="{{ route('login') }}">
        @csrf
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
            <x-primary-button>{{ __('Log in') }}</x-primary-button>
        </div>
        <div class="text-center">
            @if (Route::has('password.request'))
                <a class="text-muted small" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
            @endif
            <span class="text-muted small mx-2">&bull;</span>
            @if (Route::has('register'))
                <a class="text-muted small" href="{{ route('register') }}">{{ __('Create an account') }}</a>
            @endif
        </div>
    </form>

    @if (app()->environment(['local', 'testing']))
        <hr class="mt-4">
        <div class="small text-muted">
            <div class="fw-semibold mb-1">Default accounts</div>
            <div>Admin: <code>{{ env('DEFAULT_ADMIN_EMAIL', 'admin@thriftshop.local') }}</code> / <code>{{ env('DEFAULT_ADMIN_PASSWORD', '123') }}</code></div>
            <div>Customer: <code>{{ env('DEFAULT_CUSTOMER_EMAIL', 'customer@thriftshop.local') }}</code> / <code>{{ env('DEFAULT_CUSTOMER_PASSWORD', '123') }}</code></div>
        </div>
    @endif
</x-guest-layout>
