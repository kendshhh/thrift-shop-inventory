<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@php
        $brandName = data_get($branding ?? [], 'brand_name', config('app.name', 'Everdarling'));
        $primaryColor = data_get($branding ?? [], 'primary_color', '#0EA5E9');
    $primaryRgb = data_get($branding ?? [], 'primary_rgb', '14, 165, 233');
        $secondaryColor = data_get($branding ?? [], 'secondary_color', '#2563EB');
    $secondaryRgb = data_get($branding ?? [], 'secondary_rgb', '37, 99, 235');
    @endphp
    <title>{{ $brandName }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --accent-color: {{ $primaryColor }};
            --accent-rgb: {{ $primaryRgb }};
            --accent-deep: {{ $secondaryColor }};
            --accent-deep-rgb: {{ $secondaryRgb }};
            --accent-gradient: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-deep) 100%);
            --brand-surface: linear-gradient(145deg, rgba(255, 255, 255, 0.62) 0%, rgba(var(--accent-rgb), 0.14) 18%, rgba(255, 255, 255, 0.58) 52%, rgba(var(--accent-deep-rgb), 0.1) 100%);
            --brand-surface-strong: linear-gradient(145deg, rgba(255, 255, 255, 0.72) 0%, rgba(var(--accent-rgb), 0.18) 22%, rgba(var(--accent-deep-rgb), 0.12) 58%, rgba(255, 255, 255, 0.84) 100%);
            --brand-border: rgba(var(--accent-rgb), 0.22);
            --brand-glow-soft: rgba(var(--accent-rgb), 0.2);
            --brand-glow-strong: rgba(var(--accent-deep-rgb), 0.18);
        }
    </style>
</head>
<body class="app-shell">
    <div class="brand-ambient" aria-hidden="true">
        <span class="brand-ambient-orb brand-ambient-orb-primary"></span>
        <span class="brand-ambient-orb brand-ambient-orb-secondary"></span>
        <span class="brand-ambient-grid"></span>
    </div>
    @include('layouts.navigation')

    <main class="app-main">
        <div class="container app-container">
            @if (isset($header))
                <div class="page-header-shell">
                    {{ $header }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->has('app'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    {{ $errors->first('app') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <x-validation-summary />

            {{ $slot }}
        </div>
    </main>

    <div class="image-lightbox" id="image-lightbox" aria-hidden="true">
        <div class="image-lightbox-backdrop" data-lightbox-close></div>
        <div class="image-lightbox-dialog" role="dialog" aria-modal="true" aria-labelledby="image-lightbox-title">
            <button type="button" class="image-lightbox-close" aria-label="Close image preview" data-lightbox-close>
                <i class="bi bi-x-lg"></i>
            </button>
            <div class="image-lightbox-frame">
                <img src="" alt="" class="image-lightbox-image" id="image-lightbox-image">
            </div>
            <div class="image-lightbox-caption" id="image-lightbox-title"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
