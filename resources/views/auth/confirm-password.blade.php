<x-guest-layout>
    <div class="auth-glass">
        <div class="text-center auth-copy-stack">
            <span class="status-badge status-badge-ready rounded-pill px-3 py-2 mb-3 d-inline-flex align-items-center">
                <i class="bi bi-shield-check me-2"></i>Security check
            </span>
            <h4 class="mb-2 fw-bold">Confirm Password</h4>
            <p class="text-muted mb-0">This is a secure area. Please confirm your password before continuing.</p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="mb-4">
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="d-grid">
                <x-primary-button>{{ __('Confirm') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
