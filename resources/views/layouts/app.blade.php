<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@php
        $brandName = data_get($branding ?? [], 'brand_name', config('app.name', 'Everdarling'));
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
    @include('layouts.navigation')

    <main class="app-main">
        <div class="container app-container">
            @if (isset($header))
                <div class="page-header-shell">
                    {{ $header }}
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
