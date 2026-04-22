<x-guest-layout>
    <div class="auth-glass">
        <div class="text-center auth-copy-stack">
            <span class="status-badge status-badge-ready rounded-pill px-3 py-2 mb-3 d-inline-flex align-items-center">
                <i class="bi bi-key me-2"></i>Reset password
            </span>
            <h4 class="mb-2 fw-bold">Set a new password</h4>
            <p class="text-muted mb-0">Choose a new password for your account and confirm it below.</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="mb-3">
                <x-input-label for="email" :value="__('Email address')" />
                <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                <div class="form-text">Uppercase letters are accepted. The system will match your email in lowercase.</div>
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="mb-3">
                <x-input-label for="password" :value="__('New Password')" />
                <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                <div class="form-text">Password must be at least 8 characters long.</div>
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="mb-4">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" />
            </div>

            <div class="d-grid">
                <x-primary-button>{{ __('Reset Password') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
