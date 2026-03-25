<x-guest-layout>
    <h4 class="mb-2 fw-bold">Verify Your Email</h4>
    <p class="text-muted small mb-4">Thanks for signing up! Please verify your email address by clicking the link we sent you.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success small">A new verification link has been sent to your email address.</div>
    @endif

    <div class="d-flex gap-3 align-items-center">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Resend Verification Email</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link text-muted p-0">Log Out</button>
        </form>
    </div>
</x-guest-layout>
