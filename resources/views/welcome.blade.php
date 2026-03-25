<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $brandName = data_get($branding ?? [], 'brand_name', config('app.name', 'Thrift Shop'));
        $brandTagline = data_get($branding ?? [], 'brand_tagline') ?: 'Sleek Modern Marketplace';
        $logoUrl = data_get($branding ?? [], 'logo_url');
        $primaryColor = data_get($branding ?? [], 'primary_color', '#0EA5E9');
        $secondaryColor = data_get($branding ?? [], 'secondary_color', '#2563EB');
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brandName }} | {{ $brandTagline }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --accent-color: {{ $primaryColor }};
            --accent-deep: {{ $secondaryColor }};
            --accent-gradient: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-deep) 100%);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top navbar-modern">
        <div class="container app-container">
            <a class="navbar-brand fw-bold fs-4 d-inline-flex align-items-center" href="{{ url('/') }}">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $brandName }} logo" class="navbar-brand-logo">
                @endif
                <span>{{ $brandName }}</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#welcomeNav" aria-controls="welcomeNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="welcomeNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link mx-lg-2" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link mx-lg-2" href="#solutions">Solutions</a></li>
                    <li class="nav-item"><a class="nav-link mx-lg-2" href="#about">About</a></li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        @auth
                            <a class="btn btn-primary rounded-pill px-4" href="{{ route('dashboard') }}">Dashboard</a>
                        @else
                            <a class="btn btn-outline-custom rounded-pill px-4" href="{{ route('login') }}">Sign In</a>
                        @endauth
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="container app-container text-center">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <span class="badge rounded-pill bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-3 py-2 mb-4">
                        <i class="bi bi-stars me-2"></i>Fresh drops and featured deals every week
                    </span>
                    <h1 class="display-2 fw-bold mb-4" style="letter-spacing: -0.04em; line-height: 1.1;">
                        Design the future <br><span class="text-gradient">of sustainable shopping.</span>
                    </h1>
                    <p class="lead text-muted mb-5 fs-4 mx-auto" style="max-width: 720px;">
                        Discover curated second-hand finds, reserve in seconds, and pick up with confidence. Minimal effort, maximum impact.
                    </p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                        <a href="{{ route('items.index') }}" class="btn btn-primary btn-lg px-5">Browse Items</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-custom btn-lg px-5">Go to Dashboard <i class="bi bi-arrow-right ms-2"></i></a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-outline-custom btn-lg px-5">Create Account <i class="bi bi-arrow-right ms-2"></i></a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section id="features" class="py-5">
        <div class="container app-container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="fw-bold display-5 mb-3">Everything you need</h2>
                    <p class="text-muted fs-5">A cleaner way to shop pre-loved items without sacrificing quality.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="glass-card p-5 h-100">
                        <div class="feature-icon-wrapper"><i class="bi bi-lightning-charge feature-icon"></i></div>
                        <h4 class="fw-bold mb-3">Fast Reservation Flow</h4>
                        <p class="text-muted mb-0">Reserve items in a few clicks and skip uncertainty at pickup.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-5 h-100">
                        <div class="feature-icon-wrapper"><i class="bi bi-shield-check feature-icon"></i></div>
                        <h4 class="fw-bold mb-3">Trusted Item Status</h4>
                        <p class="text-muted mb-0">Track condition, availability, and reservation status in real time.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-5 h-100">
                        <div class="feature-icon-wrapper"><i class="bi bi-recycle feature-icon"></i></div>
                        <h4 class="fw-bold mb-3">Sustainable by Default</h4>
                        <p class="text-muted mb-0">Extend product lifecycles and reduce waste with every purchase.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="solutions" class="py-5 mb-5">
        <div class="container app-container">
            <div class="glass-card p-5 text-center overflow-hidden position-relative border-0 shadow-lg" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                <div class="row justify-content-center py-4">
                    <div class="col-lg-8">
                        <h2 class="display-6 fw-bold mb-4">Ready to elevate your thrift experience?</h2>
                        <p class="text-muted mb-5 fs-5">Join shoppers discovering smarter and greener finds every day.</p>
                        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                            <a href="{{ route('items.index') }}" class="btn btn-primary px-4">Explore Inventory</a>
                            @auth
                                <a href="{{ route('dashboard') }}" class="btn btn-outline-custom px-4">Open Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-outline-custom px-4">Sign In</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer id="about" class="footer-modern">
        <div class="container app-container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <h5 class="fw-bold mb-4 d-inline-flex align-items-center">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $brandName }} logo" class="navbar-brand-logo">
                        @endif
                        <span>{{ $brandName }}</span>
                    </h5>
                    <p class="text-muted">Building a cleaner, faster, and more delightful way to buy quality second-hand goods.</p>
                    <div class="d-flex gap-3 mt-4">
                        <a href="#" class="text-muted fs-5"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="text-muted fs-5"><i class="bi bi-github"></i></a>
                        <a href="#" class="text-muted fs-5"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2 offset-lg-2">
                    <h6 class="fw-bold mb-3">Marketplace</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('items.index') }}" class="text-muted text-decoration-none small d-block mb-2">Browse Items</a></li>
                        <li><a href="{{ route('login') }}" class="text-muted text-decoration-none small d-block mb-2">Sign In</a></li>
                        @if (Route::has('register'))
                            <li><a href="{{ route('register') }}" class="text-muted text-decoration-none small d-block mb-2">Create Account</a></li>
                        @endif
                    </ul>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <h6 class="fw-bold mb-3">Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('login') }}" class="text-muted text-decoration-none small d-block mb-2">Account Access</a></li>
                        <li><a href="{{ route('items.index') }}" class="text-muted text-decoration-none small d-block mb-2">Reservation Guide</a></li>
                        <li><a href="{{ route('items.index') }}" class="text-muted text-decoration-none small d-block mb-2">Available Categories</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <h6 class="fw-bold mb-3">Company</h6>
                    <ul class="list-unstyled">
                        <li><a href="#about" class="text-muted text-decoration-none small d-block mb-2">About</a></li>
                        <li><a href="#about" class="text-muted text-decoration-none small d-block mb-2">Privacy</a></li>
                        <li><a href="#about" class="text-muted text-decoration-none small d-block mb-2">Terms</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-5 opacity-10">
            <div class="row">
                <div class="col-12 text-center text-muted small">
                    <p class="mb-0">&copy; {{ date('Y') }} {{ $brandName }}. Crafted for a modern, sustainable marketplace.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
