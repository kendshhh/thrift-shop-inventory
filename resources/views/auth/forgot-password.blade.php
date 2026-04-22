<x-guest-layout>
    <div class="auth-glass">
        <div class="text-center auth-copy-stack">
            <span class="status-badge status-badge-ready rounded-pill px-3 py-2 mb-3 d-inline-flex align-items-center">
                <i class="bi bi-envelope-paper me-2"></i>Password reset
            </span>
            <h4 class="mb-2 fw-bold">Forgot Password</h4>
            <p class="text-muted mb-0">Enter your email and we will send you a reset link.</p>
        </div>

        <x-auth-session-status class="mb-3" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-3">
                <x-input-label for="email" :value="__('Email address')" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
                <div class="form-text">Uppercase letters are accepted. The system will match your email in lowercase.</div>
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="d-grid mb-3">
                <x-primary-button>{{ __('Send Reset Link') }}</x-primary-button>
            </div>

            <div class="text-center">
                <a class="text-muted small" href="{{ route('auth.role-selection', ['intent' => 'login']) }}">Back to login</a>
            </div>
        </form>
    </div>
</x-guest-layout>
