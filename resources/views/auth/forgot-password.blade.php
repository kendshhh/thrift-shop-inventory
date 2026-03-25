<x-guest-layout>
    <h4 class="mb-2 fw-bold">Forgot Password</h4>
    <p class="text-muted small mb-4">Enter your email and we will send you a reset link.</p>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email address')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="d-grid mb-3">
            <x-primary-button>{{ __('Send Reset Link') }}</x-primary-button>
        </div>

        <div class="text-center">
            <a class="text-muted small" href="{{ route('login') }}">Back to login</a>
        </div>
    </form>
</x-guest-layout>
