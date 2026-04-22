<x-guest-layout>
    <div class="auth-glass text-center">
        <span class="status-badge status-badge-ready rounded-pill px-3 py-2 mb-3 d-inline-flex align-items-center">
            <i class="bi bi-shield-exclamation me-2"></i>Error {{ $status }}
        </span>
        <h4 class="mb-2 fw-bold">{{ $title }}</h4>
        <p class="text-muted mb-4">{{ $message }}</p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary rounded-pill">Go Back</a>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary rounded-pill">Dashboard</a>
            @else
                <a href="{{ route('auth.role-selection', ['intent' => 'login']) }}" class="btn btn-primary rounded-pill">Sign In</a>
            @endauth
        </div>
    </div>
</x-guest-layout>
