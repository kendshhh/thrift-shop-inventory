<x-guest-layout>
    <div class="auth-glass">
        <div class="text-center auth-copy-stack">
            <span class="status-badge status-badge-ready rounded-pill px-3 py-2 mb-3 d-inline-flex align-items-center">
                <i class="bi bi-envelope-check me-2"></i>Email verification
            </span>
            <h4 class="mb-2 fw-bold">Verify Your Email</h4>
            <p class="text-muted mb-0">Please verify your email address by clicking the link we sent you.</p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success small">A new verification link has been sent to your email address.</div>
        @endif

        <div class="d-flex flex-column flex-sm-row gap-3 align-items-center justify-content-center">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary">Resend Verification Email</button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-link text-muted p-0">Log Out</button>
            </form>
        </div>
    </div>
</x-guest-layout>
