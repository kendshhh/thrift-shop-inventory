<x-guest-layout>
    <div class="auth-glass">
        <div class="text-center mb-4">
            <span class="status-badge status-badge-ready rounded-pill px-3 py-2 mb-3 d-inline-flex align-items-center">
                <i class="bi bi-bag-heart me-2"></i>Customer registration
            </span>
            <h4 class="mb-2 fw-bold">Create an account</h4>
            <p class="text-muted mb-0">Set up your customer profile to browse finds, reserve pieces, and receive order updates.</p>
        </div>


        <form method="POST" action="{{ route('register') }}">
            @csrf
            <input type="hidden" name="role" value="customer">

            <div class="mb-3">
                <x-input-label for="name" :value="__('Full Name')" />
                <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="mb-3">
                <x-input-label for="email" :value="__('Email address')" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <div class="form-text">You can type uppercase or lowercase letters. We will save the email in lowercase.</div>
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="mb-3">
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                <div class="form-text">Password must be at least 8 characters long.</div>
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="mb-4">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" />
            </div>

            <div class="auth-legal-inline mb-4">
                <div class="form-check auth-consent-check mb-0">
                    <input
                        id="legal_consent"
                        type="checkbox"
                        class="form-check-input"
                        name="legal_consent"
                        value="1"
                        aria-describedby="legal-consent-copy"
                        {{ old('legal_consent') ? 'checked' : '' }}
                    >
                    <div id="legal-consent-copy" class="form-check-label small text-muted">
                        <span class="mb-0">I agree to the</span>
                        <button
                            type="button"
                            class="auth-legal-link"
                            aria-haspopup="dialog"
                            aria-controls="privacy-notice-modal"
                            data-legal-modal-trigger="privacy-notice-modal"
                        >Privacy Notice</button>
                        and
                        <button
                            type="button"
                            class="auth-legal-link"
                            aria-haspopup="dialog"
                            aria-controls="terms-modal"
                            data-legal-modal-trigger="terms-modal"
                        >Terms and Conditions</button>.
                    </div>
                </div>
                <x-input-error :messages="$errors->get('legal_consent')" />
            </div>

            <div class="d-grid mb-3">
                <x-primary-button>{{ __('Create Account') }}</x-primary-button>
            </div>

            <div class="text-center">
                <a class="text-muted small" href="{{ route('login', ['role' => 'customer']) }}">{{ __('Already have an account?') }}</a>
            </div>
        </form>
    </div>
    <x-slot name="overlays">
        <div class="auth-legal-modal-shell" aria-hidden="true">
            <div class="auth-legal-modal-backdrop" data-legal-modal-close></div>

            <section
                id="privacy-notice-modal"
                class="auth-legal-modal-card"
                role="dialog"
                aria-modal="true"
                aria-hidden="true"
                aria-labelledby="privacy-notice-modal-title"
                tabindex="-1"
                data-legal-modal
            >
                <div class="auth-legal-modal-header">
                    <h5 class="fw-bold mb-0" id="privacy-notice-modal-title">Privacy Notice</h5>
                    <button type="button" class="auth-legal-modal-close" aria-label="Close privacy notice" data-legal-modal-close>
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="auth-legal-modal-body small text-muted">
                    <p>
                        We collect your name, email address, encrypted password, and reservation activity so we can create your
                        account, manage pickups, send service updates, and protect the platform from misuse.
                    </p>
                    <p class="mb-0">
                        We only use this information for marketplace operations, customer support, security, and legally required
                        recordkeeping. Access is limited to authorized store personnel, and you may request updates to your personal
                        information through your profile or by contacting the shop.
                    </p>
                </div>
            </section>

            <section
                id="terms-modal"
                class="auth-legal-modal-card"
                role="dialog"
                aria-modal="true"
                aria-hidden="true"
                aria-labelledby="terms-modal-title"
                tabindex="-1"
                data-legal-modal
            >
                <div class="auth-legal-modal-header">
                    <h5 class="fw-bold mb-0" id="terms-modal-title">Terms and Conditions</h5>
                    <button type="button" class="auth-legal-modal-close" aria-label="Close terms and conditions" data-legal-modal-close>
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="auth-legal-modal-body small text-muted">
                    <p>
                        By creating an account, you agree to provide accurate details, keep your credentials secure, and use the
                        marketplace only for lawful transactions.
                    </p>
                    <p class="mb-0">
                        Reservations, pickup schedules, and inventory availability remain subject to store confirmation, and the shop
                        may suspend accounts that abuse the system, submit false information, or interfere with store operations.
                    </p>
                </div>
            </section>
        </div>
    </x-slot>
</x-guest-layout>
