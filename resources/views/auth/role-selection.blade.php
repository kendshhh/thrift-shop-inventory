<x-guest-layout>
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <div class="auth-glass auth-role-selection">
        <div class="text-center auth-role-header">
            <h3 class="fw-bold mb-0">Please select your role</h3>
        </div>

        <div class="auth-role-grid">
            @foreach ($cards as $card)
                <div class="auth-role-grid-item">
                    <a
                        href="{{ $card['href'] }}"
                        aria-label="{{ $card['title'] }}. {{ $card['description'] }}"
                        class="auth-choice-card auth-choice-card-{{ $card['role'] }} text-decoration-none"
                    >
                        <div class="auth-choice-card-body">
                            <div class="auth-choice-icon" aria-hidden="true">
                                <i class="bi {{ $card['icon'] }}"></i>
                            </div>
                            <div class="auth-choice-copy">
                                <h5 class="auth-choice-title fw-bold text-dark mb-0">{{ $card['title'] }}</h5>
                                <p class="auth-choice-description mb-0">{{ $card['description'] }}</p>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        @if (app()->environment(['local', 'testing']))
            <hr class="mt-4">
            <div class="small text-muted text-center">
                <div class="fw-semibold mb-1">Default accounts</div>
                <div>Admin: <code>{{ env('DEFAULT_ADMIN_EMAIL', 'admin@thriftshop.local') }}</code> / <code>{{ env('DEFAULT_ADMIN_PASSWORD', '123') }}</code></div>
                <div>Customer: <code>{{ env('DEFAULT_CUSTOMER_EMAIL', 'customer@thriftshop.local') }}</code> / <code>{{ env('DEFAULT_CUSTOMER_PASSWORD', '123') }}</code></div>
            </div>
        @endif
    </div>
</x-guest-layout>
