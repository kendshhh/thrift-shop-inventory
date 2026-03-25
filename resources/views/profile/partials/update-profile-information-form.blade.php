<p class="text-muted small mb-4">Update your account profile information and email address.</p>

<form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

<form method="post" action="{{ route('profile.update') }}">
    @csrf
    @method('patch')

    <div class="mb-3">
        <label for="name" class="form-label fw-medium">Name</label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-4">
        <label for="email" class="form-label fw-medium">Email address</label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-2 small text-warning">
                Your email is unverified.
                <button form="send-verification" class="btn btn-link btn-sm p-0 text-warning">Resend verification email.</button>
            </div>
            @if (session('status') === 'verification-link-sent')
                <div class="mt-1 small text-success">A new verification link has been sent.</div>
            @endif
        @endif
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        @if (session('status') === 'profile-updated')
            <span class="text-success small"><i class="bi bi-check-circle me-1"></i>Saved!</span>
        @endif
    </div>
</form>
