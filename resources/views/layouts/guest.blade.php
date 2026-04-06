<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@php
        $brandName = data_get($branding ?? [], 'brand_name', config('app.name', 'Everdarling'));
        $logoUrl = data_get($branding ?? [], 'logo_url');
        $primaryColor = data_get($branding ?? [], 'primary_color', '#0EA5E9');
        $secondaryColor = data_get($branding ?? [], 'secondary_color', '#2563EB');
    @endphp
    <title>{{ $brandName }}</title>
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
<img src="/images/everdarling_logo.png" alt="Everdarling logo" class="navbar-brand-logo">
                <!-- Brand text replaced with logo -->
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#guestNavbar" aria-controls="guestNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="guestNavbar">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    @auth
                        <li class="nav-item"><a class="btn btn-primary btn-sm px-3" href="{{ route('dashboard') }}">Dashboard</a></li>
                    @else
                        <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Sign In</a></li>
                        @if (Route::has('register'))
                            <li class="nav-item"><a class="btn btn-primary btn-sm px-3" href="{{ route('register') }}">Register</a></li>
                        @endif
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main class="guest-main">
        <div class="container app-container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-9 col-lg-6 col-xl-5">
                    <div class="text-center mb-4">
                        <a href="{{ url('/') }}" class="text-decoration-none text-dark">
                            <i class="bi bi-bag-heart-fill" style="font-size: 2.4rem; color: var(--accent-color);"></i>
                            <img src="/images/everdarling_logo.png" alt="Everdarling logo" class="navbar-brand-logo footer-logo">
                        </a>
                    </div>
                    <div class="card glass-card auth-panel border-0">
                        <div class="card-body p-4 p-lg-5">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
